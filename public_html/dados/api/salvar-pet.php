<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/banco.php';
require_once dirname(__DIR__) . '/config/api.php';

iniciarRespostaApi();
exigirAcessoApi('POST');

try {
    $entrada = json_decode((string) file_get_contents('php://input'), true, 32, JSON_THROW_ON_ERROR);
    $id = (int) ($entrada['id'] ?? 0);
    $clienteId = (int) ($entrada['clienteId'] ?? 0);
    $nome = trim((string) ($entrada['nome'] ?? ''));
    $especie = trim((string) ($entrada['especie'] ?? '')) ?: null;
    $raca = trim((string) ($entrada['raca'] ?? '')) ?: null;
    $sexo = trim((string) ($entrada['sexo'] ?? '')) ?: null;
    $idade = ($entrada['idade'] ?? '') === '' ? null : (int) $entrada['idade'];
    $nascimento = trim((string) ($entrada['nascimento'] ?? '')) ?: null;
    $peso = ($entrada['peso'] ?? '') === '' ? null : (float) $entrada['peso'];
    $observacoes = trim((string) ($entrada['observacoes'] ?? '')) ?: null;
    if ($clienteId <= 0 || $nome === '') {
        responderJson(422, ['sucesso' => false, 'mensagem' => 'Tutor e nome do pet sao obrigatorios.']);
    }
    $pdo = conectarBanco();
    if ($id > 0) {
        $stmt = $pdo->prepare(
            'UPDATE pets SET cliente_id = ?, nome = ?, especie = ?, raca = ?, sexo = ?,
                    idade = ?, nascimento = ?, peso = ?, observacoes = ? WHERE id = ?'
        );
        $stmt->execute([$clienteId, $nome, $especie, $raca, $sexo, $idade, $nascimento, $peso, $observacoes, $id]);
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO pets (cliente_id, nome, especie, raca, sexo, idade, nascimento, peso, observacoes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$clienteId, $nome, $especie, $raca, $sexo, $idade, $nascimento, $peso, $observacoes]);
        $id = (int) $pdo->lastInsertId();
    }
    responderJson(200, ['sucesso' => true, 'mensagem' => 'Pet salvo com sucesso.', 'id' => $id]);
} catch (JsonException) {
    responderJson(400, ['sucesso' => false, 'mensagem' => 'Dados invalidos.']);
} catch (Throwable $erro) {
    error_log($erro->getMessage());
    responderJson(500, ['sucesso' => false, 'mensagem' => 'Nao foi possivel salvar o pet.']);
}
