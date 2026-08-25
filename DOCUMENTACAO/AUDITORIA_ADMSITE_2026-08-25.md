# Auditoria de segurança — painel administrativo (admsite) — 25/08/2026

Escopo: `public_html/admsite/` (painel administrativo do site, 667 arquivos `.php` próprios, fora de `library/`/`classes/vendor/` que são bibliotecas de terceiros). Complementa a auditoria de estrutura/código de 19/08/2026 (`AUDITORIA_ESTRUTURA_CODIGO_2026-08-19.md`), que cobriu principalmente a raiz de `public_html/` e o fluxo de login. Esta auditoria foi pedida especificamente para revisar o restante do painel administrativo.

## Resultado executivo

Esta auditoria encontrou uma superfície de risco bem maior do que a de 19/08: o painel administrativo é composto por ~30 módulos com o mesmo padrão legado repetido (SQL por concatenação, escape inconsistente, controle de acesso decorativo), e pelo menos **dois achados eram exploráveis por qualquer visitante da internet sem nenhum login** — uma execução remota de código via upload de arquivo, e uma injeção SQL sem autenticação capaz de sobrescrever qualquer coluna de qualquer tabela (incluindo, por exemplo, a senha de uma conta de administrador). Por serem falhas ativas em produção, **ambas foram corrigidas de emergência durante esta auditoria**, com autorização do usuário, antes mesmo de terminar de escrever este relatório — ver seção "Ações de emergência já executadas" abaixo.

O restante dos achados (SQL injection mitigada apenas por escaping fraco, XSS armazenado em módulos com exposição pública indireta, controle de acesso por permissão desativado no código, sessão sem proteção contra fixação, bibliotecas desatualizadas) é sério, mas não estava sendo explorado ativamente no momento da auditoria — fica registrado abaixo como plano de correção priorizado, para execução em lotes, com testes, como já é o padrão deste projeto.

## Ações de emergência já executadas em produção (25/08/2026)

Com autorização do usuário, antes de concluir o restante do relatório:

### 1. Execução remota de código via upload de arquivo (P0)

Três endpoints de upload (`file_upload/server/php/arquivos.php`, `upload_arquivo/upload_multi_arquivos_processar.php`, `upload_arquivo/upload_arquivo_processar.php`) aceitavam qualquer extensão de arquivo sem validação de conteúdo, dois deles sem exigir login algum, salvando o arquivo dentro do webroot (`public_html/arquivos/`) — um visitante anônimo podia enviar um `shell.php` e executá-lo diretamente. Um quarto ponto, `upload_foto/upload_foto_processar.php` (usado pelo cadastro de banners), tinha a checagem de login **comentada** — a versão antiga preservada em `upload_foto/old/` confirma que isso é uma regressão recente, não um design original. Um quinto, `upload_foto/upload_foto_crop.php`, também sem login, permitia ler qualquer arquivo do sistema decodificável como imagem via um parâmetro `filename` sem sanitização (path traversal).

**Correção aplicada:**
- Novo `public_html/images/.htaccess` e `public_html/arquivos/.htaccess` (cobre `images/galeria/` e qualquer subpasta futura por herança) — bloqueiam com `Require all denied` a execução de `.php`/`.phtml`/`.phar`/`.cgi`/`.pl` nesses diretórios, independente de qual script fez o upload ou de qual extensão o arquivo enviado tem. Testado: arquivo `.php` de teste colocado manualmente nas duas pastas (raiz e subpasta `galeria/`) retornou **403** em vez de executar; imagens legítimas existentes continuam servidas normalmente (**200**, `image/jpeg`); site público e admsite continuam no ar (**200**).
  - Nota técnica: a primeira tentativa (trocar o `SetHandler` do PHP-FPM via `.htaccess`) não funcionou porque o vhost do Apache define o roteamento do PHP dentro de um bloco `<FilesMatch \.php$>`, que é processado **depois** do `.htaccess` na ordem de mesclagem do Apache — por isso a correção final usa `Require all denied` (fase de controle de acesso, que roda antes do handler ser escolhido), o mesmo padrão já validado no `.htaccess` principal do site.
