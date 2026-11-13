<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Disponibilidade Entity
 *
 * @property int $id
 * @property int $coordenador_id
 * @property \Cake\I18n\DateTime $dataInicio
 * @property \Cake\I18n\DateTime $dataFim
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property \Cake\I18n\DateTime|null $deleted
 */
class Disponibilidade extends Entity
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
        'coordenador_id' => true,
        'dataInicio' => true,
        'dataFim' => true,
        'created' => true,
        'modified' => true,
        'deleted' => true,
    ];
}
