<?php
// Devolve a fotografia de perfil de um utilizador (só para quem tem sessão iniciada)
require_once __DIR__ . '/utils/session.php';
require_once __DIR__ . '/database/connection.php';
require_once __DIR__ . '/database/utilizador.php';

$id = isset($_GET['id']) && is_string($_GET['id']) && ctype_digit($_GET['id']) ? (int) $_GET['id'] : 0;
$dados = isset($_SESSION['id']) && $id > 0 ? getFotografia(getDatabaseConnection(), $id) : null;
$tipo = $dados !== null ? tipoImagemFoto($dados) : null;

if ($tipo === null) {
    http_response_code(404);
    exit();
}

header('Content-Type: ' . $tipo);
header('Content-Length: ' . strlen($dados));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-cache');
echo $dados;
