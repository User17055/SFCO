<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

require_once dirname(__DIR__) . '/config/banco.php';
require_once dirname(__DIR__) . '/config/sessao.php';

function responderLogin(int $status, array $dados): never
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    responderLogin(405, ['sucesso' => false, 'mensagem' => 'Metodo nao permitido.']);
}

try {
    $entrada = json_decode(
        file_get_contents('php://input'),
        true,
        8,
        JSON_THROW_ON_ERROR
    );
} catch (JsonException) {
    responderLogin(400, ['sucesso' => false, 'mensagem' => 'Dados invalidos.']);
}

$email = strtolower(trim((string) ($entrada['email'] ?? '')));
$senha = (string) ($entrada['senha'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '') {
    responderLogin(422, [
        'sucesso' => false,
        'mensagem' => 'Informe e-mail e senha validos.',
    ]);
}

try {
    $pdo = conectarBanco();
    $consulta = $pdo->prepare(
        'SELECT id, nome, email, senha, trocar_senha
           FROM usuarios
          WHERE email = :email
          LIMIT 1'
    );
    $consulta->execute(['email' => $email]);
    $usuario = $consulta->fetch();

    if (!$usuario || !password_verify($senha, $usuario['senha'])) {
        responderLogin(401, [
            'sucesso' => false,
            'mensagem' => 'E-mail ou senha incorretos.',
        ]);
    }

    iniciarSessao();
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = (int) $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['trocar_senha'] = (bool) $usuario['trocar_senha'];

    responderLogin(200, [
        'sucesso' => true,
        'trocarSenha' => (bool) $usuario['trocar_senha'],
        'nome' => $usuario['nome'],
    ]);
} catch (Throwable $erro) {
    error_log($erro->getMessage());
    responderLogin(500, [
        'sucesso' => false,
        'mensagem' => 'Nao foi possivel entrar agora.',
    ]);
}
