<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AlterAlunos extends BaseMigration
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

        $table->addForeignKey('curso_id', 'cursos', 'id', [
            'delete' => 'RESTRICT',
            'update' => 'CASCADE',
        ]);

        $table->update();
    }
}
