<?php

// Conta do candidato: perfil e password
class CandidatoController
{
    private Utilizador $utilizadores;

    public function __construct(PDO $pdo)
    {
        $this->utilizadores = new Utilizador($pdo);
    }

    // Ver e editar o perfil
    public function perfil(): void
    {
        Auth::exigirPerfil('candidato');

        $utilizadorId = Auth::id();
        $dados = $this->utilizadores->porId($utilizadorId);

        if (!$dados) {
            Auth::terminarSessao();
            redirecionar('login');
        }

        $mensagem = '';
        $guardado = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados['nome'] = param($_POST, 'nome');
            $dados['email'] = param($_POST, 'email');
            $dados['morada'] = param($_POST, 'morada');
            $dados['telefone'] = param($_POST, 'telefone');
            $dados['nif'] = param($_POST, 'nif');

            $mensagem = Validador::perfil($dados);

            if ($mensagem === '') {
                try {
                    $this->utilizadores->atualizarPerfil(
                        $utilizadorId,
                        $dados['nome'],
                        $dados['email'],
                        $dados['morada'] === '' ? null : $dados['morada'],
                        $dados['telefone'] === '' ? null : $dados['telefone'],
                        $dados['nif'] === '' ? null : $dados['nif']
                    );
                    $guardado = true;
                    $mensagem = 'Perfil atualizado com sucesso!';
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $mensagem = 'Este email já está em uso.';
                    } else {
                        throw $e;
                    }
                }
            }
        }

        render('candidato/perfil', [
            'titulo'   => 'O meu perfil',
            'dados'    => $dados,
            'mensagem' => $mensagem,
            'guardado' => $guardado,
        ]);
    }

    // Alterar a password (pede a atual)
    public function password(): void
    {
        Auth::exigirPerfil('candidato');

        $mensagem = '';
        $guardado = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $atual = senha($_POST, 'password_atual');
            $nova = senha($_POST, 'password_nova');
            $confirmar = senha($_POST, 'password_confirmar');

            $hashAtual = $this->utilizadores->buscarPasswordHash(Auth::id());

            if (!$hashAtual) {
                Auth::terminarSessao();
                redirecionar('login');
            }

            if (!password_verify($atual, $hashAtual)) {
                $mensagem = 'A password atual está incorreta.';
            } else {
                $mensagem = Validador::password($nova);
            }

            if ($mensagem === '' && $nova !== $confirmar) {
                $mensagem = 'A nova password e a confirmação não coincidem.';
            }

            if ($mensagem === '') {
                $this->utilizadores->guardarNovaPassword(Auth::id(), password_hash($nova, PASSWORD_DEFAULT));
                session_regenerate_id(true);
                $guardado = true;
                $mensagem = 'Password alterada com sucesso!';
            }
        }

        render('candidato/password', [
            'titulo' => 'Alterar password',
            'mensagem' => $mensagem,
            'guardado' => $guardado,
        ]);
    }
}
