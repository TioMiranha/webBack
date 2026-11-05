<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Mailer\Mailer;
use Exception;

class HelloController extends AppController
{
    /**
     * Exibe o exercício de sessão e envio de email.
     */
    public function index(): void
    {
        // Ação vazia por enquanto; o CakePHP buscará automaticamente a view correspondente.

        /*debug(var:'bug');
        debug(var:$_POST);

        $nome=$_POST['nome'];
        $dtNasc=$_POST['dtNasc'];
        $senha=$_POST['senha'];

        debug($nome);
        debug($senha);
        debug($dtNasc);
        //exit;*/

      /*  $num=$_POST['numero'];
        $num2=$_POST['numero2'];

        if ($num>$num2) {
            $res=$num;
        }


        $this->set(compact('res'));*/

        $session = $this->request->getSession();
        $itens = $session->read('itens') ?? [];

        if ($this->request->is('POST')) {
            $itens[] = $this->request->getData();
            $session->write('itens', $itens);
            $codigo = random_int(100000, 999999);
            $this->enviarEmail('diogo.santos@estudante.ifmt.edu.br', $codigo);
        }

        $this->set('itens', $itens);
    }

    /**
     * Envia o código e informa falhas no transporte de email.
     */
    protected function enviarEmail(string $destinatario, int $codigo): void
    {
        $mailer = new Mailer('default');

        try {
            $mailer
                ->setTo($destinatario)
                ->setSubject('Teste de envio de email')
                ->deliver('Olá! Este email de teste foi enviado com sucesso através do Cakephp 5. ' . $codigo);
            $this->Flash->success('Email enviado com sucesso');
        } catch (Exception $e) {
            $this->Flash->error('Erro ao enviar o Email' . $e->getMessage());
        }
    }
}
