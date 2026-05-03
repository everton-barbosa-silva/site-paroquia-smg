<?php
declare(strict_types=1);

require_once __DIR__ . '/auth/auth.php';

if (secretaria_esta_logada()) {
    header('Location: /secretaria/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;

    if (!csrf_validate($token)) {
        $error = 'Sessao invalida. Atualize a pagina e tente novamente.';
    } else {
        $email = (string) ($_POST['email'] ?? '');
        $senha = (string) ($_POST['senha'] ?? '');

        if (secretaria_login($email, $senha)) {
            header('Location: /secretaria/index.php');
            exit;
        }

        $error = 'E-mail ou senha invalidos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login da Secretaria | Paroquia SMG</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header class="header">
    <a href="index.html" class="logo">Paróquia <span>SMG</span></a>
    <div class="hamburger" id="hamburger">
      <span></span>
      <span></span>
      <span></span>
    </div>
    <nav>
      <ul class="nav-links" id="nav-links">
    <li><a href="index.html">Início</a></li>
    <li><a href="index.html#missas">Missas</a></li>
    <li><a href="index.html#liturgia">Liturgia</a></li>
    <li><a href="index.html#biblia">Bíblia</a></li>
    <li><a href="login.html">Minha Conta</a></li>
    <li><a href="oracoes.html">Orações</a></li>
    <li><a href="documentos-igreja.html">Documentos</a></li>
    <li><a href="teologia.html">Teologia</a></li>
    <li><a href="quiz.html">Quiz</a></li>
    <li><a href="index.html#contato">Contato</a></li>
</ul>
    </nav>
  </header>

  <header class="subheader">
    <h1>Painel da Secretaria</h1>
  </header>

  <section class="section light">
    <div class="container" style="max-width: 560px;">
      <div class="card auth-card">
        <h2>Acesso restrito</h2>
        <p>Use seu e-mail e senha para acessar o painel da secretaria.</p>

        <?php if ($error): ?>
          <div class="alert alert-error"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="post" class="form-grid">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

          <label for="email">E-mail</label>
          <input id="email" name="email" type="email" required>

          <label for="senha">Senha</label>
          <input id="senha" name="senha" type="password" required>

          <button class="btn" type="submit">Entrar</button>
        </form>

        <p style="margin-top: 1rem;">
          <a href="index.html" class="btn btn-outline" style="color: var(--gold-dark); border-color: var(--gold-dark);">Voltar ao site</a>
        </p>
      </div>
    </div>
  </section>
  <script src="js/app.js"></script>
</body>
</html>
