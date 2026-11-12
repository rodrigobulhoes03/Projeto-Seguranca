<?php
// Sessão: guarda quem fez login e com que perfil.

class Auth
{
    public static function iniciarSessao()
    {
        session_start();
    }

    public static function autenticado()
    {
        return isset($_SESSION['user_id']);
    }

    // Id do utilizador com sessão iniciada (0 se ninguém iniciou sessão)
    public static function id(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    // 'candidato', 'tecnico' ou '' se ninguém iniciou sessão
    public static function perfil(): string
    {
        return $_SESSION['perfil'] ?? '';
    }

    public static function exigirPerfil($perfil)
    {
        if (!Auth::autenticado()) {
            redirecionar('login');
        }

        if (Auth::perfil() !== $perfil) {
            http_response_code(403);
            exit('Acesso negado.');
        }
    }

    public static function iniciarLogin($utilizador)
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $utilizador['id'];
        $_SESSION['username'] = $utilizador['username'];
        $_SESSION['perfil'] = $utilizador['perfil'];
    }

    public static function terminarSessao()
    {
        $_SESSION = [];
        session_destroy();
    }
}
