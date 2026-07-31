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
    
    $id = (int) ($entrada['id'] ?? $entrada['assinaturaId'] ?? $entrada['clienteId'] ?? 0);
    $novoStatus = trim((string) ($entrada['status'] ?? 'Cancelado'));
    
    if (!in_array($novoStatus, ['Ativo', 'Cancelado'], true)) {
        $novoStatus = 'Cancelado';
    }

    if ($id <= 0) {
        responderJson(400, ['sucesso' => false, 'mensagem' => 'ID invalido.']);
    }

    // Tentar atualizar assinatura diretamente ou buscar por cliente
    $stmt = $pdo->prepare("UPDATE assinaturas SET status = :status WHERE id = :id");
    $stmt->execute(['status' => $novoStatus, 'id' => $id]);

    if ($stmt->rowCount() === 0) {
        // Tentar atualizar assinaturas vinculadas ao cliente_id
        $stmt2 = $pdo->prepare(
            "UPDATE assinaturas a
               JOIN pets p ON p.id = a.pet_id
                SET a.status = :status
              WHERE p.cliente_id = :cliente_id"
        );
        $stmt2->execute(['status' => $novoStatus, 'cliente_id' => $id]);
    }

    responderJson(200, [
        'sucesso' => true,
        'mensagem' => "Status alterado para {$novoStatus} com sucesso no banco de dados.",
        'status' => $novoStatus
    ]);
} catch (Throwable $erro) {
    error_log($erro->getMessage());
    responderJson(500, ['sucesso' => false, 'mensagem' => 'Erro ao atualizar status no banco de dados: ' . $erro->getMessage()]);
}
