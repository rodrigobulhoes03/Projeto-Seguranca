<?php

// Tudo o que é da área do técnico
class TecnicoController
{
    private $candidaturaModelo;
    private $bolsaModelo;
    private $esclarecimentoModelo;
    private $observacaoModelo;

    public function __construct($pdo)
    {
        $this->candidaturaModelo = new Candidatura($pdo);
        $this->bolsaModelo = new Bolsa($pdo);
        $this->esclarecimentoModelo = new Esclarecimento($pdo);
        $this->observacaoModelo = new Observacao($pdo);
    }

    // Lista das candidaturas atribuídas ao técnico, com filtros
    public function lista()
    {
        Auth::exigirPerfil('tecnico');

        $filtros = [
            'nome' => param($_GET, 'nome'),
            'bolsa' => (int) param($_GET, 'bolsa'),
            'data' => param($_GET, 'data'),
            'estado' => param($_GET, 'estado'),
        ];

        // Valores fora do esperado são ignorados
        if (!in_array($filtros['estado'], Candidatura::ESTADOS, true)) {
            $filtros['estado'] = '';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtros['data'])) {
            $filtros['data'] = '';
        }

        render('tecnico/lista', [
            'titulo' => 'Candidaturas',
            'candidaturas' => $this->candidaturaModelo->listarDoTecnico(Auth::id(), $filtros),
            'bolsas' => $this->bolsaModelo->listar(''),
            'filtros' => $filtros,
            'estados' => Candidatura::ESTADOS,
            'largo' => true,
        ]);
    }

    // Detalhe de uma candidatura atribuída ao técnico
    public function detalhe()
    {
        Auth::exigirPerfil('tecnico');

        $id = (int) param($_GET, 'id');
        $candidatura = $this->candidaturaModelo->buscarDoTecnico($id, Auth::id());

        if (!$candidatura) {
            http_response_code(404);
            echo 'Candidatura não encontrada.';
            return;
        }

        render('tecnico/detalhe', [
            'titulo' => 'Candidatura',
            'candidatura' => $candidatura,
            'historico' => $this->candidaturaModelo->historico($id),
            'transicoes' => Candidatura::TRANSICOES[$candidatura['estado']] ?? [],
            'documentos' => $this->candidaturaModelo->listarDocumentos($id),
            'esclarecimentos' => $this->esclarecimentoModelo->listar($id),
            'observacoes' => $this->observacaoModelo->listar($id),
            'podePedirEsclarecimento' => !in_array($candidatura['estado'], Candidatura::ESTADOS_FINAIS, true),
            'mensagem' => $this->lerMensagem(),
            'largo' => true,
        ]);
    }

    // Muda o estado de uma candidatura (só por POST)
    public function mudarEstado()
    {
        $id = $this->lerIdPost();

        $erro = $this->candidaturaModelo->mudarEstadoDoTecnico($id, Auth::id(), param($_POST, 'estado'));

        $this->voltar($id, $erro !== '' ? $erro : 'Estado atualizado.');
    }

    // Pede um esclarecimento ao candidato (só por POST)
    public function pedirEsclarecimento()
    {
        $id = $this->lerIdPost();
        $candidatura = $this->candidaturaAtribuida($id);

        if (!$candidatura) {
            $this->voltar($id, 'Candidatura não encontrada ou não atribuída a si.');
        }

        if (in_array($candidatura['estado'], Candidatura::ESTADOS_FINAIS, true)) {
            $this->voltar($id, 'Esta candidatura já está num estado final.');
        }

        $pergunta = param($_POST, 'pergunta');
        $erro = Validador::texto($pergunta, 'a pergunta');

        if ($erro === '') {
            $this->esclarecimentoModelo->criar($id, Auth::id(), $pergunta);
            $erro = 'Pedido de esclarecimento enviado.';
        }

        $this->voltar($id, $erro);
    }

    // Regista uma observação interna (só por POST)
    public function adicionarObservacao()
    {
        $id = $this->lerIdPost();

        if (!$this->candidaturaAtribuida($id)) {
            $this->voltar($id, 'Candidatura não encontrada ou não atribuída a si.');
        }

        $texto = param($_POST, 'texto');
        $erro = Validador::texto($texto, 'a observação');

        if ($erro === '') {
            $this->observacaoModelo->criar($id, Auth::id(), $texto);
            $erro = 'Observação registada.';
        }

        $this->voltar($id, $erro);
    }

    // Garante perfil de técnico e método POST, e devolve o id da candidatura enviado no formulário
    private function lerIdPost()
    {
        Auth::exigirPerfil('tecnico');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirecionar('tecnico');
        }

        return (int) param($_POST, 'id');
    }

    // Devolve a candidatura só se estiver atribuída ao técnico que tem a sessão iniciada (ou false)
    private function candidaturaAtribuida($id)
    {
        return $this->candidaturaModelo->buscarDoTecnico($id, Auth::id());
    }

    // Guarda uma mensagem e volta ao detalhe da candidatura
    private function voltar($id, $mensagem)
    {
        $_SESSION['mensagem'] = $mensagem;
        redirecionar('tecnico_candidatura&id=' . $id);
    }

    // Lê (e apaga) a mensagem guardada pela ação anterior
    private function lerMensagem()
    {
        $mensagem = $_SESSION['mensagem'] ?? '';
        unset($_SESSION['mensagem']);

        return $mensagem;
    }
}
