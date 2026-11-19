<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Mantém os vínculos antigos e adapta a agenda ao atendimento por setor.
 */
class CreateHorariosAndServidorAgendamentos extends BaseMigration
{
    public function up(): void
    {
        $this->table('users')
            ->addForeignKey('setor_id', 'setors', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->update();
        $this->table('horarios')
            ->addColumn('setor_id', 'integer')
            ->addColumn('dia_semana', 'integer', ['comment' => 'ISO: segunda=1, domingo=7'])
            ->addColumn('hora_inicio', 'time')
            ->addColumn('hora_fim', 'time')
            ->addColumn('created', 'datetime')
            ->addColumn('modified', 'datetime')
            ->addColumn('deleted', 'datetime', ['null' => true, 'default' => null])
            ->addIndex(['setor_id', 'dia_semana'])
            ->addForeignKey('setor_id', 'setors', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->create();

        // responsavel_id antigo identificava um vínculo, não o usuário do responsável.
        $this->table('agendamentos')->renameColumn('responsavel_id', 'responsavel_vinculo_id')->update();
        $this->table('agendamentos')
            ->addColumn('responsavel_id', 'integer', ['null' => true, 'default' => null])
            ->addColumn('servidor_id', 'integer', ['null' => true, 'default' => null])
            ->addColumn('horario_id', 'integer', ['null' => true, 'default' => null])
            ->addColumn('cancelado_em', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('reserva_ativa', 'integer', ['null' => true, 'default' => null])
            ->changeColumn('assunto', 'text', ['null' => true, 'default' => null])
            ->changeColumn('status', 'integer', ['null' => false, 'default' => 0])
            ->update();

        // SQL compatível com MySQL e SQLite; nenhum registro anterior é apagado.
        $this->execute('UPDATE agendamentos SET responsavel_id =
            (SELECT r.users_id FROM responsaveis r WHERE r.id = agendamentos.responsavel_vinculo_id),
            servidor_id = (SELECT c.users_id FROM coordenadores c WHERE c.id = agendamentos.coordenador_id)');
        $this->execute('UPDATE agendamentos SET reserva_ativa = 1
            WHERE deleted IS NULL AND servidor_id IS NOT NULL AND data IS NOT NULL AND hora IS NOT NULL');

        $this->table('agendamentos')
            ->addForeignKey('servidor_id', 'users', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('responsavel_id', 'users', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('horario_id', 'horarios', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addIndex(['servidor_id', 'data', 'hora', 'reserva_ativa'], [
                'unique' => true, 'name' => 'agendamentos_servidor_reserva',
            ])
            ->addIndex(['responsavel_id', 'data'])
            ->update();
    }

    public function down(): void
    {
        // Novos registros não possuem vínculo individual; não há conversão reversa sem perda.
        throw new RuntimeException('Esta migração preserva histórico e exige restauração por backup para reversão.');
    }
}
