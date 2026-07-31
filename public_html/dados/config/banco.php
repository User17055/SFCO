<?php
declare(strict_types=1);

/**
 * Cria uma conexao PDO com MySQL.
 *
 * Credenciais do banco de testes da hospedagem.
 */
function conectarBanco(): PDO
{
    $host = 'dadosplanilha.mysql.dbaas.com.br';
    $porta = '3306';
    $banco = 'dadosplanilha';
    $usuario = 'dadosplanilha';
    $senha = 'Sf1499@';

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
