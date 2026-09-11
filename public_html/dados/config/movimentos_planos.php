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
 * Calcula os novos pela data de inicio informada na planilha. A competencia
 * atual e o mes imediatamente posterior ao ultimo retrato historico importado.
 */
function calcularMovimentosPlanos(PDO $pdo): array
{
    $ultimaCompetencia = $pdo->query(
        "SELECT DATE_FORMAT(MAX(competencia), '%Y-%m') FROM historico_planos"
    )->fetchColumn();
    $competenciaAtual = $ultimaCompetencia
        ? (new DateTimeImmutable($ultimaCompetencia . '-01'))->modify('+1 month')->format('Y-m')
        : date('Y-m');

    $novosPorMes = [];
    $novosAtuais = [];

    $historico = $pdo->query(
        "SELECT DATE_FORMAT(competencia, '%Y-%m') AS competencia,
                DATE_FORMAT(data_inicio, '%Y-%m') AS competencia_inicio
           FROM historico_planos
          ORDER BY competencia, id"
    );
    foreach ($historico->fetchAll() as $linha) {
        $novosPorMes[$linha['competencia']] ??= 0;
        if ($linha['competencia_inicio'] === $linha['competencia']) {
            $novosPorMes[$linha['competencia']]++;
        }
    }

    $atuais = $pdo->prepare(
        "SELECT c.nome AS cliente, p.nome AS pet, pl.nome AS plano
           FROM assinaturas a
           JOIN pets p ON p.id = a.pet_id
           JOIN clientes c ON c.id = p.cliente_id
           JOIN planos pl ON pl.id = a.plano_id
          WHERE a.status = 'Ativo'
            AND DATE_FORMAT(a.data_inicio, '%Y-%m') = ?"
    );
    $atuais->execute([$competenciaAtual]);
    $novosPorMes[$competenciaAtual] ??= 0;
    foreach ($atuais->fetchAll() as $linha) {
        $chave = chaveMovimentoPlano($linha['cliente'], $linha['pet'], $linha['plano']);
        $novosAtuais[$chave] = true;
        $novosPorMes[$competenciaAtual]++;
    }
    ksort($novosPorMes);

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
