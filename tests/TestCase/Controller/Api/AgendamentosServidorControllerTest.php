<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class AgendamentosServidorControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Tipos', 'app.Users', 'app.Alunos', 'app.Responsaveis',
        'app.Cursos', 'app.Coordenadores', 'app.Disponibilidades', 'app.Agendamentos',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $connection = $this->getTableLocator()->get('Agendamentos')->getConnection();
        $timestamps = ['created' => '2026-11-12 12:00:00', 'modified' => '2026-11-12 12:00:00'];
        $connection->insert('cursos', ['id' => 2, 'nome' => 'Informática', 'deleted' => null] + $timestamps);
        $connection->insert('coordenadores', [
            'id' => 10, 'users_id' => 1, 'curso_id' => 2, 'deleted' => null,
        ] + $timestamps);

        $records = [
            ['id' => 10, 'data' => '2026-11-15', 'hora' => '14:30:00'],
            ['id' => 11, 'data' => '2026-11-14', 'hora' => '15:00:00'],
            ['id' => 12, 'data' => '2026-11-14', 'hora' => '14:30:00', 'deleted' => '2026-11-12 12:00:00'],
            ['id' => 13, 'coordenador_id' => 1, 'data' => '2026-11-14', 'hora' => '14:30:00'],
        ];
        foreach ($records as $record) {
            $connection->insert('agendamentos', $record + [
                'coordenador_id' => 10, 'responsavel_id' => 1,
                'assunto' => 'Reunião', 'status' => 0, 'deleted' => null,
            ] + $timestamps);
        }
    }

    public function testFindByServidorListsAllActiveAppointmentsForTheCoordinatorLink(): void
    {
        // O vínculo tem id 10 e users_id 1: a rota recebe o id do vínculo.
        $this->get('/api/agendamentos/buscar/servidor/10.json');

        $this->assertResponseOk();
        $this->assertContentType('application/json');
        $data = json_decode((string)$this->_response->getBody(), true)['data'];
        $this->assertCount(2, $data);
        $this->assertSame([11, 10], array_column($data, 'agendamento_id'));
        $this->assertSame([10, 10], array_column($data, 'id'));
        $this->assertSame('Informática', $data[0]['curso']);
        $this->assertNotEmpty($data[0]['coordenador']);
        $this->assertNotEmpty($data[0]['responsavel']);
        $this->assertNotEmpty($data[0]['estudante']);
        $this->assertSame('2026-11-14', $data[0]['agenda']);
        $this->assertSame('15:00:00', $data[0]['hora']);
        $this->assertSame('Reunião', $data[0]['assunto']);
        $this->assertSame(0, $data[0]['status']);
        $this->assertResponseNotContains('password');
    }

    public function testFindByServidorKeepsSingleAppointmentAsAList(): void
    {
        $this->get('/api/agendamentos/buscar/servidor/1.json');

        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true)['data'];
        $this->assertCount(1, $data);
        $this->assertSame(13, $data[0]['agendamento_id']);
    }

    public function testFindByServidorReturnsNotFoundWhenThereAreNoActiveAppointments(): void
    {
        $this->getTableLocator()->get('Agendamentos')->updateAll(
            ['deleted' => '2026-11-12 12:00:00'],
            ['coordenador_id' => 10],
        );
        $this->get('/api/agendamentos/buscar/servidor/10.json');

        $this->assertResponseCode(404);
        $this->assertSame(404, json_decode((string)$this->_response->getBody(), true)['code']);
    }

    public function testFindByServidorReturnsNotFoundForUnknownCoordinator(): void
    {
        $this->get('/api/agendamentos/buscar/servidor/999.json');

        $this->assertResponseCode(404);
        $this->assertContentType('application/json');
        $this->assertSame(
            'Registro não encontrado!',
            json_decode((string)$this->_response->getBody(), true)['message'],
        );
    }

    public function testFindByServidorRejectsPostRequests(): void
    {
        $this->post('/api/agendamentos/buscar/servidor/10.json');

        $this->assertResponseCode(405);
    }
}
