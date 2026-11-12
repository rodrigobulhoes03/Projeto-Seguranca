<?php

class Candidatura
{
    const ESTADOS = ['submetida', 'em_analise', 'aprovada', 'rejeitada', 'aguarda_documentacao', 'cancelada'];

    // Estados em que a candidatura já não muda
    const ESTADOS_FINAIS = ['aprovada', 'rejeitada', 'cancelada'];

    // Mudanças de estado que o técnico pode fazer (estado atual => estados possíveis)
    // O candidato só cancela, e só quando a candidatura ainda está submetida
    const TRANSICOES = [
        'submetida' => ['em_analise'],
        'em_analise' => ['aprovada', 'rejeitada', 'aguarda_documentacao'],
        'aguarda_documentacao' => ['em_analise'],
    ];

    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Verifica se a bolsa existe e se o prazo ainda não acabou
    public function bolsaEstaAberta($bolsaId)
    {
        $sql = 'SELECT id FROM bolsas WHERE id = ? AND prazo >= CURDATE()';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$bolsaId]);
        $bolsa = $stmt->fetch();

        if ($bolsa) {
            return true;
        }

        return false;
    }

    // Verifica se o candidato já fez candidatura a esta bolsa
    public function candidaturaJaExiste($candidatoId, $bolsaId)
    {
        $sql = 'SELECT id FROM candidaturas WHERE id_candidato = ? AND id_bolsa = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$candidatoId, $bolsaId]);
        $candidatura = $stmt->fetch();

        if ($candidatura) {
            return true;
        }

        return false;
    }

    // Guarda a candidatura. Devolve '' se correu bem, senão devolve o erro
    public function submeterCandidatura($candidatoId, $bolsaId, $instituicao, $curso, $rendimento, $iban)
    {
        // Só pode submeter dentro do prazo
        if (!$this->bolsaEstaAberta($bolsaId)) {
            return 'Esta bolsa não existe ou o prazo já terminou.';
        }

        // Só pode haver uma candidatura por bolsa
        if ($this->candidaturaJaExiste($candidatoId, $bolsaId)) {
            return 'Já submeteu uma candidatura a esta bolsa.';
        }

        $sql = 'INSERT INTO candidaturas (id_candidato, id_bolsa, id_tecnico, instituicao, curso, rendimento_agregado, iban, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$candidatoId, $bolsaId, $this->tecnicoComMenosCandidaturas(), $instituicao, $curso, $rendimento, $iban, 'submetida']);

        // Guarda no histórico que a candidatura foi submetida
        $novoId = $this->pdo->lastInsertId();
        $this->registarMudancaDeEstado($novoId, null, 'submetida', $candidatoId);

        return '';
    }

    // Distribui as candidaturas: vai para o técnico que tem menos (null se não houver técnicos)
    private function tecnicoComMenosCandidaturas()
    {
        $sql = "SELECT u.id
                FROM utilizadores u
                LEFT JOIN candidaturas c ON c.id_tecnico = u.id
                WHERE u.perfil = 'tecnico'
                GROUP BY u.id
                ORDER BY COUNT(c.id), u.id
                LIMIT 1";
        $id = $this->pdo->query($sql)->fetchColumn();

        return $id === false ? null : $id;
    }

    // Devolve todas as candidaturas do candidato
    public function listarCandidaturasDoCandidato($candidatoId)
    {
        $sql = 'SELECT c.id, c.estado, c.data_submissao, b.designacao
                FROM candidaturas c
                JOIN bolsas b ON b.id = c.id_bolsa
                WHERE c.id_candidato = ?
                ORDER BY c.data_submissao DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$candidatoId]);
        $candidaturas = $stmt->fetchAll();

        return $candidaturas;
    }

    // Devolve uma candidatura, mas só se for do candidato que pediu
    public function buscarCandidaturaPorId($id, $candidatoId)
    {
        $sql = 'SELECT c.id, c.id_bolsa, c.instituicao, c.curso, c.rendimento_agregado,
                       c.iban, c.estado, c.data_submissao, b.designacao
                FROM candidaturas c
                JOIN bolsas b ON b.id = c.id_bolsa
                WHERE c.id = ? AND c.id_candidato = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id, $candidatoId]);
        $candidatura = $stmt->fetch();

        return $candidatura;
    }

    // Edita a candidatura (só se ainda estiver submetida)
    public function editarCandidatura($id, $candidatoId, $instituicao, $curso, $rendimento, $iban)
    {
        $candidatura = $this->buscarCandidaturaPorId($id, $candidatoId);

        if (!$candidatura) {
            return 'Candidatura não encontrada.';
        }

        if ($candidatura['estado'] != 'submetida') {
            return 'Já não é possível editar esta candidatura.';
        }

        $sql = 'UPDATE candidaturas
                SET instituicao = ?, curso = ?, rendimento_agregado = ?, iban = ?
                WHERE id = ? AND id_candidato = ? AND estado = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$instituicao, $curso, $rendimento, $iban, $id, $candidatoId, 'submetida']);

        return '';
    }

    // Cancela a candidatura (só se ainda estiver submetida)
    public function cancelarCandidatura($id, $candidatoId)
    {
        $candidatura = $this->buscarCandidaturaPorId($id, $candidatoId);

        if (!$candidatura) {
            return 'Candidatura não encontrada.';
        }

        if ($candidatura['estado'] != 'submetida') {
            return 'Já não é possível cancelar esta candidatura.';
        }

        $sql = 'UPDATE candidaturas SET estado = ? WHERE id = ? AND id_candidato = ? AND estado = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['cancelada', $id, $candidatoId, 'submetida']);

        $this->registarMudancaDeEstado($id, 'submetida', 'cancelada', $candidatoId);

        return '';
    }

    // Guarda na base de dados os dados de um documento (o ficheiro fica na pasta uploads)
    public function guardarDocumento($candidaturaId, $nomeOriginal, $nomeFicheiro, $tipoMime)
    {
        $sql = 'INSERT INTO documentos (id_candidatura, nome_original, nome_ficheiro, tipo_mime)
                VALUES (?, ?, ?, ?)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$candidaturaId, $nomeOriginal, $nomeFicheiro, $tipoMime]);
    }

    // Lista os documentos de uma candidatura
    public function listarDocumentos($candidaturaId)
    {
        $sql = 'SELECT id, nome_original, tipo_mime, data_upload
                FROM documentos
                WHERE id_candidatura = ?
                ORDER BY data_upload DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$candidaturaId]);
        $documentos = $stmt->fetchAll();

        return $documentos;
    }

    // Conta os documentos de uma candidatura
    public function contarDocumentos($candidaturaId)
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS total FROM documentos WHERE id_candidatura = ?');
        $stmt->execute([$candidaturaId]);
        $linha = $stmt->fetch();

        return (int) $linha['total'];
    }

    // Devolve o documento e os donos da candidatura (para ver quem pode aceder)
    public function buscarDocumentoComDonos($documentoId)
    {
        $sql = 'SELECT d.id, d.nome_original, d.nome_ficheiro, d.tipo_mime,
                       c.id_candidato, c.id_tecnico
                FROM documentos d
                JOIN candidaturas c ON c.id = d.id_candidatura
                WHERE d.id = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$documentoId]);
        $documento = $stmt->fetch();

        return $documento;
    }

    // Guarda no histórico cada vez que o estado muda
    private function registarMudancaDeEstado($candidaturaId, $estadoAntigo, $estadoNovo, $utilizadorId)
    {
        $sql = 'INSERT INTO historico_estados (id_candidatura, estado_anterior, estado_novo, id_utilizador)
                VALUES (?, ?, ?, ?)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$candidaturaId, $estadoAntigo, $estadoNovo, $utilizadorId]);
    }

    // ---------- Área do técnico ----------

    // Candidaturas atribuídas ao técnico, com filtros opcionais
    public function listarDoTecnico($tecnicoId, $filtros)
    {
        $sql = 'SELECT c.id, c.id_tecnico, c.estado, c.data_submissao, b.designacao, u.nome AS candidato
                FROM candidaturas c
                JOIN bolsas b ON b.id = c.id_bolsa
                JOIN utilizadores u ON u.id = c.id_candidato
                WHERE c.id_tecnico = ?';
        $valores = [$tecnicoId];

        if ($filtros['nome'] !== '') {
            $sql .= ' AND u.nome LIKE ?';
            $valores[] = '%' . $filtros['nome'] . '%';
        }
        if ($filtros['bolsa'] > 0) {
            $sql .= ' AND c.id_bolsa = ?';
            $valores[] = $filtros['bolsa'];
        }
        if ($filtros['data'] !== '') {
            $sql .= ' AND DATE(c.data_submissao) = ?';
            $valores[] = $filtros['data'];
        }
        if ($filtros['estado'] !== '') {
            $sql .= ' AND c.estado = ?';
            $valores[] = $filtros['estado'];
        }

        $stmt = $this->pdo->prepare($sql . ' ORDER BY c.data_submissao DESC');
        $stmt->execute($valores);

        return $stmt->fetchAll();
    }

    // Devolve uma candidatura com os dados do candidato, mas só se estiver atribuída ao técnico
    public function buscarDoTecnico($id, $tecnicoId)
    {
        $sql = 'SELECT c.id, c.id_tecnico, c.instituicao, c.curso, c.rendimento_agregado, c.iban,
                       c.estado, c.data_submissao, b.designacao,
                       u.nome, u.email, u.morada, u.telefone, u.nif
                FROM candidaturas c
                JOIN bolsas b ON b.id = c.id_bolsa
                JOIN utilizadores u ON u.id = c.id_candidato
                WHERE c.id = ? AND c.id_tecnico = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id, $tecnicoId]);

        return $stmt->fetch();
    }

    // Muda o estado (só se a candidatura for do técnico e a mudança for permitida). Devolve '' ou o erro
    public function mudarEstadoDoTecnico($id, $tecnicoId, $novoEstado)
    {
        $stmt = $this->pdo->prepare('SELECT estado FROM candidaturas WHERE id = ? AND id_tecnico = ?');
        $stmt->execute([$id, $tecnicoId]);
        $candidatura = $stmt->fetch();

        if (!$candidatura) {
            return 'Candidatura não encontrada ou não atribuída a si.';
        }

        $estadoAntigo = $candidatura['estado'];

        if (!in_array($novoEstado, self::TRANSICOES[$estadoAntigo] ?? [], true)) {
            return 'Esta mudança de estado não é permitida.';
        }

        // O UPDATE e o histórico ficam juntos: ou gravam os dois, ou nenhum
        $this->pdo->beginTransaction();

        $stmt = $this->pdo->prepare('UPDATE candidaturas SET estado = ? WHERE id = ? AND id_tecnico = ? AND estado = ?');
        $stmt->execute([$novoEstado, $id, $tecnicoId, $estadoAntigo]);

        if ($stmt->rowCount() !== 1) {
            $this->pdo->rollBack();
            return 'O estado da candidatura mudou entretanto. Tente outra vez.';
        }

        $this->registarMudancaDeEstado($id, $estadoAntigo, $novoEstado, $tecnicoId);
        $this->pdo->commit();

        return '';
    }

    // ---------- Histórico de estados ----------

    // Todas as mudanças de estado de uma candidatura, da mais antiga para a mais recente
    public function historico($candidaturaId)
    {
        $sql = 'SELECT h.estado_anterior, h.estado_novo, h.data_alteracao, u.nome AS responsavel
                FROM historico_estados h
                JOIN utilizadores u ON u.id = h.id_utilizador
                WHERE h.id_candidatura = ?
                ORDER BY h.data_alteracao, h.id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$candidaturaId]);

        return $stmt->fetchAll();
    }
}
