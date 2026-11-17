<?php
// Área de administrador — uma secção de cada vez, escolhida por ?sec=
require_once __DIR__ . '/utils/session.php';
require_once __DIR__ . '/database/connection.php';
require_once __DIR__ . '/database/aula.php';
require_once __DIR__ . '/database/gestao.php';
require_once __DIR__ . '/templates/common.php';

requerPapel('ADMIN');

$db = getDatabaseConnection();

// chave => array(separador, título, descrição, função que desenha a secção)
$seccoes = array(
    'utilizadores' => array('Utilizadores', 'Utilizadores', 'Consulta os utilizadores e gere os papéis de treinador e administrador.', 'secUtilizadores'),
    'aulas' => array('Aulas', 'Gestão de aulas', 'Cria aulas e atribui treinadores. O treinador precisa de certificação validada para a modalidade.', 'secAulas'),
    'espacos' => array('Salas e equipamentos', 'Salas e equipamentos', 'Gere os espaços do ginásio e o estado de cada equipamento.', 'secEspacos'),
    'certificacoes' => array('Certificações', 'Validar certificações', 'Um treinador só pode dar aulas de uma modalidade depois de a certificação ser validada.', 'secCertificacoes')
);

$sec = isset($_GET['sec']) && isset($seccoes[$_GET['sec']]) ? $_GET['sec'] : 'utilizadores';

drawHeader($seccoes[$sec][1]);
drawCabecalhoPagina('Administração', $seccoes[$sec][1], $seccoes[$sec][2]);
drawSeparadores('/admin.php', $seccoes, $sec);
$seccoes[$sec][3]($db);
drawFooter();

// ---------------------------------------------------------------------
// Secções
// ---------------------------------------------------------------------

