<?php
// Todas as operações de gestão (formulários POST). Cada formulário envia um
// campo escondido «op»; iniciarOperacao() verifica o CSRF e o papel exigido.
require_once __DIR__ . '/../utils/session.php';
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../database/utilizador.php';
require_once __DIR__ . '/../database/aula.php';
require_once __DIR__ . '/../database/gestao.php';

$op = iniciarOperacao(array(
    // membro
    'inscrever' => 'MEMBRO', 'cancelar' => 'MEMBRO', 'avaliar_aula' => 'MEMBRO',
    'marcar' => 'MEMBRO', 'pt_cancelar' => 'MEMBRO',
    // treinador
    'confirmar' => 'TREINADOR', 'recusar' => 'TREINADOR', 'concluir' => 'TREINADOR',
    'avaliacao' => 'TREINADOR', 'plano' => 'TREINADOR', 'exercicio' => 'TREINADOR', 'registar' => 'TREINADOR',
    // administrador
    'criar' => 'ADMIN', 'editar_aula' => 'ADMIN', 'cancelar_aula' => 'ADMIN', 'apagar' => 'ADMIN',
    'validar' => 'ADMIN', 'rejeitar' => 'ADMIN',
    'papel' => 'ADMIN', 'desativar' => 'ADMIN', 'ativar' => 'ADMIN', 'sala' => 'ADMIN', 'equipamento' => 'ADMIN', 'equipamento_estado' => 'ADMIN'
));

$db = getDatabaseConnection();

// ---------------------------------------------------------------------
// Aulas
// ---------------------------------------------------------------------
if (in_array($op, array('inscrever', 'cancelar', 'avaliar_aula', 'criar', 'editar_aula', 'cancelar_aula', 'apagar'))) {
    if ($op === 'avaliar_aula') {
        $erro = avaliarAula($db, $_SESSION['id'], postInt('idAula'), postInt('notaAula'), postInt('notaTreinador'), post('comentario'));
        terminar($erro, 'Obrigado! A tua avaliação foi registada.', '/membro.php?sec=aulas');
    }

    if ($op === 'inscrever' || $op === 'cancelar') {
        $destino = post('origem') === 'minhas_aulas' ? '/membro.php?sec=aulas' : '/aulas.php';
        if ($op === 'inscrever') {
            $erro = inscreverMembro($db, $_SESSION['id'], postInt('idAula'));
            terminar($erro, 'Inscrição efetuada com sucesso.', $destino);
        }
        $erro = cancelarInscricao($db, $_SESSION['id'], postInt('idAula'));
        terminar($erro, 'Inscrição cancelada.', $destino);
    }

    $destino = '/admin.php?sec=aulas';

    if ($op === 'apagar') {
        terminar(apagarAula($db, postInt('idAula')), 'Aula apagada.', $destino);
    }

    if ($op === 'cancelar_aula') {
        terminar(cancelarAula($db, postInt('idAula')), 'Aula cancelada. As inscrições ativas foram canceladas.', $destino);
    }

    // criar ou editar_aula
    $idModalidade = postInt('idModalidade');
    $idSala       = postInt('idSala');
    $idTreinador  = postInt('idTreinador');
    $data         = post('data');
    $inicio       = post('horaInicio');
    $fim          = post('horaFim');
    $nivel        = post('nivel');
    $vagas        = postInt('vagas');

    $erros = array();
    if ($idModalidade === 0 || $idSala === 0 || $idTreinador === 0) $erros[] = 'Preencha a modalidade, a sala e o treinador.';
    if (!dataValida($data)) $erros[] = 'Data inválida.';
    if (!horaValida($inicio) || !horaValida($fim)) $erros[] = 'Hora inválida.';
    elseif ($fim <= $inicio) $erros[] = 'A hora de fim tem de ser depois da hora de início.';
    if (!in_array($nivel, array('INICIANTE', 'INTERMEDIO', 'AVANCADO'))) $erros[] = 'Nível inválido.';
    terminarSeErros($erros, $op === 'editar_aula' ? $destino . '&editar=' . postInt('idAula') : $destino);

    if ($op === 'editar_aula') {
        $idAula = postInt('idAula');
        $destinoEdicao = $destino . '&editar=' . $idAula;
        if ($idAula === 0) terminar('Aula inválida.', '', $destino);
        $erro = editarAula($db, $idAula, $idModalidade, $idSala, $idTreinador, $data, $inicio, $fim, $nivel, $vagas);
        if ($erro !== null) terminar($erro, '', $destinoEdicao);
        terminar(null, 'Aula atualizada.', $destino);
    }

    $erro = criarAula($db, $idModalidade, $idSala, $idTreinador, $data, $inicio, $fim, $nivel, $vagas);
    terminar($erro, 'Aula criada.', $destino);
}

