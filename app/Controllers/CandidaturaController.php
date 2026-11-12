<?php

class CandidaturaController
{
    private $candidaturaModelo;
    private $bolsaModelo;
    private $esclarecimentoModelo;

    public function __construct($pdo)
    {
        $this->candidaturaModelo = new Candidatura($pdo);
        $this->bolsaModelo = new Bolsa($pdo);
        $this->esclarecimentoModelo = new Esclarecimento($pdo);
    }

    // Mostra a lista das candidaturas do candidato
    public function lista()
    {
        Auth::exigirPerfil('candidato');

        $this->mostrarLista('');
    }

    // Formulário para submeter uma candidatura nova
    public function nova()
    {
        Auth::exigirPerfil('candidato');

        $candidatoId = $this->candidatoAtual();
        $bolsaId = $this->lerIdDoPedido('bolsa_id');

        $bolsa = $this->bolsaModelo->porId($bolsaId);

        if (!$bolsa) {
            $this->naoEncontrada();
            return;
        }

        // Não vale a pena mostrar o formulário se já não for possível candidatar
        if (!$this->candidaturaModelo->bolsaEstaAberta($bolsaId)) {
            $this->mostrarLista('O prazo desta bolsa já terminou.');
            return;
        }

        if ($this->candidaturaModelo->candidaturaJaExiste($candidatoId, $bolsaId)) {
            $this->mostrarLista('Já submeteu uma candidatura a esta bolsa.');
            return;
        }

        $erro = '';
        $dados = ['instituicao' => '', 'curso' => '', 'rendimento' => '', 'iban' => ''];

        if ($this->pedidoEPost()) {
            $dados = $this->lerDadosDoFormulario();
            $erro = Validador::candidatura($dados);

            // Só chama o modelo se os dados estiverem bem escritos
            if ($erro === '') {
                $erro = $this->candidaturaModelo->submeterCandidatura(
                    $candidatoId,
                    $bolsaId,
                    $dados['instituicao'],
                    $dados['curso'],
                    $dados['rendimento'],
                    $dados['iban']
                );
            }

            if ($erro === '') {
                redirecionar('candidaturas');
            }
        }

        render('candidato/nova', [
            'titulo' => 'Nova candidatura',
            'bolsa' => $bolsa,
            'dados' => $dados,
            'erro' => $erro,
        ]);
    }

    // Mostra os detalhes de uma candidatura
    public function detalhe()
    {
        Auth::exigirPerfil('candidato');

        $id = $this->lerIdDoPedido('id');
        $candidatura = $this->candidaturaModelo->buscarCandidaturaPorId($id, $this->candidatoAtual());

        if (!$candidatura) {
            $this->naoEncontrada();
            return;
        }

        $this->mostrarDetalhe($candidatura, '');
    }

    // Anexa um documento a uma candidatura
    public function anexarDocumento()
    {
        Auth::exigirPerfil('candidato');

        if (!$this->pedidoEPost()) {
            redirecionar('candidaturas');
        }

        $id = $this->lerIdDoPedido('candidatura_id');
        $candidatura = $this->candidaturaModelo->buscarCandidaturaPorId($id, $this->candidatoAtual());

        if (!$candidatura) {
            $this->naoEncontrada();
            return;
        }

        if (!$this->podeAnexarDocumentos($candidatura)) {
            $this->mostrarDetalhe($candidatura, 'Já não é possível anexar documentos a esta candidatura.');
            return;
        }

        // Limite para ninguém encher o disco com ficheiros
        if ($this->candidaturaModelo->contarDocumentos($id) >= 10) {
            $this->mostrarDetalhe($candidatura, 'Atingiu o limite de 10 documentos por candidatura.');
            return;
        }

        $resultado = Upload::guardar($_FILES['documento'] ?? null);

        if ($resultado['erro'] !== '') {
            $this->mostrarDetalhe($candidatura, $resultado['erro']);
            return;
        }

        $this->candidaturaModelo->guardarDocumento(
            $id,
            $resultado['nome_original'],
            $resultado['nome_ficheiro'],
            $resultado['tipo_mime']
        );

        redirecionar('candidatura&id=' . $id);
    }

    // Responde a um pedido de esclarecimento do técnico
    public function responderEsclarecimento()
    {
        Auth::exigirPerfil('candidato');

        if (!$this->pedidoEPost()) {
            redirecionar('candidaturas');
        }

        $id = $this->lerIdDoPedido('candidatura_id');
        $candidatura = $this->candidaturaModelo->buscarCandidaturaPorId($id, $this->candidatoAtual());

        if (!$candidatura) {
            $this->naoEncontrada();
            return;
        }

        $esclarecimentoId = (int) param($_POST, 'esclarecimento_id');
        $resposta = param($_POST, 'resposta');

        $erro = Validador::texto($resposta, 'a resposta');

        if ($erro === '' && !$this->esclarecimentoModelo->responder($esclarecimentoId, $id, $this->candidatoAtual(), $resposta)) {
            $erro = 'Não foi possível responder a este pedido de esclarecimento.';
        }

        if ($erro !== '') {
            $this->mostrarDetalhe($candidatura, $erro);
            return;
        }

        redirecionar('candidatura&id=' . $id);
    }

