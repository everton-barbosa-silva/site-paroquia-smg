# Paróquia Santa Maria Goretti - Website

Este é o repositório do site estático da Paróquia Santa Maria Goretti. O site foi modernizado para oferecer uma experiência premium, com novas funcionalidades de liturgia, orações e interatividade.

## 🚀 Funcionalidades

### 1. Design Premium
- Interface moderna com cores douradas e escuras.
- Totalmente responsivo (Mobile/Desktop).
- Animações suaves e tipografia elegante.

### 2. Liturgia Diária
- Integração com API de Liturgia.
- **Novo**: Seletor de data para visualizar a liturgia de qualquer dia.

### 3. Novenas Online
- **Novena de Natal**
- **Novena de São José**
- **Novena a Santa Maria Goretti**
- Botão de compartilhamento "Benção" para redes sociais.

### 4. Orações e Espiritualidade
- **Orações Comuns**: Pai Nosso, Ave Maria, Credo, Salve Rainha.
- **Santo Terço**: Guia visual passo a passo dos mistérios.

### 5. Interatividade
- **Quiz Católico**: Jogo de perguntas e respostas com 30 questões, pontuação e compartilhamento.
- **Instagram**: Links diretos para as redes sociais da paróquia.

## 🛠️ Tecnologias
- **HTML5 & CSS3**: Estrutura e estilização (Vanilla).
- **JavaScript**: Lógica para API de liturgia, Quiz e Compartilhamento.
- **AWS S3**: Hospedagem estática.

## 📂 Estrutura de Arquivos
- `index.html`: Página inicial.
- `style.css`: Estilos globais.
- `js/`: Scripts (liturgy.js, quiz.js, share.js).
- `assets/`: Imagens e ícones.
- `novenas.html`, `oracoes.html`: Páginas de aterrissagem de conteúdo.

## 📦 Como Fazer Deploy
O site é hospedado em um bucket S3 da AWS. Para atualizar:

```bash
aws s3 sync . s3://paroquiasmg.com.br --exclude ".git/*" --exclude "README.md"
```

---
*Desenvolvido com carinho para a comunidade de Santa Maria Goretti.*
