<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Disponibilidades Model
 *
 * @method \App\Model\Entity\Disponibilidade newEmptyEntity()
 * @method \App\Model\Entity\Disponibilidade newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Disponibilidade> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Disponibilidade get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Disponibilidade findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Disponibilidade patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Disponibilidade> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Disponibilidade|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Disponibilidade saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Disponibilidade>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Disponibilidade>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Disponibilidade>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Disponibilidade> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Disponibilidade>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Disponibilidade>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Disponibilidade>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Disponibilidade> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class DisponibilidadesTable extends Table
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

        $this->setTable('disponibilidades');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Coordenadores', ['foreignKey' => 'coordenador_id']);
        $this->hasMany('Agendamentos', ['foreignKey' => 'disponibilidade_id']);
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
            ->integer('coordenador_id')
            ->requirePresence('coordenador_id', 'create')
            ->notEmptyString('coordenador_id');

        $validator
            ->dateTime('dataInicio')
            ->requirePresence('dataInicio', 'create')
            ->notEmptyDateTime('dataInicio');

        $validator
            ->dateTime('dataFim')
            ->requirePresence('dataFim', 'create')
            ->notEmptyDateTime('dataFim');

        $validator
            ->dateTime('deleted')
            ->allowEmptyDateTime('deleted');

        return $validator;
    }

    /**
     * Valida o intervalo completo, inclusive quando somente uma data é editada.
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['coordenador_id'], 'Coordenadores'), ['errorField' => 'coordenador_id']);
        $rules->add(static function (EntityInterface $entity): bool {
            return $entity->get('deleted') !== null || $entity->get('dataFim') > $entity->get('dataInicio');
        }, 'intervalo', [
            'errorField' => 'dataFim',
            'message' => 'O fim deve ser posterior ao início da disponibilidade.',
        ]);

        return $rules;
    }
}
