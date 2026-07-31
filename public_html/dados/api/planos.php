<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/banco.php';
require_once dirname(__DIR__) . '/config/api.php';

iniciarRespostaApi();
exigirAcessoApi();
date_default_timezone_set('America/Sao_Paulo');

try {
    $pdo = conectarBanco();
    $consulta = $pdo->query(
        "SELECT
            a.id, c.nome AS cliente, p.nome AS pet, pl.nome AS plano,
            pl.valor, a.data_inicio, a.data_vencimento,
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
            'cliente' => $linha['cliente'],
            'pet' => $linha['pet'],
            'plano' => $linha['plano'],
            'valor' => (float) $linha['valor'],
            'dataInicio' => $linha['data_inicio'],
            'dataVencimento' => $linha['data_vencimento'],
            'status' => $linha['status'],
        ],
        $consulta->fetchAll()
    );

    $inicio = (new DateTimeImmutable('first day of this month'))->modify('-5 months');
    $mesesPt = [1 => 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    $labels = [];
    $valores = [];
    for ($indice = 0; $indice < 6; $indice++) {
        $mes = $inicio->modify("+{$indice} months");
        $proximo = $mes->modify('+1 month');
        $labels[] = $mesesPt[(int) $mes->format('n')];
        $total = 0;
        foreach ($assinaturas as $assinatura) {
            if (
                $assinatura['dataInicio'] !== null
                && $assinatura['dataInicio'] >= $mes->format('Y-m-d')
                && $assinatura['dataInicio'] < $proximo->format('Y-m-d')
            ) {
                $total++;
            }
        }
        $valores[] = $total;
    }

    responderJson(200, [
        'sucesso' => true,
        'assinaturas' => $assinaturas,
        'serie' => ['labels' => $labels, 'valores' => $valores],
    ]);
} catch (Throwable $erro) {
    error_log($erro->getMessage());
    responderJson(500, [
        'sucesso' => false,
        'mensagem' => 'Nao foi possivel carregar os planos.',
    ]);
}
