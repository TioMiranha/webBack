<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Tipo $tipo
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Desativar'),
                ['action' => 'delete', $tipo->id],
                ['confirm' => __('Deseja desativar o registro # {0}?', $tipo->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Tipos'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="tipos form content">
            <?= $this->Form->create($tipo) ?>
            <fieldset>
                <legend><?= __('Edit Tipo') ?></legend>
                <?php
                    echo $this->Form->control('nome');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
