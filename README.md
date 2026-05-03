# Deovero - Fé e Espiritualidade

Este é o repositório do site Deovero, um espaço católico para aprofundar a fé com Bíblia online, orações, liturgia diária, homilias do Papa e conteúdos espirituais.

## 🚀 Funcionalidades

### 1. Bíblia Online
- Busca interativa de versículos em português (João Ferreira de Almeida).
- Interface simples para ler passagens específicas.

### 2. Liturgia Diária
- Integração com API de Liturgia.
- Seletor de data para visualizar leituras de qualquer dia.

### 3. Orações e Espiritualidade
- **Orações Comuns**: Pai Nosso, Ave Maria, Credo, Salve Rainha.
- **Santo Terço**: Guia visual passo a passo dos mistérios.

### 4. Homilia do Papa
- Texto e links oficiais das homilias mais recentes.

### 5. Conteúdos Educativos
- **Documentos da Igreja**: Textos oficiais do Magistério.
- **Teologia**: Estudos bíblicos e explicações.
- **Quiz Católico**: Jogo de perguntas e respostas com pontuação.

### 6. Minha Conta
- Login com Google para rastrear dias de leitura (Bíblia, Liturgia, Oração).
- Dados armazenados localmente no navegador.

## 🛠️ Tecnologias
- **HTML5 & CSS3**: Estrutura e estilização (Vanilla).
- **JavaScript**: Lógica para APIs de liturgia, Bíblia e autenticação.
- **AWS S3**: Hospedagem estática.

## 📂 Estrutura de Arquivos
- `index.html`: Página inicial.
- `biblia.html`: Bíblia online.
- `login.html`: Minha conta com Google.
- `oracoes.html`, `teologia.html`, etc.: Páginas de conteúdo.
- `js/`: Scripts (bible.js, google-auth.js, liturgy.js, quiz.js, share.js).
- `assets/`: Ícones e imagens.

## 📦 Como Fazer Deploy
O site é hospedado em um bucket S3 da AWS. Para atualizar:

```bash
aws s3 sync . s3://deovero.com.br --exclude ".git/*" --exclude "README.md"
```

## 🔐 Segredos e credenciais
- O arquivo `.env` não deve ser versionado. Ele já está incluído em `.gitignore`.
- Use variáveis de ambiente no servidor ou no GitHub Actions Secrets:
  - `DB_HOST`
  - `DB_PORT`
  - `DB_NAME`
  - `DB_USER`
  - `DB_PASS`
- O código atual carrega a conexão do banco via variáveis de ambiente, então as credenciais não ficam expostas no repositório.
- Para ativar o login com Google, o `GOOGLE_CLIENT_ID` já está configurado em `js/google-auth.js`.
- A **chave secreta** (client secret) não deve ser colocada em arquivos públicos ou no repositório. Ela só deve ser usada em um backend seguro se você implementar o fluxo OAuth 2.0 servidor a servidor.

---
*Desenvolvido com carinho para a comunidade católica.*
