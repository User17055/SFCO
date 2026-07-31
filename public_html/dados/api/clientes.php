<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/banco.php';
require_once dirname(__DIR__) . '/config/api.php';

iniciarRespostaApi();
exigirAcessoApi();

try {
    $pdo = conectarBanco();
    $consulta = $pdo->query(
        "SELECT c.id, c.nome, c.telefone, c.cpf, c.email, c.observacoes, c.created_at,
                COUNT(DISTINCT p.id) AS total_pets,
                SUM(CASE WHEN a.status = 'Ativo' THEN 1 ELSE 0 END) AS planos_ativos,
                SUM(CASE WHEN a.status = 'Cancelado' THEN 1 ELSE 0 END) AS planos_cancelados,
                COALESCE(SUM(CASE WHEN a.status = 'Ativo' THEN a.valor_mensal ELSE 0 END), 0) AS projecao,
                GROUP_CONCAT(DISTINCT CASE WHEN a.status = 'Ativo' THEN pl.nome END
                             ORDER BY pl.nome SEPARATOR ', ') AS planos
           FROM clientes c
           LEFT JOIN pets p ON p.cliente_id = c.id
           LEFT JOIN assinaturas a ON a.pet_id = p.id
           LEFT JOIN planos pl ON pl.id = a.plano_id
          GROUP BY c.id ORDER BY c.nome"
    );
    $clientes = array_map(static fn (array $linha): array => [
        'id' => (int) $linha['id'],
        'nome' => $linha['nome'],
        'telefone' => $linha['telefone'] ?? '',
        'cpf' => $linha['cpf'] ?? '',
        'email' => $linha['email'] ?? '',
        'observacoes' => $linha['observacoes'] ?? '',
        'totalPets' => (int) $linha['total_pets'],
        'planosAtivos' => (int) $linha['planos_ativos'],
        'planosCancelados' => (int) $linha['planos_cancelados'],
        'projecao' => (float) $linha['projecao'],
        'planos' => $linha['planos'] ?: 'Sem plano ativo',
        'criadoEm' => $linha['created_at'],
    ], $consulta->fetchAll());
    responderJson(200, ['sucesso' => true, 'clientes' => $clientes]);
} catch (Throwable $erro) {
    error_log($erro->getMessage());
    responderJson(500, ['sucesso' => false, 'mensagem' => 'Nao foi possivel carregar os clientes.']);
}
