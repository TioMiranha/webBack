<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Responsavel $responsavel
 * @var \Cake\Collection\CollectionInterface|string[] $users
 * @var \Cake\Collection\CollectionInterface|string[] $alunos
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Ações') ?></h4>
            <?= $this->Html->link(__('Listar responsáveis'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="responsaveis form content">
            <?= $this->Form->create($responsavel) ?>
            <fieldset>
                <legend><?= __('Vincular responsável ao aluno') ?></legend>
                <?php
                    echo $this->Form->control('users_id', ['label' => 'Usuário responsável', 'options' => $users, 'empty' => 'Selecione o usuário']);
                    echo $this->Form->control('alunos_id', ['label' => 'Aluno', 'options' => $alunos, 'empty' => 'Selecione o aluno']);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Salvar')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
