<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class CoordenadoresControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Tipos', 'app.Users', 'app.Cursos', 'app.Coordenadores'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->getTableLocator()->get('Users')->updateAll(['nome' => 'Jorgin'], ['id' => 1]);
        $this->getTableLocator()->get('Cursos')->updateAll(['nome' => 'Informática', 'deleted' => null], ['id' => 1]);
        $this->getTableLocator()->get('Coordenadores')->updateAll(['deleted' => null], ['id' => 1]);
    }

    public function testFindUsesLinkIdAndReturnsItsUserAndCourse(): void
    {
        $userId = $this->createUser();
        $this->getTableLocator()->get('Coordenadores')->updateAll(['users_id' => $userId], ['id' => 1]);
        $this->get('/api/coordenadores/buscar/1.json');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true)['data'];
        $this->assertSame(1, $data['id']);
        $this->assertSame($userId, $data['users_id']);
        $this->assertSame(1, $data['curso_id']);
        $this->assertSame('Daniel', $data['nome']);
        $this->assertSame('Daniel', $data['coordenador']);
        $this->assertSame('Informática', $data['curso']);
        $this->assertResponseNotContains('password');
        $this->get('/api/coordenadores/buscar/999.json');
        $this->assertResponseCode(404);
    }

    public function testListFiltersUseTheCorrectParameterAndColumn(): void
    {
        foreach (['id' => '1', 'coordenador' => 'Jorgin', 'curso' => 'Informática'] as $field => $value) {
            $this->get('/api/coordenadores/listar.json?' . http_build_query([$field => $value]));
            $this->assertResponseOk();
            $this->assertCount(1, json_decode((string)$this->_response->getBody(), true)['data']);
            $this->get('/api/coordenadores/listar.json?' . http_build_query([$field => 'inexistente']));
            $this->assertResponseOk();
            $this->assertSame([], json_decode((string)$this->_response->getBody(), true)['data']);
        }
        $this->post('/api/coordenadores/listar.json', ['coordenador' => 'Jorgin', 'curso' => 'Informática']);
        $this->assertResponseOk();
        $this->assertCount(1, json_decode((string)$this->_response->getBody(), true)['data']);
        $this->get('/api/coordenadores/listar.json?coordenador=' . rawurlencode("' OR 1=1 --"));
        $this->assertResponseOk();
        $this->assertSame([], json_decode((string)$this->_response->getBody(), true)['data']);
        $this->get('/api/coordenadores/listar.json?curso[]=1');
        $this->assertResponseCode(422);
    }

    public function testAddCreatesLinkAndRejectsGet(): void
    {
        $this->get('/api/coordenadores/adicionar.json?users_id=1&curso_id=1');
        $this->assertResponseCode(405);
        $courseId = $this->createCourse();
        $this->configRequest(['headers' => ['Content-Type' => 'application/json']]);
        $this->post('/api/coordenadores/adicionar.json', json_encode([
            'users_id' => 1, 'curso_id' => $courseId, 'nome' => 'Não altera o usuário', 'deleted' => '2026-01-01',
        ]));
        $this->assertResponseCode(201);
        $data = json_decode((string)$this->_response->getBody(), true)['data'];
        $this->assertSame(1, $data['users_id']);
        $this->assertSame($courseId, $data['curso_id']);
        $this->assertNull($this->getTableLocator()->get('Coordenadores')->get($data['id'])->deleted);
        $this->assertSame('Jorgin', $this->getTableLocator()->get('Users')->get(1)->nome);
    }

    public function testAddRejectsInvalidFieldsAndInactiveCourse(): void
    {
        $courseId = $this->createCourse();
        foreach (
            [[], ['users_id' => 999, 'curso_id' => $courseId], ['users_id' => 1, 'curso_id' => 999],
            ['users_id' => ['1'], 'curso_id' => $courseId]] as $data
        ) {
            $this->post('/api/coordenadores/adicionar.json', $data);
            $this->assertResponseCode(422);
        }
        $this->getTableLocator()->get('Cursos')->updateAll(['deleted' => '2026-01-01 00:00:00'], ['id' => $courseId]);
        $this->post('/api/coordenadores/adicionar.json', ['users_id' => 1, 'curso_id' => $courseId]);
        $this->assertResponseCode(422);
        $this->assertSame(1, $this->getTableLocator()->get('Coordenadores')->find()->count());
    }

    public function testAddAndEditRejectUsersWithWrongProfileOrDeactivated(): void
    {
        $types = $this->getTableLocator()->get('Tipos');
        $otherType = $types->saveOrFail($types->newEntity(['nome' => 'Responsável']));
        $userId = $this->createUser();
        $courseId = $this->createCourse();
        $users = $this->getTableLocator()->get('Users');
        foreach ([['tipo_id' => $otherType->id], ['tipo_id' => 1, 'deleted' => '2026-01-01 00:00:00']] as $changes) {
            $users->updateAll($changes, ['id' => $userId]);
            $this->post('/api/coordenadores/adicionar.json', ['users_id' => $userId, 'curso_id' => $courseId]);
            $this->assertResponseCode(422);
            $this->patch('/api/coordenadores/editar/1.json', ['users_id' => $userId]);
            $this->assertResponseCode(422);
            $this->assertSame(1, $this->getTableLocator()->get('Coordenadores')->get(1)->users_id);
        }
    }

    public function testEditUpdatesUserAndCourseAndRejectsIgnoredFields(): void
    {
        $this->patch('/api/coordenadores/editar/1.json', ['nome' => 'Daniel']);
        $this->assertResponseCode(422);
        $this->assertSame('Jorgin', $this->getTableLocator()->get('Users')->get(1)->nome);
        $userId = $this->createUser();
        $courseId = $this->createCourse();
        $this->patch('/api/coordenadores/editar/1.json', ['users_id' => $userId, 'curso_id' => $courseId]);
        $this->assertResponseOk();
        $record = $this->getTableLocator()->get('Coordenadores')->get(1);
        $this->assertSame($userId, $record->users_id);
        $this->assertSame($courseId, $record->curso_id);
        $this->getTableLocator()->get('Cursos')->updateAll(['deleted' => '2026-01-01 00:00:00'], ['id' => 1]);
        $this->patch('/api/coordenadores/editar/1.json', ['curso_id' => 1]);
        $this->assertResponseCode(422);
        $this->assertSame($courseId, $this->getTableLocator()->get('Coordenadores')->get(1)->curso_id);
    }

    public function testEditKeepsOneCoordinatorPerCourse(): void
    {
        $courseId = $this->createCourse();
        $links = $this->getTableLocator()->get('Coordenadores');
        $links->saveOrFail($links->newEntity(['users_id' => 1, 'curso_id' => $courseId]));
        $this->patch('/api/coordenadores/editar/1.json', ['curso_id' => $courseId]);
        $this->assertResponseCode(422);
        $this->assertSame(1, $links->get(1)->curso_id);
    }

    public function testDeactivateAndReactivateKeepsParentsAndChecksTheirStatus(): void
    {
        $this->post('/api/coordenadores/desativar/1.json');
        $this->assertResponseOk();
        $this->get('/api/coordenadores/listar.json');
        $this->assertResponseOk();
        $this->assertSame([], json_decode((string)$this->_response->getBody(), true)['data']);
        $types = $this->getTableLocator()->get('Tipos');
        $otherType = $types->saveOrFail($types->newEntity(['nome' => 'Responsável']));
        foreach (
            [
                ['Users', ['tipo_id' => $otherType->id], ['tipo_id' => 1]],
                ['Users', ['deleted' => '2026-01-01 00:00:00'], ['deleted' => null]],
                ['Cursos', ['deleted' => '2026-01-01 00:00:00'], ['deleted' => null]],
            ] as [$alias, $invalid, $restore]
        ) {
            $table = $this->getTableLocator()->get($alias);
            $table->updateAll($invalid, ['id' => 1]);
            $this->post('/api/coordenadores/reativar/1.json');
            $this->assertResponseCode(422);
            $this->assertNotNull($this->getTableLocator()->get('Coordenadores')->get(1)->deleted);
            $table->updateAll($restore, ['id' => 1]);
        }
        $this->post('/api/coordenadores/reativar/1.json');
        $this->assertResponseOk();
        $this->assertNull($this->getTableLocator()->get('Coordenadores')->get(1)->deleted);
        $this->assertSame('Jorgin', $this->getTableLocator()->get('Users')->get(1)->nome);
        $this->assertSame('Informática', $this->getTableLocator()->get('Cursos')->get(1)->nome);
    }

    public function testMissingLinksReturn404AndMutationsRejectGet(): void
    {
        $this->get('/api/coordenadores/buscar/999.json');
        $this->assertResponseCode(404);
        foreach (['editar', 'desativar', 'reativar'] as $action) {
            $this->post('/api/coordenadores/' . $action . '/999.json', ['users_id' => 1]);
            $this->assertResponseCode(404);
            $this->get('/api/coordenadores/' . $action . '/1.json');
            $this->assertResponseCode(405);
        }
        $this->assertNull($this->getTableLocator()->get('Coordenadores')->get(1)->deleted);
    }

    private function createUser(): int
    {
        $table = $this->getTableLocator()->get('Users');
        $record = $table->saveOrFail($table->newEntity([
            'nome' => 'Daniel', 'email' => 'daniel@example.com', 'cpf' => 12345678901,
            'telefone' => 65999999999, 'password' => 'senha123', 'tipo_id' => 1,
        ]));

        return $record->id;
    }

    private function createCourse(): int
    {
        $table = $this->getTableLocator()->get('Cursos');
        $record = $table->saveOrFail($table->newEntity(['nome' => 'Engenharia']));

        return $record->id;
    }
}
