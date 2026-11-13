<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class AgendamentosControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Tipos', 'app.Users', 'app.Alunos', 'app.Responsaveis',
        'app.Cursos', 'app.Coordenadores', 'app.Disponibilidades', 'app.Agendamentos',
    ];

    public function testCreatesListsAndFindsUsingTheFormFields(): void
    {
        $this->post('/api/agendamentos/adicionar.json', $this->form() + ['id' => 900, 'deleted' => '2026-01-01']);
        $this->assertResponseCode(201);
        $id = json_decode((string)$this->_response->getBody(), true)['data']['id'];
        $this->assertNotSame(900, $id);
        $record = $this->getTableLocator()->get('Agendamentos')->get($id);
        $this->assertSame(1, $record->responsavel_id);
        $this->assertSame(1, $record->coordenador_id);
        $this->assertSame('2026-11-14', $record->data->format('Y-m-d'));
        $this->assertSame('14:30:00', $record->hora->format('H:i:s'));
        $this->assertNull($record->deleted);
        $this->get('/api/agendamentos/listar.json?coordenador_id=1');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true)['data'];
        $this->assertCount(1, $data);
        $this->assertSame($id, $data[0]['id']);
        $this->get('/api/agendamentos/buscar/' . $id . '.json');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true)['data'];
        $this->assertSame('responsavel@example.com', $data['responsavel']['user']['email']);
        $this->assertResponseNotContains('password');
    }

    public function testEditsDeactivatesAndReactivates(): void
    {
        $this->post('/api/agendamentos/adicionar.json', $this->form());
        $this->assertResponseCode(201);
        $id = json_decode((string)$this->_response->getBody(), true)['data']['id'];
        $this->patch('/api/agendamentos/editar/' . $id . '.json', ['assunto' => 'Atualizado', 'id' => 1]);
        $this->assertResponseOk();
        $table = $this->getTableLocator()->get('Agendamentos');
        $this->assertSame('Atualizado', $table->get($id)->assunto);
        $this->delete('/api/agendamentos/desativar/' . $id . '.json');
        $this->assertResponseOk();
        $this->assertNotNull($table->get($id)->deleted);
        $this->get('/api/agendamentos/listar.json');
        $this->assertResponseOk();
        $this->assertSame([], json_decode((string)$this->_response->getBody(), true)['data']);
        $this->post('/api/agendamentos/reativar/' . $id . '.json');
        $this->assertResponseOk();
        $this->assertNull($table->get($id)->deleted);
    }

    public function testRejectsInvalidDatesAndMissingRelationsWithoutWriting(): void
    {
        foreach ([[], ['data' => 'invalida'], ['hora' => '99:99'], ['responsavel_id' => 999], ['coordenador_id' => 999]] as $changes) {
            $form = $changes ? array_replace($this->form(), $changes) : [];
            $this->post('/api/agendamentos/adicionar.json', $form);
            $this->assertResponseCode(422);
            $this->assertNotEmpty(json_decode((string)$this->_response->getBody(), true)['errors']);
        }
        $this->assertSame(1, $this->getTableLocator()->get('Agendamentos')->find()->count());
    }

    public function testMissingRecordsAndMethodRestrictions(): void
    {
        $this->get('/api/agendamentos/buscar/999.json');
        $this->assertResponseCode(404);
        foreach (['editar', 'deletar', 'reativar'] as $action) {
            $this->post('/api/agendamentos/' . $action . '/999.json', ['assunto' => 'Teste']);
            $this->assertResponseCode(404);
            $this->get('/api/agendamentos/' . $action . '/1.json');
            $this->assertResponseCode(405);
        }
    }

    public function testListsRejectArrayFilters(): void
    {
        foreach (
            [
            'agendamentos' => 'status', 'disponibilidades' => 'id', 'cursos' => 'nome',
            'coordenadores' => 'curso', 'alunos' => 'nome', 'usuarios' => 'nome',
            'responsaveis' => 'users_id', 'perfis' => 'nome',
            ] as $resource => $field
        ) {
            $this->get('/api/' . $resource . '/listar.json?' . $field . '%5B%5D=1');
            $this->assertResponseCode(422);
            $this->assertContentType('application/json');
        }
    }

    /**
     * @return array<string, int|string>
     */
    private function form(): array
    {
        return [
            'responsavel_id' => 1, 'coordenador_id' => 1, 'data' => '2026-11-14',
            'hora' => '14:30:00', 'assunto' => 'Reunião', 'status' => 0,
        ];
    }
}
