<?php
// Área de treinador — uma secção de cada vez, escolhida por ?sec=
require_once __DIR__ . '/utils/session.php';
require_once __DIR__ . '/database/connection.php';
require_once __DIR__ . '/database/aula.php';
require_once __DIR__ . '/database/gestao.php';
require_once __DIR__ . '/templates/common.php';

requerPapel('TREINADOR');

$db = getDatabaseConnection();

// chave => array(separador, título, descrição, função que desenha a secção)
$seccoes = array(
    'aulas' => array('Minhas aulas', 'Minhas aulas', 'As aulas que lecionas e os membros inscritos em cada uma.', 'secAulas'),
    'pt' => array('Sessões de PT', 'Sessões de personal training', 'Confirma pedidos, cancela sessões e conclui as que já aconteceram.', 'secPT'),
    'avaliacoes' => array('Avaliações físicas', 'Avaliações físicas', 'Regista as medições de um membro e atribui-lhe um plano de treino.', 'secAvaliacoes'),
    'planos' => array('Planos de treino', 'Planos de treino', 'Cria planos e define os exercícios. Depois atribui-os numa avaliação física.', 'secPlanos'),
    'certificacoes' => array('Certificações', 'Certificações', 'Só podes dar aulas das modalidades em que tens uma certificação validada por um administrador.', 'secCertificacoes')
);

$sec = isset($_GET['sec']) && isset($seccoes[$_GET['sec']]) ? $_GET['sec'] : 'aulas';

drawHeader($seccoes[$sec][1]);
drawCabecalhoPagina('Área de treinador', $seccoes[$sec][1], $seccoes[$sec][2]);
drawSeparadores('/treinador.php', $seccoes, $sec);
$seccoes[$sec][3]($db);
drawFooter();

// ---------------------------------------------------------------------
// Secções
// ---------------------------------------------------------------------

function secAulas($db) {
$aulas = getAulasTreinador($db, $_SESSION['id']);

// Próximas primeiro (por ordem de data), depois as que já decorreram
$proximas = array();
$passadas = array();
foreach ($aulas as $aula) {
    if (aulaJaComecou($aula)) {
        $passadas[] = $aula;
    } else {
        $proximas[] = $aula;
    }
}
$proximas = array_reverse($proximas);

$grupos = array('Próximas aulas' => $proximas, 'Aulas já realizadas' => $passadas);
foreach ($grupos as $titulo => $lista) { ?>

<section class="bloco">
    <h2><?= e($titulo) ?></h2>
    <?php if (empty($lista)) { ?>
        <p class="vazio">Não há aulas nesta secção.</p>
    <?php } else { ?>
        <section class="grelha-aulas">
            <?php foreach ($lista as $aula) {
                $inscritos = getInscritosAula($db, $aula['id']); ?>
                <article class="cartao-aula">
                    <header>
                        <h3><?= e($aula['modalidade']) ?></h3>
                        <span class="etiqueta-nivel"><?= e(nomeNivel($aula['nivel'])) ?></span>
                        <?php if ($aula['cancelada']) drawEstado('Cancelada', 'erro'); ?>
                    </header>
                    <dl>
                        <div>
                            <dt>Data</dt>
                            <dd><time datetime="<?= e($aula['data']) ?>"><?= formatarData($aula['data']) ?></time></dd>
                        </div>
                        <div>
                            <dt>Horário</dt>
                            <dd><?= e($aula['horaInicio']) ?>–<?= e($aula['horaFim']) ?></dd>
                        </div>
                        <div>
                            <dt>Sala</dt>
                            <dd><?= e($aula['sala']) ?></dd>
                        </div>
                        <div>
                            <dt>Inscritos</dt>
                            <dd><?= count($inscritos) ?> / <?= e($aula['vagas']) ?></dd>
                        </div>
                        <?php if ($aula['totalNotas'] > 0) { ?>
                            <div>
                                <dt>Avaliação média</dt>
                                <dd><?= e(number_format($aula['mediaAula'], 1, ',', '')) ?> / 5 (<?= e($aula['totalNotas']) ?>)</dd>
                            </div>
                        <?php } ?>
                    </dl>
                    <footer>
                        <?php if (empty($inscritos)) { ?>
                            <p class="nota-tabela">Sem membros inscritos.</p>
                        <?php } else { ?>
                            <ul class="lista-inscritos">
                                <?php foreach ($inscritos as $m) { ?>
                                    <li><?= e($m['nome']) ?> · <?= e($m['telemovel']) ?></li>
                                <?php } ?>
                            </ul>
                        <?php } ?>
                    </footer>
                </article>
            <?php } ?>
        </section>
    <?php } ?>
</section>

<?php }
}

