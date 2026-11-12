<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AgendamentosTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\AgendamentosTable Test Case
 */
class AgendamentosTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\AgendamentosTable
     */
    protected $Agendamentos;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Agendamentos',
        'app.Disponibilidades',
        'app.Users',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Agendamentos') ? [] : ['className' => AgendamentosTable::class];
        $this->Agendamentos = $this->getTableLocator()->get('Agendamentos', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Agendamentos);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\AgendamentosTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\AgendamentosTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
