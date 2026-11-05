<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit User'), ['action' => 'edit', $user->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Desativar usuário'), ['action' => 'delete', $user->id], ['confirm' => __('Deseja desativar o registro # {0}?', $user->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Users'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New User'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="users view content">
            <h3><?= h($user->nome) ?></h3>
            <table>
                <tr>
                    <th><?= __('Nome') ?></th>
                    <td><?= h($user->nome) ?></td>
                </tr>
                <tr>
                    <th><?= __('Email') ?></th>
                    <td><?= h($user->email) ?></td>
                </tr>
                <tr>
                    <th><?= __('Tipo') ?></th>
                    <td><?= $user->hasValue('tipo') ? $this->Html->link($user->tipo->nome, ['controller' => 'Tipos', 'action' => 'view', $user->tipo->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Deleted') ?></th>
                    <td><?= h($user->deleted) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($user->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Cpf') ?></th>
                    <td><?= $this->Number->format($user->cpf) ?></td>
                </tr>
                <tr>
                    <th><?= __('Telefone') ?></th>
                    <td><?= $this->Number->format($user->telefone) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($user->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($user->modified) ?></td>
                </tr>
            </table>
            <div class="related">
                <h4>Alunos vinculados como responsável</h4>
                <?php foreach ($user->responsaveis as $responsavel): ?>
                    <p>
                        <?= $this->Html->link($responsavel->aluno->nome, ['controller' => 'Alunos', 'action' => 'view', $responsavel->alunos_id]) ?>
                        — <?= $responsavel->deleted === null ? 'Ativo' : 'Desativado' ?>
                        <?= $this->Html->link('Ver vínculo', ['controller' => 'Responsaveis', 'action' => 'view', $responsavel->id]) ?>
                    </p>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
