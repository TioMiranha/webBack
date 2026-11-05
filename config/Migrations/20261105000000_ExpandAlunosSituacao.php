<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class ExpandAlunosSituacao extends BaseMigration
{
    /**
     * @inheritDoc
     */
    public function change(): void
    {
        $this->table('alunos')
            ->changeColumn('situacao', 'string', ['limit' => 100, 'null' => false, 'default' => null])
            ->update();
    }
}
