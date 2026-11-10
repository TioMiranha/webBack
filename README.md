# Cadastro de alunos e responsáveis — CakePHP 5

O projeto mantém os cadastros de tipos, usuários e alunos e adiciona a tabela `responsaveis`, conforme o diagrama da atividade.

## Relações

- `Users belongsTo Tipos`, usando o campo já existente `tipo_id`.
- `Responsaveis belongsTo Users`, usando `users_id`.
- `Responsaveis belongsTo Alunos`, usando `alunos_id`.
- `Users` e `Alunos` possuem `hasMany Responsaveis`.

Cada linha de `responsaveis` representa o vínculo entre um usuário responsável e um aluno. Um usuário pode ser responsável por vários alunos, e um aluno pode ter vários responsáveis. Nome, CPF, email e telefone continuam em `users`.

A tabela possui `id`, `users_id`, `alunos_id`, `created`, `modified` e `deleted`. As duas chaves estrangeiras são obrigatórias. O par `(users_id, alunos_id)` é único; um vínculo desativado deve ser reativado. As chaves estrangeiras impedem a exclusão física de usuários e alunos vinculados.

Foram preservados os campos adicionais que já existiam em `alunos` (`curso`, `situacao`, `emaila`, `emailp` e `anop`), assim como os nomes `tipos.nome` e `users.tipo_id` usados pelo projeto.

## Preparação e execução

Neste ambiente, o PHP CLI foi preparado localmente em `.runtime/php`, sem depender de `/tmp` ou de uma instalação com `sudo`. O executável `bin/cake` encontra essa instalação automaticamente. Para iniciar o servidor:

```bash
./bin/cake server
```

Abra `http://localhost:8765`. A pasta `.runtime` contém binários locais e não é versionada. Em outra máquina, instale o PHP CLI e as extensões abaixo; no Ubuntu, isso pode ser feito com `sudo apt install php-cli php-intl php-mbstring php-xml php-sqlite3 php-mysql php-zip php-gd php-curl`. O lançador também reconhece os executáveis versionados, como `php8.5` e `php8.4`.

O CakePHP requer PHP 8.2 ou superior, mas o `composer.lock` atual inclui ferramentas que exigem PHP 8.4.1 ou superior. A validação desta implementação usou PHP 8.5.4, MySQL 8.4 e SQLite. São necessárias as extensões exigidas pelo Composer, incluindo `intl`, `mbstring`, `pdo_mysql`, `pdo_sqlite`, `dom`, `xml`, `gd` e `zip`.

```bash
composer install
```

Configure o banco MySQL em `config/app_local.php`, nas entradas `Datasources.default` (`host`, `username`, `password` e `database`). O Composer cria esse arquivo a partir de `config/app_local.example.php` quando ele não existe. Crie o banco com `utf8mb4`; as tabelas serão criadas pelas migrations. É possível sobrescrever a conexão com a variável de ambiente `DATABASE_URL`.

```bash
./bin/cake migrations migrate
./bin/cake server -p 8765
```

Abra `http://localhost:8765/responsaveis`. Os menus das telas levam aos outros cadastros.

As migrations novas permitem `deleted = NULL` nos cadastros existentes e criam `responsaveis`. Valores antigos vazios em `deleted` passam a `NULL`; outros valores existentes são preservados. Não é necessário apagar nem recriar as tabelas antigas.

Para executar localmente com SQLite, depois de instalar as dependências:

```bash
DATABASE_URL="sqlite://127.0.0.1/${PWD}/tmp/development.sqlite" ./bin/cake migrations migrate
DATABASE_URL="sqlite://127.0.0.1/${PWD}/tmp/development.sqlite" ./bin/cake server -p 8765
```

## Uso pelas telas

1. Cadastre um tipo em `/tipos/add`.
2. Cadastre o usuário responsável em `/users/add`, selecionando o tipo.
3. Cadastre o aluno em `/alunos/add`.
4. Abra `/responsaveis/add` e selecione o usuário e o aluno.
5. Consulte `/alunos/view/ID` para ver os responsáveis, ou `/users/view/ID` para ver os alunos vinculados.

Os formulários de vínculo oferecem usuários e alunos ativos. O cadastro rejeita registros inexistentes, desativados e vínculos repetidos. As telas usam proteção CSRF. As ações de desativação preenchem `deleted`, preservando os registros e suas relações. A opção **Reativar** aparece na listagem dos registros desativados; o vínculo só pode ser reativado se o usuário e o aluno estiverem ativos.

## API JSON

Os mesmos endpoints estão disponíveis para os recursos `perfis`, `usuarios`, `alunos` e `responsaveis`, usando o prefixo `/api`:

| Método | Caminho de exemplo | Resultado |
| --- | --- | --- |
| GET ou POST | `/api/responsaveis/listar.json` | Lista os vínculos ativos |
| GET | `/api/responsaveis/buscar/1.json` | Consulta um vínculo, incluindo usuário e aluno |
| POST | `/api/responsaveis/adicionar.json` | Cadastra um vínculo |
| POST, PUT ou PATCH | `/api/responsaveis/editar/1.json` | Edita o vínculo identificado na URL |
| POST ou DELETE | `/api/responsaveis/deletar/1.json` | Desativa o vínculo |
| POST, PUT ou PATCH | `/api/responsaveis/reativar/1.json` | Reativa o vínculo |

`desativar/ID` é um alias de `deletar/ID`, e `listar/ID` é um alias de `buscar/ID`. As consultas individuais e as relações também mostram registros desativados, com o campo `deleted`, para permitir a consulta do histórico. A desativação de um usuário ou aluno preserva seus vínculos.

