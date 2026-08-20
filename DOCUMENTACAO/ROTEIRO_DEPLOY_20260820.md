# Roteiro de deploy — correções de 19–20/08/2026

Cobre a publicação no servidor de tudo que foi corrigido na árvore local nesta rodada: `AUDITORIA_ESTRUTURA_CODIGO_2026-08-19.md` (itens 1–8). Siga a ordem — ela evita janelas em que o código novo espera algo que o banco/servidor ainda não tem, ou em que arquivos removidos ainda são chamados pelo código antigo.

Regra do projeto (`CHECKLIST_MELHORIAS.md`): **backup antes de alterar o servidor**, **testar no servidor antes de considerar concluído**, **um lote por vez**.

## Fase 0 — Backup

Confirmar que existe um backup Hestia recente e íntegro antes de começar (mesmo padrão da auditoria de 02/08: arquivo + tamanho + checksum registrados). Se o último backup for de antes de 19/08, gerar um novo.

## Fase 1 — Migrações de banco (rodar antes de publicar o código que depende delas)

Rodar nesta ordem, no banco de produção (e no de teste, se for um banco separado):

1. `migrations/20260819_senha_password_hash.sql` — amplia `usuarios.senha` e `cadastro.senha` para `VARCHAR(255)`. **Pré-requisito do item 5** (login com `password_hash`); sem isso a migração automática de senha trunca o hash.
2. `migrations/20260820_site_newsletter_brevo_sync.sql` — adiciona `brevo_synced_at`, `brevo_sync_attempts`, `brevo_sync_error` em `site_newsletter`. **Pré-requisito do item 8** (sincronização com a Brevo).

Cada arquivo `.sql` traz no cabeçalho a query `SHOW COLUMNS`/`SELECT VERSION()` para conferir antes de rodar.

## Fase 2 — Publicar os arquivos alterados (código PHP)

Publicar **todos de uma vez** — vários dependem uns dos outros (ex.: `newsletter_ler_db.php` só funciona sem erro porque `EgoiCustom` deixou de ser chamado; publicar um sem o outro reintroduz o bug do item 6).

**Novos:**
- `public_html/admsite/classes/autoload/SenhaSegura.Class.php`

**Alterados:**
- `public_html/.htaccess`
- `public_html/head.inc.php`
- `public_html/menu_lateral.inc.php`
- `public_html/contatos_ler.php`
- `public_html/perguntas_e_respostas_ler.php`
- `public_html/noticias_ler.php`
- `public_html/newsletter_ler_db.php`
- `public_html/admsite/classes/config.inc.php`
- `public_html/admsite/classes/autoload/Login.Class.php`
- `public_html/admsite/classes/autoload/LoginFront.Class.php`
- `public_html/admsite/classes/autoload/LoginFrontAdv.Class.php`
- `public_html/admsite/classes/usuario/Usuario.Class.php`
- `public_html/admsite/classes/composer.json`
- `public_html/admsite/classes/composer.lock`
- `public_html/admsite/newsletter/boletim_enviar_db.php`
- `public_html/admsite/newsletter/boletim_enviar.php`

**Removidos** (apagar do servidor; ou deixar para a Fase 3, que já faz isso):
- `public_html/teste.php`
- `public_html/_diag_ia.php`
- `public_html/_diag_tmpdir.php`

Se o deploy for por `rsync`/`scp` via SSH (método já usado no projeto, ver `CONSOLIDACAO_2026-08-03.md`), um exemplo de comando para essa lista específica (rodar da pasta canônica local, ajustando usuário/host):

