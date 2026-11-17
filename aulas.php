<?php
require_once __DIR__ . '/utils/session.php';
require_once __DIR__ . '/database/connection.php';
require_once __DIR__ . '/database/aula.php';
require_once __DIR__ . '/templates/common.php';

$db = getDatabaseConnection();
$aulas = getAulasDisponiveis($db);

$niveis = array(
    'INICIANTE' => 'Iniciante',
    'INTERMEDIO' => 'Intermédio',
    'AVANCADO' => 'Avançado'
);

drawHeader('Aulas');
?>

<header class="cabecalho-pagina">
    <p class="etiqueta">BioGym · Atividades</p>
    <h1>Explora as nossas aulas</h1>
    <p>Encontra a atividade ideal para ti.</p>
</header>

<?php if (empty($aulas)) { ?>

    <p>Não existem aulas agendadas.</p>

<?php } else { ?>

    <section class="grelha-aulas">

        <?php foreach ($aulas as $aula) {
            $vagasDisponiveis = max(0, $aula['vagas'] - $aula['totalInscritos']);
        ?>

            <article class="cartao-aula">

                <header>
                    <h2><?= htmlspecialchars($aula['modalidade']) ?></h2>
                    <span class="etiqueta-nivel"><?= htmlspecialchars($niveis[$aula['nivel']]) ?></span>
                </header>

                <dl>
                    <div>
                        <dt>Data</dt>
                        <dd><time datetime="<?= htmlspecialchars($aula['data']) ?>"><?= date('d/m/Y', strtotime($aula['data'])) ?></time></dd>
                    </div>
                    <div>
                        <dt>Horário</dt>
                        <dd><?= htmlspecialchars($aula['horaInicio']) ?>–<?= htmlspecialchars($aula['horaFim']) ?></dd>
                    </div>
                    <div>
                        <dt>Treinador</dt>
                        <dd><?= htmlspecialchars($aula['treinador']) ?></dd>
                    </div>
                    <div>
                        <dt>Sala</dt>
                        <dd><?= htmlspecialchars($aula['sala']) ?></dd>
                    </div>
                </dl>

                <footer>
                    <?php if ($vagasDisponiveis > 0) { ?>
                        <span class="vagas-disponiveis"><?= $vagasDisponiveis ?> vagas disponíveis</span>
                    <?php } else { ?>
                        <span class="sem-vagas">Aula lotada</span>
                    <?php } ?>
                </footer>

            </article>

        <?php } ?>

    </section>

<?php } ?>

<?php drawFooter(); ?>
