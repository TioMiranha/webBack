<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;
use Cake\I18n\DateTime;

/**
 * Tipos Controller
 *
 * @property \App\Model\Table\TiposTable $Tipos
 */
class TiposController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Tipos->find();
        $tipos = $this->paginate($query);

        $this->set(compact('tipos'));
    }

    /**
     * View method
     *
     * @param string|null $id Tipo id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $tipo = $this->Tipos->get($id, contain: ['Users']);
        $this->set(compact('tipo'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $tipo = $this->Tipos->newEmptyEntity();
        if ($this->request->is('post')) {
            $tipo = $this->Tipos->patchEntity($tipo, $this->request->getData());
            if ($this->Tipos->save($tipo)) {
                $this->Flash->success(__('The tipo has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The tipo could not be saved. Please, try again.'));
        }
        $this->set(compact('tipo'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Tipo id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $tipo = $this->Tipos->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $tipo = $this->Tipos->patchEntity($tipo, $this->request->getData());
            if ($this->Tipos->save($tipo)) {
                $this->Flash->success(__('The tipo has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The tipo could not be saved. Please, try again.'));
        }
        $this->set(compact('tipo'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Tipo id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $tipo = $this->Tipos->get($id);
        $tipo->deleted = DateTime::now()->format('Y-m-d H:i:s');
        if ($this->Tipos->save($tipo)) {
            $this->Flash->success(__('Registro desativado com sucesso.'));
        } else {
            $this->Flash->error(__('Não foi possível desativar o registro.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Reativa um registro sem alterar os vínculos existentes.
     */
    public function active(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $tipo = $this->Tipos->get($id);
        $tipo->deleted = null;
        if ($this->Tipos->save($tipo)) {
            $this->Flash->success(__('Registro reativado com sucesso.'));
        } else {
            $this->Flash->error(__('Não foi possível reativar. Verifique se os registros relacionados estão ativos.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
