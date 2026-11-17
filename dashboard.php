<?php
require_once __DIR__ . '/utils/session.php';
require_once __DIR__ . '/templates/common.php';

if (!isset($_SESSION['id'])) {
    redirecionar('/login.php');
}

$papeis = $_SESSION['papeis'] ?? array();
$nome = htmlspecialchars($_SESSION['nome']);


require_once __DIR__ . '/database/connection.php';
require_once __DIR__ . '/database/dashboard.php';

$resumo = null;

if (in_array('MEMBRO', $papeis)) {
    $db = getDatabaseConnection();
    $resumo = getResumoMembro($db, $_SESSION['id']);
}

drawHeader('Dashboard');
?>

<section class="painel">

    <div class="painel-boas-vindas">
        <span class="painel-etiqueta">
            O TEU ESPAÇO BIOGYM
        </span>

        <h1>Olá, <?= $nome ?>!</h1>

        <p>
            Bem-vindo ao teu espaço de gestão.
            Explora as funcionalidades disponíveis
            e organiza as tuas atividades.
        </p>
    </div>

    <?php if (in_array('MEMBRO', $papeis)) { ?>

        <section class="painel-seccao">
            <div class="painel-titulo">
                <h2>Área de membro</h2>
                <p>O teu treino, à tua maneira.</p>
            </div>

            <div class="painel-estatisticas">

                <div class="painel-metrica">
                    <span>PRÓXIMAS AULAS INSCRITAS</span>
                    <strong><?= $resumo['aulas'] ?></strong>
                    <p>Inscrições ativas em aulas futuras</p>
                </div>

                <div class="painel-metrica">
                    <span>SESSÕES PT AGENDADAS</span>
                    <strong><?= $resumo['sessoes'] ?></strong>
                    <p>Pendentes ou confirmadas</p>
                </div>

                <div class="painel-metrica">
                    <span>ÚLTIMO PESO REGISTADO</span>

                    <?php if ($resumo['avaliacao']) { ?>
                        <strong>
                            <?= htmlspecialchars(
                                $resumo['avaliacao']['peso']
                            ) ?> kg
                        </strong>

                        <p>
                            Avaliação de
                            <?= date(
                                'd/m/Y',
                                strtotime($resumo['avaliacao']['data'])
                            ) ?>
                        </p>
                    <?php } else { ?>
                        <strong>—</strong>
                        <p>Sem avaliações registadas</p>
                    <?php } ?>
                </div>

            </div>


            <div class="painel-grelha">

                <a href="/aulas.php" class="painel-cartao">
                    <span class="painel-icone">📅</span>
                    <h3>Explorar aulas</h3>
                    <p>
                        Descobre as próximas atividades,
                        horários e vagas disponíveis.
                    </p>
                    <span class="painel-acao">
                        Ver aulas &rarr;
                    </span>
                </a>

                <div class="painel-cartao">
                    <span class="painel-icone">🏋️</span>
                    <h3>Personal Training</h3>
                    <p>
                        Agenda sessões individuais
                        com os nossos treinadores.
                    </p>
                    <span class="painel-indisponivel">
                        Em desenvolvimento
                    </span>
                </div>

                <div class="painel-cartao">
                    <span class="painel-icone">📈</span>
                    <h3>Evolução física</h3>
                    <p>
                        Consulta as tuas avaliações
                        e acompanha o teu progresso.
                    </p>
                    <span class="painel-indisponivel">
                        Em desenvolvimento
                    </span>
                </div>

                <div class="painel-cartao">
                    <span class="painel-icone">📋</span>
                    <h3>Plano de treino</h3>
                    <p>
                        Consulta os exercícios,
                        séries e repetições do teu plano.
                    </p>
                    <span class="painel-indisponivel">
                        Em desenvolvimento
                    </span>
                </div>

            </div>
        </section>

    <?php } ?>

    <?php if (in_array('TREINADOR', $papeis)) { ?>

        <section class="painel-seccao">
            <div class="painel-titulo">
                <h2>Área de treinador</h2>
                <p>Organiza as tuas atividades e membros.</p>
            </div>

            <div class="painel-grelha">

                <div class="painel-cartao">
                    <span class="painel-icone">📅</span>
                    <h3>Minhas aulas</h3>
                    <p>Consulta as aulas que lecionas.</p>
                    <span class="painel-indisponivel">
                        Em desenvolvimento
                    </span>
                </div>

                <div class="painel-cartao">
                    <span class="painel-icone">👥</span>
                    <h3>Meus membros</h3>
                    <p>Consulta os membros das tuas aulas.</p>
                    <span class="painel-indisponivel">
                        Em desenvolvimento
                    </span>
                </div>

                <div class="painel-cartao">
                    <span class="painel-icone">📄</span>
                    <h3>Certificações</h3>
                    <p>Consulta e gere as tuas certificações.</p>
                    <span class="painel-indisponivel">
                        Em desenvolvimento
                    </span>
                </div>

                <div class="painel-cartao">
                    <span class="painel-icone">💪</span>
                    <h3>Acompanhamento físico</h3>
                    <p>Regista avaliações dos membros.</p>
                    <span class="painel-indisponivel">
                        Em desenvolvimento
                    </span>
                </div>

            </div>
        </section>

    <?php } ?>

    <?php if (in_array('ADMIN', $papeis)) { ?>

        <section class="painel-seccao">
            <div class="painel-titulo">
                <h2>Administração</h2>
                <p>Supervisiona a plataforma BioGym.</p>
            </div>

            <div class="painel-grelha">

                <div class="painel-cartao">
                    <span class="painel-icone">👥</span>
                    <h3>Utilizadores</h3>
                    <p>Gere membros e treinadores.</p>
                    <span class="painel-indisponivel">
                        Em desenvolvimento
                    </span>
                </div>

                <div class="painel-cartao">
                    <span class="painel-icone">📅</span>
                    <h3>Gestão de aulas</h3>
                    <p>Organiza horários e treinadores.</p>
                    <span class="painel-indisponivel">
                        Em desenvolvimento
                    </span>
                </div>

                <div class="painel-cartao">
                    <span class="painel-icone">🏢</span>
                    <h3>Salas e equipamentos</h3>
                    <p>Gere os espaços e recursos.</p>
                    <span class="painel-indisponivel">
                        Em desenvolvimento
                    </span>
                </div>

                <div class="painel-cartao">
                    <span class="painel-icone">✅</span>
                    <h3>Validar certificações</h3>
                    <p>Consulta e valida certificações.</p>
                    <span class="painel-indisponivel">
                        Em desenvolvimento
                    </span>
                </div>

            </div>
        </section>

    <?php } ?>

</section>

<?php drawFooter(); ?>
