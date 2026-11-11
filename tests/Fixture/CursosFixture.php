<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * CursosFixture
 */
class CursosFixture extends TestFixture
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
                'nome' => 'Lorem ipsum dolor sit amet',
                'created' => '2026-11-11 20:56:11',
                'modified' => '2026-11-11 20:56:11',
                'deleted' => '2026-11-11 20:56:11',
            ],
        ];
        parent::init();
    }
}
