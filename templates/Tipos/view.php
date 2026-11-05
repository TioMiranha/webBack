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
            <?= $this->Html->link(__('Edit Tipo'), ['action' => 'edit', $tipo->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Desativar tipo'), ['action' => 'delete', $tipo->id], ['confirm' => __('Deseja desativar o registro # {0}?', $tipo->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Tipos'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Tipo'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="tipos view content">
            <h3><?= h($tipo->nome) ?></h3>
            <table>
                <tr>
                    <th><?= __('Nome') ?></th>
                    <td><?= h($tipo->nome) ?></td>
                </tr>
                <tr>
                    <th><?= __('Deleted') ?></th>
                    <td><?= h($tipo->deleted) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($tipo->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($tipo->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($tipo->modified) ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Users') ?></h4>
                <?php if (!empty($tipo->users)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Cpf') ?></th>
                            <th><?= __('Password') ?></th>
                            <th><?= __('Nome') ?></th>
                            <th><?= __('Email') ?></th>
                            <th><?= __('Telefone') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Modified') ?></th>
                            <th><?= __('Deleted') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($tipo->users as $user) : ?>
                        <tr>
                            <td><?= h($user->id) ?></td>
                            <td><?= h($user->cpf) ?></td>
                            <td><?= h($user->password) ?></td>
                            <td><?= h($user->nome) ?></td>
                            <td><?= h($user->email) ?></td>
                            <td><?= h($user->telefone) ?></td>
                            <td><?= h($user->created) ?></td>
                            <td><?= h($user->modified) ?></td>
                            <td><?= h($user->deleted) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Users', 'action' => 'view', $user->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Users', 'action' => 'edit', $user->id]) ?>
                                <?= $this->Form->postLink(
                                    __('Desativar'),
                                    ['controller' => 'Users', 'action' => 'delete', $user->id],
                                    [
                                        'method' => 'delete',
                                        'confirm' => __('Deseja desativar o registro # {0}?', $user->id),
                                    ]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
