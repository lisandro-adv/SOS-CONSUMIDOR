-- Sincronização site_newsletter -> Brevo — 20/08/2026
--
-- Contexto: quem se cadastra na newsletter pelo site (newsletter_ler_db.php)
-- só gravava localmente em site_newsletter; nada levava esse contato até a
-- lista da Brevo usada por scripts/enviar_newsletter.py (listIds=[3]) para
-- disparar a newsletter semanal. Este script acompanha
-- scripts/sync_newsletter_brevo.py, que roda por cron e empurra os
-- cadastros pendentes para a Brevo.
--
-- Rodar antes de agendar o cron do script:
--   SHOW COLUMNS FROM site_newsletter LIKE 'brevo_synced_at';
-- Se já existir, pule esta migração (idempotente via IF NOT EXISTS, mas
-- confira a versão do MySQL/MariaDB antes: IF NOT EXISTS em ADD COLUMN
-- requer MySQL 8.0.29+ ou MariaDB 10.0+; ambiente moderno de PHP 8.3
-- provavelmente atende, mas confirme com `SELECT VERSION();` se a query
-- falhar.

ALTER TABLE site_newsletter
    ADD COLUMN IF NOT EXISTS brevo_synced_at DATETIME NULL COMMENT 'Quando o contato foi confirmado na lista da Brevo',
    ADD COLUMN IF NOT EXISTS brevo_sync_attempts INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Tentativas de sincronizar; script para de tentar após o limite (ver MAX_TENTATIVAS no script)',
    ADD COLUMN IF NOT EXISTS brevo_sync_error VARCHAR(255) NULL COMMENT 'Última mensagem de erro da API da Brevo, se houver';

-- Para acompanhar o backlog pendente de sincronização:
--   SELECT COUNT(*) AS pendentes FROM site_newsletter WHERE brevo_synced_at IS NULL AND brevo_sync_attempts < 5;
--   SELECT id, email, brevo_sync_attempts, brevo_sync_error FROM site_newsletter WHERE brevo_synced_at IS NULL AND brevo_sync_attempts >= 5;
