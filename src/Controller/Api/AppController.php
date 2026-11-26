<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController as BaseController;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
use InvalidArgumentException;

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

    /**
     * Converte um nome de referência em ID sem escolher entre homônimos.
     */
    protected function resolverReferencia(
        array $form,
        string $alias,
        string $campoId,
        string $campoNome,
        array $apelidos = [],
    ): array {
        $nome = null;
        foreach (array_merge([$campoNome], $apelidos) as $campo) {
            if (!array_key_exists($campo, $form)) {
                continue;
            }
            if (!is_string($form[$campo]) || trim($form[$campo]) === '') {
                throw new InvalidArgumentException('Informe um nome válido em ' . $campo . '.');
            }
            $valor = trim($form[$campo]);
            if ($nome !== null && $nome !== $valor) {
                throw new InvalidArgumentException('Os nomes informados em ' . $campoNome . ' são diferentes.');
            }
            $nome = $valor;
            unset($form[$campo]);
        }
        if ($nome === null) {
            return $form;
        }
        $conditions = ['nome' => $nome, 'deleted IS' => null];
        if (array_key_exists($campoId, $form)) {
            if (
                !is_scalar($form[$campoId]) || is_bool($form[$campoId])
                || !ctype_digit((string)$form[$campoId]) || (int)$form[$campoId] < 1
            ) {
                throw new InvalidArgumentException('Informe um ID válido em ' . $campoId . '.');
            }
            $conditions['id'] = (int)$form[$campoId];
        }
        $registros = $this->fetchTable($alias)->find()->select(['id'])->where($conditions)->limit(2)->all()->toList();
        if (empty($registros)) {
            throw new InvalidArgumentException(
                'Referência inexistente, desativada ou incompatível: ' . $campoNome . '.',
            );
        }
        if (count($registros) > 1) {
            throw new InvalidArgumentException('Nome repetido em ' . $campoNome . '. Informe ' . $campoId . '.');
        }
        $form[$campoId] = (int)$registros[0]->id;

        return $form;
    }

    /**
     * Localiza um registro pelo ID da rota ou pelas rotas explícitas por nome.
     */
    protected function localizarRegistro(string $alias, ?string $id): EntityInterface
    {
        $tabela = $this->fetchTable($alias);
        if ($this->request->getParam('porNome')) {
            if ($id === null || trim($id) === '') {
                throw new InvalidArgumentException('Informe o nome na rota.');
            }
            $registros = $tabela->find()->where(['nome' => trim($id)])->limit(2)->all()->toList();
            if (empty($registros)) {
                throw new RecordNotFoundException('Registro não encontrado!');
            }
            if (count($registros) > 1) {
                throw new InvalidArgumentException('Nome repetido. Informe o ID na rota.');
            }

            return $registros[0];
        }
        if ($id === null || !ctype_digit($id) || (int)$id < 1) {
            throw new InvalidArgumentException('Informe um ID válido na rota.');
        }

        return $tabela->get((int)$id);
    }
}
