<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\I18n\Time;
use Cake\Validation\Validation;
use Exception;
use InvalidArgumentException;

class AgendamentosController extends AppController
{

    public function add(): Response
    {
        $this->request->allowMethod(['post']);
        $tabela = $this->fetchTable('Agendamentos');
        $conexao = $tabela->getConnection();
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Não foi possível salvar o agendamento.';

        try {
            $form = array_intersect_key($this->request->getData(), array_flip([
                'responsavel_id',
                'responsavel_nome',
                'responsavel',
                'servidor_id',
                'servidor_nome',
                'servidor',
                'setor_id',
                'setor_nome',
                'setor',
                'horario_id',
                'data',
                'hora',
                'assunto',
            ]));

            if (isset($form['responsavel_id']) && (!is_numeric($form['responsavel_id']) || $form['responsavel_id'] < 1 || (int)$form['responsavel_id'] != $form['responsavel_id'])) {
                return $this->json(['message' => 'ID do responsável inválido.'], 422);
            }
            if (isset($form['servidor_id']) && (!is_numeric($form['servidor_id']) || $form['servidor_id'] < 1 || (int)$form['servidor_id'] != $form['servidor_id'])) {
                return $this->json(['message' => 'ID do servidor inválido.'], 422);
            }
            if (isset($form['setor_id']) && (!is_numeric($form['setor_id']) || $form['setor_id'] < 1 || (int)$form['setor_id'] != $form['setor_id'])) {
                return $this->json(['message' => 'ID do setor inválido.'], 422);
            }
            if (isset($form['horario_id']) && (!is_numeric($form['horario_id']) || $form['horario_id'] < 1 || (int)$form['horario_id'] != $form['horario_id'])) {
                return $this->json(['message' => 'ID do horário inválido.'], 422);
            }

            if (!isset($form['data']) || !is_string($form['data']) || !Validation::date($form['data'], ['ymd'])) {
                return $this->json(['message' => 'Informe uma data válida no formato AAAA-MM-DD.'], 422);
            }
            if (!isset($form['hora']) || !is_string($form['hora']) || !Validation::time($form['hora'])) {
                return $this->json(['message' => 'Informe uma hora válida.'], 422);
            }
            $dataTimestamp = strtotime(str_replace(['/', '.'], '-', $form['data']));
            if ($dataTimestamp === false) {
                return $this->json(['message' => 'Data inválida.'], 422);
            }
            $form['data'] = date('Y-m-d', $dataTimestamp);
            $form['hora'] = (new Time($form['hora']))->format('H:i:s');
            $diaSemana = (int)date('N', $dataTimestamp);

            $nomeResponsavel = $form['responsavel_nome'] ?? $form['responsavel'] ?? null;
            if ($nomeResponsavel !== null) {
                if (!is_string($nomeResponsavel) || trim($nomeResponsavel) == '') {
                    return $this->json(['message' => 'Nome do responsável inválido.'], 422);
                }
                $nomeResponsavel = trim($nomeResponsavel);
                if (isset($form['responsavel']) && (!is_string($form['responsavel']) || trim($form['responsavel']) != $nomeResponsavel)) {
                    return $this->json(['message' => 'Os nomes do responsável informados são diferentes.'], 422);
                }
            }
            $nomeServidor = $form['servidor_nome'] ?? $form['servidor'] ?? null;
            if ($nomeServidor !== null) {
                if (!is_string($nomeServidor) || trim($nomeServidor) == '') {
                    return $this->json(['message' => 'Nome do servidor inválido.'], 422);
                }
                $nomeServidor = trim($nomeServidor);
                if (isset($form['servidor']) && (!is_string($form['servidor']) || trim($form['servidor']) != $nomeServidor)) {
                    return $this->json(['message' => 'Os nomes do servidor informados são diferentes.'], 422);
                }
            }
            $nomeSetor = $form['setor_nome'] ?? $form['setor'] ?? null;
            if ($nomeSetor !== null) {
                if (!is_string($nomeSetor) || trim($nomeSetor) == '') {
                    return $this->json(['message' => 'Nome do setor inválido.'], 422);
                }
                $nomeSetor = trim($nomeSetor);
                if (isset($form['setor']) && (!is_string($form['setor']) || trim($form['setor']) != $nomeSetor)) {
                    return $this->json(['message' => 'Os nomes do setor informados são diferentes.'], 422);
                }
            }
            if (!isset($form['responsavel_id']) && $nomeResponsavel === null) {
                return $this->json(['message' => 'Informe o ID ou o nome do responsável.'], 422);
            }
            if (!isset($form['servidor_id']) && $nomeServidor === null) {
                return $this->json(['message' => 'Informe o ID ou o nome do servidor.'], 422);
            }
            if (isset($form['assunto'])) {
                if (!is_string($form['assunto'])) {
                    return $this->json(['message' => 'O assunto precisa ser um texto.'], 422);
                }
                $form['assunto'] = trim($form['assunto']);
            }

            // Busca o responsável pelo ID, pelo nome ou pelos dois.
            $sql = 'SELECT id FROM users WHERE deleted IS NULL';
            $params = [];
            if (isset($form['responsavel_id'])) {
                $sql .= ' AND id = :id';
                $params['id'] = (int)$form['responsavel_id'];
            }
            if ($nomeResponsavel !== null) {
                $sql .= ' AND nome = :nome';
                $params['nome'] = $nomeResponsavel;
            }
            $responsaveis = $conexao->execute($sql . ' LIMIT 2', $params)->fetchAll('assoc');
            if (empty($responsaveis)) {
                return $this->json(['message' => 'Responsável não encontrado, desativado ou ID e nome incompatíveis.'], 422);
            }
            if (count($responsaveis) > 1) {
                return $this->json(['message' => 'Nome de responsável repetido. Informe responsavel_id.'], 422);
            }
            $form['responsavel_id'] = (int)$responsaveis[0]['id'];

            // Busca o servidor e confere o setor de atendimento.
            $sql = 'SELECT u.id, u.setor_id FROM users u
                    INNER JOIN setors s ON s.id = u.setor_id
                    WHERE u.deleted IS NULL AND u.agendar = 1 AND s.deleted IS NULL';
            $params = [];
            if (isset($form['servidor_id'])) {
                $sql .= ' AND u.id = :id';
                $params['id'] = (int)$form['servidor_id'];
            }
            if ($nomeServidor !== null) {
                $sql .= ' AND u.nome = :nome';
                $params['nome'] = $nomeServidor;
            }
            $servidores = $conexao->execute($sql . ' LIMIT 2', $params)->fetchAll('assoc');
            if (empty($servidores)) {
                return $this->json(['message' => 'Servidor não encontrado, desativado, sem permissão ou sem setor ativo. Confira o ID e o nome.'], 422);
            }
            if (count($servidores) > 1) {
                return $this->json(['message' => 'Nome de servidor repetido. Informe servidor_id.'], 422);
            }
            $servidor = $servidores[0];
            $form['servidor_id'] = (int)$servidor['id'];

            if (isset($form['setor_id']) && (int)$form['setor_id'] != (int)$servidor['setor_id']) {
                return $this->json(['message' => 'O setor informado não pertence ao servidor.'], 422);
            }
            if ($nomeSetor !== null) {
                $setor = $conexao->execute('SELECT id FROM setors WHERE id = :id AND nome = :nome', [
                    'id' => $servidor['setor_id'],
                    'nome' => $nomeSetor,
                ])->fetch('assoc');
                if (!$setor) {
                    return $this->json(['message' => 'O setor informado não pertence ao servidor.'], 422);
                }
            }

            // A mesma consulta atende ao horário informado e à escolha automática.
            $sql = 'SELECT id FROM horarios
                    WHERE setor_id = :setor_id AND dia_semana = :dia_semana
                    AND hora_inicio <= :hora_inicio AND hora_fim > :hora_fim
                    AND deleted IS NULL';
            $params = [
                'setor_id' => $servidor['setor_id'],
                'dia_semana' => $diaSemana,
                'hora_inicio' => $form['hora'],
                'hora_fim' => $form['hora'],
            ];
            if (isset($form['horario_id'])) {
                $sql .= ' AND id = :id';
                $params['id'] = (int)$form['horario_id'];
            }
            $horarios = $conexao->execute($sql . ' LIMIT 2', $params)->fetchAll('assoc');
            if (empty($horarios)) {
                return $this->json(['message' => 'Horário inexistente ou indisponível para o setor, a data e a hora informados.'], 422);
            }
            if (count($horarios) > 1) {
                return $this->json(['message' => 'Mais de um horário encontrado. Informe horario_id.'], 422);
            }
            $form['horario_id'] = (int)$horarios[0]['id'];

            $sql = 'SELECT id FROM agendamentos
                    WHERE servidor_id = :servidor_id AND data = :data AND hora = :hora
                    AND deleted IS NULL AND cancelado_em IS NULL';
            $conflito = $conexao->execute($sql, [
                'servidor_id' => $form['servidor_id'],
                'data' => $form['data'],
                'hora' => $form['hora'],
            ])->fetch('assoc');
            if ($conflito) {
                return $this->json(['data' => null, 'message' => 'O servidor já possui uma reunião marcada nesse horário.', 'errors' => []], 422);
            }

            $novoRegistro = $tabela->newEmptyEntity();
            $novoRegistro = $tabela->patchEntity($novoRegistro, $form);
            if ($tabela->save($novoRegistro)) {
                $result = $novoRegistro;
                $message = 'Agendamento salvo com sucesso.';
                $statusCode = 201;
            } else {
                $errors = $novoRegistro->getErrors();
            }
        } catch (Exception $e) {
            $message = 'Erro: ' . $e->getMessage();
            $statusCode = 500;
        }

        return $this->json(['data' => $result, 'message' => $message, 'errors' => $errors], $statusCode);
    }

