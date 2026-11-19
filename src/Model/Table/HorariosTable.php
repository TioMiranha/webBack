<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class HorariosTable extends Table
{
    /**
     * Configura os horários semanais dos setores.
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('horarios');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Setores', [
            'className' => 'Setors',
            'foreignKey' => 'setor_id',
            'propertyName' => 'setor',
        ]);
        $this->hasMany('Agendamentos', [
            'foreignKey' => 'horario_id',
        ]);
    }

    /**
     * Valida dia da semana, horas e identificação do setor.
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('setor_id')
            ->greaterThan('setor_id', 0)
            ->requirePresence('setor_id', 'create')
            ->notEmptyString('setor_id');

        $validator
            ->integer('dia_semana')
            ->range('dia_semana', [1, 7])
            ->requirePresence('dia_semana', 'create')
            ->notEmptyString('dia_semana');

        foreach (['hora_inicio', 'hora_fim'] as $field) {
            $validator
                ->time($field)
                ->requirePresence($field, 'create')
                ->notEmptyTime($field);
        }

        return $validator;
    }

    /**
     * Verifica o intervalo e preserva as reuniões já cadastradas.
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add(function (EntityInterface $entity): bool {
            return $entity->get('deleted') !== null || $this->Setores->exists([
                'id' => $entity->get('setor_id'), 'deleted IS' => null,
            ]);
        }, 'setorAtivo', ['errorField' => 'setor_id', 'message' => 'Selecione um setor existente e ativo.']);
        $rules->add(function (EntityInterface $entity): bool {
            return $entity->get('deleted') !== null
                || $entity->get('hora_inicio')->format('H:i:s') < $entity->get('hora_fim')->format('H:i:s');
        }, 'intervalo', ['errorField' => 'hora_fim', 'message' => 'A hora final deve ser posterior à inicial.']);
        $rules->add(function (EntityInterface $entity): bool {
            if ($entity->isNew() || $entity->get('deleted') !== null) {
                return true;
            }
            $bookings = $this->Agendamentos->find()->where([
                'horario_id' => $entity->get('id'), 'deleted IS' => null, 'cancelado_em IS' => null,
            ]);
            foreach ($bookings as $booking) {
                $server = $this->Agendamentos->Servidores->get($booking->servidor_id);
                if (
                    (int)$server->setor_id !== (int)$entity->get('setor_id')
                    || (int)$booking->data->format('N') !== (int)$entity->get('dia_semana')
                    || $booking->hora->format('H:i:s') < $entity->get('hora_inicio')->format('H:i:s')
                    || $booking->hora->format('H:i:s') >= $entity->get('hora_fim')->format('H:i:s')
                ) {
                    return false;
                }
            }

            return true;
        }, 'preservarAgendamentos', [
            'errorField' => 'hora_inicio', 'message' => 'A alteração deixaria agendamentos ativos fora deste horário.',
        ]);

        return $rules;
    }
}