- `upload_foto/upload_foto_processar.php` e `upload_foto/upload_foto_crop.php`: restaurada a exigência de login (`Login::verificaToken()`) — a versão anterior tentava chamar `Login::check()`, um método que **não existe** na classe `Login`, ou seja, mesmo se a linha fosse só descomentada sem essa correção, teria dado erro fatal. Testado: requisição sem token autenticado retorna **403** nos dois arquivos; funcionalidade de upload/corte de foto para quem está logado não foi alterada.
- `upload_foto/upload_foto_crop.php`: parâmetro `filename` agora passa por `basename()` antes de montar o caminho do arquivo, fechando a leitura arbitrária de arquivo.

Os demais achados de upload (endpoints sem `checkAcao` granular, whitelist de extensão comentada em `upload_multi_arquivos_processar.php`, biblioteca WideImage de 2011, limite de tamanho quebrado em `upload_galerias_fotos_processar.php`, código morto com defaults perigosos) continuam **mitigados pelo bloqueio de execução acima** (não é mais possível obter RCE por essa via, mesmo sem essas correções), mas ficam como P1 pendente de correção definitiva — ver plano abaixo.

### 2. SQL injection sem autenticação via reordenação de itens (P0)

Quatro endpoints AJAX (`perguntas_e_respostas/organizar_ajax.php`, `perguntas_e_respostas_areas/organizar_ajax.php`, `perguntas_e_respostas_grupo/organizar_ajax.php`, `banners/organizar_ajax.php`) montavam um `UPDATE` de banco concatenando o **nome da tabela** e o **id** direto da requisição, sem validar nada e **sem exigir login**. Como o nome da tabela é colado cru na string SQL antes de `SET`, um visitante anônimo podia enviar um valor malicioso no campo `tabela` que reescrevesse a instrução inteira — por exemplo, sobrescrever a senha de uma conta de administrador diretamente, sem precisar de usuário/senha algum.

**Correção aplicada** nos 4 arquivos: (1) passou a exigir `Login::verificaToken()` válido; (2) o nome da tabela agora é validado contra uma lista fixa de um único valor permitido (o nome real daquele módulo — `perguntas_e_respostas`, `perguntas_e_respostas_areas`, `perguntas_e_respostas_grupo`, `banners`); (3) os valores de `id`/`ordem` agora passam por `AjustaInteiroGravar()` antes de entrar na consulta. Testado: requisição sem login retorna **403** nos 4 endpoints; conferido no banco que a tabela `usuarios` não foi alterada pelos testes; funcionalidade de reordenar itens (arrastar e soltar) continua igual para quem está logado, já que o JavaScript já enviava o token e o nome da tabela correto.

Backup dos 8 arquivos originais (antes de qualquer alteração) em `/root/backup_emergencia_20260825/` no servidor. Cópia local (Dropbox) sincronizada e conferida idêntica à produção nos 8 arquivos.

### 3. SQL injection sem nenhum escape em fóruns públicos, contato e notícias (item 3 do plano) — 25/08/2026

Corrigidos, com backup e testes diretos contra o banco (inserindo payloads de ataque de verdade e conferindo que ficam gravados como texto puro, sem executar), os 8 pontos listados na seção "SQL injection sem nenhum escape" abaixo:

- `classes/formulario_contatos/FormularioContatos.Class.php` — `save()` reescrito para usar consultas parametrizadas (prepared statements) de verdade, em vez de concatenação de texto.
- `classes/forum_advogado/ForumAdvogado.Class.php` e `classes/forum_consumidor/ForumConsumidor.Class.php` — `savePergunta()`, `saveResposta()` e `atualisaStatusResposta()` agora escapam texto e convertem IDs para número antes de gravar.
- `classes/forum_consumidor/ForunsDenuncias.Class.php` — `saveDenuncia()` e `excluir()` idem. (Durante o teste, uma primeira tentativa de correção tratou o campo `topico` como texto; o teste real contra o banco revelou que essa coluna é numérica, não texto — corrigido antes de publicar.)
- `admsite/noticias/db.php` — corrigido bug de variável trocada que enviava a lista de especialidades **sem** o filtro de segurança já pronto no código; e `classes/noticias_especialidades/NoticiasXEspecialidades.Class.php::save()` passou a converter os IDs para número por conta própria, como proteção extra.
- `admsite/noticias/organizar_ajax.php` — passou a exigir login (não exigia nenhum) e a converter os valores de reordenação para número antes de gravar.
- `admsite/newsletter/newsletter_ver_fonte.php` — corrigido bug de variável trocada que descartava o valor já validado do parâmetro `id` e usava o valor bruto da requisição na consulta; aproveitado para também proteger contra código malicioso o título e o HTML exibidos nessa tela (estavam sem essa proteção).
- `classes/noticias/Noticias.Class.php` — `getSqlPesquisaAdm()` passou a converter a lista de especialidades para números antes de montar a consulta.

