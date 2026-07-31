<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/banco.php';
require_once dirname(__DIR__) . '/config/api.php';
iniciarRespostaApi();
exigirAcessoApi('POST');

try {
    $entrada = json_decode((string) file_get_contents('php://input'), true, 32, JSON_THROW_ON_ERROR);
    $id = (int) ($entrada['id'] ?? 0);
    $petId = (int) ($entrada['petId'] ?? 0);
    $planoId = (int) ($entrada['planoId'] ?? 0);
    $valor = max(0, (float) ($entrada['valorMensal'] ?? 0));
    $inicio = trim((string) ($entrada['dataInicio'] ?? '')) ?: null;
    $reajuste = trim((string) ($entrada['dataReajuste'] ?? '')) ?: null;
    $adicional = trim((string) ($entrada['adicional'] ?? '')) ?: null;
    $observacoes = trim((string) ($entrada['observacoes'] ?? '')) ?: null;
    if ($petId <= 0 || $planoId <= 0) {
        responderJson(422, ['sucesso' => false, 'mensagem' => 'Pet e plano sao obrigatorios.']);
    }
    $pdo = conectarBanco();
    if ($id > 0) {
        $stmt = $pdo->prepare(
            'UPDATE assinaturas SET pet_id = ?, plano_id = ?, valor_mensal = ?, data_inicio = ?,
                    data_reajuste = ?, adicional = ?, observacoes = ? WHERE id = ?'
        );
        $stmt->execute([$petId, $planoId, $valor, $inicio, $reajuste, $adicional, $observacoes, $id]);
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO assinaturas
                (pet_id, plano_id, valor_mensal, data_inicio, data_reajuste, adicional,
                 observacoes, origem, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'Cadastro pelo sistema', 'Ativo')"
        );
        $stmt->execute([$petId, $planoId, $valor, $inicio, $reajuste, $adicional, $observacoes]);
        $id = (int) $pdo->lastInsertId();
    }
    responderJson(200, ['sucesso' => true, 'mensagem' => 'Assinatura salva com sucesso.', 'id' => $id]);
} catch (JsonException) {
    responderJson(400, ['sucesso' => false, 'mensagem' => 'Dados invalidos.']);
} catch (Throwable $erro) {
    error_log($erro->getMessage());
    responderJson(500, ['sucesso' => false, 'mensagem' => 'Nao foi possivel salvar a assinatura.']);
}
