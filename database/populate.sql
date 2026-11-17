-- =====================================================================
-- Dados de Teste (Seed Data)
-- =====================================================================

-- Utilizadores (palavra-passe de teste para todos: 'password123')
INSERT INTO Utilizador (id, nome, email, telemovel, passwordHash, fotografia, biografia, dataRegisto) VALUES
(1, 'Rui Almeida', 'admin@biogym.pt', '912345678', '$2y$10$klWSoqUNUOk.KojlN7NXiezoIMHDHD4zV7tcK6EEnmyTy.cEXNAqG', NULL, 'Administrador do sistema BioGym.', '2026-01-01'),
(2, 'Carlos Silva', 'carlos.silva@biogym.pt', '923456789', '$2y$10$klWSoqUNUOk.KojlN7NXiezoIMHDHD4zV7tcK6EEnmyTy.cEXNAqG', NULL, 'Especialista em musculação e treino funcional.', '2026-01-05'),
(3, 'Ana Rocha', 'ana.rocha@biogym.pt', '934567890', '$2y$10$klWSoqUNUOk.KojlN7NXiezoIMHDHD4zV7tcK6EEnmyTy.cEXNAqG', NULL, 'Instrutora de Yoga e Pilates.', '2026-01-06'),
(4, 'João Mendes', 'joao.mendes@gmail.com', '965432109', '$2y$10$klWSoqUNUOk.KojlN7NXiezoIMHDHD4zV7tcK6EEnmyTy.cEXNAqG', NULL, 'Focado em hipertrofia e condicionamento.', '2026-01-10'),
(5, 'Maria Santos', 'maria.santos@gmail.com', '987654321', '$2y$10$klWSoqUNUOk.KojlN7NXiezoIMHDHD4zV7tcK6EEnmyTy.cEXNAqG', NULL, 'Atleta amadora de corrida.', '2026-01-12');

-- Atribuição de Papéis
INSERT INTO Administrador (idUtilizador) VALUES (1);
INSERT INTO Treinador (idUtilizador) VALUES (2), (3);
INSERT INTO Membro (idUtilizador, dataNascimento) VALUES
(3, '1995-03-08'),
(4, '1998-04-15'),
(5, '2001-09-22');

-- Modalidades
INSERT INTO Modalidade (id, nome, descricao) VALUES
(1, 'Musculação', 'Treino de força e hipertrofia muscular.'),
(2, 'Yoga', 'Prática de flexibilidade, postura e controlo respiratório.'),
(3, 'Spinning', 'Treino cardiovascular de alta intensidade sobre bicicleta.');

-- Salas
INSERT INTO Sala (id, nome, capacidade) VALUES
(1, 'Sala Principal de Musculação', 30),
(2, 'Estúdio de Pilates/Yoga', 15),
(3, 'Sala de Cycling', 20);

-- Equipamentos
INSERT INTO Equipamento (id, nome, estado, idSala) VALUES
(1, 'Passadeira Pro 500', 'DISPONIVEL', 1),
(2, 'Bicicleta Spinning X', 'DISPONIVEL', 3),
(3, 'Multipower Squat Rack', 'EM_MANUTENCAO', 1),
(4, 'Máquina de Remo', 'EM_USO', 1),
(5, 'Tapetes de Yoga (conjunto)', 'DISPONIVEL', 2);

-- Certificações
INSERT INTO Certificacao (idTreinador, idModalidade, nome, entidadeEmissora, dataObtencao, validada, dataValidacao) VALUES
(2, 1, 'Certificado de Treinador de Musculação Nível 2', 'IFBB Portugal', '2023-05-10', 1, '2023-05-15'),
(2, 3, 'Instrutor de Indoor Cycling', 'Schwinn Fitness', '2024-02-12', 1, '2024-02-20'),
(3, 2, 'Instrutor Certificado de Hatha Yoga', 'Yoga Alliance', '2022-11-20', 1, '2022-11-25');

-- As datas são relativas ao dia em que a base de dados é criada (init.php),
-- para haver sempre aulas e sessões futuras para testar.

