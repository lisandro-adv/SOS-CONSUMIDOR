# Auditoria de estrutura e código — SOS Consumidor

Data: 19/08/2026
Escopo: árvore canônica local (`public_html/` e pastas de apoio), inspeção somente leitura. Complementa a auditoria de 02/08/2026 (`AUDITORIA_CODIGO_SOSCONSUMIDOR_2026-08-02.md`); itens já resolvidos de lá não são repetidos.

## Resultado executivo

O projeto avançou desde 02/08: repositório Git criado (335 arquivos versionados, escopo curado), `.htaccess` com headers de segurança e bloqueio de `.bak`, cache anônimo de conteúdo público, código novo (`calculos/`, helpers do `index.php`) em padrão moderno com CSRF e `strict_types`. Os problemas remanescentes se concentram em três frentes:

1. **Segredos e arquivos de diagnóstico no webroot** (risco imediato).
2. **Autenticação fraca** (MD5 e comparação direta de senha).
3. **SQL por concatenação de strings** em toda a base legada (`addslashes` como única defesa).

Além disso, o webroot acumula ~71 arquivos de backup/cópias datadas e 4 cópias do diretório `vendor`, o que aumenta a superfície de ataque e dificulta manutenção.

## Achados — Segurança

### P0 — `teste.php` público com `phpinfo()` e credenciais SendPulse

`public_html/teste.php` começa com `phpinfo(); die();` — qualquer visitante em `/teste.php` vê a configuração completa do PHP (caminhos, versões, extensões, variáveis de ambiente). Abaixo do `die()` há credenciais SendPulse hardcoded (`API_USER_ID`, `API_SECRET`). O arquivo não está no Git, mas está no webroot local (e presumivelmente no servidor).

**Ação:** remover o arquivo do servidor e da árvore local; **trocar as credenciais SendPulse** (o segredo deve ser considerado comprometido — já esteve em backup exposto, conforme observação da auditoria de 02/08).

### P0 — Senhas com MD5 (admin) e comparação direta (front)

- `admsite/classes/autoload/Login.Class.php:129` — `if (md5($this->pass) == $senha)`. MD5 sem salt é quebrável por rainbow table; a comparação com `==` ainda permite o edge case de "magic hashes" (`0e...`).
- `admsite/classes/autoload/LoginFront.Class.php:162` — `if ($linha["senha"] != $this->pass)` — comparação direta do valor recebido com o armazenado; se o chamador não pré-hasheia, as senhas dos usuários do fórum estão em texto claro no banco.

**Ação:** migrar para `password_hash()`/`password_verify()` com re-hash transparente no login (verifica MD5 antigo → grava hash novo). A linha comentada `#if (password_verify(...))` mostra que a migração já foi cogitada. Usar `hash_equals()` em qualquer comparação de token.

### P0/P1 — SQL por concatenação em toda a base legada

Padrão geral das classes em `admsite/classes/`: montagem de SQL por string, ex.:

- `Login.Class.php:117` — `WHERE E.login = '{$this->login}'` (protegido apenas por `ajustaStringBD` = `addslashes`).
- `noticias/Noticias.Class.php:874` — `AND F.titulo LIKE '%" . $txttitulo . "%'`.
- `noticias/NoticiasRelacionadas.Class.php:78` — palavras-chave interpoladas no `LIKE`.

`addslashes()` não é defesa adequada contra SQL injection (não considera charset da conexão; a proteção correta é do driver). Os IDs numéricos passam por `AjustaInteiroGravar()` (cast via `sprintf('%d')`) e estão OK; o risco real está nos campos string (login, buscas, formulários do admin).

**Ação:** migrar gradualmente para prepared statements do PDO (a infra já é PDO com `ERRMODE_EXCEPTION`). Prioridade: (1) `Login`/`LoginFront`/`LoginFrontAdv`, (2) qualquer query que receba string de formulário público, (3) admin. Não é preciso reescrever tudo de uma vez — começar pelos pontos que recebem input externo.

### P1 — Chaves de API hardcoded no código

- `admsite/classes/config.inc.php:79` — `BIT_LY_API_KEY` hardcoded **e impressa no HTML público** de `noticias_ler.php`/`perguntas_e_respostas_ler.php` (bloco AddThis).
- `admsite/classes/Egoi/EgoiCustom.php:38` — `$apiKey` E-goi hardcoded.

Nenhum dos dois arquivos está no Git (o `admsite/` não é versionado), mas ambos estiveram no backup que ficou publicamente acessível (ver auditoria de 02/08) e a chave bit.ly é visível no código-fonte de qualquer notícia.

