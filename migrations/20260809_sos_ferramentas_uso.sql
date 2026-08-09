-- Medição anônima de respostas concluídas nas ferramentas públicas.
-- Não armazena valores preenchidos, IP, e-mail ou outros dados pessoais.
CREATE TABLE IF NOT EXISTS `sos_ferramentas_uso` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `visitante_id` char(36) NOT NULL,
  `ferramenta` varchar(32) NOT NULL,
  `evento` varchar(32) NOT NULL DEFAULT 'resposta_sucesso',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ferramenta_criado` (`ferramenta`, `criado_em`),
  KEY `idx_visitante_ferramenta` (`visitante_id`, `ferramenta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
