<?php
/**
 * Horários de atendimento do profissional (R02).
 *
 * Perfil: profissional. Cadastra e remove janelas semanais (dia, início, fim, duração
 * da consulta). Os horários exibidos aos pacientes são gerados a partir delas.
 */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/ui.php';
$u = exigir_perfil('profissional');
$pdo = pdo();
$st = $pdo->prepare('SELECT id FROM profissionais WHERE usuario_id = ?'); $st->execute([$u['id']]);
$profId = (int)($st->fetchColumn() ?: 0);
if (!$profId) exit('Perfil profissional não configurado.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  if (($_POST['acao'] ?? '') === 'remover') {
    $pdo->prepare('DELETE FROM horarios_atendimento WHERE id = ? AND profissional_id = ?')->execute([(int)($_POST['id'] ?? 0), $profId]);
    flash('Horário removido.');
  } else {
    $dia = (int)($_POST['dia_semana'] ?? -1); $ini = $_POST['hora_inicio'] ?? ''; $fim = $_POST['hora_fim'] ?? ''; $dur = (int)($_POST['duracao_min'] ?? 0);
    if ($dia < 0 || $dia > 6 || !preg_match('/^\d{2}:\d{2}$/', $ini) || !preg_match('/^\d{2}:\d{2}$/', $fim) || $fim <= $ini || $dur < 10 || $dur > 240) {
      flash('Dados de horário inválidos.', 'danger');
    } else {
      $pdo->prepare('INSERT INTO horarios_atendimento (profissional_id,dia_semana,hora_inicio,hora_fim,duracao_min) VALUES (?,?,?,?,?)')->execute([$profId, $dia, $ini, $fim, $dur]);
      flash('Horário cadastrado.');
    }
  }
  header('Location: profissional_horarios.php'); exit;
}

$st = $pdo->prepare('SELECT * FROM horarios_atendimento WHERE profissional_id = ? ORDER BY dia_semana, hora_inicio'); $st->execute([$profId]);
$lista = $st->fetchAll();
desk_open('Meus horários', 'horarios'); ?>
<h1>Meus horários</h1>
<p class="sub">Defina os dias e horários em que você atende. Eles aparecem como disponíveis para os pacientes.</p>

<div class="dcard">
  <form method="post" class="grid-form"><?= csrf_field() ?>
    <div class="full"><label>Dia da semana</label><select name="dia_semana" class="input"><?php foreach (DIAS as $i => $n) echo '<option value="' . $i . '">' . e(ucfirst($n)) . '</option>'; ?></select></div>
    <div><label>Início</label><input type="time" name="hora_inicio" class="input" required></div>
    <div><label>Fim</label><input type="time" name="hora_fim" class="input" required></div>
    <div><label>Duração (min)</label><input type="number" name="duracao_min" class="input" value="30" min="10" max="240" required></div>
    <div class="full"><button class="btn" style="height:50px">Adicionar horário</button></div>
  </form>
</div>

<div class="dcard">
  <?php if (!$lista) echo '<p class="empty">Nenhum horário cadastrado.</p>'; ?>
  <?php foreach ($lista as $h): ?>
    <div class="hrow">
      <div><span class="d"><?= e(ucfirst(DIAS[$h['dia_semana']])) ?></span><span class="t"><?= e(substr($h['hora_inicio'], 0, 5)) ?> – <?= e(substr($h['hora_fim'], 0, 5)) ?> · consultas de <?= (int)$h['duracao_min'] ?> min</span></div>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="acao" value="remover"><input type="hidden" name="id" value="<?= (int)$h['id'] ?>"><button class="btn danger">Remover</button></form>
    </div>
  <?php endforeach; ?>
</div>
<?php desk_close();
