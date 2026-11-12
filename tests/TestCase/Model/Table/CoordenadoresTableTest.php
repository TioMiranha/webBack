<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\CoordenadoresTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\CoordenadoresTable Test Case
 */
class CoordenadoresTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\CoordenadoresTable
     */
    protected $Coordenadores;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Coordenadores',
        'app.Users',
        'app.Cursos',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Coordenadores') ? [] : ['className' => CoordenadoresTable::class];
        $this->Coordenadores = $this->getTableLocator()->get('Coordenadores', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Coordenadores);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\CoordenadoresTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\CoordenadoresTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
