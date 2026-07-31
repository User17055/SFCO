CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_externo VARCHAR(64) NULL UNIQUE,
    nome VARCHAR(150) NOT NULL,
    cpf VARCHAR(14) NULL,
    email VARCHAR(100) NULL,
    telefone VARCHAR(20) NULL,
    observacoes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_externo VARCHAR(64) NULL UNIQUE,
    cliente_id INT NOT NULL,
    nome VARCHAR(80) NOT NULL,
    especie VARCHAR(50) NULL,
    raca VARCHAR(80) NULL,
    sexo VARCHAR(20) NULL,
    idade INT NULL,
    nascimento DATE NULL,
    peso DECIMAL(5,2) NULL,
    observacoes TEXT NULL,
    INDEX idx_pets_cliente (cliente_id),
    CONSTRAINT fk_pets_cliente
      FOREIGN KEY (cliente_id) REFERENCES clientes(id)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS planos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    duracao_dias INT NOT NULL DEFAULT 30,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_planos_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assinaturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_externo VARCHAR(64) NULL UNIQUE,
    pet_id INT NOT NULL,
    plano_id INT NOT NULL,
    valor_mensal DECIMAL(10,2) NOT NULL DEFAULT 0,
    data_inicio DATE NULL,
    data_reajuste DATE NULL,
    adicional VARCHAR(255) NULL,
    observacoes TEXT NULL,
    origem VARCHAR(100) NULL,
    data_vencimento DATE NULL,
    status ENUM('Ativo', 'Cancelado') NOT NULL DEFAULT 'Ativo',
    cancelado_em DATETIME NULL,
    motivo_cancelamento VARCHAR(255) NULL,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_assinaturas_pet (pet_id),
    INDEX idx_assinaturas_plano (plano_id),
    CONSTRAINT fk_assinaturas_pet
      FOREIGN KEY (pet_id) REFERENCES pets(id)
      ON DELETE CASCADE,
    CONSTRAINT fk_assinaturas_plano
      FOREIGN KEY (plano_id) REFERENCES planos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS historico_planos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo_externo VARCHAR(64) NOT NULL UNIQUE,
    competencia DATE NOT NULL,
    cliente_nome VARCHAR(150) NOT NULL,
    pet_nome VARCHAR(100) NOT NULL,
    plano_nome VARCHAR(100) NOT NULL,
    valor_mensal DECIMAL(10,2) NOT NULL DEFAULT 0,
    data_inicio DATE NULL,
    data_reajuste DATE NULL,
    adicional VARCHAR(255) NULL,
    quantidade_banho INT NULL,
    valor_banho DECIMAL(10,2) NULL,
    observacoes TEXT NULL,
    origem VARCHAR(100) NOT NULL,
    linha_origem INT NULL,
    importado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_historico_competencia (competencia),
    INDEX idx_historico_plano (plano_nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cancelamentos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo_externo VARCHAR(64) NOT NULL UNIQUE,
    cliente_nome VARCHAR(150) NOT NULL,
    plano_nome VARCHAR(100) NULL,
    competencia DATE NOT NULL,
    motivo VARCHAR(255) NULL,
    tentativa_recuperacao VARCHAR(255) NULL,
    valor DECIMAL(10,2) NOT NULL DEFAULT 0,
    quantidade INT NOT NULL DEFAULT 1,
    origem VARCHAR(100) NOT NULL,
    linha_origem INT NULL,
    importado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cancelamentos_competencia (competencia),
    INDEX idx_cancelamentos_cliente (cliente_nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    trocar_senha TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
