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
        $this->assertSame(2, $this->getTableLocator()->get('Alunos')->find()->count());
        $result = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame(0, $result['inseridos']);
        $this->assertSame(1, $result['atualizados']);
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

    public function testSearchByMatriculaIncludesAllResponsibleUsersAndStudentsWithoutLinks(): void
    {
        $alunos = $this->getTableLocator()->get('Alunos');
        $aluno = $alunos->get(1);
        $aluno->matricula = 2026110512345678;
        $alunos->saveOrFail($aluno);
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'nome' => 'Outro responsável', 'cpf' => 222, 'telefone' => 222,
            'email' => 'outro@example.com', 'password' => 'senha123', 'tipo_id' => 1,
        ]);
        $users->saveOrFail($user);
        $links = $this->getTableLocator()->get('Responsaveis');
        $links->saveOrFail($links->newEntity(['users_id' => $user->id, 'alunos_id' => 1]));

        $this->get('/api/alunos/findmat/12345678.json');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true)['data'];
        $this->assertCount(1, $data);
        $this->assertSame(2026110512345678, $data[0]['matricula']);
        $this->assertCount(2, $data[0]['responsaveis']);
        $this->assertResponseNotContains('password');

        $copy = $aluno->extract(['nome', 'curso', 'situacao', 'emaila', 'emailp', 'anop']);
        $copy['matricula'] = '9223372036854775807';
        $new = $alunos->saveOrFail($alunos->newEntity($copy));
        $this->get('/api/alunos/findmat/9223372036854775807.json');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true)['data'];
        $this->assertSame($new->id, $data[0]['id']);
        $this->assertSame([], $data[0]['responsaveis']);

        $this->post('/api/alunos/desativar/' . $new->id . '.json');
        $this->get('/api/alunos/findmat/9223372036854775807.json');
        $this->assertResponseOk();
        $this->assertSame([], json_decode((string)$this->_response->getBody(), true)['data']);
        $this->get('/api/alunos/findmat/99999999999999999999.json');
        $this->assertResponseCode(422);
        $this->post('/api/alunos/findmat/123.json');
        $this->assertResponseCode(405);
    }

    public function testSearchByNameAcceptsAccentsApostrophesAndHyphens(): void
    {
        $table = $this->getTableLocator()->get('Alunos');
        $aluno = $table->get(1);
        $aluno->nome = "Ana-Maria D'Ávila";
        $table->saveOrFail($aluno);
        foreach (["D'Ávila", 'Ana-Maria', 'Ávila'] as $name) {
            $this->get('/api/alunos/findname/' . rawurlencode($name) . '.json');
            $this->assertResponseOk();
            $data = json_decode((string)$this->_response->getBody(), true)['data'];
            $this->assertSame(1, $data[0]['id']);
        }
        foreach ([' ', '%', '_', "' OR 1=1", '%C3%81vila'] as $invalid) {
            $this->get('/api/alunos/findname/' . rawurlencode($invalid) . '.json');
            $this->assertResponseCode(422);
        }
        $this->post('/api/alunos/desativar/1.json');
        $this->get('/api/alunos/findname/Ana.json');
        $this->assertResponseOk();
        $this->assertSame([], json_decode((string)$this->_response->getBody(), true)['data']);
        $this->post('/api/alunos/findname/Ana.json');
        $this->assertResponseCode(405);
    }

    public function testImportAcceptsUploadAliasesAndPreservesStudentIdAndLinks(): void
    {
        foreach (['arquivo', 'file'] as $field) {
            $this->uploadRows([
                ['nome', 'matricula', 'curso', 'situacao', 'emaila', 'emailp', 'anop'],
                ['Nome atualizado', '0001', 'Computação', 'Matriculado', '', '', '2026/2'],
            ], 'xlsx', $field);
            $this->assertResponseCode(201);
            $result = json_decode((string)$this->_response->getBody(), true);
            $this->assertSame(1, $result['atualizados']);
            $this->assertSame(0, $result['inseridos']);
            $table = $this->getTableLocator()->get('Alunos');
            $this->assertSame(1, $table->find()->count());
            $this->assertSame('Nome atualizado', $table->get(1)->nome);
            $this->assertSame(1, $this->getTableLocator()->get('Responsaveis')->get(1)->alunos_id);
        }
    }

    /**
     * @param array<array<mixed>> $rows Conteúdo da planilha.
     * @param string $extension Formato real do arquivo.
     * @param string $field Campo de upload.
     * @return void
     */
    private function uploadRows(array $rows, string $extension = 'xlsx', string $field = 'excel_file'): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows);
        $path = tempnam(TMP, 'importacao_teste_');
        $writer = $extension === 'xls' ? new Xls($spreadsheet) : new Xlsx($spreadsheet);
        $writer->save($path);
        $spreadsheet->disconnectWorksheets();
        try {
            $this->replaceRequest(['files' => [$field => [
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
