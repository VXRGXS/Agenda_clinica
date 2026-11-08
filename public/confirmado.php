<?php
/**
 * Tela "Agendamento confirmado" (R05).
 *
 * Perfil: paciente. Exibe os dados da consulta e, só se o servidor de e-mail aceitou a
 * mensagem, a prévia do e-mail enviado (resultado guardado em $_SESSION['email_ok']).
 */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/ui.php';
require __DIR__ . '/../includes/agenda.php';
$u = exigir_perfil('paciente');
$id = (int)($_GET['id'] ?? 0);
$a = agendamento_do_paciente(pdo(), $id, $u['id']);
if (!$a) { header('Location: minhas_consultas.php'); exit; }
$ts = strtotime($a['data_hora']); $emailOk = !empty($_SESSION['email_ok'][$id]);
m_open_plain('Agendamento confirmado'); ?>
<div class="result" style="padding-top:44px">
  <div class="res-ico ok"><?= icon('check', 34) ?></div>
  <h1>Agendamento confirmado!</h1>
  <p class="msg"><?= $emailOk ? 'Está tudo certo. Enviamos a confirmação para o seu e-mail.' : 'Está tudo certo. Seu horário está reservado.' ?></p>

  <div class="apt">
    <div class="apt-top"><span class="avatar"><?= e(iniciais($a['profissional'])) ?></span>
      <div><strong><?= e($a['profissional']) ?></strong><br><small style="color:var(--muted)"><?= e($a['especialidade']) ?></small></div>
      <span class="badge"><?= icon('check') ?>Confirmada</span></div>
    <div class="apt-meta">
      <div><?= icon('cal') ?><span><small>Data</small><strong><?= e(data_extenso($ts)) ?></strong></span></div>
      <div><?= icon('clock') ?><span><small>Horário</small><strong><?= e(date('H:i', $ts)) ?></strong></span></div>
    </div>
    <div class="apt-loc"><strong><?= e(cfg('clinica_nome')) ?></strong><small><?= e(cfg('clinica_endereco')) ?></small></div>
  </div>

  <?php if ($emailOk): ?>
  <div class="mail">
    <div class="mail-h"><span class="ic"><?= icon('mail', 18) ?></span><div><strong>E-mail de confirmação enviado</strong><small>para <?= e($a['paciente_email']) ?></small></div></div>
    <div class="mail-b"><small>Assunto</small><strong style="font-size:12.5px;color:var(--ink)">Consulta confirmada — AgendaClínica</strong>
      <p style="margin:8px 0">Olá, <?= e(strtok($a['paciente_nome'], ' ')) ?>! Sua consulta com <strong><?= e($a['profissional']) ?></strong> está confirmada para <strong><?= e(date('d/m/Y', $ts)) ?> às <?= e(date('H:i', $ts)) ?></strong>.</p>
      <div class="links"><a href="minhas_consultas.php">Cancelar</a><a href="agendar.php?reagendar=<?= $id ?>">Reagendar</a></div></div>
  </div>
  <?php endif; ?>

  <a class="btn" href="minhas_consultas.php">Ver minhas consultas</a>
  <a class="under" href="agendar.php">Fazer outro agendamento</a>
</div>
<?php m_close();
