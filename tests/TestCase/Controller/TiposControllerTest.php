<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class TiposControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Tipos', 'app.Users'];

    public function testScreens(): void
    {
        foreach (['/tipos', '/tipos/add', '/tipos/edit/1', '/tipos/view/1'] as $url) {
            $this->get($url);
            $this->assertResponseOk();
        }
    }

    public function testCreateWithoutDeletedAndSoftDelete(): void
    {
        $this->enableCsrfToken();
        $this->post('/tipos/add', ['nome' => 'Responsável']);
        $this->assertRedirect();
        $this->assertTrue($this->getTableLocator()->get('Tipos')->exists(['nome' => 'Responsável', 'deleted IS' => null]));
        $this->post('/tipos/delete/1');
        $this->assertRedirect();
        $this->assertNotNull($this->getTableLocator()->get('Tipos')->get(1)->deleted);
    }
}
