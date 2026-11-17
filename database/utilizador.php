<?php
function getUtilizadorPorEmail($db, $email) {
    $stmt = $db->prepare('SELECT * FROM Utilizador WHERE email = ?');
    $stmt->execute(array($email));
    return $stmt->fetch();
}

function getUtilizadorPorId($db, $id) {
    $stmt = $db->prepare('SELECT * FROM Utilizador WHERE id = ?');
    $stmt->execute(array($id));
    return $stmt->fetch();
}

function emailExiste($db, $email) {
    return getUtilizadorPorEmail($db, $email) !== false;
}

// Devolve os papéis do utilizador, por exemplo array('MEMBRO', 'TREINADOR')
function getPapeis($db, $idUtilizador) {
    $papeis = array();
    $tabelas = array('Membro' => 'MEMBRO', 'Treinador' => 'TREINADOR', 'Administrador' => 'ADMIN');
    foreach ($tabelas as $tabela => $papel) {
        $stmt = $db->prepare("SELECT 1 FROM $tabela WHERE idUtilizador = ?");
        $stmt->execute(array($idUtilizador));
        if ($stmt->fetch()) $papeis[] = $papel;
    }
    return $papeis;
}

// Cria o utilizador e o respetivo membro numa só transação
function criarMembro($db, $nome, $email, $telemovel, $password, $dataNascimento) {
    $db->beginTransaction();
    try {
        $stmt = $db->prepare('INSERT INTO Utilizador (nome, email, telemovel, passwordHash, dataRegisto) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute(array($nome, $email, $telemovel, password_hash($password, PASSWORD_DEFAULT), date('Y-m-d')));
        $id = $db->lastInsertId();

        $stmt = $db->prepare('INSERT INTO Membro (idUtilizador, dataNascimento) VALUES (?, ?)');
        $stmt->execute(array($id, $dataNascimento));

        $db->commit();
        return $id;
    } catch (PDOException $e) {
        $db->rollBack();
        throw $e;
    }
}

function treinadorExiste($db, $idUtilizador) {
    $stmt = $db->prepare('SELECT 1 FROM Treinador WHERE idUtilizador = ?');
    $stmt->execute(array($idUtilizador));
    return $stmt->fetch() !== false;
}

function membroExiste($db, $idUtilizador) {
    $stmt = $db->prepare('SELECT 1 FROM Membro WHERE idUtilizador = ?');
    $stmt->execute(array($idUtilizador));
    return $stmt->fetch() !== false;
}

// Treinadores, com as modalidades em que têm certificação validada.
// $excluirId permite deixar de fora o próprio utilizador (que pode ser treinador e membro).
function getTreinadores($db, $excluirId = 0) {
    $stmt = $db->prepare('
        SELECT
            Utilizador.id,
            Utilizador.nome,
            (
                SELECT GROUP_CONCAT(Modalidade.nome, \', \')
                FROM Certificacao
                JOIN Modalidade ON Certificacao.idModalidade = Modalidade.id
                WHERE Certificacao.idTreinador = Utilizador.id
                  AND Certificacao.validada = 1
            ) AS modalidades
        FROM Treinador
        JOIN Utilizador ON Treinador.idUtilizador = Utilizador.id
        WHERE Utilizador.id <> ? AND Utilizador.ativo = 1
        ORDER BY Utilizador.nome
    ');
    $stmt->execute(array($excluirId));
    return $stmt->fetchAll();
}

function getMembros($db, $excluirId = 0) {
    $stmt = $db->prepare('
        SELECT Utilizador.id, Utilizador.nome, Utilizador.email
        FROM Membro
        JOIN Utilizador ON Membro.idUtilizador = Utilizador.id
        WHERE Utilizador.id <> ? AND Utilizador.ativo = 1
        ORDER BY Utilizador.nome
    ');
    $stmt->execute(array($excluirId));
    return $stmt->fetchAll();
}

// ---------------------------------------------------------------------
// Administração de utilizadores
// ---------------------------------------------------------------------

function getUtilizadoresComPapeis($db) {
    return $db->query('
        SELECT
            Utilizador.id,
            Utilizador.nome,
            Utilizador.email,
            Utilizador.telemovel,
            Utilizador.dataRegisto,
            Utilizador.ativo,
            EXISTS (SELECT 1 FROM Membro WHERE Membro.idUtilizador = Utilizador.id) AS membro,
            EXISTS (SELECT 1 FROM Treinador WHERE Treinador.idUtilizador = Utilizador.id) AS treinador,
            EXISTS (SELECT 1 FROM Administrador WHERE Administrador.idUtilizador = Utilizador.id) AS admin
        FROM Utilizador
        ORDER BY Utilizador.nome
    ')->fetchAll();
}

// Dá ou retira o papel de treinador/administrador. Devolve null se correu bem ou o texto do erro.
// Regras: todo o utilizador tem pelo menos um papel; um administrador não se retira a si próprio;
// um treinador com aulas, sessões, certificações ou avaliações não pode deixar de o ser.
function alterarPapel($db, $idAlvo, $papel, $adicionar, $idAtual) {
    $tabelas = array('TREINADOR' => 'Treinador', 'ADMIN' => 'Administrador');
    if (!isset($tabelas[$papel])) return 'Papel inválido.';
    $tabela = $tabelas[$papel];

    $stmt = $db->prepare('SELECT 1 FROM Utilizador WHERE id = ?');
    $stmt->execute(array($idAlvo));
    if (!$stmt->fetch()) return 'O utilizador não existe.';

    $papeisAtuais = getPapeis($db, $idAlvo);
    $jaTem = in_array($papel, $papeisAtuais);

    if ($adicionar) {
        if ($jaTem) return 'O utilizador já tem esse papel.';
        $stmt = $db->prepare("INSERT INTO $tabela (idUtilizador) VALUES (?)");
        $stmt->execute(array($idAlvo));
        return null;
    }

    if (!$jaTem) return 'O utilizador não tem esse papel.';
    if (count($papeisAtuais) < 2) return 'Cada utilizador tem de ter pelo menos um papel.';

    if ($papel === 'ADMIN' && (int) $idAlvo === (int) $idAtual) {
        return 'Não pode retirar o seu próprio papel de administrador.';
    }

    if ($papel === 'TREINADOR') {
        $verificacoes = array(
            'Aula' => 'aulas',
            'SessaoPT' => 'sessões de personal training',
            'Certificacao' => 'certificações',
            'AvaliacaoFisica' => 'avaliações físicas'
        );
        foreach ($verificacoes as $tabelaRef => $descricao) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM $tabelaRef WHERE idTreinador = ?");
            $stmt->execute(array($idAlvo));
            if ($stmt->fetchColumn() > 0) {
                return 'O treinador tem ' . $descricao . ' associadas e não pode deixar de o ser.';
            }
        }
    }

    $stmt = $db->prepare("DELETE FROM $tabela WHERE idUtilizador = ?");
    $stmt->execute(array($idAlvo));
    return null;
}

// Ativa ou desativa uma conta. Devolve null se correu bem ou o texto do erro.
// Uma conta desativada não consegue iniciar sessão. Regras:
// - ninguém desativa a própria conta, e tem de sobrar um administrador ativo;
// - um treinador com aulas ou sessões futuras não pode ser desativado;
// - ao desativar um membro, cancelam-se as suas inscrições e sessões futuras.
function alterarEstadoConta($db, $idAlvo, $ativar, $idAtual) {
    return emTransacao($db, function () use ($db, $idAlvo, $ativar, $idAtual) {
        $alvo = getUtilizadorPorId($db, $idAlvo);
        if (!$alvo) return 'O utilizador não existe.';

        if ($ativar) {
            if ($alvo['ativo']) return 'A conta já está ativa.';
            $stmt = $db->prepare('UPDATE Utilizador SET ativo = 1 WHERE id = ?');
            $stmt->execute(array($idAlvo));
            return null;
        }

        if (!$alvo['ativo']) return 'A conta já está desativada.';
        if ((int) $idAlvo === (int) $idAtual) return 'Não pode desativar a sua própria conta.';

        $papeis = getPapeis($db, $idAlvo);
        $hoje = date('Y-m-d');
        $agora = date('H:i');

        if (in_array('ADMIN', $papeis)) {
            $stmt = $db->query('SELECT COUNT(*) FROM Administrador JOIN Utilizador ON Administrador.idUtilizador = Utilizador.id WHERE Utilizador.ativo = 1');
            if ($stmt->fetchColumn() < 2) return 'Tem de existir pelo menos um administrador ativo.';
        }

        if (in_array('TREINADOR', $papeis)) {
            $stmt = $db->prepare('SELECT COUNT(*) FROM Aula WHERE idTreinador = ? AND cancelada = 0 AND (data > ? OR (data = ? AND horaInicio > ?))');
            $stmt->execute(array($idAlvo, $hoje, $hoje, $agora));
            $aulas = $stmt->fetchColumn();

            $stmt = $db->prepare("SELECT COUNT(*) FROM SessaoPT WHERE idTreinador = ? AND estado IN ('PENDENTE', 'CONFIRMADA') AND (data > ? OR (data = ? AND horaInicio > ?))");
            $stmt->execute(array($idAlvo, $hoje, $hoje, $agora));
            $sessoes = $stmt->fetchColumn();

            if ($aulas > 0 || $sessoes > 0) {
                return 'O treinador tem aulas ou sessões futuras. Cancele-as antes de desativar a conta.';
            }
        }

        if (in_array('MEMBRO', $papeis)) {
            $stmt = $db->prepare("
                UPDATE Inscricao SET estado = 'CANCELADA'
                WHERE idMembro = ? AND estado = 'ATIVA'
                  AND idAula IN (SELECT id FROM Aula WHERE data > ? OR (data = ? AND horaInicio > ?))
            ");
            $stmt->execute(array($idAlvo, $hoje, $hoje, $agora));

            $stmt = $db->prepare("
                UPDATE SessaoPT SET estado = 'CANCELADA'
                WHERE idMembro = ? AND estado IN ('PENDENTE', 'CONFIRMADA')
                  AND (data > ? OR (data = ? AND horaInicio > ?))
            ");
            $stmt->execute(array($idAlvo, $hoje, $hoje, $agora));
        }

        $stmt = $db->prepare('UPDATE Utilizador SET ativo = 0 WHERE id = ?');
        $stmt->execute(array($idAlvo));
        return null;
    });
}

// ---------------------------------------------------------------------
// Perfil e fotografia
// ---------------------------------------------------------------------

// Tipos de imagem aceites como fotografia de perfil
function tipoImagemFoto($dados) {
    $info = @getimagesizefromstring($dados);
    if (!$info) return null;
    $tipos = array(IMAGETYPE_JPEG => 'image/jpeg', IMAGETYPE_PNG => 'image/png', IMAGETYPE_WEBP => 'image/webp');
    return isset($tipos[$info[2]]) ? $tipos[$info[2]] : null;
}

// Atualiza os dados do perfil. $foto: null (não mexer), '' (remover) ou os bytes da imagem.
function atualizarPerfil($db, $idUtilizador, $nome, $telemovel, $biografia, $foto) {
    $stmt = $db->prepare('UPDATE Utilizador SET nome = ?, telemovel = ?, biografia = ? WHERE id = ?');
    $stmt->execute(array($nome, $telemovel, $biografia === '' ? null : $biografia, $idUtilizador));

    if ($foto === null) return;
    $stmt = $db->prepare('UPDATE Utilizador SET fotografia = ? WHERE id = ?');
    if ($foto === '') {
        $stmt->bindValue(1, null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(1, $foto, PDO::PARAM_LOB);
    }
    $stmt->bindValue(2, $idUtilizador, PDO::PARAM_INT);
    $stmt->execute();
}

// Bytes da fotografia (ou null). Nas versões antigas do PHP o PDO devolve um stream.
function getFotografia($db, $idUtilizador) {
    $stmt = $db->prepare('SELECT fotografia FROM Utilizador WHERE id = ?');
    $stmt->execute(array($idUtilizador));
    $dados = $stmt->fetchColumn();
    if (is_resource($dados)) $dados = stream_get_contents($dados);
    return ($dados === false || $dados === null || $dados === '') ? null : $dados;
}

function temFotografia($db, $idUtilizador) {
    $stmt = $db->prepare('SELECT fotografia IS NOT NULL FROM Utilizador WHERE id = ?');
    $stmt->execute(array($idUtilizador));
    return (bool) $stmt->fetchColumn();
}

// Perfis públicos dos treinadores ativos, com certificações validadas e avaliação média
function getPerfisTreinadores($db) {
    return $db->query("
        SELECT
            Utilizador.id,
            Utilizador.nome,
            Utilizador.biografia,
            Utilizador.fotografia IS NOT NULL AS temFoto,
            (
                SELECT GROUP_CONCAT(Modalidade.nome, ', ')
                FROM Certificacao
                JOIN Modalidade ON Certificacao.idModalidade = Modalidade.id
                WHERE Certificacao.idTreinador = Utilizador.id AND Certificacao.validada = 1
            ) AS modalidades,
            (
                SELECT ROUND(AVG(Inscricao.notaTreinador), 1)
                FROM Inscricao JOIN Aula ON Inscricao.idAula = Aula.id
                WHERE Aula.idTreinador = Utilizador.id AND Inscricao.notaTreinador IS NOT NULL
            ) AS mediaNota,
            (
                SELECT COUNT(Inscricao.notaTreinador)
                FROM Inscricao JOIN Aula ON Inscricao.idAula = Aula.id
                WHERE Aula.idTreinador = Utilizador.id
            ) AS totalNotas
        FROM Treinador
        JOIN Utilizador ON Treinador.idUtilizador = Utilizador.id
        WHERE Utilizador.ativo = 1
        ORDER BY Utilizador.nome
    ")->fetchAll();
}

// Resumo mostrado na dashboard do membro
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
