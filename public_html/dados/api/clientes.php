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
        'status' => (int) $linha['planos_ativos'] > 0
            ? 'Ativo'
            : ((int) $linha['planos_cancelados'] > 0 ? 'Cancelado' : 'Sem plano'),
        'projecao' => (float) $linha['projecao'],
        'planos' => $linha['planos'] ?: 'Sem plano ativo',
        'criadoEm' => $linha['created_at'],
    ], $consulta->fetchAll());

    /* Usa a primeira aparicao do tutor no historico real importado. */
    $tutoresPorMes = [];
    $historico = $pdo->query(
        "SELECT DATE_FORMAT(competencia, '%Y-%m') AS competencia, cliente_nome
           FROM historico_planos
          WHERE cliente_nome IS NOT NULL AND TRIM(cliente_nome) <> ''
          GROUP BY competencia, cliente_nome
          ORDER BY competencia"
    );
    foreach ($historico->fetchAll() as $linha) {
        $nome = mb_strtoupper(trim((string) $linha['cliente_nome']));
        $nome = preg_replace('/\\s+/u', ' ', $nome) ?? $nome;
        $tutoresPorMes[$linha['competencia']][$nome] = true;
    }

    ksort($tutoresPorMes);
    $tutoresVistos = [];
    $novosPorMes = [];
    foreach ($tutoresPorMes as $competencia => $tutores) {
        $quantidade = 0;
        foreach ($tutores as $nome => $_) {
            if (!isset($tutoresVistos[$nome])) {
                $quantidade++;
                $tutoresVistos[$nome] = true;
            }
        }
        $novosPorMes[$competencia] = $quantidade;
    }

    $novosPorMes = array_slice($novosPorMes, -6, null, true);
    $nomesMeses = [
        1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun',
        7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez',
    ];
    $labels = [];
    foreach (array_keys($novosPorMes) as $competencia) {
        [$ano, $mes] = array_map('intval', explode('-', $competencia));
        $labels[] = $nomesMeses[$mes] . '/' . substr((string) $ano, -2);
    }

    $totalAtivos = (int) $pdo->query(
        "SELECT COUNT(*) FROM assinaturas WHERE status = 'Ativo'"
    )->fetchColumn();
    $totalCanceladosAtuais = (int) $pdo->query(
        "SELECT COUNT(*) FROM assinaturas WHERE status = 'Cancelado'"
    )->fetchColumn();
    $totalCanceladosImportados = (int) $pdo->query(
        'SELECT COALESCE(SUM(quantidade), 0) FROM cancelamentos'
    )->fetchColumn();

    responderJson(200, [
        'sucesso' => true,
        'clientes' => $clientes,
        'series' => [
            'labels' => $labels,
            'novosClientes' => array_values($novosPorMes),
            'statusPlanos' => [
                'labels' => ['Ativos', 'Cancelados'],
                'valores' => [$totalAtivos, $totalCanceladosAtuais + $totalCanceladosImportados],
                'cores' => ['#16a34a', '#dc2626'],
            ],
        ],
    ]);
} catch (Throwable $erro) {
    error_log($erro->getMessage());
    responderJson(500, ['sucesso' => false, 'mensagem' => 'Nao foi possivel carregar os clientes.']);
}