O acerto do `PDOConfig.php` (credenciais em `/home/.../private/sos-db-credentials.php`, fora do webroot) é o padrão a seguir.

**Ação:** mover todas as chaves para o arquivo privado fora do webroot (ou variáveis de ambiente), trocar as chaves expostas (bit.ly, E-goi) e, se desejar apagar o histórico do GitHub, reescrever o histórico ou simplesmente revogar as chaves antigas (mais simples e suficiente).

### P1 — Arquivos de diagnóstico "REMOVER APÓS O USO" ainda presentes

`_diag_ia.php` e `_diag_tmpdir.php` continuam no webroot, protegidos apenas por um token fixo na URL (`?t=sos-admin-2026-planos`) que está no próprio código. Retornavam 403 na verificação de 02/08, mas o token pode vazar por logs/histórico.

**Ação:** remover do servidor (estão fora do Git, então basta apagar).

### P1 — Sintaxe Apache 2.2 nos bloqueios do `.htaccess`

Os blocos `<FilesMatch>` de proteção usam `Order allow,deny` / `Deny from all` (sintaxe do Apache 2.2). Em Apache 2.4 isso só funciona com `mod_access_compat` habilitado; se o módulo for desativado numa atualização do servidor, **todos os bloqueios de `.bak`, config e dumps caem silenciosamente**.

**Ação:** trocar por `Require all denied` (sintaxe 2.4), mantendo o comportamento.

### P1 — Cópias datadas de `.php` executáveis no webroot

O padrão de bloqueio do `.htaccess` cobre `.bak`, `.old` etc., mas **não** cobre:

- `2017-05-25-index.php`, `2017-04-26-noticias_ler.php`, `noticias_ler.php310517`, `header.inc.php030818` — terminam em `.php`, são executáveis publicamente e rodam código antigo sem manutenção (podem conter vulnerabilidades já corrigidas na versão atual).
- `noticias_ler.php-01122017`, `noticias_ler.php_disqus` — dependendo do handler PHP configurado, podem ser servidos como texto puro (vazamento de código-fonte) ou executados.

**Ação:** mover todas as cópias datadas para `ARQUIVO_HISTORICO/` (fora do webroot). Enquanto isso não acontece, ampliar o `FilesMatch` para cobrir `\.php[-_.0-9]` e nomes iniciados por data.

### P2 — Vazamento de erro no `PDOConfig`

`admsite/classes/PDOConfig.php` faz `die($e->getMessage())` na falha de conexão — expõe host/usuário do banco em caso de erro. Trocar por mensagem genérica + log.

### P2 — Headers: falta CSP; `X-XSS-Protection` obsoleto

O conjunto atual é bom (HSTS, nosniff, frame-options, referrer-policy). Falta `Content-Security-Policy` — mesmo uma política inicial em modo `Report-Only` já ajudaria a mapear o inline script/style existente. `X-XSS-Protection` foi descontinuado pelos navegadores e pode ser removido.

### P2 — Sanitização de saída inconsistente

O código novo escapa corretamente (`htmlspecialchars` com `ENT_QUOTES` nos helpers do `index.php`). O legado grava com `ajustaStringBD` (que mistura preocupação de SQL com `&quot;`) e nem sempre escapa na saída. Ao migrar para prepared statements, remover o "escape na entrada" e padronizar: **entrada crua no banco, escape na saída**.

## Achados — Estrutura

### E1 — ~71 arquivos de backup/cópias no webroot

`header.inc.php.bak-*` (13 variações), `footer.inc.php.bak-*`, `head.inc.php.bak-*`, `.htaccess.bak*`, `newsletter_ler_db_old.php`, `perguntas_e_respostas_ler-old.php` etc. O `.htaccess` bloqueia a maioria, mas eles poluem o diretório, confundem deploys e alguns escapam do padrão de bloqueio (ver P1 acima).

**Ação:** definir a regra "backup nunca no webroot" — cópias vão para `BACKUPS/` ou `ARQUIVO_HISTORICO/`; o Git já é o histórico do código. Fazer uma limpeza única (mover, não apagar) e refletir no servidor.

### E2 — Quatro cópias do `vendor` no admsite

`admsite/classes/vendor` (2,6 MB, ativa), `vendor-old`, `vendor_old02-03-2020`, `2017-05-12-vendor` — ~7,5 MB de bibliotecas duplicadas e desatualizadas publicamente acessíveis (SwiftMailer 5 com testes inclusos). Vendor antigo é vetor clássico de exploit.

**Ação:** manter apenas `vendor/` ativa; mover as demais para `ARQUIVO_HISTORICO/`. A migração de SwiftMailer → Symfony Mailer já consta na auditoria de 02/08 e continua pendente.

