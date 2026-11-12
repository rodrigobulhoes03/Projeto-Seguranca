<?php // Campos comuns à nova candidatura e à edição. Usa $dados e $acao (a rota do formulário) ?>
<form method="post" action="index.php?rota=<?= escapar($acao) ?>">
    <?php foreach ($camposOcultos as $nome => $valor): ?>
        <input type="hidden" name="<?= escapar($nome) ?>" value="<?= (int) $valor ?>">
    <?php endforeach; ?>

    <label>Instituição de ensino
        <input type="text" name="instituicao" maxlength="150" value="<?= escapar($dados['instituicao']) ?>" required>
    </label>

    <label>Curso
        <input type="text" name="curso" maxlength="150" value="<?= escapar($dados['curso']) ?>" required>
    </label>

    <label>Rendimento do agregado familiar (€)
        <input type="text" name="rendimento" inputmode="decimal" maxlength="11" value="<?= escapar($dados['rendimento']) ?>" required>
    </label>

    <label>IBAN
        <input type="text" name="iban" maxlength="34" placeholder="PT50 0000 0000 0000 0000 0000 0" value="<?= escapar($dados['iban']) ?>" required>
    </label>

    <button type="submit"><?= escapar($botao) ?></button>
</form>
