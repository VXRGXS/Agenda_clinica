<?php
/**
 * Componentes de interface (HTML).
 *
 * Cabeçalhos/rodapés para o layout mobile (paciente) e desktop (profissional e recepção),
 * ícones SVG e a marca. Mantém o visual do protótipo do Figma.
 */

/** Ícone SVG inline (estilo Lucide) pelo nome. $s é o tamanho em px. */
function icon(string $n, int $s = 20): string {
  static $p = [
    'steth' => '<path d="M11 2v2"/><path d="M5 2v2"/><path d="M5 3H4a2 2 0 0 0-2 2v4a6 6 0 0 0 12 0V5a2 2 0 0 0-2-2h-1"/><path d="M8 15a6 6 0 0 0 12 0v-3"/><circle cx="20" cy="10" r="2"/>',
    'cal' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
    'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
    'back' => '<path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>',
    'left' => '<path d="m15 18-6-6 6-6"/>', 'right' => '<path d="m9 18 6-6-6-6"/>',
    'plus' => '<path d="M5 12h14M12 5v14"/>', 'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
    'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
    'check' => '<path d="M20 6 9 17l-5-5"/>', 'x' => '<path d="M18 6 6 18M6 6l12 12"/>',
    'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
    'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/>',
    'history' => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l4 2"/>',
    'mail' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
  ];
  return '<svg class="icon" width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" aria-hidden="true">' . $p[$n] . '</svg>';
}

/** Logo "AgendaClínica" (marca + texto). */
function brand(): string { return '<div class="brand"><span class="brand-mark">' . icon('steth', 22) . '</span><span>Agenda<b>Clínica</b></span></div>'; }

/** Abre o documento HTML (head, fonte Inter e CSS) e o body. */
function html_open(string $titulo, string $bodyClass = ''): void {
  echo '<!doctype html><html lang="pt-br"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
     . '<title>' . e($titulo) . ' — AgendaClínica</title>'
     . '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
     . '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">'
     . '<link rel="stylesheet" href="assets/style.css"></head><body class="' . e($bodyClass) . '">';
}
/** Fecha o documento e carrega o JavaScript. */
function html_close(): void { echo '<script src="assets/app.js"></script></body></html>'; }
/** Exibe (e consome) a mensagem flash, se houver. */
function flash_html(): void { if ($f = flash()) echo '<div class="alert ' . e($f[1]) . '">' . e($f[0]) . '</div>'; }

/** Cabeçalho desktop do paciente (aparece só em telas largas; no celular valem a barra superior e a barra de abas). */
function paciente_header(): void {
  $u = usuario(); if (!$u) return;
  $pg = basename($_SERVER['SCRIPT_NAME'], '.php');
  $ativo = $pg === 'minhas_consultas' ? 'c' : (in_array($pg, ['agendar', 'confirmado', 'indisponivel'], true) ? 'a' : '');
  echo '<header class="dhead only-d"><div class="dhead-in">' . brand() . '<nav class="dnav">'
     . '<a href="minhas_consultas.php" class="' . ($ativo === 'c' ? 'on' : '') . '">' . icon('cal') . 'Minhas consultas</a>'
     . '<a href="agendar.php" class="' . ($ativo === 'a' ? 'on' : '') . '">' . icon('plus') . 'Novo agendamento</a></nav>'
     . '<details class="userchip"><summary><span class="avatar">' . e(iniciais($u['nome'])) . '</span><span class="who"><strong>' . e($u['nome']) . '</strong><small>Paciente</small></span>' . icon('right', 16)
     . '</summary><div class="pop"><a href="perfil.php">Meu perfil</a><a href="logout.php">Sair</a></div></details></div></header>';
}

/** Páginas do paciente com barra superior (voltar + título). $fundo: 'white' | ''. */
function m_open(string $titulo, string $voltar, string $fundo = ''): void {
  html_open($titulo); paciente_header();
  $logado = (bool)usuario();
  echo '<div class="m-wrap ' . e($fundo) . ($logado ? '' : ' auth') . '"><header class="topbar' . ($logado ? ' only-m' : '') . '"><a href="' . e($voltar) . '" aria-label="Voltar">' . icon('back') . '</a><h1>' . e($titulo) . '</h1><span></span></header>';
  flash_html();
}
/** Páginas do paciente sem barra superior (ex.: confirmação). */
function m_open_plain(string $titulo): void {
  html_open($titulo); paciente_header();
  echo '<div class="m-wrap white">'; flash_html();
}
function m_close(): void { echo '</div>'; html_close(); }

/** Páginas do paciente com a marca no topo e menu. */
function m_open_brand(string $titulo): void {
  html_open($titulo); paciente_header();
  echo '<div class="m-wrap with-tabbar"><header class="brandbar only-m">' . brand()
     . '<button class="menu-btn" data-menu aria-label="Menu">' . icon('menu', 24) . '</button>'
     . '<div class="menu-pop" id="menu-pop"><a href="perfil.php">Meu perfil</a><a href="logout.php">Sair</a></div></header>';
  flash_html();
}
function tabbar(string $ativo): string {
  $i = fn($k, $href, $ic, $t) => '<a href="' . $href . '" class="' . ($ativo === $k ? 'on' : '') . '">' . icon($ic, 22) . $t . '</a>';
  return '<nav class="tabbar only-m">' . $i('consultas', 'minhas_consultas.php', 'cal', 'Consultas') . $i('agendar', 'agendar.php', 'plus', 'Agendar') . $i('perfil', 'perfil.php', 'user', 'Perfil') . '</nav>';
}

/** Páginas de profissional/recepção (layout desktop com cabeçalho). */
function desk_open(string $titulo, string $ativo, bool $largo = false): void {
  $u = usuario(); html_open($titulo);
  $abas = $u['perfil'] === 'recepcao'
    ? [['agenda', 'painel.php', 'cal', 'Agenda'], ['pacientes', 'pacientes.php', 'user', 'Pacientes']]
    : [['atend', 'profissional_agenda.php', 'cal', 'Atendimentos'], ['horarios', 'profissional_horarios.php', 'clock', 'Meus horários']];
  echo '<header class="dhead"><div class="dhead-in">' . brand() . '<nav class="dnav">';
  foreach ($abas as [$k, $h, $ic, $t]) echo '<a href="' . $h . '" class="' . ($ativo === $k ? 'on' : '') . '">' . icon($ic) . $t . '</a>';
  $rotulo = ['recepcao' => 'Recepção', 'profissional' => 'Profissional'][$u['perfil']];
  echo '</nav><details class="userchip"><summary><span class="avatar">' . e(iniciais($u['nome'])) . '</span><span class="who"><strong>' . e($u['nome']) . '</strong><small>' . $rotulo . '</small></span>' . icon('right', 16)
     . '</summary><div class="pop"><a href="logout.php">Sair</a></div></details></div></header><main class="desk' . ($largo ? ' wide' : '') . '">';
  flash_html();
}
/** Fecha o layout desktop. */
function desk_close(): void { echo '</main>'; html_close(); }
