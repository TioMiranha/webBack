<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class AlunosControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Alunos'];

    public function testImportFormAndLink(): void
    {
        $this->get('/alunos');
        $this->assertResponseOk();
        $this->assertResponseContains('/alunos/importar');
        $this->get('/alunos/importar');
        $this->assertResponseOk();
        $this->assertResponseContains('multipart/form-data');
        $this->assertResponseContains('name="excel_file"');
    }

    public function testImportFormSavesAndRedirects(): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['nome', 'matricula', 'curso', 'situacao', 'emailacademico', 'emailpessoal', 'anoperiodoletivo'],
            ['Aluno da tela', '20261105', 'Informática', 'Ativo', '', '', '2026/2'],
        ]);
        $path = tempnam(TMP, 'importacao_web_');
        (new Xls($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
        try {
            $this->enableCsrfToken();
            $this->configRequest(['files' => ['excel_file' => [
                'tmp_name' => $path, 'error' => UPLOAD_ERR_OK, 'name' => 'alunos.xls',
                'type' => 'application/vnd.ms-excel', 'size' => filesize($path),
            ]]]);
            $this->post('/alunos/importar');
            $this->assertRedirect(['controller' => 'Alunos', 'action' => 'index']);
            $this->assertFlashMessage('1 alunos importados com sucesso.');
            $this->assertSame(2, $this->getTableLocator()->get('Alunos')->find()->count());
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testImportWithoutFileDisplaysError(): void
    {
        $this->enableCsrfToken();
        $this->post('/alunos/importar');
        $this->assertResponseCode(422);
        $this->assertResponseContains('Envie uma planilha válida');
        $this->assertSame(1, $this->getTableLocator()->get('Alunos')->find()->count());
    }
}
