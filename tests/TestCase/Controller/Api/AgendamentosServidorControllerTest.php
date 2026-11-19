<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class AgendamentosServidorControllerTest extends TestCase
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
        foreach (['2026-11-28', '2026-11-21'] as $date) {
            $this->post('/api/agendamentos/adicionar.json', array_replace($this->form(), ['data' => $date]));
            $this->assertResponseCode(201);
        }
    }

    public function testFindByServidorUsesUserIdAndOrdersByDate(): void
    {
        $this->get('/api/agendamentos/buscar/servidor/10.json');
        $this->assertResponseOk();
        $this->assertSame(['2026-11-21', '2026-11-28'], array_column($this->responseData(), 'data'));
        $this->get('/api/agendamentos/buscar/servidor/1.json');
        $this->assertResponseOk();
        $this->assertSame([], $this->responseData());
    }

    public function testSearchesByDateResponsibleAndStudentKeepBothStudents(): void
    {
        foreach (
            [
            'data/2026-11-21' => 1, 'data/10/2026-11-28' => 1, 'responsavel/1' => 2, 'aluno/2' => 2,
            ] as $path => $count
        ) {
            $this->get('/api/agendamentos/buscar/' . $path . '.json');
            $this->assertResponseOk();
            $data = $this->responseData();
            $this->assertCount($count, $data);
            $this->assertSame('Ana, Bruno', $data[0]['aluno']);
            $this->assertSame('Informática, Informática', $data[0]['curso']);
        }
        $this->get('/api/agendamentos/listar.json?servidor_id=10&data=2026-11-21');
        $this->assertCount(1, $this->responseData());
    }

    public function testExcludesInactiveStudentsWithoutRepeatingAppointments(): void
    {
        $this->getTableLocator()->get('Alunos')->updateAll(['deleted' => '2026-11-19 12:00:00'], ['id' => 2]);
        $this->get('/api/agendamentos/buscar/responsavel/1.json');
        $this->assertResponseOk();
        $this->assertCount(2, $this->responseData());
        $this->assertSame('Bruno', $this->responseData()[0]['aluno']);
        $this->get('/api/agendamentos/buscar/aluno/2.json');
        $this->assertSame([], $this->responseData());
    }

    public function testFiltersAreValidatedAndSqlParametersCannotChangeQuery(): void
    {
        foreach (
            [
            'agendamentos/listar.json?servidor_id[]=10', 'agendamentos/listar.json?data=2026-02-30',
            'agendamentos/buscar/data/errada.json', 'agendamentos/listar.json?servidor_id=1%20OR%201=1',
            ] as $path
        ) {
            $this->get('/api/' . $path);
            $this->assertResponseCode(422);
        }
        $this->post('/api/agendamentos/buscar/servidor/10.json');
        $this->assertResponseCode(405);
        $this->get('/api/agendamentos/buscar/data/2026-11-30.json');
        $this->assertSame([], $this->responseData());
    }
}
