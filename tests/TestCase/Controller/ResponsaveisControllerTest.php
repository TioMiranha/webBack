<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class ResponsaveisControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Tipos', 'app.Users', 'app.Alunos', 'app.Responsaveis'];

    public function testScreensRenderTheRelationships(): void
    {
        foreach (
            ['/responsaveis', '/responsaveis/view/1', '/responsaveis/add', '/responsaveis/edit/1',
            '/alunos', '/alunos/view/1', '/alunos/add', '/alunos/edit/1', '/users/view/1'] as $url
        ) {
            $this->get($url);
            $this->assertResponseOk();
        }
        $this->get('/alunos/view/1');
        $this->assertResponseContains('/responsaveis/view/1');
        $this->assertResponseContains('/users/view/1');
    }

    public function testFormReportsDuplicateAndSavesValidLink(): void
    {
        $this->enableCsrfToken();
        $this->post('/responsaveis/add', ['users_id' => 1, 'alunos_id' => 1]);
        $this->assertResponseOk();
        $this->assertResponseContains('já está vinculado');
        $table = $this->getTableLocator()->get('Responsaveis');
        $table->deleteOrFail($table->get(1));
        $this->post('/responsaveis/add', ['users_id' => 1, 'alunos_id' => 1]);
        $this->assertRedirect(['controller' => 'Responsaveis', 'action' => 'index']);
        $this->assertSame(1, $table->find()->count());
    }

    public function testSoftDeleteAndRestorePreserveLink(): void
    {
        $this->enableCsrfToken();
        $this->post('/responsaveis/delete/1');
        $this->assertRedirect();
        $table = $this->getTableLocator()->get('Responsaveis');
        $this->assertNotNull($table->get(1)->deleted);
        $this->post('/responsaveis/active/1');
        $this->assertRedirect();
        $this->assertNull($table->get(1)->deleted);
        $this->post('/alunos/delete/1');
        $this->assertRedirect();
        $this->assertSame(1, $table->get(1)->alunos_id);
    }

    public function testCompleteRegistrationThroughForms(): void
    {
        $this->enableCsrfToken();
        $this->post('/users/add', [
            'cpf' => 12345678901, 'password' => 'senha123', 'nome' => 'Responsável novo',
            'email' => 'novo@example.com', 'telefone' => 65999999999, 'tipo_id' => 1,
        ]);
        $this->assertRedirect();
        $user = $this->getTableLocator()->get('Users')->find()->where(['email' => 'novo@example.com'])->firstOrFail();
        $this->post('/alunos/add', [
            'nome' => 'Aluno novo', 'matricula' => 1234, 'curso' => 'Informática', 'situacao' => 'Ativo',
            'emaila' => 'aluno@ifmt.edu.br', 'emailp' => 'aluno@example.com', 'anop' => '2026',
        ]);
        $this->assertRedirect();
        $aluno = $this->getTableLocator()->get('Alunos')->find()->where(['matricula' => 1234])->firstOrFail();
        $this->post('/responsaveis/add', ['users_id' => $user->id, 'alunos_id' => $aluno->id]);
        $this->assertRedirect();
        $this->get('/alunos/view/' . $aluno->id);
        $this->assertResponseOk();
        $this->assertResponseContains('Responsável novo');
    }
}
