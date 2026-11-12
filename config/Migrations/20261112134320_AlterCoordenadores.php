<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AlterCoordenadores extends BaseMigration
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
        $this->table('coordenadores')
        ->changeColumn('deleted', 'datetime', [
            'null' => true,
            'default' => null,
        ])
        ->update();
    }
}
