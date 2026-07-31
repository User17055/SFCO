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
        "SELECT id, cliente_nome, plano_nome, competencia, motivo, valor, quantidade
           FROM cancelamentos ORDER BY competencia DESC, id DESC LIMIT 500"
    );
    $cancelamentos = array_map(static fn (array $linha): array => [
        'id' => (int) $linha['id'],
        'cliente' => $linha['cliente_nome'],
        'plano' => $linha['plano_nome'] ?? 'Nao informado',
        'competencia' => $linha['competencia'],
        'motivo' => $linha['motivo'] ?? '',
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

    responderJson(200, [
        'sucesso' => true,
        'catalogo' => $catalogo,
        'assinaturas' => $assinaturas,
        'cancelamentosImportados' => $cancelamentos,
        'crescimento' => $crescimento,
    ]);
} catch (Throwable $erro) {
    error_log($erro->getMessage());
    responderJson(500, ['sucesso' => false, 'mensagem' => 'Nao foi possivel carregar os planos.']);
}
