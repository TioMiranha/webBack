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
    protected array $fixtures = ['app.Tipos', 'app.Users', 'app.Alunos', 'app.Responsaveis'];

    public function testImportRejectsRepeatedAndAmbiguousMatriculasWithoutSaving(): void
    {
        $table = $this->getTableLocator()->get('Alunos');
        $original = $table->get(1)->nome;
        $result = $this->importRows([
            ['Nome alterado', '01', 'Informática', 'Ativo', '', '', '2026/2'],
            ['Outra linha', '1', 'Informática', 'Ativo', '', '', '2026/2'],
        ]);
        $this->assertSame(0, $result['imported']);
        $this->assertArrayHasKey('matricula', $result['errors'][3]);
        $this->assertSame($original, $table->get(1)->nome);

        $copy = $table->get(1)->toArray();
        unset($copy['id']);
        $table->saveOrFail($table->newEntity($copy));
        $result = $this->importRows([
            ['Nome alterado', '1', 'Informática', 'Ativo', '', '', '2026/2'],
            ['Aluno novo', '2', 'Informática', 'Ativo', '', '', '2026/2'],
        ]);
        $this->assertSame(0, $result['imported']);
        $this->assertArrayHasKey('matricula', $result['errors'][2]);
        $this->assertSame(2, $table->find()->count());
        $this->assertSame($original, $table->get(1)->nome);
    }

    public function testImportPreservesDeactivationAndRejectsLongFieldsWithoutTruncation(): void
    {
        $table = $this->getTableLocator()->get('Alunos');
        $aluno = $table->get(1);
        $aluno->deleted = '2026-11-01 12:00:00';
        $table->saveOrFail($aluno);
        $result = $this->importRows([
            ['Nome atualizado', '1', 'Informática', 'Matriculado', '', '', '2026/2'],
        ]);
        $this->assertSame(1, $result['atualizados']);
        $this->assertSame('2026-11-01 12:00:00', $table->get(1)->deleted);

        $result = $this->importRows([
            ['Não deve salvar', '1', 'Informática', 'Ativo', '', '', '2026/2'],
            [str_repeat('a', 201), '2', 'Informática', 'Ativo', '', '', '2026/2'],
        ]);
        $this->assertSame(0, $result['imported']);
        $this->assertArrayHasKey('nome', $result['errors'][3]);
        $this->assertSame('Nome atualizado', $table->get(1)->nome);
        $this->assertSame(1, $table->find()->count());
    }

    public function testImportRollsBackUpdatesAsWellAsInsertsWhenLaterRuleFails(): void
    {
        $table = $this->getTableLocator()->get('Alunos');
        $original = $table->get(1)->nome;
        $table->rulesChecker()->add(
            static fn($entity) => (string)$entity->matricula !== '999',
            'bloqueada',
            ['errorField' => 'matricula', 'message' => 'Matrícula bloqueada.'],
        );
        $result = $this->importRows([
            ['Nome alterado', '1', 'Informática', 'Ativo', '', '', '2026/2'],
            ['Aluno novo', '2', 'Informática', 'Ativo', '', '', '2026/2'],
            ['Aluno bloqueado', '999', 'Informática', 'Ativo', '', '', '2026/2'],
        ]);
        $this->assertSame(0, $result['imported']);
        $this->assertArrayHasKey('matricula', $result['errors'][4]);
        $this->assertSame($original, $table->get(1)->nome);
        $this->assertSame(1, $table->find()->count());
        $this->assertSame(1, $this->getTableLocator()->get('Responsaveis')->get(1)->alunos_id);
    }

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

    /**
     * @param array<array<mixed>> $rows Alunos da planilha.
     * @return array<string, mixed>
     */
    private function importRows(array $rows): array
    {
        array_unshift($rows, ['nome', 'matricula', 'curso', 'situacao', 'emaila', 'emailp', 'anop']);
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows);
        $path = tempnam(TMP, 'importacao_atualizacao_');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
        try {
            $upload = new UploadedFile($path, filesize($path), UPLOAD_ERR_OK, 'alunos.xlsx');

            return (new AlunosImportService($this->getTableLocator()->get('Alunos')))->import($upload);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
