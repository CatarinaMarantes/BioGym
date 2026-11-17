<?php
require_once __DIR__ . '/gestao.php';
require_once __DIR__ . '/utilizador.php';

// Consulta base das aulas, com o número de inscrições ativas
function sqlAulas() {
    return "
        SELECT
            Aula.id,
            Aula.data,
            Aula.horaInicio,
            Aula.horaFim,
            Aula.nivel,
            Aula.vagas,
            Aula.idTreinador,
            Aula.idSala,
            Aula.idModalidade,
            Aula.cancelada,
            Modalidade.nome AS modalidade,
            Sala.nome AS sala,
            Sala.capacidade AS capacidadeSala,
            Utilizador.nome AS treinador,
            (
                SELECT COUNT(*)
                FROM Inscricao
                WHERE Inscricao.idAula = Aula.id
                  AND Inscricao.estado = 'ATIVA'
            ) AS totalInscritos,
            (
                SELECT COUNT(*)
                FROM Inscricao
                WHERE Inscricao.idAula = Aula.id
            ) AS totalRegistos,
            (
                SELECT ROUND(AVG(Inscricao.notaAula), 1)
                FROM Inscricao
                WHERE Inscricao.idAula = Aula.id AND Inscricao.notaAula IS NOT NULL
            ) AS mediaAula,
            (
                SELECT COUNT(Inscricao.notaAula)
                FROM Inscricao
                WHERE Inscricao.idAula = Aula.id
            ) AS totalNotas
        FROM Aula
        JOIN Modalidade ON Aula.idModalidade = Modalidade.id
        JOIN Sala ON Aula.idSala = Sala.id
        JOIN Treinador ON Aula.idTreinador = Treinador.idUtilizador
        JOIN Utilizador ON Treinador.idUtilizador = Utilizador.id
    ";
}

