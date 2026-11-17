<?php
// Área de membro — uma secção de cada vez, escolhida por ?sec=
require_once __DIR__ . '/utils/session.php';
require_once __DIR__ . '/database/connection.php';
require_once __DIR__ . '/database/aula.php';
require_once __DIR__ . '/database/gestao.php';
require_once __DIR__ . '/templates/common.php';

requerPapel('MEMBRO');

$db = getDatabaseConnection();

// chave => array(separador, título, descrição, função que desenha a secção)
$seccoes = array(
    'aulas' => array('As minhas aulas', 'As minhas aulas', 'Consulta e gere as tuas inscrições.', 'secAulas'),
    'pt' => array('Personal training', 'Personal Training', 'Agenda sessões individuais com os nossos treinadores.', 'secPT'),
    'avaliacoes' => array('Avaliações físicas', 'Avaliações físicas', 'As medições registadas pelos treinadores ao longo do tempo.', 'secAvaliacoes'),
    'plano' => array('Plano de treino', 'Plano de treino', 'Os exercícios, séries e repetições do teu plano atual.', 'secPlano')
);

$sec = isset($_GET['sec']) && isset($seccoes[$_GET['sec']]) ? $_GET['sec'] : 'aulas';

drawHeader($seccoes[$sec][1]);
drawCabecalhoPagina('Área de membro', $seccoes[$sec][1], $seccoes[$sec][2]);
drawSeparadores('/membro.php', $seccoes, $sec);
$seccoes[$sec][3]($db);
drawFooter();

// ---------------------------------------------------------------------
// Secções
// ---------------------------------------------------------------------

