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
            <?= $this->Html->link(__('List Tipos'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="tipos form content">
            <?= $this->Form->create($tipo) ?>
            <fieldset>
                <legend><?= __('Add Tipo') ?></legend>
                <?php
                    echo $this->Form->control('nome');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
