<?php
/**
 * Agendamento e reagendamento feitos pela recepção em nome do paciente (R08, R04, R06).
 *
 * Perfil: recepção.
 *  - Novo agendamento: escolhe paciente, profissional, data e horário livre.
 *  - Reagendamento (?reagendar=ID): o paciente fica fixo; ao confirmar o novo horário,
 *    a consulta anterior é cancelada (se o novo horário falhar, a anterior é mantida).
 * Usa a mesma regra de conflito do paciente (includes/agenda.php: criar_agendamento).
 * Ao agendar, envia o e-mail de confirmação ao paciente.
 */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/ui.php';
require __DIR__ . '/../includes/agenda.php';
$u = exigir_perfil('recepcao');
$pdo = pdo();

// Reagendamento: a consulta precisa estar agendada e no futuro.
$reagId = (int)($_GET['reagendar'] ?? $_POST['reagendar'] ?? 0);
$reag = $reagId ? agendamento_completo($pdo, $reagId) : null;
if ($reag && ($reag['status'] !== 'agendado' || strtotime($reag['data_hora']) <= time())) $reag = null;
$reagId = $reag ? $reagId : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $pac = $reag ? (int)$reag['paciente_id'] : (int)($_POST['paciente_id'] ?? 0);   // no reagendamento o paciente não muda
  $prof = (int)($_POST['profissional_id'] ?? 0);
  $existe = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE id = ? AND perfil = 'paciente'"); $existe->execute([$pac]);
  $volta = 'recepcao_agendar.php?' . http_build_query(array_filter(['paciente' => $pac, 'prof' => $prof, 'data' => substr($_POST['data_hora'] ?? '', 0, 10), 'reagendar' => $reagId]));
  if (!$existe->fetchColumn()) { flash('Paciente inválido.', 'danger'); header('Location: recepcao_agendar.php'); exit; }

  $r = criar_agendamento($pdo, $pac, $prof, $_POST['data_hora'] ?? '', $_POST['tipo'] ?? 'consulta');
  if (!$r['ok']) { flash($r['msg'] === 'Horário inválido.' ? 'Selecione um horário.' : 'Horário indisponível. Escolha outro horário.', 'danger'); header('Location: ' . $volta); exit; }

  if ($reag) $pdo->prepare("UPDATE agendamentos SET status='cancelado' WHERE id = ? AND paciente_id = ? AND status='agendado'")->execute([$reagId, $pac]);
  $ag = agendamento_completo($pdo, $r['id']);
  $env = enviar_confirmacao($ag);
  flash('Consulta de ' . $ag['paciente_nome'] . ($reag ? ' reagendada para ' : ' agendada para ') . date('d/m/Y', strtotime($ag['data_hora'])) . ' às ' . date('H:i', strtotime($ag['data_hora'])) . '. ' . ($env ? 'E-mail de confirmação enviado.' : 'Não foi possível enviar o e-mail de confirmação neste ambiente.'), $env ? 'success' : 'info');
  header('Location: painel.php?data=' . urlencode(substr($ag['data_hora'], 0, 10))); exit;
}

$pacs = $pdo->query("SELECT id, nome FROM usuarios WHERE perfil = 'paciente' ORDER BY nome")->fetchAll();
$profs = $pdo->query('SELECT p.id, u.nome, p.especialidade FROM profissionais p JOIN usuarios u ON u.id = p.usuario_id ORDER BY p.id')->fetchAll();
$pac = $reag ? (int)$reag['paciente_id'] : (int)($_GET['paciente'] ?? 0);
$prof = (int)($_GET['prof'] ?? ($reag['prof_id'] ?? ($profs[0]['id'] ?? 0)));
$data = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data'] ?? '') ? $_GET['data'] : '';
if ($data !== '' && $data < date('Y-m-d')) $data = '';
$slots = ($prof && $data) ? slots_do_dia($pdo, $prof, $data) : [];
$tipoPadrao = $reag['tipo'] ?? 'consulta';
desk_open($reag ? 'Reagendar consulta' : 'Novo agendamento', 'agenda'); ?>
<h1><?= $reag ? 'Reagendar consulta' : 'Novo agendamento' ?></h1>
<?php if ($reag): ?>
  <div class="alert info" style="margin:0 0 16px">Reagendando a consulta de <strong><?= e($reag['paciente_nome']) ?></strong> de <?= e(date('d/m/Y', strtotime($reag['data_hora']))) ?> às <?= e(date('H:i', strtotime($reag['data_hora']))) ?> com <?= e($reag['profissional']) ?>. A consulta anterior será cancelada ao confirmar o novo horário.</div>
