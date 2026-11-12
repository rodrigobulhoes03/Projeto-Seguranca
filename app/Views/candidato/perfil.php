<h1>O meu perfil</h1>

<?php if (!empty($mensagem)): ?>
    <p class="<?= !empty($guardado) ? 'ok' : 'erro' ?>"><?= escapar($mensagem) ?></p>
<?php endif; ?>

<form method="post" action="index.php?rota=perfil">
    <label>Username
        <input type="text" value="<?= escapar($dados['username']) ?>" disabled>
    </label>

    <label>Nome
        <input type="text" name="nome" maxlength="100" value="<?= escapar($dados['nome']) ?>" required>
    </label>

    <label>Email
        <input type="email" name="email" maxlength="100" value="<?= escapar($dados['email']) ?>" required>
    </label>

    <label>Morada
        <input type="text" name="morada" maxlength="200" value="<?= escapar($dados['morada']) ?>">
    </label>

    <label>Contacto telefónico
        <input type="text" name="telefone" maxlength="20" value="<?= escapar($dados['telefone']) ?>">
    </label>

    <label>NIF
        <input type="text" name="nif" maxlength="9" value="<?= escapar($dados['nif']) ?>">
    </label>

    <button type="submit">Guardar</button>
</form>

<p><a href="index.php?rota=password">Alterar password</a></p>
