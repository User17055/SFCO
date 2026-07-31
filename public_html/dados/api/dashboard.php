<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/banco.php';
require_once dirname(__DIR__) . '/config/api.php';

iniciarRespostaApi();
exigirAcessoApi();
date_default_timezone_set('America/Sao_Paulo');

try {
    $pdo = conectarBanco();
    $metricas = $pdo->query(
        "SELECT
            (SELECT COUNT(*) FROM clientes) AS total_clientes,
            (SELECT COUNT(*) FROM pets) AS total_pets,
            (SELECT COUNT(*) FROM assinaturas WHERE status = 'Ativo') AS planos_ativos,
            (SELECT COUNT(*) FROM assinaturas WHERE status = 'Cancelado') AS planos_cancelados,
            (SELECT COUNT(*) FROM planos WHERE ativo = 1) AS tipos_planos,
            (SELECT COALESCE(SUM(valor_mensal), 0) FROM assinaturas WHERE status = 'Ativo') AS projecao"
    )->fetch();

    $ativos = (int) $metricas['planos_ativos'];
    $projecao = (float) $metricas['projecao'];

    $historico = $pdo->query(
        "SELECT DATE_FORMAT(competencia, '%Y-%m') AS competencia,
                COUNT(*) AS quantidade, ROUND(SUM(valor_mensal), 2) AS projecao
           FROM historico_planos
          GROUP BY competencia ORDER BY competencia"
    )->fetchAll();
    $historico[] = [
        'competencia' => '2026-07',
        'quantidade' => $ativos,
        'projecao' => $projecao,
    ];

    $porPlano = $pdo->query(
        "SELECT pl.nome, COUNT(*) AS quantidade, ROUND(SUM(a.valor_mensal), 2) AS projecao
           FROM assinaturas a JOIN planos pl ON pl.id = a.plano_id
          WHERE a.status = 'Ativo'
          GROUP BY pl.id, pl.nome ORDER BY quantidade DESC, pl.nome"
    )->fetchAll();

    $cancelamentos = $pdo->query(
        "SELECT DATE_FORMAT(competencia, '%Y-%m') AS competencia,
                SUM(quantidade) AS quantidade
           FROM cancelamentos GROUP BY competencia ORDER BY competencia"
    )->fetchAll();

    responderJson(200, [
        'sucesso' => true,
        'atualizadoEm' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
        'kpis' => [
            'totalClientes' => (int) $metricas['total_clientes'],
            'totalPets' => (int) $metricas['total_pets'],
            'planosAtivos' => $ativos,
            'planosCancelados' => (int) $metricas['planos_cancelados'],
            'tiposPlanos' => (int) $metricas['tipos_planos'],
        ],
        'projecao' => [
            'mensal' => $projecao,
            'ticketMedio' => $ativos > 0 ? round($projecao / $ativos, 2) : 0,
        ],
        'historico' => array_map(static fn (array $linha): array => [
            'competencia' => $linha['competencia'],
            'quantidade' => (int) $linha['quantidade'],
            'projecao' => (float) $linha['projecao'],
        ], $historico),
        'porPlano' => array_map(static fn (array $linha): array => [
            'nome' => $linha['nome'],
            'quantidade' => (int) $linha['quantidade'],
            'projecao' => (float) $linha['projecao'],
        ], $porPlano),
        'cancelamentos' => array_map(static fn (array $linha): array => [
            'competencia' => $linha['competencia'],
            'quantidade' => (int) $linha['quantidade'],
        ], $cancelamentos),
    ]);
} catch (Throwable $erro) {
    error_log($erro->getMessage());
    responderJson(500, ['sucesso' => false, 'mensagem' => 'Nao foi possivel carregar o dashboard.']);
}
