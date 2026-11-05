<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class UsersControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Tipos', 'app.Users', 'app.Alunos', 'app.Responsaveis'];

    public function testScreens(): void
    {
        foreach (['/users', '/users/add', '/users/edit/1', '/users/view/1'] as $url) {
            $this->get($url);
            $this->assertResponseOk();
        }
    }

    public function testEditBlankPasswordAndSoftDelete(): void
    {
        $this->enableCsrfToken();
        $users = $this->getTableLocator()->get('Users');
        $hash = $users->get(1)->password;
        $this->post('/users/edit/1', ['nome' => 'Nome editado', 'password' => '']);
        $this->assertRedirect();
        $this->assertSame($hash, $users->get(1)->password);
        $this->post('/users/delete/1');
        $this->assertRedirect();
        $this->assertNotNull($users->get(1)->deleted);
        $this->assertSame(1, $this->getTableLocator()->get('Responsaveis')->find()->count());
    }
}