Testado: inserção normal, edição, e tentativas de ataque reais (aspas, subconsultas, `DROP TABLE`) em cada um dos métodos alterados, direto contra o banco de teste, com `ROLLBACK` ao final (nenhum dado de teste ficou gravado). Nenhum erro novo apareceu no log do servidor; site público, formulário de contato, notícias e admsite testados e funcionando normalmente.

### 4. Controle de acesso, sessão e uploads (itens 5, 6, 9 e 10 do plano) — 25/08/2026

**Antes de mexer no código**, foi descoberto que ativar o bloqueio de permissão (abaixo) quebraria duas telas reais do painel — "Contato" (`institucional/inst_fale_conosco.php`) e a tela ligada a "Trabalhe Conosco" (`contato/trabalhe_conosco.php`) — porque as duas contas de administrador existentes não tinham, no banco, o registro de permissão para essas telas específicas (provavelmente esquecido quando essas telas foram criadas). Corrigido primeiro no banco (adicionado o registro de permissão completo para as duas contas existentes, nível 1), testado, e só depois habilitado o bloqueio no código — para não trocar uma falha de segurança por uma tela quebrada.

- **`classes/permissao/Permissao.Class.php`** — o bloqueio que estava comentado (item P0 "Controle de acesso por permissão desativado") agora funciona de verdade: quem não tem permissão para uma tela recebe uma mensagem de acesso negado em vez de conseguir ver o conteúdo. Testado diretamente: as contas existentes (nível 1) continuam com acesso a tudo, incluindo as duas telas corrigidas no banco; uma conta de nível sem nenhuma permissão é bloqueada corretamente.
- **`admsite/principal.php`** — o parâmetro que escolhe qual tela abrir (`pg=`) agora é validado: só aceita um caminho que exista de fato **dentro** da pasta do painel e termine em `.php`, fechando a brecha que permitiria tentar acessar arquivos fora dessa pasta. Testado com várias tentativas de fuga de pasta (`../../etc/passwd` e variações) — todas rejeitadas; telas normais continuam abrindo normalmente.
- **`classes/autoload/Login.Class.php`** — a sessão agora é regenerada logo após o login ser confirmado, fechando a brecha de fixação de sessão.
- **Uploads sem verificação de tipo de arquivo** (`upload_arquivo/upload_arquivo_processar.php`, `upload_arquivo/upload_multi_arquivos_processar.php`, `file_upload/server/php/arquivos.php`) — passaram a exigir login (dois deles não exigiam nenhum) e a checar a extensão do arquivo contra uma lista de tipos permitidos (imagens e documentos comuns), além de sanitizar o nome do arquivo recebido. Isso é uma proteção extra por cima do bloqueio de execução já aplicado nas pastas de destino (ação de emergência 1) — mesmo que uma das duas falhe, a outra seguraria.
- **`upload_foto/upload_galerias_fotos_processar.php`** — corrigido o erro de cálculo do limite de tamanho (permitia até 50 GB apesar da mensagem dizer "500 MB"); agora o limite real é 500 MB. Também passou a exigir login (não exigia).
- **`classes/.htaccess`** (novo) — bloqueia qualquer acesso direto pelo navegador à pasta que concentra toda a lógica de negócio do painel (nada legítimo precisa acessá-la assim). Testado: acesso direto retorna erro de acesso negado; painel continua funcionando normalmente (a pasta continua acessível internamente pelo código, só não pode mais ser aberta como página).

Testado em conjunto: login, navegação e as duas telas corrigidas de permissão precisam de confirmação ao vivo no navegador (feito nas rodadas anteriores para login/senha; pendente confirmação específica destas mudanças — ver observação abaixo).

### 5. XSS armazenado explorável por visitante anônimo (item 4 do plano) — 25/08/2026

Corrigidos os 13 arquivos que exibiam, sem nenhuma proteção contra código malicioso, dados originados de formulários públicos (auto-cadastro de advogado, fale conosco/reserva/trabalhe conosco, opinião, fórum de advogados) ou de conteúdo externo curado (notícias, newsletter):

