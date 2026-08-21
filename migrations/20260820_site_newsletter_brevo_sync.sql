-- Sincronização site_newsletter -> Brevo — 20/08/2026
--
-- Contexto: quem se cadastra na newsletter pelo site (newsletter_ler_db.php)
-- só gravava localmente em site_newsletter; nada levava esse contato até a
-- lista da Brevo usada por scripts/enviar_newsletter.py (listIds=[3]) para
-- disparar a newsletter semanal. Este script acompanha
-- scripts/sync_newsletter_brevo.py, que roda por cron e empurra os
-- cadastros pendentes para a Brevo.
--
-- Rodar antes de agendar o cron do script. Checar antes se já rodou (esta
-- ALTER NÃO é idempotente — o MySQL desta instância, 8.0.46, não aceita
-- "ADD COLUMN IF NOT EXISTS" apesar de constar na documentação para 8.0.29+;
-- testado e confirmado que dá erro de sintaxe. Rodar de novo sobre colunas
-- já existentes falha com "Duplicate column name"):
--   SHOW COLUMNS FROM site_newsletter LIKE 'brevo_synced_at';
-- Se já existir, pule esta migração.

ALTER TABLE site_newsletter
    ADD COLUMN brevo_synced_at DATETIME NULL COMMENT 'Quando o contato foi confirmado na lista da Brevo',
    ADD COLUMN brevo_sync_attempts INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Tentativas de sincronizar; script para de tentar após o limite (ver MAX_TENTATIVAS no script)',
    ADD COLUMN brevo_sync_error VARCHAR(255) NULL COMMENT 'Última mensagem de erro da API da Brevo, se houver';

-- Para acompanhar o backlog pendente de sincronização:
--   SELECT COUNT(*) AS pendentes FROM site_newsletter WHERE brevo_synced_at IS NULL AND brevo_sync_attempts < 5;
--   SELECT id, email, brevo_sync_attempts, brevo_sync_error FROM site_newsletter WHERE brevo_synced_at IS NULL AND brevo_sync_attempts >= 5;
