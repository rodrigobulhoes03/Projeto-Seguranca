<?php

class Utilizador
{
    public function __construct(private PDO $pdo) {}

    public function criarCandidato(string $username, string $hash, string $nome, string $email): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO utilizadores (username, password_hash, perfil, nome, email)
             VALUES (?, ?, 'candidato', ?, ?)"
        );
        $stmt->execute([$username, $hash, $nome, $email]);
    }

    public function porUsername(string $username): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, username, password_hash, perfil FROM utilizadores WHERE username = ?'
        );
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

        // Devolve os dados do perfil de um utilizador (sem a password)
    public function porId(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, username, nome, email, morada, telefone, nif
             FROM utilizadores WHERE id = ?'
        );
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    // Atualiza os dados do perfil
    public function atualizarPerfil(int $id, string $nome, string $email, ?string $morada, ?string $telefone, ?string $nif): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE utilizadores
             SET nome = ?, email = ?, morada = ?, telefone = ?, nif = ?
             WHERE id = ?'
        );
        $stmt->execute([$nome, $email, $morada, $telefone, $nif, $id]);
    }

    public function emailEmUso(string $email, int $idIgnorar): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM utilizadores WHERE email = ? AND id != ?');
        $stmt->execute([$email, $idIgnorar]);

        return $stmt->fetch() !== false;
    }

    // Devolve o hash da password atual (ou false se o utilizador não existir)
    public function buscarPasswordHash(int $id): string|false
    {
        $stmt = $this->pdo->prepare('SELECT password_hash FROM utilizadores WHERE id = ?');
        $stmt->execute([$id]);
        $linha = $stmt->fetch();

        if ($linha) {
            return $linha['password_hash'];
        }

        return false;
    }

    // Guarda o novo hash da password
    public function guardarNovaPassword(int $id, string $novoHash): void
    {
        $stmt = $this->pdo->prepare('UPDATE utilizadores SET password_hash = ? WHERE id = ?');
        $stmt->execute([$novoHash, $id]);
    }

}