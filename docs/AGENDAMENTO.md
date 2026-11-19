# Agenda por setor: requisitos do professor

A implementação segue as operações enviadas, o quadro e a consulta da foto. Cada reunião é entre um **usuário servidor** e um **usuário responsável**. A resposta mostra **todos os alunos ativos vinculados ao responsável**, conforme confirmado, com data, hora, servidor, responsável, alunos e seus cursos.

## Preparar e executar

Na raiz do projeto:

```sh
bin/cake migrations migrate
bin/cake schema_cache clear
bin/cake server
```

O banco continua configurado em `config/app_local.php` ou `DATABASE_URL`. A nova migration preserva os dados: o antigo `agendamentos.responsavel_id`, que identificava um vínculo de `responsaveis`, passa a se chamar `responsavel_vinculo_id`; o novo `responsavel_id` identifica **users.id**. O servidor antigo é obtido de `coordenadores.users_id`. Registros antigos sem horário de setor continuam consultáveis. Para editá-los ou reativá-los como reserva, informe os campos atuais e cadastre o horário correspondente. A migração não cria setores, perfis nem horários fictícios e sua reversão exige backup.

## Relações

- `users.tipo_id → tipos.id`: perfil do usuário, sem presumir números para professor ou técnico.
- `users.setor_id → setors.id`: setor do servidor. O nome físico `setors` já existia e foi preservado.
- `users.agendar`: habilita o servidor para receber agendamentos. Novos servidores habilitados precisam de setor ativo.
- `horarios.setor_id → setors.id`: disponibilidade semanal do setor.
- `agendamentos.servidor_id → users.id` e `responsavel_id → users.id`.
- `agendamentos.horario_id → horarios.id`.
- `responsaveis.users_id → users.id`, `responsaveis.alunos_id → alunos.id`: vários alunos por responsável e vários responsáveis por aluno.
- Não existe `users.agendamentos_id`: um usuário pode participar de várias reuniões.
- Não se escolhe `aluno_id` no cadastro da reunião: a busca reúne todos os alunos ativos do responsável. O filtro por aluno localiza a reunião e preserva os demais alunos na resposta.

## Testar no Insomnia

Importe `docs/insomnia-agenda.json`. No ambiente da coleção, ajuste os IDs com os retornados pelas requisições; os valores iniciais são exemplos. A URL base é `http://localhost:8765`. Envie JSON no **Body**, com `Content-Type: application/json`.

1. Cadastre um setor em `POST /api/setores/adicionar.json`: `{"nome":"Administrativo"}`.
2. Cadastre ou edite o usuário servidor em `/api/usuarios/...`, incluindo `"setor_id": ID_DO_SETOR, "agendar": true`. O cadastro completo também exige cpf, nome, email, password, telefone e tipo_id já existente.
3. Cadastre o usuário responsável e vincule-o aos alunos existentes em `POST /api/responsaveis/adicionar.json`: `{"users_id":ID_DO_USUARIO,"alunos_id":ID_DO_ALUNO}`. Repita apenas para os vínculos necessários.
4. Cadastre um horário do setor:

```http
POST /api/horarios/adicionar.json
Content-Type: application/json

{"setor_id":1,"dia_semana":6,"hora_inicio":"13:00:00","hora_fim":"18:00:00"}
```

`dia_semana` segue ISO: segunda=1, terça=2, quarta=3, quinta=4, sexta=5, sábado=6, domingo=7. É uma janela semanal; o início é incluído e o fim é excluído.

5. Cadastre uma reunião, substituindo os IDs:

```http
POST /api/agendamentos/adicionar.json
Content-Type: application/json

{"servidor_id":10,"responsavel_id":2,"horario_id":1,"data":"2026-11-21","hora":"14:30:00","assunto":"Acompanhamento do aluno"}
```

O servidor deve estar ativo e habilitado, pertencer ao setor desse horário e o responsável deve ter pelo menos um vínculo ativo com aluno ativo. O horário e o setor também precisam estar ativos. A data precisa cair no dia da semana informado e a hora, dentro da janela. `assunto` é opcional. `status` antigo é preservado para histórico; o cancelamento é indicado por `cancelado_em` e `cancelado` na resposta.

## Alternativas por nome, mantendo os IDs

As referências podem ser informadas por ID, por nome completo ou combinando as duas formas. O nome é resolvido para o ID existente; o banco continua armazenando as chaves estrangeiras. As pessoas, alunos, setores e perfis precisam estar cadastrados. Espaços nas pontas dos nomes são removidos; a comparação usa igualdade, sem busca parcial.

