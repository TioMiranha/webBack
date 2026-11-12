<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
use Exception;

/**
 * Disponibilidades Controller
 *
 * @property \App\Model\Table\DisponibilidadesTable $Disponibilidades
 */
class DisponibilidadesController extends AppController
{
    /**
     * Cadastra a disponibilidade para um vínculo do usuário autenticado.
     */
    public function add(): Response
    {
        $this->request->allowMethod(['post']);
        $tabela = $this->fetchTable('Disponibilidades');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Erro ao cadastrar o registro.';

        try {
            $usuarioId = $this->request->getSession()->read('Auth.User.id');
            if (!$usuarioId) {
                return $this->json([
                    'message' => 'Faça login para cadastrar sua disponibilidade.',
                ], 401);
            }

            // Aceita somente os campos deste cadastro.
            $form = array_intersect_key($this->request->getData(), array_flip([
                'dataInicio',
                'dataFim',
            ]));
            $sql = 'SELECT u.id, u.nome AS coordenador_data, u.tipo_id
            FROM users u
            WHERE u.id = :users_id
            AND u.deleted IS NULL';

            $conexao = $this->fetchTable('Users')->getConnection();

            $results = $conexao->execute($sql, [
                'users_id' => (int)$usuarioId,
            ])->fetchAll('assoc');

            if (empty($results) || (int)$results[0]['tipo_id'] !== 1) {
                return $this->json([
                    'message' => 'Usuário inexistente, desativado ou sem perfil de coordenador.',
                ], 422);
            }

            $conditions = ['users_id' => (int)$usuarioId, 'deleted IS' => null];
            // O curso só precisa ser escolhido quando há mais de um vínculo.
            $cursoId = $this->request->getData('curso_id');
            if ($cursoId !== null) {
                if (
                    (!is_int($cursoId) && !is_string($cursoId))
                    || filter_var($cursoId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false
                ) {
                    return $this->json(['message' => 'Informe um curso_id válido.'], 422);
                }
                $conditions['curso_id'] = (int)$cursoId;
            }
            $coordenadores = $this->fetchTable('Coordenadores')->find()
                ->where($conditions)->limit(2)->all()->toList();
            if (!$coordenadores) {
                return $this->json(['message' => 'Usuário sem vínculo ativo de coordenador para este curso.'], 422);
            }
            if (count($coordenadores) > 1) {
                return $this->json(['message' => 'Informe curso_id para escolher um dos seus cursos.'], 422);
            }
            $coordenador = $coordenadores[0];

            if (
                !$this->fetchTable('Cursos')->exists([
                    'id' => $coordenador->curso_id,
                    'deleted IS' => null,
                ])
            ) {
                return $this->json(['message' => 'Curso inexistente ou desativado.'], 422);
            }

            // O vínculo vem da sessão e da consulta, nunca de coordenador_id enviado pelo cliente.
            $form['coordenador_id'] = (int)$coordenador->id;
            $novoRegistro = $tabela->newEmptyEntity();
            $novoRegistro = $tabela->patchEntity($novoRegistro, $form);
            if ($novoRegistro->hasErrors()) {
                return $this->json([
                    'data' => null,
                    'message' => $message,
                    'errors' => $novoRegistro->getErrors(),
                ], 422);
            }

            if ($tabela->save($novoRegistro)) {
                $result = $novoRegistro;
                $message = 'Registro cadastrado com sucesso!';
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
        $statusCode = 200;

        try {
            $sql = 'SELECT co.id, u.nome, d.dataInicio, d.dataFim AS coordenador, c.nome AS curso
                    FROM coordenadores co
                    INNER JOIN users u ON u.id = co.users_id
                    INNER JOIN cursos c ON c.id = co.curso_id
                    INNER JOIN disponibilidades d ON d.coordenador_id = co.id
                    WHERE co.deleted IS NULL';

            $form = $this->request->getData() + $this->request->getQueryParams();
            $params = [];
            $fields = [
                'id' => 'co.id',
                'coordenador' => 'u.nome',
                'curso' => 'c.nome',
                'dataInicio' => 'd.dataInicio',
                'dataFim' => 'd.dataFim'
            ];
            foreach ($fields as $field => $column) {
                if (isset($form[$field]) && $form[$field] !== '') {
                    if (!is_scalar($form[$field])) {
                        return $this->json(['message' => 'Filtro inválido: ' . $field], 422);
                    }
                    $sql .= ' AND ' . $column . ' = :' . $field;
                    $params[$field] = $form[$field];
                }
            }
            $sql .= ' ORDER BY co.id';
            $conexao = $this->fetchTable('Coordenadores')->getConnection();
            $result = $conexao->execute($sql, $params)->fetchAll('assoc');
        } catch (Exception $e) {
            return $this->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }

        return $this->json(['data' => $result], $statusCode);
    }

     public function find(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        $statusCode = 200;
        $result = null;
        try {
           $sql = 'SELECT co.id, u.nome, d.dataInicio, d.dataFim AS coordenador, c.nome AS curso
                    FROM coordenadores co
                    INNER JOIN users u ON u.id = co.users_id
                    INNER JOIN cursos c ON c.id = co.curso_id
                    INNER JOIN disponibilidades d ON d.coordenador_id = co.id
                    WHERE co.id = :id';
            $conexao = $this->fetchTable('Coordenadores')->getConnection();
            $results = $conexao->execute($sql, ['id' => (int)$id])->fetchAll('assoc');

            if (!empty($results)) {
                $result = $results[0];
            } else {
                return $this->json(['message' => 'Registro não encontrado!', 'code' => 404], 404);
            }
        } catch (Exception $e) {
            return $this->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }

        return $this->json(['data' => $result], $statusCode);
    }

    public function edit(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $tabela = $this->fetchTable('Disponibilidades');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Erro ao alterar o registro.';

        try {
            $registro = $tabela->get((int)$id);
            $form = array_intersect_key($this->request->getData(), array_flip([
                'dataInicio',
                'dataFim',
            ]));
            if (!$form) {
                return $this->json(['message' => 'Informe users_id ou curso_id para editar o vínculo.'], 422);
            }

            $registro = $tabela->patchEntity($registro, $form);
            if ($registro->hasErrors()) {
                return $this->json([
                    'data' => null, 'message' => $message, 'errors' => $registro->getErrors(),
                ], 422);
            }

            if ($tabela->save($registro)) {
                $result = $registro;
                $message = 'Registro alterado com sucesso!';
                $statusCode = 200;
            } else {
                $errors = $registro->getErrors();
            }
        } catch (RecordNotFoundException $e) {
            $message = 'Registro não encontrado!';
            $statusCode = 404;
        } catch (Exception $e) {
            $message = 'Erro: ' . $e->getMessage();
            $statusCode = 500;
        }

        return $this->json(['data' => $result, 'message' => $message, 'errors' => $errors], $statusCode);
    }
}
