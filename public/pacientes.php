<?php
/**
 * Lista e busca de pacientes (R08).
 *
 * Perfil: recepção. Cada linha leva ao histórico do paciente.
 */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/ui.php';
exigir_perfil('recepcao');
$q = trim($_GET['q'] ?? '');
$sql = "SELECT u.id, u.nome, u.email, u.telefone, u.criado_em, (SELECT COUNT(*) FROM atendimentos t WHERE t.paciente_id = u.id) AS n
        FROM usuarios u WHERE u.perfil = 'paciente'" . ($q !== '' ? ' AND (u.nome LIKE ? OR u.email LIKE ?)' : '') . ' ORDER BY u.nome';
$st = pdo()->prepare($sql); $st->execute($q !== '' ? ["%$q%", "%$q%"] : []);
$lista = $st->fetchAll();
desk_open('Pacientes', 'pacientes'); ?>
<h1>Pacientes</h1>
<p class="sub">Consulte o histórico de atendimentos e marque novas consultas.</p>
<form method="get" class="toolbar" style="margin-bottom:18px"><label class="search" style="margin-left:0;flex:1"><?= icon('search', 18) ?><input name="q" value="<?= e($q) ?>" placeholder="Buscar por nome ou e-mail"></label><button class="btn sm">Buscar</button></form>
<div class="dcard" style="padding:6px 22px">
  <?php if (!$lista) echo '<p class="empty" style="padding:16px 0">Nenhum paciente encontrado.</p>'; ?>
  <?php foreach ($lista as $p): ?>
    <a class="hrow plink" href="historico_paciente.php?id=<?= (int)$p['id'] ?>">
      <span style="display:flex;align-items:center;gap:14px"><span class="avatar"><?= e(iniciais($p['nome'])) ?></span><span><strong><?= e($p['nome']) ?></strong><br><small style="color:var(--muted)"><?= e($p['email']) ?><?= $p['telefone'] ? ' · ' . e($p['telefone']) : '' ?></small></span></span>
      <span class="badge gray"><?= (int)$p['n'] ?> atendimento<?= (int)$p['n'] === 1 ? '' : 's' ?></span>
    </a>
  <?php endforeach; ?>
</div>
<?php desk_close();
