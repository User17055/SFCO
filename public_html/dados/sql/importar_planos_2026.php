<?php
declare(strict_types=1);

/**
 * Importa o JSON criado por scripts/extrair_planos_2026.py.
 * Uso: php importar_planos_2026.php caminho/planos_2026_importacao.json
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config/banco.php';

$arquivo = $argv[1] ?? '';
if ($arquivo === '' || !is_file($arquivo)) {
    fwrite(STDERR, "Informe um arquivo JSON de importacao valido.\n");
    exit(1);
}

$conteudo = file_get_contents($arquivo);
$dados = json_decode((string) $conteudo, true, 512, JSON_THROW_ON_ERROR);
$pdo = conectarBanco();
$pdo->setAttribute(PDO::ATTR_TIMEOUT, 30);

function planoId(PDO $pdo, array &$cache, string $nome, float $valorPadrao = 0): int
{
    if (isset($cache[$nome])) {
        return $cache[$nome];
    }
    $consulta = $pdo->prepare('SELECT id FROM planos WHERE nome = ? ORDER BY id LIMIT 1');
    $consulta->execute([$nome]);
    $id = $consulta->fetchColumn();
    if ($id === false) {
        $inserir = $pdo->prepare(
            'INSERT INTO planos (nome, valor, duracao_dias, ativo) VALUES (?, ?, 30, 1)'
        );
        $inserir->execute([$nome, $valorPadrao]);
        $id = $pdo->lastInsertId();
    }
    $cache[$nome] = (int) $id;
    return (int) $id;
}

function importarHistorico(PDO $pdo, array $registros): int
{
    $total = 0;
    foreach (array_chunk($registros, 200) as $lote) {
        $valores = [];
        $parametros = [];
        foreach ($lote as $registro) {
            $valores[] = '(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            array_push(
                $parametros,
                $registro['codigo_externo'], $registro['competencia'],
                $registro['cliente_nome'], $registro['pet_nome'], $registro['plano_nome'],
                $registro['valor_mensal'], $registro['data_inicio'], $registro['data_reajuste'],
                $registro['adicional'], $registro['quantidade_banho'], $registro['valor_banho'],
                $registro['observacoes'], $registro['origem'], $registro['linha_origem']
            );
        }
        $sql = 'INSERT INTO historico_planos
            (codigo_externo, competencia, cliente_nome, pet_nome, plano_nome,
             valor_mensal, data_inicio, data_reajuste, adicional, quantidade_banho,
             valor_banho, observacoes, origem, linha_origem) VALUES '
            . implode(', ', $valores)
            . ' ON DUPLICATE KEY UPDATE
                competencia = VALUES(competencia), cliente_nome = VALUES(cliente_nome),
                pet_nome = VALUES(pet_nome), plano_nome = VALUES(plano_nome),
                valor_mensal = VALUES(valor_mensal), data_inicio = VALUES(data_inicio),
                data_reajuste = VALUES(data_reajuste), adicional = VALUES(adicional),
                quantidade_banho = VALUES(quantidade_banho), valor_banho = VALUES(valor_banho),
                observacoes = VALUES(observacoes), origem = VALUES(origem),
                linha_origem = VALUES(linha_origem)';
        $pdo->prepare($sql)->execute($parametros);
        $total += count($lote);
    }
    return $total;
}

function importarCancelamentos(PDO $pdo, array $registros): int
{
    $total = 0;
    foreach (array_chunk($registros, 200) as $lote) {
        $valores = [];
        $parametros = [];
        foreach ($lote as $registro) {
            $valores[] = '(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            array_push(
                $parametros,
                $registro['codigo_externo'], $registro['cliente_nome'], $registro['plano_nome'],
                $registro['competencia'], $registro['motivo'], $registro['tentativa_recuperacao'],
                $registro['valor'], $registro['quantidade'], $registro['origem'],
                $registro['linha_origem']
            );
        }
        $sql = 'INSERT INTO cancelamentos
            (codigo_externo, cliente_nome, plano_nome, competencia, motivo,
             tentativa_recuperacao, valor, quantidade, origem, linha_origem) VALUES '
            . implode(', ', $valores)
            . ' ON DUPLICATE KEY UPDATE
                cliente_nome = VALUES(cliente_nome), plano_nome = VALUES(plano_nome),
                competencia = VALUES(competencia), motivo = VALUES(motivo),
                tentativa_recuperacao = VALUES(tentativa_recuperacao), valor = VALUES(valor),
                quantidade = VALUES(quantidade), origem = VALUES(origem),
                linha_origem = VALUES(linha_origem)';
        $pdo->prepare($sql)->execute($parametros);
        $total += count($lote);
    }
    return $total;
}

$contadores = [
    'historico' => 0,
    'cancelamentos' => 0,
    'clientes' => 0,
    'pets' => 0,
    'assinaturas' => 0,
];

try {
    $pdo->beginTransaction();

    $catalogoValores = [];
    foreach ($dados['catalogo'] as $plano) {
        $catalogoValores[$plano['nome']] = (float) $plano['valor_padrao'];
    }

    $cachePlanos = [];
    foreach ($dados['catalogo'] as $plano) {
        $id = planoId($pdo, $cachePlanos, $plano['nome'], (float) $plano['valor_padrao']);
        $atualizar = $pdo->prepare('UPDATE planos SET valor = ?, ativo = 1 WHERE id = ?');
        $atualizar->execute([(float) $plano['valor_padrao'], $id]);
    }
    $pdo->exec("UPDATE planos SET ativo = 0 WHERE nome IN ('Bronze', 'Prata', 'Ouro')");

    /*
     * Cada execucao representa o retrato completo da planilha. As chaves de
     * origem usam a linha do Excel; por isso removemos o retrato anterior antes
     * de gravar o novo. Isso evita sobras quando linhas forem inseridas,
     * removidas ou movidas na planilha atualizada.
     */
    $pdo->prepare('DELETE FROM historico_planos WHERE origem = ?')
        ->execute(['PLANOS 2026 - DETAILS']);
    $pdo->prepare('DELETE FROM cancelamentos WHERE origem = ?')
        ->execute(['PLANOS 2026 - CANCELAMENTOS']);
    $pdo->exec("DELETE FROM assinaturas WHERE origem LIKE 'PLANOS 2026 - %'");

    $contadores['historico'] = importarHistorico($pdo, $dados['historico']);
    $contadores['cancelamentos'] = importarCancelamentos($pdo, $dados['cancelamentos']);

    $cliente = $pdo->prepare(
        'INSERT INTO clientes (codigo_externo, nome, cpf, email, telefone, observacoes)
         VALUES (:codigo, :nome, NULL, NULL, NULL, :observacoes)
         ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), nome = VALUES(nome)'
    );
    $pet = $pdo->prepare(
        "INSERT INTO pets (codigo_externo, cliente_id, nome, especie, observacoes)
         VALUES (:codigo, :cliente_id, :nome, 'Nao informado', :observacoes)
         ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), cliente_id = VALUES(cliente_id),
             nome = VALUES(nome)"
    );
    $assinatura = $pdo->prepare(
        "INSERT INTO assinaturas
            (codigo_externo, pet_id, plano_id, valor_mensal, data_inicio,
             data_reajuste, adicional, observacoes, origem, data_vencimento,
             status, cancelado_em, motivo_cancelamento)
         VALUES
            (:codigo, :pet_id, :plano_id, :valor, :inicio,
             :reajuste, :adicional, :observacoes, :origem, NULL,
             'Ativo', NULL, NULL)
         ON DUPLICATE KEY UPDATE
            pet_id = VALUES(pet_id), plano_id = VALUES(plano_id),
            valor_mensal = VALUES(valor_mensal), data_inicio = VALUES(data_inicio),
            data_reajuste = VALUES(data_reajuste), adicional = VALUES(adicional),
            observacoes = VALUES(observacoes), origem = VALUES(origem),
            status = 'Ativo', cancelado_em = NULL, motivo_cancelamento = NULL"
    );

    $clientesVistos = [];
    $petsVistos = [];
    foreach ($dados['atuais'] as $registro) {
        $cliente->execute([
            'codigo' => $registro['cliente_codigo'],
            'nome' => $registro['cliente_nome'],
            'observacoes' => 'Importado automaticamente da planilha PLANOS 2026.',
        ]);
        $clienteId = (int) $pdo->lastInsertId();
        $clientesVistos[$registro['cliente_codigo']] = true;

        $pet->execute([
            'codigo' => $registro['pet_codigo'],
            'cliente_id' => $clienteId,
            'nome' => $registro['pet_nome'],
            'observacoes' => 'Importado automaticamente da planilha PLANOS 2026.',
        ]);
        $petId = (int) $pdo->lastInsertId();
        $petsVistos[$registro['pet_codigo']] = true;

        $planoId = planoId(
            $pdo,
            $cachePlanos,
            $registro['plano_nome'],
            $catalogoValores[$registro['plano_nome']] ?? 0
        );
        $assinatura->execute([
            'codigo' => $registro['codigo_externo'],
            'pet_id' => $petId,
            'plano_id' => $planoId,
            'valor' => $registro['valor_mensal'],
            'inicio' => $registro['data_inicio'],
            'reajuste' => $registro['data_reajuste'],
            'adicional' => $registro['adicional'],
            'observacoes' => $registro['observacoes'],
            'origem' => $registro['origem'],
        ]);
        $contadores['assinaturas']++;
    }
    $contadores['clientes'] = count($clientesVistos);
    $contadores['pets'] = count($petsVistos);

    /* Remove apenas cadastros importados que ficaram sem assinatura atual. */
    $pdo->exec(
        "DELETE p FROM pets p
          LEFT JOIN assinaturas a ON a.pet_id = p.id
         WHERE p.observacoes = 'Importado automaticamente da planilha PLANOS 2026.'
           AND a.id IS NULL"
    );
    $pdo->exec(
        "DELETE c FROM clientes c
          LEFT JOIN pets p ON p.cliente_id = c.id
         WHERE c.observacoes = 'Importado automaticamente da planilha PLANOS 2026.'
           AND p.id IS NULL"
    );

    $pdo->commit();
    echo json_encode([
        'sucesso' => true,
        'importados' => $contadores,
        'projecao_atual' => $dados['resumo']['projecao_atual']
            ?? $dados['resumo']['projecao_julho']
            ?? null,
        'avisos' => $dados['avisos'] ?? [],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (Throwable $erro) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, $erro->getMessage() . PHP_EOL);
    exit(1);
}
