<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * User Entity
 *
 * @property int $id
 * @property int $cpf
 * @property string $password
 * @property string $nome
 * @property string $email
 * @property int $telefone
 * @property int $tipo_id
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property string|null $deleted
 * @property int|null $setor_id
 * @property bool|null $agendar
 *
 * @property \App\Model\Entity\Responsavel[] $responsaveis
 * @property \App\Model\Entity\Tipo $tipo
 */
class User extends Entity
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
        'cpf' => true,
        'password' => true,
        'nome' => true,
        'email' => true,
        'telefone' => true,
        'tipo_id' => true,
        'created' => true,
        'modified' => true,
        'deleted' => true,
        'setor_id' => true,
        'agendar' => true,
        'responsaveis' => true,
        'tipo' => true,
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var array<string>
     */
    protected array $_hidden = [
        'password',
    ];

    /**
     * Armazena a senha como hash.
     */
    protected function _setPassword(string $password): ?string
    {
        if (mb_strlen($password) > 0) {
            return password_hash($password, PASSWORD_DEFAULT);
        }

        return null;
    }
}
