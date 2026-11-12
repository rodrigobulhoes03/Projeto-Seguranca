<?php

// Pedidos de esclarecimento: o técnico pergunta, o candidato responde
class Esclarecimento
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function listar($candidaturaId)
    {
        $sql = 'SELECT e.id, e.pergunta, e.resposta, e.data_pedido, e.data_resposta, u.nome AS tecnico
                FROM esclarecimentos e
                JOIN utilizadores u ON u.id = e.id_tecnico
                WHERE e.id_candidatura = ?
                ORDER BY e.data_pedido, e.id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$candidaturaId]);

        return $stmt->fetchAll();
    }

    public function criar($candidaturaId, $tecnicoId, $pergunta)
    {
        $sql = 'INSERT INTO esclarecimentos (id_candidatura, id_tecnico, pergunta) VALUES (?, ?, ?)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$candidaturaId, $tecnicoId, $pergunta]);
    }

    // Guarda a resposta do candidato. Só funciona se a candidatura for dele e a pergunta ainda não tiver resposta
    public function responder($id, $candidaturaId, $candidatoId, $resposta)
    {
        $sql = 'UPDATE esclarecimentos
                SET resposta = ?, data_resposta = NOW()
                WHERE id = ? AND id_candidatura = ? AND resposta IS NULL
                  AND id_candidatura IN (SELECT id FROM candidaturas WHERE id_candidato = ?)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$resposta, $id, $candidaturaId, $candidatoId]);

        return $stmt->rowCount() === 1;
    }
}
