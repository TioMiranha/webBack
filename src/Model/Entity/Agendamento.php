<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Agendamento Entity
 *
 * @property int $id
 * @property int $disponibilidade_id
 * @property int $users_id
 * @property string $assunto
 * @property int $status
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property \Cake\I18n\DateTime $deleted
 *
 * @property \App\Model\Entity\Disponibilidade $disponibilidade
 * @property \App\Model\Entity\User $user
 */
class Agendamento extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'disponibilidade_id' => true,
        'users_id' => true,
        'assunto' => true,
        'status' => true,
        'created' => true,
        'modified' => true,
        'deleted' => true,
        'disponibilidade' => true,
        'user' => true,
    ];
}
