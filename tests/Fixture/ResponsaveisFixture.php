<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ResponsaveisFixture
 */
class ResponsaveisFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'users_id' => 1,
                'alunos_id' => 1,
                'created' => '2026-11-13 00:00:37',
                'modified' => '2026-11-13 00:00:37',
                'deleted' => null,
            ],
        ];
        parent::init();
    }
}
