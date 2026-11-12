<h1>As minhas candidaturas</h1>

<?php if (!empty($erro)): ?>
    <p class="erro"><?= escapar($erro) ?></p>
<?php endif; ?>

<?php if (empty($candidaturas)): ?>
    <p>Ainda não submeteu nenhuma candidatura. <a href="index.php?rota=bolsas">Ver bolsas</a></p>
<?php else: ?>
    <table>
        <tr>
            <th>Bolsa</th>
            <th>Estado</th>
            <th>Submetida em</th>
            <th></th>
        </tr>
        <?php foreach ($candidaturas as $c): ?>
            <tr>
                <td><?= escapar($c['designacao']) ?></td>
                <td><?= escapar(nomeEstado($c['estado'])) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($c['data_submissao'])) ?></td>
                <td><a href="index.php?rota=candidatura&amp;id=<?= (int) $c['id'] ?>">Detalhes</a></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>