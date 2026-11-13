<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class SetoresControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Setors'];

    public function testCrudAndActivation(): void
    {
        $this->post('/api/setores/adicionar.json', [
            'nome' => 'Financeiro', 'id' => 100, 'deleted' => '2026-11-12 12:00:00',
        ]);
        $this->assertResponseCode(201);
        $this->assertContentType('application/json');
        $data = json_decode((string)$this->_response->getBody(), true)['data'];
        $id = $data['id'];
        $this->assertNotSame(100, $id);
        $table = $this->getTableLocator()->get('Setors');
        $this->assertNull($table->get($id)->deleted);
        $this->assertNotNull($table->get($id)->created);

        $this->get('/api/setores/buscar/' . $id . '.json');
        $this->assertResponseOk();
        $this->assertSame('Financeiro', json_decode((string)$this->_response->getBody(), true)['data']['nome']);

        $this->patch('/api/setores/editar/' . $id . '.json', ['nome' => 'Administração']);
        $this->assertResponseOk();
        $this->assertSame('Administração', $table->get($id)->nome);

        $this->delete('/api/setores/deletar/' . $id . '.json');
        $this->assertResponseOk();
        $this->assertNotNull($table->get($id)->deleted);
        $this->get('/api/setores/listar.json?id=' . $id);
        $this->assertResponseOk();
        $this->assertSame([], json_decode((string)$this->_response->getBody(), true)['data']);

        $this->post('/api/setores/reativar/' . $id . '.json');
        $this->assertResponseOk();
        $this->assertNull($table->get($id)->deleted);
        $this->get('/api/setores/listar.json?id=' . $id);
        $this->assertResponseOk();
        $this->assertSame('Administração', json_decode((string)$this->_response->getBody(), true)['data'][0]['setor']);
    }

    public function testListFiltersAndExcludesInactiveRecords(): void
    {
        $this->get('/api/setores/listar.json');
        $this->assertResponseOk();
        $this->assertSame(
            [['id' => 1, 'setor' => 'Secretaria']],
            json_decode((string)$this->_response->getBody(), true)['data'],
        );

        $this->post('/api/setores/listar.json', ['nome' => 'Secretaria', 'id' => 1]);
        $this->assertResponseOk();
        $this->assertCount(1, json_decode((string)$this->_response->getBody(), true)['data']);

        $this->get('/api/setores/listar.json?nome=Biblioteca');
        $this->assertResponseOk();
        $this->assertSame([], json_decode((string)$this->_response->getBody(), true)['data']);

        $this->get('/api/setores/listar.json?nome=Secretaria%27%20OR%201=1--');
        $this->assertResponseOk();
        $this->assertSame([], json_decode((string)$this->_response->getBody(), true)['data']);

        $this->get('/api/setores/listar.json?id[]=1');
        $this->assertResponseCode(422);
    }

    public function testFindAliasAndDeactivationAlias(): void
    {
        $this->get('/api/setores/listar/1.json');
        $this->assertResponseOk();
        $this->assertSame('Secretaria', json_decode((string)$this->_response->getBody(), true)['data']['nome']);

        $this->post('/api/setores/desativar/1.json');
        $this->assertResponseOk();
        $this->assertNotNull($this->getTableLocator()->get('Setors')->get(1)->deleted);
    }

    public function testValidationAndProtectedFields(): void
    {
        foreach ([[], ['nome' => ''], ['nome' => str_repeat('a', 256)], ['nome' => ['inválido']]] as $data) {
            $this->post('/api/setores/adicionar.json', $data);
            $this->assertResponseCode(422);
            $this->assertArrayHasKey('nome', json_decode((string)$this->_response->getBody(), true)['errors']);
        }

        $this->patch('/api/setores/editar/1.json', ['nome' => '']);
        $this->assertResponseCode(422);
        $this->assertSame('Secretaria', $this->getTableLocator()->get('Setors')->get(1)->nome);

        $this->patch('/api/setores/editar/1.json', ['nome' => 'Secretaria Geral', 'deleted' => '2026-11-12 12:00:00']);
        $this->assertResponseOk();
        $this->assertNull($this->getTableLocator()->get('Setors')->get(1)->deleted);
    }

    public function testMissingRecords(): void
    {
        $this->get('/api/setores/buscar/999.json');
        $this->assertResponseCode(404);
        $this->assertContentType('application/json');

        foreach (['editar', 'deletar', 'reativar'] as $action) {
            $this->post('/api/setores/' . $action . '/999.json', ['nome' => 'Teste']);
            $this->assertResponseCode(404);
        }
    }

    public function testRejectsUnsupportedMethods(): void
    {
        foreach (['adicionar', 'editar/1', 'deletar/1', 'reativar/1'] as $action) {
            $this->get('/api/setores/' . $action . '.json');
            $this->assertResponseCode(405);
        }
        $this->post('/api/setores/buscar/1.json');
        $this->assertResponseCode(405);
    }
}
