<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
use Exception;
use InvalidArgumentException;

class ResponsaveisController extends AppController
{
    /**
     * Cadastra o registro com os campos permitidos.
     */
    public function add(): Response
    {
        $this->request->allowMethod(['post']);
        $tabela = $this->fetchTable('Responsaveis');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Erro ao cadastrar o registro.';

        try {
            // Aceita somente os campos deste cadastro.
            $form = array_intersect_key($this->request->getData(), array_flip([
                'users_id', 'alunos_id', 'responsavel_nome', 'responsavel', 'aluno_nome', 'aluno',
            ]));
            $form = $this->resolverReferencia($form, 'Users', 'users_id', 'responsavel_nome', ['responsavel']);
            $form = $this->resolverReferencia($form, 'Alunos', 'alunos_id', 'aluno_nome', ['aluno']);
            $novoRegistro = $tabela->newEmptyEntity();
            $novoRegistro = $tabela->patchEntity($novoRegistro, $form);

            if ($tabela->save($novoRegistro)) {
                $result = $novoRegistro;
                $message = 'Registro cadastrado com sucesso!';
                $statusCode = 201;
            } else {
                $errors = $novoRegistro->getErrors();
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
     * Desativa o registro sem remover seus vínculos.
     */
    public function delete(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $tabela = $this->fetchTable('Responsaveis');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Não foi possível alterar a ativação do registro.';

        try {
            $registro = $this->localizarRegistro('Responsaveis', $id);
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
     * Reativa o registro, respeitando as regras do cadastro.
     */
    public function active(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $tabela = $this->fetchTable('Responsaveis');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Não foi possível alterar a ativação do registro.';

        try {
            $registro = $this->localizarRegistro('Responsaveis', $id);
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
     * Altera o registro identificado na rota.
     */
    public function edit(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $tabela = $this->fetchTable('Responsaveis');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Erro ao alterar o registro.';

        try {
            $registro = $this->localizarRegistro('Responsaveis', $id);
            $form = array_intersect_key($this->request->getData(), array_flip([
                'users_id', 'alunos_id', 'responsavel_nome', 'responsavel', 'aluno_nome', 'aluno',
            ]));

            $form = $this->resolverReferencia($form, 'Users', 'users_id', 'responsavel_nome', ['responsavel']);
            $form = $this->resolverReferencia($form, 'Alunos', 'alunos_id', 'aluno_nome', ['aluno']);
            $registro = $tabela->patchEntity($registro, $form);

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
     * Lista os registros ativos e aplica os filtros recebidos.
     */
    public function list(): Response
    {
        $this->request->allowMethod(['get', 'post']);
        $tabela = $this->fetchTable('Responsaveis');
        $statusCode = 200;

        try {
            $query = $tabela->find()->contain(['Users', 'Alunos'])->where(['Responsaveis.deleted IS' => null]);
            $form = $this->request->getData() + $this->request->getQueryParams();
            $campos = [
                'users_id' => 'Responsaveis.users_id', 'alunos_id' => 'Responsaveis.alunos_id',
                'responsavel_nome' => 'Users.nome', 'aluno_nome' => 'Alunos.nome',
            ];
            foreach ($campos as $field => $coluna) {
                if (isset($form[$field]) && $form[$field] !== '') {
                    if (!is_scalar($form[$field])) {
                        return $this->json(['message' => 'Filtro inválido: ' . $field], 422);
                    }
                    $query->where([$coluna => $form[$field]]);
                }
            }
            $result = $query->orderBy(['Responsaveis.id' => 'ASC'])->all();
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
     * Busca o registro e seus vínculos pelo ID da rota.
     */
    public function find(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        $statusCode = 200;

        try {
            $registro = $this->localizarRegistro('Responsaveis', $id);
            $result = $this->fetchTable('Responsaveis')->get($registro->id, contain: ['Users', 'Alunos'])->toArray();
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
}