// Aulas que ainda não começaram e não foram canceladas.
// $filtros (todos opcionais): modalidade (id), nivel, data (Y-m-d), treinador (id).
function getAulasDisponiveis($db, $filtros = array()) {
    $sql = sqlAulas() . '
        WHERE (Aula.data > ? OR (Aula.data = ? AND Aula.horaInicio > ?))
          AND Aula.cancelada = 0
    ';
    $params = array(date('Y-m-d'), date('Y-m-d'), date('H:i'));

    if (!empty($filtros['modalidade'])) {
        $sql .= ' AND Aula.idModalidade = ?';
        $params[] = $filtros['modalidade'];
    }
    if (!empty($filtros['nivel'])) {
        $sql .= ' AND Aula.nivel = ?';
        $params[] = $filtros['nivel'];
    }
    if (!empty($filtros['data'])) {
        $sql .= ' AND Aula.data = ?';
        $params[] = $filtros['data'];
    }
    if (!empty($filtros['treinador'])) {
        $sql .= ' AND Aula.idTreinador = ?';
        $params[] = $filtros['treinador'];
    }

    $stmt = $db->prepare($sql . ' ORDER BY Aula.data ASC, Aula.horaInicio ASC');
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getAulaPorId($db, $idAula) {
    $stmt = $db->prepare(sqlAulas() . ' WHERE Aula.id = ?');
    $stmt->execute(array($idAula));
    return $stmt->fetch();
}

// Todas as aulas (passadas e futuras), para a administração
function getTodasAulas($db) {
    return $db->query(sqlAulas() . ' ORDER BY Aula.data DESC, Aula.horaInicio DESC')->fetchAll();
}

function getAulasTreinador($db, $idTreinador) {
    $stmt = $db->prepare(sqlAulas() . ' WHERE Aula.idTreinador = ? ORDER BY Aula.data DESC, Aula.horaInicio DESC');
    $stmt->execute(array($idTreinador));
    return $stmt->fetchAll();
}

// Membros com inscrição ativa numa aula
function getInscritosAula($db, $idAula) {
    $stmt = $db->prepare("
        SELECT Utilizador.nome, Utilizador.email, Utilizador.telemovel
        FROM Inscricao
        JOIN Utilizador ON Inscricao.idMembro = Utilizador.id
        WHERE Inscricao.idAula = ? AND Inscricao.estado = 'ATIVA'
        ORDER BY Utilizador.nome
    ");
    $stmt->execute(array($idAula));
    return $stmt->fetchAll();
}

function aulaJaComecou($aula) {
    return dataHoraJaComecou($aula['data'], $aula['horaInicio']);
}

// ---------------------------------------------------------------------
// Inscrições
// ---------------------------------------------------------------------

// Devolve array(idAula => estado) com as inscrições do membro
function getEstadosInscricao($db, $idMembro) {
    $stmt = $db->prepare('SELECT idAula, estado FROM Inscricao WHERE idMembro = ?');
    $stmt->execute(array($idMembro));
    $estados = array();
    foreach ($stmt->fetchAll() as $linha) {
        $estados[$linha['idAula']] = $linha['estado'];
    }
    return $estados;
}

// Todas as inscrições do membro, com a informação da aula
function getInscricoesMembro($db, $idMembro) {
    $stmt = $db->prepare('
        SELECT
            Inscricao.idAula,
            Inscricao.estado,
            Inscricao.notaAula,
            Inscricao.notaTreinador,
            Inscricao.comentario,
            Aula.cancelada,
            Aula.data,
            Aula.horaInicio,
            Aula.horaFim,
            Modalidade.nome AS modalidade,
            Sala.nome AS sala,
            Utilizador.nome AS treinador
        FROM Inscricao
        JOIN Aula ON Inscricao.idAula = Aula.id
        JOIN Modalidade ON Aula.idModalidade = Modalidade.id
        JOIN Sala ON Aula.idSala = Sala.id
        JOIN Utilizador ON Aula.idTreinador = Utilizador.id
        WHERE Inscricao.idMembro = ?
        ORDER BY Aula.data ASC, Aula.horaInicio ASC
    ');
    $stmt->execute(array($idMembro));
    return $stmt->fetchAll();
}

// Inscreve o membro na aula. Devolve null se correu bem ou o texto do erro.
// Regras: aula existente e ainda não iniciada, membro não é o treinador da aula,
// sem inscrição ativa, com vagas, e sem outra atividade no mesmo horário.
function inscreverMembro($db, $idMembro, $idAula) {
    return emTransacao($db, function () use ($db, $idMembro, $idAula) {
        $aula = getAulaPorId($db, $idAula);
        if (!$aula) return 'A aula não existe.';
        if ($aula['cancelada']) return 'Esta aula foi cancelada.';
        if (aulaJaComecou($aula)) return 'A aula já começou ou já decorreu.';
        if ((int) $aula['idTreinador'] === (int) $idMembro) return 'Não pode inscrever-se numa aula que leciona.';

        $stmt = $db->prepare('SELECT estado FROM Inscricao WHERE idMembro = ? AND idAula = ?');
        $stmt->execute(array($idMembro, $idAula));
        $estado = $stmt->fetchColumn();

        if ($estado === 'ATIVA') return 'Já está inscrito nesta aula.';
        if ($aula['totalInscritos'] >= $aula['vagas']) return 'A aula está lotada.';
        if (membroOcupado($db, $idMembro, $aula['data'], $aula['horaInicio'], $aula['horaFim'])) {
            return 'Já tem outra aula ou sessão de personal training nesse horário.';
        }

        if ($estado === 'CANCELADA') {
            // A chave primária é (membro, aula): reativa-se a inscrição cancelada
            $stmt = $db->prepare("UPDATE Inscricao SET estado = 'ATIVA', dataInscricao = ? WHERE idMembro = ? AND idAula = ?");
            $stmt->execute(array(date('Y-m-d H:i'), $idMembro, $idAula));
        } else {
            $stmt = $db->prepare("INSERT INTO Inscricao (idMembro, idAula, dataInscricao, estado) VALUES (?, ?, ?, 'ATIVA')");
            $stmt->execute(array($idMembro, $idAula, date('Y-m-d H:i')));
        }
        return null;
    });
}

// Cancela a inscrição ativa do membro. Devolve null se correu bem ou o texto do erro.
function cancelarInscricao($db, $idMembro, $idAula) {
    $aula = getAulaPorId($db, $idAula);
    $stmt = $db->prepare('SELECT estado FROM Inscricao WHERE idMembro = ? AND idAula = ?');
    $stmt->execute(array($idMembro, $idAula));
    $estado = $stmt->fetchColumn();

    if (!$aula || $estado !== 'ATIVA') return 'Não tem uma inscrição ativa nesta aula.';
    if (aulaJaComecou($aula)) return 'A aula já começou ou já decorreu; já não é possível cancelar.';

    $stmt = $db->prepare("UPDATE Inscricao SET estado = 'CANCELADA' WHERE idMembro = ? AND idAula = ?");
    $stmt->execute(array($idMembro, $idAula));
    return null;
}

// ---------------------------------------------------------------------
// Avaliação de aulas
// ---------------------------------------------------------------------

// O membro avalia a aula e o treinador (1 a 5 + comentário opcional).
// Só se avaliam aulas em que a inscrição está ativa e que já terminaram, e uma só vez.
// Devolve null se correu bem ou o texto do erro.
function avaliarAula($db, $idMembro, $idAula, $notaAula, $notaTreinador, $comentario) {
    if ($notaAula < 1 || $notaAula > 5 || $notaTreinador < 1 || $notaTreinador > 5) {
        return 'As notas têm de estar entre 1 e 5.';
    }
    if (strlen($comentario) > 500) return 'O comentário não pode ter mais de 500 caracteres.';

    return emTransacao($db, function () use ($db, $idMembro, $idAula, $notaAula, $notaTreinador, $comentario) {
        $aula = getAulaPorId($db, $idAula);
        $stmt = $db->prepare('SELECT estado, notaAula, notaTreinador FROM Inscricao WHERE idMembro = ? AND idAula = ?');
        $stmt->execute(array($idMembro, $idAula));
        $inscricao = $stmt->fetch();

        if (!$aula || !$inscricao || $inscricao['estado'] !== 'ATIVA' || $aula['cancelada']) {
            return 'Só pode avaliar aulas em que esteve inscrito.';
        }
        if (!dataHoraJaComecou($aula['data'], $aula['horaFim'])) return 'Só pode avaliar a aula depois de ela terminar.';
        if ($inscricao['notaAula'] !== null || $inscricao['notaTreinador'] !== null) return 'Já avaliou esta aula.';

        $stmt = $db->prepare('UPDATE Inscricao SET notaAula = ?, notaTreinador = ?, comentario = ? WHERE idMembro = ? AND idAula = ?');
        $stmt->execute(array($notaAula, $notaTreinador, $comentario === '' ? null : $comentario, $idMembro, $idAula));
        return null;
    });
}

// ---------------------------------------------------------------------
// Gestão de aulas (administração)
// ---------------------------------------------------------------------

function getModalidades($db) {
    return $db->query('SELECT id, nome FROM Modalidade ORDER BY nome')->fetchAll();
}

// Validação comum a criar e editar uma aula. $c: modalidade, sala, treinador, data, inicio,
// fim, nivel, vagas. $ignorarAula: id da aula que está a ser editada (0 ao criar).
// Devolve null se estiver tudo bem ou o texto do erro.
function validarAula($db, $c, $ignorarAula) {
    $stmt = $db->prepare('SELECT 1 FROM Modalidade WHERE id = ?');
    $stmt->execute(array($c['modalidade']));
    if (!$stmt->fetch()) return 'Modalidade inválida.';

    $stmt = $db->prepare('SELECT capacidade FROM Sala WHERE id = ?');
    $stmt->execute(array($c['sala']));
    $capacidade = $stmt->fetchColumn();
    if ($capacidade === false) return 'Sala inválida.';

    if (!treinadorExiste($db, $c['treinador'])) return 'Treinador inválido.';
    $treinador = getUtilizadorPorId($db, $c['treinador']);
    if (!$treinador['ativo']) return 'O treinador escolhido tem a conta desativada.';

    if ($c['vagas'] < 1) return 'O número de vagas deve ser pelo menos 1.';
    if ($c['vagas'] > $capacidade) return 'As vagas (' . $c['vagas'] . ') não podem exceder a capacidade da sala (' . $capacidade . ').';

    $stmt = $db->prepare('SELECT 1 FROM Certificacao WHERE idTreinador = ? AND idModalidade = ? AND validada = 1');
    $stmt->execute(array($c['treinador'], $c['modalidade']));
    if (!$stmt->fetch()) return 'O treinador não tem certificação validada para esta modalidade.';

    if (salaOcupada($db, $c['sala'], $c['data'], $c['inicio'], $c['fim'], $ignorarAula)) return 'A sala já está ocupada nesse horário.';
    if (treinadorOcupado($db, $c['treinador'], $c['data'], $c['inicio'], $c['fim'], $ignorarAula)) return 'O treinador já tem uma aula ou sessão nesse horário.';
    return null;
}

// Cria uma aula. Devolve null se correu bem ou o texto do erro.
function criarAula($db, $idModalidade, $idSala, $idTreinador, $data, $inicio, $fim, $nivel, $vagas) {
    return emTransacao($db, function () use ($db, $idModalidade, $idSala, $idTreinador, $data, $inicio, $fim, $nivel, $vagas) {
        if (!dataHoraNoFuturo($data, $inicio)) return 'A aula tem de ser agendada para o futuro.';

        $erro = validarAula($db, array(
            'modalidade' => $idModalidade, 'sala' => $idSala, 'treinador' => $idTreinador,
            'data' => $data, 'inicio' => $inicio, 'fim' => $fim, 'vagas' => $vagas
        ), 0);
        if ($erro !== null) return $erro;

        $stmt = $db->prepare('
            INSERT INTO Aula (data, horaInicio, horaFim, nivel, vagas, idModalidade, idSala, idTreinador)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute(array($data, $inicio, $fim, $nivel, $vagas, $idModalidade, $idSala, $idTreinador));
        return null;
    });
}

// Edita uma aula futura. Se já tem inscritos, a modalidade, a data e o horário ficam
// bloqueados (os inscritos escolheram aquele horário); as vagas não podem ficar
// abaixo do número de inscritos. Devolve null se correu bem ou o texto do erro.
function editarAula($db, $idAula, $idModalidade, $idSala, $idTreinador, $data, $inicio, $fim, $nivel, $vagas) {
    return emTransacao($db, function () use ($db, $idAula, $idModalidade, $idSala, $idTreinador, $data, $inicio, $fim, $nivel, $vagas) {
        $aula = getAulaPorId($db, $idAula);
        if (!$aula) return 'A aula não existe.';
        if ($aula['cancelada']) return 'Esta aula foi cancelada e já não pode ser editada.';
        if (aulaJaComecou($aula)) return 'Só se podem editar aulas que ainda não começaram.';

        $mudouHorario = $aula['data'] !== $data || $aula['horaInicio'] !== $inicio
            || $aula['horaFim'] !== $fim || (int) $aula['idModalidade'] !== (int) $idModalidade;
        if ($mudouHorario) {
            if ($aula['totalInscritos'] > 0) {
                return 'Esta aula tem inscritos: só pode alterar a sala, o treinador, o nível e as vagas. '
                     . 'Para mudar a modalidade, a data ou o horário, cancele a aula e crie outra.';
            }
            if (!dataHoraNoFuturo($data, $inicio)) return 'A aula tem de ser agendada para o futuro.';
        }
        if ($vagas < $aula['totalInscritos']) {
            return 'Já existem ' . $aula['totalInscritos'] . ' inscritos: as vagas não podem ser menos do que isso.';
        }

        $erro = validarAula($db, array(
            'modalidade' => $idModalidade, 'sala' => $idSala, 'treinador' => $idTreinador,
            'data' => $data, 'inicio' => $inicio, 'fim' => $fim, 'vagas' => $vagas
        ), $idAula);
        if ($erro !== null) return $erro;

        $stmt = $db->prepare('
            UPDATE Aula SET data = ?, horaInicio = ?, horaFim = ?, nivel = ?, vagas = ?,
                            idModalidade = ?, idSala = ?, idTreinador = ?
            WHERE id = ?
        ');
        $stmt->execute(array($data, $inicio, $fim, $nivel, $vagas, $idModalidade, $idSala, $idTreinador, $idAula));
        return null;
    });
}

// Cancela uma aula futura e as inscrições ativas nela. Devolve null se correu bem ou o texto do erro.
function cancelarAula($db, $idAula) {
    return emTransacao($db, function () use ($db, $idAula) {
        $aula = getAulaPorId($db, $idAula);
        if (!$aula) return 'A aula não existe.';
        if ($aula['cancelada']) return 'A aula já está cancelada.';
        if (aulaJaComecou($aula)) return 'A aula já começou ou já decorreu.';

        $stmt = $db->prepare('UPDATE Aula SET cancelada = 1 WHERE id = ?');
        $stmt->execute(array($idAula));
        $stmt = $db->prepare("UPDATE Inscricao SET estado = 'CANCELADA' WHERE idAula = ? AND estado = 'ATIVA'");
        $stmt->execute(array($idAula));
        return null;
    });
}

// Só se podem apagar aulas sem qualquer inscrição (mesmo cancelada)
function apagarAula($db, $idAula) {
    $aula = getAulaPorId($db, $idAula);
    if (!$aula) return 'A aula não existe.';
    if ($aula['totalRegistos'] > 0) return 'Esta aula tem inscrições registadas e não pode ser apagada.';

    $stmt = $db->prepare('DELETE FROM Aula WHERE id = ?');
    $stmt->execute(array($idAula));
    return null;
}