    public function list(): Response
    {
        $this->request->allowMethod(['get', 'post']);

        try {
            $tabela = $this->fetchTable('Agendamentos');
            $conexao = $tabela->getConnection();
            $sql = 'SELECT
                        a.id,
                        a.data,
                        a.hora,
                        serv.nome AS servidor,
                        resp.nome AS responsavel,
                        GROUP_CONCAT(
                            al.nome ORDER BY al.nome, al.id SEPARATOR ", "
                        ) AS aluno,
                        GROUP_CONCAT(
                            COALESCE(c.nome, al.curso, "")
                            ORDER BY al.nome, al.id SEPARATOR ", "
                        ) AS curso
                    FROM agendamentos a
                    LEFT JOIN users serv ON serv.id = a.servidor_id
                    LEFT JOIN users resp ON resp.id = a.responsavel_id
                    LEFT JOIN responsaveis r
                        ON r.users_id = a.responsavel_id
                        AND r.deleted IS NULL
                    LEFT JOIN alunos al
                        ON al.id = r.alunos_id
                        AND al.deleted IS NULL
                    LEFT JOIN cursos c ON c.id = al.curso_id
                    WHERE a.deleted IS NULL
                    GROUP BY a.id, a.data, a.hora, serv.nome, resp.nome
                    ORDER BY a.data, a.hora, a.id';

            $result = $conexao->execute($sql)->fetchAll('assoc');
        } catch (Exception $e) {
            return $this->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }

        return $this->json(['data' => $result], 200);
    }

