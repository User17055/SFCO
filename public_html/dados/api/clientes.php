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
            c.id, c.nome, c.telefone, c.cpf, c.email, c.created_at,
            COUNT(DISTINCT p.id) AS total_pets,
            COALESCE(
              GROUP_CONCAT(DISTINCT pl.nome ORDER BY pl.nome SEPARATOR ', '),
              'Sem plano'
            ) AS planos,
            CASE
              WHEN SUM(CASE WHEN a.status <> 'Cancelado'
                         AND CURDATE() BETWEEN a.data_inicio AND a.data_vencimento
                       THEN 1 ELSE 0 END) > 0 THEN 'Ativo'
              WHEN SUM(CASE WHEN a.status <> 'Cancelado' AND a.data_inicio > CURDATE()
                       THEN 1 ELSE 0 END) > 0 THEN 'Pendente'
              WHEN SUM(CASE WHEN a.status <> 'Cancelado' AND a.data_vencimento < CURDATE()
                       THEN 1 ELSE 0 END) > 0 THEN 'Vencido'
              WHEN SUM(CASE WHEN a.status = 'Cancelado' THEN 1 ELSE 0 END) > 0
                       THEN 'Cancelado'
              WHEN COUNT(a.id) > 0 THEN 'Sem data'
              ELSE 'Sem plano'
            END AS status
         FROM clientes c
         LEFT JOIN pets p ON p.cliente_id = c.id
         LEFT JOIN assinaturas a ON a.pet_id = p.id
         LEFT JOIN planos pl ON pl.id = a.plano_id
         GROUP BY c.id, c.nome, c.telefone, c.cpf, c.email, c.created_at
         ORDER BY c.created_at DESC, c.id DESC"
    );
    $clientes = array_map(
        static fn (array $linha): array => [
            'id' => (int) $linha['id'],
            'nome' => $linha['nome'],
            'telefone' => $linha['telefone'] ?? '',
            'cpf' => $linha['cpf'],
            'email' => $linha['email'] ?? '',
            'totalPets' => (int) $linha['total_pets'],
            'planos' => $linha['planos'],
            'status' => $linha['status'],
            'criadoEm' => $linha['created_at'],
        ],
        $consulta->fetchAll()
    );

    $inicio = (new DateTimeImmutable('first day of this month'))->modify('-5 months');
    $fim = (new DateTimeImmutable('first day of next month'));
    $novosConsulta = $pdo->prepare(
        "SELECT DATE_FORMAT(created_at, '%Y-%m') AS mes, COUNT(*) AS total
           FROM clientes
          WHERE created_at >= ? AND created_at < ?
          GROUP BY DATE_FORMAT(created_at, '%Y-%m')"
    );
    $novosConsulta->execute([$inicio->format('Y-m-d'), $fim->format('Y-m-d')]);
    $novosPorMes = [];
    foreach ($novosConsulta->fetchAll() as $linha) {
        $novosPorMes[$linha['mes']] = (int) $linha['total'];
    }

    $mesesPt = [1 => 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    $labels = [];
    $novos = [];
    for ($indice = 0; $indice < 6; $indice++) {
        $mes = $inicio->modify("+{$indice} months");
        $labels[] = $mesesPt[(int) $mes->format('n')];
        $novos[] = $novosPorMes[$mes->format('Y-m')] ?? 0;
    }

    $statusValores = ['Ativo' => 0, 'Pendente' => 0, 'Vencido' => 0, 'Cancelado' => 0];
    $statusConsulta = $pdo->query(
        "SELECT status, data_inicio, data_vencimento FROM assinaturas"
    );
    $hoje = date('Y-m-d');
    foreach ($statusConsulta->fetchAll() as $assinatura) {
        if ($assinatura['status'] === 'Cancelado') {
            $statusValores['Cancelado']++;
        } elseif ($assinatura['data_inicio'] > $hoje) {
            $statusValores['Pendente']++;
        } elseif ($assinatura['data_vencimento'] < $hoje) {
            $statusValores['Vencido']++;
        } else {
            $statusValores['Ativo']++;
        }
    }

    responderJson(200, [
        'sucesso' => true,
        'clientes' => $clientes,
        'series' => [
            'labels' => $labels,
            'novosClientes' => $novos,
            'statusPlanos' => [
                'labels' => array_keys($statusValores),
                'valores' => array_values($statusValores),
                'cores' => ['#16a34a', '#d97706', '#dc2626', '#94a3b8'],
            ],
        ],
    ]);
} catch (Throwable $erro) {
    error_log($erro->getMessage());
    responderJson(500, [
        'sucesso' => false,
        'mensagem' => 'Nao foi possivel carregar os clientes.',
    ]);
}