// ---------------------------------------------------------------------
// Personal training
// ---------------------------------------------------------------------
if (in_array($op, array('marcar', 'pt_cancelar', 'confirmar', 'recusar', 'concluir'))) {
    if ($op === 'pt_cancelar') {
        terminar(cancelarSessaoMembro($db, postInt('idSessao'), $_SESSION['id']), 'Sessão cancelada.', '/membro.php?sec=pt');
    }

    if ($op === 'marcar') {
        $idTreinador = postInt('idTreinador');
        $data        = post('data');
        $inicio      = post('horaInicio');
        $fim         = post('horaFim');

        $erros = array();
        if ($idTreinador === 0) $erros[] = 'Escolha um treinador.';
        if (!dataValida($data)) $erros[] = 'Data inválida.';
        if (!horaValida($inicio) || !horaValida($fim)) $erros[] = 'Hora inválida.';
        elseif ($fim <= $inicio) $erros[] = 'A hora de fim tem de ser depois da hora de início.';
        terminarSeErros($erros, '/membro.php?sec=pt');

        $erro = criarSessaoPT($db, $_SESSION['id'], $idTreinador, $data, $inicio, $fim);
        terminar($erro, 'Sessão marcada. Fica pendente até o treinador a confirmar.', '/membro.php?sec=pt');
    }

    // confirmar, recusar ou concluir
    $mensagens = array(
        'confirmar' => 'Sessão confirmada.',
        'recusar'   => 'Sessão cancelada.',
        'concluir'  => 'Sessão marcada como concluída.'
    );
    $erro = atualizarSessaoTreinador($db, postInt('idSessao'), $_SESSION['id'], $op);
    terminar($erro, $mensagens[$op], '/treinador.php?sec=pt');
}

// ---------------------------------------------------------------------
// Avaliações físicas e planos
// ---------------------------------------------------------------------
if (in_array($op, array('avaliacao', 'plano', 'exercicio'))) {
    if ($op === 'plano') {
        $nome       = post('nome');
        $objetivo   = post('objetivo');
        $frequencia = postInt('frequenciaSemanal');
        $duracao    = postInt('duracaoSemanas');

        $erros = array();
        if ($nome === '') $erros[] = 'O nome do plano é obrigatório.';
        if ($frequencia < 1 || $frequencia > 7) $erros[] = 'A frequência semanal deve estar entre 1 e 7.';
        if ($duracao < 1) $erros[] = 'A duração deve ser de pelo menos 1 semana.';
        terminarSeErros($erros, '/treinador.php?sec=planos');

        $id = criarPlano($db, $_SESSION['id'], $nome, $objetivo, $frequencia, $duracao);
        adicionarMensagem('sucesso', 'Plano criado. Acrescente agora os exercícios.');
        redirecionar('/treinador.php?sec=planos&id=' . $id);
    }

    if ($op === 'exercicio') {
        $idPlano    = postInt('idPlano');
        $exercicio  = post('exercicio');
        $series     = postInt('series');
        $repeticoes = postInt('repeticoes');

        $erros = array();
        if ($exercicio === '') $erros[] = 'O nome do exercício é obrigatório.';
        if ($series < 1) $erros[] = 'As séries devem ser pelo menos 1.';
        if ($repeticoes < 1) $erros[] = 'As repetições devem ser pelo menos 1.';
        $destino = '/treinador.php?sec=planos&id=' . $idPlano;
        terminarSeErros($erros, $destino);

        $erro = adicionarItemPlano($db, $_SESSION['id'], $idPlano, $exercicio, $series, $repeticoes);
        terminar($erro, 'Exercício adicionado.', $destino);
    }

    // avaliacao
    $destino  = '/treinador.php?sec=avaliacoes';
    $idMembro = postInt('idMembro');
    $idPlano  = postInt('idPlano');
    $data     = post('data');
    $peso     = lerNumero(post('peso'));
    $altura   = lerNumero(post('altura'));

    // Campos opcionais: vazio = não medido
    function lerOpcional($nome, &$erros, $rotulo, $maximo) {
        $texto = post($nome);
        if ($texto === '') return null;
        $valor = lerNumero($texto);
        if ($valor === null || $valor <= 0 || $valor > $maximo) {
            $erros[] = $rotulo . ' inválido.';
            return null;
        }
        return $valor;
    }

    $erros = array();
    if ($idMembro === 0) $erros[] = 'Escolha um membro.';
    if ($idPlano === 0) $erros[] = 'Escolha um plano de treino.';
    if (!dataValida($data) || $data > date('Y-m-d')) $erros[] = 'A data da avaliação é inválida (não pode ser futura).';
    if ($peso === null || $peso < 20 || $peso > 400) $erros[] = 'Peso inválido (em kg, entre 20 e 400).';
    if ($altura === null || $altura < 0.5 || $altura > 2.5) $erros[] = 'Altura inválida (em metros, por exemplo 1.78).';

    $gordura = lerOpcional('gordura', $erros, 'Percentagem de gordura', 100);
    $cintura = lerOpcional('cintura', $erros, 'Perímetro da cintura', 300);
    $peito   = lerOpcional('peito', $erros, 'Perímetro do peito', 300);
    $braco   = lerOpcional('braco', $erros, 'Perímetro do braço', 100);
    $coxa    = lerOpcional('coxa', $erros, 'Perímetro da coxa', 200);
    terminarSeErros($erros, $destino);

    $erro = criarAvaliacao($db, $idMembro, $_SESSION['id'], $idPlano, array(
        'data' => $data, 'peso' => $peso, 'altura' => $altura, 'gordura' => $gordura,
        'cintura' => $cintura, 'peito' => $peito, 'braco' => $braco, 'coxa' => $coxa
    ));
    terminar($erro, 'Avaliação física registada.', $destino);
}

