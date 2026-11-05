<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Responsavel> $responsaveis
 */
?>
<div class="responsaveis index content">
    <?= $this->Html->link(__('Novo responsável'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Responsáveis') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('users_id', 'Responsável') ?></th>
                    <th><?= $this->Paginator->sort('alunos_id', 'Aluno') ?></th>
                    <th><?= $this->Paginator->sort('created') ?></th>
                    <th><?= $this->Paginator->sort('modified') ?></th>
                    <th><?= $this->Paginator->sort('deleted') ?></th>
                    <th class="actions"><?= __('Ações') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($responsaveis as $responsavel): ?>
                <tr>
                    <td><?= $this->Number->format($responsavel->id) ?></td>
                    <td><?= $responsavel->hasValue('user') ? $this->Html->link($responsavel->user->nome, ['controller' => 'Users', 'action' => 'view', $responsavel->user->id]) : '' ?></td>
                    <td><?= $responsavel->hasValue('aluno') ? $this->Html->link($responsavel->aluno->nome, ['controller' => 'Alunos', 'action' => 'view', $responsavel->aluno->id]) : '' ?></td>
                    <td><?= h($responsavel->created) ?></td>
                    <td><?= h($responsavel->modified) ?></td>
                    <td><?= h($responsavel->deleted) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('Visualizar'), ['action' => 'view', $responsavel->id]) ?>
                        <?= $this->Html->link(__('Editar'), ['action' => 'edit', $responsavel->id]) ?>
                        <?php if ($responsavel->deleted !== null): ?>
                            <?= $this->Form->postLink('Reativar', ['action' => 'active', $responsavel->id]) ?>
                        <?php endif; ?>
                        <?= $this->Form->postLink(
                            __('Desativar'),
                            ['action' => 'delete', $responsavel->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Deseja desativar o vínculo # {0}?', $responsavel->id),
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
