<?php
declare(strict_types=1);

/**
 * Cria uma conexao PDO com MySQL.
 *
 * Na hospedagem, prefira definir as variaveis de ambiente TPP_DB_*.
 * Os valores padrao abaixo atendem a instalacao comum do XAMPP.
 */
function conectarBanco(): PDO
{
    $host = getenv('TPP_DB_HOST') ?: 'localhost';
    $porta = getenv('TPP_DB_PORT') ?: '3306';
    $banco = getenv('TPP_DB_NAME') ?: 'tudoprapet';
    $usuario = getenv('TPP_DB_USER') ?: 'root';
    $senhaAmbiente = getenv('TPP_DB_PASSWORD');
    $senha = $senhaAmbiente === false ? '' : $senhaAmbiente;

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $host,
        $porta,
        $banco
    );

    return new PDO($dsn, $usuario, $senha, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
