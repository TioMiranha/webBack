<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SetorsFixture extends TestFixture
{
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'nome' => 'Secretaria',
                'created' => '2026-11-12 12:00:00',
                'modified' => '2026-11-12 12:00:00',
                'deleted' => null,
            ],
            [
                'id' => 2,
                'nome' => 'Biblioteca',
                'created' => '2026-11-12 12:00:00',
                'modified' => '2026-11-12 12:00:00',
                'deleted' => '2026-11-12 12:00:00',
            ],
        ];
        parent::init();
    }
}