function secPT($db) {
$sessoes = getSessoesTreinador($db, $_SESSION['id']);

// Por tratar: pendentes ou confirmadas. Resto: histórico.
$ativas = array();
$historico = array();
foreach ($sessoes as $s) {
    if (in_array($s['estado'], array('PENDENTE', 'CONFIRMADA'))) {
        $ativas[] = $s;
    } else {
        $historico[] = $s;
    }
}
$historico = array_reverse($historico);
?>

<section class="bloco">
    <h2>Por tratar</h2>
    <?php if (empty($ativas)) { ?>
        <p class="vazio">Não tens sessões pendentes ou confirmadas.</p>
    <?php } else { ?>
        <div class="tabela-scroll">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Horário</th>
                        <th>Membro</th>
                        <th>Estado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ativas as $s) {
                        $estado = estadoSessaoPT($s['estado']);
                        $comecou = dataHoraJaComecou($s['data'], $s['horaInicio']); ?>
                        <tr>
                            <td><time datetime="<?= e($s['data']) ?>"><?= formatarData($s['data']) ?></time></td>
                            <td><?= e($s['horaInicio']) ?>–<?= e($s['horaFim']) ?></td>
                            <td><?= e($s['membro']) ?></td>
                            <td><?php drawEstado($estado[0], $estado[1]); ?></td>
                            <td>
                                <div class="acoes">
                                    <?php if ($s['estado'] === 'PENDENTE' && !$comecou) {
                                        drawFormAcao('/actions/action_gestao.php', array('idSessao' => $s['id'], 'op' => 'confirmar'), 'Confirmar');
                                    }
                                    if ($s['estado'] === 'CONFIRMADA' && $comecou) {
                                        drawFormAcao('/actions/action_gestao.php', array('idSessao' => $s['id'], 'op' => 'concluir'), 'Concluir');
                                    }
                                    if (!$comecou) {
                                        drawFormAcao('/actions/action_gestao.php', array('idSessao' => $s['id'], 'op' => 'recusar'), $s['estado'] === 'PENDENTE' ? 'Recusar' : 'Cancelar', 'botao-perigo');
                                    } ?>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>
</section>

<section class="bloco">
    <h2>Histórico</h2>
    <?php if (empty($historico)) { ?>
        <p class="vazio">Ainda não tens sessões no histórico.</p>
    <?php } else { ?>
        <div class="tabela-scroll">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Horário</th>
                        <th>Membro</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historico as $s) {
                        $estado = estadoSessaoPT($s['estado']); ?>
                        <tr>
                            <td><time datetime="<?= e($s['data']) ?>"><?= formatarData($s['data']) ?></time></td>
                            <td><?= e($s['horaInicio']) ?>–<?= e($s['horaFim']) ?></td>
                            <td><?= e($s['membro']) ?></td>
                            <td><?php drawEstado($estado[0], $estado[1]); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>
</section>
<?php }

function secAvaliacoes($db) {
$membros = getMembros($db, $_SESSION['id']);
$planos = getPlanos($db, $_SESSION['id']);
$avaliacoes = getAvaliacoesRegistadasPor($db, $_SESSION['id']);
?>

<section class="formulario largo">
    <h2>Nova avaliação</h2>
    <?php if (empty($planos)) { ?>
        <p>É preciso existir pelo menos um plano de treino. <a href="/treinador.php?sec=planos">Criar um plano</a>.</p>
    <?php } elseif (empty($membros)) { ?>
        <p>Não existem membros registados.</p>
    <?php } else { ?>
        <form action="/actions/action_gestao.php" method="post">
            <?php drawCsrf(); ?>
            <input type="hidden" name="op" value="avaliacao">

            <fieldset class="campos">
                <p>
                    <label for="idMembro">Membro</label>
                    <select id="idMembro" name="idMembro" required>
                        <option value="">Escolher…</option>
                        <?php foreach ($membros as $m) { ?>
                            <option value="<?= e($m['id']) ?>"><?= e($m['nome']) ?> (<?= e($m['email']) ?>)</option>
                        <?php } ?>
                    </select>
                </p>
                <p>
                    <label for="idPlano">Plano de treino</label>
                    <select id="idPlano" name="idPlano" required>
                        <option value="">Escolher…</option>
                        <?php foreach ($planos as $p) { ?>
                            <option value="<?= e($p['id']) ?>"><?= e($p['nome']) ?></option>
                        <?php } ?>
                    </select>
                </p>
                <p>
                    <label for="data">Data</label>
                    <input type="date" id="data" name="data" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                </p>
            </fieldset>

            <fieldset class="campos">
                <p>
                    <label for="peso">Peso (kg)</label>
                    <input type="text" inputmode="decimal" id="peso" name="peso" placeholder="75.5" required>
                </p>
                <p>
                    <label for="altura">Altura (m)</label>
                    <input type="text" inputmode="decimal" id="altura" name="altura" placeholder="1.78" required>
                </p>
                <p>
                    <label for="gordura">Gordura (%)</label>
                    <input type="text" inputmode="decimal" id="gordura" name="gordura" placeholder="opcional">
                </p>
            </fieldset>

            <fieldset class="campos">
                <p>
                    <label for="cintura">Cintura (cm)</label>
                    <input type="text" inputmode="decimal" id="cintura" name="cintura" placeholder="opcional">
                </p>
                <p>
                    <label for="peito">Peito (cm)</label>
                    <input type="text" inputmode="decimal" id="peito" name="peito" placeholder="opcional">
                </p>
                <p>
                    <label for="braco">Braço (cm)</label>
                    <input type="text" inputmode="decimal" id="braco" name="braco" placeholder="opcional">
                </p>
                <p>
                    <label for="coxa">Coxa (cm)</label>
                    <input type="text" inputmode="decimal" id="coxa" name="coxa" placeholder="opcional">
                </p>
            </fieldset>

            <button type="submit">Registar avaliação</button>
        </form>
    <?php } ?>
</section>

<section class="bloco">
    <h2>Avaliações registadas por mim</h2>
    <?php if (empty($avaliacoes)) { ?>
        <p class="vazio">Ainda não registaste avaliações.</p>
    <?php } else { ?>
        <div class="tabela-scroll">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Membro</th>
                        <th>Peso</th>
                        <th>Altura</th>
                        <th>IMC</th>
                        <th>Gordura</th>
                        <th>Plano</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($avaliacoes as $a) { ?>
                        <tr>
                            <td><time datetime="<?= e($a['data']) ?>"><?= formatarData($a['data']) ?></time></td>
                            <td><?= e($a['membro']) ?></td>
                            <td><?= e($a['peso']) ?> kg</td>
                            <td><?= e($a['altura']) ?> m</td>
                            <td><?= number_format(calcularImc($a['peso'], $a['altura']), 1, ',', '') ?></td>
                            <td><?= $a['percentagemGordura'] === null ? '—' : e($a['percentagemGordura']) . ' %' ?></td>
                            <td><?= e($a['plano']) ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>
</section>
<?php }

function secPlanos($db) {
// Se vier ?id=, mostra os exercícios desse plano
$plano = null;
$itens = array();
if (isset($_GET['id']) && is_string($_GET['id']) && ctype_digit($_GET['id'])) {
    $plano = getPlano($db, (int) $_GET['id'], $_SESSION['id']);
    if ($plano) $itens = getItensPlano($db, $plano['id']);
}

$planos = getPlanos($db, $_SESSION['id']);
?>

<?php if ($plano) { ?>
<section class="bloco">
    <h2><?= e($plano['nome']) ?></h2>
    <p>
        <?php if ($plano['objetivo']) { ?><?= e($plano['objetivo']) ?> · <?php } ?>
        <?= e($plano['frequenciaSemanal']) ?>× por semana · <?= e($plano['duracaoSemanas']) ?> semanas
    </p>

    <?php if (empty($itens)) { ?>
        <p class="vazio">Este plano ainda não tem exercícios.</p>
    <?php } else { ?>
        <div class="tabela-scroll">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Exercício</th>
                        <th>Séries</th>
                        <th>Repetições</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itens as $item) { ?>
                        <tr>
                            <td><?= e($item['ordem']) ?></td>
                            <td><?= e($item['exercicio']) ?></td>
                            <td><?= e($item['series']) ?></td>
                            <td><?= e($item['repeticoes']) ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>
</section>

<section class="formulario largo">
    <h2>Adicionar exercício</h2>
    <form action="/actions/action_gestao.php" method="post">
        <?php drawCsrf(); ?>
        <input type="hidden" name="op" value="exercicio">
        <input type="hidden" name="idPlano" value="<?= e($plano['id']) ?>">
        <fieldset class="campos">
            <p>
                <label for="exercicio">Exercício</label>
                <input type="text" id="exercicio" name="exercicio" required>
            </p>
            <p>
                <label for="series">Séries</label>
                <input type="number" id="series" name="series" min="1" required>
            </p>
            <p>
                <label for="repeticoes">Repetições</label>
                <input type="number" id="repeticoes" name="repeticoes" min="1" required>
            </p>
        </fieldset>
        <button type="submit">Adicionar ao plano</button>
    </form>
</section>
<?php } ?>

<section class="bloco">
    <h2>Os meus planos</h2>
    <?php if (empty($planos)) { ?>
        <p class="vazio">Ainda não existem planos.</p>
    <?php } else { ?>
        <div class="tabela-scroll">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Objetivo</th>
                        <th>Frequência</th>
                        <th>Duração</th>
                        <th>Exercícios</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($planos as $p) { ?>
                        <tr>
                            <td><?= e($p['nome']) ?></td>
                            <td><?= e($p['objetivo']) ?></td>
                            <td><?= e($p['frequenciaSemanal']) ?>× / semana</td>
                            <td><?= e($p['duracaoSemanas']) ?> semanas</td>
                            <td><?= e($p['totalExercicios']) ?></td>
                            <td><a href="/treinador.php?sec=planos&amp;id=<?= e($p['id']) ?>" class="botao-secundario">Ver / editar</a></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>
</section>

<section class="formulario largo">
    <h2>Novo plano</h2>
    <form action="/actions/action_gestao.php" method="post">
        <?php drawCsrf(); ?>
        <input type="hidden" name="op" value="plano">
        <fieldset class="campos">
            <p>
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" required>
            </p>
            <p>
                <label for="objetivo">Objetivo</label>
                <input type="text" id="objetivo" name="objetivo" placeholder="opcional">
            </p>
            <p>
                <label for="frequenciaSemanal">Frequência semanal (1–7)</label>
                <input type="number" id="frequenciaSemanal" name="frequenciaSemanal" min="1" max="7" required>
            </p>
            <p>
                <label for="duracaoSemanas">Duração (semanas)</label>
                <input type="number" id="duracaoSemanas" name="duracaoSemanas" min="1" required>
            </p>
        </fieldset>
        <button type="submit">Criar plano</button>
    </form>
</section>
<?php }

function secCertificacoes($db) {
$certificacoes = getCertificacoesTreinador($db, $_SESSION['id']);
$modalidades = getModalidades($db);
?>

<section class="bloco">
    <h2>As minhas certificações</h2>
    <?php if (empty($certificacoes)) { ?>
        <p class="vazio">Ainda não registaste certificações.</p>
    <?php } else { ?>
        <div class="tabela-scroll">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Modalidade</th>
                        <th>Certificação</th>
                        <th>Entidade</th>
                        <th>Obtida em</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($certificacoes as $c) { ?>
                        <tr>
                            <td><?= e($c['modalidade']) ?></td>
                            <td><?= e($c['nome']) ?></td>
                            <td><?= e($c['entidadeEmissora']) ?></td>
                            <td><time datetime="<?= e($c['dataObtencao']) ?>"><?= formatarData($c['dataObtencao']) ?></time></td>
                            <td>
                                <?php if ($c['validada']) {
                                    drawEstado('Validada', 'sucesso');
                                } else {
                                    drawEstado('Por validar', 'aviso');
                                } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>
</section>

<section class="formulario largo">
    <h2>Registar certificação</h2>
    <form action="/actions/action_gestao.php" method="post">
        <?php drawCsrf(); ?>
        <input type="hidden" name="op" value="registar">
        <fieldset class="campos">
            <p>
                <label for="idModalidade">Modalidade</label>
                <select id="idModalidade" name="idModalidade" required>
                    <option value="">Escolher…</option>
                    <?php foreach ($modalidades as $m) { ?>
                        <option value="<?= e($m['id']) ?>"><?= e($m['nome']) ?></option>
                    <?php } ?>
                </select>
            </p>
            <p>
                <label for="nome">Nome da certificação</label>
                <input type="text" id="nome" name="nome" required>
            </p>
            <p>
                <label for="entidade">Entidade emissora</label>
                <input type="text" id="entidade" name="entidade" required>
            </p>
            <p>
                <label for="dataObtencao">Data de obtenção</label>
                <input type="date" id="dataObtencao" name="dataObtencao" max="<?= date('Y-m-d') ?>" required>
            </p>
        </fieldset>
        <button type="submit">Registar</button>
    </form>
</section>
<?php }
