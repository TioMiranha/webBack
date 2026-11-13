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
                'dataInicio' => '2026-11-13 00:00:37',
                'dataFim' => '2026-11-13 00:30:37',
                'created' => '2026-11-13 00:00:37',
                'modified' => '2026-11-13 00:00:37',
                'deleted' => '2026-11-13 00:00:37',
            ],
        ];
        parent::init();
    }
}
