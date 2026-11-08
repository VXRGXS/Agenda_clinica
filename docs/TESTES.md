# Roteiro de testes funcionais

Testes manuais, executados com os dados fictícios do `seed`/`dados_demo.sql`.

| ID | Req. | Procedimento | Resultado esperado |
|---|---|---|---|
| T01 | R01, R09 | Cadastrar paciente sem marcar a autorização de uso dos dados | Cadastro recusado com mensagem |
| T02 | R01, R09 | Entrar com senha errada; abrir página de outro perfil | Login recusado; acesso negado |
| T03 | R02 | Profissional cadastra horário; paciente consulta a agenda | Horário aparece como disponível |
| T04 | R03 | Paciente agenda escolhendo profissional, data e horário | Consulta listada em "Minhas consultas" |
| T05 | R04 | Segundo paciente tenta o horário já ocupado | Recusado, tela "Horário indisponível" |
| T06 | R06 | Paciente cancela a consulta | Horário volta a ficar livre |
| T07 | R04 | Duas requisições simultâneas para o mesmo horário | Apenas uma gravada |
| T08 | R05 | Agendar e verificar o e-mail (caixa de entrada e `storage/emails.log`) | E-mail com data, horário e profissional |
| T09 | R07 | Profissional registra atendimento; consultar histórico do paciente | Atendimento no histórico; consulta "realizada" |
| T10 | R08 | Abrir o painel em dia com agendamentos; buscar paciente | Agendamentos por profissional; busca filtra |
| T11 | R04, R08 | Recepção agenda horário livre e repete o mesmo horário | Primeiro aceito; segundo recusado |
| T12 | R08, R09 | Paciente e profissional abrem o painel da recepção | Acesso negado |
| T13 | R01 | Cadastro de paciente sem data de nascimento ou sem convênio | Cadastro recusado |
| T14 | R06, R08 | Recepção cancela consulta futura pelo histórico do paciente | Consulta cancelada; horário livre |
| T15 | R06, R08 | Recepção reagenda consulta futura | Nova consulta criada e a anterior cancelada |
| T16 | R08 | Recepção edita nome, telefone, nascimento, convênio e observação | Dados atualizados e exibidos no histórico |
