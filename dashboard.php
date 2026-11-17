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

<?php drawCabecalhoPagina('Painel', $_SESSION['nome']); ?>

<?php if (in_array('MEMBRO', $papeis)) { ?>

    <section class="painel-seccao">
        <header>
            <h2>Área de membro</h2>
        </header>

        <dl class="painel-estatisticas">
            <div class="painel-metrica">
                <dt>Próximas aulas inscritas</dt>
                <dd class="valor"><?= $resumo['aulas'] ?></dd>
                <dd>Inscrições ativas em aulas futuras</dd>
            </div>

            <div class="painel-metrica">
                <dt>Sessões PT agendadas</dt>
                <dd class="valor"><?= $resumo['sessoes'] ?></dd>
                <dd>Pendentes ou confirmadas</dd>
            </div>

            <div class="painel-metrica">
                <dt>Último peso registado</dt>
                <?php if ($resumo['avaliacao']) { ?>
                    <dd class="valor"><?= htmlspecialchars($resumo['avaliacao']['peso']) ?> kg</dd>
                    <dd>
                        Avaliação de
                        <time datetime="<?= htmlspecialchars($resumo['avaliacao']['data']) ?>"><?= date('d/m/Y', strtotime($resumo['avaliacao']['data'])) ?></time>
                    </dd>
                <?php } else { ?>
                    <dd class="valor">—</dd>
                    <dd>Sem avaliações registadas</dd>
                <?php } ?>
            </div>
        </dl>

        <?php drawCartaoPainel('Explorar aulas', '/aulas.php'); ?>
        <?php drawCartaoPainel('As minhas aulas', '/membro.php?sec=aulas'); ?>
        <?php drawCartaoPainel('Personal Training', '/membro.php?sec=pt'); ?>
        <?php drawCartaoPainel('Avaliações físicas', '/membro.php?sec=avaliacoes'); ?>
        <?php drawCartaoPainel('Plano de treino', '/membro.php?sec=plano'); ?>
        <?php drawCartaoPainel('Treinadores', '/treinadores.php'); ?>
        <?php drawCartaoPainel('Estado dos equipamentos', '/equipamentos.php'); ?>
        <?php drawCartaoPainel('O meu perfil', '/perfil.php'); ?>
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
