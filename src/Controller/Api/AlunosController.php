<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\AlunosImportService;
use Cake\Database\Expression\QueryExpression;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;

class AlunosController extends CrudController
{
    protected string $tableAlias = 'Alunos';

    protected array $fields = ['nome', 'matricula', 'curso', 'situacao', 'emaila', 'emailp', 'anop'];

    protected array $relations = ['Responsaveis.Users'];

    protected array $filters = ['nome', 'matricula'];

    /**
     * Importa uma planilha de alunos usando o ORM e uma transação.
     */
    public function import(): Response
    {
        $this->request->allowMethod(['post']);
        $file = $this->request->getUploadedFile('excel_file')
            ?? $this->request->getUploadedFile('arquivo')
            ?? $this->request->getUploadedFile('file');
        $result = (new AlunosImportService($this->fetchTable('Alunos')))
            ->import($file);

        return $this->json($result, $result['imported'] > 0 ? 201 : 422);
    }

    /**
     * Busca parte da matrícula sem perder dígitos em conversões numéricas.
     */
    public function findByMat(?string $matricula = null): Response
    {
        $this->request->allowMethod(['get']);
        if ($matricula === null || !ctype_digit($matricula) || strlen($matricula) > 19) {
            return $this->json(['message' => 'Informe uma matrícula com até 19 dígitos.'], 422);
        }
        $query = $this->searchQuery();
        $query->where(static function (QueryExpression $expression) use ($matricula): QueryExpression {
            return $expression->like('CAST(Alunos.matricula AS CHAR)', '%' . $matricula . '%');
        });

        return $this->json(['data' => $query->all()]);
    }

    /**
     * Busca por parte do nome, incluindo nomes compostos e acentos.
     */
    public function findByName(?string $name = null): Response
    {
        $this->request->allowMethod(['get']);
        $name = trim($name ?? '');
        if ($name === '' || preg_match("/^[\\p{L}\\p{M} '\\x{2019}-]+$/u", $name) !== 1) {
            return $this->json(['message' => 'Informe um nome válido.'], 422);
        }

        return $this->json(['data' => $this->searchQuery()->where(['Alunos.nome LIKE' => '%' . $name . '%'])->all()]);
    }

    /**
     * Inclui todos os responsáveis e também os alunos ainda sem vínculos.
     *
     * @return \Cake\ORM\Query\SelectQuery
     */
    private function searchQuery(): SelectQuery
    {
        return $this->fetchTable('Alunos')->find()->contain($this->relations)
            ->where(['Alunos.deleted IS' => null])->orderBy(['Alunos.nome' => 'ASC', 'Alunos.id' => 'ASC']);
    }
}
