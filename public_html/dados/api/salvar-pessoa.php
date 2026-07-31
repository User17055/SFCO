<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/banco.php';
require_once dirname(__DIR__) . '/config/api.php';

iniciarRespostaApi();
exigirAcessoApi('POST');

try {
    $entrada = json_decode((string) file_get_contents('php://input'), true, 32, JSON_THROW_ON_ERROR);
    $id = (int) ($entrada['id'] ?? 0);
    $nome = trim((string) ($entrada['nome'] ?? ''));
    $email = trim((string) ($entrada['email'] ?? ''));
    $cpf = preg_replace('/\D+/', '', (string) ($entrada['cpf'] ?? '')) ?: null;
    $telefone = preg_replace('/\D+/', '', (string) ($entrada['telefone'] ?? '')) ?: null;
    $observacoes = trim((string) ($entrada['observacoes'] ?? '')) ?: null;
    if ($nome === '' || mb_strlen($nome) > 150) {
        responderJson(422, ['sucesso' => false, 'mensagem' => 'Informe o nome do cliente.']);
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        responderJson(422, ['sucesso' => false, 'mensagem' => 'Informe um e-mail valido.']);
    }
    $pdo = conectarBanco();
    if ($id > 0) {
        $stmt = $pdo->prepare(
            'UPDATE clientes SET nome = ?, cpf = ?, email = ?, telefone = ?, observacoes = ? WHERE id = ?'
        );
        $stmt->execute([$nome, $cpf, $email ?: null, $telefone, $observacoes, $id]);
        if ($stmt->rowCount() === 0) {
            $existe = $pdo->prepare('SELECT 1 FROM clientes WHERE id = ?');
            $existe->execute([$id]);
            if (!$existe->fetchColumn()) {
                responderJson(404, ['sucesso' => false, 'mensagem' => 'Cliente nao encontrado.']);
            }
        }
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO clientes (nome, cpf, email, telefone, observacoes) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$nome, $cpf, $email ?: null, $telefone, $observacoes]);
        $id = (int) $pdo->lastInsertId();
    }
    responderJson(200, ['sucesso' => true, 'mensagem' => 'Cliente salvo com sucesso.', 'id' => $id]);
} catch (JsonException) {
    responderJson(400, ['sucesso' => false, 'mensagem' => 'Dados invalidos.']);
} catch (Throwable $erro) {
    error_log($erro->getMessage());
    responderJson(500, ['sucesso' => false, 'mensagem' => 'Nao foi possivel salvar o cliente.']);
}
