<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Table\AlunosTable;
use Cake\Utility\Text;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

class AlunosImportService
{
    /**
     * Cabeçalhos normalizados => campos do banco. Mantém os nomes antigos da API.
     *
     * @var array<string, string>
     */
    private const CAMPOS = [
        'nome' => 'nome',
        'matricula' => 'matricula',
        'curso' => 'curso',
        'situacao' => 'situacao',
        'emailacademico' => 'emaila',
        'emailpessoal' => 'emailp',
        'anoperiodoletivo' => 'anop',
        'emaila' => 'emaila',
        'emailp' => 'emailp',
        'anop' => 'anop',
    ];

    /**
     * @param \App\Model\Table\AlunosTable $alunos Tabela de destino.
     */
    public function __construct(private AlunosTable $alunos)
    {
    }

    /**
     * Importa a primeira aba, criando ou atualizando pela matrícula em uma transação.
     *
     * @param \Psr\Http\Message\UploadedFileInterface|null $file Upload recebido no formulário.
     * @return array<string, mixed> Quantidade importada, mensagem e erros por linha da planilha.
     */
    public function import(?UploadedFileInterface $file): array
    {
        if (!$file || $file->getError() !== UPLOAD_ERR_OK) {
            return $this->failure('Envie uma planilha válida no campo excel_file.');
        }
        $extension = strtolower(pathinfo($file->getClientFilename() ?? '', PATHINFO_EXTENSION));
        if (!in_array($extension, ['xls', 'xlsx'], true)) {
            return $this->failure('Apenas arquivos XLS e XLSX são aceitos.');
        }

        $path = tempnam(TMP, 'alunos_');
        if ($path === false) {
            throw new RuntimeException('Não foi possível preparar a importação.');
        }
        $spreadsheet = null;
        try {
            $file->moveTo($path);
            $reader = IOFactory::createReaderForFile($path, [IOFactory::READER_XLS, IOFactory::READER_XLSX]);
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);
            $sheets = $reader->listWorksheetNames($path);
            $reader->setLoadSheetsOnly($sheets[0]);
            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getSheet(0);
            $columns = [];
            foreach ($sheet->getRowIterator(1, 1)->current()->getCellIterator() as $cell) {
                $header = strtolower(Text::transliterate(trim((string)$cell->getValue())));
                $header = preg_replace('/[^a-z0-9]/', '', $header);
                $field = self::CAMPOS[$header] ?? null;
                if ($field === null) {
                    continue;
                }
                if (isset($columns[$field])) {
                    return $this->failure('Cabeçalho duplicado para o campo ' . $field . '.');
                }
                $columns[$field] = $cell->getColumn();
            }
            $missing = array_diff(array_unique(array_values(self::CAMPOS)), array_keys($columns));
            if ($missing) {
                return $this->failure('Cabeçalhos obrigatórios ausentes: ' . implode(', ', $missing) . '.');
            }
            if ($sheet->getHighestDataRow() < 2) {
                return $this->failure('A planilha não contém alunos para importar.');
            }

            $records = [];
            $errors = [];
            foreach ($sheet->getRowIterator(2, $sheet->getHighestDataRow()) as $row) {
                $line = $row->getRowIndex();
                $data = [];
                foreach ($columns as $field => $column) {
                    // Lê o valor original, sem executar fórmulas da planilha.
                    $value = $sheet->getCell($column . $line)->getValue();
                    // O leitor XLS retorna números como float; evita notação científica na matrícula.
                    if ($field === 'matricula' && is_float($value) && floor($value) === $value) {
                        $value = sprintf('%.0f', $value);
                    }
                    $data[$field] = trim((string)$value);
                }
                if (ctype_digit($data['matricula'])) {
                    $data['matricula'] = ltrim($data['matricula'], '0') ?: '0';
                }
                if (!array_filter($data, static fn($value) => $value !== '')) {
                    continue;
                }
                $record = $this->alunos->newEntity($data, ['validate' => 'import']);
                if ($record->hasErrors()) {
                    $errors[$line] = $record->getErrors();
                }
                $records[$line] = $record;
            }
            if ($errors) {
                return $this->failure('Nenhum aluno foi importado. Corrija as linhas indicadas.', $errors);
            }
            if (!$records) {
                return $this->failure('A planilha não contém alunos para importar.');
            }

            // A matrícula é numérica no banco: 001 e 1 identificam o mesmo aluno.
            $linesByMatricula = [];
            foreach ($records as $line => $record) {
                $matricula = (string)$record->matricula;
                if (isset($linesByMatricula[$matricula])) {
                    $errors[$line]['matricula'] = ['duplicada' => 'Matrícula repetida na planilha.'];
                } else {
                    $linesByMatricula[$matricula] = $line;
                }
            }
            if ($errors) {
                return $this->failure('Nenhum aluno foi importado. Corrija as matrículas repetidas.', $errors);
            }

            // Consulta em lotes para não exceder o limite de parâmetros do banco.
            $existing = [];
            foreach (array_chunk(array_keys($linesByMatricula), 500) as $matriculas) {
                foreach ($this->alunos->find()->where(['matricula IN' => $matriculas])->all() as $record) {
                    $matricula = (string)$record->matricula;
                    if (isset($existing[$matricula])) {
                        $line = $linesByMatricula[$matricula];
                        $errors[$line]['matricula'] = [
                            'ambigua' => 'Há mais de um aluno com esta matrícula no banco. Corrija o cadastro.',
                        ];
                    }
                    $existing[$matricula] = $record;
                }
            }
            if ($errors) {
                return $this->failure('Nenhum aluno foi importado. Há matrículas ambíguas no cadastro.', $errors);
            }

            $updated = 0;
            foreach ($records as $line => $record) {
                $current = $existing[(string)$record->matricula] ?? null;
                if ($current !== null) {
                    // Preserva ID, vínculos e estado de ativação; não corta campos da planilha.
                    $records[$line] = $this->alunos->patchEntity(
                        $current,
                        $record->extract(array_values(array_unique(self::CAMPOS))),
                        ['validate' => 'import'],
                    );
                    $updated++;
                }
            }
            if (!$this->alunos->saveMany($records)) {
                foreach ($records as $line => $record) {
                    if ($record->hasErrors()) {
                        $errors[$line] = $record->getErrors();
                    }
                }

                return $this->failure('Nenhum aluno foi importado. Verifique os dados.', $errors);
            }

            return [
                'message' => 'Alunos importados com sucesso.',
                'imported' => count($records),
                'inseridos' => count($records) - $updated,
                'atualizados' => $updated,
            ];
        } catch (ReaderException $exception) {
            return $this->failure('Não foi possível ler a planilha. Envie um arquivo XLS ou XLSX válido.');
        } finally {
            $spreadsheet?->disconnectWorksheets();
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    /**
     * @param string $message Motivo da rejeição.
     * @param array<int, mixed> $errors Erros identificados por linha.
     * @return array<string, mixed>
     */
    private function failure(string $message, array $errors = []): array
    {
        return ['message' => $message, 'imported' => 0, 'errors' => $errors];
    }
}
