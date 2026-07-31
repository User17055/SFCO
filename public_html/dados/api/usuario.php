<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/banco.php';
require_once dirname(__DIR__) . '/config/api.php';

iniciarRespostaApi();
exigirAcessoApi();

try {
    $pdo = conectarBanco();
    $consulta = $pdo->prepare(
        'SELECT nome, email FROM usuarios WHERE id = :id LIMIT 1'
    );
    $consulta->execute(['id' => (int) $_SESSION['usuario_id']]);
    $usuario = $consulta->fetch();
    if (!$usuario) {
        responderJson(401, ['sucesso' => false, 'mensagem' => 'Usuario nao encontrado.']);
    }

    $partes = preg_split('/\s+/', trim($usuario['nome'])) ?: [];
    $iniciais = '';
    foreach (array_slice(array_filter($partes), 0, 2) as $parte) {
        $iniciais .= mb_strtoupper(mb_substr($parte, 0, 1));
    }

    responderJson(200, [
        'sucesso' => true,
        'usuario' => [
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'iniciais' => $iniciais,
        ],
    ]);
} catch (Throwable $erro) {
    error_log($erro->getMessage());
    responderJson(500, ['sucesso' => false, 'mensagem' => 'Nao foi possivel carregar o usuario.']);
}
