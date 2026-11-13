<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Responsavel;
use Cake\Datasource\EntityInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Responsaveis Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\AlunosTable&\Cake\ORM\Association\BelongsTo $Alunos
 * @method \App\Model\Entity\Responsavel newEmptyEntity()
 * @method \App\Model\Entity\Responsavel newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Responsavel> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Responsavel get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Responsavel findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Responsavel patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Responsavel> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Responsavel|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Responsavel saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Responsavel>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Responsavel>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Responsavel>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Responsavel> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Responsavel>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Responsavel>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Responsavel>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Responsavel> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ResponsaveisTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('responsaveis');
        $this->setEntityClass(Responsavel::class);
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'users_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Alunos', [
            'foreignKey' => 'alunos_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        foreach (['users_id', 'alunos_id'] as $field) {
            $validator->integer($field)
                ->greaterThan($field, 0)
                ->requirePresence($field, 'create')
                ->notEmptyString($field);
        }

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['users_id'], 'Users'), ['errorField' => 'users_id']);
        $rules->add($rules->existsIn(['alunos_id'], 'Alunos'), ['errorField' => 'alunos_id']);
        $rules->add($rules->isUnique(['users_id', 'alunos_id']), [
            'errorField' => 'alunos_id',
            'message' => 'Este usuário já está vinculado ao aluno. Reative o vínculo se estiver desativado.',
        ]);

        foreach (['users_id' => 'Users', 'alunos_id' => 'Alunos'] as $field => $association) {
            $rules->add(function (EntityInterface $entity) use ($field, $association): bool {
                return $entity->get('deleted') !== null || $this->getAssociation($association)
                    ->getTarget()->exists(['id' => $entity->get($field), 'deleted IS' => null]);
            }, 'active' . $association, [
                'errorField' => $field,
                'message' => 'Selecione um registro existente e ativo.',
            ]);
        }

        return $rules;
    }
}
