-- AgendaClínica - estrutura das tabelas para o cPanel (phpMyAdmin > selecione o banco > Importar)

CREATE TABLE usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  telefone VARCHAR(20) NULL,
  data_nascimento DATE NULL,
  convenio VARCHAR(60) NULL,
  observacao_geral VARCHAR(255) NULL,
  senha_hash VARCHAR(255) NOT NULL,
  perfil ENUM('paciente','recepcao','profissional') NOT NULL DEFAULT 'paciente',
  consentimento_lgpd_em DATETIME NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE profissionais (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL UNIQUE,
  especialidade VARCHAR(80) NOT NULL,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE horarios_atendimento (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  profissional_id INT UNSIGNED NOT NULL,
  dia_semana TINYINT NOT NULL,            -- 0=domingo ... 6=sábado
  hora_inicio TIME NOT NULL,
  hora_fim TIME NOT NULL,
  duracao_min SMALLINT NOT NULL DEFAULT 30,
  FOREIGN KEY (profissional_id) REFERENCES profissionais(id)
) ENGINE=InnoDB;

CREATE TABLE agendamentos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  paciente_id INT UNSIGNED NOT NULL,
  profissional_id INT UNSIGNED NOT NULL,
  data_hora DATETIME NOT NULL,
  duracao_min SMALLINT NOT NULL DEFAULT 30,
  tipo ENUM('consulta','retorno','avaliacao','procedimento') NOT NULL DEFAULT 'consulta',
  status ENUM('agendado','cancelado','realizado','faltou') NOT NULL DEFAULT 'agendado',
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  -- Coluna gerada: só vale enquanto o status é 'agendado'. Cancelar libera o horário.
  slot_ativo DATETIME GENERATED ALWAYS AS (IF(status = 'agendado', data_hora, NULL)) STORED,
  FOREIGN KEY (paciente_id) REFERENCES usuarios(id),
  FOREIGN KEY (profissional_id) REFERENCES profissionais(id),
  -- Garantia de integridade: dois agendamentos ativos no mesmo horário são impossíveis.
  UNIQUE KEY uq_profissional_slot (profissional_id, slot_ativo)
) ENGINE=InnoDB;

CREATE TABLE atendimentos (   -- histórico (R07): registro feito pelo profissional após a consulta
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  agendamento_id INT UNSIGNED NULL UNIQUE,
  paciente_id INT UNSIGNED NOT NULL,
  profissional_id INT UNSIGNED NOT NULL,
  data_hora DATETIME NOT NULL,
  tipo ENUM('consulta','retorno','avaliacao','procedimento') NOT NULL DEFAULT 'consulta',
  descricao TEXT NOT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (agendamento_id) REFERENCES agendamentos(id),
  FOREIGN KEY (paciente_id) REFERENCES usuarios(id),
  FOREIGN KEY (profissional_id) REFERENCES profissionais(id),
  INDEX idx_paciente_data (paciente_id, data_hora)
) ENGINE=InnoDB;
