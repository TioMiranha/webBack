<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Cake\Database\Exception\QueryException;
use Cake\TestSuite\TestCase;

class AgendamentosTableTest extends TestCase
{
    protected array $fixtures = [
        'app.Tipos', 'app.Setors', 'app.Users', 'app.Alunos', 'app.Responsaveis',
        'app.Cursos', 'app.Coordenadores', 'app.Disponibilidades', 'app.Horarios', 'app.Agendamentos',
    ];

    public function testValidationDefault(): void
    {
        $table = $this->getTableLocator()->get('Agendamentos');
        $record = $table->newEntity([
            'servidor_id' => 1, 'responsavel_id' => 1, 'horario_id' => 1,
            'data' => '2026-02-30', 'hora' => '25:00:00',
        ]);
        $this->assertArrayHasKey('data', $record->getErrors());
        $this->assertArrayHasKey('hora', $record->getErrors());
        $this->assertFalse($table->save($record));
    }

    public function testDatabasePreventsDuplicateActiveReservationsEvenWithoutOrm(): void
    {
        $db = $this->getTableLocator()->get('Agendamentos')->getConnection();
        $fields = [
            'servidor_id' => 1, 'responsavel_id' => 1, 'horario_id' => 1,
            'data' => '2026-11-21', 'hora' => '14:30:00', 'reserva_ativa' => 1,
            'created' => '2026-11-19 12:00:00', 'modified' => '2026-11-19 12:00:00',
        ];
        $db->insert('agendamentos', $fields);
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('SQLSTATE[23000]');
        $db->insert('agendamentos', $fields);
    }

    public function testNewAppointmentDoesNotRequireLegacyLinksAndKeepsHistory(): void
    {
        $db = $this->getTableLocator()->get('Agendamentos')->getConnection();
        $schema = $db->getSchemaCollection()->describe('agendamentos');
        $this->assertTrue($schema->getColumn('responsavel_vinculo_id')['null']);
        $this->assertTrue($schema->getColumn('coordenador_id')['null']);
        $db->update('agendamentos', ['responsavel_vinculo_id' => 1, 'coordenador_id' => 1], ['id' => 1]);

        $db->insert('agendamentos', [
            'servidor_id' => 1, 'responsavel_id' => 1, 'horario_id' => 1,
            'data' => '2026-11-21', 'hora' => '14:30:00', 'reserva_ativa' => 1,
            'created' => '2026-11-19 12:00:00', 'modified' => '2026-11-19 12:00:00',
        ]);
        $result = $db->execute('SELECT * FROM agendamentos WHERE id != 1')->fetch('assoc');
        $this->assertNull($result['responsavel_vinculo_id']);
        $this->assertNull($result['coordenador_id']);
        $old = $db->execute('SELECT * FROM agendamentos WHERE id=1')->fetch('assoc');
        $this->assertSame(1, (int)$old['responsavel_vinculo_id']);
        $this->assertSame(1, (int)$old['coordenador_id']);
    }
}
