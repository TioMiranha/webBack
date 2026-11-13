<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Agendamentos Model
 *
 * @property \App\Model\Table\DisponibilidadesTable&\Cake\ORM\Association\BelongsTo $Disponibilidades
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @method \App\Model\Entity\Agendamento newEmptyEntity()
 * @method \App\Model\Entity\Agendamento newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Agendamento> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Agendamento get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Agendamento findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Agendamento patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Agendamento> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Agendamento|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Agendamento saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Agendamento>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Agendamento>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Agendamento>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Agendamento> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Agendamento>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Agendamento>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Agendamento>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Agendamento> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class AgendamentosTable extends Table
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

        $this->setTable('agendamentos');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Responsaveis', ['foreignKey' => 'responsavel_id', 'propertyName' => 'responsavel']);
        $this->belongsTo('Coordenadores', ['foreignKey' => 'coordenador_id', 'propertyName' => 'coordenador']);
        $this->belongsTo('Disponibilidades', [
            'foreignKey' => 'disponibilidade_id',
        ]);
        $this->belongsTo('Users', [
            'foreignKey' => 'users_id',
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
            ->scalar('assunto')
            ->requirePresence('assunto', 'create')
            ->notEmptyString('assunto');

        $validator
            ->date('data', ['ymd'])
            ->requirePresence('data', 'create')
            ->notEmptyDate('data');

        $validator
            ->time('hora')
            ->requirePresence('hora', 'create')
            ->notEmptyTime('hora');

        $validator
            ->integer('responsavel_id')
            ->greaterThan('responsavel_id', 0)
            ->requirePresence('responsavel_id', 'create')
            ->notEmptyString('responsavel_id');

        $validator
            ->integer('coordenador_id')
            ->greaterThan('coordenador_id', 0)
            ->requirePresence('coordenador_id', 'create')
            ->notEmptyString('coordenador_id');

        $validator
            ->integer('status')
            ->requirePresence('status', 'create')
            ->notEmptyString('status');

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
        $rules->add(
            $rules->existsIn(['responsavel_id'], 'Responsaveis', [
                'allowNullableNulls' => true,
                'message' => 'Responsável não encontrado. Use o id do vínculo cadastrado em responsaveis.',
            ]),
            ['errorField' => 'responsavel_id'],
        );
        $rules->add(
            $rules->existsIn(['coordenador_id'], 'Coordenadores', [
                'allowNullableNulls' => true,
                'message' => 'Coordenador não encontrado. Use o id do vínculo cadastrado em coordenadores.',
            ]),
            ['errorField' => 'coordenador_id'],
        );

        return $rules;
    }
}
