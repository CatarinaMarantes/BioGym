# BioGym — Website de Gestão de Ginásio

Projeto da unidade curricular **Engenharia de Sistemas de Informação (ESIN)**, 2026/2027
Mestrado em Bioengenharia — Engenharia Biomédica · Turma M.BIOM.EBM_1B

## Autoras

| Nome | Email |
|---|---|
| Catarina Marantes | up202602687@up.pt |
| Cláudia Reis | up202609579@up.pt |
| Jheniffer Guimarães | up202511696@up.pt |

---

## Descrição

O BioGym é um website de gestão de um ginásio com três tipos de utilizadores:

- **Membros** — inscrevem-se em aulas, marcam sessões de personal training, consultam o estado dos equipamentos e acompanham a sua evolução física.
- **Treinadores** — gerem as suas aulas, os membros inscritos e as suas certificações.
- **Administradores** — gerem utilizadores, aulas, salas e equipamentos e supervisionam a plataforma.

Um utilizador pode acumular vários papéis (por exemplo, ser treinador e membro ao mesmo tempo), mas tem sempre pelo menos um.

### Tecnologias

- HTML, CSS e PHP (sem frameworks, bibliotecas externas nem JavaScript)
- SQLite, acedido através de PDO
- Docker, com a imagem `gfcg/vesica-php73:dev` (PHP 7.3 + Apache)

---

## Como executar

### 1. Pré-requisitos

- Docker Desktop (Windows/macOS) ou Docker Engine (Linux) instalado e em execução.

### 2. Arrancar o container

Substituir `<CAMINHO_DO_PROJETO>` pelo caminho da pasta que contém este README.

**Windows (PowerShell como administrador):**
```
docker run -d -p 9000:8080 -it --name=php -v <CAMINHO_DO_PROJETO>:/var/www/html gfcg/vesica-php73:dev
```
Exemplo: `-v C:\html:/var/www/html`

**Linux / macOS:**
```
sudo docker run -d -p 9000:8080 -it --name=php -v <CAMINHO_DO_PROJETO>:/var/www/html gfcg/vesica-php73:dev
```

### 3. Abrir o website

Abrir no browser: **http://localhost:9000**

Sem sessão iniciada, o website mostra a página de login. Depois do login, mostra a dashboard do utilizador.

A base de dados (`database/biogym.db`) já vem criada e povoada no ficheiro entregue.

### 4. (Opcional) Criar ou recriar a base de dados

A pasta `database/` está protegida por um `.htaccess` e não é acessível pelo browser. O script `init.php` também só corre a partir da linha de comandos.

Para criar a base de dados (por exemplo, depois de clonar o repositório, já que o `biogym.db` não está no Git) ou repô-la no estado inicial, executar com o container a correr:
```
docker exec -u www-data php php /var/www/html/database/init.php
```
Deve aparecer `Base de dados criada. Utilizadores: 5`. O script apaga o `biogym.db` e volta a criá-lo a partir de `create.sql` e `populate.sql`. O `-u www-data` garante que o ficheiro pertence ao utilizador do Apache, que precisa de escrever nele.

> **Atenção (Windows):** colocar o projeto fora do OneDrive (por exemplo em `C:\html`). Dentro do OneDrive, o servidor pode responder *Forbidden* por não conseguir ler os ficheiros.

### Comandos úteis do Docker

| Ação | Comando |
|---|---|
| Ver containers em execução | `docker ps` |
| Voltar a arrancar o container (nos dias seguintes) | `docker start php` |
| Parar o container | `docker stop php` |
| Apagar o container (os ficheiros do projeto não se perdem) | `docker rm -f php` |

---

## Contas de teste

A palavra-passe de **todas** as contas é `password123`.

| Nome | Email | Papel(éis) |
|---|---|---|
| Rui Almeida | admin@biogym.pt | Administrador |
| Carlos Silva | carlos.silva@biogym.pt | Treinador (Musculação, Spinning) |
| Ana Rocha | ana.rocha@biogym.pt | Treinadora (Yoga) + Membro |
| João Mendes | joao.mendes@gmail.com | Membro |
| Maria Santos | maria.santos@gmail.com | Membro |