function secUtilizadores($db) {
$utilizadores = getUtilizadoresComPapeis($db);
?>

<section class="bloco">
    <div class="tabela-scroll">
        <table class="tabela">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Registo</th>
                    <th>Papéis</th>
                    <th>Treinador</th>
                    <th>Administrador</th>
                    <th>Conta</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($utilizadores as $u) { ?>
                    <tr>
                        <td><?= e($u['nome']) ?></td>
                        <td><?= e($u['email']) ?></td>
                        <td><time datetime="<?= e($u['dataRegisto']) ?>"><?= formatarData($u['dataRegisto']) ?></time></td>
                        <td>
                            <?php if ($u['membro']) drawEstado('Membro', 'neutro'); ?>
                            <?php if ($u['treinador']) drawEstado('Treinador', 'sucesso'); ?>
                            <?php if ($u['admin']) drawEstado('Admin', 'aviso'); ?>
                        </td>
                        <td>
                            <?php if ($u['treinador']) {
                                drawFormAcao('/actions/action_gestao.php', array('op' => 'papel', 'idUtilizador' => $u['id'], 'papel' => 'TREINADOR', 'acao' => 'remover'), 'Retirar', 'botao-perigo');
                            } else {
                                drawFormAcao('/actions/action_gestao.php', array('op' => 'papel', 'idUtilizador' => $u['id'], 'papel' => 'TREINADOR', 'acao' => 'adicionar'), 'Tornar treinador', 'botao-secundario');
                            } ?>
                        </td>
                        <td>
                            <?php if ($u['admin']) {
                                if ((int) $u['id'] !== (int) $_SESSION['id']) {
                                    drawFormAcao('/actions/action_gestao.php', array('op' => 'papel', 'idUtilizador' => $u['id'], 'papel' => 'ADMIN', 'acao' => 'remover'), 'Retirar', 'botao-perigo');
                                } else { ?>
                                    <span class="nota-tabela">És tu</span>
                                <?php }
                            } else {
                                drawFormAcao('/actions/action_gestao.php', array('op' => 'papel', 'idUtilizador' => $u['id'], 'papel' => 'ADMIN', 'acao' => 'adicionar'), 'Tornar admin', 'botao-secundario');
                            } ?>
                        </td>
                        <td>
                            <?php if ((int) $u['id'] === (int) $_SESSION['id']) { ?>
                                <?php drawEstado('Ativa', 'sucesso'); ?>
                            <?php } elseif ($u['ativo']) { ?>
                                <?php drawFormAcao('/actions/action_gestao.php', array('op' => 'desativar', 'idUtilizador' => $u['id']), 'Desativar', 'botao-perigo'); ?>
                            <?php } else { ?>
                                <?php drawEstado('Desativada', 'erro'); ?>
                                <?php drawFormAcao('/actions/action_gestao.php', array('op' => 'ativar', 'idUtilizador' => $u['id']), 'Reativar', 'botao-secundario'); ?>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <p class="nota-tabela">
        Todos os utilizadores têm de ter pelo menos um papel. Um treinador com aulas, sessões,
        certificações ou avaliações associadas não pode deixar de o ser. Uma conta desativada não
        consegue iniciar sessão: as inscrições e sessões futuras de um membro são canceladas, e um
        treinador só pode ser desativado sem aulas nem sessões futuras.
    </p>
</section>
<?php }

function secAulas($db) {
$aulas = getTodasAulas($db);
$modalidades = getModalidades($db);
$salas = getSalas($db);
$treinadores = getTreinadores($db);
?>

<?php
// ?editar=ID mostra o formulário já preenchido com essa aula (só se for futura e não cancelada)
$editar = null;
if (isset($_GET['editar']) && is_string($_GET['editar']) && ctype_digit($_GET['editar'])) {
    $editar = getAulaPorId($db, (int) $_GET['editar']);
    if ($editar && ($editar['cancelada'] || aulaJaComecou($editar))) $editar = null;
}
drawFormAula($modalidades, $salas, $treinadores, $editar);
?>

<section class="bloco">
    <h2>Todas as aulas</h2>
    <?php if (empty($aulas)) { ?>
        <p class="vazio">Ainda não existem aulas.</p>
    <?php } else { ?>
        <div class="tabela-scroll">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Modalidade</th>
                        <th>Data</th>
                        <th>Horário</th>
                        <th>Sala</th>
                        <th>Treinador</th>
                        <th>Inscritos</th>
                        <th>Estado</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($aulas as $a) { ?>
                        <tr>
                            <td><?= e($a['modalidade']) ?> <span class="nota-tabela">(<?= e(nomeNivel($a['nivel'])) ?>)</span></td>
                            <td><time datetime="<?= e($a['data']) ?>"><?= formatarData($a['data']) ?></time></td>
                            <td><?= e($a['horaInicio']) ?>–<?= e($a['horaFim']) ?></td>
                            <td><?= e($a['sala']) ?></td>
                            <td><?= e($a['treinador']) ?></td>
                            <td><?= e($a['totalInscritos']) ?> / <?= e($a['vagas']) ?></td>
                            <td>
                                <?php if ($a['cancelada']) {
                                    drawEstado('Cancelada', 'erro');
                                } elseif (aulaJaComecou($a)) {
                                    drawEstado('Realizada', 'neutro');
                                } else {
                                    drawEstado('Agendada', 'sucesso');
                                } ?>
                            </td>
                            <td>
                                <?php if (!$a['cancelada'] && !aulaJaComecou($a)) { ?>
                                    <span class="acoes">
                                        <a href="/admin.php?sec=aulas&amp;editar=<?= e($a['id']) ?>" class="botao-secundario">Editar</a>
                                        <?php drawFormAcao('/actions/action_gestao.php', array('op' => 'cancelar_aula', 'idAula' => $a['id']), 'Cancelar aula', 'botao-perigo'); ?>
                                        <?php if ($a['totalRegistos'] == 0) {
                                            drawFormAcao('/actions/action_gestao.php', array('op' => 'apagar', 'idAula' => $a['id']), 'Apagar', 'botao-perigo');
                                        } ?>
                                    </span>
                                <?php } else { ?>
                                    <span class="nota-tabela">—</span>
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

function secEspacos($db) {
$salas = getSalas($db);
$equipamentos = getEquipamentos($db);

$estadosEquipamento = array('DISPONIVEL' => 'Disponível', 'EM_USO' => 'Em uso', 'EM_MANUTENCAO' => 'Em manutenção');
?>

<section class="bloco">
    <h2>Salas</h2>
    <?php if (empty($salas)) { ?>
        <p class="vazio">Ainda não existem salas.</p>
    <?php } else { ?>
        <div class="tabela-scroll">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Capacidade</th>
                        <th>Equipamentos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($salas as $s) { ?>
                        <tr>
                            <td><?= e($s['nome']) ?></td>
                            <td><?= e($s['capacidade']) ?> lugares</td>
                            <td><?= e($s['totalEquipamentos']) ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>
</section>

<section class="formulario largo">
    <h2>Nova sala</h2>
    <form action="/actions/action_gestao.php" method="post">
        <?php drawCsrf(); ?>
        <input type="hidden" name="op" value="sala">
        <fieldset class="campos">
            <p>
                <label for="nomeSala">Nome</label>
                <input type="text" id="nomeSala" name="nome" required>
            </p>
            <p>
                <label for="capacidade">Capacidade</label>
                <input type="number" id="capacidade" name="capacidade" min="1" required>
            </p>
        </fieldset>
        <button type="submit">Criar sala</button>
    </form>
</section>

<section class="bloco">
    <h2>Equipamentos</h2>
    <?php if (empty($equipamentos)) { ?>
        <p class="vazio">Ainda não existem equipamentos.</p>
    <?php } else { ?>
        <div class="tabela-scroll">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Sala</th>
                        <th>Estado</th>
                        <th>Alterar estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipamentos as $q) {
                        $estado = estadoEquipamento($q['estado']); ?>
                        <tr>
                            <td><?= e($q['nome']) ?></td>
                            <td><?= $q['sala'] === null ? '—' : e($q['sala']) ?></td>
                            <td><?php drawEstado($estado[0], $estado[1]); ?></td>
                            <td>
                                <form action="/actions/action_gestao.php" method="post" class="formulario-linha">
                                    <?php drawCsrf(); ?>
                                    <input type="hidden" name="op" value="equipamento_estado">
                                    <input type="hidden" name="idEquipamento" value="<?= e($q['id']) ?>">
                                    <label for="estado<?= e($q['id']) ?>" class="nota-tabela">Novo estado</label>
                                    <select id="estado<?= e($q['id']) ?>" name="estado">
                                        <?php foreach ($estadosEquipamento as $valor => $texto) { ?>
                                            <option value="<?= e($valor) ?>"<?= $valor === $q['estado'] ? ' selected' : '' ?>><?= e($texto) ?></option>
                                        <?php } ?>
                                    </select>
                                    <button type="submit" class="botao-secundario">Guardar</button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>
</section>

<section class="formulario largo">
    <h2>Novo equipamento</h2>
    <form action="/actions/action_gestao.php" method="post">
        <?php drawCsrf(); ?>
        <input type="hidden" name="op" value="equipamento">
        <fieldset class="campos">
            <p>
                <label for="nomeEquipamento">Nome</label>
                <input type="text" id="nomeEquipamento" name="nome" required>
            </p>
            <p>
                <label for="estadoNovo">Estado</label>
                <select id="estadoNovo" name="estado" required>
                    <?php foreach ($estadosEquipamento as $valor => $texto) { ?>
                        <option value="<?= e($valor) ?>"><?= e($texto) ?></option>
                    <?php } ?>
                </select>
            </p>
            <p>
                <label for="idSalaEquip">Sala</label>
                <select id="idSalaEquip" name="idSala">
                    <option value="0">Sem sala</option>
                    <?php foreach ($salas as $s) { ?>
                        <option value="<?= e($s['id']) ?>"><?= e($s['nome']) ?></option>
                    <?php } ?>
                </select>
            </p>
        </fieldset>
        <button type="submit">Adicionar equipamento</button>
    </form>
</section>
<?php }

function secCertificacoes($db) {
$pendentes = getCertificacoesPendentes($db);
?>

<section class="bloco">
    <h2>Por validar</h2>
    <?php if (empty($pendentes)) { ?>
        <p class="vazio">Não existem certificações por validar.</p>
    <?php } else { ?>
        <div class="tabela-scroll">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Treinador</th>
                        <th>Modalidade</th>
                        <th>Certificação</th>
                        <th>Entidade</th>
                        <th>Obtida em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendentes as $c) {
                        $campos = array('idTreinador' => $c['idTreinador'], 'idModalidade' => $c['idModalidade']); ?>
                        <tr>
                            <td><?= e($c['treinador']) ?></td>
                            <td><?= e($c['modalidade']) ?></td>
                            <td><?= e($c['nome']) ?></td>
                            <td><?= e($c['entidadeEmissora']) ?></td>
                            <td><time datetime="<?= e($c['dataObtencao']) ?>"><?= formatarData($c['dataObtencao']) ?></time></td>
                            <td>
                                <div class="acoes">
                                    <?php drawFormAcao('/actions/action_gestao.php', $campos + array('op' => 'validar'), 'Validar');
                                    drawFormAcao('/actions/action_gestao.php', $campos + array('op' => 'rejeitar'), 'Rejeitar', 'botao-perigo'); ?>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>
</section>
<?php }

// Formulário de criar uma aula ($aula = null) ou de editar uma aula futura.
// Se a aula já tem inscritos, a modalidade, a data e o horário ficam só de leitura.
function drawFormAula($modalidades, $salas, $treinadores, $aula) {
    $edita = $aula !== null;
    $bloqueado = $edita && $aula['totalInscritos'] > 0;
    $v = function ($campo, $padrao = '') use ($aula) {
        return $aula !== null ? $aula[$campo] : $padrao;
    }; ?>
<section class="formulario largo">
    <h2><?= $edita ? 'Editar aula' : 'Nova aula' ?></h2>
    <?php if (empty($modalidades) || empty($salas) || empty($treinadores)) { ?>
        <p>É preciso existir pelo menos uma modalidade, uma sala e um treinador.</p>
    <?php } else { ?>
        <form action="/actions/action_gestao.php" method="post">
            <?php drawCsrf(); ?>
            <input type="hidden" name="op" value="<?= $edita ? 'editar_aula' : 'criar' ?>">
            <?php if ($edita) { ?>
                <input type="hidden" name="idAula" value="<?= e($aula['id']) ?>">
            <?php } ?>

            <fieldset class="campos">
                <p>
                    <label for="idModalidade">Modalidade</label>
                    <select id="idModalidade" name="idModalidade" required<?= $bloqueado ? ' disabled' : '' ?>>
                        <option value="">Escolher…</option>
                        <?php foreach ($modalidades as $m) { ?>
                            <option value="<?= e($m['id']) ?>"<?= (int) $v('idModalidade') === (int) $m['id'] ? ' selected' : '' ?>><?= e($m['nome']) ?></option>
                        <?php } ?>
                    </select>
                    <?php if ($bloqueado) { ?>
                        <input type="hidden" name="idModalidade" value="<?= e($aula['idModalidade']) ?>">
                    <?php } ?>
                </p>
                <p>
                    <label for="idTreinador">Treinador</label>
                    <select id="idTreinador" name="idTreinador" required>
                        <option value="">Escolher…</option>
                        <?php foreach ($treinadores as $t) { ?>
                            <option value="<?= e($t['id']) ?>"<?= (int) $v('idTreinador') === (int) $t['id'] ? ' selected' : '' ?>>
                                <?= e($t['nome']) ?><?= $t['modalidades'] ? ' (' . e($t['modalidades']) . ')' : ' (sem certificações validadas)' ?>
                            </option>
                        <?php } ?>
                    </select>
                </p>
                <p>
                    <label for="nivel">Nível</label>
                    <select id="nivel" name="nivel" required>
                        <?php foreach (array('INICIANTE', 'INTERMEDIO', 'AVANCADO') as $n) { ?>
                            <option value="<?= e($n) ?>"<?= $v('nivel') === $n ? ' selected' : '' ?>><?= e(nomeNivel($n)) ?></option>
                        <?php } ?>
                    </select>
                </p>
            </fieldset>

            <fieldset class="campos">
                <p>
                    <label for="idSala">Sala</label>
                    <select id="idSala" name="idSala" required>
                        <option value="">Escolher…</option>
                        <?php foreach ($salas as $s) { ?>
                            <option value="<?= e($s['id']) ?>"<?= (int) $v('idSala') === (int) $s['id'] ? ' selected' : '' ?>><?= e($s['nome']) ?> (<?= e($s['capacidade']) ?> lugares)</option>
                        <?php } ?>
                    </select>
                </p>
                <p>
                    <label for="vagas">Vagas</label>
                    <input type="number" id="vagas" name="vagas" min="<?= $edita ? max(1, (int) $aula['totalInscritos']) : 1 ?>" value="<?= e($v('vagas')) ?>" required>
                    <small class="ajuda">Máximo: a capacidade da sala.<?= $edita ? ' Mínimo: o número de inscritos (' . e($aula['totalInscritos']) . ').' : '' ?></small>
                </p>
            </fieldset>

            <fieldset class="campos">
                <p>
                    <label for="data">Data</label>
                    <input type="date" id="data" name="data" value="<?= e($v('data')) ?>"<?= $edita ? '' : ' min="' . date('Y-m-d') . '"' ?> required<?= $bloqueado ? ' readonly' : '' ?>>
                </p>
                <p>
                    <label for="horaInicio">Hora de início</label>
                    <input type="time" id="horaInicio" name="horaInicio" value="<?= e($v('horaInicio')) ?>" required<?= $bloqueado ? ' readonly' : '' ?>>
                </p>
                <p>
                    <label for="horaFim">Hora de fim</label>
                    <input type="time" id="horaFim" name="horaFim" value="<?= e($v('horaFim')) ?>" required<?= $bloqueado ? ' readonly' : '' ?>>
                </p>
            </fieldset>

            <small class="ajuda">
                A sala e o treinador têm de estar livres nesse horário.
                <?= $bloqueado ? 'Esta aula tem inscritos, por isso a modalidade, a data e o horário não podem ser alterados: cancele a aula e crie outra.' : '' ?>
            </small>

            <span class="acoes">
                <button type="submit"><?= $edita ? 'Guardar alterações' : 'Criar aula' ?></button>
                <?php if ($edita) { ?>
                    <a href="/admin.php?sec=aulas" class="botao-secundario">Voltar</a>
                <?php } ?>
            </span>
        </form>
    <?php } ?>
</section>
<?php }
