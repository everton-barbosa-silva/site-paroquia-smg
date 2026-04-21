<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';

secretaria_require_login();

$erro = '';
$sucesso = '';

try {
    $pdo = get_db_connection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrf_validate($_POST['csrf_token'] ?? null)) {
            throw new RuntimeException('Sessao invalida. Atualize a pagina.');
        }

        $inscricaoId = (int) ($_POST['inscricao_id'] ?? 0);
        $acao = (string) ($_POST['acao'] ?? '');

        if ($inscricaoId <= 0) {
            throw new RuntimeException('Inscricao invalida.');
        }

        if ($acao === 'aprovar_documentos') {
            $stmt = $pdo->prepare('UPDATE inscricoes SET status_documentos = :status WHERE id = :id');
            $stmt->execute(['status' => 'documentos_aprovados', 'id' => $inscricaoId]);
            $sucesso = 'Documentos aprovados com sucesso.';
        } elseif ($acao === 'rejeitar_documentos') {
            $stmt = $pdo->prepare('UPDATE inscricoes SET status_documentos = :status WHERE id = :id');
            $stmt->execute(['status' => 'documentos_rejeitados', 'id' => $inscricaoId]);
            $sucesso = 'Documentos rejeitados.';
        } elseif ($acao === 'aprovar_inscricao') {
            $stmt = $pdo->prepare('UPDATE inscricoes SET status_inscricao = :status WHERE id = :id');
            $stmt->execute(['status' => 'aprovada', 'id' => $inscricaoId]);
            $sucesso = 'Inscricao aprovada.';
        } elseif ($acao === 'recusar_inscricao') {
            $stmt = $pdo->prepare('UPDATE inscricoes SET status_inscricao = :status WHERE id = :id');
            $stmt->execute(['status' => 'recusada', 'id' => $inscricaoId]);
            $sucesso = 'Inscricao recusada.';
        } else {
            throw new RuntimeException('Acao invalida.');
        }
    }

    $pendentesStmt = $pdo->query(
        "SELECT i.*, COUNT(d.id) AS total_documentos
         FROM inscricoes i
         LEFT JOIN inscricao_documentos d ON d.inscricao_id = i.id
         WHERE i.status_documentos = 'pendente_validacao'
         GROUP BY i.id
         ORDER BY i.created_at DESC"
    );
    $pendentes = $pendentesStmt->fetchAll();

    $todosStmt = $pdo->query(
        "SELECT i.*, COUNT(d.id) AS total_documentos
         FROM inscricoes i
         LEFT JOIN inscricao_documentos d ON d.inscricao_id = i.id
         GROUP BY i.id
         ORDER BY i.created_at DESC"
    );
    $todos = $todosStmt->fetchAll();

    $porTipo = ['batismo' => [], 'casamento' => []];
    foreach ($todos as $inscricao) {
        $tipo = $inscricao['tipo_inscricao'];
        if (!isset($porTipo[$tipo])) {
            $porTipo[$tipo] = [];
        }
        $porTipo[$tipo][] = $inscricao;
    }
} catch (Throwable $e) {
    $erro = $e->getMessage();
    $pendentes = [];
    $porTipo = ['batismo' => [], 'casamento' => []];
}

