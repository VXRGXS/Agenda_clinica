<?php
/**
 * Inicialização comum a todas as páginas.
 *
 * Configuração, sessão segura, conexão PDO, CSRF, mensagens flash, controle de acesso por
 * perfil e utilitários de data em português. Incluído com require no topo de cada página.
 */
declare(strict_types=1);
date_default_timezone_set('America/Sao_Paulo');
session_name('agenda_clinica');   // nome próprio: não colide com outros sistemas do mesmo domínio
session_set_cookie_params(['path' => '/', 'httponly' => true, 'samesite' => 'Lax',
  'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')]);
session_start();

/** Lê uma chave da configuração (config.php), carregada uma única vez. */
function cfg(string $k) {
  static $c = null;
  if ($c === null) {
    $arq = __DIR__ . '/../config.php';
    if (!is_file($arq)) { http_response_code(500); exit('config.php não encontrado. Copie config.example.php para config.php e preencha os dados (veja README.md).'); }
    $c = require $arq;
  }
  return $c[$k] ?? null;
}

/** Conexão PDO única (MySQL, utf8mb4). Exceções ativadas e fuso -03:00 na sessão do banco. */
function pdo(): PDO {
  static $pdo = null;
  if ($pdo === null) {
    $pdo = new PDO('mysql:host=' . cfg('db_host') . ';dbname=' . cfg('db_name') . ';charset=utf8mb4',
      cfg('db_user'), cfg('db_pass'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '-03:00'",   // NOW()/CURDATE() no horário de Brasília, igual ao PHP
      ]);
  }
  return $pdo;
}

/** URL pública da aplicação, sem barra final (ex.: https://seudominio.com.br/agenda_clinica). Funciona em subpasta e em localhost. */
function url_base(): string {
  if (cfg('base_url')) return rtrim((string)cfg('base_url'), '/');
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
  $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
  $dir = preg_replace('#/public$#', '', $dir);   // quando o .htaccess serve a pasta public como raiz
  return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir;
}

/** Escapa texto para HTML (proteção contra XSS). Use em TODA saída vinda do usuário ou do banco. */
function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** Token CSRF da sessão (criado sob demanda). */
function csrf_token(): string {
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
  return $_SESSION['csrf'];
}
/** Campo hidden com o token CSRF, para colocar dentro de cada <form method="post">. */
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
/** Valida o token CSRF do POST; encerra com 400 se for inválido. */
function csrf_check(): void {
  if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(400); exit('Requisição inválida.'); }
}

/** Mensagem de uma só exibição. flash($msg, $tipo) grava; flash() sem argumentos lê e apaga. */
function flash(?string $msg = null, string $tipo = 'success'): ?array {
  if ($msg !== null) { $_SESSION['flash'] = [$msg, $tipo]; return null; }
  $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}

/** Usuário logado: [id, nome, perfil] ou null. */
function usuario(): ?array { return $_SESSION['usuario'] ?? null; }

// Controle de acesso por perfil (R01/R09)
function exigir_perfil(string ...$perfis): array {
  $u = usuario();
  if (!$u) { header('Location: index.php'); exit; }
  if ($perfis && !in_array($u['perfil'], $perfis, true)) { http_response_code(403); exit('Acesso negado.'); }
  return $u;
}

/** Página inicial de cada perfil, usada após o login. */
function home_do_perfil(string $perfil): string {
  return ['paciente' => 'minhas_consultas.php', 'profissional' => 'profissional_horarios.php', 'recepcao' => 'painel.php'][$perfil] ?? 'index.php';
}

/* ---------- datas em português ---------- */
const MESES = ['janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'];
const DIAS  = ['domingo','segunda-feira','terça-feira','quarta-feira','quinta-feira','sexta-feira','sábado'];
/** Ex.: "18 de março, terça-feira". */
function data_curta(int $ts): string { return date('j', $ts) . ' de ' . MESES[(int)date('n', $ts) - 1] . ', ' . DIAS[(int)date('w', $ts)]; }
/** Ex.: "18 de março de 2026". */
function data_extenso(int $ts): string { return date('j', $ts) . ' de ' . MESES[(int)date('n', $ts) - 1] . ' de ' . date('Y', $ts); }
/** Ex.: "Terça-feira, 18 de março". */
function dia_extenso(int $ts): string { return ucfirst(DIAS[(int)date('w', $ts)]) . ', ' . date('j', $ts) . ' de ' . MESES[(int)date('n', $ts) - 1]; }
/** Iniciais para o avatar (ignora "Dr."/"Dra."). Ex.: "Dra. Helena Martins" => "HM". */
function iniciais(string $nome): string {
  $p = preg_split('/\s+/', trim(preg_replace('/^(Dra?\.)\s*/u', '', $nome)));
  return mb_strtoupper(mb_substr($p[0], 0, 1) . (count($p) > 1 ? mb_substr(end($p), 0, 1) : ''));
}
