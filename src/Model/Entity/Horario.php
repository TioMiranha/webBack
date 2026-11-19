<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Horario extends Entity
{
    protected array $_accessible = [
        'setor_id' => true,
        'dia_semana' => true,
        'hora_inicio' => true,
        'hora_fim' => true,
    ];
}
