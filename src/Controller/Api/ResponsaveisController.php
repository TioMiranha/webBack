<?php
declare(strict_types=1);

namespace App\Controller\Api;

class ResponsaveisController extends CrudController
{
    protected string $tableAlias = 'Responsaveis';

    protected array $fields = ['users_id', 'alunos_id'];

    protected array $relations = ['Users', 'Alunos'];

    protected array $filters = ['users_id', 'alunos_id'];
}
