<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/banco.php';
require_once dirname(__DIR__) . '/config/api.php';

iniciarRespostaApi();
exigirAcessoApi();

try {
    $pdo = conectarBanco();
    $consulta = $pdo->query(
        "SELECT p.id, p.cliente_id, p.nome, p.especie, p.raca, p.sexo, p.idade,
                p.nascimento, p.peso, p.observacoes, c.nome AS tutor,
                COUNT(CASE WHEN a.status = 'Ativo' THEN 1 END) AS planos_ativos,
                COALESCE(SUM(CASE WHEN a.status = 'Ativo' THEN a.valor_mensal ELSE 0 END), 0) AS projecao,
                GROUP_CONCAT(DISTINCT CASE WHEN a.status = 'Ativo' THEN pl.nome END
                             ORDER BY pl.nome SEPARATOR ', ') AS planos
           FROM pets p JOIN clientes c ON c.id = p.cliente_id
           LEFT JOIN assinaturas a ON a.pet_id = p.id
           LEFT JOIN planos pl ON pl.id = a.plano_id
          GROUP BY p.id ORDER BY c.nome, p.nome"
    );
    $pets = array_map(static fn (array $linha): array => [
        'id' => (int) $linha['id'],
        'clienteId' => (int) $linha['cliente_id'],
        'nome' => $linha['nome'],
        'especie' => $linha['especie'] ?? '',
        'raca' => $linha['raca'] ?? '',
        'sexo' => $linha['sexo'] ?? '',
        'idade' => $linha['idade'] === null ? null : (int) $linha['idade'],
        'nascimento' => $linha['nascimento'],
        'peso' => $linha['peso'] === null ? null : (float) $linha['peso'],
        'observacoes' => $linha['observacoes'] ?? '',
        'tutor' => $linha['tutor'],
        'planosAtivos' => (int) $linha['planos_ativos'],
        'planos' => $linha['planos'] ?: 'Sem plano ativo',
        'projecao' => (float) $linha['projecao'],
    ], $consulta->fetchAll());
    responderJson(200, ['sucesso' => true, 'pets' => $pets]);
} catch (Throwable $erro) {
    error_log($erro->getMessage());
    responderJson(500, ['sucesso' => false, 'mensagem' => 'Nao foi possivel carregar os pets.']);
}
