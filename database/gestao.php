<?php
// Funções de base de dados da parte de gestão: horários, personal training,
// avaliações e planos, certificações, salas e equipamentos.
require_once __DIR__ . '/utilizador.php';

// =====================================================================
// Horários (sobreposições)
// =====================================================================

// Verificações de sobreposição de horários.
// Dois intervalos do mesmo dia sobrepõem-se se cada um começa antes de o outro acabar.

// $ignorarAula: id de uma aula a não contar (ao editá-la); aulas canceladas nunca contam.
function treinadorOcupado($db, $idTreinador, $data, $inicio, $fim, $ignorarAula = 0) {
    $stmt = $db->prepare('
        SELECT COUNT(*) FROM Aula
        WHERE idTreinador = ? AND data = ? AND horaInicio < ? AND ? < horaFim
          AND cancelada = 0 AND id <> ?
    ');
    $stmt->execute(array($idTreinador, $data, $fim, $inicio, $ignorarAula));
    if ($stmt->fetchColumn() > 0) return true;

    $stmt = $db->prepare("
        SELECT COUNT(*) FROM SessaoPT
        WHERE idTreinador = ? AND data = ? AND estado IN ('PENDENTE', 'CONFIRMADA')
          AND horaInicio < ? AND ? < horaFim
    ");
    $stmt->execute(array($idTreinador, $data, $fim, $inicio));
    return $stmt->fetchColumn() > 0;
}

function membroOcupado($db, $idMembro, $data, $inicio, $fim) {
    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM Inscricao
        JOIN Aula ON Inscricao.idAula = Aula.id
        WHERE Inscricao.idMembro = ? AND Inscricao.estado = 'ATIVA' AND Aula.cancelada = 0
          AND Aula.data = ? AND Aula.horaInicio < ? AND ? < Aula.horaFim
    ");
    $stmt->execute(array($idMembro, $data, $fim, $inicio));
    if ($stmt->fetchColumn() > 0) return true;

    $stmt = $db->prepare("
        SELECT COUNT(*) FROM SessaoPT
        WHERE idMembro = ? AND data = ? AND estado IN ('PENDENTE', 'CONFIRMADA')
          AND horaInicio < ? AND ? < horaFim
    ");
    $stmt->execute(array($idMembro, $data, $fim, $inicio));
    return $stmt->fetchColumn() > 0;
}

function salaOcupada($db, $idSala, $data, $inicio, $fim, $ignorarAula = 0) {
    $stmt = $db->prepare('
        SELECT COUNT(*) FROM Aula
        WHERE idSala = ? AND data = ? AND horaInicio < ? AND ? < horaFim
          AND cancelada = 0 AND id <> ?
    ');
    $stmt->execute(array($idSala, $data, $fim, $inicio, $ignorarAula));
    return $stmt->fetchColumn() > 0;
}

// =====================================================================
// Sessões de personal training
// =====================================================================

// Sessões de personal training

function getSessoesMembro($db, $idMembro) {
    $stmt = $db->prepare('
        SELECT SessaoPT.id, SessaoPT.data, SessaoPT.horaInicio, SessaoPT.horaFim, SessaoPT.estado,
               Utilizador.nome AS treinador
        FROM SessaoPT
        JOIN Utilizador ON SessaoPT.idTreinador = Utilizador.id
        WHERE SessaoPT.idMembro = ?
        ORDER BY SessaoPT.data ASC, SessaoPT.horaInicio ASC
    ');
    $stmt->execute(array($idMembro));
    return $stmt->fetchAll();
}

function getSessoesTreinador($db, $idTreinador) {
    $stmt = $db->prepare('
        SELECT SessaoPT.id, SessaoPT.data, SessaoPT.horaInicio, SessaoPT.horaFim, SessaoPT.estado,
               Utilizador.nome AS membro
        FROM SessaoPT
        JOIN Utilizador ON SessaoPT.idMembro = Utilizador.id
        WHERE SessaoPT.idTreinador = ?
        ORDER BY SessaoPT.data ASC, SessaoPT.horaInicio ASC
    ');
    $stmt->execute(array($idTreinador));
    return $stmt->fetchAll();
}

// O membro marca uma sessão (fica PENDENTE até o treinador confirmar).
// Devolve null se correu bem ou o texto do erro.
function criarSessaoPT($db, $idMembro, $idTreinador, $data, $inicio, $fim) {
    return emTransacao($db, function () use ($db, $idMembro, $idTreinador, $data, $inicio, $fim) {
        if (!treinadorExiste($db, $idTreinador)) return 'Treinador inválido.';
        if ((int) $idMembro === (int) $idTreinador) return 'Não pode marcar uma sessão consigo próprio.';
        if (!dataHoraNoFuturo($data, $inicio)) return 'A sessão tem de ser marcada para o futuro.';
        if (treinadorOcupado($db, $idTreinador, $data, $inicio, $fim)) return 'O treinador não está disponível nesse horário.';
        if (membroOcupado($db, $idMembro, $data, $inicio, $fim)) return 'Já tem outra aula ou sessão nesse horário.';

        $stmt = $db->prepare("
            INSERT INTO SessaoPT (data, horaInicio, horaFim, estado, idMembro, idTreinador)
            VALUES (?, ?, ?, 'PENDENTE', ?, ?)
        ");
        $stmt->execute(array($data, $inicio, $fim, $idMembro, $idTreinador));
        return null;
    });
}

function getSessaoPT($db, $idSessao) {
    $stmt = $db->prepare('SELECT * FROM SessaoPT WHERE id = ?');
    $stmt->execute(array($idSessao));
    return $stmt->fetch();
}

function atualizarEstadoSessao($db, $idSessao, $novoEstado) {
    $stmt = $db->prepare('UPDATE SessaoPT SET estado = ? WHERE id = ?');
    $stmt->execute(array($novoEstado, $idSessao));
}

// O membro cancela uma sessão sua que ainda não começou
function cancelarSessaoMembro($db, $idSessao, $idMembro) {
    $sessao = getSessaoPT($db, $idSessao);
    if (!$sessao || (int) $sessao['idMembro'] !== (int) $idMembro) return 'Sessão não encontrada.';
    if (!in_array($sessao['estado'], array('PENDENTE', 'CONFIRMADA'))) return 'Esta sessão já não pode ser cancelada.';
    if (dataHoraJaComecou($sessao['data'], $sessao['horaInicio'])) return 'A sessão já começou ou já decorreu.';

    atualizarEstadoSessao($db, $idSessao, 'CANCELADA');
    return null;
}

// O treinador confirma, recusa (cancela) ou conclui uma sessão sua.
// $acao: 'confirmar' (PENDENTE → CONFIRMADA), 'recusar' (PENDENTE/CONFIRMADA → CANCELADA),
//        'concluir' (CONFIRMADA → CONCLUIDA, só depois de a sessão começar).
function atualizarSessaoTreinador($db, $idSessao, $idTreinador, $acao) {
    $sessao = getSessaoPT($db, $idSessao);
    if (!$sessao || (int) $sessao['idTreinador'] !== (int) $idTreinador) return 'Sessão não encontrada.';

    $comecou = dataHoraJaComecou($sessao['data'], $sessao['horaInicio']);

    if ($acao === 'confirmar') {
        if ($sessao['estado'] !== 'PENDENTE') return 'Só se podem confirmar sessões pendentes.';
        if ($comecou) return 'A hora desta sessão já passou.';
        atualizarEstadoSessao($db, $idSessao, 'CONFIRMADA');
    } elseif ($acao === 'recusar') {
        if (!in_array($sessao['estado'], array('PENDENTE', 'CONFIRMADA'))) return 'Esta sessão já não pode ser cancelada.';
        if ($comecou) return 'A hora desta sessão já passou.';
        atualizarEstadoSessao($db, $idSessao, 'CANCELADA');
    } elseif ($acao === 'concluir') {
        if ($sessao['estado'] !== 'CONFIRMADA') return 'Só se podem concluir sessões confirmadas.';
        if (!$comecou) return 'A sessão ainda não começou.';
        atualizarEstadoSessao($db, $idSessao, 'CONCLUIDA');
    } else {
        return 'Ação inválida.';
    }
    return null;
}

// =====================================================================
// Avaliações físicas e planos de treino
// =====================================================================

// Cada plano pertence ao treinador que o criou: só esse treinador o vê e o edita.
function getPlanos($db, $idTreinador) {
    $stmt = $db->prepare('
        SELECT PlanoTreino.id, PlanoTreino.nome, PlanoTreino.objetivo,
               PlanoTreino.frequenciaSemanal, PlanoTreino.duracaoSemanas,
               (SELECT COUNT(*) FROM ItemPlano WHERE ItemPlano.idPlanoTreino = PlanoTreino.id) AS totalExercicios
        FROM PlanoTreino
        WHERE PlanoTreino.idTreinador = ?
        ORDER BY PlanoTreino.nome
    ');
    $stmt->execute(array($idTreinador));
    return $stmt->fetchAll();
}

// $idTreinador (opcional): devolve o plano só se for desse treinador
function getPlano($db, $idPlano, $idTreinador = null) {
    $stmt = $db->prepare('SELECT * FROM PlanoTreino WHERE id = ?');
    $stmt->execute(array($idPlano));
    $plano = $stmt->fetch();
    if ($plano && $idTreinador !== null && (int) $plano['idTreinador'] !== (int) $idTreinador) return false;
    return $plano;
}

function getItensPlano($db, $idPlano) {
    $stmt = $db->prepare('SELECT * FROM ItemPlano WHERE idPlanoTreino = ? ORDER BY ordem');
    $stmt->execute(array($idPlano));
    return $stmt->fetchAll();
}

function criarPlano($db, $idTreinador, $nome, $objetivo, $frequencia, $duracao) {
    $stmt = $db->prepare('INSERT INTO PlanoTreino (nome, objetivo, frequenciaSemanal, duracaoSemanas, idTreinador) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute(array($nome, $objetivo === '' ? null : $objetivo, $frequencia, $duracao, $idTreinador));
    return $db->lastInsertId();
}

// Acrescenta um exercício no fim de um plano do treinador
function adicionarItemPlano($db, $idTreinador, $idPlano, $exercicio, $series, $repeticoes) {
    return emTransacao($db, function () use ($db, $idTreinador, $idPlano, $exercicio, $series, $repeticoes) {
        if (!getPlano($db, $idPlano, $idTreinador)) return 'O plano não existe.';

        $stmt = $db->prepare('SELECT COALESCE(MAX(ordem), 0) + 1 FROM ItemPlano WHERE idPlanoTreino = ?');
        $stmt->execute(array($idPlano));
        $ordem = (int) $stmt->fetchColumn();

        $stmt = $db->prepare('INSERT INTO ItemPlano (idPlanoTreino, ordem, exercicio, series, repeticoes) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute(array($idPlano, $ordem, $exercicio, $series, $repeticoes));
        return null;
    });
}

// Avaliações físicas

function getAvaliacoesMembro($db, $idMembro) {
    $stmt = $db->prepare('
        SELECT AvaliacaoFisica.*,
               Utilizador.nome AS treinador,
               PlanoTreino.nome AS plano
        FROM AvaliacaoFisica
        JOIN Utilizador ON AvaliacaoFisica.idTreinador = Utilizador.id
        JOIN PlanoTreino ON AvaliacaoFisica.idPlanoTreino = PlanoTreino.id
        WHERE AvaliacaoFisica.idMembro = ?
        ORDER BY AvaliacaoFisica.data DESC, AvaliacaoFisica.id DESC
    ');
    $stmt->execute(array($idMembro));
    return $stmt->fetchAll();
}

function getAvaliacoesRegistadasPor($db, $idTreinador) {
    $stmt = $db->prepare('
        SELECT AvaliacaoFisica.*,
               Utilizador.nome AS membro,
               PlanoTreino.nome AS plano
        FROM AvaliacaoFisica
        JOIN Utilizador ON AvaliacaoFisica.idMembro = Utilizador.id
        JOIN PlanoTreino ON AvaliacaoFisica.idPlanoTreino = PlanoTreino.id
        WHERE AvaliacaoFisica.idTreinador = ?
        ORDER BY AvaliacaoFisica.data DESC, AvaliacaoFisica.id DESC
    ');
    $stmt->execute(array($idTreinador));
    return $stmt->fetchAll();
}

// $d: array com data, peso, altura, gordura, cintura, peito, braco, coxa (os últimos 5 podem ser null)
function criarAvaliacao($db, $idMembro, $idTreinador, $idPlano, $d) {
    if (!membroExiste($db, $idMembro)) return 'Membro inválido.';
    if ((int) $idMembro === (int) $idTreinador) return 'Não pode registar uma avaliação a si próprio.';
    if (!getPlano($db, $idPlano, $idTreinador)) return 'Plano de treino inválido.';

    $stmt = $db->prepare('
        INSERT INTO AvaliacaoFisica
            (data, peso, altura, percentagemGordura, perimetroCintura, perimetroPeito,
             perimetroBraco, perimetroCoxa, idMembro, idTreinador, idPlanoTreino)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute(array(
        $d['data'], $d['peso'], $d['altura'], $d['gordura'], $d['cintura'],
        $d['peito'], $d['braco'], $d['coxa'], $idMembro, $idTreinador, $idPlano
    ));
    return null;
}

// Índice de massa corporal (peso em kg, altura em metros)
function calcularImc($peso, $altura) {
    return $peso / ($altura * $altura);
}

// =====================================================================
// Certificações dos treinadores
// =====================================================================

function getCertificacoesTreinador($db, $idTreinador) {
    $stmt = $db->prepare('
        SELECT Certificacao.*, Modalidade.nome AS modalidade
        FROM Certificacao
        JOIN Modalidade ON Certificacao.idModalidade = Modalidade.id
        WHERE Certificacao.idTreinador = ?
        ORDER BY Modalidade.nome
    ');
    $stmt->execute(array($idTreinador));
    return $stmt->fetchAll();
}

// Um treinador só pode ter uma certificação por modalidade (chave primária).
// Fica por validar até a administração a aprovar.
function criarCertificacao($db, $idTreinador, $idModalidade, $nome, $entidade, $dataObtencao) {
    $stmt = $db->prepare('SELECT 1 FROM Modalidade WHERE id = ?');
    $stmt->execute(array($idModalidade));
    if (!$stmt->fetch()) return 'Modalidade inválida.';

    $stmt = $db->prepare('SELECT 1 FROM Certificacao WHERE idTreinador = ? AND idModalidade = ?');
    $stmt->execute(array($idTreinador, $idModalidade));
    if ($stmt->fetch()) return 'Já registou uma certificação para esta modalidade.';

    $stmt = $db->prepare('
        INSERT INTO Certificacao (idTreinador, idModalidade, nome, entidadeEmissora, dataObtencao, validada)
        VALUES (?, ?, ?, ?, ?, 0)
    ');
    $stmt->execute(array($idTreinador, $idModalidade, $nome, $entidade, $dataObtencao));
    return null;
}

function getCertificacoesPendentes($db) {
    return $db->query('
        SELECT Certificacao.*, Modalidade.nome AS modalidade, Utilizador.nome AS treinador
        FROM Certificacao
        JOIN Modalidade ON Certificacao.idModalidade = Modalidade.id
        JOIN Utilizador ON Certificacao.idTreinador = Utilizador.id
        WHERE Certificacao.validada = 0
        ORDER BY Certificacao.dataObtencao
    ')->fetchAll();
}

function validarCertificacao($db, $idTreinador, $idModalidade) {
    $stmt = $db->prepare('
        UPDATE Certificacao SET validada = 1, dataValidacao = ?
        WHERE idTreinador = ? AND idModalidade = ? AND validada = 0
    ');
    $stmt->execute(array(date('Y-m-d'), $idTreinador, $idModalidade));
    return $stmt->rowCount() > 0 ? null : 'Certificação não encontrada ou já validada.';
}

// Rejeitar = apagar uma certificação ainda por validar
function rejeitarCertificacao($db, $idTreinador, $idModalidade) {
    $stmt = $db->prepare('DELETE FROM Certificacao WHERE idTreinador = ? AND idModalidade = ? AND validada = 0');
    $stmt->execute(array($idTreinador, $idModalidade));
    return $stmt->rowCount() > 0 ? null : 'Certificação não encontrada ou já validada.';
}

// =====================================================================
// Salas e equipamentos
// =====================================================================

// Salas e equipamentos

function getSalas($db) {
    return $db->query('
        SELECT Sala.id, Sala.nome, Sala.capacidade,
               (SELECT COUNT(*) FROM Equipamento WHERE Equipamento.idSala = Sala.id) AS totalEquipamentos
        FROM Sala
        ORDER BY Sala.nome
    ')->fetchAll();
}

function criarSala($db, $nome, $capacidade) {
    $stmt = $db->prepare('SELECT 1 FROM Sala WHERE nome = ?');
    $stmt->execute(array($nome));
    if ($stmt->fetch()) return 'Já existe uma sala com esse nome.';

    $stmt = $db->prepare('INSERT INTO Sala (nome, capacidade) VALUES (?, ?)');
    $stmt->execute(array($nome, $capacidade));
    return null;
}

function getEquipamentos($db) {
    return $db->query('
        SELECT Equipamento.id, Equipamento.nome, Equipamento.estado, Equipamento.idSala, Sala.nome AS sala
        FROM Equipamento
        LEFT JOIN Sala ON Equipamento.idSala = Sala.id
        ORDER BY Equipamento.nome
    ')->fetchAll();
}

// $idSala pode ser null (equipamento sem sala atribuída)
function criarEquipamento($db, $nome, $estado, $idSala) {
    if ($idSala !== null) {
        $stmt = $db->prepare('SELECT 1 FROM Sala WHERE id = ?');
        $stmt->execute(array($idSala));
        if (!$stmt->fetch()) return 'Sala inválida.';
    }
    $stmt = $db->prepare('INSERT INTO Equipamento (nome, estado, idSala) VALUES (?, ?, ?)');
    $stmt->execute(array($nome, $estado, $idSala));
    return null;
}

function atualizarEstadoEquipamento($db, $idEquipamento, $estado) {
    $stmt = $db->prepare('UPDATE Equipamento SET estado = ? WHERE id = ?');
    $stmt->execute(array($estado, $idEquipamento));
    return $stmt->rowCount() > 0 ? null : 'Equipamento não encontrado.';
}


function getSala($db, $idSala) {
    $stmt = $db->prepare('SELECT * FROM Sala WHERE id = ?');
    $stmt->execute(array($idSala));
    return $stmt->fetch();
}

function editarSala($db, $idSala, $nome, $capacidade) {
    return emTransacao($db, function () use ($db, $idSala, $nome, $capacidade) {
        if (!getSala($db, $idSala)) return 'A sala não existe.';

        $stmt = $db->prepare('SELECT 1 FROM Sala WHERE nome = ? AND id <> ?');
        $stmt->execute(array($nome, $idSala));
        if ($stmt->fetch()) return 'Já existe uma sala com esse nome.';

        // Não deixar baixar a capacidade abaixo das vagas de aulas futuras (regra do validarAula)
        $stmt = $db->prepare('SELECT COALESCE(MAX(vagas), 0) FROM Aula WHERE idSala = ? AND cancelada = 0 AND data >= ?');
        $stmt->execute(array($idSala, date('Y-m-d')));
        $maxVagas = (int) $stmt->fetchColumn();
        if ($capacidade < $maxVagas) {
            return 'Há aulas futuras nesta sala com ' . $maxVagas . ' vagas: a capacidade não pode ser inferior.';
        }

        $stmt = $db->prepare('UPDATE Sala SET nome = ?, capacidade = ? WHERE id = ?');
        $stmt->execute(array($nome, $capacidade, $idSala));
        return null;
    });
}

// $idSala null = retirar o equipamento da sala
function mudarSalaEquipamento($db, $idEquipamento, $idSala) {
    if ($idSala !== null) {
        $stmt = $db->prepare('SELECT 1 FROM Sala WHERE id = ?');
        $stmt->execute(array($idSala));
        if (!$stmt->fetch()) return 'Sala inválida.';
    }
    $stmt = $db->prepare('UPDATE Equipamento SET idSala = ? WHERE id = ?');
    $stmt->execute(array($idSala, $idEquipamento));
    return $stmt->rowCount() > 0 ? null : 'Equipamento não encontrado.';
}
