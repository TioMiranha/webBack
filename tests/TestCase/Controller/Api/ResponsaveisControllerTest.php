<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class ResponsaveisControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Tipos', 'app.Users', 'app.Alunos', 'app.Responsaveis'];

    public function testListAndStudentContainTheUserWithoutPassword(): void
    {
        $this->get('/api/responsaveis/listar.json?alunos_id=1');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true)['data'];
        $this->assertCount(1, $data);
        $this->assertSame('responsavel@example.com', $data[0]['user']['email']);
        $this->assertArrayNotHasKey('password', $data[0]['user']);

        $this->get('/api/alunos/buscar/1.json');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true)['data'];
        $this->assertSame(1, $data['matricula']);
        $this->assertSame(1, $data['responsaveis'][0]['user']['id']);
        $this->assertArrayNotHasKey('password', $data['responsaveis'][0]['user']);
    }

    public function testAddEditDeactivateAndReactivate(): void
    {
        $table = $this->getTableLocator()->get('Alunos');
        $copy = $table->get(1)->toArray();
        unset($copy['id']);
        $copy['matricula'] = 2;
        $aluno = $table->saveOrFail($table->newEntity($copy));
        $this->configRequest(['headers' => ['Content-Type' => 'application/json']]);
        $this->post('/api/responsaveis/adicionar.json', json_encode(['users_id' => 1, 'alunos_id' => $aluno->id]));
        $this->assertResponseCode(201);
        $id = json_decode((string)$this->_response->getBody(), true)['data']['id'];
        $this->patch('/api/responsaveis/editar/' . $id . '.json', ['alunos_id' => $aluno->id]);
        $this->assertResponseOk();
        $this->delete('/api/responsaveis/deletar/' . $id . '.json');
        $this->assertResponseOk();
        $this->get('/api/responsaveis/listar.json?alunos_id=' . $aluno->id);
        $this->assertResponseOk();
        $this->assertSame([], json_decode((string)$this->_response->getBody(), true)['data']);
        $this->post('/api/responsaveis/reativar/' . $id . '.json');
        $this->assertResponseOk();
        $this->assertNull($this->getTableLocator()->get('Responsaveis')->get($id)->deleted);
    }

    public function testRejectsDuplicateAndInvalidLinks(): void
    {
        foreach (
            [['users_id' => 1, 'alunos_id' => 1], ['users_id' => 999, 'alunos_id' => 1],
            ['users_id' => 1, 'alunos_id' => 999], ['users_id' => -1], []] as $data
        ) {
            $this->post('/api/responsaveis/adicionar.json', $data);
            $this->assertResponseCode(422);
            $this->assertNotEmpty(json_decode((string)$this->_response->getBody(), true)['errors']);
        }
    }

    public function testReactivationRequiresActiveParents(): void
    {
        $this->post('/api/responsaveis/desativar/1.json');
        $this->assertResponseOk();
        $users = $this->getTableLocator()->get('Users');
        $user = $users->get(1);
        $user->deleted = DateTime::now();
        $users->saveOrFail($user);
        $this->post('/api/responsaveis/reativar/1.json');
        $this->assertResponseCode(422);
        $this->assertNotNull($this->getTableLocator()->get('Responsaveis')->get(1)->deleted);
    }

    public function testFilterMissingRecordAndMethodRestrictions(): void
    {
        $this->get('/api/responsaveis/listar.json?users_id=999');
        $this->assertResponseOk();
        $this->assertSame([], json_decode((string)$this->_response->getBody(), true)['data']);
        $this->get('/api/responsaveis/buscar/999.json');
        $this->assertResponseCode(404);
        $this->get('/api/responsaveis/deletar/1.json');
        $this->assertResponseCode(405);
        $this->get('/api/responsaveis/adicionar.json');
        $this->assertResponseCode(405);
    }

    public function testNewLinksRequireActiveUsersAndStudents(): void
    {
        $links = $this->getTableLocator()->get('Responsaveis');
        $links->deleteOrFail($links->get(1));
        foreach (['Users' => 'users_id', 'Alunos' => 'alunos_id'] as $alias => $field) {
            $table = $this->getTableLocator()->get($alias);
            $parent = $table->get(1);
            $parent->deleted = DateTime::now();
            $table->saveOrFail($parent);
            $this->post('/api/responsaveis/adicionar.json', ['users_id' => 1, 'alunos_id' => 1]);
            $this->assertResponseCode(422);
            $this->assertArrayHasKey($field, json_decode((string)$this->_response->getBody(), true)['errors']);
            $parent->deleted = null;
            $table->saveOrFail($parent);
        }
    }
}
