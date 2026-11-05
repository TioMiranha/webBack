<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ProdutosFixture
 */
class ProdutosFixture extends TestFixture
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
                'valor' => 1.5,
                'peso' => 1.5,
                'undMedida' => 'Lorem ipsum dolor sit amet',
                'created' => '2026-09-11 19:59:38',
                'modified' => '2026-09-11 19:59:38',
                'deleted' => '2026-09-11 19:59:38',
            ],
        ];
        parent::init();
    }
}