---

## Estrutura do projeto

```
html/
├── README.md
├── index.php            Redireciona para o login ou para a dashboard
├── login.php            Login
├── registo.php          Registo de novo membro
├── dashboard.php        Painel do utilizador, com uma secção por papel
├── aulas.php            Aulas disponíveis, com filtros e inscrição/cancelamento
├── treinadores.php      Perfis dos treinadores (fotografia, biografia, modalidades, avaliação média)
├── equipamentos.php     Estado dos equipamentos (disponível, em uso, em manutenção)
├── perfil.php           Edição do perfil (nome, telemóvel, biografia, fotografia)
├── foto.php             Devolve a fotografia de perfil (só com sessão iniciada)
├── membro.php           (membro) Separadores: as minhas aulas, personal training, avaliações físicas, plano de treino
├── treinador.php        (treinador) Separadores: aulas, sessões de PT, avaliações, planos, certificações
├── admin.php            (administrador) Separadores: utilizadores, aulas, salas/equipamentos, certificações
├── actions/
│   ├── action_login.php
│   ├── action_logout.php
│   ├── action_registo.php
│   ├── action_perfil.php   Atualização do perfil e da fotografia
│   └── action_gestao.php   Todas as restantes operações (inscrições, PT, avaliações, planos, certificações, administração)
├── css/style.css        Folha de estilos (cores definidas como variáveis no início)
├── database/
│   ├── .htaccess        Bloqueia o acesso à pasta a partir do browser
│   ├── create.sql       Criação das tabelas
│   ├── populate.sql     Dados de teste
│   ├── init.php         (Re)cria biogym.db (só pela linha de comandos)
│   ├── biogym.db        Base de dados SQLite (não está no Git)
│   ├── connection.php   Ligação PDO e transações
│   ├── utilizador.php   Utilizadores, papéis, contas ativas/desativadas, perfil e fotografia
│   ├── aula.php         Aulas, inscrições, avaliação de aulas, criar/editar/cancelar aulas
│   └── gestao.php       Horários, personal training, avaliações, planos, certificações, salas e equipamentos
├── templates/common.php Header, footer, mensagens e componentes comuns
└── utils/session.php    Sessão, CSRF, permissões e validação de dados
```

`action_gestao.php` trata várias operações: cada formulário envia um campo escondido `op` (por exemplo `op=inscrever`) e `iniciarOperacao()` verifica o token CSRF e o papel exigido por essa operação. `membro.php`, `treinador.php` e `admin.php` mostram uma secção de cada vez, escolhida por `?sec=` (por exemplo `/admin.php?sec=aulas`).

### Organização do código

- **`database/`** — todo o SQL está aqui, em funções agrupadas por entidade (ex.: `utilizador.php`). As páginas nunca escrevem SQL diretamente, exceto em consultas simples.
- **HTML e CSS separados** — o HTML descreve só a estrutura, com elementos semânticos (`header`, `nav`, `main`, `section`, `article`, `footer`, `dl`, `time`). Toda a aparência está em `css/style.css`, sem atributos `style`, e as cores estão definidas como variáveis CSS no início do ficheiro.
- **`templates/`** — HTML reutilizável. Todas as páginas usam `drawHeader($titulo)` e `drawFooter()`.
- **`actions/`** — recebem formulários `POST`, validam os dados, alteram a base de dados e redirecionam (padrão *Post/Redirect/Get*). Não produzem HTML.
- **Páginas na raiz** — seguem sempre a mesma estrutura:
  ```php
  require_once __DIR__ . '/utils/session.php';
  require_once __DIR__ . '/database/connection.php';
  require_once __DIR__ . '/templates/common.php';
  // lógica e consultas
  drawHeader('Título');
  // conteúdo
  drawFooter();
  ```