- `advogados/cadastro.php` — 14 campos do cadastro de advogado (nome, OAB, cpf, e-mail, site, cep, endereço, bairro, telefone, celular, empresa, cnpj, destaque) e 2 IDs (estado/cidade, usados dentro de um trecho de JavaScript, corrigidos com conversão para número em vez de proteção de HTML, que não funcionaria nesse contexto).
- `contato/listar.php`, `ver.php`, `listar_reserva.php`, `ver_reserva.php`, `trabalhe_conosco_ver.php` — nome, e-mail, telefone, assunto, mensagem e campos do formulário de reserva em grupo.
- `opiniao/listar.php` — nome e texto da opinião.
- `forum_advogados_topicos/topico.php` e `listar.php` — assunto e mensagem de perguntas/respostas do fórum.
- `forum_advogados_peticoes/listar.php` — título da petição, nome do advogado, OAB e área (menor severidade, exige conta de advogado, mas corrigido pelo mesmo padrão).
- `noticias/listar.php` e `cadastro.php` — título e 8 outros campos de notícia (a notícia pode vir de um robô de coleta de fontes externas antes da aprovação humana).
- `classes/newsletter/Newsletter.Class.php` — nome do informativo e título da notícia, exibidos duas vezes na montagem do e-mail de newsletter.

Testado: confirmado que um trecho de código malicioso de teste é corretamente neutralizado (vira texto inofensivo em vez de executar) e que a combinação com a função que converte quebra de linha em `<br/>` continua funcionando (quebra de linha preservada, código malicioso neutralizado). Site público, painel administrativo, notícias e cadastro de advogado testados e funcionando normalmente; nenhum erro novo no log do servidor.

## Achados pendentes — P0 (graves, mas sem exploração pública ativa confirmada hoje)

### Controle de acesso por permissão desativado no código

`admsite/classes/permissao/Permissao.Class.php:30-45` (`setSessao()`) chama `checkSessao($sessaoId)` para saber se o nível do usuário logado tem permissão para aquela tela, mas a linha que deveria bloquear o acesso está **comentada**:
```php
if($checkSessao && !$this->checkSessao($sessaoId)){
#    throw new ExceptionCustom("Você não tem permissão para esta sessão.");
}
```
Ou seja, o resultado da checagem é sempre descartado. O menu lateral (`classes/autoload/Menu.Class.php:46,68`) filtra corretamente o que aparece para cada nível — mas isso é só cosmético: o despachante principal (`admsite/principal.php:100-106`) só exige um token de sessão válido (`Login::verificaToken()`) para incluir qualquer página pedida via `?pg=`, sem checar se o nível do usuário tem permissão para aquele módulo específico. Na prática, **qualquer conta logada no admsite consegue visualizar qualquer tela de listagem/relatório do painel**, mesmo que o menu nunca mostre esse link para o nível dela.

Ações de **escrita** (inserir/editar/excluir) são, na maior parte dos módulos, protegidas separadamente por `$permissao->checkAcao(...)` dentro do próprio `cadastro.php`/`cadastro_db.php` — essa parte funciona. O problema é específico à **leitura** (visualização de listagens/relatórios).

Impacto prático hoje: **baixo** — só existem 2 contas no admsite (`admin` e `lisandro`), ambas nível 1 (o nível mais alto), então não há hoje nenhuma conta de nível restrito para explorar essa falha. Mas é uma falha de design que precisa ser corrigida **antes** de criar qualquer conta de acesso limitado (ex.: um assistente que só deveria ver alguns módulos).

**Ação:** descomentar e implementar de fato o bloqueio em `Permissao::setSessao()` (lançar exceção ou redirecionar), e testar com uma conta de nível restrito antes de confiar nisso.

### Endpoint próprio do dispatcher aceita qualquer caminho de arquivo (`pg=`)

`admsite/principal.php:100-106`:
```php
if ((isset($_GET['pg'])) && (Login::verificaToken(get('token')))) {
    $pagina = dirname(__FILE__) . '/' . get('pg');
}
if (existe_arquivo($pagina)) {
    require_once $pagina;
}
```
`get('pg')` é `$_GET['pg']` cru, e `existe_arquivo()` (`classes/funcoes.php:3-10`) é só um `file_exists()` — nenhum dos dois sanitiza `../`. Qualquer conta autenticada no admsite (nível 1 ou não) pode, em tese, incluir qualquer arquivo `.php` do servidor que o usuário do PHP-FPM consiga ler, navegando para fora da pasta `admsite/`.

