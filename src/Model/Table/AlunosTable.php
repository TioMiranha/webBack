<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Alunos Model
 *
 * @method \App\Model\Entity\Aluno newEmptyEntity()
 * @method \App\Model\Entity\Aluno newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Aluno> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Aluno get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Aluno findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Aluno patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Aluno> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Aluno|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Aluno saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Aluno>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Aluno>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Aluno>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Aluno> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Aluno>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Aluno>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Aluno>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Aluno> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class AlunosTable extends Table
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

        $this->setTable('alunos');
        $this->setDisplayField('nome');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('Responsaveis', ['foreignKey' => 'alunos_id']);
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
            ->maxLength('nome', 200)
            ->requirePresence('nome', 'create')
            ->notEmptyString('nome');

        $validator
            ->requirePresence('matricula', 'create')
            ->notEmptyString('matricula');

        $validator
            ->scalar('curso')
            ->maxLength('curso', 255)
            ->requirePresence('curso', 'create')
            ->notEmptyString('curso');

        $validator
            ->scalar('situacao')
            ->maxLength('situacao', 100)
            ->requirePresence('situacao', 'create')
            ->notEmptyString('situacao');

        $validator
            ->scalar('emaila')
            ->maxLength('emaila', 200)
            ->requirePresence('emaila', 'create')
            ->notEmptyString('emaila');

        $validator
            ->scalar('emailp')
            ->maxLength('emailp', 200)
            ->requirePresence('emailp', 'create')
            ->notEmptyString('emailp');

        $validator
            ->scalar('anop')
            ->maxLength('anop', 20)
            ->requirePresence('anop', 'create')
            ->notEmptyString('anop');

        $validator
            ->scalar('deleted')
            ->maxLength('deleted', 255)
            ->allowEmptyString('deleted');

        return $validator;
    }

    /**
     * Valida o relatório, que pode não informar os e-mails do aluno.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationImport(Validator $validator): Validator
    {
        $validator = $this->validationDefault($validator);
        $validator->allowEmptyString('emaila')->allowEmptyString('emailp');
        $validator->nonNegativeInteger('matricula')->add('matricula', 'somenteDigitos', [
            'rule' => static fn($value): bool => ctype_digit((string)$value),
            'message' => 'A matrícula deve conter apenas dígitos.',
        ])->add('matricula', 'limiteBigint', [
            'rule' => static function ($value): bool {
                $digits = ltrim((string)$value, '0');
                $max = '9223372036854775807';

                return strlen($digits) < strlen($max)
                    || (strlen($digits) === strlen($max) && strcmp($digits, $max) <= 0);
            },
            'message' => 'A matrícula ultrapassa o limite numérico do banco.',
        ]);

        return $validator;
    }

    /**
     * @inheritDoc
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->addDelete($rules->isNotLinkedTo('Responsaveis'), ['errorField' => 'id']);

        return $rules;
    }
}