---

## Base de dados

O modelo é composto por 14 tabelas:

| Grupo | Tabelas |
|---|---|
| Utilizadores e papéis | `Utilizador`, `Membro`, `Treinador`, `Administrador` |
| Espaços e equipamentos | `Sala`, `Equipamento` |
| Aulas | `Modalidade`, `Aula`, `Inscricao` |
| Treinadores | `Certificacao`, `SessaoPT` |
| Acompanhamento físico | `AvaliacaoFisica`, `PlanoTreino`, `ItemPlano` |

Os papéis são modelados como uma generalização: `Membro`, `Treinador` e `Administrador` usam o `id` do `Utilizador` como chave primária e estrangeira.

### Regras de negócio garantidas em PHP

Algumas regras não podem ser impostas apenas com restrições SQL e são verificadas no código:

- Um utilizador registado tem sempre pelo menos um papel (o registo cria `Utilizador` + `Membro` numa única transação).
- As vagas disponíveis de uma aula contam apenas as inscrições com estado `ATIVA`.
- O número de inscrições ativas numa aula não pode exceder as vagas (verificado numa transação `BEGIN IMMEDIATE`, para que duas inscrições em simultâneo não ultrapassem o limite).
- O número de vagas de uma aula não pode exceder a capacidade da sala.
- Um membro só pode avaliar (uma vez) aulas a que esteve inscrito e que já terminaram; as notas vão de 1 a 5.
- Uma sala ou um treinador não podem ter duas aulas/sessões sobrepostas, e um membro não pode ter duas atividades sobrepostas (`database/gestao.php`).
- Um treinador só pode dar aulas de modalidades em que tem certificação validada.
- Só se pode inscrever ou cancelar uma aula que ainda não começou; um treinador não se pode inscrever nas suas próprias aulas.
- Um membro que cancele uma inscrição pode voltar a inscrever-se (a inscrição cancelada é reativada, porque a chave é `(idMembro, idAula)`).
- Uma sessão de PT é criada como `PENDENTE`; o treinador confirma ou recusa, e só pode concluir sessões confirmadas que já começaram.
- Um utilizador tem sempre pelo menos um papel; um administrador não se retira a si próprio; um treinador com aulas, sessões, certificações ou avaliações não deixa de o ser.
- Só se editam aulas futuras. Com inscritos, a modalidade, a data e o horário ficam bloqueados e as vagas não podem ficar abaixo do número de inscritos. Cancelar uma aula cancela as inscrições ativas e liberta a sala e o treinador.
- Uma conta desativada não inicia sessão e, se já tinha sessão, perde-a no pedido seguinte. Ninguém desativa a própria conta, tem de sobrar um administrador ativo, e um treinador com aulas ou sessões futuras não pode ser desativado. Ao desativar um membro, cancelam-se as suas inscrições e sessões de PT futuras.
- Cada plano de treino pertence ao treinador que o criou: só esse treinador o vê, o edita e o atribui numa avaliação física.
- O nome e os papéis de quem tem sessão iniciada são relidos da base de dados em cada pedido (`atualizarSessao()`), por isso uma mudança de papéis vale logo, sem novo login.

---

## Segurança

