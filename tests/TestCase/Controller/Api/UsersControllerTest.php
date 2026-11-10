<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class UsersControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Tipos', 'app.Users', 'app.Alunos', 'app.Responsaveis'];

    public function testDirectActionsRejectGetMutationsAndReturnMissingRecords(): void
    {
        foreach (['usuarios', 'alunos', 'responsaveis'] as $resource) {
            foreach (['adicionar', 'editar/1', 'desativar/1', 'reativar/1'] as $action) {
                $this->get('/api/' . $resource . '/' . $action . '.json');
                $this->assertResponseCode(405);
            }
            $this->get('/api/' . $resource . '/buscar/999.json');
            $this->assertResponseCode(404);
            $this->assertContentType('application/json');
        }
        foreach (['Users', 'Alunos', 'Responsaveis'] as $alias) {
            $this->assertNull($this->getTableLocator()->get($alias)->get(1)->deleted);
        }
    }

    public function testSqlFindIncludesAllStudentsAndHandlesUsersWithoutLinks(): void
    {
        $table = $this->getTableLocator()->get('Alunos');
        $data = $table->get(1)->toArray();
        unset($data['id']);
        $data['matricula'] = 2;
        $aluno = $table->saveOrFail($table->newEntity($data));
        $links = $this->getTableLocator()->get('Responsaveis');
        $links->saveOrFail($links->newEntity(['users_id' => 1, 'alunos_id' => $aluno->id]));
        $this->get('/api/usuarios/buscar/1.json');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true)['data'];
        $this->assertCount(2, $data['aluno']);
        $this->assertCount(2, $data['responsaveis']);
        $this->assertSame($aluno->id, $data['responsaveis'][1]['aluno']['id']);
        $this->assertResponseNotContains('password');

        foreach ($links->find()->all() as $link) {
            $links->deleteOrFail($link);
        }
        $this->get('/api/usuarios/buscar/1.json');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true)['data'];
        $this->assertSame([], $data['aluno']);
        $this->assertSame([], $data['responsaveis']);
        $this->get('/api/usuarios/listar.json?nome=' . rawurlencode("' OR 1=1 --"));
        $this->assertResponseOk();
        $this->assertSame([], json_decode((string)$this->_response->getBody(), true)['data']);
    }

    public function testCreateHashesPasswordAndEditKeepsItWhenEmpty(): void
    {
        $this->post('/api/usuarios/adicionar.json', [
            'cpf' => 12345678901, 'password' => 'senha123', 'nome' => 'Maria',
            'email' => 'maria@example.com', 'telefone' => 65999999999, 'tipo_id' => 1,
        ]);
        $this->assertResponseCode(201);
        $data = json_decode((string)$this->_response->getBody(), true)['data'];
        $this->assertArrayNotHasKey('password', $data);
        $users = $this->getTableLocator()->get('Users');
        $hash = $users->get($data['id'])->password;
        $this->assertTrue(password_verify('senha123', $hash));
        $this->patch('/api/usuarios/editar/' . $data['id'] . '.json', ['id' => 1, 'nome' => 'Maria Silva', 'password' => '']);
        $this->assertResponseOk();
        $this->assertSame($hash, $users->get($data['id'])->password);
        $this->assertSame('Maria Silva', $users->get($data['id'])->nome);
        $this->assertNotSame('Maria Silva', $users->get(1)->nome);
    }

    public function testSoftDeleteKeepsResponsibleLinkAndRestores(): void
    {
        $this->post('/api/usuarios/desativar/1.json');
        $this->assertResponseOk();
        $this->get('/api/usuarios/listar.json');
        $this->assertResponseOk();
        $this->assertSame([], json_decode((string)$this->_response->getBody(), true)['data']);
        $this->assertSame(1, $this->getTableLocator()->get('Responsaveis')->get(1)->users_id);
        $this->post('/api/usuarios/reativar/1.json');
        $this->assertResponseOk();
        $this->get('/api/usuarios/buscar/1.json');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true)['data'];
        $this->assertSame(1, $data['responsaveis'][0]['aluno']['id']);
    }
}
