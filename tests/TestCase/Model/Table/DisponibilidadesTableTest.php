<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\DisponibilidadesTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\DisponibilidadesTable Test Case
 */
class DisponibilidadesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\DisponibilidadesTable
     */
    protected $Disponibilidades;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Disponibilidades',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Disponibilidades') ? [] : ['className' => DisponibilidadesTable::class];
        $this->Disponibilidades = $this->getTableLocator()->get('Disponibilidades', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Disponibilidades);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\DisponibilidadesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
