<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Throwable;

class AlunosController extends AppController
{
    /**
     * Valida a planilha e grava o lote em uma única transação.
     */
    public function import(): Response
    {
        $this->request->allowMethod(['post']);
        $upload = $this->request->getUploadedFile('excel_file')
            ?? $this->request->getUploadedFile('arquivo')
            ?? $this->request->getUploadedFile('file');
        if (!$upload instanceof UploadedFileInterface || $upload->getError() !== UPLOAD_ERR_OK) {
            return $this->json(['message' => 'Envie uma planilha válida.', 'imported' => 0, 'errors' => []], 422);
        }
        $extension = strtolower(pathinfo($upload->getClientFilename() ?? '', PATHINFO_EXTENSION));
        if (!in_array($extension, ['xls', 'xlsx'], true)) {
            return $this->json(['message' => 'Apenas XLS e XLSX são aceitos.', 'imported' => 0, 'errors' => []], 422);
        }

        $path = null;
        $spreadsheet = null;
        try {
            $path = tempnam(TMP, 'alunos_api_');
            if ($path === false) {
                throw new RuntimeException('Não foi possível preparar a importação.');
            }
            $upload->moveTo($path);
            $reader = IOFactory::createReaderForFile($path, [IOFactory::READER_XLS, IOFactory::READER_XLSX]);
            $reader->setReadDataOnly(true);
            $sheets = $reader->listWorksheetNames($path);
            $reader->setLoadSheetsOnly($sheets[0]);
            $spreadsheet = $reader->load($path);
            $rows = $spreadsheet->getSheet(0)->toArray(null, false, false, true);

            $fields = [
                'nome' => 'nome', 'matricula' => 'matricula', 'curso' => 'curso', 'situacao' => 'situacao',
                'emailacademico' => 'emaila', 'emailpessoal' => 'emailp', 'anoperiodoletivo' => 'anop',
            ];
            $aliases = ['emaila' => 'emailacademico', 'emailp' => 'emailpessoal', 'anop' => 'anoperiodoletivo'];
            $headers = [];
            foreach ($rows[1] ?? [] as $column => $header) {
                $header = $this->normalizeImportHeader((string)$header);
                $header = $aliases[$header] ?? $header;
                if (!isset($fields[$header])) {
                    continue;
                }
                if (isset($headers[$header])) {
                    return $this->json([
                        'message' => 'Cabeçalho duplicado: ' . $header, 'imported' => 0, 'errors' => [],
                    ], 422);
                }
                $headers[$header] = $column;
            }
            $missing = array_diff(array_keys($fields), array_keys($headers));
            if ($missing) {
                return $this->json([
                    'message' => 'Cabeçalhos ausentes: ' . implode(', ', $missing), 'imported' => 0, 'errors' => [],
                ], 422);
            }

            $alunoTable = $this->fetchTable('Alunos');
            $alunosPorMatricula = [];
            $linhaPorMatricula = [];
            $erros = [];
            foreach (array_slice($rows, 1, null, true) as $rowNumber => $row) {
                $data = [];
                foreach ($fields as $source => $destination) {
                    $value = $row[$headers[$source]] ?? '';
                    if ($destination === 'matricula' && is_float($value) && floor($value) === $value) {
                        $value = sprintf('%.0f', $value);
                    }
                    $data[$destination] = trim((string)$value);
                }
                if (!array_filter($data, static fn($value) => $value !== '')) {
                    continue;
                }
                if (ctype_digit($data['matricula'])) {
                    $data['matricula'] = ltrim($data['matricula'], '0') ?: '0';
                }
                $aluno = $alunoTable->newEntity($data, ['validate' => 'import']);
                if ($aluno->hasErrors()) {
                    $erros[$rowNumber] = $aluno->getErrors();
                    continue;
                }
                $matricula = (string)$aluno->matricula;
                if (isset($alunosPorMatricula[$matricula])) {
                    $erros[$rowNumber]['matricula'] = ['duplicada' => 'Matrícula repetida na planilha.'];
                    continue;
                }
                $alunosPorMatricula[$matricula] = $aluno;
                $linhaPorMatricula[$matricula] = $rowNumber;
            }
            if ($erros || !$alunosPorMatricula) {
                return $this->json([
                    'message' => 'Nenhum aluno foi importado. Verifique as linhas da planilha.',
                    'imported' => 0, 'errors' => $erros,
                ], 422);
            }

            // Consulta em lotes e rejeita matrículas ambíguas no banco.
            $alunosExistentes = [];
            foreach (array_chunk(array_keys($alunosPorMatricula), 500) as $loteMatriculas) {
                foreach ($alunoTable->find()->where(['matricula IN' => $loteMatriculas])->all() as $existente) {
                    $matricula = (string)$existente->matricula;
                    if (isset($alunosExistentes[$matricula])) {
                        $erros[$linhaPorMatricula[$matricula]]['matricula'] = [
                            'ambigua' => 'Há mais de um cadastro com esta matrícula.',
                        ];
                    }
                    $alunosExistentes[$matricula] = $existente;
                }
            }
            if ($erros) {
                return $this->json([
                    'message' => 'Corrija as matrículas ambíguas.', 'imported' => 0, 'errors' => $erros,
                ], 422);
            }

            $atualizados = 0;
            foreach ($alunosPorMatricula as $matricula => $aluno) {
                if (isset($alunosExistentes[$matricula])) {
                    // Preserva ID, vínculos e ativação, sem truncar dados.
                    $alunosPorMatricula[$matricula] = $alunoTable->patchEntity(
                        $alunosExistentes[$matricula],
                        $aluno->extract(array_values($fields)),
                        ['validate' => 'import'],
                    );
                    $atualizados++;
                }
            }
            // saveMany controla a transação de todo o lote.
            if (!$alunoTable->saveMany($alunosPorMatricula)) {
                foreach ($alunosPorMatricula as $matricula => $aluno) {
                    if ($aluno->hasErrors()) {
                        $erros[$linhaPorMatricula[$matricula]] = $aluno->getErrors();
                    }
                }

                return $this->json([
                    'message' => 'Nenhum aluno foi importado. Verifique os dados.', 'imported' => 0, 'errors' => $erros,
                ], 422);
            }

            return $this->json([
                'data' => 'Importação processada.', 'message' => 'Alunos importados com sucesso.',
                'imported' => count($alunosPorMatricula),
                'inseridos' => count($alunosPorMatricula) - $atualizados, 'atualizados' => $atualizados,
                'erros' => 0, 'detalhesErros' => [], 'camposTruncados' => 0,
            ], 201);
        } catch (ReaderException $e) {
            return $this->json([
                'message' => 'Envie um arquivo XLS ou XLSX válido.', 'imported' => 0, 'errors' => [],
            ], 422);
        } catch (Throwable $e) {
            return $this->json([
                'message' => 'Erro ao importar: ' . $e->getMessage(), 'imported' => 0, 'errors' => [],
            ], 500);
        } finally {
            $spreadsheet?->disconnectWorksheets();
            if (is_string($path) && is_file($path)) {
                unlink($path);
            }
        }
    }

