-- Migração de senhas para password_hash() — 19/08/2026
--
-- Pré-requisito de código: SenhaSegura.Class.php + alterações em
-- Login.Class.php, LoginFront.Class.php, LoginFrontAdv.Class.php e
-- Usuario.Class.php (ver DOCUMENTACAO/AUDITORIA_ESTRUTURA_CODIGO_2026-08-19.md).
--
-- Executar ANTES de publicar o código novo. O hash bcrypt tem 60 caracteres;
-- se a coluna senha for VARCHAR(32) (tamanho do MD5), ela precisa ser
-- ampliada, senão a migração transparente não persiste (o código detecta o
-- truncamento e mantém o valor legado, sem bloquear ninguém — mas ninguém
-- é migrado até a coluna comportar o hash).
--
-- Conferir a definição atual antes de rodar:
--   SHOW COLUMNS FROM usuarios LIKE 'senha';
--   SHOW COLUMNS FROM cadastro LIKE 'senha';
-- Se alguma coluna tiver menos de 60 caracteres, ampliar (ajuste o NOT NULL
-- conforme a definição atual mostrada pelo SHOW COLUMNS):

ALTER TABLE usuarios MODIFY senha VARCHAR(255) NOT NULL;
ALTER TABLE cadastro MODIFY senha VARCHAR(255) NOT NULL;

-- Depois da publicação, cada usuário é migrado automaticamente no primeiro
-- login. Para acompanhar o progresso da migração:
--   SELECT COUNT(*) AS legados FROM usuarios WHERE senha NOT LIKE '$2y$%';
--   SELECT COUNT(*) AS legados FROM cadastro WHERE senha NOT LIKE '$2y$%' AND senha <> '';
