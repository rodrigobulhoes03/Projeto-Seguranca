<h1>Nova candidatura</h1>
<p><strong><?= escapar($bolsa['designacao']) ?></strong></p>

<?php if (!empty($erro)): ?>
    <p class="erro"><?= escapar($erro) ?></p>
<?php endif; ?>

<?php
$acao = 'candidatura_nova';
$camposOcultos = ['bolsa_id' => $bolsa['id']];
$botao = 'Submeter candidatura';
require __DIR__ . '/formulario.php';
?>

<p><a href="index.php?rota=bolsa&amp;id=<?= (int) $bolsa['id'] ?>">&larr; Voltar à bolsa</a></p>