function secAulas($db) {
$inscricoes = getInscricoesMembro($db, $_SESSION['id']);

// Próximas: inscrição ativa numa aula que ainda não começou. Resto: histórico.
$proximas = array();
$historico = array();
foreach ($inscricoes as $inscricao) {
    if ($inscricao['estado'] === 'ATIVA' && dataHoraNoFuturo($inscricao['data'], $inscricao['horaInicio'])) {
        $proximas[] = $inscricao;
    } else {
        $historico[] = $inscricao;
    }
}
$historico = array_reverse($historico);
?>

<section class="bloco">
    <h2>Próximas aulas</h2>
    <?php if (empty($proximas)) { ?>
        <p class="vazio">Não tens inscrições em aulas futuras. <a href="/aulas.php">Ver aulas disponíveis</a>.</p>
    <?php } else { ?>
        <div class="tabela-scroll">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Modalidade</th>
                        <th>Data</th>
                        <th>Horário</th>
                        <th>Treinador</th>
                        <th>Sala</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($proximas as $i) { ?>
                        <tr>
                            <td><?= e($i['modalidade']) ?></td>
                            <td><time datetime="<?= e($i['data']) ?>"><?= formatarData($i['data']) ?></time></td>
                            <td><?= e($i['horaInicio']) ?>–<?= e($i['horaFim']) ?></td>
                            <td><?= e($i['treinador']) ?></td>
                            <td><?= e($i['sala']) ?></td>
                            <td><?php drawFormAcao('/actions/action_gestao.php', array('op' => 'cancelar', 'idAula' => $i['idAula'], 'origem' => 'minhas_aulas'), 'Cancelar', 'botao-perigo'); ?></td>
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
        <p class="vazio">Ainda não tens aulas no histórico.</p>
    <?php } else { ?>
        <div class="tabela-scroll">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Modalidade</th>
                        <th>Data</th>
                        <th>Horário</th>
                        <th>Treinador</th>
                        <th>Estado</th>
                        <th>Avaliação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historico as $i) {
                        $frequentada = $i['estado'] === 'ATIVA' && !$i['cancelada'];
                        $terminou = dataHoraJaComecou($i['data'], $i['horaFim']);
                        $avaliada = $i['notaAula'] !== null; ?>
                        <tr>
                            <td><?= e($i['modalidade']) ?></td>
                            <td><time datetime="<?= e($i['data']) ?>"><?= formatarData($i['data']) ?></time></td>
                            <td><?= e($i['horaInicio']) ?>–<?= e($i['horaFim']) ?></td>
                            <td><?= e($i['treinador']) ?></td>
                            <td>
                                <?php if ($i['cancelada']) {
                                    drawEstado('Aula cancelada', 'erro');
                                } elseif ($i['estado'] === 'CANCELADA') {
                                    drawEstado('Inscrição cancelada', 'erro');
                                } else {
                                    drawEstado('Frequentada', 'neutro');
                                } ?>
                            </td>
                            <td>
                                <?php if (!$frequentada || !$terminou) { ?>
                                    <span class="nota-tabela">—</span>
                                <?php } elseif ($avaliada) { ?>
                                    Aula <?= e($i['notaAula']) ?>/5 · Treinador <?= e($i['notaTreinador']) ?>/5
                                    <?php if ($i['comentario']) { ?>
                                        <br><span class="nota-tabela">«<?= e($i['comentario']) ?>»</span>
                                    <?php } ?>
                                <?php } else { ?>
                                    <details class="avaliar">
                                        <summary>Avaliar</summary>
                                        <form action="/actions/action_gestao.php" method="post">
                                            <?php drawCsrf(); ?>
                                            <input type="hidden" name="op" value="avaliar_aula">
                                            <input type="hidden" name="idAula" value="<?= e($i['idAula']) ?>">
                                            <?php drawNota('notaAula', 'Aula (1 a 5)'); ?>
                                            <?php drawNota('notaTreinador', 'Treinador (1 a 5)'); ?>
                                            <label for="comentario-<?= e($i['idAula']) ?>">Comentário (opcional)</label>
                                            <textarea id="comentario-<?= e($i['idAula']) ?>" name="comentario" rows="3" maxlength="500"></textarea>
                                            <button type="submit" class="botao">Enviar avaliação</button>
                                        </form>
                                    </details>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>
</section>
<?php }

function secPT($db) {
$treinadores = getTreinadores($db, $_SESSION['id']);
$sessoes = getSessoesMembro($db, $_SESSION['id']);

// Próximas: pendentes ou confirmadas que ainda não começaram. Resto: histórico.
$proximas = array();
$historico = array();
foreach ($sessoes as $s) {
    if (in_array($s['estado'], array('PENDENTE', 'CONFIRMADA')) && dataHoraNoFuturo($s['data'], $s['horaInicio'])) {
        $proximas[] = $s;
    } else {
        $historico[] = $s;
    }
}
$historico = array_reverse($historico);
?>

<section class="formulario largo">
    <h2>Marcar sessão</h2>
    <?php if (empty($treinadores)) { ?>
        <p>Não existem treinadores disponíveis.</p>
    <?php } else { ?>
        <form action="/actions/action_gestao.php" method="post">
            <?php drawCsrf(); ?>
            <input type="hidden" name="op" value="marcar">

            <fieldset class="campos">
                <p>
                    <label for="idTreinador">Treinador</label>
                    <select id="idTreinador" name="idTreinador" required>
                        <option value="">Escolher…</option>
                        <?php foreach ($treinadores as $t) { ?>
                            <option value="<?= e($t['id']) ?>">
                                <?= e($t['nome']) ?><?= $t['modalidades'] ? ' (' . e($t['modalidades']) . ')' : '' ?>
                            </option>
                        <?php } ?>
                    </select>
                </p>
                <p>
                    <label for="data">Data</label>
                    <input type="date" id="data" name="data" min="<?= date('Y-m-d') ?>" required>
                </p>
                <p>
                    <label for="horaInicio">Hora de início</label>
                    <input type="time" id="horaInicio" name="horaInicio" required>
                </p>
                <p>
                    <label for="horaFim">Hora de fim</label>
                    <input type="time" id="horaFim" name="horaFim" required>
                </p>
            </fieldset>

            <p class="ajuda">A sessão fica pendente até o treinador a confirmar.</p>
            <button type="submit">Marcar sessão</button>
        </form>
    <?php } ?>
</section>

<section class="bloco">
    <h2>Próximas sessões</h2>
    <?php if (empty($proximas)) { ?>
        <p class="vazio">Não tens sessões agendadas.</p>
    <?php } else { ?>
        <div class="tabela-scroll">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Horário</th>
                        <th>Treinador</th>
                        <th>Estado</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($proximas as $s) {
                        $estado = estadoSessaoPT($s['estado']); ?>
                        <tr>
                            <td><time datetime="<?= e($s['data']) ?>"><?= formatarData($s['data']) ?></time></td>
                            <td><?= e($s['horaInicio']) ?>–<?= e($s['horaFim']) ?></td>
                            <td><?= e($s['treinador']) ?></td>
                            <td><?php drawEstado($estado[0], $estado[1]); ?></td>
                            <td><?php drawFormAcao('/actions/action_gestao.php', array('op' => 'pt_cancelar', 'idSessao' => $s['id']), 'Cancelar', 'botao-perigo'); ?></td>
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
                        <th>Treinador</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historico as $s) {
                        $estado = estadoSessaoPT($s['estado']); ?>
                        <tr>
                            <td><time datetime="<?= e($s['data']) ?>"><?= formatarData($s['data']) ?></time></td>
                            <td><?= e($s['horaInicio']) ?>–<?= e($s['horaFim']) ?></td>
                            <td><?= e($s['treinador']) ?></td>
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
$avaliacoes = getAvaliacoesMembro($db, $_SESSION['id']);

// Mostra um valor opcional, ou um traço se não foi medido
function valorOuTraco($valor, $unidade) {
    return $valor === null ? '—' : e($valor) . ' ' . $unidade;
}
?>

<section class="bloco">
    <h2>Avaliações físicas</h2>
    <?php if (empty($avaliacoes)) { ?>
        <p class="vazio">Ainda não tens avaliações físicas registadas. Fala com um dos nossos treinadores.</p>
    <?php } else { ?>
        <div class="tabela-scroll">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Peso</th>
                        <th>Altura</th>
                        <th>IMC</th>
                        <th>Gordura</th>
                        <th>Cintura</th>
                        <th>Peito</th>
                        <th>Braço</th>
                        <th>Coxa</th>
                        <th>Treinador</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($avaliacoes as $a) { ?>
                        <tr>
                            <td><time datetime="<?= e($a['data']) ?>"><?= formatarData($a['data']) ?></time></td>
                            <td><?= e($a['peso']) ?> kg</td>
                            <td><?= e($a['altura']) ?> m</td>
                            <td><?= number_format(calcularImc($a['peso'], $a['altura']), 1, ',', '') ?></td>
                            <td><?= valorOuTraco($a['percentagemGordura'], '%') ?></td>
                            <td><?= valorOuTraco($a['perimetroCintura'], 'cm') ?></td>
                            <td><?= valorOuTraco($a['perimetroPeito'], 'cm') ?></td>
                            <td><?= valorOuTraco($a['perimetroBraco'], 'cm') ?></td>
                            <td><?= valorOuTraco($a['perimetroCoxa'], 'cm') ?></td>
                            <td><?= e($a['treinador']) ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>
</section>
<?php }

function secPlano($db) {
$avaliacoes = getAvaliacoesMembro($db, $_SESSION['id']);

// O plano de treino atual é o da avaliação mais recente
$plano = null;
$itens = array();
if (!empty($avaliacoes)) {
    $plano = getPlano($db, $avaliacoes[0]['idPlanoTreino']);
    $itens = getItensPlano($db, $plano['id']);
}
?>

<section class="bloco">
    <h2>Plano de treino</h2>
    <?php if (!$plano) { ?>
        <p class="vazio">Ainda não tens um plano de treino atribuído.</p>
<?php } else { ?>
        <p>
            <strong><?= e($plano['nome']) ?></strong>
            <?php if ($plano['objetivo']) { ?> · <?= e($plano['objetivo']) ?><?php } ?>
            · <?= e($plano['frequenciaSemanal']) ?>× por semana · <?= e($plano['duracaoSemanas']) ?> semanas
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
    <?php } ?>
</section>
<?php }
