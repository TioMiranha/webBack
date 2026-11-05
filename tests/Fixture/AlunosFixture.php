<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * AlunosFixture
 */
class AlunosFixture extends TestFixture
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
                'nome' => 'Lorem ipsum dolor sit amet',
                'matricula' => 1,
                'curso' => 'Lorem ipsum dolor sit amet',
                'situacao' => 'Lorem ipsum dolor sit a',
                'emaila' => 'aluno@example.com',
                'emailp' => 'pessoal@example.com',
                'anop' => 'Lorem ipsum dolor ',
                'created' => '2026-10-15 21:41:28',
                'modified' => '2026-10-15 21:41:28',
                'deleted' => null,
            ],
        ];
        parent::init();
    }
}
