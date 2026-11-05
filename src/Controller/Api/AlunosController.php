<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\AlunosImportService;
use Cake\Http\Response;

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
        $result = (new AlunosImportService($this->fetchTable('Alunos')))
            ->import($this->request->getUploadedFile('excel_file'));

        return $this->json($result, $result['imported'] > 0 ? 201 : 422);
    }
}