| Cadastro/edição | Por ID | Alternativa por nome |
|---|---|---|
| Usuário: setor | `setor_id` | `setor_nome` |
| Usuário: perfil | `tipo_id` | `tipo_nome` (ou `perfil_nome`) |
| Vínculo: responsável | `users_id` | `responsavel_nome` |
| Vínculo: aluno | `alunos_id` | `aluno_nome` |
| Horário: setor | `setor_id` | `setor_nome` |
| Agendamento: servidor | `servidor_id` | `servidor_nome` |
| Agendamento: responsável | `responsavel_id` | `responsavel_nome` |

Também são aceitos os apelidos `servidor`, `responsavel`, `setor` e `aluno` nos corpos desses cadastros. Se enviar ID e nome da mesma referência, ambos precisam identificar o mesmo registro. Nomes repetidos exigem ID; nenhum homônimo é escolhido automaticamente.

**Cadastrar uma reunião por nomes:**

```http
POST /api/agendamentos/adicionar.json
Content-Type: application/json

{
  "servidor_nome": "Daniel",
  "responsavel_nome": "Maria",
  "setor_nome": "Administrativo",
  "data": "2026-11-21",
  "hora": "14:30:00",
  "assunto": "Acompanhamento do aluno"
}
```

Quando `horario_id` é omitido, a API procura uma única janela ativa do setor do servidor que atenda ao dia da semana e à hora. `setor_nome` é opcional nessa procura; quando enviado, deve corresponder ao setor do servidor. Se nenhuma janela atender ou várias coincidirem, retorna `422`. Nesse último caso, informe `horario_id`. Na edição, a janela anteriormente escolhida é mantida se continuar válida.

**Buscar por nome:**

```http
GET /api/agendamentos/buscar/servidor/nome/Daniel.json
GET /api/agendamentos/buscar/responsavel/nome/Maria.json
GET /api/agendamentos/buscar/aluno/nome/Ana.json
GET /api/horarios/buscar/setor/nome/Administrativo.json
GET /api/usuarios/buscar/nome/Daniel.json
GET /api/setores/buscar/nome/Administrativo.json
```

Listagens aceitam os filtros `servidor_nome`, `responsavel_nome`, `aluno_nome` e `setor_nome` em agendamentos; `setor_nome` em horários; `responsavel_nome` e `aluno_nome` em vínculos; `setor_nome`, `tipo_nome` e o filtro já existente `nome` em usuários. Você pode combinar nomes, IDs, data e hora em agendamentos. Pesquisas de listas retornam todos os registros coincidentes, inclusive homônimos.

**Editar, excluir ou reativar usuário/setor pelo nome:**

Use `/api/usuarios/editar/nome/Daniel.json`, `/api/usuarios/deletar/nome/Daniel.json` ou `/api/usuarios/reativar/nome/Daniel.json`. Para setores, use as mesmas ações em `/api/setores/.../nome/Administrativo.json`. Os métodos HTTP são os mesmos das rotas por ID. Para renomear, o nome antigo fica na URL e o novo vai no Body: `{"nome":"Novo nome"}`.

**Identificar uma reunião, horário ou vínculo sem ID:**

Esses registros não têm nome próprio. Use um `seletor` no Body JSON das rotas sem ID. Ele identifica o registro atual, enquanto os demais campos indicam o que será alterado:

```http
PATCH /api/agendamentos/editar.json
Content-Type: application/json

{
  "seletor": {
    "servidor_nome": "Daniel",
    "responsavel_nome": "Maria",
    "data": "2026-11-21",
    "hora": "14:30:00"
  },
  "hora": "15:00:00"
}
```

Depois dessa edição, o seletor deve usar `"hora":"15:00:00"`. O mesmo formato serve para `DELETE /api/agendamentos/deletar.json`, `POST /api/agendamentos/reativar.json` e `PATCH /api/agendamentos/cancelar.json`, enviando somente o `seletor`.

| Recurso | Campos aceitos no seletor |
|---|---|
| Usuários e setores | `id`, `nome` |
| Horários | `id`, `setor_id`, `setor_nome`, `dia_semana`, `hora_inicio`, `hora_fim` |
| Vínculos de responsáveis | `id`, `users_id`, `alunos_id`, `responsavel_nome`, `aluno_nome` |
| Agendamentos | `id`, `servidor_id`, `responsavel_id`, `servidor_nome`, `responsavel_nome`, `horario_id`, `setor_nome`, `data`, `hora` |

Para horários, por exemplo, use `{"seletor":{"setor_nome":"Administrativo","dia_semana":6,"hora_inicio":"13:00:00"}}` nas ações sem ID. Para vínculos, use `{"seletor":{"responsavel_nome":"Maria","aluno_nome":"Ana"}}`.

