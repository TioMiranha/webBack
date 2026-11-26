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

class AgendamentosController extends AppController
{

    public function add(): Response
    {
        $this->request->allowMethod(['post']);
        $tabela = $this->fetchTable('Agendamentos');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Não foi possível salvar o agendamento.';
        $conexao = $tabela->getConnection();
        try {
            $form = array_intersect_key($this->request->getData(), array_flip([
                'responsavel_id',
                'servidor_id',
                'horario_id',
                'data',
                'hora',
                'assunto',
                'responsavel_nome',
                'responsavel',
                'servidor_nome',
                'servidor',
                'setor_id',
                'setor_nome',
                'setor',
            ]));

            if (isset($form['responsavel_id'])) {
                if (!is_numeric($form['responsavel_id'])) {
                    return $this->json(['message' => 'O ID do responsável precisa ser um número.'], 422);
                }

                if ($form['responsavel_id'] < 1) {
                    return $this->json(['message' => 'ID do responsável inválido.'], 422);
                }

                $responsavelId = (int)$form['responsavel_id'];
                if ($responsavelId != $form['responsavel_id']) {
                    return $this->json(['message' => 'O ID do responsável precisa ser inteiro.'], 422);
                }
            }

            if (isset($form['servidor_id'])) {
                if (!is_numeric($form['servidor_id'])) {
                    return $this->json(['message' => 'O ID do servidor precisa ser um número.'], 422);
                }

                if ($form['servidor_id'] < 1) {
                    return $this->json(['message' => 'ID do servidor inválido.'], 422);
                }

                $servidorId = (int)$form['servidor_id'];
                if ($servidorId != $form['servidor_id']) {
                    return $this->json(['message' => 'O ID do servidor precisa ser inteiro.'], 422);
                }
            }

            if (isset($form['horario_id'])) {
                if (!is_numeric($form['horario_id'])) {
                    return $this->json(['message' => 'O ID do horário precisa ser um número.'], 422);
                }

                if ($form['horario_id'] < 1) {
                    return $this->json(['message' => 'ID do horário inválido.'], 422);
                }

                $horarioId = (int)$form['horario_id'];
                if ($horarioId != $form['horario_id']) {
                    return $this->json(['message' => 'O ID do horário precisa ser inteiro.'], 422);
                }
            }

            if (empty($form['data'])) {
                return $this->json(['message' => 'Informe a data.'], 422);
            }

            if (!is_string($form['data'])) {
                return $this->json(['message' => 'A data precisa ser um texto.'], 422);
            }

            if (!Validation::date($form['data'], ['ymd'])) {
                return $this->json(['message' => 'Data inválida.'], 422);
            }
            $dataInformada = str_replace(['/', '.'], '-', $form['data']);
            $dataTimestamp = strtotime($dataInformada);
            if ($dataTimestamp === false) {
                return $this->json(['message' => 'Data inválida. Use o formato AAAA-MM-DD.'], 422);
            }
            $form['data'] = date('Y-m-d', $dataTimestamp);

            if (empty($form['hora'])) {
                return $this->json(['message' => 'Informe a hora.'], 422);
            }

            if (!is_string($form['hora'])) {
                return $this->json(['message' => 'A hora precisa ser um texto.'], 422);
            }

            if (!Validation::time($form['hora'])) {
                return $this->json(['message' => 'Hora inválida.'], 422);
            }

            if (isset($form['setor_id'])) {
                if (!is_numeric($form['setor_id'])) {
                    return $this->json(['message' => 'O ID do setor precisa ser um número.'], 422);
                }

                if ($form['setor_id'] < 1) {
                    return $this->json(['message' => 'ID do setor inválido.'], 422);
                }

                $setorId = (int)$form['setor_id'];
                if ($setorId != $form['setor_id']) {
                    return $this->json(['message' => 'O ID do setor precisa ser inteiro.'], 422);
                }
            }

            if (isset($form['responsavel_nome'])) {
                if (!is_string($form['responsavel_nome'])) {
                    return $this->json(['message' => 'O nome do responsável precisa ser um texto.'], 422);
                }

                $form['responsavel_nome'] = trim($form['responsavel_nome']);
                if ($form['responsavel_nome'] == '') {
                    return $this->json(['message' => 'Informe o nome do responsável.'], 422);
                }
            }

            if (isset($form['responsavel'])) {
                if (!is_string($form['responsavel'])) {
                    return $this->json(['message' => 'O nome do responsável precisa ser um texto.'], 422);
                }

                $form['responsavel'] = trim($form['responsavel']);
                if ($form['responsavel'] == '') {
                    return $this->json(['message' => 'Informe o nome do responsável.'], 422);
                }
            }

            if (isset($form['servidor_nome'])) {
                if (!is_string($form['servidor_nome'])) {
                    return $this->json(['message' => 'O nome do servidor precisa ser um texto.'], 422);
                }

                $form['servidor_nome'] = trim($form['servidor_nome']);
                if ($form['servidor_nome'] == '') {
                    return $this->json(['message' => 'Informe o nome do servidor.'], 422);
                }
            }

            if (isset($form['servidor'])) {
                if (!is_string($form['servidor'])) {
                    return $this->json(['message' => 'O nome do servidor precisa ser um texto.'], 422);
                }

                $form['servidor'] = trim($form['servidor']);
                if ($form['servidor'] == '') {
                    return $this->json(['message' => 'Informe o nome do servidor.'], 422);
                }
            }

            if (isset($form['setor_nome'])) {
                if (!is_string($form['setor_nome'])) {
                    return $this->json(['message' => 'O nome do setor precisa ser um texto.'], 422);
                }

                $form['setor_nome'] = trim($form['setor_nome']);
                if ($form['setor_nome'] == '') {
                    return $this->json(['message' => 'Informe o nome do setor.'], 422);
                }
            }

            if (isset($form['setor'])) {
                if (!is_string($form['setor'])) {
                    return $this->json(['message' => 'O nome do setor precisa ser um texto.'], 422);
                }

                $form['setor'] = trim($form['setor']);
                if ($form['setor'] == '') {
                    return $this->json(['message' => 'Informe o nome do setor.'], 422);
                }
            }

            if (isset($form['responsavel_nome']) && isset($form['responsavel'])) {
                if ($form['responsavel_nome'] != $form['responsavel']) {
                    return $this->json(['message' => 'Os nomes do responsável informados são diferentes.'], 422);
                }
            }

            if (isset($form['servidor_nome']) && isset($form['servidor'])) {
                if ($form['servidor_nome'] != $form['servidor']) {
                    return $this->json(['message' => 'Os nomes do servidor informados são diferentes.'], 422);
                }
            }

            if (isset($form['setor_nome']) && isset($form['setor'])) {
                if ($form['setor_nome'] != $form['setor']) {
                    return $this->json(['message' => 'Os nomes do setor informados são diferentes.'], 422);
                }
            }

            if (!isset($form['responsavel_id']) && !isset($form['responsavel_nome']) && !isset($form['responsavel'])) {
                return $this->json(['message' => 'Informe o ID ou o nome do responsável.'], 422);
            }

            if (!isset($form['servidor_id']) && !isset($form['servidor_nome']) && !isset($form['servidor'])) {
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
            $nomeResponsavel = $form['responsavel_nome'] ?? $form['responsavel'] ?? null;
            if ($nomeResponsavel !== null) {
                $sql .= ' AND nome = :nome';
                $params['nome'] = $nomeResponsavel;
            }
            $sql .= ' LIMIT 2';
            $responsaveis = $conexao->execute($sql, $params)->fetchAll('assoc');

            if (empty($responsaveis)) {
                return $this->json(['message' => 'Responsável não encontrado, desativado ou ID e nome incompatíveis.'], 422);
            }
            if (count($responsaveis) > 1) {
                return $this->json(['message' => 'Nome de responsável repetido. Informe responsavel_id.'], 422);
            }
            $form['responsavel_id'] = (int)$responsaveis[0]['id'];

            $sql = 'SELECT id, setor_id FROM users
                    WHERE deleted IS NULL AND agendar = 1';
            $params = [];

            if (isset($form['servidor_id'])) {
                $sql .= ' AND id = :id';
                $params['id'] = (int)$form['servidor_id'];
            }
            $nomeServidor = $form['servidor_nome'] ?? $form['servidor'] ?? null;
            if ($nomeServidor !== null) {
                $sql .= ' AND nome = :nome';
                $params['nome'] = $nomeServidor;
            }
            $sql .= ' LIMIT 2';
            $servidores = $conexao->execute($sql, $params)->fetchAll('assoc');

            if (empty($servidores)) {
                return $this->json(['message' => 'Servidor não encontrado, desativado, sem permissão ou ID e nome incompatíveis.'], 422);
            }
            if (count($servidores) > 1) {
                return $this->json(['message' => 'Nome de servidor repetido. Informe servidor_id.'], 422);
            }
            $servidor = $servidores[0];
            $form['servidor_id'] = (int)$servidor['id'];

            if ($servidor['setor_id'] === null) {
                return $this->json(['message' => 'O servidor precisa estar cadastrado em um setor.'], 422);
            }

            $sql = 'SELECT id FROM setors WHERE id = :setor_servidor AND deleted IS NULL';
            $params = ['setor_servidor' => (int)$servidor['setor_id']];

            if (isset($form['setor_id'])) {
                $sql .= ' AND id = :setor_informado';
                $params['setor_informado'] = (int)$form['setor_id'];
            }
            $nomeSetor = $form['setor_nome'] ?? $form['setor'] ?? null;
            if ($nomeSetor !== null) {
                $sql .= ' AND nome = :nome';
                $params['nome'] = $nomeSetor;
            }
            $setor = $conexao->execute($sql, $params)->fetch('assoc');

            if (!$setor) {
                return $this->json(['message' => 'Setor desativado ou diferente do setor do servidor.'], 422);
            }
            $setorId = (int)$setor['id'];
            $data = $form['data'];
            $hora = (new Time($form['hora']))->format('H:i:s');
            $diaSemana = (int)date('N', strtotime($data));

            if (isset($form['horario_id'])) {
                $sql = 'SELECT id, setor_id, dia_semana, hora_inicio, hora_fim
                        FROM horarios WHERE id = :id AND deleted IS NULL';
                $horario = $conexao->execute($sql, [
                    'id' => (int)$form['horario_id'],
                ])->fetch('assoc');

                if (!$horario) {
                    return $this->json(['message' => 'Horário não encontrado ou desativado.'], 422);
                }
            } else {
                $sql = 'SELECT id, setor_id, dia_semana, hora_inicio, hora_fim
                        FROM horarios
                        WHERE setor_id = :setor_id
                        AND dia_semana = :dia_semana
                        AND hora_inicio <= :hora_inicio
                        AND hora_fim > :hora_fim
                        AND deleted IS NULL
                        LIMIT 2';
                $params = [
                    'setor_id' => $setorId,
                    'dia_semana' => $diaSemana,
                    'hora_inicio' => $hora,
                    'hora_fim' => $hora,
                ];
                $horarios = $conexao->execute($sql, $params)->fetchAll('assoc');

                if (empty($horarios)) {
                    return $this->json(['message' => 'Nenhum horário disponível para essa data e hora.'], 422);
                }
                if (count($horarios) > 1) {
                    return $this->json(['message' => 'Mais de um horário encontrado. Informe horario_id.'], 422);
                }
                $horario = $horarios[0];
            }

            if ((int)$horario['setor_id'] != $setorId) {
                return $this->json(['message' => 'O horário não pertence ao setor do servidor.'], 422);
            }
            if ((int)$horario['dia_semana'] != $diaSemana) {
                return $this->json(['message' => 'O dia da semana não corresponde ao horário escolhido.'], 422);
            }
            if ($hora < $horario['hora_inicio'] || $hora >= $horario['hora_fim']) {
                return $this->json(['message' => 'A hora está fora do intervalo de atendimento.'], 422);
            }

            $form['horario_id'] = (int)$horario['id'];
            $form['hora'] = $hora;

            $sql = 'SELECT id, setor_id, dia_semana, hora_inicio, hora_fim
                        FROM horarios
                        WHERE setor_id = :setor_id
                        AND dia_semana = :dia_semana
                        AND hora_inicio = :hora_inicio
                        AND hora_fim = :hora_fim';

            $params = [
                'servidor_id' => $form['servidor_id'],
                'data' => $data,
                'hora' => $hora
            ];

            $conflito = $conexao->execute($sql, $params)->fetch('assoc');
            if ($conflito) {
                $message = 'O servidor ja possui uma reunião marcada nesse horário';
                return $this->json(['data' => $result, 'message' => $message, 'errors' => $errors], 422);
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
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Não foi possível salvar o agendamento.';
        try{
            $form = array_intersect_key($this->request->getData(), array_flip([
                'responsavel_id', 'servidor_id', 'horario_id', 'data', 'hora', 'assunto',
                'responsavel_nome', 'responsavel', 'servidor_nome', 'servidor', 'setor_id', 'setor_nome', 'setor',
            ]));
            
        }catch(RecordNotFoundException $e)
        {
            
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

        return $this->json(['data' => $result, 'message' => $message, 'errors' => $errors], $statusCode);
    }

    public function findByData(?string $data = null): Response
    {
        $this->request->allowMethod(['get']);
        $statusCode = 200;

        return $this->json(['data' => $result], $statusCode);
    }

    public function findByServidor(?string $id = null, ?string $data = null): Response
    {
        $this->request->allowMethod(['get']);
        $statusCode = 200;

        return $this->json(['data' => $result], $statusCode);
    }

    public function findByResponsavel(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        $statusCode = 200;

        return $this->json(['data' => $result], $statusCode);
    }

    public function findByAluno(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        $statusCode = 200;

        return $this->json(['data' => $result], $statusCode);
    }
}
