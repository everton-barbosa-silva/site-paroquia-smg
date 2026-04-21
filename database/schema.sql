CREATE TABLE IF NOT EXISTS secretaria_usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inscricoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo_inscricao ENUM('batismo', 'casamento') NOT NULL,
    nome_responsavel VARCHAR(160) NOT NULL,
    email VARCHAR(190) NOT NULL,
    telefone VARCHAR(50) NOT NULL,
    nome_inscrito VARCHAR(180) NOT NULL,
    nome_pai VARCHAR(160) NULL,
    nome_mae VARCHAR(160) NULL,
    pais_batizados ENUM('sim', 'nao') NULL,
    padrinhos_batizados ENUM('sim', 'nao') NULL,
    data_batismo_prevista DATE NULL,
    preferencia_documentos ENUM('anexar', 'presencial') NOT NULL,
    status_documentos ENUM('pendente_validacao', 'documentos_aprovados', 'documentos_rejeitados', 'entrega_presencial') NOT NULL,
    status_inscricao ENUM('recebida', 'aprovada', 'recusada') NOT NULL DEFAULT 'recebida',
    observacoes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tipo_inscricao (tipo_inscricao),
    INDEX idx_status_documentos (status_documentos),
    INDEX idx_status_inscricao (status_inscricao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inscricao_documentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inscricao_id INT UNSIGNED NOT NULL,
    nome_original VARCHAR(255) NOT NULL,
    caminho_arquivo VARCHAR(255) NOT NULL,
    tamanho_bytes INT UNSIGNED NOT NULL,
    tipo_mime VARCHAR(120) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_documento_inscricao
      FOREIGN KEY (inscricao_id) REFERENCES inscricoes(id)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS secretaria_atendimentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assunto ENUM('item_religioso', 'batismo', 'casamento') NOT NULL,
    nome_contato VARCHAR(160) NOT NULL,
    email VARCHAR(190) NOT NULL,
    telefone VARCHAR(50) NOT NULL,
    nome_interessado VARCHAR(180) NULL,
    item_desejado VARCHAR(180) NULL,
    mensagem TEXT NULL,
    checklist_json TEXT NULL,
    deseja_agendar TINYINT(1) NOT NULL DEFAULT 0,
    status VARCHAR(40) NOT NULL DEFAULT 'novo',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_assunto (assunto),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Gere um hash com password_hash em PHP e substitua AQUI_HASH_SEGURO
-- Exemplo de hash no terminal do cPanel:
-- php -r "echo password_hash('SUA_SENHA_FORTE', PASSWORD_DEFAULT), PHP_EOL;"
INSERT INTO secretaria_usuarios (nome, email, senha_hash)
VALUES ('Secretaria', 'evbarbosa@eusousantos.com.br', 'AQUI_HASH_SEGURO');
