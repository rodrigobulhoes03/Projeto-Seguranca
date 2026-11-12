<h1>Editar candidatura</h1>
<p><strong><?= escapar($candidatura['designacao']) ?></strong></p>

<?php if (!empty($erro)): ?>
    <p class="erro"><?= escapar($erro) ?></p>
<?php endif; ?>

<?php
$acao = 'candidatura_editar';
$camposOcultos = ['id' => $candidatura['id']];
$botao = 'Guardar alterações';
require __DIR__ . '/formulario.php';
?>

<p><a href="index.php?rota=candidatura&amp;id=<?= (int) $candidatura['id'] ?>">&larr; Voltar à candidatura</a></p>
