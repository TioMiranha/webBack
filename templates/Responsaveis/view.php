<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Responsavel $responsavel
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Ações') ?></h4>
            <?= $this->Html->link(__('Editar vínculo'), ['action' => 'edit', $responsavel->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Desativar vínculo'), ['action' => 'delete', $responsavel->id], ['confirm' => __('Deseja desativar o vínculo # {0}?', $responsavel->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('Listar responsáveis'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('Novo responsável'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="responsaveis view content">
            <h3><?= h($responsavel->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('Usuário responsável') ?></th>
                    <td><?= $responsavel->hasValue('user') ? $this->Html->link($responsavel->user->nome, ['controller' => 'Users', 'action' => 'view', $responsavel->user->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Aluno') ?></th>
                    <td><?= $responsavel->hasValue('aluno') ? $this->Html->link($responsavel->aluno->nome, ['controller' => 'Alunos', 'action' => 'view', $responsavel->aluno->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($responsavel->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Criado em') ?></th>
                    <td><?= h($responsavel->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Atualizado em') ?></th>
                    <td><?= h($responsavel->modified) ?></td>
                </tr>
                <tr>
                    <th><?= __('Desativado em') ?></th>
                    <td><?= h($responsavel->deleted) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
