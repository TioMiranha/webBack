<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class HorariosControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AgendaTestTrait;

    protected array $fixtures = [
        'app.Tipos', 'app.Setors', 'app.Users', 'app.Alunos', 'app.Responsaveis',
        'app.Cursos', 'app.Coordenadores', 'app.Disponibilidades', 'app.Horarios', 'app.Agendamentos',
    ];

    public function testCrudAndSearchesByWeekDayAndSector(): void
    {
        $this->post('/api/horarios/adicionar.json', [
            'setor_id' => 1, 'dia_semana' => 1, 'hora_inicio' => '08:00:00', 'hora_fim' => '12:00:00',
            'id' => 500, 'deleted' => '2026-11-19 12:00:00',
        ]);
        $this->assertResponseCode(201);
        $id = $this->responseData()['id'];
        $this->assertNotSame(500, $id);
        $this->get('/api/horarios/buscar/' . $id . '.json');
        $this->assertResponseOk();
        $this->assertNull($this->responseData()['deleted']);
        $this->assertSame('Secretaria', $this->responseData()['setor']['nome']);
        $this->patch('/api/horarios/editar/' . $id . '.json', ['hora_fim' => '11:00:00']);
        $this->assertResponseOk();
        $this->get('/api/horarios/buscar/dia/1.json');
        $this->assertResponseOk();
        $this->assertCount(1, $this->responseData());
        $this->get('/api/horarios/buscar/setor/1.json');
        $this->assertResponseOk();
        $this->assertCount(2, $this->responseData());
        $this->delete('/api/horarios/deletar/' . $id . '.json');
        $this->assertResponseOk();
        $this->get('/api/horarios/buscar/dia/1.json');
        $this->assertSame([], $this->responseData());
        $this->post('/api/horarios/reativar/' . $id . '.json');
        $this->assertResponseOk();
        $this->get('/api/horarios/listar.json?setor_id=1&dia_semana=1');
        $this->assertCount(1, $this->responseData());
    }

    public function testUsersApiCanAssignSectorAndEnableAttendance(): void
    {
        $this->patch('/api/usuarios/editar/1.json', ['setor_id' => 1, 'agendar' => true]);
        $this->assertResponseOk();
        $this->get('/api/usuarios/buscar/1.json');
        $this->assertSame(1, $this->responseData()['setor_id']);
        $this->assertTrue($this->responseData()['agendar']);
        $this->patch('/api/usuarios/editar/1.json', ['setor_id' => 999]);
        $this->assertResponseCode(422);
        $this->patch('/api/usuarios/editar/1.json', ['agendar' => null]);
        $this->assertResponseCode(422);
        $this->assertSame(1, $this->getTableLocator()->get('Users')->get(1)->setor_id);
    }

    public function testValidationForDayIntervalAndSector(): void
    {
        $valid = ['setor_id' => 1, 'dia_semana' => 1, 'hora_inicio' => '08:00:00', 'hora_fim' => '12:00:00'];
        foreach (
            [
            [], ['dia_semana' => 0], ['dia_semana' => 8], ['dia_semana' => 'segunda'],
            ['hora_fim' => '08:00:00'], ['hora_inicio' => '14:00:00'], ['hora_fim' => '99:99'],
            ['setor_id' => 999], ['setor_id' => 2],
            ] as $changes
        ) {
            $this->post('/api/horarios/adicionar.json', $changes ? array_replace($valid, $changes) : []);
            $this->assertResponseCode(422);
        }
        foreach (['dia/0', 'dia/8', 'dia/segunda'] as $path) {
            $this->get('/api/horarios/buscar/' . $path . '.json');
            $this->assertResponseCode(422);
        }
        $this->get('/api/horarios/listar.json?setor_id[]=1');
        $this->assertResponseCode(422);
    }

    public function testCannotChangeWindowToInvalidateExistingAppointments(): void
    {
        $this->seedAgenda();
        $this->post('/api/agendamentos/adicionar.json', $this->form());
        $this->assertResponseCode(201);
        $this->patch('/api/horarios/editar/1.json', ['hora_inicio' => '15:00:00']);
        $this->assertResponseCode(422);
        $this->patch('/api/horarios/editar/1.json', ['dia_semana' => 1]);
        $this->assertResponseCode(422);
        $this->patch('/api/horarios/editar/1.json', ['hora_fim' => '17:00:00']);
        $this->assertResponseOk();
        $this->delete('/api/horarios/deletar/1.json');
        $this->assertResponseOk();
        $this->get('/api/agendamentos/listar.json');
        $this->assertCount(1, $this->responseData());
    }

    public function testMissingRecordsMethodsAndInactiveSector(): void
    {
        foreach (['editar', 'deletar', 'reativar'] as $action) {
            $this->post('/api/horarios/' . $action . '/999.json');
            $this->assertResponseCode(404);
            $this->get('/api/horarios/' . $action . '/1.json');
            $this->assertResponseCode(405);
        }
        $this->delete('/api/horarios/deletar/1.json');
        $this->assertResponseOk();
        $this->getTableLocator()->get('Setors')->updateAll(['deleted' => '2026-11-19 12:00:00'], ['id' => 1]);
        $this->get('/api/horarios/listar.json');
        $this->assertSame([], $this->responseData());
        $this->post('/api/horarios/reativar/1.json');
        $this->assertResponseCode(422);
    }
}