    public function find(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);

        try {
            $tabela = $this->fetchTable('Agendamentos');
            $conexao = $tabela->getConnection();
            $sql = 'SELECT
                        a.id,
                        a.data,
                        a.hora,
                        serv.nome AS servidor,
                        resp.nome AS responsavel,
                        GROUP_CONCAT(
                            al.nome ORDER BY al.nome, al.id SEPARATOR ", "
                        ) AS aluno,
                        GROUP_CONCAT(
                            COALESCE(c.nome, al.curso, "")
                            ORDER BY al.nome, al.id SEPARATOR ", "
                        ) AS curso
                    FROM agendamentos a
                    LEFT JOIN users serv ON serv.id = a.servidor_id
                    LEFT JOIN users resp ON resp.id = a.responsavel_id
                    LEFT JOIN responsaveis r
                        ON r.users_id = a.responsavel_id
                        AND r.deleted IS NULL
                    LEFT JOIN alunos al
                        ON al.id = r.alunos_id
                        AND al.deleted IS NULL
                    LEFT JOIN cursos c ON c.id = al.curso_id
                    WHERE a.id = :id
                    AND a.deleted IS NULL
                    GROUP BY a.id, a.data, a.hora, serv.nome, resp.nome';

            $result = $conexao->execute($sql, ['id' => $id])->fetch('assoc');

            if (!$result) {
                return $this->json(['message' => 'Agendamento não encontrado.'], 404);
            }
        } catch (Exception $e) {
            return $this->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }

