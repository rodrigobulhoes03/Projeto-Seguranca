<h1><?= escapar($candidatura['designacao']) ?></h1>

<p><a href="index.php?rota=candidaturas">&larr; Voltar às candidaturas</a></p>

<?php if (!empty($erro)): ?>
    <p class="erro"><?= escapar($erro) ?></p>
<?php endif; ?>

<table>
    <tr><th>Estado</th><td><?= escapar(nomeEstado($candidatura['estado'])) ?></td></tr>
    <tr><th>Submetida em</th><td><?= date('d/m/Y H:i', strtotime($candidatura['data_submissao'])) ?></td></tr>
    <tr><th>Instituição</th><td><?= escapar($candidatura['instituicao']) ?></td></tr>
    <tr><th>Curso</th><td><?= escapar($candidatura['curso']) ?></td></tr>
    <tr><th>Rendimento do agregado</th><td><?= number_format((float) $candidatura['rendimento_agregado'], 2, ',', ' ') ?> €</td></tr>
    <tr><th>IBAN</th><td><?= escapar($candidatura['iban']) ?></td></tr>
</table>

<?php if ($candidatura['estado'] === 'submetida'): ?>
    <p>
        <a class="botao" href="index.php?rota=candidatura_editar&amp;id=<?= (int) $candidatura['id'] ?>">Editar</a>
    </p>
    <form method="post" action="index.php?rota=candidatura_cancelar" onsubmit="return confirm('Cancelar esta candidatura?');">
        <input type="hidden" name="id" value="<?= (int) $candidatura['id'] ?>">
        <button type="submit">Cancelar candidatura</button>
    </form>
<?php endif; ?>

<h2>Documentos</h2>
<?php if (empty($documentos)): ?>
    <p>Ainda não anexou documentos.</p>
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

<?php if (!empty($podeAnexar)): ?>
    <form method="post" action="index.php?rota=documento_enviar" enctype="multipart/form-data">
        <input type="hidden" name="candidatura_id" value="<?= (int) $candidatura['id'] ?>">
        <label>Anexar documento (PDF, JPG ou PNG, máximo 2 MB)
            <input type="file" name="documento" accept=".pdf,.jpg,.jpeg,.png" required>
        </label>
        <button type="submit">Enviar documento</button>
    </form>
<?php endif; ?>

<h2>Pedidos de esclarecimento</h2>
<?php if (!empty($podeAnexar)): ?>
    <p>Se o técnico pediu documentos adicionais, anexe-os na secção "Documentos" acima.</p>
<?php endif; ?>
<?php if (empty($esclarecimentos)): ?>
    <p>Não há pedidos de esclarecimento.</p>
<?php else: ?>
    <?php foreach ($esclarecimentos as $e): ?>
        <div class="caixa">
            <p><strong>Pergunta</strong> (<?= date('d/m/Y H:i', strtotime($e['data_pedido'])) ?>)<br>
                <?= nl2br(escapar($e['pergunta'])) ?></p>

            <?php if ($e['resposta'] !== null): ?>
                <p><strong>A sua resposta</strong> (<?= date('d/m/Y H:i', strtotime($e['data_resposta'])) ?>)<br>
                    <?= nl2br(escapar($e['resposta'])) ?></p>
            <?php else: ?>
                <form method="post" action="index.php?rota=esclarecimento_responder">
                    <input type="hidden" name="candidatura_id" value="<?= (int) $candidatura['id'] ?>">
                    <input type="hidden" name="esclarecimento_id" value="<?= (int) $e['id'] ?>">
                    <label>Responder
                        <textarea name="resposta" rows="3" maxlength="1000" required></textarea>
                    </label>
                    <button type="submit">Enviar resposta</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<h2>Histórico de estados</h2>
<table>
    <tr><th>Data</th><th>De</th><th>Para</th></tr>
    <?php foreach ($historico as $h): ?>
        <tr>
            <td><?= date('d/m/Y H:i', strtotime($h['data_alteracao'])) ?></td>
            <td><?= $h['estado_anterior'] ? escapar(nomeEstado($h['estado_anterior'])) : '—' ?></td>
            <td><?= escapar(nomeEstado($h['estado_novo'])) ?></td>
        </tr>
    <?php endforeach; ?>
</table>
