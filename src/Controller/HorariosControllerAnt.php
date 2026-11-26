<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
use Cake\I18n\Time;
use Cake\Validation\Validation;
use Exception;
use InvalidArgumentException;

/**
 * Horarios Controller
 */
class HorariosController extends AppController
{
    public function add(): Response
    {
        $this->request->allowMethod(['post']);

        $tabela = $this->fetchTable('Horarios');
        $conexao = $tabela->getConnection();
        $form = $this->request->getData();

        try {
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

            $nomeSetor = $form['setor_nome'] ?? $form['setor'] ?? null;

            if ($nomeSetor !== null) {
                if (!is_string($nomeSetor) || trim($nomeSetor) === '') {
                    return $this->json(['message' => 'Nome do setor inválido.'], 422);
                }
                if (isset($form['setor_nome']) && isset($form['setor'])) {
                    if (!is_string($form['setor']) || trim($form['setor_nome']) != trim($form['setor'])) {
                        return $this->json(['message' => 'Os nomes de setor informados são diferentes.'], 422);
                    }
                }

                $sql = 'SELECT id FROM setors
                        WHERE nome = :nome AND deleted IS NULL';
                $params = ['nome' => trim($nomeSetor)];

                if (isset($form['setor_id'])) {
                    $sql .= ' AND id = :id';
                    $params['id'] = (int)$form['setor_id'];
                }
            } elseif (isset($form['setor_id'])) {
                $sql = 'SELECT id FROM setors
                        WHERE id = :id AND deleted IS NULL';
                $params = ['id' => (int)$form['setor_id']];
            } else {
                return $this->json(['message' => 'Informe o setor.'], 422);
            }

            $sql .= ' LIMIT 2';
            $setores = $conexao->execute($sql, $params)->fetchAll('assoc');

            if (empty($setores)) {
                return $this->json(['message' => 'Setor não encontrado ou desativado.'], 422);
            }
            if (count($setores) > 1) {
                return $this->json(['message' => 'Nome de setor repetido. Informe setor_id.'], 422);
            }
            $form['setor_id'] = (int)$setores[0]['id'];

            if (!isset($form['dia_semana'])) {
                return $this->json(['message' => 'Informe o dia da semana.'], 422);
            }
            if (!is_numeric($form['dia_semana'])) {
                return $this->json(['message' => 'O dia da semana precisa ser um número.'], 422);
            }

            if ($form['dia_semana'] < 1 || $form['dia_semana'] > 7) {
                return $this->json(['message' => 'Dia da semana inválido.'], 422);
            }

            $dia = (int)$form['dia_semana'];
            if ($dia != $form['dia_semana']) {
                return $this->json(['message' => 'O dia da semana precisa ser inteiro.'], 422);
            }

            if (!isset($form['hora_inicio'])) {
                return $this->json(['message' => 'Informe a hora de início.'], 422);
            }
            if (!isset($form['hora_fim'])) {
                return $this->json(['message' => 'Informe a hora final.'], 422);
            }
            if (!is_string($form['hora_inicio']) || !Validation::time($form['hora_inicio'])) {
                return $this->json(['message' => 'Hora inicial inválida.'], 422);
            }
            if (!is_string($form['hora_fim']) || !Validation::time($form['hora_fim'])) {
                return $this->json(['message' => 'Hora final inválida.'], 422);
            }

            $registro = $tabela->newEmptyEntity();
            $registro->setor_id = $form['setor_id'];
            $registro->dia_semana = $dia;

            $registro->hora_inicio = new Time($form['hora_inicio']);
            $registro->hora_fim = new Time($form['hora_fim']);

            if ($tabela->save($registro)) {
                return $this->json([
                    'message' => 'Salvo com sucesso',
                    'data' => $registro,
                ], 201);
            }

            return $this->json([
                'message' => 'Erro ao salvar',
                'errors' => $registro->getErrors(),
            ], 422);
        } catch (Exception $e) {
            return $this->json(['erro' => $e->getMessage()], 500);
        }
    }

