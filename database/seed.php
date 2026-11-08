<?php
/**
 * Carga de dados fictícios (instalação nova).
 *
 * Uso: php database/seed.php (depois de importar schema.sql). Somente dados fictícios;
 * senha de todos os usuários: Senha@123. Chama seed_fase2.php ao final.
 */
$c = require __DIR__ . '/../config.php';
$pdo = new PDO("mysql:host={$c['db_host']};dbname={$c['db_name']};charset=utf8mb4", $c['db_user'], $c['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$senha = password_hash('Senha@123', PASSWORD_DEFAULT);
$ins = $pdo->prepare('INSERT INTO usuarios (nome,email,telefone,senha_hash,perfil,consentimento_lgpd_em) VALUES (?,?,?,?,?,NOW())');

$ins->execute(['Ana Melo', 'ana.melo@exemplo.com', '(11) 90000-0001', $senha, 'recepcao']);
$pacientes = [];
foreach ([['Marina Duarte','marina.duarte@exemplo.com','(11) 98765-4321'], ['Renato Luz','renato.luz@exemplo.com','(11) 90000-0002'], ['Laura Sales','laura.sales@exemplo.com','(11) 90000-0003']] as [$n,$e,$t]) {
  $ins->execute([$n,$e,$t,$senha,'paciente']); $pacientes[] = (int)$pdo->lastInsertId();
}

$profs = [];
foreach ([['Dra. Helena Martins','helena.martins@exemplo.com','Clínica geral'], ['Dr. Lucas Ribeiro','lucas.ribeiro@exemplo.com','Cardiologia'], ['Dra. Camila Nunes','camila.nunes@exemplo.com','Dermatologia']] as [$n,$e,$esp]) {
  $ins->execute([$n,$e,'(11) 90000-0100',$senha,'profissional']); $uid = $pdo->lastInsertId();
  $pdo->prepare('INSERT INTO profissionais (usuario_id,especialidade) VALUES (?,?)')->execute([$uid,$esp]); $pid = (int)$pdo->lastInsertId(); $profs[] = $pid;
  foreach ([1,2,3,4,5] as $dia)   // segunda a sexta, 08-12h e 14-18h, consultas de 30 min
    foreach ([['08:00','12:00'],['14:00','18:00']] as [$i,$f])
      $pdo->prepare('INSERT INTO horarios_atendimento (profissional_id,dia_semana,hora_inicio,hora_fim,duracao_min) VALUES (?,?,?,?,30)')->execute([$pid,$dia,$i,$f]);
}

// Alguns horários já ocupados (outros pacientes fictícios) no próximo dia útil, para demonstrar a recusa de conflito.
$d = new DateTime('tomorrow'); while ((int)$d->format('w') === 0 || (int)$d->format('w') === 6) $d->modify('+1 day');
$ag = $pdo->prepare('INSERT INTO agendamentos (paciente_id,profissional_id,data_hora) VALUES (?,?,?)');
$ag->execute([$pacientes[1], $profs[0], $d->format('Y-m-d') . ' 08:30:00']);
$ag->execute([$pacientes[2], $profs[0], $d->format('Y-m-d') . ' 10:00:00']);
$ag->execute([$pacientes[1], $profs[0], $d->format('Y-m-d') . ' 14:00:00']);
require __DIR__ . '/seed_fase2.php';
echo "Seed concluído. Senha de todos: Senha@123\nPaciente: marina.duarte@exemplo.com | Recepção: ana.melo@exemplo.com | Profissional: helena.martins@exemplo.com\n";
