<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 * @var string[]|\Cake\Collection\CollectionInterface $tipos
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Desativar'),
                ['action' => 'delete', $user->id],
                ['confirm' => __('Deseja desativar o registro # {0}?', $user->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Users'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="users form content">
            <?= $this->Form->create($user) ?>
            <fieldset>
                <legend><?= __('Edit User') ?></legend>
                <?php
                    echo $this->Form->control('cpf');
                    echo $this->Form->control('password', ['value' => '', 'required' => false, 'label' => 'Senha (deixe vazia para manter)']);
                    echo $this->Form->control('nome');
                    echo $this->Form->control('email');
                    echo $this->Form->control('telefone');
                    echo $this->Form->control('tipo_id', ['options' => $tipos]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
