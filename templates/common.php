<?php
// Escapa texto para HTML
function e($texto) {
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

function formatarData($data) {
    return date('d/m/Y', strtotime($data));
}

function drawHeader($titulo) { ?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · BioGym</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <header>
        <a href="/index.php" class="logo">BioGym</a>
        <nav>
            <a href="/aulas.php">Aulas</a>
            <?php if (isset($_SESSION['id'])) { ?>
                <a href="/dashboard.php">Início</a>
                <a href="/treinadores.php">Treinadores</a>
                <a href="/equipamentos.php">Equipamentos</a>
                <a href="/perfil.php"><?= e($_SESSION['nome']) ?></a>
                <form action="/actions/action_logout.php" method="post">
                    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                    <button type="submit">Sair</button>
                </form>
            <?php } else { ?>
                <a href="/login.php">Entrar</a>
                <a href="/registo.php">Registar</a>
            <?php } ?>
        </nav>
    </header>
    <main>
        <?php drawMensagens(); ?>
<?php }

function drawMensagens() {
    if (empty($_SESSION['mensagens'])) return;
    foreach ($_SESSION['mensagens'] as $mensagem) { ?>
        <p class="mensagem <?= e($mensagem['tipo']) ?>"><?= e($mensagem['texto']) ?></p>
    <?php }
    unset($_SESSION['mensagens']);
}

function drawFooter() { ?>
    </main>
    <footer>
        <p>&copy; 2026 BioGym · Projeto ESIN</p>
    </footer>
</body>
</html>
<?php }

// Cabeçalho de uma página: etiqueta pequena, título e descrição
function drawCabecalhoPagina($etiqueta, $titulo, $descricao = '') { ?>
<header class="cabecalho-pagina">
    <p class="etiqueta"><?= e($etiqueta) ?></p>
    <h1><?= e($titulo) ?></h1>
    <?php if ($descricao !== '') { ?>
        <p><?= e($descricao) ?></p>
    <?php } ?>
</header>
<?php }

// Campo escondido com o token CSRF (para usar dentro de formulários POST)
function drawCsrf() { ?>
<input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
<?php }

// Pequeno formulário de um só botão (inscrever, cancelar, confirmar, ...)
// $campos: array(nome => valor) enviados como campos escondidos
function drawFormAcao($action, $campos, $texto, $classe = 'botao') { ?>
<form action="<?= e($action) ?>" method="post">
    <?php drawCsrf(); ?>
    <?php foreach ($campos as $nome => $valor) { ?>
        <input type="hidden" name="<?= e($nome) ?>" value="<?= e($valor) ?>">
    <?php } ?>
    <button type="submit" class="<?= e($classe) ?>"><?= e($texto) ?></button>
</form>
<?php }

// Etiqueta colorida de estado. $tipo: sucesso, aviso, erro ou neutro
function drawEstado($texto, $tipo) { ?>
<span class="estado estado-<?= e($tipo) ?>"><?= e($texto) ?></span>
<?php }

function nomeNivel($nivel) {
    $niveis = array('INICIANTE' => 'Iniciante', 'INTERMEDIO' => 'Intermédio', 'AVANCADO' => 'Avançado');
    return isset($niveis[$nivel]) ? $niveis[$nivel] : $nivel;
}

// Estado de uma sessão de PT: devolve array(texto, tipo)
function estadoSessaoPT($estado) {
    $estados = array(
        'PENDENTE'   => array('Pendente', 'aviso'),
        'CONFIRMADA' => array('Confirmada', 'sucesso'),
        'CANCELADA'  => array('Cancelada', 'erro'),
        'CONCLUIDA'  => array('Concluída', 'neutro')
    );
    return isset($estados[$estado]) ? $estados[$estado] : array($estado, 'neutro');
}

// Estado de um equipamento: devolve array(texto, tipo)
function estadoEquipamento($estado) {
    $estados = array(
        'DISPONIVEL'    => array('Disponível', 'sucesso'),
        'EM_USO'        => array('Em uso', 'aviso'),
        'EM_MANUTENCAO' => array('Em manutenção', 'erro')
    );
    return isset($estados[$estado]) ? $estados[$estado] : array($estado, 'neutro');
}

// Fotografia de perfil, ou as iniciais do nome se o utilizador não tiver fotografia
function drawAvatar($idUtilizador, $temFoto, $nome) {
    if ($temFoto) { ?>
<img class="avatar" src="/foto.php?id=<?= e($idUtilizador) ?>" alt="Fotografia de <?= e($nome) ?>" width="72" height="72">
<?php } else {
        // Iniciais do primeiro e do último nome (sem depender da extensão mbstring)
        preg_match_all('/\p{L}+/u', $nome, $palavras);
        $palavras = $palavras[0];
        $iniciais = '';
        if (!empty($palavras)) {
            preg_match('/^./su', $palavras[0], $primeira);
            $iniciais = $primeira[0];
            if (count($palavras) > 1) {
                preg_match('/^./su', end($palavras), $ultima);
                $iniciais .= $ultima[0];
            }
            $iniciais = strtoupper($iniciais);
        } ?>
<span class="avatar" role="img" aria-label="Sem fotografia de <?= e($nome) ?>"><?= e($iniciais) ?></span>
<?php }
}

// Notas de 1 a 5 (um grupo de botões de opção) para os formulários de avaliação
function drawNota($nome, $rotulo) { ?>
<fieldset class="nota">
    <legend><?= e($rotulo) ?></legend>
    <?php for ($i = 1; $i <= 5; $i++) { ?>
        <label><input type="radio" name="<?= e($nome) ?>" value="<?= $i ?>" required> <?= $i ?></label>
    <?php } ?>
</fieldset>
<?php }

// Cartão de ligação da dashboard
function drawCartaoPainel($titulo, $url) { ?>
<a href="<?= e($url) ?>" class="painel-cartao"><?= e($titulo) ?> &rarr;</a>
<?php }

// Separadores de uma página com várias secções (admin.php, treinador.php)
function drawSeparadores($pagina, $seccoes, $atual) { ?>
<nav class="separadores" aria-label="Secções">
    <?php foreach ($seccoes as $chave => $s) { ?>
        <a href="<?= e($pagina) ?>?sec=<?= e($chave) ?>"<?= $chave === $atual ? ' class="ativo" aria-current="page"' : '' ?>><?= e($s[0]) ?></a>
    <?php } ?>
</nav>
<?php }
