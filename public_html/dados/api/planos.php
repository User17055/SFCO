<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/banco.php';
require_once dirname(__DIR__) . '/config/api.php';
require_once dirname(__DIR__) . '/config/movimentos_planos.php';

iniciarRespostaApi();
exigirAcessoApi();

try {
    $pdo = conectarBanco();
    date_default_timezone_set('America/Sao_Paulo');
    $movimentos = calcularMovimentosPlanos($pdo);
    $catalogoConsulta = $pdo->query(
        "SELECT pl.id, pl.nome, pl.valor, pl.ativo,
                COUNT(CASE WHEN a.status = 'Ativo' THEN 1 END) AS quantidade_ativa,
                COUNT(CASE WHEN a.status = 'Cancelado' THEN 1 END) AS quantidade_cancelada,
                COALESCE(SUM(CASE WHEN a.status = 'Ativo' THEN a.valor_mensal ELSE 0 END), 0) AS projecao
           FROM planos pl LEFT JOIN assinaturas a ON a.plano_id = pl.id
          GROUP BY pl.id ORDER BY pl.ativo DESC, quantidade_ativa DESC, pl.nome"
    );
    $catalogo = array_map(static fn (array $linha): array => [
        'id' => (int) $linha['id'],
        'nome' => $linha['nome'],
        'valorPadrao' => (float) $linha['valor'],
        'ativo' => (bool) $linha['ativo'],
        'quantidadeAtiva' => (int) $linha['quantidade_ativa'],
        'quantidadeCancelada' => (int) $linha['quantidade_cancelada'],
        'projecao' => (float) $linha['projecao'],
    ], $catalogoConsulta->fetchAll());

    $assinaturasConsulta = $pdo->query(
        "SELECT a.id, a.pet_id, c.id AS cliente_id, c.nome AS cliente, p.nome AS pet,
                pl.id AS plano_id, pl.nome AS plano, a.valor_mensal, a.data_inicio,
                a.data_reajuste, a.adicional, a.observacoes, a.status,
                a.cancelado_em, a.motivo_cancelamento, a.origem
           FROM assinaturas a
           JOIN pets p ON p.id = a.pet_id
           JOIN clientes c ON c.id = p.cliente_id
           JOIN planos pl ON pl.id = a.plano_id
          ORDER BY (a.status = 'Ativo') DESC, c.nome, p.nome, a.id"
    );
    $assinaturas = array_map(
        static function (array $linha) use ($movimentos): array {
            $chave = chaveMovimentoPlano($linha['cliente'], $linha['pet'], $linha['plano']);
            return [
                'id' => (int) $linha['id'],
                'petId' => (int) $linha['pet_id'],
                'clienteId' => (int) $linha['cliente_id'],
                'cliente' => $linha['cliente'],
                'pet' => $linha['pet'],
                'planoId' => (int) $linha['plano_id'],
                'plano' => $linha['plano'],
                'valorMensal' => (float) $linha['valor_mensal'],
                'dataInicio' => $linha['data_inicio'],
                'dataReajuste' => $linha['data_reajuste'],
                'adicional' => $linha['adicional'] ?? '',
                'observacoes' => $linha['observacoes'] ?? '',
                'status' => $linha['status'],
                'canceladoEm' => $linha['cancelado_em'],
                'motivoCancelamento' => $linha['motivo_cancelamento'] ?? '',
                'origem' => $linha['origem'] ?? '',
                'novo' => isset($movimentos['novosAtuais'][$chave]),
            ];
        },
        $assinaturasConsulta->fetchAll()
    );

    $cancelamentosConsulta = $pdo->query(
        "SELECT id, cliente_nome, plano_nome, competencia, motivo,
                tentativa_recuperacao, valor, quantidade
           FROM cancelamentos ORDER BY competencia DESC, id DESC LIMIT 500"
    );
    $cancelamentos = array_map(static fn (array $linha): array => [
        'id' => (int) $linha['id'],
        'cliente' => $linha['cliente_nome'],
        'plano' => $linha['plano_nome'] ?? 'Nao informado',
        'competencia' => $linha['competencia'],
        'motivo' => $linha['motivo'] ?? '',
        'tentativaRecuperacao' => $linha['tentativa_recuperacao'] ?? '',
        'valor' => (float) $linha['valor'],
        'quantidade' => (int) $linha['quantidade'],
    ], $cancelamentosConsulta->fetchAll());

    $meses = [1 => 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    $competencias = array_keys($movimentos['novosPorMes']);
    $competencias = array_slice($competencias, -6);
    $crescimento = [
        'labels' => array_map(
            static function (string $competencia) use ($meses): string {
                [$ano, $mes] = array_map('intval', explode('-', $competencia));
                return $meses[$mes] . '/' . $ano;
            },
            $competencias
        ),
        'novos' => array_map(
            static fn (string $competencia): int => $movimentos['novosPorMes'][$competencia] ?? 0,
            $competencias
        ),
        'cancelados' => array_map(
            static fn (string $competencia): int => $movimentos['canceladosPorMes'][$competencia] ?? 0,
            $competencias
        ),
    ];

    $normalizarNomePlano = static function (string $nome): string {
        $nome = mb_strtoupper(trim($nome));
        return preg_replace('/\s+/u', ' ', $nome) ?? $nome;
    };
    $aliasesCancelamentos = [
        'VIP' => 'PLANO VIP',
        '3 SUPER ECONOMICO FELINO' => 'PLANO ECONOMICO FELINO',
        'SUPER ECONOMICO+2 BANHOS' => 'SUPER ECONOMICO + BANHOS',
    ];
    $competenciasCanceladasNoSistema = $pdo->query(
        "SELECT DISTINCT DATE_FORMAT(cancelado_em, '%Y-%m')
           FROM assinaturas
          WHERE status = 'Cancelado' AND cancelado_em IS NOT NULL"
    )->fetchAll(PDO::FETCH_COLUMN);
    $todasCompetencias = array_values(array_unique(array_merge(
        array_keys($movimentos['novosPorMes']),
        array_keys($movimentos['canceladosPorMes']),
        $competenciasCanceladasNoSistema
    )));
    sort($todasCompetencias);
    $rotulosCompetencias = array_map(
        static function (string $competencia) use ($meses): string {
            [$ano, $mes] = array_map('intval', explode('-', $competencia));
            return $meses[$mes] . '/' . $ano;
        },
        $todasCompetencias
    );

    $relatorios = [];
    foreach ($catalogo as $plano) {
        if (!$plano['ativo'] && $plano['quantidadeAtiva'] === 0) {
            continue;
        }
        $chave = $normalizarNomePlano($plano['nome']);
        $relatorios[$chave] = [
            'nome' => $plano['nome'],
            'valorPlano' => $plano['valorPadrao'],
            'quantidadeAtual' => $plano['quantidadeAtiva'],
            'projecaoAtual' => $plano['projecao'],
            'quantidades' => array_fill_keys($todasCompetencias, 0),
            'valoresMensais' => array_fill_keys($todasCompetencias, 0.0),
            'novos' => array_fill_keys($todasCompetencias, 0),
            'cancelados' => array_fill_keys($todasCompetencias, 0),
            'valoresCancelados' => array_fill_keys($todasCompetencias, 0.0),
            'motivosCancelamentos' => [],
        ];
        $relatorios[$chave]['quantidades'][$movimentos['competenciaAtual']] = $plano['quantidadeAtiva'];
        $relatorios[$chave]['valoresMensais'][$movimentos['competenciaAtual']] = $plano['projecao'];
    }

    $historicoPorPlano = $pdo->query(
        "SELECT DATE_FORMAT(competencia, '%Y-%m') AS competencia,
                plano_nome, COUNT(*) AS quantidade,
                ROUND(SUM(valor_mensal), 2) AS valor_mensal
           FROM historico_planos
          GROUP BY competencia, plano_nome
          ORDER BY competencia, plano_nome"
    );
    foreach ($historicoPorPlano->fetchAll() as $linha) {
        $chave = $normalizarNomePlano($linha['plano_nome']);
        if (!isset($relatorios[$chave])) {
            $relatorios[$chave] = [
                'nome' => $linha['plano_nome'],
                'valorPlano' => 0.0,
                'quantidadeAtual' => 0,
                'projecaoAtual' => 0.0,
                'quantidades' => array_fill_keys($todasCompetencias, 0),
                'valoresMensais' => array_fill_keys($todasCompetencias, 0.0),
                'novos' => array_fill_keys($todasCompetencias, 0),
                'cancelados' => array_fill_keys($todasCompetencias, 0),
                'valoresCancelados' => array_fill_keys($todasCompetencias, 0.0),
                'motivosCancelamentos' => [],
            ];
        }
        $relatorios[$chave]['quantidades'][$linha['competencia']] = (int) $linha['quantidade'];
        $relatorios[$chave]['valoresMensais'][$linha['competencia']] = (float) $linha['valor_mensal'];
    }

    /* Novos contratos por plano: data de inicio dentro da competencia exibida. */
    $historicoNovos = $pdo->query(
        "SELECT DATE_FORMAT(competencia, '%Y-%m') AS competencia,
                plano_nome
           FROM historico_planos
          WHERE data_inicio IS NOT NULL
            AND DATE_FORMAT(data_inicio, '%Y-%m') = DATE_FORMAT(competencia, '%Y-%m')
          ORDER BY competencia, id"
    );
    foreach ($historicoNovos->fetchAll() as $linha) {
        $chavePlano = $normalizarNomePlano($linha['plano_nome']);
        if (isset($relatorios[$chavePlano])) {
            $relatorios[$chavePlano]['novos'][$linha['competencia']]++;
        }
    }
    foreach ($assinaturas as $assinatura) {
        if ($assinatura['status'] !== 'Ativo'
            || substr((string) $assinatura['dataInicio'], 0, 7) !== $movimentos['competenciaAtual']) {
            continue;
        }
        $chavePlano = $normalizarNomePlano($assinatura['plano']);
        if (isset($relatorios[$chavePlano])) {
            $relatorios[$chavePlano]['novos'][$movimentos['competenciaAtual']]++;
        }
    }

    $cancelamentosPorPlano = $pdo->query(
        "SELECT DATE_FORMAT(competencia, '%Y-%m') AS competencia,
                plano_nome, SUM(quantidade) AS quantidade,
                ROUND(SUM(valor), 2) AS valor
           FROM cancelamentos
          WHERE plano_nome IS NOT NULL AND TRIM(plano_nome) <> ''
          GROUP BY competencia, plano_nome
          ORDER BY competencia, plano_nome"
    );
    foreach ($cancelamentosPorPlano->fetchAll() as $linha) {
        $chave = $normalizarNomePlano($linha['plano_nome']);
        $chave = $aliasesCancelamentos[$chave] ?? $chave;
        if (!isset($relatorios[$chave])) {
            $relatorios[$chave] = [
                'nome' => $linha['plano_nome'],
                'valorPlano' => 0.0,
                'quantidadeAtual' => 0,
                'projecaoAtual' => 0.0,
                'quantidades' => array_fill_keys($todasCompetencias, 0),
                'valoresMensais' => array_fill_keys($todasCompetencias, 0.0),
                'novos' => array_fill_keys($todasCompetencias, 0),
                'cancelados' => array_fill_keys($todasCompetencias, 0),
                'valoresCancelados' => array_fill_keys($todasCompetencias, 0.0),
                'motivosCancelamentos' => [],
            ];
        }
        $relatorios[$chave]['cancelados'][$linha['competencia']] = (int) $linha['quantidade'];
        $relatorios[$chave]['valoresCancelados'][$linha['competencia']] = (float) $linha['valor'];
    }

    foreach ($cancelamentos as $cancelamento) {
        if ($cancelamento['plano'] === 'Nao informado') continue;
        $chave = $normalizarNomePlano($cancelamento['plano']);
        $chave = $aliasesCancelamentos[$chave] ?? $chave;
        if (!isset($relatorios[$chave])) continue;
        $relatorios[$chave]['motivosCancelamentos'][] = [
            'cliente' => $cancelamento['cliente'],
            'pet' => '',
            'competencia' => $cancelamento['competencia'],
            'dataCancelamento' => null,
            'motivo' => $cancelamento['motivo'],
            'tentativaRecuperacao' => $cancelamento['tentativaRecuperacao'],
            'quantidade' => $cancelamento['quantidade'],
            'valor' => $cancelamento['valor'],
        ];
    }

    $cancelamentosImportadosVistos = [];
    foreach ($cancelamentos as $cancelamento) {
        $plano = $normalizarNomePlano($cancelamento['plano']);
        $plano = $aliasesCancelamentos[$plano] ?? $plano;
        $mes = substr((string) $cancelamento['competencia'], 0, 7);
        $cancelamentosImportadosVistos[mb_strtoupper(trim($cancelamento['cliente'])) . '|' . $plano . '|' . $mes] = true;
    }
    foreach ($assinaturas as $assinatura) {
        if ($assinatura['status'] !== 'Cancelado') continue;
        $chave = $normalizarNomePlano($assinatura['plano']);
        if (!isset($relatorios[$chave])) continue;
        $competencia = $assinatura['canceladoEm']
            ? substr((string) $assinatura['canceladoEm'], 0, 7)
            : $movimentos['competenciaAtual'];
        $chaveDuplicidade = mb_strtoupper(trim($assinatura['cliente'])) . '|' . $chave . '|' . $competencia;
        if (isset($cancelamentosImportadosVistos[$chaveDuplicidade])) continue;
        $relatorios[$chave]['cancelados'][$competencia]++;
        $relatorios[$chave]['valoresCancelados'][$competencia] += (float) $assinatura['valorMensal'];
        $relatorios[$chave]['motivosCancelamentos'][] = [
            'cliente' => $assinatura['cliente'],
            'pet' => $assinatura['pet'],
            'competencia' => $competencia . '-01',
            'dataCancelamento' => $assinatura['canceladoEm'],
            'motivo' => $assinatura['motivoCancelamento'],
            'tentativaRecuperacao' => '',
            'quantidade' => 1,
            'valor' => $assinatura['valorMensal'],
        ];
    }

    $relatoriosPlanos = array_map(
        static function (array $relatorio) use ($todasCompetencias, $rotulosCompetencias, $movimentos): array {
            return [
                'nome' => $relatorio['nome'],
                'valorPlano' => (float) $relatorio['valorPlano'],
                'quantidadeAtual' => (int) $relatorio['quantidadeAtual'],
                'projecaoAtual' => (float) $relatorio['projecaoAtual'],
                'totalCancelados' => array_sum($relatorio['cancelados']),
                'valorTotalCancelado' => array_sum($relatorio['valoresCancelados']),
                'novosMesAtual' => (int) ($relatorio['novos'][$movimentos['competenciaAtual']] ?? 0),
                'labels' => $rotulosCompetencias,
                'quantidades' => array_map(
                    static fn (string $competencia): int => $relatorio['quantidades'][$competencia] ?? 0,
                    $todasCompetencias
                ),
                'cancelados' => array_map(
                    static fn (string $competencia): int => $relatorio['cancelados'][$competencia] ?? 0,
                    $todasCompetencias
                ),
                'novos' => array_map(
                    static fn (string $competencia): int => $relatorio['novos'][$competencia] ?? 0,
                    $todasCompetencias
                ),
                'valoresMensais' => array_map(
                    static fn (string $competencia): float => (float) ($relatorio['valoresMensais'][$competencia] ?? 0),
                    $todasCompetencias
                ),
                'valoresCancelados' => array_map(
                    static fn (string $competencia): float => (float) ($relatorio['valoresCancelados'][$competencia] ?? 0),
                    $todasCompetencias
                ),
                'motivosCancelamentos' => $relatorio['motivosCancelamentos'],
            ];
        },
        array_values($relatorios)
    );
    usort(
        $relatoriosPlanos,
        static fn (array $a, array $b): int => $b['quantidadeAtual'] <=> $a['quantidadeAtual']
            ?: strcasecmp($a['nome'], $b['nome'])
    );

    responderJson(200, [
        'sucesso' => true,
        'competenciaAtual' => $movimentos['competenciaAtual'],
        'competenciasDisponiveis' => $todasCompetencias,
        'catalogo' => $catalogo,
        'assinaturas' => $assinaturas,
        'cancelamentosImportados' => $cancelamentos,
        'crescimento' => $crescimento,
        'relatoriosPlanos' => $relatoriosPlanos,
    ]);
} catch (Throwable $erro) {
    error_log($erro->getMessage());
    responderJson(500, ['sucesso' => false, 'mensagem' => 'Nao foi possivel carregar os planos.']);
}
