<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateCoordenadores extends BaseMigration
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
        $table = $this->table('coordenadores');
        $table->addColumn('users_id', 'integer', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('curso_id', 'integer', [
            'default' => null,
            'null' => false,
        ]);
        
        $table->addColumn('created', 'datetime', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('modified', 'datetime', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('deleted', 'datetime', [
            'default' => null,
            'null' => false,
        ]);
        $table->addIndex(['curso_id'], ['unique' => true]);

        $table->addForeignKey('users_id', 'users', 'id', [
            'delete' => 'RESTRICT',
            'update' => 'CASCADE',
        ]);

        $table->addForeignKey('curso_id', 'cursos', 'id', [
            'delete' => 'RESTRICT',
            'update' => 'CASCADE',
        ]);
        $table->create();
    }
}
