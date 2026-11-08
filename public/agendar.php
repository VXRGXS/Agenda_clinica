<?php
/**
 * Novo agendamento do paciente, em 3 passos (R03, R04, R06).
 *
 * Perfil: paciente.
 * 1) escolhe o profissional  2) escolhe a data no calendário  3) escolhe o horário.
 * A lista de horários mostra livres e ocupados; a verificação real ocorre em
 * includes/agenda.php (criar_agendamento). Com ?reagendar=ID, a consulta anterior é
 * cancelada somente depois que o novo horário for gravado. Se o horário foi ocupado
 * nesse meio tempo, redireciona para indisponivel.php.
 */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/ui.php';
require __DIR__ . '/../includes/agenda.php';
$u = exigir_perfil('paciente');
$pdo = pdo();

// Reagendamento: consulta própria, ainda agendada e futura.
$reagId = (int)($_GET['reagendar'] ?? $_POST['reagendar'] ?? 0);
$reag = $reagId ? agendamento_do_paciente($pdo, $reagId, $u['id']) : null;
if ($reag && ($reag['status'] !== 'agendado' || strtotime($reag['data_hora']) <= time())) $reag = null;
$reagId = $reag ? $reagId : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $profId = (int)($_POST['profissional_id'] ?? 0);
  $dataHora = $_POST['data_hora'] ?? '';
  if (!empty($_POST['simular'])) {   // demonstração: tenta de verdade o 1º horário ocupado do dia
    foreach (slots_do_dia($pdo, $profId, $_POST['data'] ?? '') as $s) if ($s['ocupado']) { $dataHora = $s['hora']; break; }
  }
  $r = criar_agendamento($pdo, $u['id'], $profId, $dataHora);
  if ($r['ok']) {
    if ($reag) $pdo->prepare("UPDATE agendamentos SET status='cancelado' WHERE id = ? AND paciente_id = ? AND status='agendado'")->execute([$reagId, $u['id']]);
    $_SESSION['email_ok'][$r['id']] = enviar_confirmacao(agendamento_do_paciente($pdo, $r['id'], $u['id']));
    header('Location: confirmado.php?id=' . $r['id']); exit;
  }
  if ($r['msg'] === 'Horário inválido.') { flash('Selecione um horário.', 'danger'); header('Location: agendar.php?prof=' . $profId); exit; }
  $_SESSION['conflito'] = ['prof' => $profId, 'data_hora' => $dataHora, 'reag' => $reagId];
  header('Location: indisponivel.php'); exit;
}

$profs = $pdo->query('SELECT p.id, u.nome, p.especialidade FROM profissionais p JOIN usuarios u ON u.id = p.usuario_id ORDER BY p.id')->fetchAll();
$ids = array_column($profs, 'id');
$profId = (int)($_GET['prof'] ?? ($reag['prof_id'] ?? 0));
if (!in_array($profId, array_map('intval', $ids), true)) $profId = (int)($ids[0] ?? 0);

$mes = preg_match('/^\d{4}-\d{2}$/', $_GET['mes'] ?? '') ? $_GET['mes'] : date('Y-m');
$mesAtual = date('Y-m'); if ($mes < $mesAtual) $mes = $mesAtual;
if ($mes > date('Y-m', strtotime('+12 months'))) $mes = date('Y-m', strtotime('+12 months'));
$dias = dias_de_atendimento($pdo, $profId);
$hoje = date('Y-m-d');
$primeiro = strtotime("$mes-01"); $total = (int)date('t', $primeiro); $offset = (int)date('w', $primeiro);
$ok = fn(string $d) => $d >= $hoje && in_array((int)date('w', strtotime($d)), $dias, true);

$data = $_GET['data'] ?? '';
if (!(preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) && substr($data, 0, 7) === $mes && $ok($data))) {
  $data = ''; for ($i = 1; $i <= $total; $i++) { $d = sprintf('%s-%02d', $mes, $i); if ($ok($d)) { $data = $d; break; } }
}
$slots = $data ? slots_do_dia($pdo, $profId, $data) : [];
$haOcupado = (bool)array_filter($slots, fn($s) => $s['ocupado']);

$url = fn(array $o) => 'agendar.php?' . http_build_query(array_filter(array_merge(['prof' => $profId, 'mes' => $mes, 'data' => $data, 'reagendar' => $reagId], $o), fn($v) => $v !== null && $v !== '' && $v !== 0));
$mesAnt = date('Y-m', strtotime('-1 month', $primeiro)); $mesProx = date('Y-m', strtotime('+1 month', $primeiro));

