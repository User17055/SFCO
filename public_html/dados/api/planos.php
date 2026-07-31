<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

require_once dirname(__DIR__) . '/config/banco.php';
require_once dirname(__DIR__) . '/config/api.php';

iniciarRespostaApi();
date_default_timezone_set('America/Sao_Paulo');

try {
    $pdo = conectarBanco();

    // 1. Buscar lista de todos os planos cadastrados no MySQL
    $stmtPlanos = $pdo->query("SELECT id, nome, valor, duracao_dias FROM planos ORDER BY id ASC");
    $planos = array_map(
        static fn (array $p): array => [
            'id' => (int) $p['id'],
            'nome' => $p['nome'],
            'valor' => (float) $p['valor'],
            'duracaoDias' => (int) $p['duracao_dias']
        ],
        $stmtPlanos->fetchAll()
    );

    // 2. Buscar assinaturas/membros cadastrados no MySQL com status (incluindo Cancelado)
    $consulta = $pdo->query(
        "SELECT
            a.id, c.id AS cliente_id, c.nome AS cliente, c.email AS email, p.nome AS pet,
            pl.id AS plano_id, pl.nome AS plano, pl.valor, a.data_inicio, a.data_vencimento,
            CASE
              WHEN a.status = 'Cancelado' THEN 'Cancelado'
              WHEN a.data_inicio IS NULL OR a.data_vencimento IS NULL THEN 'Sem data'
              WHEN a.data_inicio > CURDATE() THEN 'Pendente'
              WHEN a.data_vencimento < CURDATE() THEN 'Vencido'
              ELSE 'Ativo'
            END AS status
         FROM assinaturas a
         JOIN pets p ON p.id = a.pet_id
         JOIN clientes c ON c.id = p.cliente_id
         JOIN planos pl ON pl.id = a.plano_id
         ORDER BY a.id DESC"
    );

    $assinaturas = array_map(
        static fn (array $linha): array => [
            'id' => (int) $linha['id'],
            'clienteId' => (int) $linha['cliente_id'],
            'cliente' => $linha['cliente'],
            'email' => $linha['email'] ?? '',
            'pet' => $linha['pet'],
            'planoId' => (int) $linha['plano_id'],
            'plano' => $linha['plano'],
            'valor' => (float) $linha['valor'],
            'dataInicio' => $linha['data_inicio'],
            'dataVencimento' => $linha['data_vencimento'],
            'status' => $linha['status'],
        ],
        $consulta->fetchAll()
    );

    responderJson(200, [
        'sucesso' => true,
        'planos' => $planos,
        'assinaturas' => $assinaturas
    ]);
} catch (Throwable $erro) {
    error_log($erro->getMessage());
    responderJson(500, [
        'sucesso' => false,
        'mensagem' => 'Nao foi possivel carregar os planos do banco de dados: ' . $erro->getMessage(),
    ]);
}
