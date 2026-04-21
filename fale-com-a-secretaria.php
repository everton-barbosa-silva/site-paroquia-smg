<?php
declare(strict_types=1);

require_once __DIR__ . '/auth/auth.php';

function proximo_ciclo_batismo(): array
{
    $hoje = new DateTimeImmutable('today');
    $referencia = $hoje;

    $calcular = static function (DateTimeImmutable $base): array {
        $ultimoDia = $base->modify('last day of this month');
        $ultimoSabado = $ultimoDia->modify('last saturday');
        $penultimoSabado = $ultimoSabado->modify('-7 days');
        $ultimoDomingo = $ultimoDia->modify('last sunday');

        return [$penultimoSabado, $ultimoDomingo];
    };

    [$curso, $batismo] = $calcular($referencia);
    if ($hoje > $batismo) {
        $referencia = $hoje->modify('first day of next month');
        [$curso, $batismo] = $calcular($referencia);
    }

    return ['curso' => $curso, 'batismo' => $batismo];
}

function valor_campo(array $dados, string $chave): string
{
    return trim((string) ($dados[$chave] ?? ''));
}

function checkbox_marcado(array $dados, string $chave): bool
{
    return (($dados[$chave] ?? '0') === '1');
}

function link_whatsapp(string $mensagem): string
{
  return 'https://wa.me/551142589355?text=' . rawurlencode($mensagem);
}

function montar_mensagem_whatsapp(array $linhas, string $mensagemExtra = ''): string
{
  if ($mensagemExtra !== '') {
    $linhas[] = 'Observacoes: ' . $mensagemExtra;
  }

  return implode("\n", $linhas);
}

function resumo_contagem_batismo(DateTimeImmutable $dataBatismo): string
{
    $hoje = new DateTimeImmutable('today');
    $dias = (int) $hoje->diff($dataBatismo)->format('%a');

    if ($dias === 0) {
        return 'O batismo acontece hoje.';
    }

    if ($dias === 1) {
        return 'Falta 1 dia para o proximo batismo.';
    }

    return 'Faltam ' . $dias . ' dias para o proximo batismo.';
}

function montar_link_whatsapp_batismo(
  string $nomeContato,
  string $telefone,
  string $email,
  string $nomeInteressado,
  string $mensagem,
  array $cicloBatismo
): string {
  return link_whatsapp(montar_mensagem_whatsapp([
    'Prezada secretaria, solicito inscricao no curso de batismo.',
    '',
    'Responsavel: ' . $nomeContato,
    'Crianca: ' . $nomeInteressado,
    'WhatsApp: ' . $telefone,
    'E-mail: ' . $email,
    'Curso: ' . $cicloBatismo['curso']->format('d/m/Y'),
    'Batismo: ' . $cicloBatismo['batismo']->format('d/m/Y') . ' as 10h',
    '',
    'Aguardo orientacoes. Obrigado.'
  ], $mensagem));
}

function montar_link_whatsapp_casamento(
  string $nomeContato,
  string $telefone,
  string $email,
  string $nomeInteressado,
  string $mensagem
): string {
  return link_whatsapp(montar_mensagem_whatsapp([
    'Prezada secretaria, solicito atendimento para casamento.',
    '',
    'Contato: ' . $nomeContato,
    'Noivos: ' . $nomeInteressado,
    'WhatsApp: ' . $telefone,
    'E-mail: ' . $email,
    '',
    'Aguardo orientacoes. Obrigado.'
  ], $mensagem));
}

function montar_link_whatsapp_item_religioso(
  string $nomeContato,
  string $telefone,
  string $email,
  string $itemDesejado,
  string $mensagem
): string {
  return link_whatsapp(montar_mensagem_whatsapp([
    'Prezada secretaria, gostaria de solicitar um item religioso.',
    '',
    'Contato: ' . $nomeContato,
    'Item desejado: ' . $itemDesejado,
    'WhatsApp: ' . $telefone,
    'E-mail: ' . $email,
    '',
    'Aguardo confirmacao de disponibilidade. Obrigado.'
  ], $mensagem));
}

