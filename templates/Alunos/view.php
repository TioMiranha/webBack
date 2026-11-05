<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Aluno $aluno
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Aluno'), ['action' => 'edit', $aluno->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Desativar aluno'), ['action' => 'delete', $aluno->id], ['confirm' => __('Deseja desativar o registro # {0}?', $aluno->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Alunos'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Aluno'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="alunos view content">
            <h3><?= h($aluno->nome) ?></h3>
            <table>
                <tr>
                    <th><?= __('Nome') ?></th>
                    <td><?= h($aluno->nome) ?></td>
                </tr>
                <tr>
                    <th><?= __('Curso') ?></th>
                    <td><?= h($aluno->curso) ?></td>
                </tr>
                <tr>
                    <th><?= __('Situacao') ?></th>
                    <td><?= h($aluno->situacao) ?></td>
                </tr>
                <tr>
                    <th><?= __('Emaila') ?></th>
                    <td><?= h($aluno->emaila) ?></td>
                </tr>
                <tr>
                    <th><?= __('Emailp') ?></th>
                    <td><?= h($aluno->emailp) ?></td>
                </tr>
                <tr>
                    <th><?= __('Anop') ?></th>
                    <td><?= h($aluno->anop) ?></td>
                </tr>
                <tr>
                    <th><?= __('Deleted') ?></th>
                    <td><?= h($aluno->deleted) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($aluno->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Matricula') ?></th>
                    <td><?= $this->Number->format($aluno->matricula) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($aluno->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($aluno->modified) ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Responsaveis') ?></h4>
                <?php if (!empty($aluno->responsaveis)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Responsável') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Modified') ?></th>
                            <th><?= __('Deleted') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($aluno->responsaveis as $responsavel) : ?>
                        <tr>
                            <td><?= h($responsavel->id) ?></td>
                            <td><?= $this->Html->link($responsavel->user->nome, ['controller' => 'Users', 'action' => 'view', $responsavel->users_id]) ?></td>
                            <td><?= h($responsavel->created) ?></td>
                            <td><?= h($responsavel->modified) ?></td>
                            <td><?= h($responsavel->deleted) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Responsaveis', 'action' => 'view', $responsavel->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Responsaveis', 'action' => 'edit', $responsavel->id]) ?>
                                <?= $this->Form->postLink(
                                    __('Desativar'),
                                    ['controller' => 'Responsaveis', 'action' => 'delete', $responsavel->id],
                                    [
                                        'method' => 'delete',
                                        'confirm' => __('Deseja desativar o registro # {0}?', $responsavel->id),
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
