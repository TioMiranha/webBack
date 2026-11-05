<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;

class TiposTableTest extends TestCase
{
    protected array $fixtures = ['app.Tipos', 'app.Users'];

    public function testNameRequiredAndLinkedTypeCannotBeHardDeleted(): void
    {
        $table = $this->getTableLocator()->get('Tipos');
        $this->assertNotEmpty($table->newEntity([])->getErrors()['nome']);
        $this->assertFalse($table->delete($table->get(1)));
    }
}
