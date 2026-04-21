<?php
declare(strict_types=1);

/**
 * Resolve a conexao PDO compartilhada com o public_html quando existir.
 */
function get_db_connection(): PDO
{
    static $instance = null;

    if ($instance instanceof PDO) {
        return $instance;
    }

    $sharedCandidates = [
        __DIR__ . '/../../public_html/db.php',
        __DIR__ . '/../../public_html/conexao.php',
        __DIR__ . '/../../public_html/includes/config.php',
        __DIR__ . '/../../public_html/includes/db.php',
        __DIR__ . '/../public_html/db.php',
        __DIR__ . '/../public_html/conexao.php',
        __DIR__ . '/../public_html/includes/config.php',
        __DIR__ . '/../public_html/includes/db.php',
    ];

    foreach ($sharedCandidates as $candidate) {
        if (is_file($candidate)) {
            require_once $candidate;
            break;
        }
    }

    global $pdo, $db;

    if (isset($pdo) && $pdo instanceof PDO) {
        $instance = $pdo;
        return $instance;
    }

    if (isset($db) && $db instanceof PDO) {
        $instance = $db;
        return $instance;
    }

    $host = defined('DB_HOST') ? DB_HOST : getenv('DB_HOST');
    $port = defined('DB_PORT') ? DB_PORT : (getenv('DB_PORT') ?: '3306');
    $name = defined('DB_NAME') ? DB_NAME : getenv('DB_NAME');
    $user = defined('DB_USER') ? DB_USER : getenv('DB_USER');
    $pass = defined('DB_PASS') ? DB_PASS : getenv('DB_PASS');

    if (!$host || !$name || !$user) {
        throw new RuntimeException(
            'Nao foi possivel localizar a conexao compartilhada. Configure DB_HOST, DB_NAME, DB_USER e DB_PASS no cPanel ou no arquivo de conexao compartilhado.'
        );
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
    $instance = new PDO($dsn, (string) $user, (string) $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $instance;
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
