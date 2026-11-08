<?php
/**
 * Regras de negócio da agenda.
 *
 * Geração de horários, prevenção de conflitos (R04), criação de agendamentos, e-mail de
 * confirmação (R05) e consultas do painel da recepção (R08). A verificação de
 * disponibilidade acontece SEMPRE aqui, no servidor.
 */
const TIPOS = ['consulta' => 'Consulta', 'retorno' => 'Retorno', 'avaliacao' => 'Avaliação', 'procedimento' => 'Procedimento'];

/** Todos os horários do dia (futuros): [['hora'=>'Y-m-d H:i:s','ocupado'=>bool,'dur'=>int], ...]. */
function slots_do_dia(PDO $pdo, int $profId, string $data): array {
  $d = DateTime::createFromFormat('Y-m-d', $data);
  if (!$d || $d->format('Y-m-d') !== $data) return [];
  $st = $pdo->prepare('SELECT hora_inicio, hora_fim, duracao_min FROM horarios_atendimento WHERE profissional_id = ? AND dia_semana = ?');
  $st->execute([$profId, (int)$d->format('w')]);
  $st2 = $pdo->prepare("SELECT data_hora FROM agendamentos WHERE profissional_id = ? AND status = 'agendado' AND DATE(data_hora) = ?");
  $st2->execute([$profId, $data]);
  $ocupados = array_column($st2->fetchAll(), 'data_hora');

  $agora = new DateTime(); $slots = [];
  foreach ($st->fetchAll() as $h) {
    $t = new DateTime("$data {$h['hora_inicio']}"); $fim = new DateTime("$data {$h['hora_fim']}");
    $passo = new DateInterval('PT' . (int)$h['duracao_min'] . 'M');
    while (($fimSlot = (clone $t)->add($passo)) <= $fim) {
      $s = $t->format('Y-m-d H:i:s');
      if ($t > $agora) $slots[$s] = ['ocupado' => in_array($s, $ocupados, true), 'dur' => (int)$h['duracao_min']];
      $t = $fimSlot;
    }
  }
  ksort($slots);
  $out = []; foreach ($slots as $s => $v) $out[] = ['hora' => $s, 'ocupado' => $v['ocupado'], 'dur' => $v['dur']];
  return $out;
}

function slots_livres(PDO $pdo, int $profId, string $data): array {
  return array_column(array_filter(slots_do_dia($pdo, $profId, $data), fn($s) => !$s['ocupado']), 'hora');
}

/** Dias da semana (0-6) em que o profissional atende. */
function dias_de_atendimento(PDO $pdo, int $profId): array {
  $st = $pdo->prepare('SELECT DISTINCT dia_semana FROM horarios_atendimento WHERE profissional_id = ?');
  $st->execute([$profId]);
  return array_map('intval', array_column($st->fetchAll(), 'dia_semana'));
}

/**
 * Cria o agendamento. Retorna ['ok'=>bool, 'msg'=>string, 'id'=>?int].
 * 1ª barreira: o horário precisa estar livre no cálculo feito agora, no servidor.
 * 2ª barreira: UNIQUE (profissional_id, slot_ativo) no banco, que protege contra
 * duas requisições simultâneas para o mesmo horário.
 */
function criar_agendamento(PDO $pdo, int $pacienteId, int $profId, string $dataHora, string $tipo = 'consulta'): array {
  $dt = DateTime::createFromFormat('Y-m-d H:i:s', $dataHora);
  if (!$dt || $dt->format('Y-m-d H:i:s') !== $dataHora) return ['ok' => false, 'msg' => 'Horário inválido.', 'id' => null];
  if (!isset(TIPOS[$tipo])) $tipo = 'consulta';
  $dur = null;
  foreach (slots_do_dia($pdo, $profId, $dt->format('Y-m-d')) as $s) if ($s['hora'] === $dataHora && !$s['ocupado']) $dur = $s['dur'];
  if ($dur === null) return ['ok' => false, 'msg' => 'Horário indisponível.', 'id' => null];
  try {
    $pdo->prepare('INSERT INTO agendamentos (paciente_id, profissional_id, data_hora, duracao_min, tipo) VALUES (?,?,?,?,?)')->execute([$pacienteId, $profId, $dataHora, $dur, $tipo]);
    return ['ok' => true, 'msg' => 'Consulta agendada.', 'id' => (int)$pdo->lastInsertId()];
  } catch (PDOException $e) {
    if ($e->getCode() === '23000') return ['ok' => false, 'msg' => 'Horário indisponível.', 'id' => null];
    throw $e;
  }
}

