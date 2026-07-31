<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/banco.php';
require_once dirname(__DIR__) . '/config/api.php';
iniciarRespostaApi();
exigirAcessoApi('POST');

try {
    $entrada = json_decode((string) file_get_contents('php://input'), true, 32, JSON_THROW_ON_ERROR);
    $id = (int) ($entrada['id'] ?? 0);
    if ($id <= 0) responderJson(422, ['sucesso' => false, 'mensagem' => 'Pet invalido.']);
    $pdo = conectarBanco();
    $pdo->beginTransaction();
    $removerAssinaturas = $pdo->prepare('DELETE FROM assinaturas WHERE pet_id = ?');
    $removerAssinaturas->execute([$id]);
    $stmt = $pdo->prepare('DELETE FROM pets WHERE id = ?');
    $stmt->execute([$id]);
    $pdo->commit();
    responderJson(200, ['sucesso' => true, 'mensagem' => 'Pet excluido.']);
} catch (Throwable $erro) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    error_log($erro->getMessage());
    responderJson(500, ['sucesso' => false, 'mensagem' => 'Nao foi possivel excluir o pet.']);
}
