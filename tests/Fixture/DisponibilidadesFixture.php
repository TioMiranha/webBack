<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * DisponibilidadesFixture
 */
class DisponibilidadesFixture extends TestFixture
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
                'coordenador_id' => 1,
                'dataInicio' => '2026-11-12 18:39:19',
                'dataFim' => '2026-11-12 18:39:19',
                'created' => '2026-11-12 18:39:19',
                'modified' => '2026-11-12 18:39:19',
                'deleted' => '2026-11-12 18:39:19',
            ],
        ];
        parent::init();
    }
}
