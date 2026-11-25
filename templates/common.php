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
    <link rel="stylesheet"
        href="/css/style.css?v=<?= filemtime(__DIR__ . '/../css/style.css') ?>">
</head>
<body>
    <header>
        <a href="/index.php" class="logo">
            <img src="/images/logo-biogym.png"
                alt="BioGym"
                class="header-logo">
        </a>
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
function drawCartaoPainel($titulo, $url, $icone = 'perfil', $descricao = '') { ?>
    <a href="<?= e($url) ?>" class="painel-cartao">

        <span class="painel-icone">
            <?php drawIconePainel($icone); ?>
        </span>

        <span class="painel-cartao-conteudo">
            <strong><?= e($titulo) ?></strong>

            <?php if ($descricao !== '') { ?>
                <small><?= e($descricao) ?></small>
            <?php } ?>
        </span>

        <span class="painel-seta" aria-hidden="true">
            &rarr;
        </span>

    </a>
<?php }

function drawIconePainel($icone) {

    $icones = array(

        'alongamentos' =>
        '<circle cx="12" cy="4" r="2"/>
        <path d="M3 8l9 3 9-3
                M12 7v8l-7 6
                M12 15l7 6"/>',

        'boxe' =>
            '<path d="M8 16C5 14 3 11 4 8
                    C4 4 7 2 11 2H15
                    C19 2 21 5 21 9V13
                    L17 17V22H8Z"/>
            <path d="M8 17h9
                    M8 19h9
                    M4 9c3-2 6 0 6 3v2"/>',

        'hiit' =>
            '<path d="M13 2L4 14h7
                    l-1 8 10-13h-8z"/>',

        'pilates' =>
            '<circle cx="12" cy="12" r="9"/>
            <path d="M3 12c5-4 13-4 18 0
                    M12 3c-4 5-4 13 0 18"/>',

        'kettlebell' =>
            '<path d="M8 9V6a4 4 0 0 1 8 0v3"/>
            <path d="M8 9h8
                    c3 2 5 5 5 8
                    0 4-4 5-9 5
                    s-9-1-9-5
                    c0-3 2-6 5-8z"/>',

        'danca' =>
            '<circle cx="14" cy="4" r="2"/>
            <path d="M4 4l5 6 6-2 6-5
                    M12 9l-2 7-6 5
                    M10 16l7 1 3 5"/>',

        'yoga' =>
        '<path d="M12 21C5 21 2 16 2 10
                C7 10 11 14 12 21
                C13 14 17 10 22 10
                C22 16 19 21 12 21Z"/>
        <path d="M8 12C8 8 10 5 12 3
                C14 5 16 8 16 12"/>',

        'bicicleta' =>
            '<circle cx="5.5" cy="17.5" r="3.5"/>
            <circle cx="18.5" cy="17.5" r="3.5"/>
            <path d="M5.5 17.5L10 9l4 8.5H5.5
                    M14 17.5L18 6h-3
                    M8 9h4"/>',

        'musica' =>
            '<path d="M9 18V5l12-2v13"/>
            <ellipse cx="6" cy="18" rx="3" ry="2"/>
            <ellipse cx="18" cy="16" rx="3" ry="2"/>',

        'relogio' =>
            '<circle cx="12" cy="12" r="9"/>
            <path d="M12 7v5l3 2"/>',

        'localizacao' =>
            '<path d="M20 10c0 6-8 12-8 12
                    S4 16 4 10a8 8 0 1 1 16 0Z"/>
            <circle cx="12" cy="10" r="3"/>',

        'calendario' =>
            '<rect x="3" y="5" width="18" height="16" rx="2"/>
             <path d="M16 3v4M8 3v4M3 10h18"/>',

        'inscricoes' =>
            '<rect x="3" y="5" width="18" height="16" rx="2"/>
             <path d="M16 3v4M8 3v4M3 10h18M8 15l3 3 5-5"/>',

        'treino' =>
            '<path d="M6 7v10M3 9v6M18 7v10M21 9v6M6 12h12"/>',

        'avaliacao' =>
            '<path d="M2 12h5l3-8 4 16 3-8h5"/>',

        'plano' =>
            '<rect x="5" y="4" width="14" height="18" rx="2"/>
             <rect x="9" y="2" width="6" height="4" rx="1"/>
             <path d="M9 11h6M9 15h6M9 19h4"/>',

        'equipa' =>
            '<circle cx="9" cy="7" r="3"/>
             <path d="M2 21v-3a7 7 0 0 1 14 0v3
                      M17 4a3 3 0 0 1 0 6
                      M18 14a5 5 0 0 1 4 5v2"/>',

        'equipamento' =>
            '<rect x="3" y="3" width="18" height="18" rx="3"/>
             <path d="M7 12h10M12 7v10"/>',

        'perfil' =>
            '<circle cx="12" cy="8" r="4"/>
             <path d="M4 22v-2a8 8 0 0 1 16 0v2"/>',

        'peso' =>
            '<rect x="3" y="3" width="18" height="18" rx="4"/>
             <path d="M7 9a5 5 0 0 1 10 0M12 9l2-3"/>'
    );

    $desenho = isset($icones[$icone])
        ? $icones[$icone]
        : $icones['perfil'];
    ?>

    <svg viewBox="0 0 24 24"
         width="24"
         height="24"
         fill="none"
         stroke="currentColor"
         stroke-width="1.7"
         stroke-linecap="round"
         stroke-linejoin="round"
         aria-hidden="true"
         focusable="false">

        <?= $desenho ?>

    </svg>

<?php }

// Separadores de uma página com várias secções (admin.php, treinador.php)
function drawSeparadores($pagina, $seccoes, $atual) { ?>
<nav class="separadores" aria-label="Secções">
    <?php foreach ($seccoes as $chave => $s) { ?>
        <a href="<?= e($pagina) ?>?sec=<?= e($chave) ?>"<?= $chave === $atual ? ' class="ativo" aria-current="page"' : '' ?>><?= e($s[0]) ?></a>
    <?php } ?>
</nav>
<?php }

// Lê o número da página pedida por GET (?pagina=), entre 1 e $totalPaginas
function paginaAtual($totalPaginas) {
    $pagina = isset($_GET['pagina']) && is_string($_GET['pagina']) && ctype_digit($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
    return max(1, min($pagina, max(1, $totalPaginas)));
}

// Ligações de paginação. $parametros: os outros parâmetros GET a manter
// (por exemplo, os filtros), para não se perderem ao mudar de página.
function drawPaginacao($url, $parametros, $pagina, $totalPaginas) {
    if ($totalPaginas <= 1) return;
    $ligacao = function ($p) use ($url, $parametros) {
        $parametros['pagina'] = $p;
        return $url . '?' . http_build_query($parametros);
    }; ?>
<nav class="paginacao" aria-label="Páginas">
    <?php if ($pagina > 1) { ?>
        <a href="<?= e($ligacao($pagina - 1)) ?>" rel="prev">&larr; Anterior</a>
    <?php } ?>
    <?php for ($p = 1; $p <= $totalPaginas; $p++) { ?>
        <?php if ($p === $pagina) { ?>
            <span aria-current="page"><?= $p ?></span>
        <?php } else { ?>
            <a href="<?= e($ligacao($p)) ?>"><?= $p ?></a>
        <?php } ?>
    <?php } ?>
    <?php if ($pagina < $totalPaginas) { ?>
        <a href="<?= e($ligacao($pagina + 1)) ?>" rel="next">Seguinte &rarr;</a>
    <?php } ?>
</nav>
<?php }
