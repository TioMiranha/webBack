<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class ReferenciasPorNomeControllerTest extends TestCase
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
        $this->getTableLocator()->get('Users')->updateAll(['nome' => 'Maria Responsável'], ['id' => 1]);
    }

    public function testCreatesAppointmentUsingOnlyNamesAndAutomaticallySelectsWindow(): void
    {
        $this->post('/api/agendamentos/adicionar.json', $this->formNomes());
        $this->assertResponseCode(201);
        $data = $this->responseData();
        $this->assertSame(10, $data['servidor_id']);
        $this->assertSame(1, $data['responsavel_id']);
        $this->assertSame(1, $data['horario_id']);
        $this->assertSame('Ana, Bruno', $data['aluno']);
        $this->assertSame('Informática, Informática', $data['curso']);
        $this->post('/api/agendamentos/adicionar.json', [
            'servidor' => 'Servidor técnico', 'responsavel' => 'Maria Responsável',
            'setor' => 'Secretaria', 'data' => '2026-11-21', 'hora' => '15:30:00',
        ]);
        $this->assertResponseCode(201);
        $this->post('/api/agendamentos/adicionar.json', array_replace($this->form(), ['hora' => '16:00:00']));
        $this->assertResponseCode(201);
    }

    public function testNamesCanBeMixedWithIdsAndMustReferToTheSameRecord(): void
    {
        $this->post('/api/agendamentos/adicionar.json', [
            'servidor_id' => 10, 'responsavel_nome' => 'Maria Responsável',
            'horario_id' => 1, 'data' => '2026-11-21', 'hora' => '14:30:00',
        ]);
        $this->assertResponseCode(201);
        $this->post('/api/agendamentos/adicionar.json', $this->formNomes() + ['servidor_id' => 1]);
        $this->assertResponseCode(422);
        $this->post('/api/agendamentos/adicionar.json', $this->formNomes() + ['servidor' => 'Outro nome']);
        $this->assertResponseCode(422);
    }

    public function testDoesNotChooseOneOfTwoPeopleWithTheSameName(): void
    {
        $this->duplicateUser();
        $this->post('/api/agendamentos/adicionar.json', $this->formNomes());
        $this->assertResponseCode(422);
        $this->assertResponseContains('Nome repetido');
        $this->assertSame(1, $this->getTableLocator()->get('Agendamentos')->find()->count());
        $this->post('/api/agendamentos/adicionar.json', $this->formNomes() + ['servidor_id' => 10]);
        $this->assertResponseCode(201);
        $this->assertSame(10, $this->responseData()['servidor_id']);
        $this->post('/api/agendamentos/adicionar.json', array_replace($this->formNomes(), [
            'servidor_id' => 11, 'hora' => '15:00:00',
        ]));
        $this->assertResponseCode(201);
        $this->get('/api/agendamentos/buscar/servidor/nome/' . rawurlencode('Servidor técnico') . '.json');
        $this->assertResponseOk((string)$this->_response->getBody());
        $this->assertCount(2, $this->responseData());
    }

    public function testRejectsInvalidUnknownInactiveAndInjectionNamesWithoutWriting(): void
    {
        foreach (['Não existe', "Servidor técnico' OR 1=1 --", '', ['inválido'], null, 10] as $name) {
            $this->post('/api/agendamentos/adicionar.json', array_replace($this->formNomes(), ['servidor_nome' => $name]));
            $this->assertResponseCode(422);
        }
        $this->getTableLocator()->get('Users')->updateAll(['deleted' => '2026-11-19 12:00:00'], ['id' => 10]);
        $this->post('/api/agendamentos/adicionar.json', $this->formNomes());
        $this->assertResponseCode(422);
        $this->post('/api/horarios/adicionar.json', [
            'setor_nome' => 'Biblioteca', 'dia_semana' => 1, 'hora_inicio' => '08:00:00', 'hora_fim' => '12:00:00',
        ]);
        $this->assertResponseCode(422);
        $this->assertSame(1, $this->getTableLocator()->get('Agendamentos')->find()->count());
    }

    public function testOverlappingWindowsNeedAnIdAndExistingIdEditingStillWorks(): void
    {
        $this->post('/api/horarios/adicionar.json', [
            'setor_nome' => 'Secretaria', 'dia_semana' => 6, 'hora_inicio' => '14:00:00', 'hora_fim' => '17:00:00',
        ]);
        $this->assertResponseCode(201);
        $this->post('/api/agendamentos/adicionar.json', $this->formNomes());
        $this->assertResponseCode(422);
        $this->assertResponseContains('Mais de um horário');
        $this->post('/api/agendamentos/adicionar.json', $this->formNomes() + ['horario_id' => 1]);
        $this->assertResponseCode(201);
        $id = $this->responseData()['id'];
        $this->patch('/api/agendamentos/editar/' . $id . '.json', ['hora' => '15:00:00']);
        $this->assertResponseOk((string)$this->_response->getBody());
        $this->assertSame(1, $this->responseData()['horario_id']);
        $this->assertSame('15:00:00', $this->responseData()['hora']);
    }

    public function testSearchesByNamesKeepEveryStudentAndCanCombineFilters(): void
    {
        $this->post('/api/agendamentos/adicionar.json', $this->formNomes());
        $this->assertResponseCode(201);
        foreach (
            [
            'servidor/nome/' . rawurlencode('Servidor técnico'),
            'responsavel/nome/' . rawurlencode('Maria Responsável'),
            'aluno/nome/Ana',
            ] as $path
        ) {
            $this->get('/api/agendamentos/buscar/' . $path . '.json');
            $this->assertResponseOk((string)$this->_response->getBody());
            $this->assertCount(1, $this->responseData());
            $this->assertSame('Ana, Bruno', $this->responseData()[0]['aluno']);
        }
        $filters = $this->seletorAgendamento() + ['aluno_nome' => 'Ana', 'setor_nome' => 'Secretaria'];
        $this->get('/api/agendamentos/listar.json?' . http_build_query($filters));
        $this->assertResponseOk((string)$this->_response->getBody());
        $this->assertCount(1, $this->responseData());
        $this->get('/api/agendamentos/listar.json?servidor_nome[]=invalido');
        $this->assertResponseCode(422);
        $this->get('/api/agendamentos/buscar/aluno/nome/' . rawurlencode("Ana' OR 1=1 --") . '.json');
        $this->assertResponseOk((string)$this->_response->getBody());
        $this->assertSame([], $this->responseData());
    }

    public function testFindEditDeleteReactivateAndCancelAppointmentWithSelector(): void
    {
        $this->post('/api/agendamentos/adicionar.json', $this->formNomes());
        $this->assertResponseCode(201);
        $id = $this->responseData()['id'];
        $selector = $this->seletorAgendamento();
        $this->get('/api/agendamentos/buscar.json?' . http_build_query($selector));
        $this->assertResponseOk((string)$this->_response->getBody());
        $this->assertSame($id, $this->responseData()['id']);
        $this->patch('/api/agendamentos/editar.json', ['seletor' => $selector, 'hora' => '15:00:00']);
        $this->assertResponseOk((string)$this->_response->getBody());
        $this->assertSame('15:00:00', $this->responseData()['hora']);
        $selector['hora'] = '15:00:00';
        $this->deleteJson('/api/agendamentos/deletar.json', ['seletor' => $selector]);
        $this->assertResponseOk((string)$this->_response->getBody());
        $this->assertNotNull($this->responseData()['deleted']);
        $this->post('/api/agendamentos/reativar.json', ['seletor' => $selector]);
        $this->assertResponseOk((string)$this->_response->getBody());
        $this->assertNull($this->responseData()['deleted']);
        $this->patch('/api/agendamentos/cancelar.json', ['seletor' => $selector]);
        $this->assertResponseOk((string)$this->_response->getBody());
        $this->assertTrue($this->responseData()['cancelado']);
    }

    public function testAmbiguousOrInvalidSelectorsNeverUpdateOrDeleteMultipleRecords(): void
    {
        $this->post('/api/agendamentos/adicionar.json', $this->formNomes());
        $this->assertResponseCode(201);
        $id = $this->responseData()['id'];
        $this->post('/api/agendamentos/adicionar.json', array_replace($this->formNomes(), ['hora' => '15:00:00']));
        $this->assertResponseCode(201);
        foreach ([[], ['servidor_nome' => 'Servidor técnico'], ['data' => '2026-02-30'], ['hora' => '25:00:00'], ['sql' => '1=1'], ['servidor_nome' => []]] as $selector) {
            $this->deleteJson('/api/agendamentos/deletar.json', ['seletor' => $selector]);
            $this->assertResponseCode(422);
        }
        $this->assertNull($this->getTableLocator()->get('Agendamentos')->get($id)->deleted);
        $this->get('/api/agendamentos/buscar.json?servidor_nome=Inexistente');
        $this->assertResponseCode(404);
        $this->get('/api/agendamentos/deletar.json?' . http_build_query($this->seletorAgendamento()));
        $this->assertResponseCode(405);
    }

    public function testSectorCrudByNameAndDuplicateProtection(): void
    {
        $this->post('/api/setores/adicionar.json', ['nome' => 'Administrativo']);
        $this->assertResponseCode(201);
        $id = $this->responseData()['id'];
        $this->get('/api/setores/buscar/nome/Administrativo.json');
        $this->assertResponseOk((string)$this->_response->getBody());
        $this->assertSame($id, $this->responseData()['id']);
        $this->patch('/api/setores/editar/nome/Administrativo.json', ['nome' => 'Direção']);
        $this->assertResponseOk((string)$this->_response->getBody());
        $name = rawurlencode('Direção');
        $this->delete('/api/setores/deletar/nome/' . $name . '.json');
        $this->assertResponseOk((string)$this->_response->getBody());
        $this->post('/api/setores/reativar/nome/' . $name . '.json');
        $this->assertResponseOk((string)$this->_response->getBody());
        $this->post('/api/setores/adicionar.json', ['nome' => 'Direção']);
        $this->assertResponseCode(201);
        $this->delete('/api/setores/deletar/nome/' . $name . '.json');
        $this->assertResponseCode(422);
        $this->assertNull($this->getTableLocator()->get('Setors')->get($id)->deleted);
    }

    public function testUserReferencesAndCrudByNameIncludingNumericNames(): void
    {
        $this->patch('/api/usuarios/editar/nome/' . rawurlencode('Maria Responsável') . '.json', [
            'setor_nome' => 'Secretaria', 'agendar' => true, 'tipo_nome' => 'Lorem ipsum dolor sit amet',
        ]);
        $this->assertResponseOk((string)$this->_response->getBody());
        $this->get('/api/usuarios/buscar/nome/' . rawurlencode('Maria Responsável') . '.json');
        $this->assertResponseOk((string)$this->_response->getBody());
        $this->assertSame(1, $this->responseData()['setor_id']);
        $this->assertTrue($this->responseData()['agendar']);
        $this->assertResponseNotContains('password');
        $this->delete('/api/usuarios/deletar/nome/' . rawurlencode('Maria Responsável') . '.json');
        $this->assertResponseOk((string)$this->_response->getBody());
        $this->post('/api/usuarios/reativar/nome/' . rawurlencode('Maria Responsável') . '.json');
        $this->assertResponseOk((string)$this->_response->getBody());
        $this->post('/api/usuarios/adicionar.json', [
            'nome' => '1', 'cpf' => 123, 'telefone' => 123, 'email' => 'numeric@example.com',
            'password' => 'senha-teste', 'tipo_nome' => 'Lorem ipsum dolor sit amet',
            'setor_nome' => 'Secretaria', 'agendar' => true,
        ]);
        $this->assertResponseCode(201);
        $id = $this->responseData()['id'];
        $this->get('/api/usuarios/buscar/nome/1.json');
        $this->assertResponseOk((string)$this->_response->getBody());
        $this->assertSame($id, $this->responseData()['id']);
        $this->get('/api/usuarios/listar.json?setor_nome=Secretaria');
        $this->assertResponseOk((string)$this->_response->getBody());
        $this->assertCount(3, $this->responseData());
    }

    public function testScheduleCrudBySectorNameAndSelector(): void
    {
        $this->post('/api/horarios/adicionar.json', [
            'setor_nome' => 'Secretaria', 'dia_semana' => 1, 'hora_inicio' => '08:00:00', 'hora_fim' => '12:00:00',
        ]);
        $this->assertResponseCode(201);
        $id = $this->responseData()['id'];
        $this->get('/api/horarios/buscar/setor/nome/Secretaria.json');
        $this->assertResponseOk((string)$this->_response->getBody());
        $this->assertCount(2, $this->responseData());
        $this->get('/api/horarios/listar.json?setor_nome=Secretaria&dia_semana=1');
        $this->assertCount(1, $this->responseData());
        $selector = ['setor_nome' => 'Secretaria', 'dia_semana' => 1, 'hora_inicio' => '08:00:00'];
        $this->patch('/api/horarios/editar.json', ['seletor' => $selector, 'hora_fim' => '11:00:00']);
        $this->assertResponseOk((string)$this->_response->getBody());
        $this->deleteJson('/api/horarios/deletar.json', ['seletor' => $selector]);
        $this->assertResponseOk((string)$this->_response->getBody());
        $this->post('/api/horarios/reativar.json', ['seletor' => $selector]);
        $this->assertResponseOk((string)$this->_response->getBody());
        $this->get('/api/horarios/buscar.json?' . http_build_query($selector));
        $this->assertResponseOk((string)$this->_response->getBody());
        $this->assertSame($id, $this->responseData()['id']);
        $this->deleteJson('/api/horarios/deletar.json', ['seletor' => ['setor_nome' => 'Secretaria']]);
        $this->assertResponseCode(422);
    }

    public function testResponsibleLinksCanBeCreatedChangedAndActivatedUsingNames(): void
    {
        $alunos = $this->getTableLocator()->get('Alunos');
        $data = $alunos->get(1)->toArray();
        unset($data['id']);
        $carla = $alunos->saveOrFail($alunos->newEntity(array_replace($data, ['nome' => 'Carla', 'matricula' => 3])));
        $davi = $alunos->saveOrFail($alunos->newEntity(array_replace($data, ['nome' => 'Davi', 'matricula' => 4])));
        $this->post('/api/responsaveis/adicionar.json', ['responsavel_nome' => 'Maria Responsável', 'aluno_nome' => 'Carla']);
        $this->assertResponseCode(201);
        $id = $this->responseData()['id'];
        $this->assertSame($carla->id, $this->responseData()['alunos_id']);
        $selector = ['responsavel_nome' => 'Maria Responsável', 'aluno_nome' => 'Carla'];
        $this->patch('/api/responsaveis/editar.json', ['seletor' => $selector, 'aluno_nome' => 'Davi']);
        $this->assertResponseOk((string)$this->_response->getBody());
        $this->assertSame($davi->id, $this->responseData()['alunos_id']);
        $selector['aluno_nome'] = 'Davi';
        $this->deleteJson('/api/responsaveis/deletar.json', ['seletor' => $selector]);
        $this->assertResponseOk((string)$this->_response->getBody());
        $this->post('/api/responsaveis/reativar.json', ['seletor' => $selector]);
        $this->assertResponseOk((string)$this->_response->getBody());
        $this->get('/api/responsaveis/buscar.json?' . http_build_query($selector));
        $this->assertResponseOk((string)$this->_response->getBody());
        $this->assertSame($id, $this->responseData()['id']);
        $this->get('/api/responsaveis/listar.json?aluno_nome=Davi');
        $this->assertCount(1, $this->responseData());
        $this->deleteJson('/api/responsaveis/deletar.json', ['seletor' => ['responsavel_nome' => 'Maria Responsável']]);
        $this->assertResponseCode(422);
    }

    private function deleteJson(string $url, array $data): void
    {
        $this->configRequest(['headers' => ['Content-Type' => 'application/json']]);
        $this->_sendRequest($url, 'DELETE', json_encode($data));
    }

    private function formNomes(): array
    {
        return [
            'servidor_nome' => 'Servidor técnico', 'responsavel_nome' => 'Maria Responsável',
            'setor_nome' => 'Secretaria', 'data' => '2026-11-21', 'hora' => '14:30:00',
        ];
    }

    private function seletorAgendamento(): array
    {
        return [
            'servidor_nome' => 'Servidor técnico', 'responsavel_nome' => 'Maria Responsável',
            'data' => '2026-11-21', 'hora' => '14:30:00',
        ];
    }

    private function duplicateUser(): void
    {
        $db = $this->getTableLocator()->get('Users')->getConnection();
        $data = $db->execute('SELECT * FROM users WHERE id=10')->fetch('assoc');
        $data['id'] = 11;
        $data['email'] = 'outroservidor@example.com';
        $db->insert('users', $data);
    }
}
