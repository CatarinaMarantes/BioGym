<?php

function getResumoMembro($db, $idMembro) {

    $sql = '
        SELECT COUNT(*)
        FROM Inscricao
        JOIN Aula ON Inscricao.idAula = Aula.id
        WHERE Inscricao.idMembro = ?
          AND Inscricao.estado = \'ATIVA\'
          AND Aula.data >= ?
    ';

    $stmt = $db->prepare($sql);
    $stmt->execute(array($idMembro, date('Y-m-d')));
    $aulas = (int) $stmt->fetchColumn();

    $sql = '
        SELECT COUNT(*)
        FROM SessaoPT
        WHERE idMembro = ?
          AND estado IN (\'PENDENTE\', \'CONFIRMADA\')
          AND data >= ?
    ';

    $stmt = $db->prepare($sql);
    $stmt->execute(array($idMembro, date('Y-m-d')));
    $sessoes = (int) $stmt->fetchColumn();

    $sql = '
        SELECT data, peso, altura, percentagemGordura
        FROM AvaliacaoFisica
        WHERE idMembro = ?
        ORDER BY data DESC, id DESC
        LIMIT 1
    ';

    $stmt = $db->prepare($sql);
    $stmt->execute(array($idMembro));
    $avaliacao = $stmt->fetch();

    return array(
        'aulas' => $aulas,
        'sessoes' => $sessoes,
        'avaliacao' => $avaliacao
    );
}