| Ameaça | Medida implementada |
|---|---|
| **SQL injection** | Todas as consultas com dados do utilizador usam *prepared statements* (`prepare()` + `execute()`). |
| **XSS** | Todo o conteúdo dinâmico é escapado com `htmlspecialchars()` antes de ser mostrado. |
| **CSRF** | É gerado um token aleatório por sessão (`random_bytes`), incluído em todos os formulários `POST` e verificado com `hash_equals()` em todas as actions. |
| **Palavras-passe** | Guardadas com `password_hash()` (bcrypt) e verificadas com `password_verify()`. |
| **Sessões** | O id de sessão é regenerado após o login (`session_regenerate_id(true)`). |
| **Validação** | Os dados são validados no servidor, mesmo quando o HTML já os valida (`required`, `pattern`, `minlength`). |
| **Enumeração de contas** | O login devolve a mesma mensagem para email inexistente e para palavra-passe errada. |
| **Logout** | Feito por `POST` com token CSRF, e não por um link. |
| **Acesso à base de dados** | A pasta `database/` tem um `.htaccess` com `Require all denied`, para que o `biogym.db` e os scripts não possam ser descarregados nem executados pelo browser. O `init.php` recusa correr fora da linha de comandos. |
| **Upload de fotografia** | Só JPEG, PNG ou WebP (o tipo é lido do conteúdo, não do nome nem do tipo declarado), até 1 MB e 4000×4000 px. É guardada na base de dados e servida por `foto.php` com `X-Content-Type-Options: nosniff`. |
| **Páginas privadas e permissões** | Cada página e cada action exige sessão e o papel certo (`requerPapel()` / `iniciarAction()` / `iniciarOperacao()`); sem permissão, redireciona com mensagem de erro. Esconder um botão não chega: a verificação é sempre feita no servidor. |

---

## Funcionalidades

### Implementadas

- [x] Ambiente Docker configurado e testado
- [x] Base de dados criada e povoada a partir dos scripts SQL
- [x] Ligação PDO com chaves estrangeiras ativas
- [x] Header, footer e mensagens de sucesso/erro comuns a todas as páginas
- [x] Registo de novos membros (com validação e transação)
- [x] Login e logout com sessões
- [x] Menu que muda conforme o utilizador tenha ou não sessão iniciada
- [x] Dashboard com uma secção por papel (membro, treinador, administrador)
- [x] Resumo do membro: próximas aulas inscritas, sessões de PT agendadas e último peso registado
- [x] Listagem das próximas aulas (modalidade, nível, data, horário, sala, treinador e vagas disponíveis)
- [x] Pasta `database/` protegida contra acesso pelo browser
- [x] Inscrição e cancelamento de inscrição em aulas, com página «As minhas aulas»
- [x] Marcação e cancelamento de sessões de personal training (membro) e confirmação/recusa/conclusão (treinador)
- [x] Consulta das avaliações físicas e do plano de treino (membro)
- [x] Registo de avaliações físicas e gestão de planos de treino e exercícios (treinador)
- [x] Listagem das aulas do treinador com os membros inscritos
- [x] Registo de certificações (treinador) e validação/rejeição (administrador)
- [x] Gestão de utilizadores e papéis, aulas, salas e equipamentos (administrador)
- [x] Revisão do design: contrastes de cor conforme WCAG AA, botões, tabelas e estados reutilizáveis

- [x] Filtros na listagem de aulas (modalidade, nível, data, treinador)
- [x] Avaliação de aulas e treinadores (1 a 5 + comentário) na página «As minhas aulas»
- [x] Perfis dos treinadores, com avaliação média
- [x] Estado dos equipamentos para os membros
- [x] Edição do perfil, incluindo fotografia (todos os papéis)
- [x] Administração: editar e cancelar aulas, ativar/desativar contas
- [x] Planos de treino com dono (cada treinador só vê e edita os seus)

### Por implementar

- [ ] Alteração da palavra-passe pelo próprio utilizador
- [ ] Recuperação de palavra-passe

---

## Histórico de desenvolvimento

### 1.ª entrega
- Descrição do tópico, diagrama UML e modelo relacional.
- Scripts SQL de criação (`create.sql`) e povoamento (`populate.sql`).

### Revisão dos dados de teste
- Corrigido o hash das palavras-passe, que correspondia a `password` e não a `password123`.
- Removido o papel dos nomes e emails dos utilizadores (ex.: "Carlos Silva (Treinador)" → "Carlos Silva"), já que os papéis são guardados nas tabelas próprias.
- Acrescentado um utilizador com dois papéis (Ana Rocha: treinadora e membro).
- Acrescentada a certificação de Spinning ao Carlos Silva, que dá a aula de Spinning.
- Corrigidas as inscrições, que apontavam para aulas que já não existiam (erro de chave estrangeira ao criar a base de dados).
- Acrescentadas aulas já decorridas com avaliações, inscrições em aulas futuras, uma inscrição cancelada e sessões de PT futuras, para testar todas as funcionalidades.
- Corrigida uma vírgula em falta na tabela `Certificacao` do `create.sql`.

