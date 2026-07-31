<?php
declare(strict_types=1);

function chaveMovimentoPlano(string $cliente, string $pet, string $plano): string
{
    $normalizar = static function (string $valor): string {
        $valor = mb_strtoupper(trim($valor));
        return preg_replace('/\s+/u', ' ', $valor) ?? $valor;
    };

    return hash('sha256', implode('|', [
        $normalizar($cliente),
        $normalizar($pet),
        $normalizar($plano),
    ]));
}

/**
 * Calcula a primeira aparicao de cada combinacao tutor + pet + plano.
 * A competencia atual usa as assinaturas ativas; as anteriores usam o historico.
 */
function calcularMovimentosPlanos(PDO $pdo): array
{
    $competenciaAtual = date('Y-m');
    $chavesPorMes = [];

    $historico = $pdo->query(
        "SELECT DATE_FORMAT(competencia, '%Y-%m') AS competencia,
                cliente_nome, pet_nome, plano_nome
           FROM historico_planos
          ORDER BY competencia, id"
    );
    foreach ($historico->fetchAll() as $linha) {
        $chave = chaveMovimentoPlano(
            $linha['cliente_nome'],
            $linha['pet_nome'],
            $linha['plano_nome']
        );
        $chavesPorMes[$linha['competencia']][$chave] = true;
    }

    $atuais = $pdo->query(
        "SELECT c.nome AS cliente, p.nome AS pet, pl.nome AS plano
           FROM assinaturas a
           JOIN pets p ON p.id = a.pet_id
           JOIN clientes c ON c.id = p.cliente_id
           JOIN planos pl ON pl.id = a.plano_id
          WHERE a.status = 'Ativo'"
    );
    foreach ($atuais->fetchAll() as $linha) {
        $chave = chaveMovimentoPlano($linha['cliente'], $linha['pet'], $linha['plano']);
        $chavesPorMes[$competenciaAtual][$chave] = true;
    }

    ksort($chavesPorMes);
    $vistos = [];
    $novosPorMes = [];
    $novosAtuais = [];
    foreach ($chavesPorMes as $competencia => $chaves) {
        $novos = [];
        foreach ($chaves as $chave => $_) {
            if (!isset($vistos[$chave])) {
                $novos[$chave] = true;
            }
        }
        $novosPorMes[$competencia] = count($novos);
        if ($competencia === $competenciaAtual) {
            $novosAtuais = $novos;
        }
        $vistos += $chaves;
    }

    $canceladosPorMes = [];
    $cancelamentos = $pdo->query(
        "SELECT DATE_FORMAT(competencia, '%Y-%m') AS competencia,
                SUM(quantidade) AS quantidade
           FROM cancelamentos
          GROUP BY competencia ORDER BY competencia"
    );
    foreach ($cancelamentos->fetchAll() as $linha) {
        $canceladosPorMes[$linha['competencia']] = (int) $linha['quantidade'];
    }

    return [
        'competenciaAtual' => $competenciaAtual,
        'novosPorMes' => $novosPorMes,
        'novosAtuais' => $novosAtuais,
        'canceladosPorMes' => $canceladosPorMes,
    ];
}
