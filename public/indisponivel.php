<?php
/**
 * Tela "Horário indisponível" (R04).
 *
 * Perfil: paciente. Mostra o horário recusado (guardado na sessão por agendar.php)
 * e oferece voltar à escolha de horários.
 */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/ui.php';
exigir_perfil('paciente');
$c = $_SESSION['conflito'] ?? null;
if (!$c) { header('Location: agendar.php'); exit; }
$ts = strtotime($c['data_hora']);
$volta = 'agendar.php?' . http_build_query(array_filter(['prof' => $c['prof'], 'mes' => date('Y-m', $ts), 'data' => date('Y-m-d', $ts), 'reagendar' => $c['reag']]));
m_open('Novo agendamento', $volta, 'white'); ?>
<div class="result" style="padding-top:70px">
  <div class="res-ico bad"><?= icon('x', 34) ?></div>
  <h1>Horário indisponível</h1>
  <p class="msg">Esse horário acabou de ser reservado por outra pessoa. Escolha outro horário.</p>
  <div class="infocard">
    <div class="row"><?= icon('cal') ?><div><small>Data</small><strong><?= e(data_extenso($ts)) ?></strong></div></div>
    <div class="row"><?= icon('clock') ?><div><small>Horário solicitado</small><strong><?= e(date('H:i', $ts)) ?></strong></div></div>
  </div>
  <a class="btn" href="<?= e($volta) ?>">Voltar aos horários</a>
</div>
<?php m_close();
