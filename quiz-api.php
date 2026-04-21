<?php
declare(strict_types=1);

require_once __DIR__ . '/auth/db.php';

header('Content-Type: application/json; charset=utf-8');

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function ensure_quiz_table(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS quiz_ranking (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(120) NOT NULL,
            pontuacao INT UNSIGNED NOT NULL,
            total_perguntas INT UNSIGNED NOT NULL,
            percentual DECIMAL(5,2) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_quiz_percentual (percentual),
            INDEX idx_quiz_pontuacao (pontuacao),
            INDEX idx_quiz_data (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

try {
    $pdo = get_db_connection();
    ensure_quiz_table($pdo);

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = (string) ($_GET['action'] ?? 'ranking');

    if ($method === 'GET' && $action === 'ranking') {
        $stmt = $pdo->query(
            'SELECT nome, pontuacao, total_perguntas, percentual, created_at
             FROM quiz_ranking
             ORDER BY percentual DESC, pontuacao DESC, created_at ASC
             LIMIT 20'
        );

        json_response([
            'ok' => true,
            'ranking' => $stmt->fetchAll(),
        ]);
    }

    if ($method === 'POST' && $action === 'save') {
        $payload = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            json_response(['ok' => false, 'error' => 'Payload invalido.'], 400);
        }

        $nome = trim((string) ($payload['nome'] ?? ''));
        $pontuacao = (int) ($payload['pontuacao'] ?? -1);
        $total = (int) ($payload['total_perguntas'] ?? -1);

        if ($nome === '' || mb_strlen($nome) < 2) {
            json_response(['ok' => false, 'error' => 'Informe um nome valido.'], 422);
        }

        if ($total <= 0 || $pontuacao < 0 || $pontuacao > $total) {
            json_response(['ok' => false, 'error' => 'Pontuacao invalida.'], 422);
        }

        $nome = mb_substr($nome, 0, 120);
        $percentual = round(($pontuacao / $total) * 100, 2);

        $stmt = $pdo->prepare(
            'INSERT INTO quiz_ranking (nome, pontuacao, total_perguntas, percentual)
             VALUES (:nome, :pontuacao, :total_perguntas, :percentual)'
        );

        $stmt->execute([
            'nome' => $nome,
            'pontuacao' => $pontuacao,
            'total_perguntas' => $total,
            'percentual' => $percentual,
        ]);

        json_response([
            'ok' => true,
            'message' => 'Pontuacao salva com sucesso.',
            'percentual' => $percentual,
        ]);
    }

    json_response(['ok' => false, 'error' => 'Acao nao suportada.'], 404);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => 'Erro interno ao processar quiz.'], 500);
}
