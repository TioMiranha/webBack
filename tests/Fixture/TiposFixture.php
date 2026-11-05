<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * TiposFixture
 */
class TiposFixture extends TestFixture
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
                'created' => '2026-10-15 20:23:01',
                'modified' => '2026-10-15 20:23:01',
                'deleted' => null,
            ],
        ];
        parent::init();
    }
}
