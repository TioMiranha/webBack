<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AlignAgendamentosFields extends BaseMigration
{
    /**
     * Acrescenta os campos usados pelo formulário sem apagar registros antigos.
     */
    public function up(): void
    {
        $table = $this->table('agendamentos');
        $fields = [
            'responsavel_id' => 'integer', 'coordenador_id' => 'integer', 'data' => 'date', 'hora' => 'time',
        ];
        foreach ($fields as $field => $type) {
            if (!$table->hasColumn($field)) {
                $table->addColumn($field, $type, ['null' => true, 'default' => null]);
            }
        }
        if (!$table->hasColumn('disponibilidade_id')) {
            $table->addColumn('disponibilidade_id', 'integer', ['null' => true, 'default' => null]);
        }
        $table->changeColumn('users_id', 'integer', ['null' => true, 'default' => null])
            ->changeColumn('deleted', 'datetime', ['null' => true, 'default' => null])
            ->update();

        // Também atende bancos onde CreateUsers já foi aplicada antes destes campos.
        $users = $this->table('users');
        if (!$users->hasColumn('setor_id')) {
            $users->addColumn('setor_id', 'integer', ['null' => true, 'default' => null]);
        }
        if (!$users->hasColumn('agendar')) {
            $users->addColumn('agendar', 'boolean', ['null' => false, 'default' => false]);
        }
        $users->update();
        $this->table('setors')->changeColumn('deleted', 'datetime', ['null' => true, 'default' => null])->update();
    }

    /**
     * Remove somente os campos de agendamento acrescentados por esta correção.
     */
    public function down(): void
    {
        $this->table('agendamentos')
            ->removeColumn('responsavel_id')
            ->removeColumn('coordenador_id')
            ->removeColumn('data')
            ->removeColumn('hora')
            ->update();
    }
}
