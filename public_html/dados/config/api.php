<?php
declare(strict_types=1);

require_once __DIR__ . '/sessao.php';

function responderJson(int $status, array $dados): never
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function exigirAcessoApi(string $metodo = 'GET'): void
{
    iniciarSessao();

    if (!isset($_SESSION['usuario_id'])) {
        responderJson(401, [
            'sucesso' => false,
            'mensagem' => 'Sessao expirada. Entre novamente.',
        ]);
    }
    if (!empty($_SESSION['trocar_senha'])) {
        responderJson(403, [
            'sucesso' => false,
            'mensagem' => 'Troque sua senha antes de acessar os dados.',
            'trocarSenha' => true,
        ]);
    }
    if ($_SERVER['REQUEST_METHOD'] !== $metodo) {
        header("Allow: {$metodo}");
        responderJson(405, [
            'sucesso' => false,
            'mensagem' => 'Metodo nao permitido.',
        ]);
    }
}

function iniciarRespostaApi(): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
}
