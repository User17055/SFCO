<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/banco.php';
require_once dirname(__DIR__) . '/config/api.php';
iniciarRespostaApi();
exigirAcessoApi('POST');

try {
    $entrada = json_decode((string) file_get_contents('php://input'), true, 32, JSON_THROW_ON_ERROR);
    $id = (int) ($entrada['id'] ?? 0);
    $status = ($entrada['status'] ?? 'Cancelado') === 'Ativo' ? 'Ativo' : 'Cancelado';
    $motivo = trim((string) ($entrada['motivo'] ?? '')) ?: null;
    if ($id <= 0) responderJson(422, ['sucesso' => false, 'mensagem' => 'Assinatura invalida.']);
    $pdo = conectarBanco();
    if ($status === 'Cancelado') {
        $stmt = $pdo->prepare(
            "UPDATE assinaturas SET status = 'Cancelado', cancelado_em = NOW(), motivo_cancelamento = ? WHERE id = ?"
        );
        $stmt->execute([$motivo ?: 'Cancelado pelo sistema', $id]);
    } else {
        $stmt = $pdo->prepare(
            "UPDATE assinaturas SET status = 'Ativo', cancelado_em = NULL, motivo_cancelamento = NULL WHERE id = ?"
        );
        $stmt->execute([$id]);
    }
    responderJson(200, ['sucesso' => true, 'mensagem' => $status === 'Ativo' ? 'Plano reativado.' : 'Plano cancelado.']);
} catch (Throwable $erro) {
    error_log($erro->getMessage());
    responderJson(500, ['sucesso' => false, 'mensagem' => 'Nao foi possivel alterar o plano.']);
}
