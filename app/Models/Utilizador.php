<?php

class Utilizador
{
    // NOVO: limites contra brute-force: máximo de falhas dentro da janela de tempo
    private const MAX_FALHAS_POR_USERNAME = 5;
    private const MAX_FALHAS_POR_IP = 20;
    private const JANELA_MINUTOS = 15;

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

    // ---------- NOVO: Proteção contra brute-force no login ----------

    // true se o username ou o IP já falharam demasiadas vezes nos últimos minutos
    public function demasiadasTentativas(string $username, string $ip): bool
    {
        $janela = 'data_tentativa > NOW() - INTERVAL ' . self::JANELA_MINUTOS . ' MINUTE';

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM tentativas_login WHERE username = ? AND $janela");
        $stmt->execute([$username]);
        if ((int) $stmt->fetchColumn() >= self::MAX_FALHAS_POR_USERNAME) {
            return true;
        }

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM tentativas_login WHERE ip = ? AND $janela");
        $stmt->execute([$ip]);

        return (int) $stmt->fetchColumn() >= self::MAX_FALHAS_POR_IP;
    }

    // Guarda uma tentativa falhada (também quando o username não existe)
    public function registarFalhaDeLogin(string $username, string $ip): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO tentativas_login (username, ip) VALUES (?, ?)');
        $stmt->execute([$username, $ip]);

        // Limpeza: apaga o que já tem mais de um dia
        $this->pdo->exec('DELETE FROM tentativas_login WHERE data_tentativa < NOW() - INTERVAL 1 DAY');
    }

    // Depois de um login correto, as falhas desse username deixam de contar
    public function limparFalhasDeLogin(string $username): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM tentativas_login WHERE username = ?');
        $stmt->execute([$username]);
    }
}