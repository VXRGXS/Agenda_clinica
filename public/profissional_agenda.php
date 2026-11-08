<?php
/**
 * Atendimentos do profissional: registro e faltas (R07).
 *
 * Perfil: profissional.
 * "A registrar": consultas de hoje ou anteriores ainda marcadas. Registrar grava em
 * atendimentos (histórico do paciente) e marca a consulta como realizada, numa transação;
 * "Marcar falta" muda o status para faltou. Só atende consultas do próprio profissional.
 */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/ui.php';
require __DIR__ . '/../includes/agenda.php';
$u = exigir_perfil('profissional');
$pdo = pdo();
$st = $pdo->prepare('SELECT id FROM profissionais WHERE usuario_id = ?'); $st->execute([$u['id']]);
$profId = (int)($st->fetchColumn() ?: 0);
if (!$profId) exit('Perfil profissional não configurado.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  // Só atendimentos do próprio profissional, ainda agendados e já ocorridos (hoje ou antes).
  $st = $pdo->prepare("SELECT * FROM agendamentos WHERE id = ? AND profissional_id = ? AND status = 'agendado' AND DATE(data_hora) <= CURDATE()");
  $st->execute([(int)($_POST['id'] ?? 0), $profId]);
  $a = $st->fetch();
  if (!$a) { flash('Não foi possível registrar este atendimento.', 'danger'); header('Location: profissional_agenda.php'); exit; }
  if (($_POST['acao'] ?? '') === 'faltou') {
    $pdo->prepare("UPDATE agendamentos SET status='faltou' WHERE id = ?")->execute([$a['id']]);
    flash('Falta registrada.');
  } else {
    $desc = trim($_POST['descricao'] ?? '');
    if ($desc === '' || mb_strlen($desc) > 1000) { flash('Descreva o atendimento (até 1000 caracteres).', 'danger'); header('Location: profissional_agenda.php'); exit; }
    $pdo->beginTransaction();
    $pdo->prepare('INSERT INTO atendimentos (agendamento_id,paciente_id,profissional_id,data_hora,tipo,descricao) VALUES (?,?,?,?,?,?)')
        ->execute([$a['id'], $a['paciente_id'], $profId, $a['data_hora'], $a['tipo'], $desc]);
    $pdo->prepare("UPDATE agendamentos SET status='realizado' WHERE id = ?")->execute([$a['id']]);
    $pdo->commit();
    flash('Atendimento registrado e incluído no histórico do paciente.');
  }
  header('Location: profissional_agenda.php'); exit;
}

function lista_ag(PDO $pdo, int $profId, string $where, string $ordem, string $lim = ''): array {
  $s = $pdo->prepare("SELECT a.*, u.nome AS paciente FROM agendamentos a JOIN usuarios u ON u.id = a.paciente_id WHERE a.profissional_id = ? AND $where ORDER BY $ordem $lim");
  $s->execute([$profId]); return $s->fetchAll();
}
$pendentes = lista_ag($pdo, $profId, "a.status = 'agendado' AND DATE(a.data_hora) <= CURDATE()", 'a.data_hora');
$proximos = lista_ag($pdo, $profId, "a.status = 'agendado' AND DATE(a.data_hora) > CURDATE()", 'a.data_hora', 'LIMIT 20');
$feitos = lista_ag($pdo, $profId, "a.status IN ('realizado','faltou')", 'a.data_hora DESC', 'LIMIT 10');
desk_open('Atendimentos', 'atend'); ?>
<h1>Atendimentos</h1>
<p class="sub">Registre os atendimentos realizados. O registro entra no histórico do paciente.</p>

<h2 class="sec">A registrar <span class="badge gray" style="margin-left:8px"><?= count($pendentes) ?></span></h2>
<?php if (!$pendentes): ?><div class="dcard"><p class="empty" style="margin:0">Nenhum atendimento pendente de registro.</p></div><?php endif; ?>
<?php foreach ($pendentes as $a): $ts = strtotime($a['data_hora']); ?>
  <div class="dcard">
    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px">
      <div><a href="historico_paciente.php?id=<?= (int)$a['paciente_id'] ?>"><strong style="font-size:16px"><?= e($a['paciente']) ?></strong></a><br><small style="color:var(--muted)"><?= e(date('d/m/Y', $ts)) ?> às <?= e(date('H:i', $ts)) ?></small></div>
      <span class="badge gray" style="margin:0;height:fit-content"><?= e(TIPOS[$a['tipo']] ?? '') ?></span>
    </div>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
      <textarea name="descricao" class="input" rows="3" maxlength="1000" style="height:auto;padding:12px 16px" placeholder="Descreva o atendimento realizado" required></textarea>
      <div style="display:flex;gap:10px;margin-top:12px;flex-wrap:wrap"><button class="btn sm" name="acao" value="realizado">Registrar atendimento</button><button class="btn sm outline" name="acao" value="faltou" formnovalidate>Marcar falta</button></div>
    </form>
  </div>
<?php endforeach; ?>

<h2 class="sec">Próximos agendamentos</h2>
<div class="dcard" style="padding:6px 22px">
  <?php if (!$proximos) echo '<p class="empty" style="padding:14px 0;margin:0">Nenhum agendamento futuro.</p>'; ?>
  <?php foreach ($proximos as $a): $ts = strtotime($a['data_hora']); ?>
    <div class="hrow"><span><span class="d"><?= e($a['paciente']) ?></span><span class="t"><?= e(date('d/m/Y', $ts)) ?> às <?= e(date('H:i', $ts)) ?> · <?= e(TIPOS[$a['tipo']] ?? '') ?></span></span></div>
  <?php endforeach; ?>
</div>

<h2 class="sec">Últimos registros</h2>
<div class="dcard" style="padding:6px 22px">
  <?php if (!$feitos) echo '<p class="empty" style="padding:14px 0;margin:0">Nenhum registro ainda.</p>'; ?>
  <?php foreach ($feitos as $a): $ts = strtotime($a['data_hora']); ?>
    <div class="hrow"><span><span class="d"><?= e($a['paciente']) ?></span><span class="t"><?= e(date('d/m/Y H:i', $ts)) ?> · <?= e(TIPOS[$a['tipo']] ?? '') ?></span></span><span class="badge <?= $a['status'] === 'faltou' ? 'red' : '' ?>" style="margin:0"><?= $a['status'] === 'faltou' ? 'Faltou' : 'Realizada' ?></span></div>
  <?php endforeach; ?>
</div>
<?php desk_close();
