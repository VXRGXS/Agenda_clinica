<?php
/**
 * MODELO de configuração. Copie para config.php e preencha (config.php NÃO vai para o GitHub).
 *
 *   cp config.example.php config.php
 *
 * O sistema detecta o ambiente sozinho: localhost/127.0.0.1/*.local/*.test usam o bloco $local;
 * qualquer outro endereço usa o bloco $producao. Em linha de comando, APP_ENV=producao força produção.
 */
// Os dois ambientes ficam no mesmo arquivo. O sistema detecta sozinho onde está rodando:
//  - localhost / 127.0.0.1 / *.local / *.test (e linha de comando) -> bloco "local"
//  - qualquer outro endereço (ex.: seudominio.com.br)           -> bloco "producao"
// Para forçar um ambiente (ex.: rodar o seed na hospedagem): APP_ENV=producao php database/seed.php

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$ehLocal = (bool)preg_match('/^(localhost|127\.0\.0\.1|\[::1\]|.+\.local|.+\.test)(:\d+)?$/i', $host);
if (getenv('APP_ENV')) $ehLocal = getenv('APP_ENV') !== 'producao';

$comum = [
  // Exibidos na confirmação e no e-mail (fictícios)
  'clinica_nome'     => 'Clínica Bem Viver',
  'clinica_endereco' => 'Rua das Acácias, 180 — Centro',
  // Mostra "Acessos de demonstração" no login. Desligue (false) depois da apresentação.
  'demo'             => true,
  // Deixe vazio para o sistema descobrir a URL sozinho (funciona em subpasta). Só preencha se algo sair errado.
  'base_url'         => '',
];

$local = [
  'db_host' => 'localhost', 'db_name' => 'agendamento', 'db_user' => 'root', 'db_pass' => '',
  // Em localhost normalmente não há servidor de e-mail: deixe smtp_host vazio (o envio é só registrado em storage/emails.log).
  'smtp_host' => '', 'smtp_port' => 465, 'smtp_secure' => 'ssl', 'smtp_user' => '', 'smtp_pass' => '', 'smtp_verify_cert' => true,
  'email_remetente' => 'nao-responda@agendaclinica.local',
];

$producao = [
  // cPanel > Bancos de Dados MySQL: o nome e o usuário levam o prefixo da conta (ex.: conta_agenda).
  'db_host' => 'localhost', 'db_name' => 'PREFIXO_agenda', 'db_user' => 'PREFIXO_agenda', 'db_pass' => 'TROQUE_PELA_SENHA_DO_BANCO',
  // cPanel > Contas de E-mail > Conectar dispositivos: use o "Servidor de saída" e a porta SSL (465).
  'smtp_host' => 'mail.seudominio.com.br', 'smtp_port' => 465, 'smtp_secure' => 'ssl',   // 'ssl' (465) ou 'tls' (587)
  'smtp_user' => 'agenda@seudominio.com.br', 'smtp_pass' => 'TROQUE_PELA_SENHA_DO_EMAIL',
  'smtp_verify_cert' => true,   // se der erro de certificado (comum ao usar "localhost" como servidor), troque para false
  'email_remetente' => 'agenda@seudominio.com.br',   // deve ser a mesma conta de e-mail criada no cPanel
];

return array_merge($comum, $ehLocal ? $local : $producao, ['ambiente' => $ehLocal ? 'local' : 'producao']);
