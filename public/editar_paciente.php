<?php
/**
 * Edição do cadastro de um paciente pela recepção.
 *
 * Perfil: recepção. Permite corrigir nome, e-mail, telefone, data de nascimento,
 * convênio e a observação geral exibida no histórico do paciente.
 * A senha não é alterada aqui.
 */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/ui.php';
require __DIR__ . '/../includes/pacientes.php';
exigir_perfil('recepcao');
$pdo = pdo();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$st = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? AND perfil = 'paciente'"); $st->execute([$id]);
$p = $st->fetch();
if (!$p) { http_response_code(404); exit('Paciente não encontrado.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  // Se o convênio atual não está na lista padrão (cadastro antigo), ele continua sendo aceito.
  [$d, $erros] = validar_dados_paciente($_POST, array_filter([$p['convenio']]));
  if ($erros) {
    flash($erros[0], 'danger'); $p = array_merge($p, $d);
  } else {
    try {
      $pdo->prepare('UPDATE usuarios SET nome=?, email=?, telefone=?, data_nascimento=?, convenio=?, observacao_geral=? WHERE id=? AND perfil=\'paciente\'')
          ->execute([$d['nome'], $d['email'], $d['telefone'], $d['data_nascimento'], $d['convenio'], $d['observacao_geral'] ?: null, $id]);
      flash('Dados do paciente atualizados.');
      header('Location: historico_paciente.php?id=' . $id); exit;
    } catch (PDOException $e) {
      if ($e->getCode() !== '23000') throw $e;
      flash('Já existe outro cadastro com este e-mail.', 'danger'); $p = array_merge($p, $d);
    }
  }
}
$opcoes = CONVENIOS; if ($p['convenio'] && !in_array($p['convenio'], $opcoes, true)) $opcoes[] = $p['convenio'];
desk_open('Editar paciente', 'pacientes'); ?>
<div class="crumb"><a href="pacientes.php">Pacientes</a><?= icon('right', 14) ?><a href="historico_paciente.php?id=<?= $id ?>"><?= e($p['nome']) ?></a><?= icon('right', 14) ?><span>Editar</span></div>
<h1>Editar paciente</h1>
<p class="sub">Atualize os dados cadastrais. A senha do paciente não é alterada aqui.</p>
<form method="post" class="dcard"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
  <div class="form2">
    <div class="full"><label>Nome completo</label><input class="input" name="nome" value="<?= e($p['nome']) ?>" required maxlength="120"></div>
    <div><label>E-mail</label><input class="input" type="email" name="email" value="<?= e($p['email']) ?>" required></div>
    <div><label>Telefone</label><input class="input" type="tel" name="telefone" value="<?= e($p['telefone']) ?>" required></div>
    <div><label>Data de nascimento</label><input class="input" type="date" name="data_nascimento" min="1900-01-01" max="<?= date('Y-m-d') ?>" value="<?= e($p['data_nascimento']) ?>" required></div>
    <div><label>Convênio</label><select class="input" name="convenio" required><option value="">Selecione…</option><?php foreach ($opcoes as $c) echo '<option' . ($p['convenio'] === $c ? ' selected' : '') . '>' . e($c) . '</option>'; ?></select></div>
    <div class="full"><label>Observação geral (aparece no histórico)</label><textarea class="input" name="observacao_geral" rows="3" maxlength="255" style="height:auto;padding:12px 16px"><?= e($p['observacao_geral']) ?></textarea></div>
  </div>
  <div style="display:flex;gap:12px;margin-top:22px;flex-wrap:wrap"><button class="btn sm">Salvar alterações</button><a class="btn sm outline" href="historico_paciente.php?id=<?= $id ?>">Cancelar</a></div>
</form>
<?php desk_close();
