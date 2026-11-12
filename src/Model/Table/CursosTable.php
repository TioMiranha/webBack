<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Cursos Model
 *
 * @method \App\Model\Entity\Curso newEmptyEntity()
 * @method \App\Model\Entity\Curso newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Curso> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Curso get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Curso findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Curso patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Curso> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Curso|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Curso saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Curso>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Curso>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Curso>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Curso> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Curso>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Curso>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Curso>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Curso> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CursosTable extends Table
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

        $this->setTable('cursos');
        $this->setDisplayField('nome');
        $this->setPrimaryKey('id');
        $this->hasMany('Alunos', [
            'foreignKey' => 'curso_id',
        ]);
        $this->addBehavior('Timestamp');
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
            ->scalar('nome')
            ->maxLength('nome', 255)
            ->requirePresence('nome', 'create')
            ->notEmptyString('nome');

        $validator
            ->dateTime('deleted')
            ->allowEmptyDateTime('deleted');

        return $validator;
    }
}
