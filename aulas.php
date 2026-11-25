<?php
require_once __DIR__ . '/utils/session.php';
require_once __DIR__ . '/database/connection.php';
require_once __DIR__ . '/database/aula.php';
require_once __DIR__ . '/templates/common.php';

$db = getDatabaseConnection();

// Filtros (GET): só se aceitam valores válidos, o resto é ignorado
function filtroInt($nome) {
    return isset($_GET[$nome]) && is_string($_GET[$nome]) && ctype_digit($_GET[$nome]) ? (int) $_GET[$nome] : 0;
}
$filtros = array(
    'modalidade' => filtroInt('modalidade'),
    'treinador'  => filtroInt('treinador'),
    'nivel'      => isset($_GET['nivel']) && in_array($_GET['nivel'], array('INICIANTE', 'INTERMEDIO', 'AVANCADO'), true) ? $_GET['nivel'] : '',
    'data'       => isset($_GET['data']) && is_string($_GET['data']) && dataValida($_GET['data']) ? $_GET['data'] : ''
);
$temFiltros = $filtros['modalidade'] || $filtros['treinador'] || $filtros['nivel'] !== '' || $filtros['data'] !== '';

// Paginação: 9 aulas por página (3 linhas de 3 cartões)
$porPagina = 9;
$totalAulas = contarAulasDisponiveis($db, $filtros);
$totalPaginas = (int) ceil($totalAulas / $porPagina);
$pagina = paginaAtual($totalPaginas);
$aulas = getAulasDisponiveis($db, $filtros, $pagina, $porPagina);

// Filtros escolhidos, para manter nas ligações da paginação
$parametrosFiltro = array();
foreach ($filtros as $chave => $valor) {
    if ($valor) $parametrosFiltro[$chave] = $valor;
}
$modalidades = getModalidades($db);
$treinadores = getTreinadores($db);

$ehMembro = temPapel('MEMBRO');
$estados = $ehMembro ? getEstadosInscricao($db, $_SESSION['id']) : array();

drawHeader('Aulas');
?>

<section class="painel-hero aulas-hero">

    <div class="painel-hero-conteudo">

        <p class="painel-hero-etiqueta">
            BioGym · Atividades
        </p>

        <h1>Explora as nossas aulas</h1>

        <p class="painel-hero-descricao">
            Encontra a atividade ideal para ti
            e desafia os teus limites.
        </p>

    </div>

    <div class="painel-hero-ilustracao" aria-hidden="true">
        <?php drawIconePainel('inscricoes'); ?>
    </div>

</section>

<section class="formulario largo filtro-aulas">

    <h2 class="filtro-aulas-titulo">

        <svg viewBox="0 0 24 24"
             width="22"
             height="22"
             fill="none"
             stroke="currentColor"
             stroke-width="1.8"
             stroke-linecap="round"
             aria-hidden="true">

            <path d="M4 6h16M4 12h16M4 18h16"/>
            <circle cx="8" cy="6" r="2" fill="white"/>
            <circle cx="16" cy="12" r="2" fill="white"/>
            <circle cx="10" cy="18" r="2" fill="white"/>

        </svg>

        Filtrar aulas

    </h2>
    <form action="/aulas.php" method="get">
        <fieldset class="campos">
            <p>
                <label for="modalidade">Modalidade</label>
                <select id="modalidade" name="modalidade">
                    <option value="">Todas</option>
                    <?php foreach ($modalidades as $m) { ?>
                        <option value="<?= e($m['id']) ?>"<?= $filtros['modalidade'] === (int) $m['id'] ? ' selected' : '' ?>><?= e($m['nome']) ?></option>
                    <?php } ?>
                </select>
            </p>
            <p>
                <label for="nivel">Nível</label>
                <select id="nivel" name="nivel">
                    <option value="">Todos</option>
                    <?php foreach (array('INICIANTE', 'INTERMEDIO', 'AVANCADO') as $n) { ?>
                        <option value="<?= e($n) ?>"<?= $filtros['nivel'] === $n ? ' selected' : '' ?>><?= e(nomeNivel($n)) ?></option>
                    <?php } ?>
                </select>
            </p>
            <p>
                <label for="data">Data</label>
                <input type="date" id="data" name="data" value="<?= e($filtros['data']) ?>">
            </p>
            <p>
                <label for="treinador">Treinador</label>
                <select id="treinador" name="treinador">
                    <option value="">Todos</option>
                    <?php foreach ($treinadores as $t) { ?>
                        <option value="<?= e($t['id']) ?>"<?= $filtros['treinador'] === (int) $t['id'] ? ' selected' : '' ?>><?= e($t['nome']) ?></option>
                    <?php } ?>
                </select>
            </p>
        </fieldset>
        <span class="acoes">
            <button type="submit">Filtrar</button>
            <?php if ($temFiltros) { ?>
                <a href="/aulas.php" class="botao-secundario">Limpar filtros</a>
            <?php } ?>
        </span>
    </form>
