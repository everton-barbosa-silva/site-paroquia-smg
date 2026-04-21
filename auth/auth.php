<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_validate(?string $token): bool
{
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

function secretaria_login(string $email, string $password): bool
{
    $pdo = get_db_connection();

    $stmt = $pdo->prepare('SELECT id, nome, email, senha_hash FROM secretaria_usuarios WHERE email = :email AND ativo = 1 LIMIT 1');
    $stmt->execute(['email' => mb_strtolower(trim($email))]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['senha_hash'])) {
        return false;
    }

    $_SESSION['secretaria_user_id'] = (int) $user['id'];
    $_SESSION['secretaria_user_nome'] = $user['nome'];
    $_SESSION['secretaria_user_email'] = $user['email'];

    return true;
}

function secretaria_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function secretaria_esta_logada(): bool
{
    return !empty($_SESSION['secretaria_user_id']);
}

function secretaria_require_login(): void
{
    if (!secretaria_esta_logada()) {
        header('Location: /login.php');
        exit;
    }
}
