<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class AgendamentosControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AgendaTestTrait;

    protected array $fixtures = [
        'app.Tipos', 'app.Setors', 'app.Users', 'app.Alunos', 'app.Responsaveis',
        'app.Cursos', 'app.Coordenadores', 'app.Disponibilidades', 'app.Horarios', 'app.Agendamentos',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAgenda();
    }

    public function testCreatesAndReturnsAllStudentsWithCoursesInTheSameOrder(): void
    {
        $this->post('/api/agendamentos/adicionar.json', $this->form() + [
            'id' => 900, 'deleted' => '2026-01-01', 'cancelado_em' => '2026-01-01', 'reserva_ativa' => null,
        ]);
        $this->assertResponseCode(201);
        $data = $this->responseData();
        $this->assertNotSame(900, $data['id']);
        $this->assertSame(10, $data['servidor_id']);
        $this->assertSame('Ana, Bruno', $data['aluno']);
        $this->assertSame('Informática, Informática', $data['curso']);
        $this->assertSame([2, 1], array_column($data['alunos'], 'id'));
        $this->assertSame('Servidor técnico', $data['servidor']);
        $this->assertFalse($data['cancelado']);
        $this->assertNull($data['deleted']);
        $this->assertResponseNotContains('password');
        $this->get('/api/agendamentos/buscar/' . $data['id'] . '.json');
        $this->assertResponseOk();
        $this->assertSame($data['id'], $this->responseData()['id']);
        $this->get('/api/agendamentos/listar.json');
        $this->assertResponseOk();
        $this->assertCount(1, $this->responseData());
    }

    public function testEditsDeletesAndReactivatesWithoutLosingHistory(): void
    {
        $this->post('/api/agendamentos/adicionar.json', $this->form());
        $this->assertResponseCode(201);
        $id = $this->responseData()['id'];
        $this->patch('/api/agendamentos/editar/' . $id . '.json', ['hora' => '15:00:00']);
        $this->assertResponseOk();
        $this->assertSame('15:00:00', $this->responseData()['hora']);
        $this->delete('/api/agendamentos/deletar/' . $id . '.json');
        $this->assertResponseOk();
        $this->get('/api/agendamentos/listar.json');
        $this->assertSame([], $this->responseData());
        $this->post('/api/agendamentos/reativar/' . $id . '.json');
        $this->assertResponseOk();
        $this->assertNull($this->responseData()['deleted']);
        $this->assertSame(1, $this->getTableLocator()->get('Agendamentos')->get($id)->reserva_ativa);
    }

    public function testCancellationReleasesSlotAndIsDifferentFromSoftDelete(): void
    {
        $this->post('/api/agendamentos/adicionar.json', $this->form());
        $this->assertResponseCode(201);
        $id = $this->responseData()['id'];
        $this->post('/api/agendamentos/adicionar.json', $this->form());
        $this->assertResponseCode(422);
        $this->patch('/api/agendamentos/cancelar/' . $id . '.json');
        $this->assertResponseOk();
        $cancelled = $this->responseData();
        $this->assertTrue($cancelled['cancelado']);
        $this->assertNull($cancelled['deleted']);
        $this->post('/api/agendamentos/cancelar/' . $id . '.json');
        $this->assertSame($cancelled['cancelado_em'], $this->responseData()['cancelado_em']);
        $this->post('/api/agendamentos/adicionar.json', $this->form());
        $this->assertResponseCode(201);
        $this->patch('/api/agendamentos/editar/' . $id . '.json', ['hora' => '16:00:00']);
        $this->assertResponseCode(422);
    }

    public function testReactivationCannotStealReservedTime(): void
    {
        $this->post('/api/agendamentos/adicionar.json', $this->form());
        $id = $this->responseData()['id'];
        $this->delete('/api/agendamentos/deletar/' . $id . '.json');
        $this->post('/api/agendamentos/adicionar.json', $this->form());
        $this->assertResponseCode(201);
        $this->post('/api/agendamentos/reativar/' . $id . '.json');
        $this->assertResponseCode(422);
        $this->assertNotNull($this->getTableLocator()->get('Agendamentos')->get($id)->deleted);
    }

    public function testDatabaseConflictReturnsValidationErrorWhenPrecheckIsBypassed(): void
    {
        $this->post('/api/agendamentos/adicionar.json', $this->form());
        $this->assertResponseCode(201);
        // Simula uma disputa que passou pela verificação anterior à gravação.
        $this->getTableLocator()->get('Agendamentos')->rulesChecker()->remove('reservaUnica');
        $this->post('/api/agendamentos/adicionar.json', $this->form());
        $this->assertResponseCode(422);
        $this->assertArrayHasKey('hora', json_decode((string)$this->_response->getBody(), true)['errors']);
    }

    public function testRejectsInvalidDateTimeRelationsAndOutOfSectorAvailability(): void
    {
        foreach (
            [
            [], ['data' => '2026-02-30'], ['data' => '2026-11-22'], ['hora' => '99:99'],
            ['hora' => '12:59:59'], ['hora' => '18:00:00'], ['responsavel_id' => 999],
            ['servidor_id' => 999], ['horario_id' => 999], ['servidor_id' => []],
            ] as $changes
        ) {
            $this->post('/api/agendamentos/adicionar.json', $changes ? array_replace($this->form(), $changes) : []);
            $this->assertResponseCode(422);
        }
        $this->getTableLocator()->get('Users')->updateAll(['agendar' => false], ['id' => 10]);
        $this->post('/api/agendamentos/adicionar.json', $this->form());
        $this->assertResponseCode(422);
        $this->getTableLocator()->get('Users')->updateAll(['agendar' => true, 'setor_id' => null], ['id' => 10]);
        $this->post('/api/agendamentos/adicionar.json', $this->form());
        $this->assertResponseCode(422);
        $this->assertSame(1, $this->getTableLocator()->get('Agendamentos')->find()->count());
    }

    public function testInactiveSectorAndResponsibleLinksAreRejected(): void
    {
        $this->getTableLocator()->get('Setors')->updateAll(['deleted' => '2026-11-19 12:00:00'], ['id' => 1]);
        $this->post('/api/agendamentos/adicionar.json', $this->form());
        $this->assertResponseCode(422);
        $this->getTableLocator()->get('Setors')->updateAll(['deleted' => null], ['id' => 1]);
        $this->getTableLocator()->get('Responsaveis')->updateAll(['deleted' => '2026-11-19 12:00:00'], ['users_id' => 1]);
        $this->post('/api/agendamentos/adicionar.json', $this->form());
        $this->assertResponseCode(422);
    }

    public function testMissingRecordsAndMethodRestrictions(): void
    {
        $this->get('/api/agendamentos/buscar/999.json');
        $this->assertResponseCode(404);
        foreach (['editar', 'deletar', 'reativar', 'cancelar'] as $action) {
            $this->post('/api/agendamentos/' . $action . '/999.json', ['assunto' => 'Teste']);
            $this->assertResponseCode(404);
            $this->get('/api/agendamentos/' . $action . '/1.json');
            $this->assertResponseCode(405);
        }
    }
}
