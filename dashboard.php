<?php
require_once __DIR__ . '/utils/session.php';
require_once __DIR__ . '/database/connection.php';
require_once __DIR__ . '/database/utilizador.php';
require_once __DIR__ . '/templates/common.php';

requerLogin();

$papeis = $_SESSION['papeis'] ?? array();

$resumo = null;
if (in_array('MEMBRO', $papeis)) {
    $db = getDatabaseConnection();
    $resumo = getResumoMembro($db, $_SESSION['id']);
}

drawHeader('Início');
?>

<section class="painel-hero">

    <div class="painel-hero-conteudo">

        <p class="painel-hero-etiqueta">
            O teu espaço de treino
        </p>

        <h1>Olá, <?= e($_SESSION['nome']) ?>!</h1>

        <p class="painel-hero-descricao">
            Cada treino é um passo em direção
            aos teus objetivos.
        </p>

        <?php if (in_array('MEMBRO', $papeis)) { ?>

            <a href="/aulas.php" class="painel-hero-botao">
                Explorar aulas &rarr;
            </a>

        <?php } ?>

    </div>

    <div class="painel-hero-ilustracao" aria-hidden="true">
        <?php drawIconePainel('treino'); ?>
    </div>

</section>

<?php if (in_array('MEMBRO', $papeis)) { ?>

    <section class="painel-seccao">
        <header>
            <h2>A tua atividade</h2>
        </header>

       <dl class="painel-estatisticas">

            <!-- Próximas aulas -->
            <div class="painel-metrica">

                <dt>
                    <span class="painel-metrica-icone">
                        <?php drawIconePainel('calendario'); ?>
                    </span>

                    Próximas aulas inscritas
                </dt>

                <dd class="valor"><?= $resumo['aulas'] ?></dd>

                <dd>Inscrições ativas em aulas futuras</dd>

            </div>

            <!-- Sessões de Personal Training -->
            <div class="painel-metrica">

                <dt>
                    <span class="painel-metrica-icone">
                        <?php drawIconePainel('treino'); ?>
                    </span>

                    Sessões PT agendadas
                </dt>

                <dd class="valor"><?= $resumo['sessoes'] ?></dd>

                <dd>Pendentes ou confirmadas</dd>

            </div>

            <!-- Último peso registado -->
            <div class="painel-metrica">

                <dt>
                    <span class="painel-metrica-icone">
                        <?php drawIconePainel('peso'); ?>
                    </span>

                    Último peso registado
                </dt>

                <?php if ($resumo['avaliacao']) { ?>

                    <dd class="valor">
                        <?= htmlspecialchars($resumo['avaliacao']['peso']) ?> kg
                    </dd>

                    <dd>
                        Avaliação de
                        <time datetime="<?= htmlspecialchars($resumo['avaliacao']['data']) ?>">
                            <?= date('d/m/Y', strtotime($resumo['avaliacao']['data'])) ?>
                        </time>
                    </dd>

                <?php } else { ?>

                    <dd class="valor">—</dd>
                    <dd>Sem avaliações registadas</dd>

                <?php } ?>

            </div>

        </dl>

       <header>
    <h2>Área de membro</h2>
</header>

<?php
drawCartaoPainel(
    'Explorar aulas',
    '/aulas.php',
    'calendario',
    'Descobre as modalidades'
);

drawCartaoPainel(
    'As minhas aulas',
    '/membro.php?sec=aulas',
    'inscricoes',
    'Consulta as tuas inscrições'
);

drawCartaoPainel(
    'Personal Training',
    '/membro.php?sec=pt',
    'treino',
    'Gere as tuas sessões'
);

drawCartaoPainel(
    'Avaliações físicas',
    '/membro.php?sec=avaliacoes',
    'avaliacao',
    'Acompanha os teus resultados'
);

drawCartaoPainel(
    'Plano de treino',
    '/membro.php?sec=plano',
    'plano',
    'Consulta o teu plano'
);

drawCartaoPainel(
    'Treinadores',
    '/treinadores.php',
    'equipa',
    'Conhece a nossa equipa'
);

drawCartaoPainel(
    'Estado dos equipamentos',
    '/equipamentos.php',
    'equipamento',
    'Consulta a disponibilidade'
);

drawCartaoPainel(
    'O meu perfil',
    '/perfil.php',
    'perfil',
    'Gere os teus dados'
);
?>
    </section>

<?php } ?>

<?php if (in_array('TREINADOR', $papeis)) { ?>

    <section class="painel-seccao">
        <header>
            <h2>Área de treinador</h2>
        </header>

        <?php drawCartaoPainel('Minhas aulas', '/treinador.php?sec=aulas'); ?>
        <?php drawCartaoPainel('Sessões de PT', '/treinador.php?sec=pt'); ?>
        <?php drawCartaoPainel('Acompanhamento físico', '/treinador.php?sec=avaliacoes'); ?>
        <?php drawCartaoPainel('Planos de treino', '/treinador.php?sec=planos'); ?>
        <?php drawCartaoPainel('Certificações', '/treinador.php?sec=certificacoes'); ?>
        <?php if (!in_array('MEMBRO', $papeis)) drawCartaoPainel('O meu perfil', '/perfil.php'); ?>
    </section>

<?php } ?>

<?php if (in_array('ADMIN', $papeis)) { ?>

    <section class="painel-seccao">
        <header>
            <h2>Administração</h2>
        </header>

        <?php drawCartaoPainel('Utilizadores', '/admin.php?sec=utilizadores'); ?>
        <?php drawCartaoPainel('Gestão de aulas', '/admin.php?sec=aulas'); ?>
        <?php drawCartaoPainel('Salas e equipamentos', '/admin.php?sec=espacos'); ?>
        <?php drawCartaoPainel('Validar certificações', '/admin.php?sec=certificacoes'); ?>
        <?php if (!in_array('MEMBRO', $papeis) && !in_array('TREINADOR', $papeis)) drawCartaoPainel('O meu perfil', '/perfil.php'); ?>
    </section>

<?php } ?>

<?php drawFooter(); ?>
