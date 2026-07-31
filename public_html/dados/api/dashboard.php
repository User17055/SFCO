<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

require_once dirname(__DIR__) . '/config/banco.php';
require_once dirname(__DIR__) . '/config/sessao.php';

function responderDashboard(int $status, array $dados): never
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function iniciais(string $nome): string
{
    $partes = preg_split('/\s+/', trim($nome)) ?: [];
    $partes = array_values(array_filter($partes));
    $selecionadas = array_slice($partes, 0, 2);

    return implode('', array_map(
        static fn (string $parte): string => mb_strtoupper(mb_substr($parte, 0, 1)),
        $selecionadas
    ));
}

iniciarSessao();

if (!isset($_SESSION['usuario_id'])) {
    responderDashboard(401, [
        'sucesso' => false,
        'mensagem' => 'Sessao expirada. Entre novamente.',
    ]);
}
if (!empty($_SESSION['trocar_senha'])) {
    responderDashboard(403, [
        'sucesso' => false,
        'mensagem' => 'Troque sua senha antes de acessar o painel.',
        'trocarSenha' => true,
    ]);
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    responderDashboard(405, [
        'sucesso' => false,
        'mensagem' => 'Metodo nao permitido.',
    ]);
}

date_default_timezone_set('America/Sao_Paulo');
$hoje = new DateTimeImmutable('today');
$hojeSql = $hoje->format('Y-m-d');
$mesesPt = [1 => 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

try {
    $pdo = conectarBanco();

    $usuarioConsulta = $pdo->prepare(
        'SELECT nome, email FROM usuarios WHERE id = :id LIMIT 1'
    );
    $usuarioConsulta->execute(['id' => (int) $_SESSION['usuario_id']]);
    $usuario = $usuarioConsulta->fetch();
    if (!$usuario) {
        $_SESSION = [];
        session_destroy();
        responderDashboard(401, [
            'sucesso' => false,
            'mensagem' => 'Usuario nao encontrado.',
        ]);
    }

    $metricasConsulta = $pdo->prepare(
        "SELECT
            (SELECT COUNT(*) FROM clientes) AS total_clientes,
            (SELECT COUNT(*) FROM pets) AS total_pets,
            COALESCE(SUM(CASE
              WHEN a.status <> 'Cancelado'
               AND a.data_inicio <= calendario.hoje
               AND a.data_vencimento >= calendario.hoje THEN 1 ELSE 0 END), 0) AS ativos,
            COALESCE(SUM(CASE
              WHEN a.status <> 'Cancelado'
               AND a.data_inicio <= calendario.hoje
               AND a.data_vencimento BETWEEN calendario.hoje
                   AND DATE_ADD(calendario.hoje, INTERVAL 7 DAY) THEN 1 ELSE 0 END), 0) AS vencendo,
            COALESCE(SUM(CASE
              WHEN a.status <> 'Cancelado'
               AND a.data_vencimento < calendario.hoje THEN 1 ELSE 0 END), 0) AS vencidos,
            COALESCE(SUM(CASE
              WHEN a.status <> 'Cancelado'
               AND a.data_inicio > calendario.hoje THEN 1 ELSE 0 END), 0) AS pendentes,
            COALESCE(SUM(CASE WHEN a.status = 'Cancelado' THEN 1 ELSE 0 END), 0) AS cancelados,
            COALESCE(SUM(CASE
              WHEN a.status <> 'Cancelado'
               AND a.data_inicio <= calendario.hoje
               AND a.data_vencimento >= calendario.hoje THEN pl.valor ELSE 0 END), 0) AS receita_ativa,
            COALESCE(SUM(CASE
              WHEN a.status <> 'Cancelado'
               AND a.data_inicio > calendario.hoje THEN pl.valor ELSE 0 END), 0) AS a_receber,
            COALESCE(SUM(CASE
              WHEN a.status <> 'Cancelado'
               AND a.data_vencimento < calendario.hoje THEN pl.valor ELSE 0 END), 0) AS prejuizo,
            COALESCE(SUM(CASE
              WHEN a.status = 'Cancelado' THEN pl.valor ELSE 0 END), 0) AS perdas
         FROM (SELECT CAST(? AS DATE) AS hoje) calendario
         LEFT JOIN assinaturas a ON 1 = 1
         LEFT JOIN planos pl ON pl.id = a.plano_id"
    );
    $metricasConsulta->execute([$hojeSql]);
    $metricas = $metricasConsulta->fetch();

    $totalClientes = (int) $metricas['total_clientes'];
    $totalPets = (int) $metricas['total_pets'];
    $ativos = (int) $metricas['ativos'];
    $vencendo = (int) $metricas['vencendo'];
    $vencidos = (int) $metricas['vencidos'];
    $pendentes = (int) $metricas['pendentes'];
    $cancelados = (int) $metricas['cancelados'];
    $receitaAtiva = (float) $metricas['receita_ativa'];
    $aReceber = (float) $metricas['a_receber'];
    $prejuizo = (float) $metricas['prejuizo'];
    $perdas = (float) $metricas['perdas'];

    $labels = [];
    $receitaMensal = [];
    $novosClientes = [];
    $primeiroMes = $hoje->modify('first day of this month')->modify('-5 months');
    $fimPeriodo = $hoje->modify('last day of this month');

    $assinaturasConsulta = $pdo->prepare(
        "SELECT a.data_inicio, a.data_vencimento, pl.valor
           FROM assinaturas a
           JOIN planos pl ON pl.id = a.plano_id
          WHERE a.status <> 'Cancelado'
            AND a.data_inicio <= ?
            AND a.data_vencimento >= ?"
    );
    $assinaturasConsulta->execute([
        $fimPeriodo->format('Y-m-d'),
        $primeiroMes->format('Y-m-d'),
    ]);
    $assinaturasPeriodo = $assinaturasConsulta->fetchAll();

    $clientesConsulta = $pdo->prepare(
        "SELECT DATE_FORMAT(created_at, '%Y-%m') AS mes, COUNT(*) AS total
           FROM clientes
          WHERE created_at >= ? AND created_at < ?
          GROUP BY DATE_FORMAT(created_at, '%Y-%m')"
    );
    $clientesConsulta->execute([
        $primeiroMes->format('Y-m-d'),
        $fimPeriodo->modify('+1 day')->format('Y-m-d'),
    ]);
    $clientesPorMes = [];
    foreach ($clientesConsulta->fetchAll() as $linha) {
        $clientesPorMes[$linha['mes']] = (int) $linha['total'];
    }

    for ($indice = 0; $indice < 6; $indice++) {
        $inicioMes = $primeiroMes->modify("+{$indice} months");
        $inicioProximo = $inicioMes->modify('+1 month');
        $fimMes = $inicioProximo->modify('-1 day');
        $labels[] = $mesesPt[(int) $inicioMes->format('n')];

        $receitaDoMes = 0.0;
        foreach ($assinaturasPeriodo as $assinatura) {
            if (
                $assinatura['data_inicio'] <= $fimMes->format('Y-m-d')
                && $assinatura['data_vencimento'] >= $inicioMes->format('Y-m-d')
            ) {
                $receitaDoMes += (float) $assinatura['valor'];
            }
        }
        $receitaMensal[] = $receitaDoMes;
        $novosClientes[] = $clientesPorMes[$inicioMes->format('Y-m')] ?? 0;
    }

    responderDashboard(200, [
        'sucesso' => true,
        'atualizadoEm' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
        'usuario' => [
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'iniciais' => iniciais($usuario['nome']),
        ],
        'kpis' => [
            'totalClientes' => $totalClientes,
            'totalPets' => $totalPets,
            'planosAtivos' => $ativos,
            'planosVencendo' => $vencendo,
            'planosVencidos' => $vencidos,
        ],
        'financeiro' => [
            'receitaAtiva' => $receitaAtiva,
            'aReceber' => $aReceber,
            'prejuizo' => $prejuizo,
            'perdas' => $perdas,
        ],
        'series' => [
            'labels' => $labels,
            'receitaMensal' => $receitaMensal,
            'novosClientes' => $novosClientes,
            'statusPlanos' => [
                'labels' => ['Ativo', 'Pendente', 'Vencido', 'Cancelado'],
                'valores' => [$ativos, $pendentes, $vencidos, $cancelados],
                'cores' => ['#16a34a', '#d97706', '#dc2626', '#94a3b8'],
            ],
        ],
    ]);
} catch (Throwable $erro) {
    error_log($erro->getMessage());
    responderDashboard(500, [
        'sucesso' => false,
        'mensagem' => 'Nao foi possivel carregar o dashboard.',
    ]);
}
