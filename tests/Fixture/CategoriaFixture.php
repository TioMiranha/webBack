<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * CategoriaFixture
 */
class CategoriaFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public string $table = 'categoria';
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
                'nomeCategoria' => 'Lorem ipsum dolor sit amet',
                'created' => '2026-09-17 20:20:50',
                'modified' => '2026-09-17 20:20:50',
                'deleted' => '2026-09-17 20:20:50',
            ],
        ];
        parent::init();
    }
}
