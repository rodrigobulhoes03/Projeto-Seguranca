<?php

class Bolsa
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function listar(string $pesquisa = ''): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, designacao, tipo_apoio, area_estudo, montante, prazo
             FROM bolsas
             WHERE designacao LIKE ? OR tipo_apoio LIKE ? OR area_estudo LIKE ?
             ORDER BY prazo'
        );

        $texto = '%' . trim($pesquisa) . '%';
        $stmt->execute([$texto, $texto, $texto]);

        return $stmt->fetchAll();
    }

    public function porId(int $id): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM bolsas WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch();
    }
}