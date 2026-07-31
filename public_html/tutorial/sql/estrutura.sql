CREATE DATABASE IF NOT EXISTS tudoprapet
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE tudoprapet;

CREATE TABLE IF NOT EXISTS clientes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    cpf CHAR(11) NOT NULL UNIQUE,
    telefone VARCHAR(11) NOT NULL,
    email VARCHAR(190) NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id BIGINT UNSIGNED NOT NULL,
    nome VARCHAR(100) NOT NULL,
    especie VARCHAR(30) NOT NULL,
    raca VARCHAR(100) NOT NULL,
    sexo ENUM('Macho', 'Femea') NOT NULL,
    idade TINYINT UNSIGNED NULL,
    nascimento DATE NULL,
    peso DECIMAL(6,2) NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pets_cliente (cliente_id),
    CONSTRAINT fk_pets_cliente
      FOREIGN KEY (cliente_id) REFERENCES clientes(id)
      ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS planos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pet_id BIGINT UNSIGNED NOT NULL,
    nome ENUM('Bronze', 'Prata', 'Ouro') NOT NULL,
    data_inicio DATE NOT NULL,
    data_vencimento DATE NOT NULL,
    status ENUM('Ativo', 'Pendente', 'Vencido', 'Cancelado')
      NOT NULL DEFAULT 'Ativo',
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_planos_pet (pet_id),
    CONSTRAINT fk_planos_pet
      FOREIGN KEY (pet_id) REFERENCES pets(id)
      ON DELETE CASCADE
) ENGINE=InnoDB;
