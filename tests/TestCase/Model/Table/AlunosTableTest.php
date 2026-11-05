<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;

class AlunosTableTest extends TestCase
{
    protected array $fixtures = ['app.Alunos'];

    public function testStudentCanBeCreatedActiveWithoutDeleted(): void
    {
        $table = $this->getTableLocator()->get('Alunos');
        $data = $table->get(1)->toArray();
        unset($data['id'], $data['created'], $data['modified'], $data['deleted']);
        $entity = $table->saveOrFail($table->newEntity($data));
        $this->assertNull($entity->deleted);
        $this->assertNotNull($entity->created);
        $this->assertNotEmpty($table->newEntity([])->getErrors()['matricula']);
    }
}
