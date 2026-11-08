<?php
/**
 * Login (R01, R09).
 *
 * Perfil: visitante.
 * Autentica com password_verify, renova o id da sessão (session_regenerate_id) e redireciona
 * conforme o perfil (paciente, profissional ou recepção). Também trata os atalhos de
 * demonstração (?demo=) e o aviso de "esqueci minha senha" (não implementado no protótipo).
 */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/ui.php';

if (usuario()) { header('Location: ' . home_do_perfil(usuario()['perfil'])); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $st = pdo()->prepare('SELECT * FROM usuarios WHERE email = ?');
  $st->execute([trim($_POST['email'] ?? '')]);
  $u = $st->fetch();
  if ($u && password_verify($_POST['senha'] ?? '', $u['senha_hash'])) {
    session_regenerate_id(true);
    $_SESSION['usuario'] = ['id' => (int)$u['id'], 'nome' => $u['nome'], 'perfil' => $u['perfil']];
    header('Location: ' . home_do_perfil($u['perfil'])); exit;
  }
  flash('E-mail ou senha inválidos.', 'danger');
  header('Location: index.php'); exit;
}
if (isset($_GET['esqueci'])) { flash('A recuperação de senha não está disponível nesta versão do protótipo.', 'info'); header('Location: index.php'); exit; }

$demo = ['recepcao' => 'ana.melo@exemplo.com', 'profissional' => 'helena.martins@exemplo.com'][$_GET['demo'] ?? ''] ?? '';
html_open('Entrar'); ?>
<div class="m-wrap white auth"><div class="login">
  <?= brand() ?>
  <p class="tag">Seu cuidado começa com um horário.</p>
  <?php flash_html(); ?>
  <h1 class="title" style="margin-top:8px">Bem-vindo</h1>
  <p class="sub">Entre para gerenciar suas consultas.</p>
  <form method="post"><?= csrf_field() ?>
    <label class="lbl" for="email">E-mail</label>
    <input class="input" id="email" type="email" name="email" placeholder="voce@exemplo.com" value="<?= e($demo) ?>" required autocomplete="username">
    <label class="lbl" for="senha">Senha</label>
    <input class="input" id="senha" type="password" name="senha" placeholder="Digite sua senha" value="<?= $demo ? 'Senha@123' : '' ?>" required autocomplete="current-password">
    <a class="forgot" href="index.php?esqueci=1">Esqueci minha senha</a>
    <button class="btn">Entrar</button>
  </form>
  <p class="alt">Ainda não tem uma conta? <a href="cadastro.php">Criar conta</a></p>
  <?php if (cfg('demo')): ?>
    <div class="demo">Acessos de demonstração<br><a href="index.php?demo=recepcao">Recepção</a><span class="sep"></span><a href="index.php?demo=profissional">Profissional</a></div>
  <?php endif; ?>
</div></div>
<?php html_close();
