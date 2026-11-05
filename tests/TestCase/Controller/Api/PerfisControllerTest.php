<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class PerfisControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Tipos'];

    public function testCrudUsesTiposAndRouteId(): void
    {
        $this->post('/api/perfis/adicionar.json', ['nome' => 'Responsável']);
        $this->assertResponseCode(201);
        $id = json_decode((string)$this->_response->getBody(), true)['data']['id'];
        $this->patch('/api/perfis/editar/' . $id . '.json', ['nome' => 'Família', 'id' => 1]);
        $this->assertResponseOk();
        $this->get('/api/perfis/buscar/' . $id . '.json');
        $this->assertResponseOk();
        $this->assertSame('Família', json_decode((string)$this->_response->getBody(), true)['data']['nome']);
        $this->post('/api/perfis/deletar/' . $id . '.json');
        $this->assertResponseOk();
        $this->get('/api/perfis/listar.json');
        $this->assertResponseOk();
        $this->assertCount(1, json_decode((string)$this->_response->getBody(), true)['data']);
        $this->post('/api/perfis/reativar/' . $id . '.json');
        $this->assertResponseOk();
    }
}
