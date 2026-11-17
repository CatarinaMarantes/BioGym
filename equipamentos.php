<?php
require_once __DIR__ . '/utils/session.php';
require_once __DIR__ . '/database/connection.php';
require_once __DIR__ . '/database/gestao.php';
require_once __DIR__ . '/templates/common.php';

requerLogin();

$equipamentos = getEquipamentos(getDatabaseConnection());

drawHeader('Equipamentos');
drawCabecalhoPagina('BioGym · Espaços', 'Estado dos equipamentos', 'Vê o que está livre, em uso ou em manutenção antes de vires treinar.');
?>

<?php if (empty($equipamentos)) { ?>

    <p class="vazio">Ainda não há equipamentos registados.</p>

<?php } else { ?>

    <section class="tabela-scroll">
        <table class="tabela">
            <caption>Equipamentos do ginásio</caption>
            <thead>
                <tr>
                    <th>Equipamento</th>
                    <th>Sala</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($equipamentos as $q) {
                    $estado = estadoEquipamento($q['estado']); ?>
                    <tr>
                        <td><?= e($q['nome']) ?></td>
                        <td><?= $q['sala'] ? e($q['sala']) : '—' ?></td>
                        <td><?php drawEstado($estado[0], $estado[1]); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </section>

<?php } ?>

<?php drawFooter(); ?>
