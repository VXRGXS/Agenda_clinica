<?php
/**
 * Lista de consultas do paciente, com cancelar e reagendar (R06).
 *
 * Perfil: paciente. Abas "Próximas" e "Anteriores". Cancelar só vale para consulta
 * futura do próprio paciente; o horário volta a ficar livre.
 */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/ui.php';
$u = exigir_perfil('paciente');
$pdo = pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  // Só cancela consulta do próprio paciente, ainda agendada e futura; o horário volta a ficar livre (slot_ativo vira NULL).
  $st = $pdo->prepare("UPDATE agendamentos SET status='cancelado' WHERE id = ? AND paciente_id = ? AND status='agendado' AND data_hora > NOW()");
  $st->execute([(int)($_POST['id'] ?? 0), $u['id']]);
  flash($st->rowCount() ? 'Consulta cancelada. O horário foi liberado.' : 'Não foi possível cancelar esta consulta.', $st->rowCount() ? 'success' : 'danger');
  header('Location: minhas_consultas.php'); exit;
}

$st = $pdo->prepare('SELECT a.id, a.data_hora, a.status, a.profissional_id, pu.nome AS profissional, p.especialidade
  FROM agendamentos a JOIN profissionais p ON p.id = a.profissional_id JOIN usuarios pu ON pu.id = p.usuario_id
  WHERE a.paciente_id = ?');
$st->execute([$u['id']]);
$prox = $ant = [];
foreach ($st->fetchAll() as $a) { if ($a['status'] === 'agendado' && strtotime($a['data_hora']) > time()) $prox[] = $a; else $ant[] = $a; }
usort($prox, fn($x, $y) => strcmp($x['data_hora'], $y['data_hora']));
usort($ant, fn($x, $y) => strcmp($y['data_hora'], $x['data_hora']));
$aba = ($_GET['aba'] ?? '') === 'anteriores' ? 'anteriores' : 'proximas';
$lista = $aba === 'proximas' ? $prox : $ant;
$rot = ['agendado' => ['Confirmada', ''], 'realizado' => ['Realizada', 'gray'], 'cancelado' => ['Cancelada', 'red'], 'faltou' => ['Faltou', 'gray']];

m_open_brand('Minhas consultas'); ?>
<div class="pad" style="padding-top:0">
  <div class="hello">Olá, <?= e(strtok($u['nome'], ' ')) ?></div>
  <div class="head-row"><h1 class="title" style="margin:2px 0 0">Minhas consultas</h1><a class="fab" href="agendar.php" aria-label="Novo agendamento"><?= icon('plus', 24) ?></a></div>
  <div class="tabs">
    <a class="<?= $aba === 'proximas' ? 'on' : '' ?>" href="minhas_consultas.php">Próximas <?php if ($prox): ?><span class="cnt"><?= count($prox) ?></span><?php endif; ?></a>
    <a class="<?= $aba === 'anteriores' ? 'on' : '' ?>" href="minhas_consultas.php?aba=anteriores">Anteriores</a>
  </div>
  <div class="cards">
  <?php if (!$lista): ?>
    <p class="empty" style="padding:24px 0"><?= $aba === 'proximas' ? 'Você não tem consultas marcadas.' : 'Nenhuma consulta anterior.' ?></p>
  <?php endif;
  $mesAtual = '';
  foreach ($lista as $a):
    $ts = strtotime($a['data_hora']); $m = mb_strtoupper(MESES[(int)date('n', $ts) - 1]) . ' ' . date('Y', $ts);
    if ($m !== $mesAtual) { echo '<div class="mes">' . e($m) . '</div>'; $mesAtual = $m; }
    [$txt, $cls] = $rot[$a['status']] ?? ['—', 'gray']; ?>
    <div class="card">
      <div class="top"><span class="doc"><?= icon('steth', 24) ?></span><div><div class="nm"><?= e($a['profissional']) ?></div><div class="esp"><?= e($a['especialidade']) ?></div></div><span class="badge <?= $cls ?>"><?= e($txt) ?></span></div>
      <div class="when"><span><?= icon('cal') ?><?= e(data_curta($ts)) ?></span><span><?= icon('clock') ?><?= e(date('H:i', $ts)) ?></span></div>
      <?php if ($aba === 'proximas'): ?>
      <div class="acts">
        <form method="post" onsubmit="return confirm('Cancelar esta consulta?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><button class="pill cancel">Cancelar</button></form>
        <a class="pill re" href="agendar.php?reagendar=<?= (int)$a['id'] ?>">Reagendar</a>
      </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  </div>
</div>
<?= tabbar('consultas') ?>
<?php m_close();
