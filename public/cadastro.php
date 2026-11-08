<?php
/**
 * Auto-cadastro de paciente (R01, R09).
 *
 * Perfil: visitante (sem login). Só pacientes se cadastram sozinhos; recepção e
 * profissionais são criados pelo administrador (ver database/).
 * Exige consentimento LGPD, que é registrado em usuarios.consentimento_lgpd_em.
 */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/ui.php';
require __DIR__ . '/../includes/pacientes.php';

$old = ['nome' => '', 'email' => '', 'telefone' => '', 'data_nascimento' => '', 'convenio' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  [$dados, $erros] = validar_dados_paciente($_POST);
  $old = $dados; $senha = $_POST['senha'] ?? '';
  if (strlen($senha) < 8) $erros[] = 'A senha deve ter no mínimo 8 caracteres.';
  if (empty($_POST['lgpd'])) $erros[] = 'É necessário autorizar o uso dos dados para fins de agendamento.';

  if ($erros) {
    flash($erros[0], 'danger');
  } else {
    try {
      // Só paciente se cadastra sozinho; recepção e profissional são criados pelo administrador.
      pdo()->prepare("INSERT INTO usuarios (nome,email,telefone,data_nascimento,convenio,senha_hash,perfil,consentimento_lgpd_em) VALUES (?,?,?,?,?,?, 'paciente', NOW())")
           ->execute([$dados['nome'], $dados['email'], $dados['telefone'], $dados['data_nascimento'], $dados['convenio'], password_hash($senha, PASSWORD_DEFAULT)]);
      flash('Cadastro realizado. Faça login para continuar.'); header('Location: index.php'); exit;
    } catch (PDOException $e) {
      if ($e->getCode() === '23000') flash('Este e-mail já está cadastrado.', 'danger'); else throw $e;   // 23000 = e-mail duplicado (UNIQUE)
    }
  }
}
m_open('Criar conta', 'index.php', 'white'); ?>
<div class="pad">
  <h1 class="title" style="font-size:26px">Seus dados</h1>
  <p class="sub">Preencha as informações abaixo para começar.</p>
  <form method="post"><?= csrf_field() ?>
    <label class="lbl" for="nome">Nome completo</label>
    <input class="input" id="nome" name="nome" placeholder="Ex.: Marina Duarte" value="<?= e($old['nome']) ?>" required autocomplete="name">
    <label class="lbl" for="email">E-mail</label>
    <input class="input" id="email" type="email" name="email" placeholder="marina@exemplo.com" value="<?= e($old['email']) ?>" required autocomplete="email">
    <label class="lbl" for="tel">Telefone</label>
    <input class="input" id="tel" type="tel" name="telefone" placeholder="(11) 99999-9999" value="<?= e($old['telefone']) ?>" required autocomplete="tel">
    <label class="lbl" for="nasc">Data de nascimento</label>
    <input class="input" id="nasc" type="date" name="data_nascimento" min="1900-01-01" max="<?= date('Y-m-d') ?>" value="<?= e($old['data_nascimento']) ?>" required autocomplete="bday">
    <label class="lbl" for="conv">Convênio</label>
    <select class="input" id="conv" name="convenio" required>
      <option value="">Selecione…</option>
      <?php foreach (CONVENIOS as $c) echo '<option' . ($old['convenio'] === $c ? ' selected' : '') . '>' . e($c) . '</option>'; ?>
    </select>
    <label class="lbl" for="senha">Senha</label>
    <input class="input" id="senha" type="password" name="senha" placeholder="Mínimo de 8 caracteres" minlength="8" required autocomplete="new-password">
    <label class="check"><input type="checkbox" name="lgpd" value="1"><span>Autorizo o uso dos meus dados para fins de agendamento, conforme a <a href="#" style="font-weight:700">LGPD</a>.</span></label>
    <button class="btn">Cadastrar</button>
    <p class="note">Seus dados são armazenados com segurança.</p>
  </form>
</div>
<?php m_close();
