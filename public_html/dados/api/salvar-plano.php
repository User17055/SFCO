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

    $id = isset($entrada['id']) ? (int) $entrada['id'] : 0;
    $nome = trim((string) ($entrada['nome'] ?? ''));
    $valor = (float) ($entrada['valor'] ?? 0);

    if ($nome === '') {
        responderJson(400, ['sucesso' => false, 'mensagem' => 'Nome do plano e obrigatorio.']);
    }

    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE planos SET nome = :nome, valor = :valor WHERE id = :id");
        $stmt->execute(['nome' => $nome, 'valor' => $valor, 'id' => $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO planos (nome, valor, duracao_dias) VALUES (:nome, :valor, 30)");
        $stmt->execute(['nome' => $nome, 'valor' => $valor]);
        $id = (int) $pdo->lastInsertId();
    }

    responderJson(200, [
        'sucesso' => true,
        'mensagem' => 'Plano salvo com sucesso no banco de dados.',
        'id' => $id,
        'nome' => $nome,
        'valor' => $valor
    ]);
} catch (Throwable $erro) {
    error_log($erro->getMessage());
    responderJson(500, ['sucesso' => false, 'mensagem' => 'Erro ao salvar plano no banco de dados: ' . $erro->getMessage()]);
}
