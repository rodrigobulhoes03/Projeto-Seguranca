<h1>Alterar password</h1>

<?php if (!empty($mensagem)): ?>
    <p class="<?= !empty($guardado) ? 'ok' : 'erro' ?>"><?= escapar($mensagem) ?></p>
<?php endif; ?>

<form method="post" action="index.php?rota=password">
    <label>Password atual
        <input type="password" name="password_atual" required>
    </label>

    <label>Nova password (mínimo 8 caracteres)
        <input type="password" name="password_nova" minlength="8" required>
    </label>

    <label>Confirmar nova password
        <input type="password" name="password_confirmar" minlength="8" required>
    </label>

    <button type="submit">Guardar</button>
</form>

<p><a href="index.php?rota=perfil">&larr; Voltar ao perfil</a></p>
