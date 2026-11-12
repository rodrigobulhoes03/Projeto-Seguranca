<h1>Candidatura: <?= escapar($candidatura['designacao']) ?></h1>

<p><a href="index.php?rota=tecnico">&larr; Voltar à lista</a></p>

<?php if (!empty($mensagem)): ?>
    <p class="ok"><?= escapar($mensagem) ?></p>
<?php endif; ?>

<h2>Candidato</h2>
<table>
    <tr><th>Nome</th><td><?= escapar($candidatura['nome']) ?></td></tr>
    <tr><th>Email</th><td><?= escapar($candidatura['email']) ?></td></tr>
    <tr><th>Morada</th><td><?= escapar($candidatura['morada'] ?? '—') ?></td></tr>
    <tr><th>Telefone</th><td><?= escapar($candidatura['telefone'] ?? '—') ?></td></tr>
    <tr><th>NIF</th><td><?= escapar($candidatura['nif'] ?? '—') ?></td></tr>
</table>

<h2>Dados da candidatura</h2>
<table>
    <tr><th>Instituição</th><td><?= escapar($candidatura['instituicao']) ?></td></tr>
    <tr><th>Curso</th><td><?= escapar($candidatura['curso']) ?></td></tr>
    <tr><th>Rendimento do agregado</th><td><?= number_format((float) $candidatura['rendimento_agregado'], 2, ',', ' ') ?> €</td></tr>
    <tr><th>IBAN</th><td><?= escapar($candidatura['iban']) ?></td></tr>
    <tr><th>Estado</th><td><?= escapar(nomeEstado($candidatura['estado'])) ?></td></tr>
    <tr><th>Submetida em</th><td><?= date('d/m/Y H:i', strtotime($candidatura['data_submissao'])) ?></td></tr>
</table>

    <h2>Alterar estado</h2>
    <?php if (empty($transicoes)): ?>
        <p>Esta candidatura está num estado final.</p>
    <?php else: ?>
        <form method="post" action="index.php?rota=tecnico_estado">
            <input type="hidden" name="id" value="<?= (int) $candidatura['id'] ?>">
            <select name="estado">
                <?php foreach ($transicoes as $e): ?>
                    <option value="<?= escapar($e) ?>"><?= escapar(nomeEstado($e)) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Atualizar</button>
        </form>
    <?php endif; ?>

    <h2>Documentos</h2>
    <?php if (empty($documentos)): ?>
        <p>O candidato ainda não anexou documentos.</p>
    <?php else: ?>
        <table>
            <tr><th>Ficheiro</th><th>Enviado em</th><th></th></tr>
            <?php foreach ($documentos as $d): ?>
                <tr>
                    <td><?= escapar($d['nome_original']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($d['data_upload'])) ?></td>
                    <td><a href="index.php?rota=documento&amp;id=<?= (int) $d['id'] ?>">Descarregar</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <h2>Pedidos de esclarecimento</h2>
    <?php foreach ($esclarecimentos as $e): ?>
        <div class="caixa">
            <p><strong>Pergunta</strong> (<?= date('d/m/Y H:i', strtotime($e['data_pedido'])) ?>)<br>
                <?= nl2br(escapar($e['pergunta'])) ?></p>
            <?php if ($e['resposta'] !== null): ?>
                <p><strong>Resposta</strong> (<?= date('d/m/Y H:i', strtotime($e['data_resposta'])) ?>)<br>
                    <?= nl2br(escapar($e['resposta'])) ?></p>
            <?php else: ?>
                <p>Ainda sem resposta.</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <?php if ($podePedirEsclarecimento): ?>
        <form method="post" action="index.php?rota=tecnico_esclarecimento">
            <input type="hidden" name="id" value="<?= (int) $candidatura['id'] ?>">
            <label>Novo pedido de esclarecimento
                <textarea name="pergunta" rows="3" maxlength="1000" required></textarea>
            </label>
            <button type="submit">Enviar pedido</button>
        </form>
    <?php endif; ?>

    <h2>Observações internas</h2>
    <?php foreach ($observacoes as $o): ?>
        <div class="caixa">
            <p><strong><?= escapar($o['tecnico']) ?></strong> (<?= date('d/m/Y H:i', strtotime($o['data_registo'])) ?>)<br>
                <?= nl2br(escapar($o['texto'])) ?></p>
        </div>
    <?php endforeach; ?>

    <form method="post" action="index.php?rota=tecnico_observacao">
        <input type="hidden" name="id" value="<?= (int) $candidatura['id'] ?>">
        <label>Nova observação (o candidato não a vê)
            <textarea name="texto" rows="3" maxlength="1000" required></textarea>
        </label>
        <button type="submit">Registar observação</button>
    </form>

<h2>Histórico de estados</h2>
<?php if (empty($historico)): ?>
    <p>Sem alterações registadas.</p>
<?php else: ?>
    <table>
        <tr>
            <th>Data</th>
            <th>De</th>
            <th>Para</th>
            <th>Responsável</th>
        </tr>
        <?php foreach ($historico as $h): ?>
            <tr>
                <td><?= date('d/m/Y H:i', strtotime($h['data_alteracao'])) ?></td>
                <td><?= $h['estado_anterior'] ? escapar(nomeEstado($h['estado_anterior'])) : '—' ?></td>
                <td><?= escapar(nomeEstado($h['estado_novo'])) ?></td>
                <td><?= escapar($h['responsavel']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
