# Deploy no cPanel (dominio paroquiasmg.com.br)

## 1) Banco de dados MySQL
1. No cPanel, abra phpMyAdmin e execute o SQL de [database/schema.sql](database/schema.sql).
2. Antes do INSERT do usuario, gere um hash seguro para a senha:
   - `php -r "echo password_hash('SUA_SENHA_FORTE', PASSWORD_DEFAULT), PHP_EOL;"`
3. Substitua `AQUI_HASH_SEGURO` no SQL pelo hash gerado.

## 2) Conexao compartilhada com public_html
O sistema tenta reutilizar automaticamente o arquivo de conexao em caminhos comuns:
- `../public_html/db.php`
- `../public_html/conexao.php`

Se nao encontrar, configure as variaveis de ambiente no cPanel:
- `DB_HOST`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`

## 3) Upload para o FTP correto
Dominio alvo:
- `paroquiasmg.com.br`

Document Root informado:
- `/paroquiasmg.com.br`

Ao conectar no FTP compartilhado, envie o conteudo deste projeto para a pasta:
- `paroquiasmg.com.br/`

Mantenha estes arquivos/pastas novos no servidor:
- `login.php`
- `logout.php`
- `inscricao.php`
- `auth/`
- `secretaria/`
- `database/schema.sql` (opcional manter em producao)
- `uploads/.htaccess`

## 4) Permissoes recomendadas
- Pastas: `755`
- Arquivos: `644`
- Pasta de upload `uploads/inscricoes`: escrita pelo servidor web (normalmente `755` ja funciona em cPanel).

## 5) URLs finais
- Formulario publico: `/inscricao.php`
- Login da secretaria: `/login.php`
- Painel da secretaria: `/secretaria/index.php`

## 6) Seguranca
- Nao deixe senha real escrita em codigo.
- Se a senha FTP foi compartilhada em local inseguro, troque no cPanel.
