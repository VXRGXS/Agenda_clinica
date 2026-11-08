# AgendaClínica

Plataforma web de **agendamento online para consultórios de pequeno porte**, com prevenção de conflitos de horário, confirmação por e-mail, histórico de atendimentos e painel da recepção.
Desenvolvida como protótipo no Trabalho de Conclusão de Curso de Sistemas de Informação. **Todos os dados do repositório são fictícios.**

> Protótipo de interface (Figma): https://atlas-audio-03017971.figma.site

## O que o sistema faz

| Perfil | Funcionalidades |
|---|---|
| **Paciente** | Cadastro (com consentimento LGPD), login, agendamento em 3 passos (profissional → data → horário), cancelar e reagendar, lista de consultas, confirmação por e-mail |
| **Profissional** | Define seus horários de atendimento, registra atendimentos realizados (entram no histórico do paciente) e marca faltas |
| **Recepção** | Painel com a agenda do dia por profissional, busca de pacientes, histórico do paciente, agendar/cancelar/reagendar consultas e editar o cadastro do paciente |

**Destaque técnico — sem agendamentos duplicados:** a disponibilidade é recalculada no servidor a cada tentativa e, além disso, o banco de dados impede por restrição (`UNIQUE`) dois agendamentos ativos no mesmo horário do mesmo profissional, inclusive com requisições simultâneas. Detalhes em [docs/ARQUITETURA.md](docs/ARQUITETURA.md).

## Tecnologias
PHP 7.4+ (8.x recomendado, sem framework e sem Composer) · MySQL 5.7+/MariaDB 10.2+ · HTML/CSS/JavaScript puros · SMTP próprio (sem bibliotecas) · Apache com `mod_rewrite`.

## Instalação rápida (local)

```bash
git clone <url-do-repositorio> agenda_clinica && cd agenda_clinica
cp config.example.php config.php          # ajuste o bloco $local (banco)
mysql -u root -e "CREATE DATABASE agendamento CHARACTER SET utf8mb4"
mysql -u root agendamento < database/schema.sql
php database/seed.php                     # dados fictícios (senha de todos: Senha@123)
php -S localhost:8000 -t public           # abra http://localhost:8000
```

Publicação em hospedagem cPanel (subpasta, SMTP, problemas comuns): [docs/INSTALACAO.md](docs/INSTALACAO.md).

### Usuários de demonstração (senha `Senha@123`)
| Perfil | E-mail |
|---|---|
| Paciente | `marina.duarte@exemplo.com` |
| Profissional | `helena.martins@exemplo.com` |
| Recepção | `ana.melo@exemplo.com` |

> Em um ambiente público, desligue os atalhos de demonstração (`'demo' => false` em `config.php`) e troque ou apague essas contas.

## Estrutura do projeto

```
├── config.example.php     modelo de configuração (copie para config.php — não versionado)
├── .htaccess              serve public/ como raiz e bloqueia pastas internas (hospedagem em subpasta)
├── public/                páginas acessíveis pelo navegador (+ assets/style.css e app.js)
├── includes/              regras de negócio e componentes compartilhados
│   ├── bootstrap.php      sessão, PDO, CSRF, controle de acesso, utilitários de data
│   ├── agenda.php         horários, prevenção de conflitos, agendamento, painel
│   ├── pacientes.php      validação dos dados cadastrais e convênios
│   ├── mailer.php         envio de e-mail por SMTP
│   └── ui.php             layouts, ícones e componentes HTML
├── database/              schema.sql, schema_cpanel.sql, dados_demo.sql, seeds e migrações
├── storage/               log de e-mails (não versionado)
└── docs/                  arquitetura, instalação e roteiro de testes
```

## Documentação
- [Arquitetura, modelo de dados e fluxos](docs/ARQUITETURA.md)
- [Instalação local e em cPanel](docs/INSTALACAO.md)
- [Roteiro de testes (T01–T16)](docs/TESTES.md)

## Segurança (resumo)
Senhas com `password_hash`, consultas com PDO e *prepared statements*, proteção CSRF em todos os formulários, saída escapada contra XSS, sessão com cookie `HttpOnly`/`SameSite`, controle de acesso por perfil em cada página, consentimento LGPD registrado no cadastro e pastas internas bloqueadas no servidor. Para relatar uma vulnerabilidade, abra uma *issue* sem expor dados sensíveis.

## Limitações conhecidas
Protótipo acadêmico, não preparado para produção com dados reais: sem recuperação de senha, sem confirmação de presença pelo paciente, sem aviso de cancelamento por e-mail, cadastro de profissionais e de seus horários iniciais feito por carga de dados.

## Licença
[MIT](LICENSE)
