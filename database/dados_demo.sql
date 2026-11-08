-- AgendaClínica - DADOS FICTÍCIOS de demonstração (senha de todos os usuários: Senha@123)
-- Importe DEPOIS de schema_cpanel.sql, no mesmo banco. Importe uma única vez.
SET NAMES utf8mb4;
SET time_zone = '-03:00';

INSERT INTO usuarios (id,nome,email,telefone,data_nascimento,convenio,observacao_geral,senha_hash,perfil,consentimento_lgpd_em,criado_em) VALUES
(1,'Ana Melo','ana.melo@exemplo.com','(11) 90000-0001',NULL,NULL,NULL,'$2b$12$gWDv.Ocm4Z1x0kExmht8Re1xoATXTIb6GBm8dRSY7M30HIYSjIDXa','recepcao',NOW(),NOW()),
(2,'Marina Duarte','marina.duarte@exemplo.com','(11) 98765-4321','1991-09-12','Particular','Paciente sem alergias medicamentosas relatadas.','$2b$12$gWDv.Ocm4Z1x0kExmht8Re1xoATXTIb6GBm8dRSY7M30HIYSjIDXa','paciente',NOW(),'2025-06-10 09:00:00'),
(3,'Renato Luz','renato.luz@exemplo.com','(11) 90000-0002',NULL,NULL,NULL,'$2b$12$gWDv.Ocm4Z1x0kExmht8Re1xoATXTIb6GBm8dRSY7M30HIYSjIDXa','paciente',NOW(),NOW()),
(4,'Laura Sales','laura.sales@exemplo.com','(11) 90000-0003',NULL,NULL,NULL,'$2b$12$gWDv.Ocm4Z1x0kExmht8Re1xoATXTIb6GBm8dRSY7M30HIYSjIDXa','paciente',NOW(),NOW()),
(5,'Otávio Campos','otavio.campos@exemplo.com','(11) 90000-0004',NULL,NULL,NULL,'$2b$12$gWDv.Ocm4Z1x0kExmht8Re1xoATXTIb6GBm8dRSY7M30HIYSjIDXa','paciente',NOW(),NOW()),
(6,'Bianca Paes','bianca.paes@exemplo.com','(11) 90000-0005',NULL,NULL,NULL,'$2b$12$gWDv.Ocm4Z1x0kExmht8Re1xoATXTIb6GBm8dRSY7M30HIYSjIDXa','paciente',NOW(),NOW()),
(7,'Sônia Vidal','sonia.vidal@exemplo.com','(11) 90000-0006',NULL,NULL,NULL,'$2b$12$gWDv.Ocm4Z1x0kExmht8Re1xoATXTIb6GBm8dRSY7M30HIYSjIDXa','paciente',NOW(),NOW()),
(8,'Caio Farias','caio.farias@exemplo.com','(11) 90000-0007',NULL,NULL,NULL,'$2b$12$gWDv.Ocm4Z1x0kExmht8Re1xoATXTIb6GBm8dRSY7M30HIYSjIDXa','paciente',NOW(),NOW()),
(9,'Davi Moura','davi.moura@exemplo.com','(11) 90000-0008',NULL,NULL,NULL,'$2b$12$gWDv.Ocm4Z1x0kExmht8Re1xoATXTIb6GBm8dRSY7M30HIYSjIDXa','paciente',NOW(),NOW()),
(10,'Lívia Costa','livia.costa@exemplo.com','(11) 90000-0009',NULL,NULL,NULL,'$2b$12$gWDv.Ocm4Z1x0kExmht8Re1xoATXTIb6GBm8dRSY7M30HIYSjIDXa','paciente',NOW(),NOW()),
(11,'Dra. Helena Martins','helena.martins@exemplo.com','(11) 90000-0100',NULL,NULL,NULL,'$2b$12$gWDv.Ocm4Z1x0kExmht8Re1xoATXTIb6GBm8dRSY7M30HIYSjIDXa','profissional',NOW(),NOW()),
(12,'Dr. Lucas Ribeiro','lucas.ribeiro@exemplo.com','(11) 90000-0100',NULL,NULL,NULL,'$2b$12$gWDv.Ocm4Z1x0kExmht8Re1xoATXTIb6GBm8dRSY7M30HIYSjIDXa','profissional',NOW(),NOW()),
(13,'Dra. Camila Nunes','camila.nunes@exemplo.com','(11) 90000-0100',NULL,NULL,NULL,'$2b$12$gWDv.Ocm4Z1x0kExmht8Re1xoATXTIb6GBm8dRSY7M30HIYSjIDXa','profissional',NOW(),NOW());

