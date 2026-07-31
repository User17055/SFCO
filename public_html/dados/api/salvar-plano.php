<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/banco.php';
require_once dirname(__DIR__) . '/config/api.php';
iniciarRespostaApi();
exigirAcessoApi('POST');

try {
    $entrada = json_decode((string) file_get_contents('php://input'), true, 32, JSON_THROW_ON_ERROR);
    $id = (int) ($entrada['id'] ?? 0);
    $nome = mb_strtoupper(trim((string) ($entrada['nome'] ?? '')));
    $valor = max(0, (float) ($entrada['valorPadrao'] ?? $entrada['valor'] ?? 0));
    $ativo = !array_key_exists('ativo', $entrada) || (bool) $entrada['ativo'];
    if ($nome === '' || mb_strlen($nome) > 100) {
        responderJson(422, ['sucesso' => false, 'mensagem' => 'Informe o nome do plano.']);
    }
    $pdo = conectarBanco();
    $duplicado = $pdo->prepare('SELECT id FROM planos WHERE nome = ? AND id <> ? LIMIT 1');
    $duplicado->execute([$nome, $id]);
    if ($duplicado->fetchColumn()) {
        responderJson(409, ['sucesso' => false, 'mensagem' => 'Ja existe um plano com esse nome.']);
    }
    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE planos SET nome = ?, valor = ?, ativo = ? WHERE id = ?');
        $stmt->execute([$nome, $valor, $ativo ? 1 : 0, $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO planos (nome, valor, duracao_dias, ativo) VALUES (?, ?, 30, ?)');
        $stmt->execute([$nome, $valor, $ativo ? 1 : 0]);
        $id = (int) $pdo->lastInsertId();
    }
    responderJson(200, ['sucesso' => true, 'mensagem' => 'Plano salvo com sucesso.', 'id' => $id]);
} catch (JsonException) {
    responderJson(400, ['sucesso' => false, 'mensagem' => 'Dados invalidos.']);
} catch (Throwable $erro) {
    error_log($erro->getMessage());
    responderJson(500, ['sucesso' => false, 'mensagem' => 'Nao foi possivel salvar o plano.']);
}
