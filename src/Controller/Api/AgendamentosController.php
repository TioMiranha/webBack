<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\Database\Exception\QueryException;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\I18n\Time;
use Cake\Validation\Validation;
use Exception;
use InvalidArgumentException;

/**
 * Agendamentos Controller
 */
class AgendamentosController extends AppController
{
    /**
     * Cadastra uma reunião com os campos permitidos.
     */
    public function add(): Response
    {
        $this->request->allowMethod(['post']);
        $tabela = $this->fetchTable('Agendamentos');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Não foi possível salvar o agendamento.';

        try {
            $form = array_intersect_key($this->request->getData(), array_flip([
                'responsavel_id', 'servidor_id', 'horario_id', 'data', 'hora', 'assunto',
                'responsavel_nome', 'responsavel', 'servidor_nome', 'servidor', 'setor_id', 'setor_nome', 'setor',
            ]));
            $form = $this->resolverReferencia($form, 'Users', 'servidor_id', 'servidor_nome', ['servidor']);
            $form = $this->resolverReferencia($form, 'Users', 'responsavel_id', 'responsavel_nome', ['responsavel']);
            $form = $this->resolverReferencia($form, 'Setors', 'setor_id', 'setor_nome', ['setor']);
            $form = $this->prepararHorario($form);

            $novoRegistro = $tabela->newEmptyEntity();
            $novoRegistro = $tabela->patchEntity($novoRegistro, $form);

            if ($tabela->save($novoRegistro)) {
                $result = $this->consultarRegistro((int)$novoRegistro->id);
                $message = 'Agendamento salvo com sucesso.';
                $statusCode = 201;
            } else {
                $errors = $novoRegistro->getErrors();
            }
        } catch (QueryException $e) {
            if ($this->conflitoDeReserva($e)) {
                $message = 'Este servidor já possui agendamento nesta data e hora.';
                $errors = ['hora' => ['reservaUnica' => 'Horário já reservado.']];
                $statusCode = 422;
            } else {
                $message = 'Erro: ' . $e->getMessage();
                $statusCode = 500;
            }
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'data' => null, 'message' => $e->getMessage(), 'errors' => ['referencia' => [$e->getMessage()]],
            ], 422);
        } catch (Exception $e) {
            $message = 'Erro: ' . $e->getMessage();
            $statusCode = 500;
        }

        return $this->json(['data' => $result, 'message' => $message, 'errors' => $errors], $statusCode);
    }

    /**
     * Lista as reuniões ativas e aplica os filtros recebidos.
     */
    public function list(): Response
    {
        $this->request->allowMethod(['get', 'post']);
        $statusCode = 200;

        try {
            $sql = $this->sqlAgendamentos() . ' WHERE a.deleted IS NULL';
            $form = $this->request->getData() + $this->request->getQueryParams();
            $params = [];
            $campos = [
                'id' => 'a.id', 'data' => 'a.data', 'hora' => 'a.hora', 'servidor_id' => 'a.servidor_id',
                'responsavel_id' => 'a.responsavel_id', 'horario_id' => 'a.horario_id', 'status' => 'a.status',
                'servidor_nome' => 'serv.nome', 'responsavel_nome' => 'resp.nome', 'setor_nome' => 's.nome',
                'aluno_id' => null, 'aluno_nome' => null,
            ];
            foreach ($campos as $field => $coluna) {
                if (!isset($form[$field]) || $form[$field] === '') {
                    continue;
                }
                if (!is_scalar($form[$field])) {
                    return $this->json(['message' => 'Filtro inválido: ' . $field], 422);
                }
                if ($field === 'data') {
                    if (!Validation::date($form[$field], ['ymd'])) {
                        return $this->json(['message' => 'Informe uma data válida no formato AAAA-MM-DD.'], 422);
                    }
                } elseif ($field === 'hora') {
                    if (!Validation::time($form[$field])) {
                        return $this->json(['message' => 'Informe uma hora válida.'], 422);
                    }
                } elseif (str_ends_with($field, '_nome')) {
                    if (!is_string($form[$field]) || trim($form[$field]) === '') {
                        return $this->json(['message' => 'Filtro de nome inválido: ' . $field], 422);
                    }
                    $form[$field] = trim($form[$field]);
                } elseif (!ctype_digit((string)$form[$field])) {
                    return $this->json(['message' => 'Filtro numérico inválido: ' . $field], 422);
                }
                if ($field === 'aluno_id' || $field === 'aluno_nome') {
                    // Filtra a reunião sem retirar os outros alunos da resposta.
                    $filtroAluno = $field === 'aluno_id' ? 'r.alunos_id' : 'al.nome';
                    $sql .= ' AND EXISTS (
                        SELECT 1 FROM responsaveis r
                        INNER JOIN alunos al ON al.id = r.alunos_id
                        WHERE r.users_id = a.responsavel_id AND ' . $filtroAluno . ' = :' . $field . '
                        AND r.deleted IS NULL AND al.deleted IS NULL
                    )';
                } else {
                    $sql .= ' AND ' . $coluna . ' = :' . $field;
                }
                $params[$field] = $form[$field];
            }
            $sql .= ' ORDER BY a.data, a.hora, a.id, al.nome, al.id';
            $conexao = $this->fetchTable('Agendamentos')->getConnection();
            $results = $conexao->execute($sql, $params)->fetchAll('assoc');
            $result = $this->agruparAgendamentos($results);
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'data' => null, 'message' => $e->getMessage(), 'errors' => ['referencia' => [$e->getMessage()]],
            ], 422);
        } catch (Exception $e) {
            return $this->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }

        return $this->json(['data' => $result], $statusCode);
    }

    /**
     * Consulta o agendamento pelo próprio ID, incluindo registros desativados.
     */
    public function find(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        $statusCode = 200;

        try {
            $registro = $this->localizarRegistro('Agendamentos', $id);
            $id = (string)$registro->id;
            $sql = $this->sqlAgendamentos() . ' WHERE a.id = :id ORDER BY al.nome, al.id';
            $conexao = $this->fetchTable('Agendamentos')->getConnection();
            $results = $conexao->execute($sql, ['id' => (int)$id])->fetchAll('assoc');
            if (empty($results)) {
                return $this->json(['message' => 'Registro não encontrado!', 'code' => 404], 404);
            }
            $result = $this->agruparAgendamentos($results)[0];
        } catch (RecordNotFoundException $e) {
            return $this->json(['message' => 'Registro não encontrado!', 'code' => 404], 404);
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'data' => null, 'message' => $e->getMessage(), 'errors' => ['referencia' => [$e->getMessage()]],
            ], 422);
        } catch (Exception $e) {
            return $this->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }

        return $this->json(['data' => $result], $statusCode);
    }

    /**
     * Altera o agendamento identificado na rota.
     */
    public function edit(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $tabela = $this->fetchTable('Agendamentos');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Não foi possível salvar o agendamento.';

        try {
            $registro = $this->localizarRegistro('Agendamentos', $id);
            if ($registro->deleted !== null || $registro->cancelado_em !== null) {
                return $this->json([
                    'message' => 'Somente agendamentos ativos e não cancelados podem ser editados.',
                ], 422);
            }
            $form = array_intersect_key($this->request->getData(), array_flip([
                'responsavel_id', 'servidor_id', 'horario_id', 'data', 'hora', 'assunto',
                'responsavel_nome', 'responsavel', 'servidor_nome', 'servidor', 'setor_id', 'setor_nome', 'setor',
            ]));
            $form = $this->resolverReferencia($form, 'Users', 'servidor_id', 'servidor_nome', ['servidor']);
            $form = $this->resolverReferencia($form, 'Users', 'responsavel_id', 'responsavel_nome', ['responsavel']);
            $form = $this->resolverReferencia($form, 'Setors', 'setor_id', 'setor_nome', ['setor']);
            $form = $this->prepararHorario($form, $registro);

            if (empty($form)) {
                return $this->json(['message' => 'Informe ao menos um campo do agendamento.'], 422);
            }
            $registro = $tabela->patchEntity($registro, $form);

            if ($tabela->save($registro)) {
                $result = $this->consultarRegistro((int)$registro->id);
                $message = 'Agendamento salvo com sucesso.';
                $statusCode = 200;
            } else {
                $errors = $registro->getErrors();
            }
        } catch (RecordNotFoundException $e) {
            $message = 'Registro não encontrado!';
            $statusCode = 404;
        } catch (QueryException $e) {
            if ($this->conflitoDeReserva($e)) {
                $message = 'Este servidor já possui agendamento nesta data e hora.';
                $errors = ['hora' => ['reservaUnica' => 'Horário já reservado.']];
                $statusCode = 422;
            } else {
                $message = 'Erro: ' . $e->getMessage();
                $statusCode = 500;
            }
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'data' => null, 'message' => $e->getMessage(), 'errors' => ['referencia' => [$e->getMessage()]],
            ], 422);
        } catch (Exception $e) {
            $message = 'Erro: ' . $e->getMessage();
            $statusCode = 500;
        }

        return $this->json(['data' => $result, 'message' => $message, 'errors' => $errors], $statusCode);
    }

    /**
     * Desativa o registro e libera a reserva sem apagar o histórico.
     */
    public function delete(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $tabela = $this->fetchTable('Agendamentos');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Não foi possível salvar o agendamento.';

        try {
            $registro = $this->localizarRegistro('Agendamentos', $id);
            $registro['deleted'] = DateTime::now();

            if ($tabela->save($registro)) {
                $result = $this->consultarRegistro((int)$registro->id);
                $message = 'Agendamento salvo com sucesso.';
                $statusCode = 200;
            } else {
                $errors = $registro->getErrors();
            }
        } catch (RecordNotFoundException $e) {
            $message = 'Registro não encontrado!';
            $statusCode = 404;
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'data' => null, 'message' => $e->getMessage(), 'errors' => ['referencia' => [$e->getMessage()]],
            ], 422);
        } catch (Exception $e) {
            $message = 'Erro: ' . $e->getMessage();
            $statusCode = 500;
        }

        return $this->json(['data' => $result, 'message' => $message, 'errors' => $errors], $statusCode);
    }

    /**
     * Reativa o registro verificando novamente a disponibilidade.
     */
    public function active(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $tabela = $this->fetchTable('Agendamentos');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Não foi possível salvar o agendamento.';

        try {
            $registro = $this->localizarRegistro('Agendamentos', $id);
            $registro['deleted'] = null;

            if ($tabela->save($registro)) {
                $result = $this->consultarRegistro((int)$registro->id);
                $message = 'Agendamento salvo com sucesso.';
                $statusCode = 200;
            } else {
                $errors = $registro->getErrors();
            }
        } catch (RecordNotFoundException $e) {
            $message = 'Registro não encontrado!';
            $statusCode = 404;
        } catch (QueryException $e) {
            if ($this->conflitoDeReserva($e)) {
                $message = 'Este servidor já possui agendamento nesta data e hora.';
                $errors = ['hora' => ['reservaUnica' => 'Horário já reservado.']];
                $statusCode = 422;
            } else {
                $message = 'Erro: ' . $e->getMessage();
                $statusCode = 500;
            }
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'data' => null, 'message' => $e->getMessage(), 'errors' => ['referencia' => [$e->getMessage()]],
            ], 422);
        } catch (Exception $e) {
            $message = 'Erro: ' . $e->getMessage();
            $statusCode = 500;
        }

        return $this->json(['data' => $result, 'message' => $message, 'errors' => $errors], $statusCode);
    }

    /**
     * Cancela a reunião mantendo o registro para consulta.
     */
    public function cancel(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'patch']);
        $tabela = $this->fetchTable('Agendamentos');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Não foi possível salvar o agendamento.';

        try {
            $registro = $this->localizarRegistro('Agendamentos', $id);
            if ($registro->deleted !== null) {
                return $this->json(['message' => 'O agendamento está desativado.'], 422);
            }
            if ($registro->cancelado_em === null) {
                $registro['cancelado_em'] = DateTime::now();
            }

            if ($tabela->save($registro)) {
                $result = $this->consultarRegistro((int)$registro->id);
                $message = 'Agendamento salvo com sucesso.';
                $statusCode = 200;
            } else {
                $errors = $registro->getErrors();
            }
        } catch (RecordNotFoundException $e) {
            $message = 'Registro não encontrado!';
            $statusCode = 404;
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'data' => null, 'message' => $e->getMessage(), 'errors' => ['referencia' => [$e->getMessage()]],
            ], 422);
        } catch (Exception $e) {
            $message = 'Erro: ' . $e->getMessage();
            $statusCode = 500;
        }

        return $this->json(['data' => $result, 'message' => $message, 'errors' => $errors], $statusCode);
    }

    /**
     * Busca agendamentos pela data do atendimento.
     */
    public function findByData(?string $data = null): Response
    {
        $this->request->allowMethod(['get']);
        $statusCode = 200;

        try {
            if (!Validation::date($data, ['ymd'])) {
                return $this->json(['message' => 'Informe uma data válida no formato AAAA-MM-DD.'], 422);
            }
            $sql = $this->sqlAgendamentos() . ' WHERE a.data = :data AND a.deleted IS NULL';
            $params = ['data' => $data];
            $sql .= ' ORDER BY a.data, a.hora, a.id, al.nome, al.id';
            $conexao = $this->fetchTable('Agendamentos')->getConnection();
            $results = $conexao->execute($sql, $params)->fetchAll('assoc');
            $result = $this->agruparAgendamentos($results);
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'data' => null, 'message' => $e->getMessage(), 'errors' => ['referencia' => [$e->getMessage()]],
            ], 422);
        } catch (Exception $e) {
            return $this->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }

        return $this->json(['data' => $result], $statusCode);
    }

    /**
     * Busca pelo ID do usuário servidor e, opcionalmente, pela data.
     */
    public function findByServidor(?string $id = null, ?string $data = null): Response
    {
        $this->request->allowMethod(['get']);
        $statusCode = 200;

        try {
            $porNome = (bool)$this->request->getParam('porNome');
            $coluna = $porNome ? 'serv.nome' : 'a.servidor_id';
            $sql = $this->sqlAgendamentos() . ' WHERE ' . $coluna . ' = :id AND a.deleted IS NULL';
            $params = ['id' => $porNome ? trim((string)$id) : (int)$id];
            if ($data !== null) {
                if (!Validation::date($data, ['ymd'])) {
                    return $this->json(['message' => 'Informe uma data válida no formato AAAA-MM-DD.'], 422);
                }
                $sql .= ' AND a.data = :data';
                $params['data'] = $data;
            }
            $sql .= ' ORDER BY a.data, a.hora, a.id, al.nome, al.id';
            $conexao = $this->fetchTable('Agendamentos')->getConnection();
            $results = $conexao->execute($sql, $params)->fetchAll('assoc');
            $result = $this->agruparAgendamentos($results);
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'data' => null, 'message' => $e->getMessage(), 'errors' => ['referencia' => [$e->getMessage()]],
            ], 422);
        } catch (Exception $e) {
            return $this->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }

        return $this->json(['data' => $result], $statusCode);
    }

    /**
     * Busca pelo ID do usuário responsável.
     */
    public function findByResponsavel(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        $statusCode = 200;

        try {
            $porNome = (bool)$this->request->getParam('porNome');
            $coluna = $porNome ? 'resp.nome' : 'a.responsavel_id';
            $sql = $this->sqlAgendamentos() . ' WHERE ' . $coluna . ' = :id AND a.deleted IS NULL';
            $params = ['id' => $porNome ? trim((string)$id) : (int)$id];
            $sql .= ' ORDER BY a.data, a.hora, a.id, al.nome, al.id';
            $conexao = $this->fetchTable('Agendamentos')->getConnection();
            $results = $conexao->execute($sql, $params)->fetchAll('assoc');
            $result = $this->agruparAgendamentos($results);
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'data' => null, 'message' => $e->getMessage(), 'errors' => ['referencia' => [$e->getMessage()]],
            ], 422);
        } catch (Exception $e) {
            return $this->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }

        return $this->json(['data' => $result], $statusCode);
    }

    /**
     * Busca reuniões de um aluno mantendo os demais alunos do responsável.
     */
    public function findByAluno(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        $statusCode = 200;

        try {
            $porNome = (bool)$this->request->getParam('porNome');
            $coluna = $porNome ? 'al.nome' : 'r.alunos_id';
            $sql = $this->sqlAgendamentos() . ' WHERE a.deleted IS NULL AND EXISTS (
                SELECT 1 FROM responsaveis r INNER JOIN alunos al ON al.id = r.alunos_id
                WHERE r.users_id = a.responsavel_id AND ' . $coluna . ' = :id
                AND r.deleted IS NULL AND al.deleted IS NULL
            )';
            $params = ['id' => $porNome ? trim((string)$id) : (int)$id];
            $sql .= ' ORDER BY a.data, a.hora, a.id, al.nome, al.id';
            $conexao = $this->fetchTable('Agendamentos')->getConnection();
            $results = $conexao->execute($sql, $params)->fetchAll('assoc');
            $result = $this->agruparAgendamentos($results);
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'data' => null, 'message' => $e->getMessage(), 'errors' => ['referencia' => [$e->getMessage()]],
            ], 422);
        } catch (Exception $e) {
            return $this->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }

        return $this->json(['data' => $result], $statusCode);
    }

    /**
     * Mantém as mesmas colunas e relações em todas as consultas da agenda.
     */
    private function sqlAgendamentos(): string
    {
        return 'SELECT a.id, a.data, a.hora, a.servidor_id, a.responsavel_id, a.horario_id,
                       a.assunto, a.status, a.cancelado_em, a.deleted,
                       serv.nome AS servidor, resp.nome AS responsavel,
                       h.setor_id, s.nome AS setor,
                       al.id AS aluno_id, al.nome AS aluno, COALESCE(c.nome, al.curso) AS curso
                FROM agendamentos a
                LEFT JOIN users serv ON serv.id = a.servidor_id
                LEFT JOIN users resp ON resp.id = a.responsavel_id
                LEFT JOIN horarios h ON h.id = a.horario_id
                LEFT JOIN setors s ON s.id = h.setor_id
                LEFT JOIN responsaveis r ON r.users_id = a.responsavel_id AND r.deleted IS NULL
                LEFT JOIN alunos al ON al.id = r.alunos_id AND al.deleted IS NULL
                LEFT JOIN cursos c ON c.id = al.curso_id';
    }

    /**
     * Agrupa os alunos e seus cursos na mesma ordem, sem repetir a reunião.
     */
    private function agruparAgendamentos(array $results): array
    {
        $registros = [];
        foreach ($results as $value) {
            $id = (int)$value['id'];
            if (!isset($registros[$id])) {
                $registro = $value;
                unset($registro['aluno_id'], $registro['aluno'], $registro['curso']);
                foreach (['id', 'servidor_id', 'responsavel_id', 'horario_id', 'setor_id', 'status'] as $field) {
                    $registro[$field] = $registro[$field] === null ? null : (int)$registro[$field];
                }
                $registro['cancelado'] = $registro['cancelado_em'] !== null;
                $registro['alunos'] = [];
                $registros[$id] = $registro;
            }
            if ($value['aluno_id'] !== null) {
                $registros[$id]['alunos'][(int)$value['aluno_id']] = [
                    'id' => (int)$value['aluno_id'], 'nome' => $value['aluno'], 'curso' => $value['curso'],
                ];
            }
        }
        foreach ($registros as &$registro) {
            $registro['alunos'] = array_values($registro['alunos']);
            $registro['aluno'] = implode(', ', array_column($registro['alunos'], 'nome'));
            $registro['curso'] = implode(', ', array_column($registro['alunos'], 'curso'));
        }
        unset($registro);

        return array_values($registros);
    }

    /**
     * Retorna os dados completos após cadastrar ou alterar um agendamento.
     */
    private function consultarRegistro(int $id): array
    {
        $sql = $this->sqlAgendamentos() . ' WHERE a.id = :id ORDER BY al.nome, al.id';
        $conexao = $this->fetchTable('Agendamentos')->getConnection();
        $results = $conexao->execute($sql, ['id' => $id])->fetchAll('assoc');

        return $this->agruparAgendamentos($results)[0];
    }

    /**
     * Escolhe somente uma janela compatível quando o horário não é identificado por ID.
     */
    private function prepararHorario(array $form, ?EntityInterface $registro = null): array
    {
        $setorId = $form['setor_id'] ?? null;
        unset($form['setor_id']);
        if ($setorId !== null && (!is_scalar($setorId) || !ctype_digit((string)$setorId) || (int)$setorId < 1)) {
            throw new InvalidArgumentException('Informe um setor válido.');
        }
        $selecionar = !array_key_exists('horario_id', $form) && ($registro === null || $setorId !== null
            || isset($form['servidor_id']) || isset($form['data']) || isset($form['hora']));
        if (!$selecionar && $setorId === null) {
            return $form;
        }
        $servidorId = $form['servidor_id'] ?? $registro?->get('servidor_id');
        $data = $form['data'] ?? $registro?->get('data');
        $hora = $form['hora'] ?? $registro?->get('hora');
        if ($data instanceof Date) {
            $data = $data->format('Y-m-d');
        }
        if ($hora instanceof Time) {
            $hora = $hora->format('H:i:s');
        }
        if (
            !is_scalar($servidorId) || !ctype_digit((string)$servidorId)
            || !is_string($data) || !Validation::date($data, ['ymd'])
            || !is_string($hora) || !Validation::time($hora)
        ) {
            return $form;
        }
        $hora = (new Time($hora))->format('H:i:s');
        $servidor = $this->fetchTable('Users')->find()->where(['id' => (int)$servidorId])->first();
        if (!$servidor || $servidor->setor_id === null) {
            throw new InvalidArgumentException('O servidor precisa estar cadastrado em um setor.');
        }
        if ($setorId !== null && (int)$setorId !== (int)$servidor->setor_id) {
            throw new InvalidArgumentException('O setor informado não corresponde ao setor do servidor.');
        }
        if (!$selecionar) {
            if (
                !is_scalar($form['horario_id']) || is_bool($form['horario_id'])
                || !ctype_digit((string)$form['horario_id']) || (int)$form['horario_id'] < 1
            ) {
                throw new InvalidArgumentException('Informe um horario_id válido.');
            }
            $horario = $this->fetchTable('Horarios')->find()->where(['id' => $form['horario_id']])->first();
            if (!$horario || (int)$horario->setor_id !== (int)$setorId) {
                throw new InvalidArgumentException('O horário informado não pertence ao setor escolhido.');
            }

            return $form;
        }
        // Na edição, preserva o ID anteriormente escolhido se a janela continua válida.
        if ($registro?->get('horario_id') !== null) {
            $atual = $this->fetchTable('Horarios')->find()->where([
                'id' => $registro->get('horario_id'), 'deleted IS' => null,
            ])->first();
            if (
                $atual && (int)$atual->setor_id === (int)$servidor->setor_id
                && (int)$atual->dia_semana === (int)(new Date($data))->format('N')
                && $hora >= $atual->hora_inicio->format('H:i:s') && $hora < $atual->hora_fim->format('H:i:s')
            ) {
                return $form;
            }
        }
        $conditions = [
            'setor_id' => $servidor->setor_id, 'dia_semana' => (int)(new Date($data))->format('N'),
            'hora_inicio <=' => $hora, 'hora_fim >' => $hora, 'deleted IS' => null,
        ];
        $horarios = $this->fetchTable('Horarios')->find()->where($conditions)->limit(2)->all()->toList();
        if (empty($horarios)) {
            throw new InvalidArgumentException('Nenhum horário ativo do setor atende à data e hora informadas.');
        }
        if (count($horarios) > 1) {
            throw new InvalidArgumentException('Mais de um horário atende à solicitação. Informe horario_id.');
        }
        $form['horario_id'] = (int)$horarios[0]->id;

        return $form;
    }

    /**
     * Identifica a restrição de reserva sem tratar outros erros de SQL como conflito.
     */
    private function conflitoDeReserva(QueryException $exception): bool
    {
        $message = $exception->getPrevious()?->getMessage() ?? '';

        return (str_contains($message, 'Duplicate entry')
            && str_contains($message, 'agendamentos_servidor_reserva'))
            || str_contains(
                $message,
                'UNIQUE constraint failed: agendamentos.servidor_id, agendamentos.data, '
                . 'agendamentos.hora, agendamentos.reserva_ativa',
            );
    }
}
