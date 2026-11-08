<?php
/**
 * Teste de envio de e-mail.
 *
 * Perfil: recepção. Envia uma mensagem de teste e mostra o motivo exato quando falha
 * (útil para configurar o SMTP na hospedagem).
 */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/ui.php';
require __DIR__ . '/../includes/mailer.php';
exigir_perfil('recepcao');
$res = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $para = trim($_POST['para'] ?? '');
  if (!filter_var($para, FILTER_VALIDATE_EMAIL)) { $res = [false, 'Informe um e-mail válido.']; }
  else { $ok = enviar_email($para, 'Teste de e-mail — AgendaClínica', "Se você recebeu esta mensagem, o envio de e-mail do sistema está funcionando.\r\nEnviado em " . date('d/m/Y H:i') . '.', $erro); $res = [$ok, $ok ? 'Mensagem aceita pelo servidor de e-mail. Verifique a caixa de entrada (e o spam).' : $erro]; }
}
desk_open('Teste de e-mail', 'agenda'); ?>
<h1>Teste de e-mail</h1>
<p class="sub">Ambiente: <strong><?= e(cfg('ambiente')) ?></strong> · Servidor SMTP: <strong><?= e(cfg('smtp_host') ?: 'não configurado (usa mail() do PHP)') ?></strong><?= cfg('smtp_host') ? ' · porta ' . (int)cfg('smtp_port') . ' (' . e(cfg('smtp_secure')) . ')' : '' ?></p>
<?php if ($res): ?><div class="alert <?= $res[0] ? 'success' : 'danger' ?>" style="margin:0 0 16px"><?= e($res[1]) ?></div><?php endif; ?>
<form method="post" class="dcard" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap"><?= csrf_field() ?>
  <div style="flex:1;min-width:240px"><label style="font-size:12px;font-weight:600;color:var(--muted);display:block;margin-bottom:6px">Enviar teste para</label><input class="input" type="email" name="para" placeholder="seu@email.com" required></div>
  <button class="btn sm" style="height:50px">Enviar teste</button>
</form>
<?php desk_close();
