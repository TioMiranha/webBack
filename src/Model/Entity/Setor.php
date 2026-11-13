<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Setor Entity
 */
class Setor extends Entity
{
    protected array $_accessible = [
        'nome' => true,
        'created' => true,
        'modified' => true,
        'deleted' => true,
    ];
}
