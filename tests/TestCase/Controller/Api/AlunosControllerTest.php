<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * App\Controller\Api\AlunosController Test Case
 *
 * @link \App\Controller\Api\AlunosController
 */
class AlunosControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Tipos',
        'app.Users',
        'app.Alunos',
        'app.Responsaveis',
    ];

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\Api\AlunosController::index()
     */
    public function testIndex(): void
    {
        $this->get('/api/alunos/listar.json');
        $this->assertResponseOk();
        $this->assertCount(1, json_decode((string)$this->_response->getBody(), true)['data']);
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\Api\AlunosController::view()
     */
    public function testView(): void
    {
        $this->get('/api/alunos/buscar/1.json');
        $this->assertResponseOk();
        $this->assertSame(1, json_decode((string)$this->_response->getBody(), true)['data']['matricula']);
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\Api\AlunosController::add()
     */
    public function testAdd(): void
    {
        $data = $this->getTableLocator()->get('Alunos')->get(1)->toArray();
        unset($data['id']);
        $data['matricula'] = 2;
        $this->post('/api/alunos/adicionar.json', $data);
        $this->assertResponseCode(201);
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\Api\AlunosController::edit()
     */
    public function testEdit(): void
    {
        $this->patch('/api/alunos/editar/1.json', ['nome' => 'Aluno editado']);
        $this->assertResponseOk();
        $this->assertSame('Aluno editado', $this->getTableLocator()->get('Alunos')->get(1)->nome);
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\Api\AlunosController::delete()
     */
    public function testDelete(): void
    {
        $this->post('/api/alunos/deletar/1.json');
        $this->assertResponseOk();
        $this->assertNotNull($this->getTableLocator()->get('Alunos')->get(1)->deleted);
        $this->assertSame(1, $this->getTableLocator()->get('Responsaveis')->get(1)->alunos_id);
        $this->post('/api/alunos/reativar/1.json');
        $this->assertResponseOk();
        $this->assertNull($this->getTableLocator()->get('Alunos')->get(1)->deleted);
    }

    public function testImportRequiresSpreadsheet(): void
    {
        $this->post('/api/alunos/importar.json');
        $this->assertResponseCode(422);
    }

    public function testImportSavesStudentsAndRejectsInvalidRowsAtomically(): void
    {
        foreach ([true, false] as $valid) {
            $spreadsheet = new Spreadsheet();
            $rows = [
                ['nome', 'matricula', 'curso', 'situacao', 'emaila', 'emailp', 'anop'],
                ['Aluno importado', 20261029, 'Informática', 'Ativo', 'aluno@example.com', 'pessoal@example.com', '2026'],
            ];
            if (!$valid) {
                $rows[] = ['', 20261030, '', '', '', '', ''];
            }
            $spreadsheet->getActiveSheet()->fromArray($rows);
            $path = tempnam(TMP, 'planilha_teste_');
            (new Xlsx($spreadsheet))->save($path);
            $spreadsheet->disconnectWorksheets();
            try {
                $this->replaceRequest(['files' => ['excel_file' => [
                    'tmp_name' => $path, 'error' => UPLOAD_ERR_OK, 'name' => 'alunos.xlsx',
                    'type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'size' => filesize($path),
                ]]]);
                $this->post('/api/alunos/importar.json');
                $this->assertResponseCode($valid ? 201 : 422);
                if (!$valid) {
                    $result = json_decode((string)$this->_response->getBody(), true);
                    $this->assertArrayHasKey(3, $result['errors']);
                }
                $this->assertSame(2, $this->getTableLocator()->get('Alunos')->find()->count());
            } finally {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
    }

    public function testImportReportHeadersInXlsAndXlsx(): void
    {
        foreach (['xls', 'xlsx'] as $extension) {
            $this->uploadRows([
                ['#', 'Nome', 'Matrícula', 'Curso', 'Campus', 'Polo', 'Situação',
                    'E-mail Acadêmico', 'E-mail Pessoal', 'Ano/Periodo Letivo'],
                [1, '  Aluno do relatório  ', '2026110512345678', 'Informática', 'Campus extra', 'Polo extra',
                    'Situação com mais de 25 caracteres', null, null, '2026/2'],
                [2, null, null, null, 'Coluna ignorada'],
            ], $extension);
            $this->assertSame(201, $this->_response->getStatusCode(), (string)$this->_response->getBody());
            $this->assertSame(1, json_decode((string)$this->_response->getBody(), true)['imported']);
            $aluno = $this->getTableLocator()->get('Alunos')->find()->orderByDesc('id')->firstOrFail();
            $this->assertSame('Aluno do relatório', $aluno->nome);
            $this->assertSame(2026110512345678, $aluno->matricula);
            $this->assertSame('Situação com mais de 25 caracteres', $aluno->situacao);
            $this->assertSame('', $aluno->emaila);
            $this->assertSame('', $aluno->emailp);
            $this->assertSame('2026/2', $aluno->anop);
            $this->assertNull($aluno->deleted);
        }
        $this->assertSame(3, $this->getTableLocator()->get('Alunos')->find()->count());
    }

    public function testImportRequestedHeadersInDifferentOrder(): void
    {
        $this->uploadRows([
            [' emailpessoal ', 'anoperiodoletivo', 'situacao', 'curso', 'matricula', 'nome', 'emailacademico'],
            ['pessoal@example.com', '2026/1', 'Ativo', 'Computação', '1234567890123456', 'Aluno', 'a@example.com'],
        ]);
        $this->assertResponseCode(201);
        $aluno = $this->getTableLocator()->get('Alunos')->find()->orderByDesc('id')->firstOrFail();
        $this->assertSame('pessoal@example.com', $aluno->emailp);
        $this->assertSame('a@example.com', $aluno->emaila);
        $this->assertSame('2026/1', $aluno->anop);
    }

    public function testImportIdentifiesInvalidRowsAndDoesNotSaveValidOnes(): void
    {
        $this->uploadRows([
            ['nome', 'matricula', 'curso', 'situacao', 'emailacademico', 'emailpessoal', 'anoperiodoletivo'],
            ['Aluno válido', '1234', 'Computação', 'Ativo', '', '', '2026/1'],
            [],
            ['Aluno inválido', '123ABC', 'Computação', 'Ativo', '', '', '2026/1'],
            ['Aluno com matrícula grande', '9223372036854775808', 'Computação', 'Ativo', '', '', '2026/1'],
            ['Aluno sem curso', '1235', '', 'Ativo', '', '', '2026/1'],
        ]);
        $this->assertResponseCode(422);
        $result = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame(0, $result['imported']);
        $this->assertArrayHasKey('matricula', $result['errors'][4]);
        $this->assertArrayHasKey('matricula', $result['errors'][5]);
        $this->assertArrayHasKey('curso', $result['errors'][6]);
        $this->assertSame(1, $this->getTableLocator()->get('Alunos')->find()->count());
    }

    public function testImportRejectsMissingDuplicateHeadersAndEmptySheets(): void
    {
        foreach (
            [
            [['nome', 'matricula']],
            [['nome', 'matricula', 'curso', 'situacao', 'emailacademico', 'emailpessoal', 'anoperiodoletivo', 'emaila']],
            [['nome', 'matricula', 'curso', 'situacao', 'emailacademico', 'emailpessoal', 'anoperiodoletivo']],
            [],
            ] as $rows
        ) {
            $this->uploadRows($rows);
            $this->assertResponseCode(422);
            $this->assertSame(1, $this->getTableLocator()->get('Alunos')->find()->count());
        }
    }

    public function testImportRejectsCorruptedFileAndUnsupportedExtension(): void
    {
        foreach (['alunos.xlsx', 'alunos.csv'] as $filename) {
            $path = tempnam(TMP, 'arquivo_invalido_');
            file_put_contents($path, 'Este conteúdo não é uma planilha Excel.');
            try {
                $this->replaceRequest(['files' => ['excel_file' => [
                    'tmp_name' => $path, 'error' => UPLOAD_ERR_OK, 'name' => $filename,
                    'type' => 'application/octet-stream', 'size' => filesize($path),
                ]]]);
                $this->post('/api/alunos/importar.json');
                $this->assertResponseCode(422);
                $this->assertSame(1, $this->getTableLocator()->get('Alunos')->find()->count());
            } finally {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
    }

    public function testImportOnlyAcceptsPost(): void
    {
        $this->get('/api/alunos/importar.json');
        $this->assertResponseCode(405);
    }

    /**
     * @param array<array<mixed>> $rows Conteúdo da planilha.
     * @param string $extension Formato real do arquivo.
     * @return void
     */
    private function uploadRows(array $rows, string $extension = 'xlsx'): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows);
        $path = tempnam(TMP, 'importacao_teste_');
        $writer = $extension === 'xls' ? new Xls($spreadsheet) : new Xlsx($spreadsheet);
        $writer->save($path);
        $spreadsheet->disconnectWorksheets();
        try {
            $this->replaceRequest(['files' => ['excel_file' => [
                'tmp_name' => $path, 'error' => UPLOAD_ERR_OK, 'name' => 'alunos.' . $extension,
                'type' => 'application/octet-stream', 'size' => filesize($path),
            ]]]);
            $this->post('/api/alunos/importar.json');
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