-- Aulas (1 a 3 já decorreram, para testar avaliações; 4 a 10 são futuras)
INSERT INTO Aula (id, data, horaInicio, horaFim, nivel, vagas, idModalidade, idSala, idTreinador) VALUES
(1, date('now', '-8 days'), '09:00', '10:00', 'INICIANTE', 15, 2, 2, 3),
(2, date('now', '-7 days'), '18:30', '19:30', 'INTERMEDIO', 20, 3, 3, 2),
(3, date('now', '-2 days'), '10:00', '11:00', 'AVANCADO', 30, 1, 1, 2),
(4, date('now', '+3 days'), '09:00', '10:00', 'INICIANTE', 15, 2, 2, 3),
(5, date('now', '+4 days'), '18:30', '19:30', 'INTERMEDIO', 20, 3, 3, 2),
(6, date('now', '+5 days'), '10:00', '11:00', 'AVANCADO', 30, 1, 1, 2),
(7, date('now', '+6 days'), '09:00', '10:00', 'INICIANTE', 15, 2, 2, 3),
(8, date('now', '+10 days'), '18:30', '19:30', 'AVANCADO', 20, 3, 3, 2),
(9, date('now', '+12 days'), '10:00', '11:00', 'INTERMEDIO', 30, 1, 1, 2),
(10, date('now', '+14 days'), '09:00', '10:00', 'INTERMEDIO', 2, 2, 2, 3);

-- Inscrições em Aulas
INSERT INTO Inscricao (idMembro, idAula, dataInscricao, estado, notaAula, notaTreinador, comentario) VALUES
(4, 1, datetime('now', '-12 days'), 'ATIVA', 5, 5, 'Excelente aula para quem está a começar.'),
(5, 2, datetime('now', '-10 days'), 'ATIVA', 4, 5, NULL),
(4, 3, datetime('now', '-5 days'), 'ATIVA', NULL, NULL, NULL),
(5, 3, datetime('now', '-5 days'), 'ATIVA', NULL, NULL, NULL),
(4, 6, datetime('now', '-1 days'), 'ATIVA', NULL, NULL, NULL),
(5, 5, datetime('now', '-1 days'), 'ATIVA', NULL, NULL, NULL),
(5, 4, datetime('now', '-1 days'), 'CANCELADA', NULL, NULL, NULL),
(3, 8, datetime('now', '-1 days'), 'ATIVA', NULL, NULL, NULL);

-- Planos de Treino e Itens (o plano pertence ao treinador que o criou)
INSERT INTO PlanoTreino (id, nome, objetivo, frequenciaSemanal, duracaoSemanas, idTreinador) VALUES
(1, 'Hipertrofia 3 Dias', 'Ganho de massa muscular', 3, 8, 2),
(2, 'Mobilidade e Postura', 'Flexibilidade e prevenção de lesões', 2, 6, 3);

INSERT INTO ItemPlano (id, idPlanoTreino, ordem, exercicio, series, repeticoes) VALUES
(1, 1, 1, 'Supino Reto com Barra', 4, 10),
(2, 1, 2, 'Agachamento Livre', 4, 12),
(3, 2, 1, 'Saudação ao Sol', 3, 8);

-- Avaliações Físicas
INSERT INTO AvaliacaoFisica (id, data, peso, altura, percentagemGordura, perimetroCintura, perimetroPeito, perimetroBraco, perimetroCoxa, idMembro, idTreinador, idPlanoTreino) VALUES
(1, date('now', '-60 days'), 75.5, 1.78, 16.5, 82.0, 98.0, 34.0, 56.0, 4, 2, 1);

-- Sessões de Personal Training (PT)
INSERT INTO SessaoPT (id, data, horaInicio, horaFim, estado, idMembro, idTreinador) VALUES
(1, date('now', '-6 days'), '11:00', '12:00', 'CONCLUIDA', 4, 2),
(2, date('now', '+4 days'), '11:00', '12:00', 'CONFIRMADA', 4, 2),
(3, date('now', '+7 days'), '17:00', '18:00', 'PENDENTE', 5, 3);
