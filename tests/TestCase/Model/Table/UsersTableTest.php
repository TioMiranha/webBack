<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;

class UsersTableTest extends TestCase
{
    protected array $fixtures = ['app.Tipos', 'app.Users'];

    public function testEmailMustBeUniqueAndTypeMustExist(): void
    {
        $table = $this->getTableLocator()->get('Users');
        $data = $table->get(1)->toArray();
        unset($data['id']);
        $data['password'] = 'senha123';
        $entity = $table->newEntity($data);
        $this->assertFalse($table->save($entity));
        $this->assertNotEmpty($entity->getErrors()['email']);
        $data['email'] = 'novo@example.com';
        $data['tipo_id'] = 999;
        $entity = $table->newEntity($data);
        $this->assertFalse($table->save($entity));
        $this->assertNotEmpty($entity->getErrors()['tipo_id']);
    }
}