INSERT INTO profissionais (id,usuario_id,especialidade) VALUES (1,11,'Clínica geral'),(2,12,'Cardiologia'),(3,13,'Dermatologia');

INSERT INTO horarios_atendimento (profissional_id,dia_semana,hora_inicio,hora_fim,duracao_min) VALUES
(1,1,'08:00:00','12:00:00',30),
(1,1,'14:00:00','18:00:00',30),
(1,2,'08:00:00','12:00:00',30),
(1,2,'14:00:00','18:00:00',30),
(1,3,'08:00:00','12:00:00',30),
(1,3,'14:00:00','18:00:00',30),
(1,4,'08:00:00','12:00:00',30),
(1,4,'14:00:00','18:00:00',30),
(1,5,'08:00:00','12:00:00',30),
(1,5,'14:00:00','18:00:00',30),
(2,1,'08:00:00','12:00:00',30),
(2,1,'14:00:00','18:00:00',30),
(2,2,'08:00:00','12:00:00',30),
(2,2,'14:00:00','18:00:00',30),
(2,3,'08:00:00','12:00:00',30),
(2,3,'14:00:00','18:00:00',30),
(2,4,'08:00:00','12:00:00',30),
(2,4,'14:00:00','18:00:00',30),
(2,5,'08:00:00','12:00:00',30),
(2,5,'14:00:00','18:00:00',30),
(3,1,'08:00:00','12:00:00',30),
(3,1,'14:00:00','18:00:00',30),
(3,2,'08:00:00','12:00:00',30),
(3,2,'14:00:00','18:00:00',30),
(3,3,'08:00:00','12:00:00',30),
(3,3,'14:00:00','18:00:00',30),
(3,4,'08:00:00','12:00:00',30),
(3,4,'14:00:00','18:00:00',30),
(3,5,'08:00:00','12:00:00',30),
(3,5,'14:00:00','18:00:00',30);

INSERT INTO atendimentos (paciente_id,profissional_id,data_hora,tipo,descricao) VALUES
(2,1,'2026-03-18 09:30:00','consulta','Paciente relata melhora do desconforto gástrico. Mantida orientação alimentar e hidratação.'),
(2,1,'2026-02-02 10:00:00','consulta','Queixa de dor abdominal leve após refeições. Solicitados exames laboratoriais de rotina.'),
(2,2,'2025-11-14 14:30:00','avaliacao','Avaliação cardiológica preventiva. Pressão arterial dentro dos parâmetros esperados.'),
(2,3,'2025-06-20 11:00:00','retorno','Acompanhamento dermatológico anual. Sem alterações relevantes no exame clínico.');

-- Agenda de demonstração no próximo dia útil (calculado na hora da importação)
SET @dia = DATE_ADD(CURDATE(), INTERVAL CASE DAYOFWEEK(CURDATE()) WHEN 6 THEN 3 WHEN 7 THEN 2 ELSE 1 END DAY);

INSERT INTO agendamentos (paciente_id,profissional_id,data_hora,duracao_min,tipo) VALUES
(2,1,CONCAT(@dia,' 08:00:00'),30,'consulta'),
(5,1,CONCAT(@dia,' 09:30:00'),30,'retorno'),
(4,1,CONCAT(@dia,' 10:00:00'),30,'consulta'),
(6,1,CONCAT(@dia,' 11:30:00'),30,'consulta'),
(3,1,CONCAT(@dia,' 14:00:00'),30,'consulta'),
(3,2,CONCAT(@dia,' 08:30:00'),30,'avaliacao'),
(7,2,CONCAT(@dia,' 10:30:00'),30,'retorno'),
(8,2,CONCAT(@dia,' 14:30:00'),30,'consulta'),
(4,3,CONCAT(@dia,' 08:00:00'),30,'consulta'),
(9,3,CONCAT(@dia,' 10:00:00'),30,'procedimento'),
(10,3,CONCAT(@dia,' 11:30:00'),30,'retorno');
