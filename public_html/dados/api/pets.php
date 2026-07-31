<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/banco.php';
require_once dirname(__DIR__) . '/config/api.php';

iniciarRespostaApi();
exigirAcessoApi();

try {
    $pdo = conectarBanco();
    $consulta = $pdo->query(
        "SELECT
            p.id, p.nome, p.especie, p.raca, p.sexo, p.idade, p.nascimento,
            p.peso, c.nome AS tutor, pl.nome AS plano,
            CASE
              WHEN a.id IS NULL THEN 'Sem plano'
              WHEN a.status = 'Cancelado' THEN 'Cancelado'
              WHEN a.data_inicio IS NULL OR a.data_vencimento IS NULL THEN 'Sem data'
              WHEN a.data_inicio > CURDATE() THEN 'Pendente'
              WHEN a.data_vencimento < CURDATE() THEN 'Vencido'
              ELSE 'Ativo'
            END AS status
         FROM pets p
         JOIN clientes c ON c.id = p.cliente_id
         LEFT JOIN assinaturas a ON a.id = (
           SELECT a2.id FROM assinaturas a2
            WHERE a2.pet_id = p.id
            ORDER BY a2.id DESC LIMIT 1
         )
         LEFT JOIN planos pl ON pl.id = a.plano_id
         ORDER BY p.id DESC"
    );

    $pets = array_map(
        static fn (array $linha): array => [
            'id' => (int) $linha['id'],
            'nome' => $linha['nome'],
            'especie' => $linha['especie'] ?? '',
            'raca' => $linha['raca'] ?? '',
            'sexo' => $linha['sexo'] ?? '',
            'idade' => $linha['idade'] === null ? null : (int) $linha['idade'],
            'nascimento' => $linha['nascimento'],
            'peso' => $linha['peso'] === null ? null : (float) $linha['peso'],
            'tutor' => $linha['tutor'],
            'plano' => $linha['plano'] ?? 'Sem plano',
            'status' => $linha['status'],
        ],
        $consulta->fetchAll()
    );

    responderJson(200, ['sucesso' => true, 'pets' => $pets]);
} catch (Throwable $erro) {
    error_log($erro->getMessage());
    responderJson(500, [
        'sucesso' => false,
        'mensagem' => 'Nao foi possivel carregar os pets.',
    ]);
}
