<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AlterAgendamentos extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/5/guides/writing-migrations/migration-methods.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('agendamentos');
        // Mantém os vínculos existentes ao permitir agendamentos por data e hora.
        $table->changeColumn('disponibilidade_id', 'integer', ['null' => true, 'default' => null])
            ->changeColumn('deleted', 'datetime', ['null' => true, 'default' => null])
            ->update();
    }
}