    /**
     * Normaliza os nomes das colunas da planilha.
     */
    private function normalizeImportHeader(string $header): string
    {
        $header = mb_strtolower(trim($header));

        return preg_replace('/[^a-z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $header) ?: $header);
    }

    /**
     * Busca os alunos ativos por parte da matrícula.
     */
    public function findByMat(?string $matricula = null): Response
    {
        $this->request->allowMethod(['get']);
        if ($matricula === null || !ctype_digit((string)$matricula) || strlen((string)$matricula) > 19) {
            return $this->json(['message' => 'Informe uma matrícula com até 19 dígitos.'], 422);
        }
        try {
            $alunoTable = $this->fetchTable('Alunos');
            $result = $alunoTable->find()->contain(['Cursos', 'Responsaveis.Users'])
                ->where(['Alunos.deleted IS' => null])
                ->where(static function ($exp) use ($matricula) {
                    return $exp->like('CAST(Alunos.matricula AS CHAR)', '%' . $matricula . '%');
                })->orderBy(['Alunos.nome' => 'ASC', 'Alunos.id' => 'ASC'])->all();
        } catch (Exception $e) {
            return $this->json(['message' => 'Erro ao buscar matrícula: ' . $e->getMessage()], 500);
        }

        return $this->json(['data' => $result]);
    }

    /**
     * Busca o aluno ativo pela matrícula e agrupa os responsáveis.
     */
    public function findByReg(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        if ($id === null || !ctype_digit((string)$id) || strlen((string)$id) > 19) {
            return $this->json(['message' => 'Informe uma matrícula válida.'], 422);
        }
        $statusCode = 200;
        $result = null;

        try {
            $sql = 'SELECT a.id, a.nome AS aluno, a.matricula, a.curso,
                           u.id AS usuario_id, u.nome AS Responsavel, u.telefone, u.cpf, u.email, t.nome AS perfil
                    FROM alunos a
                    LEFT JOIN responsaveis r ON a.id = r.alunos_id AND r.deleted IS NULL
                    LEFT JOIN users u ON u.id = r.users_id AND u.deleted IS NULL
                    LEFT JOIN tipos t ON u.tipo_id = t.id
                    WHERE a.matricula = :matricula AND a.deleted IS NULL ORDER BY a.id, r.id';
            $conexao = $this->fetchTable('Alunos')->getConnection();
            $results = $conexao->execute($sql, ['matricula' => (string)$id])->fetchAll('assoc');

            if (!empty($results)) {
                $result['id'] = (int)$results[0]['id'];
                $result['nome'] = $results[0]['aluno'];
                $result['curso'] = $results[0]['curso'];
                $result['matricula'] = $results[0]['matricula'];
                $result['responsavel'] = [];
                foreach ($results as $value) {
                    if ($value['usuario_id'] === null) {
                        continue;
                    }
                    $result['responsavel'][] = [
                        'cpf' => $value['cpf'], 'nome' => $value['Responsavel'], 'telefone' => $value['telefone'],
                        'email' => $value['email'], 'perfil' => $value['perfil'],
                    ];
                }
            } else {
                return $this->json(['message' => 'Aluno não encontrado!', 'code' => 404], 404);
            }
        } catch (Exception $e) {
            return $this->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }

        return $this->json(['data' => $result], $statusCode);
    }

    /**
     * Busca os alunos ativos por parte do nome.
     */
    public function findByName(?string $name = null): Response
    {
        $this->request->allowMethod(['get']);
        // A rota já decodifica o parâmetro; não decodifica uma segunda vez.
        $name = trim((string)$name);
        if ($name === '' || preg_match("/^[\\p{L}\\p{M} '\\x{2019}-]+$/u", $name) !== 1) {
            return $this->json(['message' => 'Informe um nome válido.'], 422);
        }

        try {
            $alunoTable = $this->fetchTable('Alunos');
            $result = $alunoTable->find()->contain(['Responsaveis.Users'])
                ->where(['Alunos.nome LIKE' => '%' . $name . '%', 'Alunos.deleted IS' => null])
                ->orderBy(['Alunos.nome' => 'ASC', 'Alunos.id' => 'ASC'])->all();
        } catch (Exception $e) {
            return $this->json(['message' => 'Erro ao buscar aluno: ' . $e->getMessage()], 500);
        }

        return $this->json(['data' => $result]);
    }

    /**
     * Lista os registros ativos e aplica os filtros recebidos.
     */
    public function list(): Response
    {
        $this->request->allowMethod(['get', 'post']);
        $tabela = $this->fetchTable('Alunos');
        $statusCode = 200;

        try {
            $query = $tabela->find()->contain(['Responsaveis.Users'])->where(['Alunos.deleted IS' => null]);
            $form = $this->request->getData() + $this->request->getQueryParams();
            foreach (['nome', 'matricula'] as $field) {
                if (isset($form[$field]) && $form[$field] !== '') {
                    if (!is_scalar($form[$field])) {
                        return $this->json(['message' => 'Filtro inválido: ' . $field], 422);
                    }
                    $query->where(['Alunos.' . $field => $form[$field]]);
                }
            }
            $result = $query->orderBy(['Alunos.id' => 'ASC'])->all();
        } catch (Exception $e) {
            return $this->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }

        return $this->json(['data' => $result], $statusCode);
    }

    /**
     * Busca o registro e seus vínculos pelo ID da rota.
     */
    public function find(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        $statusCode = 200;

        try {
            $result = $this->fetchTable('Alunos')->get((int)$id, contain: ['Responsaveis.Users'])->toArray();
            // Mantém a máscara de CPF somente na resposta, sem alterar o cadastro.
            foreach ($result['responsaveis'] as &$responsavel) {
                $cpf = preg_replace('/[^0-9]/', '', (string)$responsavel['user']['cpf']);
                if (strlen($cpf) === 11) {
                    $responsavel['user']['cpf'] = substr($cpf, 0, 3) . '.***.***-' . substr($cpf, 9, 2);
                }
            }
            unset($responsavel);
        } catch (RecordNotFoundException $e) {
            return $this->json(['message' => 'Registro não encontrado!', 'code' => 404], 404);
        } catch (Exception $e) {
            return $this->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }

        return $this->json(['data' => $result], $statusCode);
    }

    /**
     * Cadastra o registro com os campos permitidos.
     */
    public function add(): Response
    {
        $this->request->allowMethod(['post']);
        $tabela = $this->fetchTable('Alunos');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Erro ao cadastrar o registro.';

        try {
            // Aceita somente os campos deste cadastro.
            $form = array_intersect_key($this->request->getData(), array_flip([
                'nome', 'matricula', 'curso', 'curso_id','situacao', 'emaila', 'emailp', 'anop',
            ]));
            $novoRegistro = $tabela->newEmptyEntity();
            $novoRegistro = $tabela->patchEntity($novoRegistro, $form);

            if ($tabela->save($novoRegistro)) {
                $result = $novoRegistro;
                $message = 'Registro cadastrado com sucesso!';
                $statusCode = 201;
            } else {
                $errors = $novoRegistro->getErrors();
            }
        } catch (Exception $e) {
            $message = 'Erro: ' . $e->getMessage();
            $statusCode = 500;
        }

        return $this->json(['data' => $result, 'message' => $message, 'errors' => $errors], $statusCode);
    }

    /**
     * Altera o registro identificado na rota.
     */
    public function edit(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $tabela = $this->fetchTable('Alunos');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Erro ao alterar o registro.';

        try {
            $registro = $tabela->get((int)$id);
            $form = array_intersect_key($this->request->getData(), array_flip([
                'nome', 'matricula', 'curso', 'curso_id','situacao', 'emaila', 'emailp', 'anop',
            ]));

            $registro = $tabela->patchEntity($registro, $form);

            if ($tabela->save($registro)) {
                $result = $registro;
                $message = 'Registro alterado com sucesso!';
                $statusCode = 200;
            } else {
                $errors = $registro->getErrors();
            }
        } catch (RecordNotFoundException $e) {
            $message = 'Registro não encontrado!';
            $statusCode = 404;
        } catch (Exception $e) {
            $message = 'Erro: ' . $e->getMessage();
            $statusCode = 500;
        }

        return $this->json(['data' => $result, 'message' => $message, 'errors' => $errors], $statusCode);
    }

    /**
     * Desativa o registro sem remover seus vínculos.
     */
    public function delete(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $tabela = $this->fetchTable('Alunos');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Não foi possível alterar a ativação do registro.';

        try {
            $registro = $tabela->get((int)$id);
            $registro['deleted'] = date('Y-m-d H:i:s');

            if ($tabela->save($registro)) {
                $result = $registro;
                $message = 'Registro desativado com sucesso!';
                $statusCode = 200;
            } else {
                $errors = $registro->getErrors();
            }
        } catch (RecordNotFoundException $e) {
            $message = 'Registro não encontrado!';
            $statusCode = 404;
        } catch (Exception $e) {
            $message = 'Erro: ' . $e->getMessage();
            $statusCode = 500;
        }

        return $this->json(['data' => $result, 'message' => $message, 'errors' => $errors], $statusCode);
    }

    /**
     * Reativa o registro, respeitando as regras do cadastro.
     */
    public function active(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $tabela = $this->fetchTable('Alunos');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Não foi possível alterar a ativação do registro.';

        try {
            $registro = $tabela->get((int)$id);
            $registro['deleted'] = null;

            if ($tabela->save($registro)) {
                $result = $registro;
                $message = 'Registro reativado com sucesso!';
                $statusCode = 200;
            } else {
                $errors = $registro->getErrors();
            }
        } catch (RecordNotFoundException $e) {
            $message = 'Registro não encontrado!';
            $statusCode = 404;
        } catch (Exception $e) {
            $message = 'Erro: ' . $e->getMessage();
            $statusCode = 500;
        }

        return $this->json(['data' => $result, 'message' => $message, 'errors' => $errors], $statusCode);
    }
}
