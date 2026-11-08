<?php
/**
 * Histórico do paciente (R07).
 *
 * Perfil: recepção e profissional.
 * Mostra dados cadastrais, próximas consultas e a linha do tempo de atendimentos.
 * A recepção também pode cancelar ou reagendar consultas futuras e editar o perfil.
 */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/ui.php';
require __DIR__ . '/../includes/agenda.php';
$u = exigir_perfil('recepcao', 'profissional');
$pdo = pdo();
$st = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? AND perfil = 'paciente'"); $st->execute([(int)($_GET['id'] ?? 0)]);
$p = $st->fetch();
if (!$p) { http_response_code(404); exit('Paciente não encontrado.'); }
// Cancelamento de consulta futura (somente recepção). O horário volta a ficar livre (slot_ativo vira NULL).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $u['perfil'] === 'recepcao') {
  csrf_check();
  $c = $pdo->prepare("UPDATE agendamentos SET status='cancelado' WHERE id = ? AND paciente_id = ? AND status='agendado' AND data_hora > NOW()");
  $c->execute([(int)($_POST['id_agendamento'] ?? 0), $p['id']]);
  flash($c->rowCount() ? 'Consulta cancelada. O horário foi liberado.' : 'Não foi possível cancelar esta consulta.', $c->rowCount() ? 'success' : 'danger');
  header('Location: historico_paciente.php?id=' . (int)$p['id']); exit;
}
// Próximas consultas do paciente
$st = $pdo->prepare("SELECT a.id, a.data_hora, a.tipo, pu.nome AS profissional, pr.especialidade FROM agendamentos a JOIN profissionais pr ON pr.id = a.profissional_id JOIN usuarios pu ON pu.id = pr.usuario_id WHERE a.paciente_id = ? AND a.status = 'agendado' AND a.data_hora > NOW() ORDER BY a.data_hora");
$st->execute([$p['id']]); $proximas = $st->fetchAll();
$st = $pdo->prepare('SELECT t.*, pu.nome AS profissional, pr.especialidade FROM atendimentos t JOIN profissionais pr ON pr.id = t.profissional_id JOIN usuarios pu ON pu.id = pr.usuario_id WHERE t.paciente_id = ? ORDER BY t.data_hora DESC');
$st->execute([$p['id']]); $at = $st->fetchAll();
$abrev = ['JAN','FEV','MAR','ABR','MAI','JUN','JUL','AGO','SET','OUT','NOV','DEZ'];
$desde = strtotime($p['criado_em']);
$recep = $u['perfil'] === 'recepcao';
desk_open('Histórico do paciente', $recep ? 'pacientes' : 'atend', true); ?>
<div class="crumb"><?php if ($recep): ?><a href="pacientes.php">Pacientes</a><?= icon('right', 14) ?><?php endif; ?><span><?= e($p['nome']) ?></span></div>
<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:22px">
  <div><h1>Histórico do paciente</h1><p class="sub" style="margin:0">Informações e registros de atendimentos.</p></div>
  <?php if ($recep): ?><a class="btn sm outline" href="recepcao_agendar.php?paciente=<?= (int)$p['id'] ?>"><?= icon('cal') ?>Novo agendamento</a><?php endif; ?>
</div>
<div class="hist-grid">
  <aside class="pcard">
    <div class="pcard-top"><span class="avatar big"><?= e(iniciais($p['nome'])) ?></span><h2><?= e($p['nome']) ?></h2><small>Paciente desde <?= e(MESES[(int)date('n', $desde) - 1] . ' de ' . date('Y', $desde)) ?></small></div>
    <div class="pcard-body">
      <div class="f"><small>DATA DE NASCIMENTO</small><strong><?= $p['data_nascimento'] ? e(data_extenso(strtotime($p['data_nascimento']))) : '—' ?></strong></div>
      <div class="f"><small>TELEFONE</small><strong><?= e($p['telefone'] ?: '—') ?></strong></div>
      <div class="f"><small>E-MAIL</small><strong><?= e($p['email']) ?></strong></div>
      <div class="f"><small>CONVÊNIO</small><strong><?= e($p['convenio'] ?: '—') ?></strong></div>
      <?php if ($p['observacao_geral']): ?><div class="obs"><?= icon('file', 22) ?><div><strong>Observação geral</strong><span><?= e($p['observacao_geral']) ?></span></div></div><?php endif; ?>
      <?php if ($recep): ?><a class="btn sm outline" style="width:100%;margin-top:16px" href="editar_paciente.php?id=<?= (int)$p['id'] ?>">Editar perfil</a><?php endif; ?>
    </div>
  </aside>
  <div class="col-main">
  <section class="dcard" style="padding:0 22px 6px">
    <div class="at-head" style="padding:18px 0"><h2>Próximas consultas</h2><span class="badge gray" style="margin:0 auto 0 12px"><?= count($proximas) ?></span></div>
    <?php if (!$proximas): ?><p class="empty" style="padding:14px 0 18px;margin:0">Nenhuma consulta futura agendada.</p><?php endif; ?>
    <?php foreach ($proximas as $a): $ts = strtotime($a['data_hora']); ?>
      <div class="hrow">
        <span><span class="d"><?= e(date('d/m/Y', $ts)) ?> às <?= e(date('H:i', $ts)) ?></span><span class="t"><?= e($a['profissional']) ?> · <?= e(TIPOS[$a['tipo']] ?? '') ?></span></span>
        <?php if ($recep): ?>
        <span style="display:flex;gap:8px">
          <a class="btn sm outline" style="height:38px;font-size:13px" href="recepcao_agendar.php?reagendar=<?= (int)$a['id'] ?>">Reagendar</a>
          <form method="post" onsubmit="return confirm('Cancelar esta consulta?')"><?= csrf_field() ?><input type="hidden" name="id_agendamento" value="<?= (int)$a['id'] ?>"><button class="btn danger">Cancelar</button></form>
        </span>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </section>
  <section class="dcard" style="padding:0">
    <div class="at-head"><h2>Atendimentos</h2><span class="badge gray" style="margin:0 auto 0 12px"><?= count($at) ?> registro<?= count($at) === 1 ? '' : 's' ?></span><span class="mr"><?= icon('history', 18) ?>Mais recentes</span></div>
    <?php if (!$at): ?><p class="empty" style="padding:24px">Nenhum atendimento registrado.</p><?php endif; ?>
    <div class="tl">
      <?php foreach ($at as $k => $t): $ts = strtotime($t['data_hora']); ?>
        <article class="tl-i<?= $k === 0 ? ' first' : '' ?>">
          <span class="dotc"><?= $k === 0 ? icon('steth', 16) : '' ?></span>
          <div class="tl-top"><b><?= e(date('d', $ts) . ' ' . $abrev[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts)) ?></b><span class="badge gray"><?= e(TIPOS[$t['tipo']] ?? '') ?></span></div>
          <strong><?= e($t['profissional']) ?></strong>
          <p><?= e($t['descricao']) ?></p>
          <details><summary>Ver detalhes <?= icon('right', 14) ?></summary><p class="det">Data e horário: <?= e(date('d/m/Y', $ts) . ' às ' . date('H:i', $ts)) ?> · Especialidade: <?= e($t['especialidade']) ?> · Tipo: <?= e(TIPOS[$t['tipo']] ?? '') ?></p></details>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
  </div>
</div>
<?php desk_close();
