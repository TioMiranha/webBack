<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\Http\Response;

class UsersController extends CrudController
{
    protected string $tableAlias = 'Users';

    protected array $fields = ['cpf', 'password', 'nome', 'email', 'telefone', 'tipo_id'];

    protected array $relations = ['Tipos', 'Responsaveis.Alunos'];

    protected array $filters = ['nome', 'email', 'tipo_id'];

    /**
     * Valida as credenciais de um usuário ativo.
     */
    public function logar(): Response
    {
        $this->request->allowMethod(['post']);
        $email = $this->request->getData('email');
        $password = $this->request->getData('password');
        if (!is_string($email) || !is_string($password)) {
            return $this->json(['message' => 'Informe email e password.'], 422);
        }
        $user = $this->fetchTable('Users')->find()
            ->where(['email' => $email, 'deleted IS' => null])->first();
        if (!$user || !password_verify($password, $user->password)) {
            return $this->json(['message' => 'Email ou senha inválidos.'], 401);
        }

        $this->request->getSession()->write('Auth.User.id', $user->id);

        return $this->json(['data' => $user, 'message' => 'Login realizado com sucesso.']);
    }
}
