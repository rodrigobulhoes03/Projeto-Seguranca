<?php

// Observações internas do técnico sobre uma candidatura (o candidato não as vê)
class Observacao
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function listar($candidaturaId)
    {
        $sql = 'SELECT o.texto, o.data_registo, u.nome AS tecnico
                FROM observacoes o
                JOIN utilizadores u ON u.id = o.id_tecnico
                WHERE o.id_candidatura = ?
                ORDER BY o.data_registo DESC, o.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$candidaturaId]);

        return $stmt->fetchAll();
    }

    public function criar($candidaturaId, $tecnicoId, $texto)
    {
        $sql = 'INSERT INTO observacoes (id_candidatura, id_tecnico, texto) VALUES (?, ?, ?)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$candidaturaId, $tecnicoId, $texto]);
    }
}
