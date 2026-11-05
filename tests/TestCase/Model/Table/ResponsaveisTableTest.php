<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Cake\Database\Exception\QueryException;
use Cake\TestSuite\TestCase;

class ResponsaveisTableTest extends TestCase
{
    protected array $fixtures = ['app.Tipos', 'app.Users', 'app.Alunos', 'app.Responsaveis'];

    public function testManyUsersCanBeLinkedToManyStudents(): void
    {
        $users = $this->getTableLocator()->get('Users');
        $data = $users->get(1)->toArray();
        unset($data['id']);
        $data['email'] = 'segundo@example.com';
        $data['password'] = 'senha-segura';
        $user = $users->saveOrFail($users->newEntity($data));
        $alunos = $this->getTableLocator()->get('Alunos');
        $data = $alunos->get(1)->toArray();
        unset($data['id']);
        $data['matricula'] = 2;
        $aluno = $alunos->saveOrFail($alunos->newEntity($data));
        $table = $this->getTableLocator()->get('Responsaveis');
        $table->saveOrFail($table->newEntity(['users_id' => $user->id, 'alunos_id' => 1]));
        $table->saveOrFail($table->newEntity(['users_id' => 1, 'alunos_id' => $aluno->id]));
        $this->assertCount(2, $alunos->get(1, contain: ['Responsaveis.Users'])->responsaveis);
        $this->assertCount(2, $users->get(1, contain: ['Responsaveis.Alunos'])->responsaveis);
    }

    public function testHardDeletingLinkedParentsIsRejected(): void
    {
        foreach (['Users', 'Alunos'] as $alias) {
            $table = $this->getTableLocator()->get($alias);
            $entity = $table->get(1);
            $this->assertFalse($table->delete($entity));
            $this->assertNotEmpty($entity->getErrors());
        }
        $this->assertSame(1, $this->getTableLocator()->get('Responsaveis')->find()->count());
    }

    public function testDatabaseAlsoEnforcesForeignKeysAndUniquePair(): void
    {
        $connection = $this->getTableLocator()->get('Responsaveis')->getConnection();
        foreach ([[1, 1], [999, 1], [1, 999]] as [$userId, $alunoId]) {
            $rejected = false;
            try {
                $connection->execute(
                    'INSERT INTO responsaveis (users_id, alunos_id, created, modified) VALUES (:user, :aluno, :date, :date)',
                    ['user' => $userId, 'aluno' => $alunoId, 'date' => '2026-10-29 20:00:00'],
                );
            } catch (QueryException $exception) {
                $rejected = true;
            }
            $this->assertTrue($rejected, 'A restrição do banco deveria impedir o vínculo inválido.');
        }
    }
}
