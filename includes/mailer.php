<?php
/**
 * Envio de e-mail por SMTP, sem bibliotecas externas.
 *
 * Suporta SSL (porta 465) e STARTTLS (587) com AUTH LOGIN. Se smtp_host estiver vazio,
 * usa o mail() do PHP. O motivo de qualquer falha é devolvido por referência em $erro.
 */

/** Lê a resposta do servidor SMTP (pode ter várias linhas). Retorna [código, texto]. */
function smtp_resposta($fp): array {
  $txt = ''; $cod = 0;
  while (($l = fgets($fp, 1024)) !== false) {
    $txt .= $l; $cod = (int)substr($l, 0, 3);
    if (strlen($l) < 4 || $l[3] === ' ') break;   // última linha da resposta
  }
  return [$cod, trim($txt)];
}
/** Envia um comando SMTP e confere se o código de resposta é um dos esperados. */
function smtp_cmd($fp, string $cmd, array $esperados, ?string &$erro): bool {
  if ($cmd !== '') fwrite($fp, $cmd . "\r\n");
  [$cod, $txt] = smtp_resposta($fp);
  if (!in_array($cod, $esperados, true)) { $erro = 'SMTP: resposta inesperada (' . $cod . ') ' . $txt; return false; }
  return true;
}

/** Envia um e-mail de texto. Retorna true se o servidor aceitou; em falha, $erro traz o motivo. */
function enviar_email(string $para, string $assunto, string $texto, ?string &$erro = null): bool {
  $erro = null;
  $de = (string)cfg('email_remetente');
  $msgId = '<' . bin2hex(random_bytes(8)) . '@' . (parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), PHP_URL_HOST) ?: 'localhost') . '>';
  $cab = ['Date: ' . date('r'), 'From: ' . $de, 'To: ' . $para, 'Subject: =?UTF-8?B?' . base64_encode($assunto) . '?=',
          'Message-ID: ' . $msgId, 'MIME-Version: 1.0', 'Content-Type: text/plain; charset=UTF-8', 'Content-Transfer-Encoding: base64'];
  $corpo = chunk_split(base64_encode($texto), 76, "\r\n");

  $host = (string)cfg('smtp_host');
  if ($host === '') {   // sem SMTP configurado: tenta o mail() do servidor
    $ok = @mail($para, '=?UTF-8?B?' . base64_encode($assunto) . '?=', $texto, "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nFrom: $de");
    if (!$ok) $erro = 'mail() do PHP não está disponível/configurado neste servidor.';
    return $ok;
  }

  $porta = (int)cfg('smtp_port'); $seg = (string)cfg('smtp_secure');
  $ctx = stream_context_create(['ssl' => ['verify_peer' => (bool)cfg('smtp_verify_cert'), 'verify_peer_name' => (bool)cfg('smtp_verify_cert'), 'allow_self_signed' => !cfg('smtp_verify_cert')]]);
  $fp = @stream_socket_client(($seg === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $porta, $en, $es, 15, STREAM_CLIENT_CONNECT, $ctx);
  if (!$fp) { $erro = "Não foi possível conectar em $host:$porta ($es)"; return false; }
  stream_set_timeout($fp, 15);
  $nome = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), PHP_URL_HOST) ?: 'localhost';
  try {
    if (!smtp_cmd($fp, '', [220], $erro)) return false;
    if (!smtp_cmd($fp, "EHLO $nome", [250], $erro)) return false;
    if ($seg === 'tls') {
      if (!smtp_cmd($fp, 'STARTTLS', [220], $erro)) return false;
      if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) { $erro = 'Falha ao iniciar TLS (STARTTLS).'; return false; }
      if (!smtp_cmd($fp, "EHLO $nome", [250], $erro)) return false;
    }
    if ((string)cfg('smtp_user') !== '') {
      if (!smtp_cmd($fp, 'AUTH LOGIN', [334], $erro)) return false;
      if (!smtp_cmd($fp, base64_encode((string)cfg('smtp_user')), [334], $erro)) return false;
      if (!smtp_cmd($fp, base64_encode((string)cfg('smtp_pass')), [235], $erro)) { $erro = 'Usuário ou senha do e-mail recusados pelo servidor.'; return false; }
    }
    if (!smtp_cmd($fp, "MAIL FROM:<$de>", [250], $erro)) return false;
    if (!smtp_cmd($fp, "RCPT TO:<$para>", [250, 251], $erro)) return false;
    if (!smtp_cmd($fp, 'DATA', [354], $erro)) return false;
    fwrite($fp, implode("\r\n", $cab) . "\r\n\r\n" . $corpo . "\r\n.\r\n");
    if (!smtp_cmd($fp, '', [250], $erro)) return false;
    smtp_cmd($fp, 'QUIT', [221], $ignorar);
    return true;
  } finally { @fclose($fp); }
}
