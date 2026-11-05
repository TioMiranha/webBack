<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Responsavel extends Entity
{
    protected array $_accessible = [
        'users_id' => true,
        'alunos_id' => true,
    ];
}
