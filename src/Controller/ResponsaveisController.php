<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;
use Cake\I18n\DateTime;

/**
 * Responsaveis Controller
 *
 * @property \App\Model\Table\ResponsaveisTable $Responsaveis
 */
class ResponsaveisController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Responsaveis->find()
            ->contain(['Users', 'Alunos']);
        $responsaveis = $this->paginate($query);

        $this->set(compact('responsaveis'));
    }

    /**
     * View method
     *
     * @param string|null $id Responsavel id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $responsavel = $this->Responsaveis->get($id, contain: ['Users', 'Alunos']);
        $this->set(compact('responsavel'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $responsavel = $this->Responsaveis->newEmptyEntity();
        if ($this->request->is('post')) {
            $responsavel = $this->Responsaveis->patchEntity($responsavel, $this->request->getData());
            if ($this->Responsaveis->save($responsavel)) {
                $this->Flash->success(__('Vínculo do responsável salvo com sucesso.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('Não foi possível salvar o vínculo. Verifique os dados.'));
        }
        $users = $this->Responsaveis->Users->find('list')->where(['deleted IS' => null])->all();
        $alunos = $this->Responsaveis->Alunos->find('list')->where(['deleted IS' => null])->all();
        $this->set(compact('responsavel', 'users', 'alunos'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Responsavel id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $responsavel = $this->Responsaveis->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $responsavel = $this->Responsaveis->patchEntity($responsavel, $this->request->getData());
            if ($this->Responsaveis->save($responsavel)) {
                $this->Flash->success(__('Vínculo do responsável salvo com sucesso.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('Não foi possível salvar o vínculo. Verifique os dados.'));
        }
        $users = $this->Responsaveis->Users->find('list')->where(['deleted IS' => null])->all();
        $alunos = $this->Responsaveis->Alunos->find('list')->where(['deleted IS' => null])->all();
        $this->set(compact('responsavel', 'users', 'alunos'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Responsavel id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $responsavel = $this->Responsaveis->get($id);
        $responsavel->deleted = DateTime::now()->format('Y-m-d H:i:s');
        if ($this->Responsaveis->save($responsavel)) {
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
        $responsavel = $this->Responsaveis->get($id);
        $responsavel->deleted = null;
        if ($this->Responsaveis->save($responsavel)) {
            $this->Flash->success(__('Registro reativado com sucesso.'));
        } else {
            $this->Flash->error(__('Não foi possível reativar. Verifique se os registros relacionados estão ativos.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