$sucesso = '';
$erro = '';
$whatsappRedirectUrl = '';
$dados = $_POST;
$assuntoSelecionado = (string) ($dados['assunto'] ?? '');

$checklistBatismo = [
    'doc_certidao_batizando' => 'Certidao de nascimento da crianca',
    'doc_pais' => 'Documentos dos pais da crianca',
    'doc_residencia' => 'Comprovante de residencia',
    'doc_padrinhos' => 'Documentos dos padrinhos (CPF e RG)',
    'doc_casamento_padrinhos' => 'Certidao de casamento religioso dos padrinhos (nao obrigatorio)',
];

$cicloBatismo = proximo_ciclo_batismo();
$resumoBatismo = resumo_contagem_batismo($cicloBatismo['batismo']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!csrf_validate($_POST['csrf_token'] ?? null)) {
            throw new RuntimeException('Sessao invalida. Atualize a pagina e tente novamente.');
        }

        if (!in_array($assuntoSelecionado, ['item_religioso', 'batismo', 'casamento'], true)) {
            throw new RuntimeException('Escolha um assunto valido para continuar.');
        }

        $nomeContato = valor_campo($dados, 'nome_contato');
        $email = mb_strtolower(valor_campo($dados, 'email'));
        $telefone = valor_campo($dados, 'telefone');
        $nomeInteressado = valor_campo($dados, 'nome_interessado');
        $itemDesejado = valor_campo($dados, 'item_desejado');
        $mensagem = valor_campo($dados, 'mensagem');
        if ($nomeContato === '' || $email === '' || $telefone === '') {
            throw new RuntimeException('Preencha nome, e-mail e telefone para a secretaria retornar.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Informe um e-mail valido para contato.');
        }

        $telefoneNumeros = preg_replace('/\D+/', '', $telefone) ?? '';
        if (strlen($telefoneNumeros) < 10) {
            throw new RuntimeException('Informe um telefone com DDD para contato.');
        }

        if ($assuntoSelecionado === 'item_religioso') {
            if ($itemDesejado === '') {
                throw new RuntimeException('Descreva qual item religioso voce deseja comprar.');
            }
        }

        if ($assuntoSelecionado === 'casamento') {
            if ($nomeInteressado === '') {
                throw new RuntimeException('Informe o nome do casal ou dos noivos para o atendimento.');
            }
        }

        if ($assuntoSelecionado === 'batismo') {
            if ($nomeInteressado === '') {
                throw new RuntimeException('Informe o nome da crianca que sera batizada.');
            }

            $checklist = [];
            foreach ($checklistBatismo as $campo => $rotulo) {
                $checklist[$campo] = [
                    'rotulo' => $rotulo,
                    'ok' => checkbox_marcado($dados, $campo),
                    'obrigatorio' => $campo !== 'doc_casamento_padrinhos',
                ];
            }

            foreach ($checklist as $item) {
                if ($item['obrigatorio'] && $item['ok'] !== true) {
                    throw new RuntimeException('Marque todos os documentos obrigatorios antes de solicitar o agendamento do batismo.');
                }
            }
        }

        if ($assuntoSelecionado === 'batismo') {
            $sucesso = 'Mensagem preparada. Voce sera encaminhado ao WhatsApp da paroquia.';
            $whatsappRedirectUrl = montar_link_whatsapp_batismo(
                $nomeContato,
                $telefone,
                $email,
                $nomeInteressado,
                $mensagem,
                $cicloBatismo
            );
        }

        if ($assuntoSelecionado === 'casamento') {
            $sucesso = 'Mensagem preparada. Voce sera encaminhado ao WhatsApp da paroquia.';
            $whatsappRedirectUrl = montar_link_whatsapp_casamento(
                $nomeContato,
                $telefone,
                $email,
                $nomeInteressado,
                $mensagem
            );
        }

        if ($assuntoSelecionado === 'item_religioso') {
            $sucesso = 'Mensagem preparada. Voce sera encaminhado ao WhatsApp da paroquia.';
            $whatsappRedirectUrl = montar_link_whatsapp_item_religioso(
                $nomeContato,
                $telefone,
                $email,
                $itemDesejado,
                $mensagem
            );
        }

        $dados = [];
        $assuntoSelecionado = '';
    } catch (Throwable $e) {
        $erro = $e->getMessage();
    }
}
$tipoGet = in_array($_GET['tipo'] ?? '', ['batismo', 'casamento', 'item_religioso'], true)
    ? $_GET['tipo']
    : '';