Exemplo de cadastro do responsável:

```bash
curl -X POST http://localhost:8765/api/responsaveis/adicionar.json \
  -H 'Content-Type: application/json' \
  -d '{"users_id": 1, "alunos_id": 1}'
```

As consultas aceitam filtros por `users_id` e `alunos_id`, por exemplo `/api/responsaveis/listar.json?alunos_id=1`. `/api/alunos/buscar/1.json` retorna os responsáveis com os dados dos usuários. As senhas são armazenadas como hash e omitidas das respostas JSON.

`GET /api/alunos/findmat/1234.json` busca parte da matrícula (até 19 dígitos) e `GET /api/alunos/findname/Ana.json` busca parte do nome. As duas buscas retornam uma lista de alunos ativos com todos os responsáveis, incluindo alunos sem vínculos. Nomes aceitam acentos, espaços, apóstrofos e hífens; caracteres de busca inválidos retornam HTTP 422. O alias `/api/perfil` também atende às rotas de `/api/perfis`.

As respostas de sucesso usam a chave `data`; cadastros retornam HTTP 201, edições e consultas HTTP 200. Erros de validação retornam HTTP 422 com `message` e `errors`, IDs inexistentes retornam 404 e métodos não permitidos retornam 405.

O login existente é atendido por `POST /api/usuarios/entrar.json` (ou `/login/entrar`) com `email` e `password`. Ele verifica as credenciais e registra o usuário na sessão. O projeto acadêmico mantém as rotas de cadastro abertas; o login não implementa controle de permissões sobre elas.

As sessões usam `tmp/sessions`, criada pelo instalador do projeto. Essa pasta deve ter permissão de escrita para o processo PHP, assim como as demais pastas de `tmp`.

### Importação de alunos

Acesse **Alunos → Importar planilha** (`/alunos/importar`) ou envie um arquivo XLS ou XLSX para `POST /api/alunos/importar.json`, no campo de upload `excel_file` (a API também aceita `arquivo` e `file`). A leitura usa `phpoffice/phpspreadsheet`, já instalado no projeto, e a gravação usa o ORM do CakePHP.

A primeira aba deve ter os cabeçalhos na primeira linha, em qualquer ordem:

| Cabeçalho da planilha | Campo no banco |
| --- | --- |
| nome | nome |
| matricula | matricula |
| curso | curso |
| situacao | situacao |
| emailacademico | emaila |
| emailpessoal | emailp |
| anoperiodoletivo | anop |

Também são aceitos os títulos do `Relatorio.xlsx`: `Nome`, `Matrícula`, `Curso`, `Situação`, `E-mail Acadêmico`, `E-mail Pessoal` e `Ano/Periodo Letivo`. A comparação ignora maiúsculas, acentos, espaços e pontuação. Os nomes antigos `emaila`, `emailp` e `anop` continuam aceitos. Colunas extras, como `#`, `Campus` e `Polo`, são ignoradas; dois cabeçalhos para o mesmo campo são rejeitados.

Os sete cabeçalhos são obrigatórios. Os e-mails podem ter células vazias na importação e são gravados como texto vazio; os outros campos devem ser preenchidos. A matrícula deve conter apenas dígitos e caber em um BIGINT assinado. Para preservar matrículas longas no próprio Excel, prefira células de texto. Linhas com os sete campos vazios são ignoradas. A importação **atualiza pela matrícula ou cria um aluno novo**, preservando ID, responsáveis e estado de ativação. Reenviar a planilha não duplica os cadastros. Zeros à esquerda não diferenciam matrículas, pois o campo é numérico no banco. Matrículas repetidas na planilha ou com múltiplos cadastros no banco são rejeitadas com erro por linha, sem escolher arbitrariamente um aluno. Campos longos são rejeitados em vez de cortados; o texto da situação não desativa nem reativa o aluno automaticamente.

Antes de usar, aplique a migration que amplia `alunos.situacao` para 100 caracteres, pois o relatório tem situações maiores que o limite anterior de 25:

```bash
./bin/cake migrations migrate
```

Exemplo de envio pela API:

```bash
curl -X POST http://localhost:8765/api/alunos/importar.json \
  -F 'excel_file=@/home/eduardosilva/Documentos/Relatorio.xlsx'
```

O sucesso retorna HTTP 201 com `{"message":"Alunos importados com sucesso.","imported":6189,"inseridos":6189,"atualizados":0}` (exemplo de primeira importação). `imported` soma as linhas inseridas e atualizadas. Uma planilha inválida retorna HTTP 422 com `imported: 0` e `errors` indexados pelo número real da linha do Excel. A gravação é atômica: nenhuma linha é salva se houver erro de validação ou de regra de negócio. O arquivo temporário é removido ao terminar.

## Testes

```bash
composer test
```

A configuração de exemplo usa SQLite no banco de testes. Para testar no MySQL, configure um banco separado em `Datasources.test` ou informe `DATABASE_TEST_URL`. Use exclusivamente um banco de testes: a suíte recria o schema e limpa os dados.

Os testes cobrem as migrations pelas fixtures, o cadastro completo pelas telas, relações em ambas as direções, chaves estrangeiras, duplicidade, edição, desativação, reativação, senha, login e importação XLS/XLSX pela API e pela tela. A importação é verificada com cabeçalhos do relatório, nomes alternativos, e-mails vazios, matrículas longas, erros por linha, atomicidade e arquivos inválidos. Scaffolds vazios de controllers inexistentes (`Categoria` e `Marca`) foram removidos da suíte.
