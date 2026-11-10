<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
use Exception;

class UsersController extends AppController
{
    /**
     * Cadastra o registro com os campos permitidos.
     */
    public function add(): Response
    {
        $this->request->allowMethod(['post']);
        $tabela = $this->fetchTable('Users');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Erro ao cadastrar o registro.';

        try {
            // Aceita somente os campos deste cadastro.
            $form = array_intersect_key($this->request->getData(), array_flip([
                'cpf', 'password', 'nome', 'email', 'telefone', 'tipo_id',
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

    /**
     * Lista os registros ativos e aplica os filtros recebidos.
     */
    public function list(): Response
    {
        $this->request->allowMethod(['get', 'post']);
        $statusCode = 200;

        try {
            $sql = 'SELECT u.id, u.nome, u.cpf, u.telefone, u.email, u.tipo_id, t.nome as perfil
                    FROM users u
                    INNER JOIN tipos t ON u.tipo_id = t.id
                    WHERE u.deleted IS NULL';
            $form = $this->request->getData() + $this->request->getQueryParams();
            $params = [];
            foreach (['nome', 'email', 'tipo_id'] as $field) {
                if (isset($form[$field]) && $form[$field] !== '') {
                    $sql .= ' AND u.' . $field . ' = :' . $field;
                    $params[$field] = $form[$field];
                }
            }
            $sql .= ' ORDER BY u.id';
            $conexao = $this->fetchTable('Users')->getConnection();
            $result = $conexao->execute($sql, $params)->fetchAll('assoc');
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
        $result = null;
        try {
            $sql = 'SELECT u.id, u.cpf, u.nome, u.email, u.telefone, u.deleted,
                           t.nome as perfil, r.id as vinculo_id, r.deleted as vinculo_deleted,
                           a.id as aluno_id, a.nome as aluno, a.curso
                    FROM users u
                    INNER JOIN tipos t ON u.tipo_id = t.id
                    LEFT JOIN responsaveis r ON u.id = r.users_id
                    LEFT JOIN alunos a ON r.alunos_id = a.id
                    WHERE u.id = :id ORDER BY r.id';
            $conexao = $this->fetchTable('Users')->getConnection();
            $results = $conexao->execute($sql, ['id' => (int)$id])->fetchAll('assoc');

            if (!empty($results)) {
                $result['id'] = (int)$results[0]['id'];
                $result['cpf'] = $results[0]['cpf'];
                $result['nome'] = $results[0]['nome'];
                $result['telefone'] = $results[0]['telefone'];
                $result['email'] = $results[0]['email'];
                $result['perfil'] = $results[0]['perfil'];
                $result['deleted'] = $results[0]['deleted'];
                $result['aluno'] = [];
                $result['responsaveis'] = [];

                foreach ($results as $value) {
                    if ($value['aluno_id'] === null) {
                        continue;
                    }
                    $aluno = ['id' => (int)$value['aluno_id'], 'nome' => $value['aluno'], 'curso' => $value['curso']];
                    $result['aluno'][] = $aluno;
                    $result['responsaveis'][] = [
                        'id' => (int)$value['vinculo_id'], 'users_id' => $result['id'],
                        'alunos_id' => $aluno['id'], 'deleted' => $value['vinculo_deleted'], 'aluno' => $aluno,
                    ];
                }
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
        $tabela = $this->fetchTable('Users');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Erro ao alterar o registro.';

        try {
            $registro = $tabela->get((int)$id);
            $form = array_intersect_key($this->request->getData(), array_flip([
                'cpf', 'password', 'nome', 'email', 'telefone', 'tipo_id',
            ]));
            // Uma senha vazia na edição mantém o hash atual.
            if (isset($form['password']) && $form['password'] === '') {
                unset($form['password']);
            }
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

    /**
     * Desativa o registro sem remover seus vínculos.
     */
    public function delete(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $tabela = $this->fetchTable('Users');
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
     * Reativa o registro, respeitando as regras do cadastro.
     */
    public function active(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $tabela = $this->fetchTable('Users');
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

    /**
     * Valida as credenciais e registra o usuário na sessão.
     */
    public function logar(): Response
    {
        $this->request->allowMethod(['post']);
        $email = $this->request->getData('email');
        $password = $this->request->getData('password');
        if (!is_string($email) || !is_string($password)) {
            return $this->json(['message' => 'Informe email e password.'], 422);
        }

        try {
            $usuario = $this->fetchTable('Users')->find()
                ->where(['email' => $email, 'deleted IS' => null])->first();
            if (!$usuario || !password_verify($password, $usuario->password)) {
                return $this->json(['message' => 'Email ou senha inválidos.'], 401);
            }
            $this->request->getSession()->write('Auth.User.id', $usuario->id);
        } catch (Exception $e) {
            return $this->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }

        return $this->json(['data' => $usuario, 'message' => 'Login realizado com sucesso.']);
    }
}
