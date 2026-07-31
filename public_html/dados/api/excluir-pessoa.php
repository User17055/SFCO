<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

require_once dirname(__DIR__) . '/config/banco.php';
require_once dirname(__DIR__) . '/config/api.php';

iniciarRespostaApi();

try {
    $pdo = conectarBanco();
    $entrada = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int) ($entrada['id'] ?? 0);

    if ($id <= 0) {
        responderJson(400, ['sucesso' => false, 'mensagem' => 'ID invalido.']);
    }

    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = :id");
    $stmt->execute(['id' => $id]);

    responderJson(200, [
        'sucesso' => true,
        'mensagem' => 'Pessoa removida com sucesso do banco de dados.'
    ]);
} catch (Throwable $erro) {
    error_log($erro->getMessage());
    responderJson(500, ['sucesso' => false, 'mensagem' => 'Erro ao remover do banco de dados: ' . $erro->getMessage()]);
}
