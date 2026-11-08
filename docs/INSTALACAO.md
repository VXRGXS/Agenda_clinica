# Instalação

Requisitos: PHP 7.4+ (8.x recomendado) com `pdo_mysql`, `mbstring` e `openssl`; MySQL 5.7+ / MariaDB 10.2+; Apache com `mod_rewrite` (para hospedagem em subpasta).

## 1. Local

**Servidor embutido do PHP**
```bash
cp config.example.php config.php            # ajuste o bloco $local
mysql -u root -e "CREATE DATABASE agendamento CHARACTER SET utf8mb4"
mysql -u root agendamento < database/schema.sql
php database/seed.php
php -S localhost:8000 -t public
```

**XAMPP/WAMP:** copie o projeto para `htdocs/agenda_clinica` e abra `http://localhost/agenda_clinica/` (o `.htaccess` da raiz serve a pasta `public/`).

E-mail em localhost: deixe `smtp_host` vazio. O sistema tenta o `mail()` do PHP e registra o resultado em `storage/emails.log`. Para testar de verdade, preencha o SMTP também no bloco `$local`.

Banco criado antes da Fase 2? Rode `database/migracao_v3.sql` e depois `php database/seed_fase2.php`.

## 2. Hospedagem cPanel (subpasta, ex.: `https://seudominio.com.br/agenda_clinica/`)

1. **Banco** (cPanel › *Bancos de Dados MySQL*): crie banco e usuário (o nome real leva o prefixo da conta) e dê *todos os privilégios*. No *phpMyAdmin*, importe `database/schema_cpanel.sql` e, se quiser dados de demonstração, `database/dados_demo.sql` (uma única vez).
2. **E-mail** (cPanel › *Contas de E-mail*): crie, por exemplo, `agenda@seudominio.com.br`. Em *Conectar dispositivos*, confira o servidor de saída e a porta SSL (465).
3. **Configuração:** copie `config.example.php` para `config.php` e preencha **só o bloco `$producao`** (banco e SMTP). O bloco `$local` continua valendo no seu computador.
4. **Arquivos:** no *Gerenciador de Arquivos*, crie a pasta `agenda_clinica` no domínio e envie o projeto para dentro dela (ative *Mostrar arquivos ocultos* para conferir o `.htaccess`). Resultado esperado: `agenda_clinica/.htaccess`, `config.php`, `includes/`, `public/`, `storage/`, `database/`. A pasta `storage/` precisa aceitar escrita (755; se o log não for gravado, 775).
5. **PHP:** em *MultiPHP Manager*, deixe o domínio em PHP 8.x.
6. **Teste:** abra `https://seudominio.com.br/agenda_clinica/`, entre como recepção e acesse `/diagnostico_email.php` para enviar um e-mail de teste.

### Problemas comuns
| Sintoma | O que fazer |
|---|---|
| Página em branco ou erro 500 | Veja o `error_log` da pasta; confirme PHP ≥ 7.4 e que o `.htaccess` foi enviado |
| `config.php não encontrado` | Copie `config.example.php` para `config.php` |
| Erro de conexão com o banco | Confira prefixo no nome/usuário e a senha do bloco `$producao` |
| Página sem CSS | Acesse com a barra final: `/agenda_clinica/` |
| E-mail: usuário ou senha recusados | `smtp_user` é o endereço completo; use a senha da conta de e-mail |
| E-mail: não foi possível conectar | Teste a porta 587 com `smtp_secure => 'tls'`; confirme `smtp_host` |
| E-mail: erro de certificado | `smtp_verify_cert => false` (comum ao usar `localhost` como servidor) |
| E-mail cai no spam | cPanel › *Entregabilidade de E-mail*: ative SPF e DKIM |

### Depois da demonstração
Coloque `'demo' => false` em `config.php` e troque as senhas ou apague os dados de demonstração.