Para buscar um único registro sem ID, envie os campos do seletor nos **Query Parameters** de `GET /api/horarios/buscar.json`, `GET /api/responsaveis/buscar.json` ou `GET /api/agendamentos/buscar.json`. Exemplo: `/api/agendamentos/buscar.json?servidor_nome=Daniel&data=2026-11-21&hora=15:00:00`.

O seletor precisa identificar exatamente um registro: nenhum resultado retorna `404`; vários resultados ou seletor inválido retornam `422`, sem alterar registros. Registros desativados também entram na identificação, permitindo reativação. As rotas por ID e suas regras de validação continuam disponíveis.

## Rotas

Todas aceitam o sufixo `.json`.

| Operação | Método | Horários | Agendamentos |
|---|---|---|---|
| Cadastrar | POST | `/api/horarios/adicionar` | `/api/agendamentos/adicionar` |
| Listar | GET | `/api/horarios/listar` | `/api/agendamentos/listar` |
| Buscar por ID | GET | `/api/horarios/buscar/{id}` | `/api/agendamentos/buscar/{id}` |
| Editar | PATCH ou PUT | `/api/horarios/editar/{id}` | `/api/agendamentos/editar/{id}` |
| Deletar logicamente | DELETE | `/api/horarios/deletar/{id}` | `/api/agendamentos/deletar/{id}` |
| Reativar | POST | `/api/horarios/reativar/{id}` | `/api/agendamentos/reativar/{id}` |
| Buscar por dia semanal | GET | `/api/horarios/buscar/dia/{dia}` | — |
| Buscar por setor | GET | `/api/horarios/buscar/setor/{id}` | — |
| Buscar por data | GET | — | `/api/agendamentos/buscar/data/2026-11-21` |
| Buscar por servidor (users.id) | GET | — | `/api/agendamentos/buscar/servidor/{id}` |
| Buscar por responsável (users.id) | GET | — | `/api/agendamentos/buscar/responsavel/{id}` |
| Buscar por aluno (alunos.id) | GET | — | `/api/agendamentos/buscar/aluno/{id}` |
| Cancelar | POST ou PATCH | — | `/api/agendamentos/cancelar/{id}` |

A busca antiga `/api/agendamentos/buscar/data/{id}/{data}` também combina **ID do usuário servidor** e data. Listagens aceitam filtros combinados: `?servidor_id=10&data=2026-11-21`, `?responsavel_id=2`, `?aluno_id=3`; horários aceitam `?setor_id=1&dia_semana=6`. Listas sem resultado retornam `200` com `data: []`; ID inexistente retorna `404`; dados inválidos, `422`; método incorreto, `405`.

## Exclusão, cancelamento e reservas

Deletar preenche `deleted` e oculta a reunião nas buscas; reativar limpa esse campo. Cancelar preenche `cancelado_em`, libera a reserva e mantém a reunião visível para consulta. Reativar uma reunião cancelada **não desfaz o cancelamento**. Para outro atendimento, crie uma nova reunião. Não se edita reunião cancelada ou desativada.

Uma restrição UNIQUE impede duas reservas ativas para o mesmo servidor na mesma data e hora, inclusive em requisições simultâneas. Editar ou reativar também verifica conflitos. Horários são janelas; **não foi definida duração de reunião nem divisão automática em intervalos**, pois isso não consta do material. Também não foram impostos prazos, antecedência mínima ou bloqueio de datas passadas.

Alterar um horário não pode deixar reuniões ativas fora do dia/faixa/setor. Desativar o horário impede novas reservas e mantém reuniões anteriores para consulta.

## Consulta SQL da foto

O arquivo `docs/sql/agendamentos-por-servidor.sql` pode ser executado no MySQL/TablePlus. Ele agrupa os alunos antes de juntar a agenda, evitando repetir a reunião, e ordena nomes e cursos pelo mesmo aluno. **Não usa DISTINCT nos cursos**, pois dois alunos podem ter o mesmo curso. A API também devolve o array `alunos`, que mantém cada nome ligado ao curso mesmo quando os nomes contêm vírgulas. Na API, o agrupamento é feito em PHP e não depende do limite `group_concat_max_len` do SQL.

## Verificação isolada

```sh
DATABASE_TEST_URL='sqlite:///:memory:' .runtime/php/bin/php vendor/bin/phpunit --colors=never
```

O bootstrap bloqueia testes contra o mesmo banco da aplicação. Para MySQL, configure `DATABASE_TEST_URL` com um banco exclusivo de testes; a suíte recria tabelas desse banco. A agenda fornece a base CRUD solicitada; permissões por usuário autenticado e a tela de calendário não foram definidas nas anotações e não fazem parte deste incremento.