**Ação:** validar `pg` contra uma lista de páginas conhecidas (ou, no mínimo, usar `realpath()` e confirmar que o resultado começa com o diretório `admsite/`), rejeitando qualquer `../`.

### SQL injection sem nenhum escape (dado alimentável por visitante anônimo)

Estas gravações usam concatenação de string **sem sequer `ajustaStringBD()`** — ou seja, sem proteção nenhuma:

| Arquivo | Função/linha | Origem do dado |
|---|---|---|
| `classes/formulario_contatos/FormularioContatos.Class.php:34-39` | `save()` | Formulário público de contato/reserva (`contato_db.php`, fora do admsite) |
| `classes/forum_advogado/ForumAdvogado.Class.php:482-483,504` | `savePergunta()`, `saveResposta()` | Fórum de advogados (público) |
| `classes/forum_consumidor/ForumConsumidor.Class.php:535-536,557` | `savePergunta()`, `saveResposta()` | Fórum do consumidor (público) |
| `classes/forum_consumidor/ForunsDenuncias.Class.php:230-236` | `saveDenuncia()` | Denúncia via fórum (público) |
| `admsite/noticias/db.php:93` + `classes/noticias_especialidades/NoticiasXEspecialidades.Class.php:26-37` | `save()` | Bug de variável trocada: usa o array de especialidades **antes** de `AjustaInteiroGravar`, não depois — explorável por qualquer conta autenticada que cadastre notícia |
| `admsite/noticias/organizar_ajax.php:674-679` + `saveBD()` | reordenar notícias | Sem exigir permissão de módulo; valores de `saveBD` não são colocados entre aspas nem escapados |
| `admsite/newsletter/newsletter_ver_fonte.php:17` | visualizar "código-fonte" do informativo | Bug de variável trocada: calcula `$id` sanitizado mas usa `$_REQUEST["id"]` cru na query |
| `classes/noticias/Noticias.Class.php:852` (`getSqlPesquisaAdm`) | filtro de especialidades na busca de notícias | Array de `post('especialidades')` sem cast, direto num `IN (...)` |

**Ação:** migrar essas gravações/consultas para `PDO::prepare()` com parâmetros — são poucos pontos e concentram o risco mais alto do painel, por serem alimentados por formulários públicos sem exigir login.

### ~~XSS armazenado explorável por visitante anônimo~~ — corrigido, ver item 5 da seção de ações acima (25/08/2026)

**Ação:** aplicar `htmlspecialchars($valor, ENT_QUOTES, 'UTF-8')` em cada ponto de saída listado. É uma correção mecânica e de baixo risco de regressão (só muda como o texto é exibido, não a lógica).

### Sessão sem proteção contra fixação de sessão

`classes/autoload/Login.Class.php` (`setSession()`, chamada após login bem-sucedido) nunca chama `session_regenerate_id()`. Um atacante que consiga fixar um `PHPSESSID` na vítima antes do login (ex.: por um link) herda a sessão já autenticada depois.

**Ação:** adicionar `session_regenerate_id(true)` logo após confirmar a senha, antes de gravar os dados da sessão.

### ~~Conteúdo do editor de texto (TinyMCE) sem sanitização no servidor~~ — corrigido (25/08/2026), ver item 8 abaixo

Correção resumida abaixo; nota de precisão: a investigação inicial (na auditoria) dizia que não havia nenhum filtro — na prática existia um filtro parcial (`ajustaTextAreaBD()` já removia tags fora de uma lista pequena, via `strip_tags()`), mas esse filtro **não olha para os atributos das tags permitidas** — ou seja, um `<a href="javascript:...">` ou um `<img onerror="...">` passavam intactos, porque `<a>` e `<img>` estão na lista de tags permitidas. Essa é a brecha real que foi fechada.

**Ação:** aplicar uma biblioteca de sanitização de HTML (ex. HTMLPurifier) com whitelist de tags antes de persistir o texto — ou, no mínimo, antes de exibir no site público.

## Achados pendentes — P1

