<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class DisponibilidadesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Tipos', 'app.Users', 'app.Cursos', 'app.Coordenadores', 'app.Disponibilidades',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->getTableLocator()->get('Cursos')->updateAll(['deleted' => null], ['id' => 1]);
        $this->getTableLocator()->get('Coordenadores')->updateAll(['deleted' => null], ['id' => 1]);
    }

    public function testRequiresLoginBeforeValidatingTheForm(): void
    {
        $this->post('/api/disponibilidades/adicionar.json', []);
        $this->assertResponseCode(401);
        $this->assertSame(1, $this->getTableLocator()->get('Disponibilidades')->find()->count());
    }

    public function testCreatesAvailabilityWithOnlyDatesUsingTheLoggedInUsersLink(): void
    {
        $users = $this->getTableLocator()->get('Users');
        $user = $users->saveOrFail($users->newEntity([
            'nome' => 'Daniel', 'cpf' => 12345678901, 'email' => 'daniel@example.com',
            'telefone' => 65999999999, 'password' => 'senha123', 'tipo_id' => 1,
        ]));
        $this->getTableLocator()->get('Coordenadores')->updateAll(['users_id' => $user->id], ['id' => 1]);
        $this->session(['Auth' => ['User' => ['id' => $user->id]]]);
        $this->configRequest(['headers' => ['Content-Type' => 'application/json']]);
        $this->post('/api/disponibilidades/adicionar.json', json_encode([
            'dataInicio' => '2026-11-13 14:00:00', 'dataFim' => '2026-11-13 14:30:00',
        ]));
        $this->assertResponseCode(201);
        $data = json_decode((string)$this->_response->getBody(), true)['data'];
        $this->assertSame(1, $data['coordenador_id']);
        $record = $this->getTableLocator()->get('Disponibilidades')->get($data['id']);
        $this->assertSame(1, $record->coordenador_id);
        $this->assertSame('2026-11-13 14:00:00', $record->dataInicio->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-13 14:30:00', $record->dataFim->format('Y-m-d H:i:s'));
        $this->assertNull($record->deleted);
    }

    public function testIgnoresCoordinatorAndUserIdsSuppliedByTheClient(): void
    {
        $this->session(['Auth' => ['User' => ['id' => 1]]]);
        $this->post('/api/disponibilidades/adicionar.json', [
            'dataInicio' => '2026-11-13 14:00:00', 'dataFim' => '2026-11-13 14:30:00',
            'coordenador_id' => 999, 'users_id' => 999, 'id' => 500, 'deleted' => '2026-01-01',
        ]);
        $this->assertResponseCode(201);
        $data = json_decode((string)$this->_response->getBody(), true)['data'];
        $this->assertSame(1, $data['coordenador_id']);
        $this->assertNotSame(500, $data['id']);
        $this->assertNull($this->getTableLocator()->get('Disponibilidades')->get($data['id'])->deleted);
    }

    public function testRejectsMissingInvalidOrInactiveUser(): void
    {
        $this->session(['Auth' => ['User' => ['id' => 999]]]);
        $this->post('/api/disponibilidades/adicionar.json', $this->dates());
        $this->assertResponseCode(422);
        $types = $this->getTableLocator()->get('Tipos');
        $otherType = $types->saveOrFail($types->newEntity(['nome' => 'Responsável']));
        foreach ([['tipo_id' => $otherType->id], ['tipo_id' => 1, 'deleted' => '2026-01-01 00:00:00']] as $changes) {
            $this->getTableLocator()->get('Users')->updateAll($changes, ['id' => 1]);
            $this->session(['Auth' => ['User' => ['id' => 1]]]);
            $this->post('/api/disponibilidades/adicionar.json', $this->dates());
            $this->assertResponseCode(422);
        }
        $this->assertSame(1, $this->getTableLocator()->get('Disponibilidades')->find()->count());
    }

    public function testRejectsInactiveLinkAndCourse(): void
    {
        foreach (['Coordenadores', 'Cursos'] as $alias) {
            $table = $this->getTableLocator()->get($alias);
            $table->updateAll(['deleted' => '2026-01-01 00:00:00'], ['id' => 1]);
            $this->session(['Auth' => ['User' => ['id' => 1]]]);
            $this->post('/api/disponibilidades/adicionar.json', $this->dates());
            $this->assertResponseCode(422);
            $table->updateAll(['deleted' => null], ['id' => 1]);
        }
        $this->assertSame(1, $this->getTableLocator()->get('Disponibilidades')->find()->count());
    }

    public function testValidatesDatesAfterFillingCoordinator(): void
    {
        foreach ([[], ['dataInicio' => 'invalida', 'dataFim' => '2026-11-13 14:30:00']] as $form) {
            $this->session(['Auth' => ['User' => ['id' => 1]]]);
            $this->post('/api/disponibilidades/adicionar.json', $form);
            $this->assertResponseCode(422);
            $errors = json_decode((string)$this->_response->getBody(), true)['errors'];
            $this->assertArrayHasKey('dataInicio', $errors);
            $this->assertArrayNotHasKey('coordenador_id', $errors);
        }
    }

    public function testMultipleLinksRequireAnOwnedCourseSelection(): void
    {
        $courses = $this->getTableLocator()->get('Cursos');
        $course = $courses->saveOrFail($courses->newEntity(['nome' => 'Engenharia']));
        $links = $this->getTableLocator()->get('Coordenadores');
        $link = $links->saveOrFail($links->newEntity(['users_id' => 1, 'curso_id' => $course->id]));
        foreach ([null, 999, ['1']] as $courseId) {
            $this->session(['Auth' => ['User' => ['id' => 1]]]);
            $form = $this->dates();
            if ($courseId !== null) {
                $form['curso_id'] = $courseId;
            }
            $this->post('/api/disponibilidades/adicionar.json', $form);
            $this->assertResponseCode(422);
        }
        $this->session(['Auth' => ['User' => ['id' => 1]]]);
        $this->post('/api/disponibilidades/adicionar.json', $this->dates() + ['curso_id' => $course->id]);
        $this->assertResponseCode(201);
        $data = json_decode((string)$this->_response->getBody(), true)['data'];
        $this->assertSame($link->id, $data['coordenador_id']);
    }

    public function testAddRejectsGet(): void
    {
        $this->get('/api/disponibilidades/adicionar.json');
        $this->assertResponseCode(405);
    }

    /**
     * @return array<string, string>
     */
    private function dates(): array
    {
        return ['dataInicio' => '2026-11-13 14:00:00', 'dataFim' => '2026-11-13 14:30:00'];
    }
}