### 2.ª entrega (em curso)
1. Configuração do Docker e teste de PHP + SQLite.
2. Estrutura de pastas e script `init.php` para criar a base de dados.
3. Ligação PDO (`connection.php`).
4. Templates comuns (header/footer) e CSS base.
5. Registo, login e logout com proteção CSRF.
6. Página inicial com as próximas aulas e informação completa (JOIN com `Modalidade`, `Sala` e `Utilizador`).
7. Página de aulas com vagas disponíveis (contando só inscrições ativas).
8. Dashboard por papel, com o resumo do membro. O `index.php` passa a redirecionar para o login ou para a dashboard.
9. Proteção da pasta `database/` (`.htaccess`) e do `init.php` (só linha de comandos).
10. Revisão da separação estrutura/apresentação: elementos semânticos em vez de `<div>`, texto em maiúsculas feito com CSS e não no HTML, CSS reorganizado com variáveis e sem regras duplicadas.
11. Inscrição e cancelamento de aulas, com regras de vagas, horário e transação.
12. Personal training (marcação, confirmação, conclusão), avaliações físicas, planos de treino e certificações.
13. Área de administração: utilizadores e papéis, aulas, salas, equipamentos e validação de certificações.
14. Revisão do design: tokens de cor com contraste mínimo WCAG AA (4.5:1), componentes comuns (botões, tabelas, estados) e dashboard sem cartões «Em desenvolvimento».
15. Simplificação da estrutura: páginas de membro, treinador e administrador reunidas em `membro.php`, `treinador.php` e `admin.php` com separadores; actions novas reunidas em `action_gestao.php`; funções de base de dados novas reunidas em `database/gestao.php`.
16. Funcionalidades que faltavam: filtros de aulas, avaliação de aulas, perfis de treinadores, estado dos equipamentos, perfil com fotografia.
17. Administração: editar e cancelar aulas (`cancelada` em `Aula`), ativar/desativar contas (`ativo` em `Utilizador`), papéis atualizados em cada pedido.
18. Código: planos de treino com dono (`idTreinador` em `PlanoTreino`), ligação PDO única por pedido, campos de formulário em `fieldset`/`p` em vez de `div`, cores soltas passadas para variáveis CSS.
19. Dados de teste com datas relativas (`date('now', '+3 days')`), para haver sempre aulas e sessões futuras.

---

## Notas

- As datas dos dados de teste são relativas ao dia em que o `biogym.db` é criado. **Recriar a base de dados (passo 4) no próprio dia da entrega**, antes de gerar o ZIP, para haver aulas futuras.
- Esta versão alterou o esquema (`Utilizador.ativo`, `Aula.cancelada`, `PlanoTreino.idTreinador`): quem tiver um `biogym.db` antigo tem de o recriar com o passo 4.
- O código não usa as extensões `mbstring` nem `gd`, que podem não existir na imagem Docker.
- A imagem Docker usa **PHP 7.3**, por isso não são usadas funcionalidades de versões posteriores (`fn() =>`, `match`, `str_contains`, `?->`, argumentos com nome).
- **Antes da entrega final:**
  - criar o `biogym.db` com o comando do passo 4 e incluí-lo no ZIP, já que o professor só deve precisar de correr o `docker run`;
  - validar o HTML de cada página em [validator.w3.org](https://validator.w3.org/) e o CSS em [jigsaw.w3.org/css-validator](https://jigsaw.w3.org/css-validator/).
- Se aparecer o erro *"attempt to write a readonly database"*, a pasta `database/` (e não só o ficheiro `.db`) tem de ter permissões de escrita dentro do container.