    public function list(?string $id = null): Response
    {
        $this->request->allowMethod(['get', 'post']);

        if ($id != null) {
            return $this->find($id);
        }

        try {
            $tabela = $this->fetchTable('Horarios');
            $conexao = $tabela->getConnection();
            $form = $this->request->getData() + $this->request->getQueryParams();

            $sql = 'SELECT horarios.id,
                           horarios.setor_id,
                           horarios.dia_semana,
                           horarios.hora_inicio,
                           horarios.hora_fim,
                           setors.nome AS setor
                    FROM horarios
                    INNER JOIN setors ON horarios.setor_id = setors.id
                    WHERE horarios.deleted IS NULL
                    AND setors.deleted IS NULL';
            $params = [];

            if (isset($form['setor_id']) && $form['setor_id'] != '') {
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

                $sql .= ' AND horarios.setor_id = :setor_id';
                $params['setor_id'] = $setorId;
            }

            if (isset($form['dia_semana']) && $form['dia_semana'] != '') {
                if (!is_numeric($form['dia_semana'])) {
                    return $this->json(['message' => 'O dia da semana precisa ser um número.'], 422);
                }

                if ($form['dia_semana'] < 1 || $form['dia_semana'] > 7) {
                    return $this->json(['message' => 'Dia da semana inválido.'], 422);
                }

                $dia = (int)$form['dia_semana'];
                if ($dia != $form['dia_semana']) {
                    return $this->json(['message' => 'O dia da semana precisa ser inteiro.'], 422);
                }

                $sql .= ' AND horarios.dia_semana = :dia_semana';
                $params['dia_semana'] = $dia;
            }

            if (isset($form['setor_nome']) && $form['setor_nome'] != '') {
                if (!is_string($form['setor_nome'])) {
                    return $this->json(['message' => 'O nome do setor precisa ser um texto.'], 422);
                }

                $nome = trim($form['setor_nome']);
                if ($nome == '') {
                    return $this->json(['message' => 'Nome do setor inválido.'], 422);
                }

                $sql .= ' AND setors.nome = :setor_nome';
                $params['setor_nome'] = $nome;
            }

            $sql .= ' ORDER BY horarios.dia_semana, horarios.hora_inicio, horarios.id';
            $result = $conexao->execute($sql, $params)->fetchAll('assoc');

            return $this->json(['data' => $result], 200);
        } catch (Exception $e) {
            return $this->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }
    }

    public function find(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        $statusCode = 200;

        if (!is_numeric($id)) {
            return $this->json(['message' => 'O ID do horário precisa ser um número.'], 422);
        }

        if ($id < 1) {
            return $this->json(['message' => 'ID do horário inválido.'], 422);
        }

        $horarioId = (int)$id;
        if ($horarioId != $id) {
            return $this->json(['message' => 'O ID do horário precisa ser inteiro.'], 422);
        }

        try {
            $tabela = $this->fetchTable('Horarios');
            $conexao = $tabela->getConnection();

            $sql = 'SELECT id, setor_id, dia_semana, hora_inicio, hora_fim
                    FROM horarios
                    WHERE id = :id
                    AND deleted IS NULL';

            $params = ['id' => $horarioId];
            $result = $conexao->execute($sql, $params)->fetch('assoc');

            if (!$result) {
                return $this->json(['message' => 'Horário não encontrado.'], 404);
            }
        } catch (Exception $e) {
            return $this->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }

        return $this->json(['data' => $result], $statusCode);
    }

