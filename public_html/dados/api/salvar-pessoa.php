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
    $email = trim((string) ($entrada['email'] ?? ''));
    $planoId = isset($entrada['planoId']) ? (int) $entrada['planoId'] : 1;
    $status = trim((string) ($entrada['status'] ?? 'Ativo'));
    $dataCadastro = trim((string) ($entrada['dataCadastro'] ?? date('Y-m-d')));

    if ($nome === '' || $email === '') {
        responderJson(400, ['sucesso' => false, 'mensagem' => 'Nome e e-mail sao obrigatorios.']);
    }

    if (!in_array($status, ['Ativo', 'Cancelado'], true)) {
        $status = 'Ativo';
    }

    $pdo->beginTransaction();

    if ($id > 0) {
        // Atualizar cliente existente
        $stmt = $pdo->prepare("UPDATE clientes SET nome = :nome, email = :email WHERE id = :id");
        $stmt->execute(['nome' => $nome, 'email' => $email, 'id' => $id]);
        $clienteId = $id;

        // Atualizar plano e status da assinatura vinculada
        $stmtAss = $pdo->prepare(
            "UPDATE assinaturas a
               JOIN pets p ON p.id = a.pet_id
                SET a.plano_id = :plano_id, a.status = :status
              WHERE p.cliente_id = :cliente_id"
        );
        $stmtAss->execute([
            'plano_id' => $planoId,
            'status' => $status,
            'cliente_id' => $clienteId
        ]);
    } else {
        // Inserir novo cliente
        $cpf = preg_replace('/\D+/', '', $entrada['cpf'] ?? '') ?: sprintf('%011d', rand(1, 99999999999));
        $telefone = preg_replace('/\D+/', '', $entrada['telefone'] ?? '') ?: '11999999999';

        $stmt = $pdo->prepare(
            "INSERT INTO clientes (nome, cpf, email, telefone, created_at)
             VALUES (:nome, :cpf, :email, :telefone, :created_at)"
        );
        $stmt->execute([
            'nome' => $nome,
            'cpf' => $cpf,
            'email' => $email,
            'telefone' => $telefone,
            'created_at' => $dataCadastro . ' 00:00:00'
        ]);
        $clienteId = (int) $pdo->lastInsertId();

        // Inserir pet padrao
        $stmtPet = $pdo->prepare("INSERT INTO pets (cliente_id, nome, especie) VALUES (:cliente_id, :nome, 'Pet')");
        $stmtPet->execute(['cliente_id' => $clienteId, 'nome' => 'Pet de ' . $nome]);
        $petId = (int) $pdo->lastInsertId();

        // Inserir assinatura
        $dataVencimento = date('Y-m-d', strtotime('+30 days', strtotime($dataCadastro)));
        $stmtAss = $pdo->prepare(
            "INSERT INTO assinaturas (pet_id, plano_id, data_inicio, data_vencimento, status)
             VALUES (:pet_id, :plano_id, :data_inicio, :data_vencimento, :status)"
        );
        $stmtAss->execute([
            'pet_id' => $petId,
            'plano_id' => $planoId,
            'data_inicio' => $dataCadastro,
            'data_vencimento' => $dataVencimento,
            'status' => $status
        ]);
    }

    $pdo->commit();

    responderJson(200, [
        'sucesso' => true,
        'mensagem' => 'Pessoa salva com sucesso no banco de dados.',
        'clienteId' => $clienteId
    ]);
} catch (Throwable $erro) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($erro->getMessage());
    responderJson(500, ['sucesso' => false, 'mensagem' => 'Erro ao salvar no banco de dados: ' . $erro->getMessage()]);
}