/** Envia o e-mail de confirmação. true = o servidor de e-mail aceitou a mensagem. Sempre registra o resultado em storage/emails.log. */
function enviar_confirmacao(array $ag): bool {
  require_once __DIR__ . '/mailer.php';
  $assunto = 'Consulta confirmada — AgendaClínica';
  $corpo = 'Olá, ' . strtok($ag['paciente_nome'], ' ') . '! Sua consulta com ' . $ag['profissional'] . ' está confirmada para '
         . date('d/m/Y', strtotime($ag['data_hora'])) . ' às ' . date('H:i', strtotime($ag['data_hora'])) . '.' . "\r\n\r\n"
         . cfg('clinica_nome') . ' — ' . cfg('clinica_endereco') . "\r\n\r\n"
         . 'Para cancelar ou reagendar, acesse: ' . url_base() . '/minhas_consultas.php';
  $ok = enviar_email($ag['paciente_email'], $assunto, $corpo, $erro);
  @file_put_contents(__DIR__ . '/../storage/emails.log',
    date('c') . ' | para=' . $ag['paciente_email'] . ' | ' . ($ok ? 'ENVIADO' : 'FALHOU: ' . $erro) . ' | ' . $assunto . "\n", FILE_APPEND);
  return $ok;
}

const SQL_AG = 'SELECT a.*, p.id AS prof_id, p.especialidade, pu.nome AS profissional, u.nome AS paciente_nome, u.email AS paciente_email
  FROM agendamentos a JOIN profissionais p ON p.id = a.profissional_id JOIN usuarios pu ON pu.id = p.usuario_id JOIN usuarios u ON u.id = a.paciente_id';

function agendamento_do_paciente(PDO $pdo, int $id, int $pacienteId): ?array {
  $st = $pdo->prepare(SQL_AG . ' WHERE a.id = ? AND a.paciente_id = ?'); $st->execute([$id, $pacienteId]);
  return $st->fetch() ?: null;
}
function agendamento_completo(PDO $pdo, int $id): ?array {
  $st = $pdo->prepare(SQL_AG . ' WHERE a.id = ?'); $st->execute([$id]);
  return $st->fetch() ?: null;
}

/** Dados do painel da recepção (R08) para um dia: profissionais, janelas de atendimento e agendamentos (opcionalmente filtrados por nome). */
function agenda_do_dia(PDO $pdo, string $data, string $busca = ''): array {
  $profs = $pdo->query('SELECT p.id, u.nome, p.especialidade FROM profissionais p JOIN usuarios u ON u.id = p.usuario_id ORDER BY p.id')->fetchAll();
  $h = $pdo->prepare('SELECT profissional_id, hora_inicio, hora_fim FROM horarios_atendimento WHERE dia_semana = ?');
  $h->execute([(int)date('w', strtotime($data))]);
  $janelas = [];
  foreach ($h->fetchAll() as $r) $janelas[$r['profissional_id']][] = [substr($r['hora_inicio'], 0, 5), substr($r['hora_fim'], 0, 5)];
  $sql = "SELECT a.id, a.profissional_id, a.paciente_id, a.data_hora, a.duracao_min, a.tipo, a.status, u.nome AS paciente
          FROM agendamentos a JOIN usuarios u ON u.id = a.paciente_id WHERE DATE(a.data_hora) = ? AND a.status <> 'cancelado'";
  $par = [$data];
  if ($busca !== '') { $sql .= ' AND u.nome LIKE ?'; $par[] = '%' . $busca . '%'; }
  $st = $pdo->prepare($sql . ' ORDER BY a.data_hora'); $st->execute($par);
  $ags = []; $total = 0;
  foreach ($st->fetchAll() as $r) { $ags[$r['profissional_id']][] = $r; $total++; }
  return ['profs' => $profs, 'janelas' => $janelas, 'ags' => $ags, 'total' => $total];
}
