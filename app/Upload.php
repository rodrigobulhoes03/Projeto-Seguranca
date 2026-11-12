<?php

class Upload
{
    // Tamanho máximo: 2 MB (é também o limite normal do PHP, upload_max_filesize)
    const TAMANHO_MAXIMO = 2097152;

    // Tipos aceites (tipo real do ficheiro => extensão que usamos)
    const TIPOS = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    // Pasta onde ficam os ficheiros: uploads/, fora da pasta public/
    public static function pasta()
    {
        $pasta = __DIR__ . '/../uploads/';

        if (!is_dir($pasta)) {
            mkdir($pasta, 0750, true);
        }

        return $pasta;
    }

    // Valida e guarda o ficheiro. Devolve 'erro' vazio se correu bem
    public static function guardar($ficheiro)
    {
        if (!is_array($ficheiro) || !isset($ficheiro['error']) || is_array($ficheiro['error'])) {
            return ['erro' => 'Ficheiro inválido.'];
        }

        if ($ficheiro['error'] === UPLOAD_ERR_NO_FILE) {
            return ['erro' => 'Escolha um ficheiro.'];
        }

        if ($ficheiro['error'] === UPLOAD_ERR_INI_SIZE || $ficheiro['error'] === UPLOAD_ERR_FORM_SIZE) {
            return ['erro' => 'O ficheiro é demasiado grande (máximo 2 MB).'];
        }

        if ($ficheiro['error'] !== UPLOAD_ERR_OK) {
            return ['erro' => 'Ocorreu um erro ao carregar o ficheiro.'];
        }

        if ($ficheiro['size'] <= 0 || $ficheiro['size'] > self::TAMANHO_MAXIMO) {
            return ['erro' => 'O ficheiro é demasiado grande (máximo 2 MB).'];
        }

        // Confirma que o ficheiro veio mesmo de um upload
        if (!is_uploaded_file($ficheiro['tmp_name'])) {
            return ['erro' => 'Ficheiro inválido.'];
        }

        // Descobre o tipo real pelo conteúdo (não confia no nome nem no tipo enviado pelo browser)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $tipo = $finfo->file($ficheiro['tmp_name']);

        if (!isset(self::TIPOS[$tipo])) {
            return ['erro' => 'Tipo de ficheiro não permitido. Só são aceites PDF, JPG e PNG.'];
        }

        // Nome aleatório: o nome que o utilizador deu nunca é usado no disco
        $nomeFicheiro = bin2hex(random_bytes(16)) . '.' . self::TIPOS[$tipo];

        if (!move_uploaded_file($ficheiro['tmp_name'], self::pasta() . $nomeFicheiro)) {
            return ['erro' => 'Não foi possível guardar o ficheiro.'];
        }

        // O nome original só serve para mostrar ao utilizador, por isso fica só com caracteres seguros
        $nomeOriginal = preg_replace('/[^A-Za-z0-9._ -]/', '_', basename($ficheiro['name']));
        $nomeOriginal = substr($nomeOriginal, 0, 100);

        return [
            'erro' => '',
            'nome_original' => $nomeOriginal,
            'nome_ficheiro' => $nomeFicheiro,
            'tipo_mime' => $tipo,
        ];
    }
}