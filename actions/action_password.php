<?php
// Alteração da palavra-passe de quem tem sessão iniciada (qualquer papel)
require_once __DIR__ . '/../utils/session.php';
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../database/utilizador.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirecionar('/perfil.php');
verificarCsrf();
requerLogin();

// As palavras-passe não levam trim(): os espaços fazem parte delas
$atual       = isset($_POST['passwordAtual']) && is_string($_POST['passwordAtual']) ? $_POST['passwordAtual'] : '';
$nova        = isset($_POST['passwordNova']) && is_string($_POST['passwordNova']) ? $_POST['passwordNova'] : '';
$confirmacao = isset($_POST['confirmacao']) && is_string($_POST['confirmacao']) ? $_POST['confirmacao'] : '';

$db = getDatabaseConnection();
$utilizador = getUtilizadorPorId($db, $_SESSION['id']);

$erros = array();
if (!password_verify($atual, $utilizador['passwordHash'])) $erros[] = 'A palavra-passe atual está incorreta.';
if (strlen($nova) < 8) $erros[] = 'A nova palavra-passe deve ter pelo menos 8 caracteres.';
if ($nova !== $confirmacao) $erros[] = 'As novas palavras-passe não coincidem.';
if (empty($erros) && $nova === $atual) $erros[] = 'A nova palavra-passe tem de ser diferente da atual.';
terminarSeErros($erros, '/perfil.php');

alterarPassword($db, $_SESSION['id'], $nova);

// Novo id de sessão depois de mudar as credenciais
session_regenerate_id(true);
terminar(null, 'Palavra-passe alterada.', '/perfil.php');
