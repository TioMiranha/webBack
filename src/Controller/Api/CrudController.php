<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\Datasource\EntityInterface;
use Cake\Http\Response;
use Cake\I18n\DateTime;

abstract class CrudController extends AppController
{
    protected string $tableAlias;

    protected array $fields = [];

    protected array $relations = [];

    protected array $filters = [];

    /**
     * Lista registros ativos, incluindo as relações.
     */
    public function list(?string $id = null): Response
    {
        $this->request->allowMethod(['get', 'post']);
        if ($id !== null) {
            return $this->findById($id);
        }
        $table = $this->fetchTable($this->tableAlias);
        $query = $table->find()->contain($this->relations)
            ->where([$this->tableAlias . '.deleted IS' => null]);
        $data = $this->request->getData() + $this->request->getQueryParams();
        foreach ($this->filters as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $query->where([$this->tableAlias . '.' . $field => $data[$field]]);
            }
        }

        return $this->json(['data' => $query->orderBy([$this->tableAlias . '.id' => 'ASC'])->all()]);
    }

    /**
     * Busca um registro e suas relações pelo ID da rota.
     */
    public function findById(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        $record = $this->fetchTable($this->tableAlias)->get($id, contain: $this->relations);

        return $this->json(['data' => $record]);
    }

    /**
     * Cadastra um registro.
     */
    public function add(): Response
    {
        $this->request->allowMethod(['post']);
        $record = $this->fetchTable($this->tableAlias)->newEntity($this->input());

        return $this->saveRecord($record, 201);
    }

    /**
     * Atualiza os campos permitidos de um registro.
     */
    public function edit(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $table = $this->fetchTable($this->tableAlias);
        $record = $table->patchEntity($table->get($id), $this->input());

        return $this->saveRecord($record);
    }

    /**
     * Desativa sem remover o registro e seus vínculos.
     */
    public function delete(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $record = $this->fetchTable($this->tableAlias)->get($id);
        $record->set('deleted', DateTime::now()->format('Y-m-d H:i:s'));

        return $this->saveRecord($record);
    }

    /**
     * Reativa um registro existente.
     */
    public function active(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $record = $this->fetchTable($this->tableAlias)->get($id);
        $record->set('deleted', null);

        return $this->saveRecord($record);
    }

    /**
     * Seleciona os campos aceitos pelo cadastro.
     */
    protected function input(): array
    {
        $data = array_intersect_key($this->request->getData(), array_flip($this->fields));
        if ($this->request->getParam('action') !== 'add' && isset($data['password']) && $data['password'] === '') {
            unset($data['password']);
        }

        return $data;
    }

    /**
     * Salva o registro e informa erros de validação em JSON.
     */
    protected function saveRecord(EntityInterface $record, int $status = 200): Response
    {
        if (!$this->fetchTable($this->tableAlias)->save($record)) {
            return $this->json([
                'message' => 'Não foi possível salvar o registro.',
                'errors' => $record->getErrors(),
            ], 422);
        }

        return $this->json(['data' => $record, 'message' => 'Registro salvo com sucesso.'], $status);
    }
}
