<?php
declare(strict_types=1);

function iniciarSessao(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name('tpp_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * Confere novamente no banco antes de exigir a troca. Isso corrige sessoes
 * antigas que ainda guardavam trocar_senha=true depois da senha ja alterada.
 */
function sessaoExigeTrocaSenha(): bool
{
    if (empty($_SESSION['trocar_senha'])) {
        return false;
    }
    if (!isset($_SESSION['usuario_id']) || !function_exists('conectarBanco')) {
        return true;
    }

    try {
        $pdo = conectarBanco();
        $consulta = $pdo->prepare('SELECT trocar_senha FROM usuarios WHERE id = ? LIMIT 1');
        $consulta->execute([(int) $_SESSION['usuario_id']]);
        $trocar = (bool) $consulta->fetchColumn();
        $_SESSION['trocar_senha'] = $trocar;
        return $trocar;
    } catch (Throwable) {
        return true;
    }
}
