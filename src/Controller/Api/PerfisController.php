<?php
declare(strict_types=1);

namespace App\Controller\Api;

class PerfisController extends CrudController
{
    protected string $tableAlias = 'Tipos';

    protected array $fields = ['nome'];

    protected array $filters = ['nome'];
}
