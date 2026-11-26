<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class CursosControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Cursos'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->getTableLocator()->get('Cursos')->updateAll(['deleted' => null], ['id' => 1]);
    }

    private function responseData(): mixed
    {
        return json_decode((string)$this->_response->getBody(), true)['data'];
    }

    public function testList(): void
    {
        $this->get('/api/cursos/listar.json');
        $this->assertResponseOk();
        $this->assertCount(1, $this->responseData());
        $this->get('/api/cursos/listar.json?id=999');
        $this->assertResponseOk();
        $this->assertSame([], $this->responseData());
    }

    public function testFind(): void
    {
        $this->get('/api/cursos/buscar/1.json');
        $this->assertResponseOk();
        $this->assertSame(1, $this->responseData()['id']);
        $this->get('/api/cursos/buscar/999.json');
        $this->assertResponseCode(404);
    }

    public function testAdd(): void
    {
        $this->post('/api/cursos/adicionar.json', ['nome' => 'Técnico em Informática', 'id' => 999]);
        $this->assertResponseCode(201);
        $this->assertSame('Técnico em Informática', $this->responseData()['nome']);
        $this->assertNotSame(999, $this->responseData()['id']);
        $this->post('/api/cursos/adicionar.json', ['nome' => '']);
        $this->assertResponseCode(422);
        $this->assertSame(2, $this->getTableLocator()->get('Cursos')->find()->count());
        $this->get('/api/cursos/adicionar.json');
        $this->assertResponseCode(405);
    }

    public function testEdit(): void
    {
        $this->patch('/api/cursos/editar/1.json', ['nome' => 'Administração']);
        $this->assertResponseOk();
        $this->assertSame('Administração', $this->responseData()['nome']);
        $this->assertSame(1, $this->getTableLocator()->get('Cursos')->find()->count());
        $this->patch('/api/cursos/editar/999.json', ['nome' => 'Inexistente']);
        $this->assertResponseCode(404);
    }

    public function testDeleteAndReactivate(): void
    {
        $this->delete('/api/cursos/deletar/1.json');
        $this->assertResponseOk();
        $this->assertNotNull($this->responseData()['deleted']);
        $this->get('/api/cursos/listar.json');
        $this->assertResponseOk();
        $this->assertSame([], $this->responseData());
        $this->post('/api/cursos/reativar/1.json');
        $this->assertResponseOk();
        $this->assertNull($this->responseData()['deleted']);
        $this->get('/api/cursos/listar.json');
        $this->assertCount(1, $this->responseData());
        $this->delete('/api/cursos/deletar/999.json');
        $this->assertResponseCode(404);
        $this->post('/api/cursos/reativar/999.json');
        $this->assertResponseCode(404);
    }
}
