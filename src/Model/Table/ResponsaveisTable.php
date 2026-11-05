<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Responsavel;
use Cake\Datasource\EntityInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class ResponsaveisTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('responsaveis');
        $this->setEntityClass(Responsavel::class);
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Users', ['foreignKey' => 'users_id', 'joinType' => 'INNER']);
        $this->belongsTo('Alunos', ['foreignKey' => 'alunos_id', 'joinType' => 'INNER']);
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
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
