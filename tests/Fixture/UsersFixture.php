<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * UsersFixture
 */
class UsersFixture extends TestFixture
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
                'cpf' => 1,
                'password' => 'Lorem ipsum dolor sit amet',
                'nome' => 'Lorem ipsum dolor sit amet',
                'email' => 'responsavel@example.com',
                'telefone' => 1,
                'tipo_id' => 1,
                'setor_id' => null,
                'agendar' => true,
                'created' => '2026-10-15 20:23:10',
                'modified' => '2026-10-15 20:23:10',
                'deleted' => null,
            ],
        ];
        parent::init();
    }
}
