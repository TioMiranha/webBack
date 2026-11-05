<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Tipo> $tipos
 */
?>
<div class="tipos index content">
    <?= $this->Html->link(__('New Tipo'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Tipos') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('nome') ?></th>
                    <th><?= $this->Paginator->sort('created') ?></th>
                    <th><?= $this->Paginator->sort('modified') ?></th>
                    <th><?= $this->Paginator->sort('deleted') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tipos as $tipo): ?>
                <tr>
                    <td><?= $this->Number->format($tipo->id) ?></td>
                    <td><?= h($tipo->nome) ?></td>
                    <td><?= h($tipo->created) ?></td>
                    <td><?= h($tipo->modified) ?></td>
                    <td><?= h($tipo->deleted) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $tipo->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $tipo->id]) ?>
                        <?php if ($tipo->deleted !== null): ?>
                            <?= $this->Form->postLink('Reativar', ['action' => 'active', $tipo->id]) ?>
                        <?php endif; ?>
                        <?= $this->Form->postLink(
                            __('Desativar'),
                            ['action' => 'delete', $tipo->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Deseja desativar o registro # {0}?', $tipo->id),
                            ]
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
    </div>
</div>
