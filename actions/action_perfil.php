<?php
// Atualiza o perfil de quem tem sessão iniciada (qualquer papel)
require_once __DIR__ . '/../utils/session.php';
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../database/utilizador.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirecionar('/perfil.php');
verificarCsrf();
requerLogin();

$nome      = post('nome');
$telemovel = post('telemovel');
$biografia = post('biografia');

$erros = array();
if ($nome === '') $erros[] = 'O nome é obrigatório.';
if (!preg_match('/^9[0-9]{8}$/', $telemovel)) $erros[] = 'O telemóvel deve ter 9 dígitos e começar por 9.';
if (strlen($biografia) > 500) $erros[] = 'A biografia não pode ter mais de 500 caracteres.';

// Fotografia: null = manter, '' = remover, outro valor = nova imagem
$foto = null;
if (post('removerFoto') === '1') {
    $foto = '';
}
if (isset($_FILES['fotografia']) && $_FILES['fotografia']['error'] !== UPLOAD_ERR_NO_FILE) {
    $ficheiro = $_FILES['fotografia'];
    if ($ficheiro['error'] !== UPLOAD_ERR_OK) {
        $erros[] = 'Não foi possível carregar a fotografia.';
    } elseif ($ficheiro['size'] > 1000000) {
        $erros[] = 'A fotografia não pode ter mais de 1 MB.';
    } elseif (!is_uploaded_file($ficheiro['tmp_name'])) {
        $erros[] = 'Ficheiro inválido.';
    } else {
        $dados = file_get_contents($ficheiro['tmp_name']);
        $info = @getimagesizefromstring($dados);
        if (tipoImagemFoto($dados) === null) {
            $erros[] = 'A fotografia tem de ser uma imagem JPEG, PNG ou WebP.';
        } elseif ($info[0] > 4000 || $info[1] > 4000) {
            $erros[] = 'A fotografia não pode ter mais de 4000 pixéis de largura ou altura.';
        } else {
            $foto = $dados;
        }
    }
}

terminarSeErros($erros, '/perfil.php');

atualizarPerfil(getDatabaseConnection(), $_SESSION['id'], $nome, $telemovel, $biografia, $foto);
$_SESSION['nome'] = $nome;
terminar(null, 'Perfil atualizado.', '/perfil.php');