m_open('Novo agendamento', 'minhas_consultas.php');
if ($reag) echo '<div class="alert info">Reagendando a consulta de ' . e(date('d/m', strtotime($reag['data_hora']))) . ' às ' . e(date('H:i', strtotime($reag['data_hora']))) . ' com ' . e($reag['profissional']) . '. A anterior será cancelada ao confirmar.</div>'; ?>

<h1 class="title dtitle only-d">Novo agendamento</h1>
<div class="agendar-grid"><div class="col-l">
<section class="step" style="margin-top:12px">
  <div class="step-h"><span class="step-n">1</span><div><h2>Escolha o profissional</h2><p>Selecione quem vai atender você</p></div></div>
  <?php foreach ($profs as $i => $p): ?>
    <a class="prof <?= $p['id'] == $profId ? 'sel' : '' ?>" href="<?= e($url(['prof' => (int)$p['id'], 'data' => null])) ?>">
      <span class="avatar av-<?= ($i % 3) + 1 ?>"><?= e(iniciais($p['nome'])) ?></span>
      <span><span class="nm"><?= e($p['nome']) ?></span><br><span class="esp"><?= e($p['especialidade']) ?></span></span>
      <span class="radio"></span>
    </a>
  <?php endforeach; ?>
</section>

<section class="step">
  <div class="step-h"><span class="step-n">2</span><div><h2>Escolha a data</h2><p>Datas disponíveis para consulta</p></div></div>
  <div class="cal">
    <div class="cal-h">
      <?php if ($mes > $mesAtual): ?><a href="<?= e($url(['mes' => $mesAnt, 'data' => null])) ?>" aria-label="Mês anterior"><?= icon('left') ?></a><?php else: ?><span class="dis"><?= icon('left') ?></span><?php endif; ?>
      <span><?= e(ucfirst(MESES[(int)date('n', $primeiro) - 1]) . ' ' . date('Y', $primeiro)) ?></span>
      <a href="<?= e($url(['mes' => $mesProx, 'data' => null])) ?>" aria-label="Próximo mês"><?= icon('right') ?></a>
    </div>
    <div class="cal-g">
      <?php foreach (['D','S','T','Q','Q','S','S'] as $w) echo '<span class="wd">' . $w . '</span>';
      for ($i = 0; $i < $offset; $i++) echo '<span></span>';
      for ($i = 1; $i <= $total; $i++) {
        $d = sprintf('%s-%02d', $mes, $i);
        if (!$ok($d)) echo '<span class="d off">' . $i . '</span>';
        elseif ($d === $data) echo '<span class="d sel">' . $i . '</span>';
        else echo '<a class="d" href="' . e($url(['data' => $d])) . '">' . $i . '</a>';
      } ?>
    </div>
  </div>
</section>

</div><div class="col-r">
<section class="step">
  <div class="step-h"><span class="step-n">3</span><div><h2>Escolha o horário</h2><p><?= $data ? e(dia_extenso(strtotime($data))) : 'Sem datas disponíveis neste mês' ?></p></div></div>
  <?php if (!$slots): ?>
    <p class="empty">Nenhum horário disponível para esta data.</p>
  <?php else: ?>
    <div class="legend"><span><i style="background:var(--green)"></i>Livre</span><span><i style="background:var(--red)"></i>Ocupado</span><span><i style="background:var(--primary)"></i>Selecionado</span></div>
    <form method="post"><?= csrf_field() ?>
      <input type="hidden" name="profissional_id" value="<?= $profId ?>"><input type="hidden" name="data" value="<?= e($data) ?>"><input type="hidden" name="reagendar" value="<?= $reagId ?>">
      <div class="slots">
        <?php foreach ($slots as $s): $h = substr($s['hora'], 11, 5); ?>
          <?php if ($s['ocupado']): ?><div class="slot busy"><span><?= $h ?></span></div>
          <?php else: ?><label class="slot"><input type="radio" name="data_hora" value="<?= e($s['hora']) ?>" required><span><?= $h ?></span></label><?php endif; ?>
        <?php endforeach; ?>
      </div>
      <div class="simular"><?php if ($haOcupado): ?><button class="linkbtn" name="simular" value="1" formnovalidate>Simular tentativa em horário ocupado</button><?php endif; ?></div>
      <button class="btn">Confirmar agendamento</button>
    </form>
  <?php endif; ?>
</section>
</div></div>
<?php m_close();
