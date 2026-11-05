<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Aluno> $alunos
 */
?>
<div class="alunos index content">
    <?= $this->Html->link(__('New Aluno'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Alunos') ?></h3>
    <?= $this->Html->link('Importar planilha', ['action' => 'importar'], ['class' => 'button']) ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('nome') ?></th>
                    <th><?= $this->Paginator->sort('matricula') ?></th>
                    <th><?= $this->Paginator->sort('curso') ?></th>
                    <th><?= $this->Paginator->sort('situacao') ?></th>
                    <th><?= $this->Paginator->sort('emaila') ?></th>
                    <th><?= $this->Paginator->sort('emailp') ?></th>
                    <th><?= $this->Paginator->sort('anop') ?></th>
                    <th><?= $this->Paginator->sort('created') ?></th>
                    <th><?= $this->Paginator->sort('modified') ?></th>
                    <th><?= $this->Paginator->sort('deleted') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alunos as $aluno): ?>
                <tr>
                    <td><?= $this->Number->format($aluno->id) ?></td>
                    <td><?= h($aluno->nome) ?></td>
                    <td><?= $this->Number->format($aluno->matricula) ?></td>
                    <td><?= h($aluno->curso) ?></td>
                    <td><?= h($aluno->situacao) ?></td>
                    <td><?= h($aluno->emaila) ?></td>
                    <td><?= h($aluno->emailp) ?></td>
                    <td><?= h($aluno->anop) ?></td>
                    <td><?= h($aluno->created) ?></td>
                    <td><?= h($aluno->modified) ?></td>
                    <td><?= h($aluno->deleted) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $aluno->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $aluno->id]) ?>
                        <?php if ($aluno->deleted !== null): ?>
                            <?= $this->Form->postLink('Reativar', ['action' => 'active', $aluno->id]) ?>
                        <?php endif; ?>
                        <?= $this->Form->postLink(
                            __('Desativar'),
                            ['action' => 'delete', $aluno->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Deseja desativar o registro # {0}?', $aluno->id),
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