- **SQL injection mitigada só por `addslashes` (ajustaStringBD), não por prepared statements**: padrão recorrente (~30+ pontos) em praticamente todas as telas de busca/filtro do painel (`noticias`, `advogados`, `consumidores`, `contato`, `forum_*`, `perguntas_e_respostas*`, `banners`, `institucional`, `outros_servicos`, `newsletter`, `noticias_fonte`, `noticias_especialidades`, `opiniao`). Não é uma injeção grave hoje, mas é uma proteção frágil (não trata charset da conexão) espalhada por todo o legado. Ação: migração gradual para prepared statements, mesmo trabalho já feito no login.
- **XSS armazenado sem exposição pública confirmada hoje, mas sem escape**: `consumidores/listar.php`, `denuncias/listar.php` (fluxo de denúncia pública parece desativado atualmente, mas o dado antigo continua exibido sem escape), `usuario/listar.php` e `usuario/nivel_listar.php` (exploração exigiria uma conta admin maliciosa contra outra).
- **SwiftMailer 5.4.12** (biblioteca de envio de e-mail) — fim de vida desde nov/2021, sem correção disponível na série 5.x para o `CVE-2024-28859` (risco relevante só se dados de usuário chegarem a `unserialize()` no fluxo de e-mail — não confirmado, mas é o tipo de biblioteca que deveria ser trocada por padrão). Ação: migrar para Symfony Mailer (já apontado como pendente desde a auditoria de 02/08).
- **TinyMCE 4.4.3 ativo** — anterior a correções de XSS conhecidas da série 4.x (GHSA-vrv8-v4w8-f95h, entre outras). Ação: atualizar para uma versão atual (TinyMCE 6/7) ou outro editor mantido.
- **Ausência de `.htaccess` protegendo `admsite/classes/`** — essa pasta concentra toda a lógica de negócio e depende inteiramente de nenhuma classe ter efeito colateral se acessada direto por URL. Ação: bloquear acesso HTTP direto a `classes/` inteira.
- **Falta de `checkAcao()` granular nos endpoints de upload** — qualquer conta do admsite consegue fazer upload em qualquer módulo, independente de permissão. Ação: aplicar `$permissao->checkAcao('upload')` (padrão já usado em `cadastro_galeria_ajax.php`).
- **Limite de tamanho quebrado em `upload_foto/upload_galerias_fotos_processar.php:30`** — a mensagem diz "500 MB" mas o código checa 50 GB (typo no multiplicador), viabilizando esgotamento de disco. Ação: corrigir o multiplicador.
- **Código de debug esquecido em produção**: `classes/permissao/../forum_pecas/ForumPecas.Class.php:90` tem um `echo $sSql;` ativo; `file_upload/server/php/postAcceptor.php:7-8` tem `print_r2($_FILES); die();` sem autenticação. Ação: remover.

## Achados pendentes — P2 / estrutura e limpeza

- `admsite/error_log` (~7 KB, entradas de 2020/2021 com caminhos completos do servidor) — mover para fora do webroot ou apagar.
- Três cópias mortas do TinyMCE em `library/` (`tiny_mce_old/`, `tiny_mce_31-10-2016/`, `tiny_mce-03-11-2016/`, ~820 arquivos, nenhuma referência ativa) — arquivar.
- `upload_foto/old/` — cópia duplicada e desatualizada do fluxo de upload de foto, ainda executável via URL direta — remover.
- `file_upload/server/php/test/` — página de teste QUnit de uma biblioteca de terceiros, publicamente acessível, faz upload/exclusão reais contra produção se alguém abrir — remover do deploy.
- `file_upload/server/php/UploadHandler.php` — classe morta (não instanciada), com defaults perigosos (aceita qualquer extensão) — remover para não ser reativada por engano no futuro.
- Arquivos de lixo (`Thumbs.db`, `script.log`) versionados dentro de bibliotecas antigas de upload — remover e considerar `.gitignore` (embora `admsite/` não seja versionado hoje).
- ~~**Timeout de inatividade não é aplicado**~~ — corrigido (25/08), ver item 6 abaixo.
- **XSS refletido de baixo impacto** (~15 pontos): vários módulos imprimem `get('sessao')`/`get('pg')` direto em atributos/breadcrumbs sem escapar — só afeta o próprio operador logado que clicar num link malicioso (self-XSS). Corrigir por padrão defensivo, sem urgência.

### Bug pré-existente encontrado durante o teste ao vivo e corrigido — 25/08/2026

