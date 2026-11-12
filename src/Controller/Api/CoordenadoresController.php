<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
use Exception;

/**
 * Coordenadores Controller
 */
class CoordenadoresController extends AppController
{
    /**
     * Cadastra o vínculo entre um coordenador ativo e um curso ativo.
     */
    public function add(): Response
    {
        $this->request->allowMethod(['post']);
        $tabela = $this->fetchTable('Coordenadores');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Erro ao cadastrar o registro.';

        try {
            // Aceita somente os campos deste cadastro.
            $form = array_intersect_key($this->request->getData(), array_flip([
                'users_id',
                'curso_id',
            ]));
            $novoRegistro = $tabela->newEmptyEntity();
            $novoRegistro = $tabela->patchEntity($novoRegistro, $form);
            if ($novoRegistro->hasErrors()) {
                return $this->json([
                    'data' => null, 'message' => $message, 'errors' => $novoRegistro->getErrors(),
                ], 422);
            }

            $sql = 'SELECT u.id, u.nome AS coordenador_data, u.tipo_id
            FROM users u
            WHERE u.id = :users_id
            AND u.deleted IS NULL';

            $conexao = $this->fetchTable('Users')->getConnection();

            $results = $conexao->execute($sql, [
                'users_id' => (int)($form['users_id'] ?? 0),
            ])->fetchAll('assoc');

            if (empty($results) || (int)$results[0]['tipo_id'] !== 1) {
                return $this->json([
                    'message' => 'Usuário inexistente, desativado ou sem perfil de coordenador.',
                ], 422);
            }

            if (
                !$this->fetchTable('Cursos')->exists([
                'id' => $form['curso_id'] ?? 0, 'deleted IS' => null,
                ])
            ) {
                return $this->json(['message' => 'Curso inexistente ou desativado.'], 422);
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

    /**
     * Lista os vínculos ativos com filtros por ID, coordenador e curso.
     */
    public function list(): Response
    {
        $this->request->allowMethod(['get', 'post']);
        $statusCode = 200;

        try {
            $sql = 'SELECT co.id, u.nome AS coordenador, c.nome AS curso
                    FROM coordenadores co
                    INNER JOIN users u ON u.id = co.users_id
                    INNER JOIN cursos c ON c.id = co.curso_id
                    WHERE co.deleted IS NULL';

            $form = $this->request->getData() + $this->request->getQueryParams();
            $params = [];
            $fields = [
                'id' => 'co.id',
                'coordenador' => 'u.nome',
                'curso' => 'c.nome',
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

    /**
     * Busca pelo ID do vínculo, incluindo os dados de usuário e curso.
     */
    public function find(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        $statusCode = 200;
        $result = null;
        try {
            $sql = 'SELECT co.id, co.users_id, co.curso_id, co.deleted,
                           u.nome, u.nome AS coordenador, c.nome AS curso
                    FROM coordenadores co
                    INNER JOIN users u ON u.id = co.users_id
                    INNER JOIN cursos c ON c.id = co.curso_id
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

    /**
     * Altera o registro identificado na rota.
     */
    public function edit(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $tabela = $this->fetchTable('Coordenadores');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Erro ao alterar o registro.';

        try {
            $registro = $tabela->get((int)$id);
            $form = array_intersect_key($this->request->getData(), array_flip([
                'users_id',
                'curso_id',
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

            $usuario = $this->fetchTable('Users')->find()->where([
                'id' => $registro->users_id, 'tipo_id' => 1, 'deleted IS' => null,
            ])->first();
            if (!$usuario) {
                return $this->json([
                    'message' => 'Usuário inexistente, desativado ou sem perfil de coordenador.',
                ], 422);
            }
            if (
                !$this->fetchTable('Cursos')->exists([
                'id' => $registro->curso_id, 'deleted IS' => null,
                ])
            ) {
                return $this->json(['message' => 'Curso inexistente ou desativado.'], 422);
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

    /**
     * Desativa o vínculo sem excluir o usuário ou o curso.
     */
    public function delete(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $tabela = $this->fetchTable('Coordenadores');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Não foi possível alterar a ativação do registro.';

        try {
            $registro = $tabela->get((int)$id);
            $registro['deleted'] = date('Y-m-d H:i:s');

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
        } catch (Exception $e) {
            $message = 'Erro: ' . $e->getMessage();
            $statusCode = 500;
        }

        return $this->json(['data' => $result, 'message' => $message, 'errors' => $errors], $statusCode);
    }

    /**
     * Reativa o vínculo se usuário, perfil e curso continuarem válidos.
     */
    public function active(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $tabela = $this->fetchTable('Coordenadores');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Não foi possível alterar a ativação do registro.';

        try {
            $registro = $tabela->get((int)$id);
            $usuario = $this->fetchTable('Users')->find()->where([
                'id' => $registro->users_id, 'tipo_id' => 1, 'deleted IS' => null,
            ])->first();
            if (!$usuario) {
                return $this->json([
                    'message' => 'Usuário inexistente, desativado ou sem perfil de coordenador.',
                ], 422);
            }
            if (
                !$this->fetchTable('Cursos')->exists([
                'id' => $registro->curso_id, 'deleted IS' => null,
                ])
            ) {
                return $this->json(['message' => 'Curso inexistente ou desativado.'], 422);
            }
            $registro['deleted'] = null;

            if ($tabela->save($registro)) {
                $result = $registro;
                $message = 'Registro reativado com sucesso!';
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
