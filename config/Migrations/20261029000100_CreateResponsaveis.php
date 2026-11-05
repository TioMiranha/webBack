<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateResponsaveis extends BaseMigration
{
    /**
     * @inheritDoc
     */
    public function change(): void
    {
        $this->table('responsaveis')
            ->addColumn('users_id', 'integer', ['null' => false])
            ->addColumn('alunos_id', 'integer', ['null' => false])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addColumn('deleted', 'datetime', ['null' => true, 'default' => null])
            ->addIndex(['users_id', 'alunos_id'], ['unique' => true])
            ->addIndex(['alunos_id'])
            ->addForeignKey('users_id', 'users', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('alunos_id', 'alunos', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->create();
    }
}
