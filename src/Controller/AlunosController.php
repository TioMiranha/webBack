<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\AlunosImportService;
use Cake\Http\Response;
use Cake\I18n\DateTime;

/**
 * Alunos Controller
 *
 * @property \App\Model\Table\AlunosTable $Alunos
 */
class AlunosController extends AppController
{
    /**
     * Recebe a planilha pelo formulário de importação.
     *
     * @return \Cake\Http\Response|null
     */
    public function importar(): ?Response
    {
        $this->request->allowMethod(['get', 'post']);
        $importErrors = [];
        if ($this->request->is('post')) {
            $result = (new AlunosImportService($this->Alunos))
                ->import($this->request->getUploadedFile('excel_file'));
            if ($result['imported'] > 0) {
                $this->Flash->success(__('{0} alunos importados com sucesso.', $result['imported']));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error($result['message']);
            $importErrors = $result['errors'];
            $this->response = $this->response->withStatus(422);
        }
        $this->set(compact('importErrors'));

        return null;
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Alunos->find();
        $alunos = $this->paginate($query);

        $this->set(compact('alunos'));
    }

    /**
     * View method
     *
     * @param string|null $id Aluno id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $aluno = $this->Alunos->get($id, contain: ['Responsaveis.Users']);
        $this->set(compact('aluno'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $aluno = $this->Alunos->newEmptyEntity();
        if ($this->request->is('post')) {
            $aluno = $this->Alunos->patchEntity($aluno, $this->request->getData());
            if ($this->Alunos->save($aluno)) {
                $this->Flash->success(__('The aluno has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The aluno could not be saved. Please, try again.'));
        }
        $this->set(compact('aluno'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Aluno id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $aluno = $this->Alunos->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $aluno = $this->Alunos->patchEntity($aluno, $this->request->getData());
            if ($this->Alunos->save($aluno)) {
                $this->Flash->success(__('The aluno has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The aluno could not be saved. Please, try again.'));
        }
        $this->set(compact('aluno'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Aluno id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $aluno = $this->Alunos->get($id);
        $aluno->deleted = DateTime::now()->format('Y-m-d H:i:s');
        if ($this->Alunos->save($aluno)) {
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
        $aluno = $this->Alunos->get($id);
        $aluno->deleted = null;
        if ($this->Alunos->save($aluno)) {
            $this->Flash->success(__('Registro reativado com sucesso.'));
        } else {
            $this->Flash->error(__('Não foi possível reativar. Verifique se os registros relacionados estão ativos.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