Ao testar as telas do painel após o lote de correções de controle de acesso, o usuário reportou que a tela "Consumidores" (`admsite/consumidores/listar.php`) mostrava os dados nas colunas erradas (telefone aparecia vazio, e-mail aparecia deslocado). Investigado e confirmado: bug estático sem relação com as correções de hoje — a linha da tabela imprimia uma coluna extra de CPF que não tinha cabeçalho correspondente (`mascaraCnpjCpf($noticia_array['cpf'])`), empurrando todas as colunas seguintes uma posição para a direita; além disso, a coluna "Data" do cabeçalho nunca era preenchida (só "Hora" aparecia). Corrigido: removida a coluna de CPF sem cabeçalho e adicionada a exibição da data que faltava. Conferido direto no banco que os dados agora batem com o cabeçalho (ex.: o cadastro de "kelly sousa" realmente não tem telefone cadastrado — por isso aparecia em branco). A tela de Denúncias, que usa um padrão parecido, foi conferida e não tem o mesmo problema.

### 6. Tempo de sessão por inatividade + limpeza estrutural (itens 13 e 11 do plano) — 25/08/2026

- **`classes/autoload/Login.Class.php` / `admsite/index.php`** — o timestamp de última atividade, que já existia mas nunca era conferido, agora é checado a cada acesso ao painel: depois de **60 minutos parado**, a sessão expira sozinha e pede login de novo (cada clique renova o prazo, então quem está usando o painel não é interrompido). Testado com sessões simuladas de 59, 60+ e 0 minutos de inatividade — comportamento correto em todos os casos.
- Arquivadas (movidas para fora do site, não apagadas) as três cópias antigas do editor de texto (TinyMCE), a cópia duplicada e desatualizada do fluxo de upload de foto (`upload_foto/old/`), o arquivo de registro de erros de 2020/2021 que ficava na raiz do painel, e a página de teste de uma biblioteca de terceiros publicamente acessível. Removidos (sem valor de preservar) dois arquivos de lixo do Windows (`Thumbs.db`, `script.log`) e a classe de upload morta com configuração perigosa (`UploadHandler.php`).
- Removida uma linha de depuração esquecida (`echo $sSql;`) que expunha a consulta SQL interna do módulo "Peças de Fórum".
- **Achado extra durante a limpeza**: um segundo arquivo de depuração (`file_upload/server/php/postAcceptor.php`) não era código morto — estava ligado ao botão de inserir imagem do editor de texto (TinyMCE), e o `die()` de depuração **quebrava essa função silenciosamente havia tempo indeterminado**. Corrigido (removida a linha de depuração, adicionada exigência de login que também faltava) — o recurso de inserir imagem pelo editor volta a funcionar.
- **Achado extra e corrigido, fora do escopo do admsite**: durante os testes, uma página pública (`cadastro_advogado.php`, formulário de auto-cadastro de advogado) apresentou erro fatal intermitente por referenciar uma constante (`ADVOGADOS_DIR`) que não existe em lugar nenhum do código — resultado de uma limpeza anterior que descontinuou a funcionalidade de "diretório de busca de advogados" (bloqueada de propósito com erro 410 no `.htaccess`) sem atualizar os links do formulário de cadastro, que continua ativo. A página ficava com o conteúdo cortado ao meio, sem mostrar o formulário. Corrigido em `cadastro_advogado.php` e `cadastro_advogado_ativar.php` (2 arquivos, 4 pontos), removendo os links quebrados para a funcionalidade descontinuada. Confirmado no log do servidor que o erro parou de ocorrer após a correção.

### 7. Cadastro de advogados e Fórum de Advogados desativados temporariamente (pedido do usuário) — 25/08/2026

A pedido do usuário, o cadastro público de advogados (`cadastro_advogado.php` e os 2 arquivos ligados a ele) e o Fórum de Advogados (`forum_advogado/`, com suas ~18 URLs amigáveis) foram **desativados temporariamente** — não há previsão de uso agora, mas podem ser necessários no futuro. O código de ambos permanece correto e intacto (já corrigido nos itens 3, 4 e nesta mesma rodada); a desativação foi feita só na porta de entrada, em `public_html/.htaccess`, retornando **403** (acesso negado) para quem tentar acessar. Diferente das funcionalidades já descontinuadas de vez (busca de advogados, Fórum do Consumidor antigo — essas usam 410 "removido definitivamente"), aqui foi usado 403 de propósito, por ser reversível: **para reativar, basta remover as 3 linhas marcadas no `.htaccess`** (comentário `"desativados temporariamente"`), sem precisar mexer em código nenhum.

Conferido que: nenhum menu ou link do site público aponta para essas duas funcionalidades hoje (não vai gerar links quebrados visíveis); as telas do painel administrativo que **gerenciam** cadastros de advogados e fóruns (usadas para revisar/aprovar o que já foi cadastrado) são telas separadas e continuam funcionando normalmente; o restante do site (home, notícias, contato, calculadoras, admsite) testado e funcionando sem alteração.

