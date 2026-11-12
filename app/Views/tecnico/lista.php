<h1>Candidaturas</h1>

<form method="get" action="index.php" class="pesquisa">
    <input type="hidden" name="rota" value="tecnico">

    <label>Candidato
        <input type="text" name="nome" maxlength="100" value="<?= escapar($filtros['nome']) ?>">
    </label>

    <label>Bolsa
        <select name="bolsa">
            <option value="0">Todas</option>
            <?php foreach ($bolsas as $b): ?>
                <option value="<?= (int) $b['id'] ?>" <?= $filtros['bolsa'] === (int) $b['id'] ? 'selected' : '' ?>>
                    <?= escapar($b['designacao']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Data de submissão
        <input type="date" name="data" value="<?= escapar($filtros['data']) ?>">
    </label>

    <label>Estado
        <select name="estado">
            <option value="">Todos</option>
            <?php foreach ($estados as $e): ?>
                <option value="<?= escapar($e) ?>" <?= $filtros['estado'] === $e ? 'selected' : '' ?>>
                    <?= escapar(nomeEstado($e)) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <button type="submit">Filtrar</button>
</form>

<?php if (empty($candidaturas)): ?>
    <p>Nenhuma candidatura encontrada.</p>
<?php else: ?>
    <table>
        <tr>
            <th>Candidato</th>
            <th>Bolsa</th>
            <th>Estado</th>
            <th>Submetida em</th>
            <th></th>
        </tr>
        <?php foreach ($candidaturas as $c): ?>
            <tr>
                <td><?= escapar($c['candidato']) ?></td>
                <td><?= escapar($c['designacao']) ?></td>
                <td><?= escapar(nomeEstado($c['estado'])) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($c['data_submissao'])) ?></td>
                <td><a href="index.php?rota=tecnico_candidatura&amp;id=<?= (int) $c['id'] ?>">Detalhes</a></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>