### E3 — Diretórios residuais no webroot

- `apagar/` (17 MB!) — o nome já diz; contém sistemas antigos completos.
- `www.sosconsumidor.com.br/` — vazio, remover.
- `mascote-teste/`, `rogerio/`, `temp-imgs/`, `newsletter_preview_temp.html` — material de teste/pessoal em produção.
- `14b6a130ff65a9e5f9da0a7c9d25fffd`, `8796BD03E16346CC5195732122851131.txt` etc. — verificações de domínio antigas; conferir se ainda são necessárias.

### E4 — Git cobre só parte do que roda

O repositório versiona 335 arquivos (escopo curado — bom), mas o servidor roda muito mais coisa. Isso significa que parte do que está em produção não tem histórico nem revisão.

**Ação:** meta de médio prazo — todo `.php` servido em produção ou está no Git ou está agendado para remoção. A limpeza E1–E3 reduz muito essa diferença.

## Pontos positivos (manter o padrão)

- `calculos/csrf.php`: `strict_types`, sessão com `use_strict_mode`, cookie scoped, SameSite — é o padrão de referência para código novo.
- Cache anônimo de páginas públicas sem abrir sessão (`config.inc.php`) — boa otimização, bem implementada.
- Credenciais de banco fora do webroot (`PDOConfig`).
- CSS versionado (`?v=20260810-perf2`), preload de fontes, lazy-loading de imagens, WebP com fallback.
- Domínio canônico + HTTPS forçado + HSTS; sitemap dinâmico; 410 para seções descontinuadas (correto para SEO).
- Documentação e disciplina de processo (`CHECKLIST_MELHORIAS.md`, QA de calculadoras).

## Plano de melhorias priorizado

| # | Ação | Esforço | Risco de regressão |
|---|---|---|---|
| 1 | Apagar `teste.php`, `_diag_*.php` do servidor; trocar credenciais SendPulse | Minutos | Nenhum |
| 2 | Trocar chaves bit.ly e E-goi; mover todas p/ arquivo privado fora do webroot | Baixo | Baixo (testar newsletter) |
| 3 | `.htaccess`: `Require all denied` + ampliar padrão p/ `.php` datados | Baixo | Baixo (testar rotas) |
| 4 | Mover backups/cópias datadas/vendors antigos/`apagar/` p/ fora do webroot | Médio (inventário) | Baixo se mover, não apagar |
| 5 | `password_hash`/`password_verify` com re-hash transparente (admin + front) | Médio | Médio (testar login) |
| 6 | Prepared statements nos logins e formulários públicos | Médio | Médio |
| 7 | Prepared statements no restante (admin), por módulo | Alto (gradual) | Médio |
| 8 | CSP em `Report-Only` → enforce; remover `X-XSS-Protection` | Médio | Baixo |
| 9 | SwiftMailer → Symfony Mailer (pendente de 02/08) | Alto | Médio |

Itens 1–4 são uma tarde de trabalho e eliminam os riscos mais imediatos sem tocar em lógica de negócio. Itens 5–6 são o próximo lote, com testes de login no ambiente de teste antes de publicar.

## Execução — 19/08/2026 (itens 1–3, árvore local)

Alterações aplicadas na pasta canônica (ainda **não** publicadas no servidor):

1. Removidos `public_html/teste.php`, `public_html/_diag_ia.php`, `public_html/_diag_tmpdir.php` (não versionados; remover também no servidor).
2. `admsite/classes/config.inc.php`: chave bit.ly retirada do código; agora carrega `private/sos-db-credentials.php` (mesmo arquivo do banco, fora do webroot) e usa `BIT_LY_API_KEY`/`BIT_LY_LOGIN` de lá, com fallback vazio.
3. `admsite/classes/Egoi/EgoiCustom.php`: chave E-goi retirada do código; lida da constante `EGOI_API_KEY` (definir no arquivo privado).
4. `noticias_ler.php` e `perguntas_e_respostas_ler.php`: removido o bloco AddThis/bit.ly que **imprimia a chave bit.ly no HTML público**. O AddThis foi descontinuado em 2023; o bloco era código morto.
5. `.htaccess`: bloqueios convertidos para sintaxe Apache 2.4 (`Require all denied`) com fallback 2.2 dentro de `IfModule`; novo `FilesMatch` bloqueia cópias datadas/variantes (`.php310517`, `2017-*-index.php`, `_old.php`, `_bk.php`, `.php_disqus` etc.). Validado contra os 335 arquivos versionados: nenhum arquivo ativo é atingido; 46 cópias antigas passam a ser bloqueadas.

### Item 4 executado (árvore local) — limpeza do webroot

