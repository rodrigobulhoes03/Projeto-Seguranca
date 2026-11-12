<?php
// Funções de apoio usadas em todo o projeto (carregadas pelo public/index.php).

// Escapa texto antes de o mostrar numa página (protege contra XSS)
function escapar($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

// Lê um texto de $_GET ou $_POST, sem espaços nas pontas ('' se não existir)
function param(array $origem, string $chave): string
{
    $valor = $origem[$chave] ?? '';

    if (is_string($valor)) {
        return trim($valor);
    }

    return '';
}

// Lê uma password de $_POST. Não leva trim: os espaços fazem parte da password
function senha(array $origem, string $chave): string
{
    $valor = $origem[$chave] ?? '';

    return is_string($valor) ? $valor : '';
}

// Mostra uma view dentro do layout. Os $dados ficam disponíveis como variáveis na view
function render(string $view, array $dados = []): void
{
    extract($dados);
    $ficheiroView = __DIR__ . '/Views/' . $view . '.php';
    require __DIR__ . '/Views/layout.php';
}

// Vai para outra rota (ex.: redirecionar('login') ou redirecionar('candidatura&id=3'))
function redirecionar(string $rota): void
{
    header('Location: index.php?rota=' . $rota);
    exit;
}

// Nome do estado para mostrar ao utilizador
function nomeEstado($estado)
{
    $nomes = [
        'submetida' => 'Submetida',
        'em_analise' => 'Em análise',
        'aprovada' => 'Aprovada',
        'rejeitada' => 'Rejeitada',
        'aguarda_documentacao' => 'A aguardar documentação',
        'cancelada' => 'Cancelada',
    ];

    return $nomes[$estado] ?? $estado;
}
