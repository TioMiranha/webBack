<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Aluno Entity
 *
 * @property int $id
 * @property string $nome
 * @property int $matricula
 * @property string $curso
 * @property string $situacao
 * @property string $emaila
 * @property string $emailp
 * @property string $anop
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property string $deleted
 */
class Aluno extends Entity
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
        'nome' => true,
        'matricula' => true,
        'curso' => true,
        'curso_id' => true,
        'situacao' => true,
        'emaila' => true,
        'emailp' => true,
        'anop' => true,
        'created' => true,
        'modified' => true,
        'deleted' => true,
    ];
}
