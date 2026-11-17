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
├── index.php                 Redireciona para o login ou para a dashboard
├── login.php                 Formulário de login
├── registo.php               Formulário de registo de novo membro
├── dashboard.php             Painel do utilizador, com uma secção por papel
├── aulas.php                 Listagem das próximas aulas, com vagas disponíveis
├── actions/                  Scripts que processam formulários (POST)
│   ├── action_login.php
│   ├── action_logout.php
│   └── action_registo.php
├── css/
│   └── style.css             Folha de estilos do website
├── database/
│   ├── .htaccess             Bloqueia o acesso à pasta a partir do browser
│   ├── create.sql            Script de criação das tabelas
│   ├── populate.sql          Dados de teste
│   ├── init.php              (Re)cria biogym.db a partir dos scripts SQL (só pela linha de comandos)
│   ├── biogym.db             Base de dados SQLite (não está no Git)
│   ├── connection.php        Ligação PDO à base de dados
│   ├── utilizador.php        Queries relacionadas com utilizadores
│   ├── aula.php              Queries relacionadas com aulas
│   └── dashboard.php         Queries do resumo mostrado na dashboard
├── images/                   Imagens do website
├── templates/
│   └── common.php            Header, footer e mensagens comuns a todas as páginas
└── utils/
    └── session.php           Sessão, token CSRF e funções auxiliares
```

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
- *(a implementar)* O número de inscrições ativas numa aula não pode exceder as vagas.
- *(a implementar)* O número de vagas de uma aula não pode exceder a capacidade da sala.
- *(a implementar)* Um membro só pode avaliar aulas a que esteve inscrito e que já decorreram.
- *(a implementar)* Uma sala ou um treinador não podem ter duas aulas/sessões sobrepostas.
- *(a implementar)* Um treinador só pode dar aulas de modalidades em que tem certificação validada.

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
| **Páginas privadas** | A dashboard redireciona para o login quando não há sessão iniciada. |

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

### Por implementar

**Membros**
- [ ] Filtros na listagem de aulas (modalidade, nível, data, treinador)
- [ ] Inscrição e cancelamento de inscrição em aulas
- [ ] Avaliação de aulas e treinadores (1 a 5 + comentário)
- [ ] Consulta de perfis de treinadores
- [ ] Consulta do estado dos equipamentos
- [ ] Marcação de sessões de personal training
- [ ] Consulta das avaliações físicas e do plano de treino
- [ ] Edição do perfil (incluindo fotografia)

**Treinadores**
- [ ] Gestão do próprio perfil e certificações
- [ ] Listagem das aulas atribuídas e dos membros inscritos
- [ ] Confirmação/conclusão de sessões de personal training
- [ ] Registo de avaliações físicas

**Administradores**
- [ ] Gestão de membros e treinadores
- [ ] Gestão de aulas e atribuição de treinadores
- [ ] Gestão de salas e equipamentos
- [ ] Promoção de utilizadores a administrador
- [ ] Validação de certificações

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

---

## Notas

- A imagem Docker usa **PHP 7.3**, por isso não são usadas funcionalidades de versões posteriores (`fn() =>`, `match`, `str_contains`, `?->`, argumentos com nome).
- **Antes da entrega final:**
  - criar o `biogym.db` com o comando do passo 4 e incluí-lo no ZIP, já que o professor só deve precisar de correr o `docker run`;
  - remover os cartões "Em desenvolvimento" da dashboard que não tenham sido implementados;
  - validar o HTML de cada página em [validator.w3.org](https://validator.w3.org/) e o CSS em [jigsaw.w3.org/css-validator](https://jigsaw.w3.org/css-validator/).
- Se aparecer o erro *"attempt to write a readonly database"*, a pasta `database/` (e não só o ficheiro `.db`) tem de ter permissões de escrita dentro do container.