</section>

<?php if (empty($aulas)) { ?>

    <p class="vazio"><?= $temFiltros ? 'Nenhuma aula corresponde aos filtros escolhidos.' : 'Não existem aulas agendadas.' ?></p>

<?php } else { ?>

    <p class="resultados"><?= $totalAulas ?> <?= $totalAulas === 1 ? 'aula encontrada' : 'aulas encontradas' ?><?= $totalPaginas > 1 ? ' · página ' . $pagina . ' de ' . $totalPaginas : '' ?></p>

    <section class="grelha-aulas">

        <?php foreach ($aulas as $aula) {
            $vagasDisponiveis = max(0, $aula['vagas'] - $aula['totalInscritos']);
            $estado = isset($estados[$aula['id']]) ? $estados[$aula['id']] : null;
            $euDou = isset($_SESSION['id']) && (int) $aula['idTreinador'] === (int) $_SESSION['id'];
        ?>

            <article class="cartao-aula">

                <div class="aula-ilustracao" aria-hidden="true">

                    <?php
                    $iconesModalidade = array(
                        'Alongamentos'    => 'alongamentos',
                        'Boxe'            => 'boxe',
                        'HIIT'            => 'hiit',
                        'Musculação'      => 'treino',
                        'Pilates'         => 'pilates',
                        'Spinning'        => 'bicicleta',
                        'Treino Funcional' => 'kettlebell',
                        'Yoga'            => 'yoga',
                        'Zumba'           => 'danca'
                    );

                    $iconeAula = isset($iconesModalidade[$aula['modalidade']])
                        ? $iconesModalidade[$aula['modalidade']]
                        : 'treino';

                    drawIconePainel($iconeAula);
                    ?>

                </div>

                <header>
                    <h2><?= e($aula['modalidade']) ?></h2>
                    <span class="etiqueta-nivel"><?= e(nomeNivel($aula['nivel'])) ?></span>
                </header>

                <dl>
                    <div>
                        <dt>
                            <?php drawIconePainel('calendario'); ?>
                            Data
                        </dt>
                        <dd><time datetime="<?= e($aula['data']) ?>"><?= formatarData($aula['data']) ?></time></dd>
                    </div>
                    <div>
                        <dt>
                            <?php drawIconePainel('relogio'); ?>
                            Horário
                        </dt>
                        <dd><?= e($aula['horaInicio']) ?>–<?= e($aula['horaFim']) ?></dd>
                    </div>
                    <div>
                        <dt>
                            <?php drawIconePainel('perfil'); ?>
                            Treinador
                        </dt>
                        <dd><?= e($aula['treinador']) ?></dd>
                    </div>
                    <div>
                        <dt>
                            <?php drawIconePainel('localizacao'); ?>
                            Sala
                        </dt>
                        <dd><?= e($aula['sala']) ?></dd>
                    </div>
                </dl>

                <footer>
                    <?php if ($vagasDisponiveis > 0) { ?>
                        <p class="vagas-disponiveis"><?= $vagasDisponiveis ?> <?= $vagasDisponiveis === 1 ? 'vaga disponível' : 'vagas disponíveis' ?></p>
                    <?php } else { ?>
                        <p class="sem-vagas">Aula lotada</p>
                    <?php } ?>

                    <?php if ($euDou) { ?>
                        <?php drawEstado('Leciona esta aula', 'neutro'); ?>
                    <?php } elseif ($ehMembro && $estado === 'ATIVA') { ?>
                        <?php drawEstado('Estás inscrito', 'sucesso'); ?>
                        <?php drawFormAcao('/actions/action_gestao.php', array('op' => 'cancelar', 'idAula' => $aula['id'], 'origem' => 'aulas'), 'Cancelar inscrição', 'botao-perigo'); ?>
                    <?php } elseif ($ehMembro && $vagasDisponiveis > 0) { ?>
                        <?php drawFormAcao('/actions/action_gestao.php', array('op' => 'inscrever', 'idAula' => $aula['id'], 'origem' => 'aulas'), 'Inscrever-me'); ?>
                    <?php } elseif (!isset($_SESSION['id'])) { ?>
                        <a href="/login.php" class="botao-secundario">Entrar para te inscreveres</a>
                    <?php } ?>
                </footer>

            </article>

        <?php } ?>

    </section>

    <?php drawPaginacao('/aulas.php', $parametrosFiltro, $pagina, $totalPaginas); ?>

<?php } ?>

<?php drawFooter(); ?>
