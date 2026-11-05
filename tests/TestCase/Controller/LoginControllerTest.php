<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class LoginControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Tipos', 'app.Users'];

    public function testLoginChecksPasswordAndActiveStatus(): void
    {
        $users = $this->getTableLocator()->get('Users');
        $user = $users->get(1);
        $user->password = 'senha123';
        $users->saveOrFail($user);
        $this->post('/api/usuarios/entrar.json', ['email' => $user->email, 'password' => 'errada']);
        $this->assertResponseCode(401);
        $this->post('/api/usuarios/entrar.json', ['email' => $user->email, 'password' => 'senha123']);
        $this->assertResponseOk();
        $this->assertSession(1, 'Auth.User.id');
        $this->assertResponseNotContains('password');
        $user->deleted = DateTime::now();
        $users->saveOrFail($user);
        $this->post('/login/entrar', ['email' => $user->email, 'password' => 'senha123']);
        $this->assertResponseCode(401);
    }
}
