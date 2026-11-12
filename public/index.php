<?php
// Ponto de entrada: só carrega o projeto e escolhe o controller pela rota (?rota=...)

// Carrega as classes de app/ quando são precisadas
spl_autoload_register(function ($classe) {
    foreach (['', 'Models/', 'Controllers/'] as $pasta) {
        $ficheiro = __DIR__ . '/../app/' . $pasta . $classe . '.php';

        if (is_file($ficheiro)) {
            require $ficheiro;
            return;
        }
    }
});

require __DIR__ . '/../app/helpers.php';

// rota => [controller, método]
$rotas = [
    'registo'  => ['AuthController', 'registo'],
    'login'    => ['AuthController', 'login'],
    'logout'   => ['AuthController', 'logout'],

    'bolsas'   => ['BolsaController', 'lista'],
    'bolsa'    => ['BolsaController', 'detalhe'],

    'perfil'   => ['CandidatoController', 'perfil'],
    'password' => ['CandidatoController', 'password'],

    'candidaturas'             => ['CandidaturaController', 'lista'],
    'candidatura'              => ['CandidaturaController', 'detalhe'],
    'candidatura_nova'         => ['CandidaturaController', 'nova'],
    'candidatura_editar'       => ['CandidaturaController', 'editar'],
    'candidatura_cancelar'     => ['CandidaturaController', 'cancelar'],
    'documento'                => ['CandidaturaController', 'descarregarDocumento'],
    'documento_enviar'         => ['CandidaturaController', 'anexarDocumento'],
    'esclarecimento_responder' => ['CandidaturaController', 'responderEsclarecimento'],

    'tecnico'                  => ['TecnicoController', 'lista'],
    'tecnico_candidatura'      => ['TecnicoController', 'detalhe'],
    'tecnico_estado'           => ['TecnicoController', 'mudarEstado'],
    'tecnico_esclarecimento'   => ['TecnicoController', 'pedirEsclarecimento'],
    'tecnico_observacao'       => ['TecnicoController', 'adicionarObservacao'],
];

Auth::iniciarSessao();

$rota = $_GET['rota'] ?? 'bolsas';

if (!is_string($rota) || !isset($rotas[$rota])) {
    http_response_code(404);
    exit('Página não encontrada.');
}

$pdo = require __DIR__ . '/../config.php';

[$controller, $metodo] = $rotas[$rota];
(new $controller($pdo))->$metodo();