<?php else: ?>
  <p class="sub">Marque uma consulta em nome do paciente. O horário só é aceito se estiver livre.</p>
<?php endif; ?>

<form method="get" class="dcard filtros"><?php if ($reag) echo '<input type="hidden" name="reagendar" value="' . $reagId . '">'; ?>
  <div><label>Paciente</label>
    <?php if ($reag): ?><input class="input" value="<?= e($reag['paciente_nome']) ?>" disabled>
    <?php else: ?><select name="paciente" class="input" required><option value="">Selecione…</option><?php foreach ($pacs as $p) echo '<option value="' . (int)$p['id'] . '"' . ($p['id'] == $pac ? ' selected' : '') . '>' . e($p['nome']) . '</option>'; ?></select><?php endif; ?></div>
  <div><label>Profissional</label><select name="prof" class="input"><?php foreach ($profs as $p) echo '<option value="' . (int)$p['id'] . '"' . ($p['id'] == $prof ? ' selected' : '') . '>' . e($p['nome']) . ' — ' . e($p['especialidade']) . '</option>'; ?></select></div>
  <div><label>Data</label><input type="date" name="data" class="input" min="<?= date('Y-m-d') ?>" value="<?= e($data) ?>" required></div>
  <div><button class="btn outline" style="height:50px">Ver horários</button></div>
</form>

<?php if ($pac && $data): ?>
<div class="dcard">
  <h2 style="margin:0 0 4px;font-size:16px">Horários de <?= e(dia_extenso(strtotime($data))) ?></h2>
  <?php if (!$slots): ?><p class="empty">Nenhum horário disponível para este profissional nesta data.</p>
  <?php else: ?>
  <div class="legend" style="margin:8px 0 14px"><span><i style="background:var(--green)"></i>Livre</span><span><i style="background:var(--red)"></i>Ocupado</span><span><i style="background:var(--primary)"></i>Selecionado</span></div>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="paciente_id" value="<?= $pac ?>"><input type="hidden" name="profissional_id" value="<?= $prof ?>"><input type="hidden" name="reagendar" value="<?= $reagId ?>">
    <div class="slots rec">
      <?php foreach ($slots as $s): $h = substr($s['hora'], 11, 5); ?>
        <?php if ($s['ocupado']): ?><div class="slot busy"><span><?= $h ?></span></div>
        <?php else: ?><label class="slot"><input type="radio" name="data_hora" value="<?= e($s['hora']) ?>" required><span><?= $h ?></span></label><?php endif; ?>
      <?php endforeach; ?>
    </div>
    <div style="display:flex;gap:12px;align-items:flex-end;margin-top:20px;flex-wrap:wrap">
      <div style="min-width:200px"><label style="font-size:12px;font-weight:600;color:var(--muted);display:block;margin-bottom:6px">Tipo de atendimento</label>
        <select name="tipo" class="input"><?php foreach (TIPOS as $k => $v) echo '<option value="' . $k . '"' . ($k === $tipoPadrao ? ' selected' : '') . '>' . e($v) . '</option>'; ?></select></div>
      <button class="btn sm" style="height:50px"><?= $reag ? 'Confirmar reagendamento' : 'Confirmar agendamento' ?></button>
    </div>
  </form>
  <?php endif; ?>
</div>
<?php endif; desk_close();
