<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/banco.php';

$pdo = conectarBanco();
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

echo "Migracao concluida.\n";
