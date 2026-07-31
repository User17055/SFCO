<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/banco.php';
require_once dirname(__DIR__) . '/config/api.php';

iniciarRespostaApi();
exigirAcessoApi();

try {
    $pdo = conectarBanco();
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
    $assinaturas = array_map(static fn (array $linha): array => [
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
    ], $assinaturasConsulta->fetchAll());

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

    responderJson(200, [
        'sucesso' => true,
        'catalogo' => $catalogo,
        'assinaturas' => $assinaturas,
        'cancelamentosImportados' => $cancelamentos,
    ]);
} catch (Throwable $erro) {
    error_log($erro->getMessage());
    responderJson(500, ['sucesso' => false, 'mensagem' => 'Nao foi possivel carregar os planos.']);
}