### 8. Sanitização de HTML do editor de texto (item 7 do plano) — 25/08/2026

Instalada a biblioteca HTMLPurifier (via Composer, com verificação de assinatura do instalador antes de rodar) no projeto — primeira dependência nova adicionada desde a auditoria original. `composer audit` rodado logo em seguida não acusou nenhuma vulnerabilidade na própria biblioteca (só reconfirmou a já conhecida do SwiftMailer, item 12, não relacionada).

Criada a função `purificarHtmlEditorBD()` (`classes/funcoes_formatacao.php`), que substitui `ajustaTextAreaBD()` nos 14 pontos onde o conteúdo do editor de texto é recebido do formulário: `noticias/db.php` e os 13 arquivos `institucional/inst_*.php`. A função permite uma lista de tags e atributos considerados seguros (negrito, itálico, links, imagens, tabelas, títulos, citação, vídeo incorporado do YouTube/Vimeo) e remove **tanto tags quanto atributos** fora dessa lista — a diferença chave em relação ao filtro anterior, que só olhava para o nome da tag.

Testado com o fluxo real de gravação (não só a função isolada): um texto contendo `<script>` com roubo de cookie, `<img onerror="...">` e `<a href="javascript:...">` foi gravado através da classe `Institucional` de verdade — o resultado ficou limpo (script removido, atributos perigosos removidos, texto e formatação legítima preservados). Mesma verificação feita para o campo de texto de notícias. Testado também que aspas simples continuam sendo escapadas corretamente para gravação segura no banco, e que uma citação de vídeo do YouTube incorporada continua funcionando. Site público, notícias, páginas institucionais e admsite testados e funcionando normalmente depois da publicação.

## Escopo não coberto nesta rodada

Por profundidade/tempo, não foram revisados linha a linha: `classes/galerias/` (parece código órfão, sem chamador ativo encontrado — revisar antes de reativar), `classes/consulta_cep/`, `classes/ibge/` (baixo risco, funções de consulta simples), e os arquivos `js.php` de cada módulo (só JavaScript de suporte, não processam dado de servidor).

## Plano de correção priorizado

| # | Ação | Esforço | Status |
|---|---|---|---|
| 1 | RCE via upload sem autenticação/validação | — | **Feito (mitigação de emergência, 25/08)** |
| 2 | SQL injection sem autenticação em `organizar_ajax.php` (4 arquivos) | — | **Feito (25/08)** |
| 3 | SQL injection sem nenhum escape (fóruns públicos, contato, notícias, newsletter) | Médio | **Feito (25/08)** |
| 4 | XSS armazenado alimentável por visitante anônimo (advogados, contato, opinião, fórum, notícias/newsletter) | Médio | **Feito (25/08)** |
| 5 | Restaurar o bloqueio comentado em `Permissao::setSessao()` + sanitizar `pg=` no dispatcher | Baixo–Médio | **Feito (25/08)** |
| 6 | `session_regenerate_id()` após login | Baixo | **Feito (25/08)** |
| 7 | Sanitização de HTML do TinyMCE no backend (HTMLPurifier) | Médio | **Feito (25/08)** |
| 8 | Prepared statements no restante das buscas/filtros (~30+ pontos com addslashes fraco) | Alto (gradual) | Pendente |
| 9 | Autenticação/whitelist de extensão nos uploads restantes + corrigir limite de tamanho quebrado | Baixo | **Feito (25/08)** — falta ainda `checkAcao()` granular por módulo, ver nota |
| 10 | `.htaccess` bloqueando `classes/` | Baixo | **Feito (25/08)** |
| 11 | Limpeza estrutural (TinyMCE antigo, `upload_foto/old`, `error_log`, código morto/debug) | Baixo | **Feito (25/08)** |
| 12 | Migrar SwiftMailer → Symfony Mailer; atualizar TinyMCE | Alto | Pendente (já apontado em 02/08) |
| 13 | Timeout de inatividade de sessão real | Baixo | **Feito (25/08)** |

Itens 3–6 e 9–10 são o próximo lote natural (mesmo padrão de risco dos itens já corrigidos de emergência, mas sem exploração pública confirmada hoje — dá para agendar com backup e teste, sem pressa de minutos). Itens 7, 8, 12 são projetos maiores, a fazer em lotes.
