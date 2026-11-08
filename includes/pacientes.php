<?php
/**
 * Dados cadastrais do paciente: lista de convênios e validação.
 *
 * Usado pelo auto-cadastro (public/cadastro.php) e pela edição feita pela
 * recepção (public/editar_paciente.php), para que as duas telas apliquem
 * exatamente as mesmas regras.
 */

/** Opções do campo "Convênio" (tipo de atendimento do paciente). */
const CONVENIOS = ['Particular', 'Unimed', 'Amil', 'Bradesco Saúde', 'SulAmérica', 'Porto Seguro', 'Outro'];

/**
 * Valida e normaliza os dados de um paciente vindos de um formulário.
 *
 * @param array $in           Dados brutos ($_POST): nome, email, telefone, data_nascimento, convenio, [observacao_geral].
 * @param array $convExtra    Convênios aceitos além de CONVENIOS (ex.: valor antigo de um cadastro existente).
 * @return array              [dados normalizados, lista de mensagens de erro]. Sem erros => lista vazia.
 */
function validar_dados_paciente(array $in, array $convExtra = []): array {
  $erros = [];
  $d = [
    'nome' => trim($in['nome'] ?? ''),
    'email' => trim($in['email'] ?? ''),
    'telefone' => trim($in['telefone'] ?? ''),
    'data_nascimento' => trim($in['data_nascimento'] ?? ''),
    'convenio' => trim($in['convenio'] ?? ''),
    'observacao_geral' => trim($in['observacao_geral'] ?? ''),
  ];

  if ($d['nome'] === '' || mb_strlen($d['nome']) > 120) $erros[] = 'Informe o nome completo.';
  if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'Informe um e-mail válido.';

  $digitos = preg_replace('/\D/', '', $d['telefone']);
  if (strlen($digitos) < 10 || strlen($digitos) > 11) $erros[] = 'Informe o telefone com DDD.';

  $nasc = DateTime::createFromFormat('Y-m-d', $d['data_nascimento']);
  if (!$nasc || $nasc->format('Y-m-d') !== $d['data_nascimento'] || $d['data_nascimento'] > date('Y-m-d') || $d['data_nascimento'] < '1900-01-01')
    $erros[] = 'Informe uma data de nascimento válida.';

  if (!in_array($d['convenio'], array_merge(CONVENIOS, $convExtra), true)) $erros[] = 'Selecione o convênio.';
  if (mb_strlen($d['observacao_geral']) > 255) $erros[] = 'A observação geral aceita até 255 caracteres.';

  return [$d, $erros];
}
