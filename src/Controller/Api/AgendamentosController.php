<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
use Cake\I18n\Date;
use Exception;

/**
 * Agendamentos Controller
 *
 * @property \App\Model\Table\AgendamentosTable $Agendamentos
 */
class AgendamentosController extends AppController
{
    /**
     * Cadastra o registro com os campos permitidos.
     */
    public function add(): Response
    {
        $this->request->allowMethod(['post']);
        $tabela = $this->fetchTable('Agendamentos');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Erro ao cadastrar o registro.';

        try {
            // Aceita somente os campos deste cadastro.
            $form = array_intersect_key($this->request->getData(), array_flip([
                'responsavel_id',
                'coordenador_id',
                'data',
                'hora',
                'assunto',
                'status',
            ]));
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


    public function findByServidor(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        $statusCode = 200;
        try {
            $sql = 'SELECT 
                           u.nome AS coordenador, c.nome AS curso,
                           ur.nome AS responsavel, al.nome, al.curso AS estudante,
                           a.data AS agenda, a.hora
                    FROM coordenadores co
                    INNER JOIN users u ON u.id = co.users_id
                    INNER JOIN cursos c ON c.id = co.curso_id
                    INNER JOIN agendamentos a ON a.coordenador_id = co.id
                    INNER JOIN responsaveis r ON r.id = a.responsavel_id
                    INNER JOIN users ur ON ur.id = r.users_id
                    INNER JOIN alunos al ON al.id = r.alunos_id
                    WHERE co.id = :id AND a.deleted IS NULL
                    ORDER BY a.data, a.hora, a.id';
            $conexao = $this->fetchTable('Coordenadores')->getConnection();
            $result = $conexao->execute($sql, ['id' => (int)$id])->fetchAll('assoc');

            if (empty($result)) {
                return $this->json(['message' => 'Registro não encontrado!', 'code' => 404], 404);
            }
        } catch (Exception $e) {
            return $this->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }

        return $this->json(['data' => $result], $statusCode);
    }

    public function findByData(?Date $data = null): Response
    {
        $this->request->allowMethod(['get']);
        $statusCode = 200;
        try {
            $sql = 'SELECT 
                           u.nome AS coordenador, c.nome AS curso,
                           ur.nome AS responsavel, al.nome, al.curso AS estudante,
                           a.data AS agenda, a.hora
                    FROM coordenadores co
                    INNER JOIN users u ON u.id = co.users_id
                    INNER JOIN cursos c ON c.id = co.curso_id
                    INNER JOIN agendamentos a ON a.coordenador_id = co.id
                    INNER JOIN responsaveis r ON r.id = a.responsavel_id
                    INNER JOIN users ur ON ur.id = r.users_id
                    INNER JOIN alunos al ON al.id = r.alunos_id
                    WHERE co.id = :id AND a.deleted IS NULL
                    ORDER BY a.data, a.hora, a.id';
            $conexao = $this->fetchTable('Coordenadores')->getConnection();
            $result = $conexao->execute($sql, ['data' => (int)$data])->fetchAll('assoc');

            if (empty($result)) {
                return $this->json(['message' => 'Registro não encontrado!', 'code' => 404], 404);
            }
        } catch (Exception $e) {
            return $this->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }

        return $this->json(['data' => $result], $statusCode);
    }


    public function delete(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $tabela = $this->fetchTable('Agendamentos');
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
}
