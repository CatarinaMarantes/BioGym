<?php
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../database/utilizador.php';

session_start();
date_default_timezone_set('Europe/Lisbon');

if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// Termina o pedido se o token CSRF enviado não coincidir com o da sessão
function verificarCsrf() {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        http_response_code(403);
        die('Pedido inválido.');
    }
}

// Guarda uma mensagem para mostrar na próxima página ('sucesso' ou 'erro')
function adicionarMensagem($tipo, $texto) {
    $_SESSION['mensagens'][] = array('tipo' => $tipo, 'texto' => $texto);
}

// Redireciona para outra página e termina o script
function redirecionar($url) {
    header('Location: ' . $url);
    exit();
}

// ---------------------------------------------------------------------
// Permissões
// ---------------------------------------------------------------------

// Em cada pedido, volta a ler da base de dados o nome e os papéis de quem tem
// sessão iniciada. Assim, uma alteração de papéis feita por um administrador
// vale logo, e uma conta desativada deixa de ter acesso.
function atualizarSessao() {
    if (!isset($_SESSION['id'])) return;

    $utilizador = getUtilizadorPorId(getDatabaseConnection(), $_SESSION['id']);
    if (!$utilizador || !$utilizador['ativo']) {
        unset($_SESSION['id'], $_SESSION['nome'], $_SESSION['papeis']);
        session_regenerate_id(true);
        adicionarMensagem('erro', 'A sua sessão terminou: a conta já não está ativa.');
        return;
    }

    $_SESSION['nome'] = $utilizador['nome'];
    $_SESSION['papeis'] = getPapeis(getDatabaseConnection(), $utilizador['id']);
}

atualizarSessao();

function temPapel($papel) {
    return isset($_SESSION['papeis']) && in_array($papel, $_SESSION['papeis']);
}

// Obriga a ter sessão iniciada
function requerLogin() {
    if (!isset($_SESSION['id'])) {
        adicionarMensagem('erro', 'Tem de iniciar sessão.');
        redirecionar('/login.php');
    }
}

// Obriga a ter sessão iniciada e o papel indicado ('MEMBRO', 'TREINADOR' ou 'ADMIN')
function requerPapel($papel) {
    requerLogin();
    if (!temPapel($papel)) {
        adicionarMensagem('erro', 'Não tem permissão para aceder a essa página.');
        redirecionar('/dashboard.php');
    }
}

// Arranque comum a todas as actions: só POST, token CSRF e papel exigido
function iniciarAction($papel) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirecionar('/index.php');
    verificarCsrf();
    requerPapel($papel);
}

// Como iniciarAction, mas para ficheiros que tratam várias operações.
// $operacoes: array(operação => papel exigido). Devolve a operação pedida.
function iniciarOperacao($operacoes) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirecionar('/index.php');
    verificarCsrf();
    $op = isset($_POST['op']) && is_string($_POST['op']) ? $_POST['op'] : '';
    if (!isset($operacoes[$op])) {
        adicionarMensagem('erro', 'Operação inválida.');
        redirecionar('/dashboard.php');
    }
    requerPapel($operacoes[$op]);
    return $op;
}

// Guarda o resultado de uma operação (null = correu bem) e volta para $destino
function terminar($erro, $sucesso, $destino) {
    if ($erro === null) {
        adicionarMensagem('sucesso', $sucesso);
    } else {
        adicionarMensagem('erro', $erro);
    }
    redirecionar($destino);
}

// Guarda uma lista de erros de validação e volta para $destino (se houver erros)
function terminarSeErros($erros, $destino) {
    if (empty($erros)) return;
    foreach ($erros as $erro) adicionarMensagem('erro', $erro);
    redirecionar($destino);
}

// ---------------------------------------------------------------------
// Leitura e validação de dados
// ---------------------------------------------------------------------

// Texto enviado por POST, sem espaços nas pontas ('' se não existir ou não for texto)
function post($nome) {
    $valor = isset($_POST[$nome]) ? $_POST[$nome] : '';
    return is_string($valor) ? trim($valor) : '';
}

// Inteiro positivo enviado por POST (0 se for inválido)
function postInt($nome) {
    $valor = post($nome);
    return ctype_digit($valor) ? (int) $valor : 0;
}

// Número decimal (aceita vírgula). Devolve null se for inválido.
function lerNumero($valor) {
    $valor = str_replace(',', '.', trim($valor));
    return is_numeric($valor) ? (float) $valor : null;
}

function dataValida($data) {
    $d = DateTime::createFromFormat('Y-m-d', $data);
    return $d && $d->format('Y-m-d') === $data;
}

function horaValida($hora) {
    return (bool) preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $hora);
}

// true se a data e hora (Y-m-d, H:i) ainda não aconteceram
function dataHoraNoFuturo($data, $hora) {
    return $data . ' ' . $hora > date('Y-m-d H:i');
}

// true se a data e hora (Y-m-d, H:i) já aconteceram ou estão a acontecer
function dataHoraJaComecou($data, $hora) {
    return !dataHoraNoFuturo($data, $hora);
}
