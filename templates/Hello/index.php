<!--<form method="POST"   >
    Numero: <input type="number" id="numero" name="numero">
    Numero2: <input type="number" id="numero2" name="numero2">
    Nome: <input type="text" id="nome" name="nome">
    Data nascimento: <input type="date" id="dtNasc" name="dtNasc">
    passwd: <input type="password" id="senha" name="senha">
    <button type="submit">Enviar</button>
    <button type="reset">Limpar</button>
    <button type="submit">Enviar</button>
    <br>
    Resultado: <input type="number" id="resultado" name="resultado" value="<?php //if(!empty($res)) echo $res;?>">
</form>


<div style="text-align: center; margin-top: 50px; font-family: sans-serif;">
    <h1 style="color: #d33c43;">Hello World! 🚀</h1>
    <p>Meu ambiente CakePHP 5 está rodando perfeitamente no Ubuntu!</p>
</div>

-->


<?= $this->Form->create() ?>
    Produto: <input type="text" id="produto" name="produto">
    Quantidade <input type="number" id="quantidade" name="quantidade" min="0" step="0.01" inputmode="decimal" placeholder="0.00">
    Valor <input type="number" id="valor" name="valor" min="0" step="0.01" inputmode="decimal" placeholder="0.00">

    <button type="submit">
        Inserir
    </button>

<?= $this->Form->end() ?>

<table>

    <tr>
        <th>#</th>
        <th>Produto</th>
        <th>Quantidade</th>
        <th>Valor</th>
    </tr>


    <?php if(!empty($itens)):?>
        <?php $total = 0.0 ?>
        <?php $x=1;?>
        <?php foreach ($itens as $item):?>
            <tr>
                <td><?=$x?></td>
                <td><?=$item['produto']?></td>
                <td><?=$item['quantidade']?></td>
                <td><?=$item['valor']?></td>
            </tr>
            <?php $total += ($item['valor'] * $item['quantidade']);?>
            <?php $x++;?>

        <?php endforeach;?>
        <tr>
            <td>Valor R$ <?=$total;?></td>
        </tr>

    <?php endif; ?>

</table>


