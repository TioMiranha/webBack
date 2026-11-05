<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\AlunosImportService;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AlunosImportServiceTest extends TestCase
{
    protected array $fixtures = ['app.Alunos'];

    public function testImportRollsBackWhenLaterRowFailsBusinessRule(): void
    {
        $table = $this->getTableLocator()->get('Alunos');
        $table->rulesChecker()->add(
            static fn($entity) => (string)$entity->matricula !== '999',
            'matriculaBloqueada',
            ['errorField' => 'matricula', 'message' => 'Matrícula bloqueada para este teste.'],
        );
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['nome', 'matricula', 'curso', 'situacao', 'emailacademico', 'emailpessoal', 'anoperiodoletivo'],
            ['Primeiro aluno', '123', 'Informática', 'Ativo', '', '', '2026/2'],
            ['Segundo aluno', '999', 'Informática', 'Ativo', '', '', '2026/2'],
        ]);
        $path = tempnam(TMP, 'importacao_transacao_');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
        try {
            $upload = new UploadedFile($path, filesize($path), UPLOAD_ERR_OK, 'alunos.xlsx');
            $result = (new AlunosImportService($table))->import($upload);
            $this->assertSame(0, $result['imported']);
            $this->assertArrayHasKey('matricula', $result['errors'][3]);
            $this->assertSame(1, $table->find()->count());
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
