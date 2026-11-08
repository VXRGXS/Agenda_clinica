<?php
/**
 * Painel da recepção: agenda do dia por profissional (R08).
 *
 * Perfil: recepção. Uma coluna por profissional, com os horários de atendimento em
 * branco e os agendamentos posicionados por horário/duração. Navegação por data e busca
 * por nome de paciente (parâmetros GET data e q).
 */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/ui.php';
require __DIR__ . '/../includes/agenda.php';
exigir_perfil('recepcao');
$pdo = pdo();

$data = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data'] ?? '') && strtotime($_GET['data'])) ? $_GET['data'] : date('Y-m-d');
$q = trim($_GET['q'] ?? '');
$A = agenda_do_dia($pdo, $data, $q);
$ts = strtotime($data);
$mins = fn(string $hm) => (int)substr($hm, 0, 2) * 60 + (int)substr($hm, 3, 2);

// Faixa de horas exibida: 08:00-18:00, ampliada se houver atendimento fora dela.
$ini = 480; $fim = 1080;
foreach ($A['janelas'] as $js) foreach ($js as [$i, $f]) { $ini = min($ini, $mins($i)); $fim = max($fim, $mins($f)); }
foreach ($A['ags'] as $lista) foreach ($lista as $r) { $m = $mins(substr($r['data_hora'], 11, 5)); $ini = min($ini, $m); $fim = max($fim, $m + (int)$r['duracao_min']); }
$ini = intdiv($ini, 30) * 30; $fim = (int)(ceil($fim / 30) * 30);
$ppm = 56 / 30; $altura = (int)round(($fim - $ini) * $ppm);   // 56 px por meia hora
$n = max(1, count($A['profs']));
$nav = fn(string $d) => 'painel.php?' . http_build_query(array_filter(['data' => $d, 'q' => $q]));

desk_open('Agenda', 'agenda', true); ?>
<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:22px">
  <div><h1>Agenda do dia</h1><p class="sub" style="margin:0">Gerencie os atendimentos da clínica.</p></div>
  <a class="btn sm" href="recepcao_agendar.php?data=<?= e($data) ?>"><?= icon('plus') ?>Novo agendamento</a>
</div>

<form method="get" class="toolbar">
  <a class="navbtn l" href="<?= e($nav(date('Y-m-d', strtotime('-1 day', $ts)))) ?>" aria-label="Dia anterior"><?= icon('left') ?></a>
  <label class="datebox"><?= icon('cal') ?><span><small>DATA SELECIONADA</small><strong><?= e(dia_extenso($ts)) ?></strong></span>
    <input type="date" name="data" value="<?= e($data) ?>" onchange="this.form.submit()" aria-label="Escolher data"></label>
  <a class="navbtn r" href="<?= e($nav(date('Y-m-d', strtotime('+1 day', $ts)))) ?>" aria-label="Próximo dia"><?= icon('right') ?></a>
  <a class="navbtn today" href="<?= e($nav(date('Y-m-d'))) ?>">Hoje</a>
  <span class="count"><i></i><?= (int)$A['total'] ?> agendamento<?= $A['total'] === 1 ? '' : 's' ?></span>
  <label class="search"><?= icon('search', 18) ?><input name="q" value="<?= e($q) ?>" placeholder="Buscar paciente"></label>
</form>

<div class="pwrap"><div class="pgrid" style="grid-template-columns:70px repeat(<?= $n ?>,minmax(260px,1fr))">
  <div class="phead tm">HORÁRIO</div>
  <?php foreach ($A['profs'] as $i => $p): ?>
    <div class="phead"><span class="avatar av-<?= ($i % 3) + 1 ?>"><?= e(iniciais($p['nome'])) ?></span><span><strong><?= e($p['nome']) ?></strong><small><?= e($p['especialidade']) ?></small></span><i class="dot <?= empty($A['janelas'][$p['id']]) ? 'off' : '' ?>" title="<?= empty($A['janelas'][$p['id']]) ? 'Sem atendimento neste dia' : 'Atende neste dia' ?>"></i></div>
  <?php endforeach; ?>

  <div class="ptime" style="height:<?= $altura ?>px">
    <?php for ($m = $ini; $m < $fim; $m += 30): ?><span style="top:<?= (int)round(($m - $ini) * $ppm) ?>px"><?= sprintf('%02d:%02d', intdiv($m, 60), $m % 60) ?></span><?php endfor; ?>
  </div>
  <?php foreach ($A['profs'] as $i => $p): ?>
    <div class="pcol" style="height:<?= $altura ?>px">
      <?php foreach ($A['janelas'][$p['id']] ?? [] as [$a, $b]): ?>
        <div class="work" style="top:<?= (int)round(($mins($a) - $ini) * $ppm) ?>px;height:<?= (int)round(($mins($b) - $mins($a)) * $ppm) ?>px"></div>
      <?php endforeach; ?>
      <?php foreach ($A['ags'][$p['id']] ?? [] as $r):
        $h = substr($r['data_hora'], 11, 5); $m = $mins($h); $fimH = sprintf('%02d:%02d', intdiv($m + (int)$r['duracao_min'], 60), ($m + (int)$r['duracao_min']) % 60);
        $cls = $r['tipo'] === 'retorno' ? 'ev-r' : 'ev-' . (($i % 3) + 1); ?>
        <a class="ev <?= $cls ?> <?= e($r['status']) ?>" href="historico_paciente.php?id=<?= (int)$r['paciente_id'] ?>"
           style="top:<?= (int)round(($m - $ini) * $ppm) + 2 ?>px;height:<?= (int)round((int)$r['duracao_min'] * $ppm) - 4 ?>px">
          <strong><?= e($r['paciente']) ?></strong>
          <span><?= e(TIPOS[$r['tipo']] ?? '') ?><?= $r['status'] === 'realizado' ? ' · Realizada' : ($r['status'] === 'faltou' ? ' · Faltou' : '') ?></span>
          <em><?= e($h) ?> – <?= e($fimH) ?></em>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</div></div>
<?php desk_close();
