<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class AgendamentosTable extends Table
{
    /**
     * Configura as relações com usuários e horários.
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('agendamentos');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Servidores', [
            'className' => 'Users',
            'foreignKey' => 'servidor_id',
        ]);
        $this->belongsTo('ResponsaveisUsuarios', [
            'className' => 'Users',
            'foreignKey' => 'responsavel_id',
        ]);
        $this->belongsTo('Horarios', [
            'foreignKey' => 'horario_id',
        ]);
    }

    /**
     * Valida os campos de uma reunião.
     */
    public function validationDefault(Validator $validator): Validator
    {
        foreach (['servidor_id', 'responsavel_id', 'horario_id'] as $field) {
            $validator
                ->integer($field)
                ->greaterThan($field, 0)
                ->requirePresence($field, 'create')
                ->notEmptyString($field);
        }

        $validator
            ->date('data', ['ymd'])
            ->requirePresence('data', 'create')
            ->notEmptyDate('data');

        $validator
            ->time('hora')
            ->requirePresence('hora', 'create')
            ->notEmptyTime('hora');

        $validator
            ->scalar('assunto')
            ->allowEmptyString('assunto');

        return $validator;
    }

    /**
     * Verifica responsáveis, atendimento no setor e reserva exclusiva.
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add(function (EntityInterface $entity): bool {
            return !$this->occupiesSlot($entity) || ($entity->get('servidor_id') !== null && $this->Servidores->exists([
                'id' => $entity->get('servidor_id'), 'deleted IS' => null, 'agendar' => true,
            ]));
        }, 'servidorAtivo', [
            'errorField' => 'servidor_id',
            'message' => 'Selecione um servidor ativo e habilitado para atender (agendar=true).',
        ]);
        $rules->add(function (EntityInterface $entity): bool {
            if (!$this->occupiesSlot($entity)) {
                return true;
            }
            if (
                $entity->get('responsavel_id') === null || !$this->ResponsaveisUsuarios->exists([
                'id' => $entity->get('responsavel_id'), 'deleted IS' => null,
                ])
            ) {
                return false;
            }

            return $this->ResponsaveisUsuarios->Responsaveis->find()->innerJoinWith('Alunos')->where([
                'Responsaveis.users_id' => $entity->get('responsavel_id'),
                'Responsaveis.deleted IS' => null, 'Alunos.deleted IS' => null,
            ])->count() > 0;
        }, 'responsavelAtivo', [
            'errorField' => 'responsavel_id',
            'message' => 'Selecione um usuário ativo vinculado a pelo menos um aluno ativo.',
        ]);
        $rules->add(function (EntityInterface $entity): bool {
            if (!$this->occupiesSlot($entity)) {
                return true;
            }
            foreach (['servidor_id', 'horario_id', 'data', 'hora'] as $field) {
                if ($entity->get($field) === null) {
                    return false;
                }
            }
            $window = $this->Horarios->find()->where([
                'id' => $entity->get('horario_id'), 'deleted IS' => null,
            ])->first();
            $server = $this->Servidores->find()->where(['id' => $entity->get('servidor_id')])->first();
            if (!$window || !$server || (int)$server->setor_id !== (int)$window->setor_id) {
                return false;
            }
            if (!$this->Horarios->Setores->exists(['id' => $window->setor_id, 'deleted IS' => null])) {
                return false;
            }
            $time = $entity->get('hora')->format('H:i:s');

            return (int)$entity->get('data')->format('N') === (int)$window->dia_semana
                && $time >= $window->hora_inicio->format('H:i:s') && $time < $window->hora_fim->format('H:i:s');
        }, 'horarioDisponivel', [
            'errorField' => 'horario_id',
            'message' => 'A data e hora devem estar dentro de um horário ativo do setor do servidor.',
        ]);
        $rules->add(function (EntityInterface $entity): bool {
            if (!$this->occupiesSlot($entity)) {
                return true;
            }
            foreach (['servidor_id', 'data', 'hora'] as $field) {
                if ($entity->get($field) === null) {
                    return false;
                }
            }
            $conditions = [
                'servidor_id' => $entity->get('servidor_id'), 'data' => $entity->get('data'),
                'hora' => $entity->get('hora'), 'deleted IS' => null, 'cancelado_em IS' => null,
            ];
            if (!$entity->isNew()) {
                $conditions['id !='] = $entity->get('id');
            }

            return !$this->exists($conditions);
        }, 'reservaUnica', [
            'errorField' => 'hora', 'message' => 'Este servidor já possui agendamento nesta data e hora.',
        ]);

        return $rules;
    }

    /**
     * Mantém a chave de exclusividade somente para reservas ativas.
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $entity->set('reserva_ativa', $this->occupiesSlot($entity) ? 1 : null);
    }

    /**
     * Informa se a reunião ocupa a data e hora do servidor.
     */
    private function occupiesSlot(EntityInterface $entity): bool
    {
        return $entity->get('deleted') === null && $entity->get('cancelado_em') === null;
    }
}
