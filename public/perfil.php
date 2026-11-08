<?php
/**
 * Perfil do paciente (somente leitura).
 *
 * Perfil: paciente. Mostra nome, e-mail, telefone, data de nascimento e convênio.
 */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/ui.php';
$u = exigir_perfil('paciente');
$st = pdo()->prepare('SELECT nome, email, telefone, data_nascimento, convenio FROM usuarios WHERE id = ?'); $st->execute([$u['id']]); $d = $st->fetch();
m_open_brand('Perfil'); ?>
<div class="pad narrow" style="text-align:center;padding-top:36px">
  <span class="avatar" style="width:84px;height:84px;font-size:24px;margin:0 auto 14px;background:var(--primary);color:#fff"><?= e(iniciais($d['nome'])) ?></span>
  <h1 class="title" style="font-size:24px"><?= e($d['nome']) ?></h1>
  <div class="infocard" style="margin-top:22px">
    <div class="row"><div><small>E-mail</small><strong><?= e($d['email']) ?></strong></div></div>
    <div class="row"><div><small>Telefone</small><strong><?= e($d['telefone'] ?: '—') ?></strong></div></div>
    <div class="row"><div><small>Data de nascimento</small><strong><?= $d['data_nascimento'] ? e(data_extenso(strtotime($d['data_nascimento']))) : '—' ?></strong></div></div>
    <div class="row"><div><small>Convênio</small><strong><?= e($d['convenio'] ?: '—') ?></strong></div></div>
  </div>
  <a class="btn outline" href="logout.php">Sair da conta</a>
</div>
<?= tabbar('perfil') ?>
<?php m_close();
