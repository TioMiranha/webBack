<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController as BaseController;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
use Cake\Validation\Validation;
use InvalidArgumentException;

class AppController extends BaseController
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->viewBuilder()->setClassName('Json');
    }

    /**
     * Retorna uma resposta JSON com o status HTTP informado.
     */
    protected function json(mixed $data, int $status = 200): Response
    {
        return $this->response->withStatus($status)->withType('application/json')
            ->withStringBody(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Converte um nome de referência em ID sem escolher entre homônimos.
     */
    protected function resolverReferencia(
        array $form,
        string $alias,
        string $campoId,
        string $campoNome,
        array $apelidos = [],
    ): array {
        $nome = null;
        foreach (array_merge([$campoNome], $apelidos) as $campo) {
            if (!array_key_exists($campo, $form)) {
                continue;
            }
            if (!is_string($form[$campo]) || trim($form[$campo]) === '') {
                throw new InvalidArgumentException('Informe um nome válido em ' . $campo . '.');
            }
            $valor = trim($form[$campo]);
            if ($nome !== null && $nome !== $valor) {
                throw new InvalidArgumentException('Os nomes informados em ' . $campoNome . ' são diferentes.');
            }
            $nome = $valor;
            unset($form[$campo]);
        }
        if ($nome === null) {
            return $form;
        }
        $conditions = ['nome' => $nome, 'deleted IS' => null];
        if (array_key_exists($campoId, $form)) {
            if (
                !is_scalar($form[$campoId]) || is_bool($form[$campoId])
                || !ctype_digit((string)$form[$campoId]) || (int)$form[$campoId] < 1
            ) {
                throw new InvalidArgumentException('Informe um ID válido em ' . $campoId . '.');
            }
            $conditions['id'] = (int)$form[$campoId];
        }
        $registros = $this->fetchTable($alias)->find()->select(['id'])->where($conditions)->limit(2)->all()->toList();
        if (empty($registros)) {
            throw new InvalidArgumentException(
                'Referência inexistente, desativada ou incompatível: ' . $campoNome . '.',
            );
        }
        if (count($registros) > 1) {
            throw new InvalidArgumentException('Nome repetido em ' . $campoNome . '. Informe ' . $campoId . '.');
        }
        $form[$campoId] = (int)$registros[0]->id;

        return $form;
    }

    /**
     * Localiza um único alvo por ID ou por um seletor de nomes e campos do registro.
     */
    protected function localizarRegistro(string $alias, ?string $id): EntityInterface
    {
        $tabela = $this->fetchTable($alias);
        if ($id !== null && !$this->request->getParam('porNome')) {
            return $tabela->get((int)$id);
        }
        $form = $this->request->getData() + $this->request->getQueryParams();
        $seletor = $form['seletor'] ?? $this->request->getQueryParams();
        if ($this->request->getParam('porNome')) {
            $seletor = ['nome' => $id];
        }
        if (!is_array($seletor) || empty($seletor)) {
            throw new InvalidArgumentException('Informe o ID na rota ou um seletor para identificar o registro.');
        }
        $campos = ['id' => $alias . '.id'];
        $relacoes = [];
        if ($alias === 'Users' || $alias === 'Setors') {
            $campos += ['nome' => $alias . '.nome'];
        } elseif ($alias === 'Horarios') {
            $campos += [
                'setor_id' => 'Horarios.setor_id', 'setor_nome' => 'Setores.nome',
                'dia_semana' => 'Horarios.dia_semana', 'hora_inicio' => 'Horarios.hora_inicio',
                'hora_fim' => 'Horarios.hora_fim',
            ];
            $relacoes = ['Setores'];
        } elseif ($alias === 'Responsaveis') {
            $campos += [
                'users_id' => 'Responsaveis.users_id', 'alunos_id' => 'Responsaveis.alunos_id',
                'responsavel_nome' => 'Users.nome', 'aluno_nome' => 'Alunos.nome',
            ];
            $relacoes = ['Users', 'Alunos'];
        } elseif ($alias === 'Agendamentos') {
            $campos += [
                'servidor_id' => 'Agendamentos.servidor_id', 'responsavel_id' => 'Agendamentos.responsavel_id',
                'horario_id' => 'Agendamentos.horario_id', 'data' => 'Agendamentos.data',
                'hora' => 'Agendamentos.hora', 'servidor_nome' => 'Servidores.nome',
                'responsavel_nome' => 'ResponsaveisUsuarios.nome', 'setor_nome' => 'Setores.nome',
            ];
            $relacoes = ['Servidores', 'ResponsaveisUsuarios', 'Horarios.Setores'];
        }
        $query = $tabela->find();
        foreach ($relacoes as $relacao) {
            $query->leftJoinWith($relacao);
        }
        foreach ($seletor as $campo => $valor) {
            if (!isset($campos[$campo])) {
                throw new InvalidArgumentException('Campo não permitido no seletor: ' . $campo . '.');
            }
            if (!is_scalar($valor) || is_bool($valor) || trim((string)$valor) === '') {
                throw new InvalidArgumentException('Seletor inválido: ' . $campo . '.');
            }
            if ($campo === 'id' || str_ends_with($campo, '_id') || $campo === 'dia_semana') {
                if (
                    !ctype_digit((string)$valor) || (int)$valor < 1
                    || ($campo === 'dia_semana' && (int)$valor > 7)
                ) {
                    throw new InvalidArgumentException('Seletor numérico inválido: ' . $campo . '.');
                }
            } elseif ($campo === 'data' && !Validation::date($valor, ['ymd'])) {
                throw new InvalidArgumentException('Informe uma data válida no seletor.');
            } elseif (in_array($campo, ['hora', 'hora_inicio', 'hora_fim'], true) && !Validation::time($valor)) {
                throw new InvalidArgumentException('Informe uma hora válida no seletor.');
            }
            $query->where([$campos[$campo] => is_string($valor) ? trim($valor) : $valor]);
        }
        $registros = $query->limit(2)->all()->toList();
        if (empty($registros)) {
            throw new RecordNotFoundException('Registro não encontrado!');
        }
        if (count($registros) > 1) {
            throw new InvalidArgumentException(
                'O seletor encontrou mais de um registro. Complete os campos ou use o ID.',
            );
        }

        return $registros[0];
    }
}
