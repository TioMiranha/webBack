<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;
use Cake\I18n\DateTime;

/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 */
class UsersController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Users->find()
            ->contain(['Tipos']);
        $users = $this->paginate($query);

        $this->set(compact('users'));
    }

    /**
     * View method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $user = $this->Users->get($id, contain: ['Tipos', 'Responsaveis.Alunos']);
        $this->set(compact('user'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $user = $this->Users->newEmptyEntity();
        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $user = $this->Users->patchEntity($user, $data);
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user could not be saved. Please, try again.'));
        }
        $tipos = $this->Users->Tipos->find('list', limit: 200)->all();
        $this->set(compact('user', 'tipos'));
    }

    /**
     * Edit method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $user = $this->Users->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            if (isset($data['password']) && $data['password'] === '') {
                unset($data['password']);
            }
            $user = $this->Users->patchEntity($user, $data);
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user could not be saved. Please, try again.'));
        }
        $tipos = $this->Users->Tipos->find('list', limit: 200)->all();
        $this->set(compact('user', 'tipos'));
    }

    /**
     * Delete method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $user = $this->Users->get($id);
        $user->deleted = DateTime::now()->format('Y-m-d H:i:s');
        if ($this->Users->save($user)) {
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
        $user = $this->Users->get($id);
        $user->deleted = null;
        if ($this->Users->save($user)) {
            $this->Flash->success(__('Registro reativado com sucesso.'));
        } else {
            $this->Flash->error(__('Não foi possível reativar. Verifique se os registros relacionados estão ativos.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