function status_legivel(string $status): string
{
    $mapa = [
        'recebida' => 'Recebida',
        'aprovada' => 'Aprovada',
        'recusada' => 'Recusada',
        'pendente_validacao' => 'Pendente validacao',
        'documentos_aprovados' => 'Documentos aprovados',
        'documentos_rejeitados' => 'Documentos rejeitados',
        'entrega_presencial' => 'Entrega presencial',
    ];

    return $mapa[$status] ?? $status;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Painel da Secretaria | Paroquia SMG</title>
  <link rel="stylesheet" href="/style.css">
</head>
<body>
  <header class="subheader">
    <h1>Painel da Secretaria</h1>
    <p style="text-align:center; margin-top: .5rem;">Bem-vindo(a), <?= h((string) ($_SESSION['secretaria_user_nome'] ?? '')) ?></p>
  </header>

  <section class="section light">
    <div class="container">
      <div class="panel-actions">
        <a href="/inscricao.php" class="btn btn-outline" style="color: var(--gold-dark); border-color: var(--gold-dark);">Nova inscricao (publica)</a>
        <a href="/logout.php" class="btn" style="background: #8b1f1f; border-color: #8b1f1f; box-shadow:none;">Sair</a>
      </div>

      <?php if ($erro): ?>
        <div class="alert alert-error"><?= h($erro) ?></div>
      <?php endif; ?>

      <?php if ($sucesso): ?>
        <div class="alert alert-success"><?= h($sucesso) ?></div>
      <?php endif; ?>

      <div class="card" style="margin-bottom: 2rem;">
        <h2>Pendentes de validacao de documentos</h2>
        <?php if (empty($pendentes)): ?>
          <p>Nenhuma inscricao pendente no momento.</p>
        <?php else: ?>
          <div class="table-wrap">
            <table class="panel-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Tipo</th>
                  <th>Responsavel</th>
                  <th>Inscrito</th>
                  <th>Documentos</th>
                  <th>Acoes</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pendentes as $item): ?>
                  <tr>
                    <td><?= (int) $item['id'] ?></td>
                    <td><?= h(mb_strtoupper((string) $item['tipo_inscricao'])) ?></td>
                    <td><?= h((string) $item['nome_responsavel']) ?></td>
                    <td><?= h((string) $item['nome_inscrito']) ?></td>
                    <td><?= (int) $item['total_documentos'] ?></td>
                    <td>
                      <form method="post" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="inscricao_id" value="<?= (int) $item['id'] ?>">
                        <button type="submit" name="acao" value="aprovar_documentos" class="btn btn-small">Aprovar docs</button>
                        <button type="submit" name="acao" value="rejeitar_documentos" class="btn btn-small btn-danger">Rejeitar docs</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <div class="card" style="margin-bottom: 2rem;">
        <h2>Inscricoes de Batismo</h2>
        <?php if (empty($porTipo['batismo'])): ?>
          <p>Nenhuma inscricao de batismo.</p>
        <?php else: ?>
          <div class="table-wrap">
            <table class="panel-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Responsavel</th>
                  <th>Contato</th>
                  <th>Inscrito</th>
                  <th>Data prevista</th>
                  <th>Status docs</th>
                  <th>Status inscricao</th>
                  <th>Acoes</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($porTipo['batismo'] as $item): ?>
                  <tr>
                    <td><?= (int) $item['id'] ?></td>
                    <td><?= h((string) $item['nome_responsavel']) ?></td>
                    <td>
                      <?= h((string) $item['email']) ?><br>
                      <?= h((string) $item['telefone']) ?>
                    </td>
                    <td><?= h((string) $item['nome_inscrito']) ?></td>
                    <td><?= h((string) ($item['data_batismo_prevista'] ?? '-')) ?></td>
                    <td><?= h(status_legivel((string) $item['status_documentos'])) ?></td>
                    <td><?= h(status_legivel((string) $item['status_inscricao'])) ?></td>
                    <td>
                      <form method="post" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="inscricao_id" value="<?= (int) $item['id'] ?>">
                        <button type="submit" name="acao" value="aprovar_inscricao" class="btn btn-small">Aprovar</button>
                        <button type="submit" name="acao" value="recusar_inscricao" class="btn btn-small btn-danger">Recusar</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <div class="card">
        <h2>Inscricoes de Casamento</h2>
        <?php if (empty($porTipo['casamento'])): ?>
          <p>Nenhuma inscricao de casamento.</p>
        <?php else: ?>
          <div class="table-wrap">
            <table class="panel-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Responsavel</th>
                  <th>Contato</th>
                  <th>Casal</th>
                  <th>Status docs</th>
                  <th>Status inscricao</th>
                  <th>Acoes</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($porTipo['casamento'] as $item): ?>
                  <tr>
                    <td><?= (int) $item['id'] ?></td>
                    <td><?= h((string) $item['nome_responsavel']) ?></td>
                    <td>
                      <?= h((string) $item['email']) ?><br>
                      <?= h((string) $item['telefone']) ?>
                    </td>
                    <td><?= h((string) $item['nome_inscrito']) ?></td>
                    <td><?= h(status_legivel((string) $item['status_documentos'])) ?></td>
                    <td><?= h(status_legivel((string) $item['status_inscricao'])) ?></td>
                    <td>
                      <form method="post" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="inscricao_id" value="<?= (int) $item['id'] ?>">
                        <button type="submit" name="acao" value="aprovar_inscricao" class="btn btn-small">Aprovar</button>
                        <button type="submit" name="acao" value="recusar_inscricao" class="btn btn-small btn-danger">Recusar</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</body>
</html>
