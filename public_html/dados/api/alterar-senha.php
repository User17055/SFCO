<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

require_once dirname(__DIR__) . '/config/banco.php';
require_once dirname(__DIR__) . '/config/sessao.php';

function responderSenha(int $status, array $dados): never
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

iniciarSessao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    responderSenha(405, ['sucesso' => false, 'mensagem' => 'Metodo nao permitido.']);
}
if (!isset($_SESSION['usuario_id'])) {
    responderSenha(401, ['sucesso' => false, 'mensagem' => 'Sessao expirada. Entre novamente.']);
}

try {
    $entrada = json_decode(
        file_get_contents('php://input'),
        true,
        8,
        JSON_THROW_ON_ERROR
    );
} catch (JsonException) {
    responderSenha(400, ['sucesso' => false, 'mensagem' => 'Dados invalidos.']);
}

$novaSenha = (string) ($entrada['novaSenha'] ?? '');
$confirmacao = (string) ($entrada['confirmacao'] ?? '');

if (strlen($novaSenha) < 8) {
    responderSenha(422, [
        'sucesso' => false,
        'mensagem' => 'A nova senha deve possuir pelo menos 8 caracteres.',
    ]);
}
if ($novaSenha !== $confirmacao) {
    responderSenha(422, [
        'sucesso' => false,
        'mensagem' => 'As senhas informadas nao conferem.',
    ]);
}

try {
    $pdo = conectarBanco();
    $alterar = $pdo->prepare(
        'UPDATE usuarios
            SET senha = :senha, trocar_senha = 0
          WHERE id = :id'
    );
    $alterar->execute([
        'senha' => password_hash($novaSenha, PASSWORD_DEFAULT),
        'id' => (int) $_SESSION['usuario_id'],
    ]);

    $_SESSION['trocar_senha'] = false;
    responderSenha(200, [
        'sucesso' => true,
        'mensagem' => 'Senha alterada com sucesso.',
    ]);
} catch (Throwable $erro) {
    error_log($erro->getMessage());
    responderSenha(500, [
        'sucesso' => false,
        'mensagem' => 'Nao foi possivel alterar a senha.',
    ]);
}