// ---------------------------------------------------------------------
// Certificações
// ---------------------------------------------------------------------
if (in_array($op, array('registar', 'validar', 'rejeitar'))) {
    if ($op === 'registar') {
        $idModalidade = postInt('idModalidade');
        $nome         = post('nome');
        $entidade     = post('entidade');
        $data         = post('dataObtencao');

        $erros = array();
        if ($idModalidade === 0) $erros[] = 'Escolha uma modalidade.';
        if ($nome === '') $erros[] = 'O nome da certificação é obrigatório.';
        if ($entidade === '') $erros[] = 'A entidade emissora é obrigatória.';
        if (!dataValida($data) || $data > date('Y-m-d')) $erros[] = 'Data de obtenção inválida (não pode ser futura).';
        terminarSeErros($erros, '/treinador.php?sec=certificacoes');

        $erro = criarCertificacao($db, $_SESSION['id'], $idModalidade, $nome, $entidade, $data);
        terminar($erro, 'Certificação registada. Fica pendente até ser validada por um administrador.', '/treinador.php?sec=certificacoes');
    }

    $destino = '/admin.php?sec=certificacoes';
    if ($op === 'validar') {
        terminar(validarCertificacao($db, postInt('idTreinador'), postInt('idModalidade')), 'Certificação validada.', $destino);
    }
    terminar(rejeitarCertificacao($db, postInt('idTreinador'), postInt('idModalidade')), 'Certificação rejeitada e removida.', $destino);
}

// ---------------------------------------------------------------------
// Utilizadores, salas e equipamentos
// ---------------------------------------------------------------------
if (in_array($op, array('papel', 'desativar', 'ativar', 'sala', 'equipamento', 'equipamento_estado'))) {
    $estadosValidos = array('DISPONIVEL', 'EM_USO', 'EM_MANUTENCAO');

    if ($op === 'papel') {
        $destino = '/admin.php?sec=utilizadores';
        $idAlvo = postInt('idUtilizador');
        $acao   = post('acao');
        if ($acao !== 'adicionar' && $acao !== 'remover') {
            terminar('Ação inválida.', '', $destino);
        }
        $erro = alterarPapel($db, $idAlvo, post('papel'), $acao === 'adicionar', $_SESSION['id']);
        // Se o utilizador alterou os próprios papéis, atualiza a sessão
        if ($erro === null && $idAlvo === (int) $_SESSION['id']) {
            $_SESSION['papeis'] = getPapeis($db, $idAlvo);
        }
        terminar($erro, 'Papéis atualizados.', $destino);
    }

    if ($op === 'desativar' || $op === 'ativar') {
        $erro = alterarEstadoConta($db, postInt('idUtilizador'), $op === 'ativar', $_SESSION['id']);
        terminar($erro, $op === 'ativar' ? 'Conta ativada.' : 'Conta desativada.', '/admin.php?sec=utilizadores');
    }

    $destino = '/admin.php?sec=espacos';

    if ($op === 'sala') {
        $nome       = post('nome');
        $capacidade = postInt('capacidade');
        $erros = array();
        if ($nome === '') $erros[] = 'O nome da sala é obrigatório.';
        if ($capacidade < 1) $erros[] = 'A capacidade deve ser pelo menos 1.';
        terminarSeErros($erros, $destino);
        terminar(criarSala($db, $nome, $capacidade), 'Sala criada.', $destino);
    }

    $estado = post('estado');
    if (!in_array($estado, $estadosValidos)) {
        terminar('Estado inválido.', '', $destino);
    }

    if ($op === 'equipamento_estado') {
        terminar(atualizarEstadoEquipamento($db, postInt('idEquipamento'), $estado), 'Estado do equipamento atualizado.', $destino);
    }

    // equipamento (novo). idSala 0 = sem sala atribuída
    $nome   = post('nome');
    $idSala = postInt('idSala');
    terminarSeErros($nome === '' ? array('O nome do equipamento é obrigatório.') : array(), $destino);
    terminar(criarEquipamento($db, $nome, $estado, $idSala === 0 ? null : $idSala), 'Equipamento adicionado.', $destino);
}
