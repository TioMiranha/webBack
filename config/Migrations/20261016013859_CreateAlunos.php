<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateAlunos extends BaseMigration
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
        $table = $this->table('alunos');
        $table->addColumn('nome', 'string', [
            'default' => null,
            'limit' => 200,
            'null' => false,
        ]);
        $table->addColumn('matricula', 'biginteger', [
            'default' => null,
            'limit' => 20,
            'null' => false,
        ]);
        $table->addColumn('curso', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ]);
        $table->addColumn('situacao', 'string', [
            'default' => null,
            'limit' => 25,
            'null' => false,
        ]);
        $table->addColumn('emaila', 'string', [
            'default' => null,
            'limit' => 200,
            'null' => false,
        ]);
        $table->addColumn('emailp', 'string', [
            'default' => null,
            'limit' => 200,
            'null' => false,
        ]);
        $table->addColumn('anop', 'string', [
            'default' => null,
            'limit' => 20,
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
        $table->addColumn('deleted', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ]);
        $table->create();
    }
}
