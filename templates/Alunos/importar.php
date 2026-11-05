<?php
/**
 * @var \App\View\AppView $this
 * @var array<int, array<string, array<string, string>>> $importErrors
 */
?>
<div class="alunos form content">
    <h3>Importar alunos</h3>
    <p>Selecione um arquivo XLS ou XLSX com os cabeçalhos na primeira linha:</p>
    <p>Nome, Matrícula, Curso, Situação, E-mail Acadêmico, E-mail Pessoal e Ano/Periodo Letivo.</p>
    <p>Os e-mails podem ficar vazios. Se houver uma linha inválida, nenhum aluno será salvo.</p>
    <?= $this->Form->create(null, ['type' => 'file']) ?>
    <?= $this->Form->control('excel_file', [
        'type' => 'file',
        'label' => 'Planilha de alunos',
        'accept' => '.xls,.xlsx',
        'required' => true,
    ]) ?>
    <?= $this->Form->button('Importar') ?>
    <?= $this->Form->end() ?>
    <?php if ($importErrors) : ?>
        <h4>Erros na planilha</h4>
        <ul>
            <?php foreach ($importErrors as $line => $fields) : ?>
                <?php foreach ($fields as $field => $messages) : ?>
                    <li><?= h('Linha ' . $line . ' — ' . $field . ': ' . implode('; ', $messages)) ?></li>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?= $this->Html->link('Voltar para alunos', ['action' => 'index']) ?>
</div>
