<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class HorariosFixture extends TestFixture
{
    public array $records = [[
        'id' => 1, 'setor_id' => 1, 'dia_semana' => 6,
        'hora_inicio' => '13:00:00', 'hora_fim' => '18:00:00',
        'created' => '2026-11-19 12:00:00', 'modified' => '2026-11-19 12:00:00', 'deleted' => null,
    ]];
}
