<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Coordenadores Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\CursosTable&\Cake\ORM\Association\BelongsTo $Cursos
 * @method \App\Model\Entity\Coordenadore newEmptyEntity()
 * @method \App\Model\Entity\Coordenadore newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Coordenadore> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Coordenadore get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Coordenadore findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Coordenadore patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Coordenadore> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Coordenadore|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Coordenadore saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Coordenadore>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Coordenadore>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Coordenadore>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Coordenadore> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Coordenadore>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Coordenadore>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Coordenadore>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Coordenadore> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CoordenadoresTable extends Table
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

        $this->setTable('coordenadores');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'users_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Cursos', [
            'foreignKey' => 'curso_id',
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
        $validator
            ->integer('users_id')
            ->notEmptyString('users_id');

        $validator
            ->integer('curso_id')
            ->notEmptyString('curso_id')
            ->add('curso_id', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->dateTime('deleted')
            ->allowEmptyDateTime('deleted');

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
        $rules->add($rules->isUnique(['curso_id']), ['errorField' => 'curso_id']);
        $rules->add($rules->existsIn(['users_id'], 'Users'), ['errorField' => 'users_id']);
        $rules->add($rules->existsIn(['curso_id'], 'Cursos'), ['errorField' => 'curso_id']);

        return $rules;
    }
}
