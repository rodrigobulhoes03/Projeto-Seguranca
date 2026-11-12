<?php

class AuthController
{
    private Utilizador $modeloUtilizador;

    public function __construct(PDO $pdo)
    {
        $this->modeloUtilizador = new Utilizador($pdo);
    }

    public function registo(): void
    {
        $mensagemRegisto = '';
        $contaCriada     = false;
        $dadosFormulario = ['username' => '', 'nome' => '', 'email' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dadosFormulario['username'] = param($_POST, 'username');
            $dadosFormulario['nome'] = param($_POST, 'nome');
            $dadosFormulario['email'] = param($_POST, 'email');
            $password = senha($_POST, 'password');

            $mensagemRegisto = Validador::registo($dadosFormulario, $password);

            if ($mensagemRegisto === '') {
                try {
                    $this->modeloUtilizador->criarCandidato(
                        $dadosFormulario['username'],
                        password_hash($password, PASSWORD_DEFAULT),
                        $dadosFormulario['nome'],
                        $dadosFormulario['email']
                    );
                    $contaCriada     = true;
                    $mensagemRegisto = 'Conta criada com sucesso!';
                    $dadosFormulario = ['username' => '', 'nome' => '', 'email' => ''];
                } catch (PDOException $excecao) {
                    if ($excecao->getCode() == 23000) {
                        $mensagemRegisto = 'Este username ou email já existe.';
                    } else {
                        throw $excecao;
                    }
                }
            }
        }

        render('auth/registo', [
            'titulo' => 'Registo',
            'mensagem' => $mensagemRegisto,
            'sucesso' => $contaCriada,
            'dadosFormulario' => $dadosFormulario,
        ]);
    }

    public function login(): void
    {
        $mensagemErro = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = param($_POST, 'username');
            $password = senha($_POST, 'password');

            $utilizadorEncontrado = $this->modeloUtilizador->porUsername($username);

            if ($utilizadorEncontrado && password_verify($password, $utilizadorEncontrado['password_hash'])) {
                Auth::iniciarLogin($utilizadorEncontrado);

                if ($utilizadorEncontrado['perfil'] === 'tecnico') {
                    redirecionar('tecnico');
                }

                redirecionar('perfil');
            }

            $mensagemErro = 'Username ou password incorretos.';
        }

        render('auth/login', ['titulo' => 'Login', 'erro' => $mensagemErro]);
    }

    public function logout(): void
    {
        // Só termina a sessão com um pedido POST (o botão do menu)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::terminarSessao();
        }

        redirecionar('login');
    }
}