    public function edit(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $tabela = $this->fetchTable('Horarios');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Não foi possível salvar o registro.';

        if (!is_numeric($id)) {
            return $this->json(['message' => 'O ID do horário precisa ser um número.'], 422);
        }

        if ($id < 1) {
            return $this->json(['message' => 'ID do horário inválido.'], 422);
        }

        $horarioId = (int)$id;
        if ($horarioId != $id) {
            return $this->json(['message' => 'O ID do horário precisa ser inteiro.'], 422);
        }
        try {
            $registro = $tabela->get($horarioId);
            $form = array_intersect_key(
                $this->request->getData(),
                array_flip([
                    'setor_id',
                    'dia_semana',
                    'hora_inicio',
                    'hora_fim',
                ])
            );
            if (empty($form)) {
                return $this->json(['message' => 'Informe ao menos um campo do horário para alterar.'], 422);
            }

            $registro = $tabela->patchEntity($registro, $form);
            if ($tabela->save($registro)) {
                $result = $registro;
                $message = 'Registro alterado com sucesso';
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

    public function delete(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $tabela = $this->fetchTable('Horarios');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Não foi possível salvar o registro.';
        if (!is_numeric($id)) {
            return $this->json(['message' => 'O ID do horário precisa ser um número.'], 422);
        }

        if ($id < 1) {
            return $this->json(['message' => 'ID do horário inválido.'], 422);
        }

        $horarioId = (int)$id;
        if ($horarioId != $id) {
            return $this->json(['message' => 'O ID do horário precisa ser inteiro.'], 422);
        }
        try {
            $registro = $tabela->get($horarioId);

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
        $tabela = $this->fetchTable('Horarios');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Não foi possível salvar o registro.';

        if (!is_numeric($id)) {
            return $this->json(['message' => 'O ID do horário precisa ser um número.'], 422);
        }

        if ($id < 1) {
            return $this->json(['message' => 'ID do horário inválido.'], 422);
        }

        $horarioId = (int)$id;
        if ($horarioId != $id) {
            return $this->json(['message' => 'O ID do horário precisa ser inteiro.'], 422);
        }
        try {
            $registro = $tabela->get($horarioId);
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

    /**
     * Busca os horários ativos pelo dia da semana ISO.
     */
    public function findByDia(?string $dia = null): Response
    {
        $this->request->allowMethod(['get']);
        $statusCode = 200;

        if (!is_numeric($dia)) {
            return $this->json(['message' => 'O dia da semana precisa ser um número.'], 422);
        }

        if ($dia < 1 || $dia > 7) {
            return $this->json(['message' => 'Use dia_semana de 1 (segunda) a 7 (domingo).'], 422);
        }

        $diaSemana = (int)$dia;
        if ($diaSemana != $dia) {
            return $this->json(['message' => 'O dia da semana precisa ser inteiro.'], 422);
        }

        try {
            $tabela = $this->fetchTable('Horarios');
            $conexao = $tabela->getConnection();

            $sql = 'SELECT horarios.id,
                           horarios.setor_id,
                           horarios.dia_semana,
                           horarios.hora_inicio,
                           horarios.hora_fim,
                           setors.nome AS setor
                    FROM horarios
                    INNER JOIN setors ON horarios.setor_id = setors.id
                    WHERE horarios.dia_semana = :dia_semana
                    AND horarios.deleted IS NULL
                    AND setors.deleted IS NULL
                    ORDER BY horarios.hora_inicio, horarios.id';

            $params = ['dia_semana' => $diaSemana];
            $result = $conexao->execute($sql, $params)->fetchAll('assoc');
        } catch (Exception $e) {
            return $this->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }

        return $this->json(['data' => $result], $statusCode);
    }

    /**
     * Busca os horários ativos de um setor por ID ou nome.
     */
    public function findBySetor(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        $statusCode = 200;

        if ($this->request->getParam('porNome')) {
            $nome = trim((string)$id);
            if ($nome == '') {
                return $this->json(['message' => 'Informe o nome do setor.'], 422);
            }
            $filtro = 'setors.nome = :nome';
            $params = ['nome' => $nome];
        } else {
            if (!is_numeric($id)) {
                return $this->json(['message' => 'O ID do setor precisa ser um número.'], 422);
            }

            if ($id < 1) {
                return $this->json(['message' => 'ID do setor inválido.'], 422);
            }

            $setorId = (int)$id;
            if ($setorId != $id) {
                return $this->json(['message' => 'O ID do setor precisa ser inteiro.'], 422);
            }
            $filtro = 'horarios.setor_id = :setor_id';
            $params = ['setor_id' => $setorId];
        }

        try {
            $tabela = $this->fetchTable('Horarios');
            $conexao = $tabela->getConnection();

            $sql = 'SELECT horarios.id,
                           horarios.setor_id,
                           horarios.dia_semana,
                           horarios.hora_inicio,
                           horarios.hora_fim,
                           setors.nome AS setor
                    FROM horarios
                    INNER JOIN setors ON horarios.setor_id = setors.id
                    WHERE horarios.deleted IS NULL
                    AND setors.deleted IS NULL
                    AND ' . $filtro . '
                    ORDER BY horarios.dia_semana, horarios.hora_inicio, horarios.id';

            $result = $conexao->execute($sql, $params)->fetchAll('assoc');
        } catch (Exception $e) {
            return $this->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }

        return $this->json(['data' => $result], $statusCode);
    }
}