// After a successful POST, keep the tipo context so we stay in menu view
if ($sucesso !== '') {
    $tipoGet = '';
}

// If POST had an assunto, maintain that view on validation error
if ($erro !== '' && $assuntoSelecionado !== '') {
    $tipoGet = $assuntoSelecionado;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Atendimento digital da secretaria paroquial para batismo, casamento e itens religiosos.">
  <title>Secretaria | Paroquia SMG</title>
  <link rel="stylesheet" href="style.css">
  <link rel="icon" href="assets/favicon.png" type="image/png">
  <style>
    /* ── Secretaria Menu Page ── */
    .sec-hero {
      background: linear-gradient(145deg, #1a1206 0%, #2c1e04 60%, #3d2c06 100%);
      padding: 4rem 1.5rem 3rem;
      text-align: center;
      color: #fff;
    }
    .sec-hero .sec-cross {
      display: block;
      font-size: 2.2rem;
      margin-bottom: 1rem;
      opacity: .85;
    }
    .sec-hero h1 {
      font-family: 'Playfair Display', serif;
      font-size: clamp(1.8rem, 5vw, 2.8rem);
      color: var(--gold);
      margin: 0 0 .6rem;
      line-height: 1.2;
    }
    .sec-hero p {
      font-size: 1rem;
      color: rgba(255,255,255,.7);
      max-width: 460px;
      margin: 0 auto;
    }

    .sec-menu {
      background: #faf7f0;
      padding: 3rem 1.5rem 4rem;
    }
    .sec-menu-grid {
      max-width: 560px;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }
    .sec-card {
      display: flex;
      align-items: center;
      gap: 1.2rem;
      background: #fff;
      border: 1.5px solid #e8e0cc;
      border-radius: 14px;
      padding: 1.3rem 1.5rem;
      text-decoration: none;
      color: #1a1206;
      font-weight: 600;
      font-size: 1.05rem;
      transition: box-shadow .2s, border-color .2s, transform .15s;
      cursor: pointer;
    }
    .sec-card:hover {
      border-color: var(--gold);
      box-shadow: 0 4px 20px rgba(212,175,55,.18);
      transform: translateY(-2px);
    }
    .sec-card .sec-icon {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      flex-shrink: 0;
    }
    .sec-card .sec-label { flex: 1; }
    .sec-card .sec-label small {
      display: block;
      font-size: .8rem;
      font-weight: 400;
      color: #888;
      margin-top: 2px;
    }
    .sec-card .sec-arrow {
      font-size: 1.1rem;
      color: #bbb;
      flex-shrink: 0;
    }
    .sec-card.back {
      border-style: dashed;
      background: transparent;
      color: #777;
      font-size: .95rem;
    }
    .sec-card.back:hover { border-color: #aaa; box-shadow: none; transform: none; }

    /* icon bg colours */
    .icon-batismo { background: #fff4d6; color: #b8860b; }
    .icon-casamento { background: #fdeef6; color: #b5357b; }
    .icon-item { background: #edf5ff; color: #3570b5; }

    /* ── Form panel ── */
    .sec-form-wrap {
      background: #faf7f0;
      padding: 2.5rem 1.5rem 4rem;
    }
    .sec-form-inner {
      max-width: 540px;
      margin: 0 auto;
    }
    .sec-back-link {
      display: inline-flex;
      align-items: center;
      gap: .4rem;
      color: var(--gold-dark);
      text-decoration: none;
      font-size: .9rem;
      margin-bottom: 1.8rem;
    }
    .sec-back-link:hover { text-decoration: underline; }
    .sec-form-title {
      display: flex;
      align-items: center;
      gap: .9rem;
      margin-bottom: 1.8rem;
    }
    .sec-form-title .sec-icon {
      width: 52px; height: 52px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.5rem; flex-shrink: 0;
    }
    .sec-form-title h2 {
      font-family: 'Playfair Display', serif;
      font-size: 1.5rem;
      margin: 0;
      color: #1a1206;
    }
    .sec-form-title p { font-size: .85rem; color: #888; margin: 2px 0 0; }

    /* Batismo info box */
    .batismo-info {
      background: #fff8e6;
      border: 1px solid #f0dc9a;
      border-radius: 10px;
      padding: 1.1rem 1.3rem;
      margin-bottom: 1.5rem;
      font-size: .9rem;
      color: #6b4f00;
    }
    .batismo-info strong { display: block; margin-bottom: .2rem; color: #3d2c06; }
    .batismo-info-row { display: flex; gap: 1.5rem; flex-wrap: wrap; }
    .batismo-info-row div { flex: 1; min-width: 160px; }

    /* Checklist */
    .sec-checklist { list-style: none; padding: 0; margin: 0 0 1.5rem; }
    .sec-checklist li {
      display: flex;
      align-items: center;
      gap: .7rem;
      padding: .6rem 0;
      border-bottom: 1px solid #ece7d8;
      font-size: .95rem;
      color: #333;
    }
    .sec-checklist li:last-child { border: none; }
    .sec-checklist input[type=checkbox] { width: 18px; height: 18px; accent-color: var(--gold-dark); flex-shrink: 0; }
    .sec-checklist .opt-badge {
      font-size: .7rem;
      background: #eee;
      color: #888;
      border-radius: 4px;
      padding: 1px 5px;
      margin-left: auto;
    }

    /* Form fields */
    .sec-field { margin-bottom: 1.2rem; }
    .sec-field label { display: block; font-size: .85rem; font-weight: 600; color: #444; margin-bottom: .4rem; }
    .sec-field input, .sec-field textarea, .sec-field select {
      width: 100%;
      box-sizing: border-box;
      border: 1.5px solid #ddd;
      border-radius: 8px;
      padding: .7rem 1rem;
      font-size: .95rem;
      font-family: inherit;
      background: #fff;
      transition: border-color .2s;
    }
    .sec-field input:focus, .sec-field textarea:focus {
      outline: none;
      border-color: var(--gold);
    }
    .sec-field textarea { resize: vertical; }
    .sec-btn {
      display: block;
      width: 100%;
      background: linear-gradient(135deg, var(--gold-dark), var(--gold));
      color: #1a1206;
      font-weight: 700;
      font-size: 1rem;
      border: none;
      border-radius: 10px;
      padding: .9rem;
      cursor: pointer;
      transition: opacity .2s, transform .15s;
      margin-top: 1rem;
    }
    .sec-btn:hover { opacity: .92; transform: translateY(-1px); }

    .alert { border-radius: 8px; padding: 1rem 1.2rem; margin-bottom: 1.5rem; font-size: .95rem; }
    .alert-success { background: #edfbf0; border: 1px solid #86efac; color: #166534; }
    .alert-error { background: #fff1f1; border: 1px solid #fca5a5; color: #991b1b; }
    .sec-whatsapp-cta {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: .55rem;
      margin-top: 1rem;
      padding: .85rem 1.2rem;
      border-radius: 999px;
      background: #25d366;
      color: #fff;
      text-decoration: none;
      font-weight: 700;
      box-shadow: 0 8px 24px rgba(37, 211, 102, .22);
    }
    .sec-whatsapp-note {
      margin-top: .85rem;
      color: #666;
      font-size: .85rem;
    }
  </style>
</head>
<body>
  <header class="header">
    <a href="index.html" class="logo">Paroquia <span>SMG</span></a>
    <div class="hamburger" id="hamburger">
      <span></span><span></span><span></span>
    </div>
    <nav>
      <ul class="nav-links" id="nav-links">
        <li><a href="index.html">Inicio</a></li>
        <li><a href="index.html#missas">Missas</a></li>
        <li><a href="index.html#liturgia">Liturgia</a></li>
        <li><a href="index.html#biblia">Biblia</a></li>
        <li><a href="novenas.html">Novenas</a></li>
        <li><a href="oracoes.html">Oracoes</a></li>
        <li><a href="documentos-igreja.html">Documentos</a></li>
        <li><a href="teologia.html">Teologia</a></li>
        <li><a href="quiz.html">Quiz</a></li>
        <li><a href="index.html#contato">Contato</a></li>
      </ul>
    </nav>
  </header>

  <div class="sec-hero">
    <span class="sec-cross">✝</span>
    <h1>Secretaria Paroquial</h1>
    <p>Atendimento digital — batismo, casamento e itens religiosos.</p>
  </div>

  <?php if ($sucesso): ?>
  <div class="sec-form-wrap">
    <div class="sec-form-inner">
      <div class="alert alert-success">
        <?= h($sucesso) ?>
      </div>
      <?php if ($whatsappRedirectUrl !== ''): ?>
        <a href="<?= h($whatsappRedirectUrl) ?>" class="sec-whatsapp-cta" target="_blank" rel="noopener noreferrer">
          Abrir WhatsApp da Paroquia
        </a>
        <p class="sec-whatsapp-note">Se o WhatsApp nao abrir automaticamente, use o botao acima.</p>
      <?php endif; ?>
      <a href="fale-com-a-secretaria.php" class="sec-back-link">&#8592; Voltar ao menu</a>
    </div>
  </div>

  <?php elseif ($tipoGet === ''): ?>
  <!-- ── Menu principal ── -->
  <section class="sec-menu" aria-label="Menu da secretaria">
    <div class="sec-menu-grid">
      <a href="?tipo=batismo" class="sec-card">
        <span class="sec-icon icon-batismo">🕊️</span>
        <span class="sec-label">
          Inscricao do Batismo
          <small>Curso + celebracao + checklist de documentos</small>
        </span>
        <span class="sec-arrow">›</span>
      </a>
      <a href="?tipo=casamento" class="sec-card">
        <span class="sec-icon icon-casamento">💍</span>
        <span class="sec-label">
          Inscricao do Casamento
          <small>Pre-atendimento e agendamento de noivos</small>
        </span>
        <span class="sec-arrow">›</span>
      </a>
      <a href="?tipo=item_religioso" class="sec-card">
        <span class="sec-icon icon-item">📿</span>
        <span class="sec-label">
          Comprar Item Religioso
          <small>Terco, medalha, vela, imagem e outros</small>
        </span>
        <span class="sec-arrow">›</span>
      </a>
      <a href="index.html" class="sec-card back">
        <span style="font-size:1.1rem;">&#8592;</span>
        <span>Voltar para o site</span>
      </a>
    </div>
  </section>

  <?php elseif ($tipoGet === 'batismo'): ?>
  <!-- ── Formulario Batismo ── -->
  <div class="sec-form-wrap">
    <div class="sec-form-inner">
      <a href="fale-com-a-secretaria.php" class="sec-back-link">&#8592; Voltar ao menu</a>
      <div class="sec-form-title">
        <span class="sec-icon icon-batismo">🕊️</span>
        <div>
          <h2>Inscricao do Batismo</h2>
          <p>Preencha os dados e a secretaria retornara em breve.</p>
        </div>
      </div>

      <div class="batismo-info">
        <div class="batismo-info-row">
          <div>
            <strong>Curso de Batismo</strong>
            Penultimo sabado do mes<br>
            Proxima data: <?= h($cicloBatismo['curso']->format('d/m/Y')) ?>
          </div>
          <div>
            <strong>Celebracao</strong>
            Ultimo domingo do mes, missa das 10h<br>
            Proxima data: <?= h($cicloBatismo['batismo']->format('d/m/Y')) ?><br>
            <?= h($resumoBatismo) ?>
          </div>
          <div>
            <strong>Taxa paroquial</strong>
            R$ 120,00
          </div>
        </div>
      </div>

      <p style="font-size:.85rem;color:#666;margin-bottom:.6rem;font-weight:600;">Documentos necessarios:</p>
      <ul class="sec-checklist">
        <li><input type="checkbox" disabled> Certidao de nascimento da crianca</li>
        <li><input type="checkbox" disabled> Documentos dos pais</li>
        <li><input type="checkbox" disabled> Comprovante de residencia</li>
        <li><input type="checkbox" disabled> Documentos dos padrinhos (CPF e RG)</li>
        <li><input type="checkbox" disabled> Certidao de casamento religioso dos padrinhos <span class="opt-badge">opcional</span></li>
      </ul>

      <?php if ($erro): ?><div class="alert alert-error"><?= h($erro) ?></div><?php endif; ?>

      <form method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="assunto" value="batismo">
        <input type="hidden" name="deseja_agendar" value="1">
        <input type="hidden" name="doc_certidao_batizando" value="1">
        <input type="hidden" name="doc_pais" value="1">
        <input type="hidden" name="doc_residencia" value="1">
        <input type="hidden" name="doc_padrinhos" value="1">
        <input type="hidden" name="doc_casamento_padrinhos" value="0">
        <div class="sec-field">
          <label for="b_nome">Seu nome (responsavel)</label>
          <input id="b_nome" name="nome_contato" type="text" required value="<?= h(valor_campo($dados, 'nome_contato')) ?>">
        </div>
        <div class="sec-field">
          <label for="b_crianca">Nome da crianca (batizando)</label>
          <input id="b_crianca" name="nome_interessado" type="text" required value="<?= h(valor_campo($dados, 'nome_interessado')) ?>">
        </div>
        <div class="sec-field">
          <label for="b_tel">WhatsApp</label>
          <input id="b_tel" name="telefone" type="text" inputmode="tel" required value="<?= h(valor_campo($dados, 'telefone')) ?>">
        </div>
        <div class="sec-field">
          <label for="b_email">E-mail</label>
          <input id="b_email" name="email" type="email" required value="<?= h(valor_campo($dados, 'email')) ?>">
        </div>
        <div class="sec-field">
          <label for="b_obs">Observacoes (opcional)</label>
          <textarea id="b_obs" name="mensagem" rows="3" placeholder="Ex.: data preferida, duvidas sobre documentos..."><?= h(valor_campo($dados, 'mensagem')) ?></textarea>
        </div>
        <button type="submit" class="sec-btn">Enviar inscricao</button>
      </form>
    </div>
  </div>

  <?php elseif ($tipoGet === 'casamento'): ?>
  <!-- ── Formulario Casamento ── -->
  <div class="sec-form-wrap">
    <div class="sec-form-inner">
      <a href="fale-com-a-secretaria.php" class="sec-back-link">&#8592; Voltar ao menu</a>
      <div class="sec-form-title">
        <span class="sec-icon icon-casamento">💍</span>
        <div>
          <h2>Inscricao do Casamento</h2>
          <p>Pre-atendimento para noivos. A secretaria entrara em contato.</p>
        </div>
      </div>

      <?php if ($erro): ?><div class="alert alert-error"><?= h($erro) ?></div><?php endif; ?>

      <form method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="assunto" value="casamento">
        <div class="sec-field">
          <label for="c_nome">Seu nome</label>
          <input id="c_nome" name="nome_contato" type="text" required value="<?= h(valor_campo($dados, 'nome_contato')) ?>">
        </div>
        <div class="sec-field">
          <label for="c_casal">Nome do casal / dos noivos</label>
          <input id="c_casal" name="nome_interessado" type="text" required value="<?= h(valor_campo($dados, 'nome_interessado')) ?>">
        </div>
        <div class="sec-field">
          <label for="c_tel">WhatsApp</label>
          <input id="c_tel" name="telefone" type="text" inputmode="tel" required value="<?= h(valor_campo($dados, 'telefone')) ?>">
        </div>
        <div class="sec-field">
          <label for="c_email">E-mail</label>
          <input id="c_email" name="email" type="email" required value="<?= h(valor_campo($dados, 'email')) ?>">
        </div>
        <div class="sec-field">
          <label for="c_msg">Data pretendida ou observacoes</label>
          <textarea id="c_msg" name="mensagem" rows="3" placeholder="Ex.: gostaríamos de casar em outubro, temos duvidas sobre os documentos..."><?= h(valor_campo($dados, 'mensagem')) ?></textarea>
        </div>
        <button type="submit" class="sec-btn">Solicitar atendimento</button>
      </form>
    </div>
  </div>

  <?php elseif ($tipoGet === 'item_religioso'): ?>
  <!-- ── Formulario Item Religioso ── -->
  <div class="sec-form-wrap">
    <div class="sec-form-inner">
      <a href="fale-com-a-secretaria.php" class="sec-back-link">&#8592; Voltar ao menu</a>
      <div class="sec-form-title">
        <span class="sec-icon icon-item">📿</span>
        <div>
          <h2>Comprar Item Religioso</h2>
          <p>Informe o que procura. A secretaria confirma disponibilidade e valor.</p>
        </div>
      </div>

      <?php if ($erro): ?><div class="alert alert-error"><?= h($erro) ?></div><?php endif; ?>

      <form method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="assunto" value="item_religioso">
        <div class="sec-field">
          <label for="i_nome">Seu nome</label>
          <input id="i_nome" name="nome_contato" type="text" required value="<?= h(valor_campo($dados, 'nome_contato')) ?>">
        </div>
        <div class="sec-field">
          <label for="i_item">Qual item voce procura?</label>
          <input id="i_item" name="item_desejado" type="text" placeholder="Ex.: terco, medalha, vela, imagem de santo..." required value="<?= h(valor_campo($dados, 'item_desejado')) ?>">
        </div>
        <div class="sec-field">
          <label for="i_tel">WhatsApp</label>
          <input id="i_tel" name="telefone" type="text" inputmode="tel" required value="<?= h(valor_campo($dados, 'telefone')) ?>">
        </div>
        <div class="sec-field">
          <label for="i_email">E-mail</label>
          <input id="i_email" name="email" type="email" required value="<?= h(valor_campo($dados, 'email')) ?>">
        </div>
        <div class="sec-field">
          <label for="i_msg">Detalhes (opcional)</label>
          <textarea id="i_msg" name="mensagem" rows="3" placeholder="Quantidade, tamanho, urgencia, presente..."><?= h(valor_campo($dados, 'mensagem')) ?></textarea>
        </div>
        <button type="submit" class="sec-btn">Enviar pedido</button>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <footer class="footer">
    <p style="margin-bottom:.3rem;opacity:.6;font-size:.8rem;">Secretaria: terca a sexta, das 9h as 16h &nbsp;|&nbsp; WhatsApp: (11) 4258-9355</p>
    <p>&copy; 2026 Paroquia Santa Maria Goretti | Rua Holdo Botto Malanconi, 355</p>
  </footer>

  <a href="https://wa.me/551142589355" class="whatsapp-float" target="_blank" rel="noopener noreferrer">
    <img src="assets/whats.png" alt="WhatsApp">
  </a>

  <script src="js/app.js"></script>
  <?php if ($whatsappRedirectUrl !== ''): ?>
  <script>
    window.setTimeout(function () {
      window.location.href = <?= json_encode($whatsappRedirectUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    }, 900);
  </script>
  <?php endif; ?>
</body>
</html>