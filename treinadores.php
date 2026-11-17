<?php
require_once __DIR__ . '/utils/session.php';
require_once __DIR__ . '/database/connection.php';
require_once __DIR__ . '/database/utilizador.php';
require_once __DIR__ . '/templates/common.php';

requerLogin();

$treinadores = getPerfisTreinadores(getDatabaseConnection());

drawHeader('Treinadores');
drawCabecalhoPagina('BioGym · Equipa', 'Os nossos treinadores', 'Conhece quem te acompanha e as modalidades em que têm certificação validada.');
?>

<?php if (empty($treinadores)) { ?>

    <p class="vazio">Ainda não há treinadores registados.</p>

<?php } else { ?>

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
            </article>
        <?php } ?>
    </section>

<?php } ?>

<?php drawFooter(); ?>