```
rsync -avz --files-from=- "public_html/" usuario@servidor:/home/user/web/sosconsumidor.com.br/public_html/ <<'EOF'
.htaccess
head.inc.php
menu_lateral.inc.php
contatos_ler.php
perguntas_e_respostas_ler.php
noticias_ler.php
newsletter_ler_db.php
admsite/classes/autoload/SenhaSegura.Class.php
admsite/classes/config.inc.php
admsite/classes/autoload/Login.Class.php
admsite/classes/autoload/LoginFront.Class.php
admsite/classes/autoload/LoginFrontAdv.Class.php
admsite/classes/usuario/Usuario.Class.php
admsite/classes/composer.json
admsite/classes/composer.lock
admsite/newsletter/boletim_enviar_db.php
admsite/newsletter/boletim_enviar.php
EOF
```

Ajuste para o método de deploy que vocês já usam se não for este.

## Fase 3 — Limpeza do webroot (rodar DEPOIS da Fase 2)

Importante fazer nessa ordem: o script remove `admsite/classes/Egoi/` e `admsite/classes/vendor/sendpulse/`. Se rodar antes de publicar o código novo, o site quebra (o código antigo ainda chama `EgoiCustom`). Depois de publicado o código da Fase 2, rodar:

```
sh scripts/limpeza_webroot_20260819.sh /home/user/web/sosconsumidor.com.br/public_html
```

Isso também apaga `teste.php`/`_diag_*.php` (cobre o que sobrou da Fase 2) e move ~85 arquivos/pastas de backup e código morto para uma quarentena fora do webroot. Nada é apagado permanentemente, exceto os 3 arquivos de diagnóstico.

## Fase 4 — Script de sincronização da newsletter

1. Publicar `scripts/sync_newsletter_brevo.py` e o `scripts/requirements.txt` atualizado no diretório de scripts do servidor (mesmo lugar de `enviar_newsletter.py`).
2. Instalar dependências no ambiente Python usado pelo cron: `pip install -r requirements.txt` (o `sib-api-v3-sdk` já deve estar instalado, já que `enviar_newsletter.py` roda hoje — o requirements.txt só passou a declarar).
3. Rodar uma vez manualmente para varrer o backlog: `python3 sync_newsletter_brevo.py`
4. Conferir no painel da Brevo (lista ID 3) se os contatos antigos de `site_newsletter` apareceram.
5. Adicionar ao crontab:
   ```
   */15 * * * * cd /caminho/para/scripts && /usr/bin/python3 sync_newsletter_brevo.py >> /var/log/sos/newsletter_sync.log 2>&1
   ```

## Fase 5 — Testes (ambiente de teste antes de confiar em produção)

- [ ] Home carrega normalmente
- [ ] Uma notícia abre e o compartilhamento não quebra (sem o bloco AddThis removido)
- [ ] Uma pergunta/resposta abre
- [ ] Cadastro de newsletter pelo formulário público → confirma mensagem de sucesso e a linha aparece em `site_newsletter`
- [ ] Login do admin com usuário existente (senha ainda em MD5) → entra e a coluna `senha` vira `$2y$...`
- [ ] Segundo login do mesmo usuário (já em bcrypt) → continua entrando
- [ ] Senha errada → mensagem de erro correta, não fatal error
- [ ] Troca de senha pelo admsite → grava com `password_hash`
- [ ] Login do fórum do advogado com conta existente e com conta nova
- [ ] `curl -I https://www.sosconsumidor.com.br/teste.php` → 404
- [ ] `curl -I https://www.sosconsumidor.com.br/noticias_ler.php310517` → 403
- [ ] Cadastrar um e-mail de teste na newsletter, rodar `sync_newsletter_brevo.py` manualmente, conferir que apareceu na Brevo

## Fase 6 — Fechamento (credenciais, sem dependência de horário)

Pode ser feito a qualquer momento, inclusive em paralelo às fases anteriores:

- [ ] Revogar a chave da E-goi no painel dela (`e-goi.com` → configurações da conta/API)
- [ ] Revogar a chave do SendPulse (`login.sendpulse.com/settings/#api`)
- [ ] Decidir sobre bit.ly: rotacionar a chave ou abandonar (a integração que a usava, AddThis, está descontinuada)
