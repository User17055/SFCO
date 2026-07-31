CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) NOT NULL UNIQUE,
    email VARCHAR(100) NULL,
    telefone VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    nome VARCHAR(80) NOT NULL,
    especie VARCHAR(50) NULL,
    raca VARCHAR(80) NULL,
    sexo VARCHAR(20) NULL,
    idade INT NULL,
    nascimento DATE NULL,
    peso DECIMAL(5,2) NULL,
    INDEX idx_pets_cliente (cliente_id),
    CONSTRAINT fk_pets_cliente
      FOREIGN KEY (cliente_id) REFERENCES clientes(id)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS planos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(30) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    duracao_dias INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assinaturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pet_id INT NOT NULL,
    plano_id INT NOT NULL,
    data_inicio DATE NULL,
    data_vencimento DATE NULL,
    INDEX idx_assinaturas_pet (pet_id),
    INDEX idx_assinaturas_plano (plano_id),
    CONSTRAINT fk_assinaturas_pet
      FOREIGN KEY (pet_id) REFERENCES pets(id)
      ON DELETE CASCADE,
    CONSTRAINT fk_assinaturas_plano
      FOREIGN KEY (plano_id) REFERENCES planos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO planos (nome, valor, duracao_dias)
SELECT 'Bronze', 49.90, 30
WHERE NOT EXISTS (SELECT 1 FROM planos WHERE nome = 'Bronze');

INSERT INTO planos (nome, valor, duracao_dias)
SELECT 'Prata', 79.90, 30
WHERE NOT EXISTS (SELECT 1 FROM planos WHERE nome = 'Prata');

INSERT INTO planos (nome, valor, duracao_dias)
SELECT 'Ouro', 119.90, 30
WHERE NOT EXISTS (SELECT 1 FROM planos WHERE nome = 'Ouro');

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    trocar_senha TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
