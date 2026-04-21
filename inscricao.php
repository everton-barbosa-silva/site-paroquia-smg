<?php
declare(strict_types=1);

require_once __DIR__ . '/auth/auth.php';

function proximo_ultimo_domingo(): string
{
    $hoje = new DateTimeImmutable('today');

    $calcularUltimoDomingo = static function (DateTimeImmutable $referencia): DateTimeImmutable {
        $ultimoDia = $referencia->modify('last day of this month');
        return $ultimoDia->modify('last sunday');
    };

    $ultimoDomingoAtual = $calcularUltimoDomingo($hoje);
    if ($hoje > $ultimoDomingoAtual) {
        $ultimoDomingoAtual = $calcularUltimoDomingo($hoje->modify('first day of next month'));
    }

    return $ultimoDomingoAtual->format('Y-m-d');
}

$sucesso = '';
$erro = '';
$dataBatismoPrevista = proximo_ultimo_domingo();

$documentosObrigatoriosBatismo = [
  'doc_batisterio_padrinho' => 'Batisterio do padrinho',
  'doc_batisterio_madrinha' => 'Batisterio da madrinha',
  'doc_rg_madrinha' => 'RG da madrinha',
  'doc_rg_padrinho' => 'RG do padrinho',
  'doc_certidao_crianca' => 'Certidao de nascimento da crianca',
  'doc_rg_pai' => 'RG do pai',
  'doc_rg_mae' => 'RG da mae',
  'doc_comprovante_endereco' => 'Comprovante de endereco da familia',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!csrf_validate($_POST['csrf_token'] ?? null)) {
            throw new RuntimeException('Sessao invalida. Recarregue a pagina e tente novamente.');
        }

        $tipoInscricao = (string) ($_POST['tipo_inscricao'] ?? 'batismo');
        if (!in_array($tipoInscricao, ['batismo', 'casamento'], true)) {
            throw new RuntimeException('Tipo de inscricao invalido.');
        }

        $nomeResponsavel = trim((string) ($_POST['nome_responsavel'] ?? ''));
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $telefone = trim((string) ($_POST['telefone'] ?? ''));
        $nomeInscrito = trim((string) ($_POST['nome_inscrito'] ?? ''));
        $nomePai = trim((string) ($_POST['nome_pai'] ?? ''));
        $nomeMae = trim((string) ($_POST['nome_mae'] ?? ''));
        $paisBatizados = (string) ($_POST['pais_batizados'] ?? '');
        $padrinhosBatizados = (string) ($_POST['padrinhos_batizados'] ?? '');
        $preferenciaDocumentos = (string) ($_POST['preferencia_documentos'] ?? 'presencial');
        $observacoes = trim((string) ($_POST['observacoes'] ?? ''));

        if ($nomeResponsavel === '' || $email === '' || $telefone === '' || $nomeInscrito === '') {
            throw new RuntimeException('Preencha todos os campos obrigatorios.');
        }

        $telefoneNumeros = preg_replace('/\D+/', '', $telefone) ?? '';
        if (strlen($telefoneNumeros) < 10) {
          throw new RuntimeException('Informe um telefone valido para contato com DDD.');
        }

        if (!in_array($preferenciaDocumentos, ['anexar', 'presencial'], true)) {
            throw new RuntimeException('Escolha valida para envio de documentos.');
        }

        if ($tipoInscricao === 'batismo') {
            if ($paisBatizados !== 'sim' || $padrinhosBatizados !== 'sim') {
                throw new RuntimeException('Para inscricao de batismo, pais e padrinhos precisam ser batizados (resposta SIM).');
            }

          // Batismo exige anexos obrigatorios para todos os documentos.
          $preferenciaDocumentos = 'anexar';

          foreach ($documentosObrigatoriosBatismo as $campoArquivo => $rotulo) {
            $arquivo = $_FILES[$campoArquivo] ?? null;
            if (!is_array($arquivo) || (int) ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
              throw new RuntimeException('No batismo, o documento "' . $rotulo . '" e obrigatorio.');
            }
          }
        } else {
            $nomePai = '';
            $nomeMae = '';
            $paisBatizados = null;
            $padrinhosBatizados = null;
        }

        $arquivos = $_FILES['documentos'] ?? null;
        $temArquivo = is_array($arquivos) && !empty($arquivos['name'][0]);

        if ($preferenciaDocumentos === 'anexar' && !$temArquivo) {
            throw new RuntimeException('Voce escolheu anexar os documentos. Envie pelo menos um arquivo.');
        }

        $pdo = get_db_connection();
        $pdo->beginTransaction();

        $statusDocumentos = $preferenciaDocumentos === 'anexar' ? 'pendente_validacao' : 'entrega_presencial';
        $statusInscricao = 'recebida';

        $stmt = $pdo->prepare(
            'INSERT INTO inscricoes (
                tipo_inscricao, nome_responsavel, email, telefone, nome_inscrito,
                nome_pai, nome_mae, pais_batizados, padrinhos_batizados,
                data_batismo_prevista, preferencia_documentos, status_documentos,
                status_inscricao, observacoes
            ) VALUES (
                :tipo_inscricao, :nome_responsavel, :email, :telefone, :nome_inscrito,
                :nome_pai, :nome_mae, :pais_batizados, :padrinhos_batizados,
                :data_batismo_prevista, :preferencia_documentos, :status_documentos,
                :status_inscricao, :observacoes
            )'
        );

        $stmt->execute([
            'tipo_inscricao' => $tipoInscricao,
            'nome_responsavel' => $nomeResponsavel,
            'email' => $email,
            'telefone' => $telefone,
            'nome_inscrito' => $nomeInscrito,
            'nome_pai' => $nomePai,
            'nome_mae' => $nomeMae,
            'pais_batizados' => $paisBatizados,
            'padrinhos_batizados' => $padrinhosBatizados,
            'data_batismo_prevista' => $tipoInscricao === 'batismo' ? $dataBatismoPrevista : null,
            'preferencia_documentos' => $preferenciaDocumentos,
            'status_documentos' => $statusDocumentos,
            'status_inscricao' => $statusInscricao,
            'observacoes' => $observacoes,
        ]);

        $inscricaoId = (int) $pdo->lastInsertId();

        $permitidos = ['pdf', 'jpg', 'jpeg', 'png'];
        $maxBytes = 5 * 1024 * 1024;
        $uploadDir = __DIR__ . '/uploads/inscricoes/' . $inscricaoId;

        $garantirPastaUpload = static function () use ($uploadDir): void {
          if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            throw new RuntimeException('Nao foi possivel criar pasta para upload.');
          }
        };

        $salvarArquivo = static function (array $arquivo, string $descricao) use ($permitidos, $maxBytes, $uploadDir, $pdo, $inscricaoId, $garantirPastaUpload): void {
          if ((int) ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Erro no envio do documento "' . $descricao . '".');
          }

          $nomeOriginal = (string) ($arquivo['name'] ?? '');
          $tmpName = (string) ($arquivo['tmp_name'] ?? '');
          $tamanho = (int) ($arquivo['size'] ?? 0);
          $ext = mb_strtolower((string) pathinfo($nomeOriginal, PATHINFO_EXTENSION));

          if (!in_array($ext, $permitidos, true)) {
            throw new RuntimeException('Formato nao permitido em "' . $descricao . '". Use PDF, JPG, JPEG ou PNG.');
          }

          if ($tamanho > $maxBytes) {
            throw new RuntimeException('O documento "' . $descricao . '" deve ter no maximo 5MB.');
          }

          $garantirPastaUpload();

          $novoNome = bin2hex(random_bytes(12)) . '.' . $ext;
          $destino = $uploadDir . '/' . $novoNome;

          if (!move_uploaded_file($tmpName, $destino)) {
            throw new RuntimeException('Falha ao salvar o documento "' . $descricao . '".');
          }

          $stmtAnexo = $pdo->prepare(
            'INSERT INTO inscricao_documentos (inscricao_id, nome_original, caminho_arquivo, tamanho_bytes, tipo_mime)
             VALUES (:inscricao_id, :nome_original, :caminho_arquivo, :tamanho_bytes, :tipo_mime)'
          );

          $stmtAnexo->execute([
            'inscricao_id' => $inscricaoId,
            'nome_original' => $descricao . ' - ' . $nomeOriginal,
            'caminho_arquivo' => 'uploads/inscricoes/' . $inscricaoId . '/' . $novoNome,
            'tamanho_bytes' => $tamanho,
            'tipo_mime' => (string) ($arquivo['type'] ?? ''),
          ]);
        };

        if ($tipoInscricao === 'batismo') {
          foreach ($documentosObrigatoriosBatismo as $campoArquivo => $rotulo) {
            $arquivo = $_FILES[$campoArquivo] ?? null;
            if (!is_array($arquivo)) {
              throw new RuntimeException('Documento obrigatorio ausente: ' . $rotulo . '.');
            }

            $salvarArquivo($arquivo, $rotulo);
          }
        } elseif ($temArquivo) {
          for ($i = 0; $i < count($arquivos['name']); $i++) {
            if ((int) $arquivos['error'][$i] === UPLOAD_ERR_NO_FILE) {
              continue;
            }

            $salvarArquivo([
              'name' => (string) $arquivos['name'][$i],
              'tmp_name' => (string) $arquivos['tmp_name'][$i],
              'size' => (int) $arquivos['size'][$i],
              'type' => (string) ($arquivos['type'][$i] ?? ''),
              'error' => (int) $arquivos['error'][$i],
            ], 'Documento geral');
          }
        }

        $pdo->commit();
        $sucesso = 'Inscricao enviada com sucesso. A secretaria fara o acompanhamento.';
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $erro = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inscricao | Paroquia SMG</title>
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
        <li><a href="novenas.html">Novenas</a></li>
        <li><a href="oracoes.html">Orações</a></li>
        <li><a href="documentos-igreja.html">Documentos</a></li>
        <li><a href="teologia.html">Teologia</a></li>
        <li><a href="quiz.html">Quiz</a></li>
        <li><a href="index.html#contato">Contato</a></li>
      </ul>
    </nav>
  </header>

  <header class="subheader">
    <h1>Inscricao de Sacramentos</h1>
  </header>

  <section class="section light">
    <div class="container" style="max-width: 820px;">
      <div class="card">
        <h2>Formulario de inscricao</h2>
        <p>Batismos acontecem no ultimo domingo do mes. Para batismo, as respostas sobre pais e padrinhos precisam ser SIM.</p>

        <?php if ($erro): ?>
          <div class="alert alert-error"><?= h($erro) ?></div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
          <div class="alert alert-success"><?= h($sucesso) ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="form-grid" id="form-inscricao">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

          <label for="tipo_inscricao">Tipo de inscricao</label>
          <select name="tipo_inscricao" id="tipo_inscricao" required>
            <option value="batismo">Batismo</option>
            <option value="casamento">Casamento</option>
          </select>

          <label for="nome_responsavel">Nome do responsavel</label>
          <input id="nome_responsavel" name="nome_responsavel" required>

          <label for="email">E-mail</label>
          <input id="email" name="email" type="email" required>

          <label for="telefone">Telefone</label>
          <input id="telefone" name="telefone" type="tel" inputmode="tel" pattern="[0-9\s\-\(\)\+]{10,}" title="Informe telefone com DDD" required>

          <label for="nome_inscrito">Nome da crianca/casal</label>
          <input id="nome_inscrito" name="nome_inscrito" required>

          <div id="campos-batismo">
            <label for="nome_pai">Nome do pai</label>
            <input id="nome_pai" name="nome_pai">

            <label for="nome_mae">Nome da mae</label>
            <input id="nome_mae" name="nome_mae">

            <label for="pais_batizados">Pais sao batizados?</label>
            <select id="pais_batizados" name="pais_batizados">
              <option value="">Selecione</option>
              <option value="sim">Sim</option>
              <option value="nao">Nao</option>
            </select>

            <label for="padrinhos_batizados">Padrinhos sao batizados?</label>
            <select id="padrinhos_batizados" name="padrinhos_batizados">
              <option value="">Selecione</option>
              <option value="sim">Sim</option>
              <option value="nao">Nao</option>
            </select>

            <label for="data_batismo_prevista">Data prevista do batismo (ultimo domingo)</label>
            <input id="data_batismo_prevista" type="date" value="<?= h($dataBatismoPrevista) ?>" disabled>
          </div>

          <div id="preferencia-documentos-wrapper">
            <label for="preferencia_documentos">Documentos</label>
            <select id="preferencia_documentos" name="preferencia_documentos" required>
              <option value="presencial">Prefiro levar presencialmente na secretaria</option>
              <option value="anexar">Quero anexar agora</option>
            </select>
          </div>

          <div id="bloco-documentos-batismo" class="hidden">
            <h3 style="margin-top: 1rem;">Documentos obrigatorios para batismo</h3>

            <label for="doc_batisterio_padrinho">Batisterio do padrinho</label>
            <input id="doc_batisterio_padrinho" name="doc_batisterio_padrinho" type="file" accept=".pdf,.jpg,.jpeg,.png">

            <label for="doc_batisterio_madrinha">Batisterio da madrinha</label>
            <input id="doc_batisterio_madrinha" name="doc_batisterio_madrinha" type="file" accept=".pdf,.jpg,.jpeg,.png">

            <label for="doc_rg_madrinha">RG da madrinha</label>
            <input id="doc_rg_madrinha" name="doc_rg_madrinha" type="file" accept=".pdf,.jpg,.jpeg,.png">

            <label for="doc_rg_padrinho">RG do padrinho</label>
            <input id="doc_rg_padrinho" name="doc_rg_padrinho" type="file" accept=".pdf,.jpg,.jpeg,.png">

            <label for="doc_certidao_crianca">Certidao de nascimento da crianca</label>
            <input id="doc_certidao_crianca" name="doc_certidao_crianca" type="file" accept=".pdf,.jpg,.jpeg,.png">

            <label for="doc_rg_pai">RG do pai</label>
            <input id="doc_rg_pai" name="doc_rg_pai" type="file" accept=".pdf,.jpg,.jpeg,.png">

            <label for="doc_rg_mae">RG da mae</label>
            <input id="doc_rg_mae" name="doc_rg_mae" type="file" accept=".pdf,.jpg,.jpeg,.png">

            <label for="doc_comprovante_endereco">Comprovante de endereco da familia</label>
            <input id="doc_comprovante_endereco" name="doc_comprovante_endereco" type="file" accept=".pdf,.jpg,.jpeg,.png">
          </div>

          <div id="bloco-documentos-geral" class="hidden">
            <label for="documentos">Anexar documentos (PDF/JPG/PNG, max. 5MB cada)</label>
            <input id="documentos" name="documentos[]" type="file" accept=".pdf,.jpg,.jpeg,.png" multiple>
          </div>

          <label for="observacoes">Observacoes</label>
          <textarea id="observacoes" name="observacoes" rows="4"></textarea>

          <button type="submit" class="btn">Enviar inscricao</button>
        </form>

        <p style="margin-top: 1rem;">
          <a href="index.html" class="btn btn-outline" style="color: var(--gold-dark); border-color: var(--gold-dark);">Voltar ao site</a>
        </p>
      </div>
    </div>
  </section>

  <script>
    const tipo = document.getElementById('tipo_inscricao');
    const camposBatismo = document.getElementById('campos-batismo');
    const preferenciaWrapper = document.getElementById('preferencia-documentos-wrapper');
    const prefDocs = document.getElementById('preferencia_documentos');
    const blocoDocumentosBatismo = document.getElementById('bloco-documentos-batismo');
    const blocoDocumentosGeral = document.getElementById('bloco-documentos-geral');
    const inputDocs = document.getElementById('documentos');
    const docsBatismoInputs = blocoDocumentosBatismo.querySelectorAll('input[type="file"]');

    function atualizarVisibilidade() {
      const ehBatismo = tipo.value === 'batismo';
      camposBatismo.classList.toggle('hidden', !ehBatismo);

      document.getElementById('pais_batizados').required = ehBatismo;
      document.getElementById('padrinhos_batizados').required = ehBatismo;
      document.getElementById('nome_pai').required = ehBatismo;
      document.getElementById('nome_mae').required = ehBatismo;

      preferenciaWrapper.classList.toggle('hidden', ehBatismo);
      blocoDocumentosBatismo.classList.toggle('hidden', !ehBatismo);

      if (ehBatismo) {
        prefDocs.value = 'anexar';
        docsBatismoInputs.forEach((input) => {
          input.required = true;
        });
      } else {
        docsBatismoInputs.forEach((input) => {
          input.required = false;
          input.value = '';
        });
      }

      atualizarDocumentos();
    }

    function atualizarDocumentos() {
      const ehBatismo = tipo.value === 'batismo';
      const precisaAnexo = prefDocs.value === 'anexar';

      blocoDocumentosGeral.classList.toggle('hidden', ehBatismo || !precisaAnexo);
      inputDocs.required = !ehBatismo && precisaAnexo;

      if (ehBatismo) {
        inputDocs.required = false;
        inputDocs.value = '';
      }
    }

    tipo.addEventListener('change', atualizarVisibilidade);
    prefDocs.addEventListener('change', atualizarDocumentos);
    atualizarVisibilidade();
    atualizarDocumentos();
  </script>
  <script src="js/app.js"></script>
</body>
</html>