- **79 arquivos** de backup/cópias datadas e **5 diretórios** (`apagar/` 17 MB, `vendor-old`, `vendor_old02-03-2020`, `2017-05-12-vendor`, `2017-08-09-upload_arquivo`) movidos — não apagados — para `ARQUIVO_HISTORICO/limpeza-webroot-20260819/`, preservando os caminhos relativos. Manifesto completo em `MANIFESTO_ARQUIVOS.txt` nessa pasta. Diretório vazio `www.sosconsumidor.com.br/` removido.
- Verificado por grep que nenhum código ativo referencia os itens movidos.
- Para repetir a limpeza no servidor: `scripts/limpeza_webroot_20260819.sh` (gerado a partir do manifesto; move para quarentena fora do webroot e apaga apenas `teste.php`/`_diag_*.php`). Uso: `sh limpeza_webroot_20260819.sh [WEBROOT] [QUARENTENA]`.

### Item 5 executado (árvore local) — senhas com password_hash

Nova classe `admsite/classes/autoload/SenhaSegura.Class.php` (carregada pelo autoloader):

- `SenhaSegura::confere()` — aceita `password_hash` **e** os formatos legados (MD5; e texto puro apenas na tabela `cadastro`, nunca na `usuarios`, para não permitir login apresentando o próprio hash). Usa `hash_equals` nas comparações legadas.
- `SenhaSegura::migrar()` — no primeiro login legado bem-sucedido regrava a senha com `password_hash` (migração transparente, sem exigir troca de senha). Lê o valor de volta e desfaz se a coluna truncar o hash (proteção contra `VARCHAR(32)`), e nunca lança exceção — falha de migração não impede login.

Alterações:

- `Login.Class.php` (admin) — `checkPass()` com prepared statement no login e `SenhaSegura`; login lido cru (sem `addslashes`), senha mantém o tratamento original para preservar os hashes MD5 existentes.
- `LoginFront.Class.php` e `LoginFrontAdv.Class.php` (tabela `cadastro`) — prepared statements + `SenhaSegura` com aceitação de texto puro legado. Compatível com qualquer formato que os fluxos do servidor gravem hoje (texto puro antigo, MD5 dos cadastros novos de advogado, ou hash novo).
- `Usuario.Class.php` — gravação de senha do admin passa de `md5()` para `password_hash()` (fluxo verificado de ponta a ponta localmente: o formulário só envia senha quando digitada).
- `migrations/20260819_senha_password_hash.sql` — **executar antes de publicar**: amplia `usuarios.senha` e `cadastro.senha` para `VARCHAR(255)` (conferir com `SHOW COLUMNS` antes) e traz consultas para acompanhar o progresso da migração.

Não alterados de propósito (gravadores cujos chamadores estão só no servidor): `Cadastro.Class.php` e `AdvogadoDirectory::salvarCadastro()` seguem gravando como hoje — a cadeia de verificação dos logins cobre esses formatos e migra no primeiro login. Revisar esses gravadores (e os fluxos de trocar/recuperar senha do `forum_advogado` e `ia_consumidor` no servidor) num segundo passo, quando for possível inspecioná-los.

Testes obrigatórios no ambiente de teste antes de publicar: login do admin com usuário existente (senha MD5) → deve entrar e a coluna virar `$2y$...`; segundo login do mesmo usuário (agora bcrypt); troca de senha pelo admsite; login errado (mensagem de senha inválida); login do fórum do advogado com conta existente e com conta recém-criada.

### Pendências para concluir os itens 1–5 (dependem do servidor)

Na ordem, para não interromper a newsletter:

1. **Rotacionar credenciais** (as antigas são públicas): SendPulse (`teste.php`), E-goi (painel E-goi → nova chave API) e bit.ly (se ainda usado; a integração AddThis morreu, provavelmente pode ser abandonada).
2. Adicionar ao `/home/user/web/sosconsumidor.com.br/private/sos-db-credentials.php` no servidor:
   `define('EGOI_API_KEY', '<nova chave>');` e, se aplicável, `define('BIT_LY_API_KEY', '<nova chave>');`
3. Apagar no servidor: `teste.php`, `_diag_ia.php`, `_diag_tmpdir.php`.
4. Publicar os arquivos alterados (config.inc.php, EgoiCustom.php, noticias_ler.php, perguntas_e_respostas_ler.php, .htaccess) e testar: home, uma notícia, uma pergunta/resposta, cadastro de newsletter e login do admin.
5. Confirmar bloqueios: `curl -I https://www.sosconsumidor.com.br/teste.php` (404), `/noticias_ler.php310517` (403), `/2017-05-25-index.php` (403).
