<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/banco.php';

$pdo = conectarBanco();

function colunaExiste(PDO $pdo, string $tabela, string $coluna): bool
{
    $consulta = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $consulta->execute([$tabela, $coluna]);
    return (int) $consulta->fetchColumn() > 0;
}

function indiceExiste(PDO $pdo, string $tabela, string $indice): bool
{
    $consulta = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
    );
    $consulta->execute([$tabela, $indice]);
    return (int) $consulta->fetchColumn() > 0;
}
$consulta = $pdo->prepare(
    "SELECT COUNT(*)
       FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'pets'
        AND COLUMN_NAME = 'nascimento'"
);
$consulta->execute();

if ((int) $consulta->fetchColumn() === 0) {
    $pdo->exec('ALTER TABLE pets ADD COLUMN nascimento DATE NULL AFTER idade');
    echo "Coluna pets.nascimento adicionada.\n";
} else {
    echo "Coluna pets.nascimento ja existe.\n";
}

$consulta = $pdo->prepare(
    "SELECT COUNT(*)
       FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'usuarios'
        AND COLUMN_NAME = 'trocar_senha'"
);
$consulta->execute();

if ((int) $consulta->fetchColumn() === 0) {
    $pdo->exec(
        'ALTER TABLE usuarios
         ADD COLUMN trocar_senha TINYINT(1) NOT NULL DEFAULT 1 AFTER senha'
    );
    echo "Coluna usuarios.trocar_senha adicionada.\n";
} else {
    echo "Coluna usuarios.trocar_senha ja existe.\n";
}

$consulta = $pdo->prepare(
    "SELECT COUNT(*)
       FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'assinaturas'
        AND COLUMN_NAME = 'status'"
);
$consulta->execute();

if ((int) $consulta->fetchColumn() === 0) {
    $pdo->exec(
        "ALTER TABLE assinaturas
         ADD COLUMN status ENUM('Ativo', 'Cancelado')
         NOT NULL DEFAULT 'Ativo' AFTER data_vencimento"
    );
    echo "Coluna assinaturas.status adicionada.\n";
} else {
    echo "Coluna assinaturas.status ja existe.\n";
}

$pdo->exec(
    'ALTER TABLE clientes
       MODIFY nome VARCHAR(150) NOT NULL,
       MODIFY cpf VARCHAR(14) NULL'
);

$novasColunas = [
    ['clientes', 'codigo_externo', 'VARCHAR(64) NULL AFTER id'],
    ['clientes', 'observacoes', 'TEXT NULL AFTER telefone'],
    ['pets', 'codigo_externo', 'VARCHAR(64) NULL AFTER id'],
    ['pets', 'observacoes', 'TEXT NULL AFTER peso'],
    ['planos', 'ativo', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER duracao_dias'],
    ['planos', 'created_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER ativo'],
    ['planos', 'updated_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at'],
    ['assinaturas', 'codigo_externo', 'VARCHAR(64) NULL AFTER id'],
    ['assinaturas', 'valor_mensal', 'DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER plano_id'],
    ['assinaturas', 'data_reajuste', 'DATE NULL AFTER data_inicio'],
    ['assinaturas', 'adicional', 'VARCHAR(255) NULL AFTER data_reajuste'],
    ['assinaturas', 'observacoes', 'TEXT NULL AFTER adicional'],
    ['assinaturas', 'origem', 'VARCHAR(100) NULL AFTER observacoes'],
    ['assinaturas', 'cancelado_em', 'DATETIME NULL AFTER status'],
    ['assinaturas', 'motivo_cancelamento', 'VARCHAR(255) NULL AFTER cancelado_em'],
    ['assinaturas', 'updated_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER motivo_cancelamento'],
];

foreach ($novasColunas as [$tabela, $coluna, $definicao]) {
    if (!colunaExiste($pdo, $tabela, $coluna)) {
        $pdo->exec("ALTER TABLE {$tabela} ADD COLUMN {$coluna} {$definicao}");
        echo "Coluna {$tabela}.{$coluna} adicionada.\n";
    }
}

$pdo->exec('ALTER TABLE planos MODIFY nome VARCHAR(100) NOT NULL');

$indices = [
    ['clientes', 'uq_clientes_codigo_externo', 'codigo_externo'],
    ['pets', 'uq_pets_codigo_externo', 'codigo_externo'],
    ['assinaturas', 'uq_assinaturas_codigo_externo', 'codigo_externo'],
];
foreach ($indices as [$tabela, $indice, $coluna]) {
    if (!indiceExiste($pdo, $tabela, $indice)) {
        $pdo->exec("ALTER TABLE {$tabela} ADD UNIQUE INDEX {$indice} ({$coluna})");
        echo "Indice {$indice} adicionado.\n";
    }
}

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS historico_planos (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        codigo_externo VARCHAR(64) NOT NULL,
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
        UNIQUE KEY uq_historico_codigo_externo (codigo_externo),
        INDEX idx_historico_competencia (competencia),
        INDEX idx_historico_plano (plano_nome)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);
echo "Tabela historico_planos pronta.\n";

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS cancelamentos (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        codigo_externo VARCHAR(64) NOT NULL,
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
        UNIQUE KEY uq_cancelamentos_codigo_externo (codigo_externo),
        INDEX idx_cancelamentos_competencia (competencia),
        INDEX idx_cancelamentos_cliente (cliente_nome)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);
echo "Tabela cancelamentos pronta.\n";

echo "Migracao concluida.\n";
