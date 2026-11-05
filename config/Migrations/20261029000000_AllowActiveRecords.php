<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AllowActiveRecords extends BaseMigration
{
    /**
     * @inheritDoc
     */
    public function up(): void
    {
        // Mantém o tipo e os dados antigos; NULL passa a representar um registro ativo.
        foreach (['tipos', 'users', 'alunos'] as $name) {
            $this->table($name)
                ->changeColumn('deleted', 'string', ['limit' => 255, 'null' => true, 'default' => null])
                ->update();
            $this->execute("UPDATE {$name} SET deleted = NULL WHERE deleted = ''");
        }
    }

    /**
     * @inheritDoc
     */
    public function down(): void
    {
        foreach (['tipos', 'users', 'alunos'] as $name) {
            $this->execute("UPDATE {$name} SET deleted = '' WHERE deleted IS NULL");
            $this->table($name)
                ->changeColumn('deleted', 'string', ['limit' => 255, 'null' => false, 'default' => null])
                ->update();
        }
    }
}