        return $this->json(['data' => $result], 200);
    }

    public function edit(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $tabela = $this->fetchTable('Agendamentos');
        $conexao = $tabela->getConnection();
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Não foi possível salvar o agendamento.';
        if (!is_numeric($id) || $id < 1 || (int)$id != $id) {
            return $this->json(['message' => 'ID do agendamento inválido.'], 422);
        }

        try {
            $registro = $tabela->get($id);
            if ($registro->deleted !== null || $registro->cancelado_em !== null) {
                return $this->json(['message' => 'Somente agendamentos ativos e não cancelados podem ser editados.'], 422);
            }
            $form = array_intersect_key($this->request->getData(), array_flip([
                'responsavel_id',
                'responsavel_nome',
                'responsavel',
                'servidor_id',
                'servidor_nome',
                'servidor',
                'setor_id',
                'setor_nome',
                'setor',
                'horario_id',
                'data',
                'hora',
                'assunto',
            ]));

            if (empty($form)) {
                return $this->json(['message' => 'Informe ao menos um campo para alterar.'], 422);
            }

            // Mantém os valores atuais dos campos que não vieram na requisição.
            if (!array_key_exists('data', $form)) {
                $form['data'] = $registro->data?->format('Y-m-d');
            }
            if (!array_key_exists('hora', $form)) {
                $form['hora'] = $registro->hora?->format('H:i:s');
            }
            if (!array_key_exists('responsavel_id', $form) && !isset($form['responsavel_nome']) && !isset($form['responsavel'])) {
                $form['responsavel_id'] = $registro->responsavel_id;
            }
            if (!array_key_exists('servidor_id', $form) && !isset($form['servidor_nome']) && !isset($form['servidor'])) {
                $form['servidor_id'] = $registro->servidor_id;
            }

            if (isset($form['responsavel_id']) && (!is_numeric($form['responsavel_id']) || $form['responsavel_id'] < 1 || (int)$form['responsavel_id'] != $form['responsavel_id'])) {
                return $this->json(['message' => 'ID do responsável inválido.'], 422);
            }
            if (isset($form['servidor_id']) && (!is_numeric($form['servidor_id']) || $form['servidor_id'] < 1 || (int)$form['servidor_id'] != $form['servidor_id'])) {
                return $this->json(['message' => 'ID do servidor inválido.'], 422);
            }
            if (isset($form['setor_id']) && (!is_numeric($form['setor_id']) || $form['setor_id'] < 1 || (int)$form['setor_id'] != $form['setor_id'])) {
                return $this->json(['message' => 'ID do setor inválido.'], 422);
            }
            if (isset($form['horario_id']) && (!is_numeric($form['horario_id']) || $form['horario_id'] < 1 || (int)$form['horario_id'] != $form['horario_id'])) {
                return $this->json(['message' => 'ID do horário inválido.'], 422);
            }

            if (!isset($form['data']) || !is_string($form['data']) || !Validation::date($form['data'], ['ymd'])) {
                return $this->json(['message' => 'Informe uma data válida no formato AAAA-MM-DD.'], 422);
            }
            if (!isset($form['hora']) || !is_string($form['hora']) || !Validation::time($form['hora'])) {
                return $this->json(['message' => 'Informe uma hora válida.'], 422);
            }
            $dataTimestamp = strtotime(str_replace(['/', '.'], '-', $form['data']));
            if ($dataTimestamp === false) {
                return $this->json(['message' => 'Data inválida.'], 422);
            }
            $form['data'] = date('Y-m-d', $dataTimestamp);
            $form['hora'] = (new Time($form['hora']))->format('H:i:s');
            $diaSemana = (int)date('N', $dataTimestamp);

            $nomeResponsavel = $form['responsavel_nome'] ?? $form['responsavel'] ?? null;
            if ($nomeResponsavel !== null) {
                if (!is_string($nomeResponsavel) || trim($nomeResponsavel) == '') {
                    return $this->json(['message' => 'Nome do responsável inválido.'], 422);
                }
                $nomeResponsavel = trim($nomeResponsavel);
                if (isset($form['responsavel']) && (!is_string($form['responsavel']) || trim($form['responsavel']) != $nomeResponsavel)) {
                    return $this->json(['message' => 'Os nomes do responsável informados são diferentes.'], 422);
                }
            }
            $nomeServidor = $form['servidor_nome'] ?? $form['servidor'] ?? null;
            if ($nomeServidor !== null) {
                if (!is_string($nomeServidor) || trim($nomeServidor) == '') {
                    return $this->json(['message' => 'Nome do servidor inválido.'], 422);
                }
                $nomeServidor = trim($nomeServidor);
                if (isset($form['servidor']) && (!is_string($form['servidor']) || trim($form['servidor']) != $nomeServidor)) {
                    return $this->json(['message' => 'Os nomes do servidor informados são diferentes.'], 422);
                }
            }
            $nomeSetor = $form['setor_nome'] ?? $form['setor'] ?? null;
            if ($nomeSetor !== null) {
                if (!is_string($nomeSetor) || trim($nomeSetor) == '') {
                    return $this->json(['message' => 'Nome do setor inválido.'], 422);
                }
                $nomeSetor = trim($nomeSetor);
                if (isset($form['setor']) && (!is_string($form['setor']) || trim($form['setor']) != $nomeSetor)) {
                    return $this->json(['message' => 'Os nomes do setor informados são diferentes.'], 422);
                }
            }
            if (!isset($form['responsavel_id']) && $nomeResponsavel === null) {
                return $this->json(['message' => 'Informe o ID ou o nome do responsável.'], 422);
            }
            if (!isset($form['servidor_id']) && $nomeServidor === null) {
                return $this->json(['message' => 'Informe o ID ou o nome do servidor.'], 422);
            }
            if (isset($form['assunto'])) {
                if (!is_string($form['assunto'])) {
                    return $this->json(['message' => 'O assunto precisa ser um texto.'], 422);
                }
                $form['assunto'] = trim($form['assunto']);
            }

            $sql = 'SELECT id FROM users WHERE deleted IS NULL';
            $params = [];
            if (isset($form['responsavel_id'])) {
                $sql .= ' AND id = :id';
                $params['id'] = (int)$form['responsavel_id'];
            }
            if ($nomeResponsavel !== null) {
                $sql .= ' AND nome = :nome';
                $params['nome'] = $nomeResponsavel;
            }
            $responsaveis = $conexao->execute($sql . ' LIMIT 2', $params)->fetchAll('assoc');
            if (empty($responsaveis)) {
                return $this->json(['message' => 'Responsável não encontrado, desativado ou ID e nome incompatíveis.'], 422);
            }
            if (count($responsaveis) > 1) {
                return $this->json(['message' => 'Nome de responsável repetido. Informe responsavel_id.'], 422);
            }
            $form['responsavel_id'] = (int)$responsaveis[0]['id'];

            $sql = 'SELECT u.id, u.setor_id FROM users u
                    INNER JOIN setors s ON s.id = u.setor_id
                    WHERE u.deleted IS NULL AND u.agendar = 1 AND s.deleted IS NULL';
            $params = [];
            if (isset($form['servidor_id'])) {
                $sql .= ' AND u.id = :id';
                $params['id'] = (int)$form['servidor_id'];
            }
            if ($nomeServidor !== null) {
                $sql .= ' AND u.nome = :nome';
                $params['nome'] = $nomeServidor;
            }
            $servidores = $conexao->execute($sql . ' LIMIT 2', $params)->fetchAll('assoc');
            if (empty($servidores)) {
                return $this->json(['message' => 'Servidor não encontrado, desativado, sem permissão ou sem setor ativo. Confira o ID e o nome.'], 422);
            }
            if (count($servidores) > 1) {
                return $this->json(['message' => 'Nome de servidor repetido. Informe servidor_id.'], 422);
            }
            $servidor = $servidores[0];
            $form['servidor_id'] = (int)$servidor['id'];

            if (isset($form['setor_id']) && (int)$form['setor_id'] != (int)$servidor['setor_id']) {
                return $this->json(['message' => 'O setor informado não pertence ao servidor.'], 422);
            }
            if ($nomeSetor !== null) {
                $setor = $conexao->execute('SELECT id FROM setors WHERE id = :id AND nome = :nome', [
                    'id' => $servidor['setor_id'],
                    'nome' => $nomeSetor,
                ])->fetch('assoc');
                if (!$setor) {
                    return $this->json(['message' => 'O setor informado não pertence ao servidor.'], 422);
                }
            }
            $sql = 'SELECT id FROM horarios
                    WHERE setor_id = :setor_id AND dia_semana = :dia_semana
                    AND hora_inicio <= :hora_inicio AND hora_fim > :hora_fim
                    AND deleted IS NULL';
            $params = [
                'setor_id' => $servidor['setor_id'],
                'dia_semana' => $diaSemana,
                'hora_inicio' => $form['hora'],
                'hora_fim' => $form['hora'],
            ];
            if (!array_key_exists('horario_id', $form) && $registro->horario_id !== null) {
                $paramsAtuais = $params;
                $paramsAtuais['id'] = $registro->horario_id;
                $horarioAtual = $conexao->execute($sql . ' AND id = :id', $paramsAtuais)->fetch('assoc');
                if ($horarioAtual) {
                    $form['horario_id'] = (int)$horarioAtual['id'];
                }
            }
            if (isset($form['horario_id'])) {
                $sql .= ' AND id = :id';
                $params['id'] = (int)$form['horario_id'];
            }
            $horarios = $conexao->execute($sql . ' LIMIT 2', $params)->fetchAll('assoc');
            if (empty($horarios)) {
                return $this->json(['message' => 'Horário inexistente ou indisponível para o setor, a data e a hora informados.'], 422);
            }
            if (count($horarios) > 1) {
                return $this->json(['message' => 'Mais de um horário encontrado. Informe horario_id.'], 422);
            }
            $form['horario_id'] = (int)$horarios[0]['id'];

            $sql = 'SELECT id FROM agendamentos
                    WHERE servidor_id = :servidor_id
                    AND data = :data
                    AND hora = :hora
                    AND id != :id
                    AND deleted IS NULL
                    AND cancelado_em IS NULL';

            $conflito = $conexao->execute($sql, [
                'servidor_id' => $form['servidor_id'],
                'data' => $form['data'],
                'hora' => $form['hora'],
                'id' => $registro->id,
            ])->fetch('assoc');
            if ($conflito) {
                return $this->json(['data' => null, 'message' => 'O servidor já possui uma reunião marcada nesse horário.', 'errors' => []], 422);
            }

            $registro = $tabela->patchEntity($registro, $form);
            if ($tabela->save($registro)) {
                $result = $registro;
                $message = 'Agendamento alterado com sucesso.';
                $statusCode = 200;
            } else {
                $errors = $registro->getErrors();
            }
        } catch (RecordNotFoundException $e) {
            $message = 'Agendamento não encontrado.';
            $statusCode = 404;
        } catch (Exception $e) {
            $message = 'Erro: ' . $e->getMessage();
            $statusCode = 500;
        }
        return $this->json(['data' => $result, 'message' => $message, 'errors' => $errors], $statusCode);
    }

    public function delete(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $tabela = $this->fetchTable('Agendamentos');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Não foi possível salvar o agendamento.';
        if (!is_numeric($id)) {
            return $this->json(['message' => 'O ID do agendamento precisa ser um número.'], 422);
        }

        if ($id < 1) {
            return $this->json(['message' => 'ID do agendamento inválido.'], 422);
        }

        $agendamentoId = (int)$id;
        if ($agendamentoId != $id) {
            return $this->json(['message' => 'O ID do agendamento precisa ser inteiro.'], 422);
        }
        try {
            $registro = $tabela->get($agendamentoId);

            $registro->deleted = date('Y-m-d H:i:s');

            if ($tabela->save($registro)) {
                $result = $registro;
                $message = 'Registro desativado com sucesso!';
                $statusCode = 200;
            } else {
                $errors = $registro->getErrors();
            }
        } catch (RecordNotFoundException $e) {
            $message = 'Registro não encontrado!';
            $statusCode = 404;
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'data' => null,
                'message' => $e->getMessage(),
                'errors' => ['referencia' => [$e->getMessage()]],
            ], 422);
        } catch (Exception $e) {
            $message = 'Erro: ' . $e->getMessage();
            $statusCode = 500;
        }

        return $this->json(['data' => $result, 'message' => $message, 'errors' => $errors], $statusCode);
    }

    public function active(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $tabela = $this->fetchTable('Agendamentos');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Não foi possível salvar o agendamento.';

        if (!is_numeric($id)) {
            return $this->json(['message' => 'O ID do agendamento precisa ser um número.'], 422);
        }

        if ($id < 1) {
            return $this->json(['message' => 'ID do agendamento inválido.'], 422);
        }

        $agendamentoId = (int)$id;
        if ($agendamentoId != $id) {
            return $this->json(['message' => 'O ID do agendamento precisa ser inteiro.'], 422);
        }
        try {
            $registro = $tabela->get($agendamentoId);
            $registro['deleted'] = null;

            if ($tabela->save($registro)) {
                $result = $registro;
                $message = 'Registro ativado com sucesso!';
                $statusCode = 200;
            } else {
                $errors = $registro->getErrors();
            }
        } catch (RecordNotFoundException $e) {
            $message = 'Registro não encontrado!';
            $statusCode = 404;
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'data' => null,
                'message' => $e->getMessage(),
                'errors' => ['referencia' => [$e->getMessage()]],
            ], 422);
        } catch (Exception $e) {
            $message = 'Erro: ' . $e->getMessage();
            $statusCode = 500;
        }

        return $this->json(['data' => $result, 'message' => $message, 'errors' => $errors], $statusCode);
    }

    public function cancel(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'patch']);
        $tabela = $this->fetchTable('Agendamentos');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Não foi possível salvar o agendamento.';
        if (!is_numeric($id)) {
            return $this->json(['message' => 'O ID do agendamento precisa ser um número.'], 422);
        }

        if ($id < 1) {
            return $this->json(['message' => 'ID do agendamento inválido.'], 422);
        }

        $agendamentoId = (int)$id;
        if ($agendamentoId != $id) {
            return $this->json(['message' => 'O ID do agendamento precisa ser inteiro.'], 422);
        }
        try {
            $registro = $tabela->get($agendamentoId);
            if ($registro->deleted !== null) {
                return $this->json(['message' => 'O agendamento está desativado.'], 422);
            }
            if ($registro->cancelado_em === null) {
                $registro['cancelado_em'] = DateTime::now();
            }

            if ($tabela->save($registro)) {
                $result = $registro;
                $message = 'Agendamento cancelado com sucesso!';
                $statusCode = 200;
            } else {
                $errors = $registro->getErrors();
            }
        } catch (RecordNotFoundException $e) {
            $message = 'Registro não encontrado!';
            $statusCode = 404;
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'data' => null,
                'message' => $e->getMessage(),
                'errors' => ['referencia' => [$e->getMessage()]],
            ], 422);
        } catch (Exception $e) {
            $message = 'Erro: ' . $e->getMessage();
            $statusCode = 500;
        }

        return $this->json(['data' => $result, 'message' => $message, 'errors' => $errors], $statusCode);
    }

    public function findByData(?string $data = null): Response
    {
        $this->request->allowMethod(['get']);
        $tabela = $this->fetchTable('Agendamentos');
        $conexao = $tabela->getConnection();
        $statusCode = 200;
        try {
            if (!is_string($data) || !Validation::date($data, ['ymd'])) {
                return $this->json(['message' => 'Informe uma data válida no formato AAAA-MM-DD.'], 422);
            }
            $timestamp = strtotime(str_replace(['/', '.'], '-', $data));
            if ($timestamp === false) {
                return $this->json(['message' => 'Data inválida.'], 422);
            }
            $data = date('Y-m-d', $timestamp);
            $sql = 'SELECT
                        a.id,
                        a.data,
                        a.hora,
                        serv.nome AS servidor,
                        resp.nome AS responsavel,
                        GROUP_CONCAT(
                            al.nome ORDER BY al.nome, al.id SEPARATOR ", "
                        ) AS aluno,
                        GROUP_CONCAT(
                            COALESCE(c.nome, al.curso, "")
                            ORDER BY al.nome, al.id SEPARATOR ", "
                        ) AS curso
                    FROM agendamentos a
                    LEFT JOIN users serv ON serv.id = a.servidor_id
                    LEFT JOIN users resp ON resp.id = a.responsavel_id
                    LEFT JOIN responsaveis r
                        ON r.users_id = a.responsavel_id
                        AND r.deleted IS NULL
                    LEFT JOIN alunos al
                        ON al.id = r.alunos_id
                        AND al.deleted IS NULL
                    LEFT JOIN cursos c ON c.id = al.curso_id
                    WHERE a.data = :data
                    AND a.deleted IS NULL
                    GROUP BY a.id, a.data, a.hora, serv.nome, resp.nome
                    ORDER BY a.data, a.hora, a.id';
            $params = ['data' => $data];
            $result = $conexao->execute($sql, $params)->fetchAll('assoc');
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'data' => null,
                'message' => $e->getMessage(),
                'errors' => ['referencia' => [$e->getMessage()]],
            ], 422);
        } catch (Exception $e) {
            return $this->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }
        return $this->json(['data' => $result], $statusCode);
    }

    public function findByServidor(?string $id = null, ?string $data = null): Response
    {
        $this->request->allowMethod(['get']);
        $tabela = $this->fetchTable('Agendamentos');
        $conexao = $tabela->getConnection();
        $statusCode = 200;
        try {
            $sql = 'SELECT
                        a.id,
                        a.data,
                        a.hora,
                        serv.nome AS servidor,
                        resp.nome AS responsavel,
                        GROUP_CONCAT(
                            al.nome ORDER BY al.nome, al.id SEPARATOR ", "
                        ) AS aluno,
                        GROUP_CONCAT(
                            COALESCE(c.nome, al.curso, "")
                            ORDER BY al.nome, al.id SEPARATOR ", "
                        ) AS curso
                    FROM agendamentos a
                    LEFT JOIN users serv ON serv.id = a.servidor_id
                    LEFT JOIN users resp ON resp.id = a.responsavel_id
                    LEFT JOIN responsaveis r
                        ON r.users_id = a.responsavel_id
                        AND r.deleted IS NULL
                    LEFT JOIN alunos al
                        ON al.id = r.alunos_id
                        AND al.deleted IS NULL
                    LEFT JOIN cursos c ON c.id = al.curso_id
                    WHERE a.deleted IS NULL';
            $params = ['id' => $id];
            if ($this->request->getParam('porNome')) {
                $sql .= ' AND serv.nome = :id';
                $params['id'] = trim((string)$id);
            } else {
                $sql .= ' AND a.servidor_id = :id';
            }
            if ($data !== null) {
                if (!Validation::date($data, ['ymd'])) {
                    return $this->json(['message' => 'Informe uma data válida no formato AAAA-MM-DD.'], 422);
                }
                $sql .= ' AND a.data = :data';
                $timestamp = strtotime(str_replace(['/', '.'], '-', $data));
                if ($timestamp === false) {
                    return $this->json(['message' => 'Data inválida.'], 422);
                }
                $params['data'] = date('Y-m-d', $timestamp);
            }
            $sql .= ' GROUP BY a.id, a.data, a.hora, serv.nome, resp.nome ORDER BY a.data, a.hora, a.id';
            $result = $conexao->execute($sql, $params)->fetchAll('assoc');
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'data' => null,
                'message' => $e->getMessage(),
                'errors' => ['referencia' => [$e->getMessage()]],
            ], 422);
        } catch (Exception $e) {
            return $this->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }
        return $this->json(['data' => $result], $statusCode);
    }

    public function findByResponsavel(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        $tabela = $this->fetchTable('Agendamentos');
        $conexao = $tabela->getConnection();
        $statusCode = 200;
        try {

            $sql = 'SELECT
                        a.id,
                        a.data,
                        a.hora,
                        serv.nome AS servidor,
                        resp.nome AS responsavel,
                        GROUP_CONCAT(
                            al.nome ORDER BY al.nome, al.id SEPARATOR ", "
                        ) AS aluno,
                        GROUP_CONCAT(
                            COALESCE(c.nome, al.curso, "")
                            ORDER BY al.nome, al.id SEPARATOR ", "
                        ) AS curso
                    FROM agendamentos a
                    LEFT JOIN users serv ON serv.id = a.servidor_id
                    LEFT JOIN users resp ON resp.id = a.responsavel_id
                    LEFT JOIN responsaveis r
                        ON r.users_id = a.responsavel_id
                        AND r.deleted IS NULL
                    LEFT JOIN alunos al
                        ON al.id = r.alunos_id
                        AND al.deleted IS NULL
                    LEFT JOIN cursos c ON c.id = al.curso_id
                    WHERE a.deleted IS NULL';
            $params = ['id' => $id];
            if ($this->request->getParam('porNome')) {
                $sql .= ' AND resp.nome = :id';
                $params['id'] = trim((string)$id);
            } else {
                $sql .= ' AND a.responsavel_id = :id';
            }
            $sql .= ' GROUP BY a.id, a.data, a.hora, serv.nome, resp.nome ORDER BY a.data, a.hora, a.id';

            $result = $conexao->execute($sql, $params)->fetchAll('assoc');
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'data' => null,
                'message' => $e->getMessage(),
                'errors' => ['referencia' => [$e->getMessage()]],
            ], 422);

        } catch (Exception $e) {
            return $this->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }
        return $this->json(['data' => $result], $statusCode);
    }

    public function findByAluno(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        $tabela = $this->fetchTable('Agendamentos');
        $conexao = $tabela->getConnection();
        $statusCode = 200;
        try {

            $sql = 'SELECT
                        a.id,
                        a.data,
                        a.hora,
                        serv.nome AS servidor,
                        resp.nome AS responsavel,
                        GROUP_CONCAT(
                            al.nome ORDER BY al.nome, al.id SEPARATOR ", "
                        ) AS aluno,
                        GROUP_CONCAT(
                            COALESCE(c.nome, al.curso, "")
                            ORDER BY al.nome, al.id SEPARATOR ", "
                        ) AS curso
                    FROM agendamentos a
                    LEFT JOIN users serv ON serv.id = a.servidor_id
                    LEFT JOIN users resp ON resp.id = a.responsavel_id
                    LEFT JOIN responsaveis r
                        ON r.users_id = a.responsavel_id
                        AND r.deleted IS NULL
                    LEFT JOIN alunos al
                        ON al.id = r.alunos_id
                        AND al.deleted IS NULL
                    LEFT JOIN cursos c ON c.id = al.curso_id
                    WHERE a.deleted IS NULL AND a.responsavel_id IN (
                        SELECT vinculo.users_id FROM responsaveis vinculo
                        INNER JOIN alunos estudante ON estudante.id = vinculo.alunos_id
                        WHERE vinculo.deleted IS NULL AND estudante.deleted IS NULL';
            $params = ['id' => $id];
            if ($this->request->getParam('porNome')) {
                $sql .= ' AND estudante.nome = :id';
                $params['id'] = trim((string)$id);
            } else {
                $sql .= ' AND estudante.id = :id';
            }
            $sql .= ') GROUP BY a.id, a.data, a.hora, serv.nome, resp.nome ORDER BY a.data, a.hora, a.id';
            $result = $conexao->execute($sql, $params)->fetchAll('assoc');
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'data' => null,
                'message' => $e->getMessage(),
                'errors' => ['referencia' => [$e->getMessage()]],
            ], 422);

        } catch (Exception $e) {
            return $this->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }

        return $this->json(['data' => $result], $statusCode);
    }
}
