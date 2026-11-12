<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Throwable;

/**
 * Cursos Controller
 *
 */
class CursosController extends AppController
{
    public function add(): Response
    {
        $this->request->allowMethod(['post']);
        $tabela = $this->fetchTable('Cursos');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Erro ao cadastrar o registro.';

        try {
            // Aceita somente os campos deste cadastro.
            $form = array_intersect_key($this->request->getData(), array_flip([
                'nome',
            ]));
            $novoRegistro = $tabela->newEmptyEntity();
            $novoRegistro = $tabela->patchEntity($novoRegistro, $form);

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
            $sql = 'SELECT id, nome
                    FROM cursos c
                    WHERE deleted IS NULL';
            $form = $this->request->getData() + $this->request->getQueryParams();
            $params = [];
            foreach (['id', 'nome'] as $field) {
                if (isset($form[$field]) && $form[$field] !== '') {
                    $sql .= ' AND c.' . $field . ' = :' . $field;
                    $params[$field] = $form[$field];
                }
            }
            $sql .= ' ORDER BY id';
            $conexao = $this->fetchTable('Cursos')->getConnection();
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
            $sql = 'SELECT id,nome
                    FROM cursos c
                    WHERE c.id = :id';
            $conexao = $this->fetchTable('Cursos')->getConnection();
            $results = $conexao->execute($sql, ['id' => (int)$id])->fetchAll('assoc');

            if (!empty($results)) {
                $result['id'] = (int)$results[0]['id'];
                $result['nome'] = $results[0]['nome'];

               // $curso = ['id' => (int)$result['id'], 'nome' => $result['nome']];
                
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
        $tabela = $this->fetchTable('Cursos');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Erro ao alterar o registro.';

        try {
            $registro = $tabela->get((int)$id);
            $form = array_intersect_key($this->request->getData(), array_flip([
                'nome',
            ]));
            // Uma senha vazia na edição mantém o hash atual.

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
        } catch (Exception $e) {
            $message = 'Erro: ' . $e->getMessage();
            $statusCode = 500;
        }

        return $this->json(['data' => $result, 'message' => $message, 'errors' => $errors], $statusCode);
    }

    public function delete(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $tabela = $this->fetchTable('Cursos');
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

    public function active(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $tabela = $this->fetchTable('Cursos');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Não foi possível alterar a ativação do registro.';

        try {
            $registro = $tabela->get((int)$id);
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
