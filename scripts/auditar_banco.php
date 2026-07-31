<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/public_html/dados/config/banco.php';

$pdo = conectarBanco();
$consultas = [
    'clientes' => 'SELECT COUNT(*) FROM clientes',
    'pets' => 'SELECT COUNT(*) FROM pets',
    'assinaturas_ativas' => "SELECT COUNT(*) FROM assinaturas WHERE status = 'Ativo'",
    'projecao_ativa' => "SELECT ROUND(COALESCE(SUM(valor_mensal), 0), 2) FROM assinaturas WHERE status = 'Ativo'",
    'historico' => 'SELECT COUNT(*) FROM historico_planos',
    'cancelamentos' => 'SELECT COALESCE(SUM(quantidade), 0) FROM cancelamentos',
    'tipos_planos_ativos' => 'SELECT COUNT(*) FROM planos WHERE ativo = 1',
];

$resultado = [];
foreach ($consultas as $nome => $sql) {
    $resultado[$nome] = $pdo->query($sql)->fetchColumn();
}
$resultado['historico_mensal'] = $pdo->query(
    "SELECT DATE_FORMAT(competencia, '%Y-%m') AS competencia,
            COUNT(*) AS quantidade, ROUND(SUM(valor_mensal), 2) AS projecao
       FROM historico_planos GROUP BY competencia ORDER BY competencia"
)->fetchAll();
$resultado['cancelamentos_mensais'] = $pdo->query(
    "SELECT DATE_FORMAT(competencia, '%Y-%m') AS competencia,
            SUM(quantidade) AS quantidade
       FROM cancelamentos GROUP BY competencia ORDER BY competencia"
)->fetchAll();
$resultado['cadastros_fora_da_planilha'] = $pdo->query(
    "SELECT c.id AS cliente_id, c.nome AS cliente, p.id AS pet_id, p.nome AS pet,
            a.id AS assinatura_id, pl.nome AS plano, a.valor_mensal, a.origem
       FROM clientes c
       LEFT JOIN pets p ON p.cliente_id = c.id
       LEFT JOIN assinaturas a ON a.pet_id = p.id
       LEFT JOIN planos pl ON pl.id = a.plano_id
      WHERE c.codigo_externo IS NULL"
)->fetchAll();
$resultado['usuarios'] = $pdo->query(
    "SELECT email, trocar_senha FROM usuarios
      WHERE email IN ('marco@saofrancisco.vet.br', 'sac@saofrancisco.vet.br')
      ORDER BY email"
)->fetchAll();

echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