    // Entrega um documento, mas só a quem tem permissão (o candidato dono ou o técnico atribuído)
    public function descarregarDocumento()
    {
        if (!Auth::autenticado()) {
            redirecionar('login');
        }

        $documentoId = $this->lerIdDoPedido('id');
        $documento = $this->candidaturaModelo->buscarDocumentoComDonos($documentoId);

        // Se não existe ou não tem permissão, a resposta é a mesma
        if (!$documento || !$this->podeVerDocumento($documento)) {
            $this->naoEncontrada();
            return;
        }

        $caminho = Upload::pasta() . $documento['nome_ficheiro'];

        if (!is_file($caminho)) {
            $this->naoEncontrada();
            return;
        }

        // Tira aspas e quebras de linha do nome, para não estragar o cabeçalho
        $nome = str_replace(['"', "\r", "\n"], '', $documento['nome_original']);

        header('Content-Type: ' . $documento['tipo_mime']);
        header('Content-Length: ' . filesize($caminho));
        header('Content-Disposition: attachment; filename="' . $nome . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');

        readfile($caminho);
        exit;
    }

    // Formulário para editar uma candidatura
    public function editar()
    {
        Auth::exigirPerfil('candidato');

        $candidatoId = $this->candidatoAtual();
        $id = $this->lerIdDoPedido('id');

        $candidatura = $this->candidaturaModelo->buscarCandidaturaPorId($id, $candidatoId);

        if (!$candidatura) {
            $this->naoEncontrada();
            return;
        }

        // Depois de entrar em análise já não se pode editar
        if ($candidatura['estado'] !== 'submetida') {
            $this->mostrarLista('Já não é possível editar esta candidatura.');
            return;
        }

        $erro = '';
        $dados = [
            'instituicao' => $candidatura['instituicao'],
            'curso' => $candidatura['curso'],
            'rendimento' => $candidatura['rendimento_agregado'],
            'iban' => $candidatura['iban'],
        ];

        if ($this->pedidoEPost()) {
            $dados = $this->lerDadosDoFormulario();
            $erro = Validador::candidatura($dados);

            if ($erro === '') {
                $erro = $this->candidaturaModelo->editarCandidatura(
                    $id,
                    $candidatoId,
                    $dados['instituicao'],
                    $dados['curso'],
                    $dados['rendimento'],
                    $dados['iban']
                );
            }

            if ($erro === '') {
                redirecionar('candidaturas');
            }
        }

        render('candidato/editar', [
            'titulo' => 'Editar candidatura',
            'candidatura' => $candidatura,
            'dados' => $dados,
            'erro' => $erro,
        ]);
    }

    // Cancela uma candidatura (só aceita pedidos POST)
    public function cancelar()
    {
        Auth::exigirPerfil('candidato');

        if (!$this->pedidoEPost()) {
            redirecionar('candidaturas');
        }

        $id = $this->lerIdDoPedido('id');
        $erro = $this->candidaturaModelo->cancelarCandidatura($id, $this->candidatoAtual());

        if ($erro !== '') {
            $this->mostrarLista($erro);
            return;
        }

        redirecionar('candidaturas');
    }

    // Mostra a página de detalhe com os documentos e uma mensagem de erro se for preciso
    private function mostrarDetalhe($candidatura, $erro)
    {
        $documentos = $this->candidaturaModelo->listarDocumentos($candidatura['id']);

        render('candidato/detalhe', [
            'titulo' => 'Candidatura',
            'candidatura' => $candidatura,
            'documentos' => $documentos,
            'esclarecimentos' => $this->esclarecimentoModelo->listar($candidatura['id']),
            'historico' => $this->candidaturaModelo->historico($candidatura['id']),
            'podeAnexar' => $this->podeAnexarDocumentos($candidatura),
            'erro' => $erro,
        ]);
    }

    // Só se anexam documentos enquanto a candidatura não está num estado final
    // (inclui "em análise", porque o técnico pode pedir documentos adicionais)
    private function podeAnexarDocumentos($candidatura)
    {
        return !in_array($candidatura['estado'], Candidatura::ESTADOS_FINAIS, true);
    }

    // O candidato só vê os seus documentos e o técnico só os das candidaturas que lhe foram atribuídas
    private function podeVerDocumento($documento)
    {
        $perfil = Auth::perfil();
        $utilizadorId = Auth::id();

        if ($perfil === 'candidato') {
            return (int) $documento['id_candidato'] === $utilizadorId;
        }

        if ($perfil === 'tecnico') {
            return $documento['id_tecnico'] !== null && (int) $documento['id_tecnico'] === $utilizadorId;
        }

        return false;
    }

    // Mostra a página da lista, com uma mensagem de erro se for preciso
    private function mostrarLista($erro)
    {
        $candidaturas = $this->candidaturaModelo->listarCandidaturasDoCandidato($this->candidatoAtual());

        render('candidato/lista', [
            'titulo' => 'As minhas candidaturas',
            'candidaturas' => $candidaturas,
            'erro' => $erro,
        ]);
    }

    // Id do candidato que tem a sessão iniciada (nunca vem do formulário)
    private function candidatoAtual()
    {
        return Auth::id();
    }

    // Vê se o pedido veio de um formulário enviado (POST)
    private function pedidoEPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    // Lê um id do formulário (POST) ou do URL (GET) e garante que é um número
    private function lerIdDoPedido($nome)
    {
        if ($this->pedidoEPost()) {
            return (int) param($_POST, $nome);
        }

        return (int) param($_GET, $nome);
    }

    // Apanha os dados do formulário e arruma-os
    private function lerDadosDoFormulario()
    {
        $dados = [];

        $dados['instituicao'] = param($_POST, 'instituicao');
        $dados['curso'] = param($_POST, 'curso');

        // Aceita 1200,50 e transforma em 1200.50
        $dados['rendimento'] = str_replace(',', '.', param($_POST, 'rendimento'));

        // Tira os espaços e põe em maiúsculas (pt50 0002... passa a PT500002...)
        $dados['iban'] = strtoupper(str_replace(' ', '', param($_POST, 'iban')));

        return $dados;
    }

    // Resposta quando a candidatura ou a bolsa não existe (ou não é do candidato)
    private function naoEncontrada()
    {
        http_response_code(404);
        echo 'Não encontrado.';
    }
}