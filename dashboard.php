<?php
require_once __DIR__ . '/utils/session.php';
require_once __DIR__ . '/database/connection.php';
require_once __DIR__ . '/database/dashboard.php';
require_once __DIR__ . '/templates/common.php';

if (!isset($_SESSION['id'])) {
    redirecionar('/login.php');
}

$papeis = $_SESSION['papeis'] ?? array();

$resumo = null;
if (in_array('MEMBRO', $papeis)) {
    $db = getDatabaseConnection();
    $resumo = getResumoMembro($db, $_SESSION['id']);
}

drawHeader('Dashboard');
?>

<header class="painel-boas-vindas">
    <p class="etiqueta">O teu espaço BioGym</p>
    <h1>Olá, <?= htmlspecialchars($_SESSION['nome']) ?>!</h1>
    <p>
        Bem-vindo ao teu espaço de gestão.
        Explora as funcionalidades disponíveis
        e organiza as tuas atividades.
    </p>
</header>

<?php if (in_array('MEMBRO', $papeis)) { ?>

    <section class="painel-seccao">
        <header>
            <h2>Área de membro</h2>
            <p>O teu treino, à tua maneira.</p>
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

        <a href="/aulas.php" class="painel-cartao">
            <span class="painel-icone" aria-hidden="true">📅</span>
            <h3>Explorar aulas</h3>
            <p>Descobre as próximas atividades, horários e vagas disponíveis.</p>
            <span class="painel-acao">Ver aulas &rarr;</span>
        </a>

        <article class="painel-cartao">
            <span class="painel-icone" aria-hidden="true">🏋️</span>
            <h3>Personal Training</h3>
            <p>Agenda sessões individuais com os nossos treinadores.</p>
            <span class="painel-indisponivel">Em desenvolvimento</span>
        </article>

        <article class="painel-cartao">
            <span class="painel-icone" aria-hidden="true">📈</span>
            <h3>Evolução física</h3>
            <p>Consulta as tuas avaliações e acompanha o teu progresso.</p>
            <span class="painel-indisponivel">Em desenvolvimento</span>
        </article>

        <article class="painel-cartao">
            <span class="painel-icone" aria-hidden="true">📋</span>
            <h3>Plano de treino</h3>
            <p>Consulta os exercícios, séries e repetições do teu plano.</p>
            <span class="painel-indisponivel">Em desenvolvimento</span>
        </article>
    </section>

<?php } ?>

<?php if (in_array('TREINADOR', $papeis)) { ?>

    <section class="painel-seccao">
        <header>
            <h2>Área de treinador</h2>
            <p>Organiza as tuas atividades e membros.</p>
        </header>

        <article class="painel-cartao">
            <span class="painel-icone" aria-hidden="true">📅</span>
            <h3>Minhas aulas</h3>
            <p>Consulta as aulas que lecionas.</p>
            <span class="painel-indisponivel">Em desenvolvimento</span>
        </article>

        <article class="painel-cartao">
            <span class="painel-icone" aria-hidden="true">👥</span>
            <h3>Meus membros</h3>
            <p>Consulta os membros das tuas aulas.</p>
            <span class="painel-indisponivel">Em desenvolvimento</span>
        </article>

        <article class="painel-cartao">
            <span class="painel-icone" aria-hidden="true">📄</span>
            <h3>Certificações</h3>
            <p>Consulta e gere as tuas certificações.</p>
            <span class="painel-indisponivel">Em desenvolvimento</span>
        </article>

        <article class="painel-cartao">
            <span class="painel-icone" aria-hidden="true">💪</span>
            <h3>Acompanhamento físico</h3>
            <p>Regista avaliações dos membros.</p>
            <span class="painel-indisponivel">Em desenvolvimento</span>
        </article>
    </section>

<?php } ?>

<?php if (in_array('ADMIN', $papeis)) { ?>

    <section class="painel-seccao">
        <header>
            <h2>Administração</h2>
            <p>Supervisiona a plataforma BioGym.</p>
        </header>

        <article class="painel-cartao">
            <span class="painel-icone" aria-hidden="true">👥</span>
            <h3>Utilizadores</h3>
            <p>Gere membros e treinadores.</p>
            <span class="painel-indisponivel">Em desenvolvimento</span>
        </article>

        <article class="painel-cartao">
            <span class="painel-icone" aria-hidden="true">📅</span>
            <h3>Gestão de aulas</h3>
            <p>Organiza horários e treinadores.</p>
            <span class="painel-indisponivel">Em desenvolvimento</span>
        </article>

        <article class="painel-cartao">
            <span class="painel-icone" aria-hidden="true">🏢</span>
            <h3>Salas e equipamentos</h3>
            <p>Gere os espaços e recursos.</p>
            <span class="painel-indisponivel">Em desenvolvimento</span>
        </article>

        <article class="painel-cartao">
            <span class="painel-icone" aria-hidden="true">✅</span>
            <h3>Validar certificações</h3>
            <p>Consulta e valida certificações.</p>
            <span class="painel-indisponivel">Em desenvolvimento</span>
        </article>
    </section>

<?php } ?>

<?php drawFooter(); ?>
