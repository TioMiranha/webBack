<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController as BaseController;
use Cake\Http\Response;

class AppController extends BaseController
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->viewBuilder()->setClassName('Json');
    }

    /**
     * Retorna uma resposta JSON com o status HTTP informado.
     */
    protected function json(mixed $data, int $status = 200): Response
    {
        return $this->response->withStatus($status)->withType('application/json')
            ->withStringBody(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }
}
