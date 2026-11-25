<?php
require_once __DIR__ . '/utils/session.php';
require_once __DIR__ . '/database/connection.php';
require_once __DIR__ . '/database/utilizador.php';
require_once __DIR__ . '/database/aula.php';
require_once __DIR__ . '/templates/common.php';

requerLogin();

$db = getDatabaseConnection();

// Pesquisa e filtros (GET): valores inválidos são ignorados
$pesquisa = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
if (strlen($pesquisa) > 100) $pesquisa = substr($pesquisa, 0, 100);
$idModalidade = isset($_GET['modalidade']) && is_string($_GET['modalidade']) && ctype_digit($_GET['modalidade']) ? (int) $_GET['modalidade'] : 0;
$ordem = isset($_GET['ordem']) && $_GET['ordem'] === 'avaliacao' ? 'avaliacao' : 'nome';
$temFiltros = $pesquisa !== '' || $idModalidade > 0 || $ordem !== 'nome';

$treinadores = getPerfisTreinadores($db, $pesquisa, $idModalidade, $ordem);
$modalidades = getModalidades($db);

drawHeader('Treinadores');
drawCabecalhoPagina('BioGym · Equipa', 'Os nossos treinadores', 'Conhece quem te acompanha e as modalidades em que têm certificação validada.');
?>

<section class="formulario largo">
    <h2>Procurar treinadores</h2>
    <form action="/treinadores.php" method="get">
        <fieldset class="campos">
            <p>
                <label for="q">Nome</label>
                <input type="search" id="q" name="q" value="<?= e($pesquisa) ?>" maxlength="100" placeholder="Ex.: Ana">
            </p>
            <p>
                <label for="modalidade">Modalidade</label>
                <select id="modalidade" name="modalidade">
                    <option value="">Todas</option>
                    <?php foreach ($modalidades as $m) { ?>
                        <option value="<?= e($m['id']) ?>"<?= $idModalidade === (int) $m['id'] ? ' selected' : '' ?>><?= e($m['nome']) ?></option>
                    <?php } ?>
                </select>
            </p>
            <p>
                <label for="ordem">Ordenar por</label>
                <select id="ordem" name="ordem">
                    <option value="nome">Nome</option>
                    <option value="avaliacao"<?= $ordem === 'avaliacao' ? ' selected' : '' ?>>Melhor avaliação</option>
                </select>
            </p>
        </fieldset>
        <span class="acoes">
            <button type="submit">Procurar</button>
            <?php if ($temFiltros) { ?>
                <a href="/treinadores.php" class="botao-secundario">Limpar</a>
            <?php } ?>
        </span>
    </form>
</section>

<?php if (empty($treinadores)) { ?>

    <p class="vazio"><?= $temFiltros ? 'Nenhum treinador corresponde à pesquisa.' : 'Ainda não há treinadores registados.' ?></p>

<?php } else { ?>

    <p class="resultados"><?= count($treinadores) ?> <?= count($treinadores) === 1 ? 'treinador' : 'treinadores' ?></p>

    <section class="grelha-aulas">
        <?php foreach ($treinadores as $t) { ?>
            <article class="cartao-aula perfil-treinador">
                <header>
                    <?php drawAvatar($t['id'], $t['temFoto'], $t['nome']); ?>
                    <h2><?= e($t['nome']) ?></h2>
                </header>

                <?php if ($t['biografia']) { ?>
                    <p><?= e($t['biografia']) ?></p>
                <?php } ?>

                <dl>
                    <div>
                        <dt>Modalidades</dt>
                        <dd><?= $t['modalidades'] ? e($t['modalidades']) : '—' ?></dd>
                    </div>
                    <div>
                        <dt>Avaliação</dt>
                        <dd>
                            <?php if ($t['totalNotas'] > 0) { ?>
                                <?= e(number_format($t['mediaNota'], 1, ',', '')) ?> / 5
                                (<?= e($t['totalNotas']) ?> <?= (int) $t['totalNotas'] === 1 ? 'avaliação' : 'avaliações' ?>)
                            <?php } else { ?>
                                Ainda sem avaliações
                            <?php } ?>
                        </dd>
                    </div>
                </dl>

                <footer>
                    <a href="/aulas.php?treinador=<?= e($t['id']) ?>" class="botao-secundario">Ver as aulas de <?= e(explode(' ', $t['nome'])[0]) ?></a>
                </footer>
            </article>
        <?php } ?>
    </section>

<?php } ?>

<?php drawFooter(); ?>
