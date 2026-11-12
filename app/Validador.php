<?php
// Validações dos formulários. Cada função devolve '' se estiver tudo certo,
// ou a mensagem de erro para mostrar ao utilizador.

class Validador
{
    public static function registo(array $dados, string $password): string
    {
        if (mb_strlen($dados['username']) < 3 || mb_strlen($dados['username']) > 50) {
            return 'O username deve ter entre 3 e 50 caracteres.';
        }
        if ($dados['nome'] === '' || mb_strlen($dados['nome']) > 100) {
            return 'Indique o seu nome (máximo 100 caracteres).';
        }
        if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($dados['email']) > 100) {
            return 'Indique um email válido.';
        }

        return self::password($password);
    }

    public static function password(string $password): string
    {
        if (strlen($password) < 8 || strlen($password) > 72) {
            return 'A password deve ter entre 8 e 72 caracteres.';
        }

        return '';
    }

    public static function perfil(array $dados): string
    {
        if ($dados['nome'] === '' || mb_strlen($dados['nome']) > 100) {
            return 'Indique o seu nome (máximo 100 caracteres).';
        }
        if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($dados['email']) > 100) {
            return 'Indique um email válido.';
        }
        if (mb_strlen($dados['morada']) > 200) {
            return 'A morada pode ter no máximo 200 caracteres.';
        }
        if ($dados['telefone'] !== '' && !preg_match('/^\+?[0-9 ]{9,20}$/', $dados['telefone'])) {
            return 'O contacto telefónico é inválido.';
        }
        if ($dados['nif'] !== '' && !preg_match('/^[0-9]{9}$/', $dados['nif'])) {
            return 'O NIF deve ter exatamente 9 dígitos.';
        }

        return '';
    }

    public static function candidatura(array $dados): string
    {
        if ($dados['instituicao'] === '' || mb_strlen($dados['instituicao']) > 150) {
            return 'Indique a instituição de ensino (máximo 150 caracteres).';
        }

        if ($dados['curso'] === '' || mb_strlen($dados['curso']) > 150) {
            return 'Indique o curso (máximo 150 caracteres).';
        }

        // Número positivo com no máximo 2 casas decimais
        if (!preg_match('/^[0-9]{1,8}(\.[0-9]{1,2})?$/', $dados['rendimento'])) {
            return 'O rendimento do agregado familiar é inválido.';
        }

        if (!self::iban($dados['iban'])) {
            return 'O IBAN é inválido. Use o formato PT50 seguido de 21 dígitos.';
        }

        return '';
    }

    // Texto livre obrigatório (pergunta, resposta, observação). $nome é, por exemplo, 'a pergunta'
    public static function texto(string $texto, string $nome, int $maximo = 1000): string
    {
        if ($texto === '' || mb_strlen($texto) > $maximo) {
            return 'Escreva ' . $nome . ' (máximo ' . $maximo . ' caracteres).';
        }

        return '';
    }

    // Valida o IBAN português: formato e dígitos de controlo (algoritmo mod 97)
    public static function iban(string $iban): bool
    {
        // PT + 23 dígitos = 25 caracteres
        if (!preg_match('/^PT[0-9]{23}$/', $iban)) {
            return false;
        }

        // Passa os 4 primeiros caracteres para o fim e troca as letras por números (P = 25, T = 29)
        $rearranjado = substr($iban, 4) . substr($iban, 0, 4);
        $rearranjado = str_replace(['P', 'T'], ['25', '29'], $rearranjado);

        // O número é demasiado grande para um int, por isso calcula-se o resto dígito a dígito
        $resto = 0;

        foreach (str_split($rearranjado) as $digito) {
            $resto = ($resto * 10 + (int) $digito) % 97;
        }

        return $resto === 1;
    }
}
