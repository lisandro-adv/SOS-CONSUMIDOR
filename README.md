# SOSConsumidor — pasta canônica

Esta é a **única pasta canônica** do projeto SOSConsumidor.

Todo código, documentação, banco de dados, backup ou material histórico novo deve ser colocado dentro desta pasta. Não criar outra árvore do projeto no OneDrive, em `AUTOMATIZAÇÃO DO ESCRITÓRIO` ou em outra área do Dropbox.

## Estrutura

- `public_html/` — código atual de trabalho.
- `DOCUMENTACAO/` — auditorias, planos e registros do projeto.
- `BANCOS_DE_DADOS/` — bancos históricos importados do OneDrive.
- `BACKUPS/` — backups integrais do servidor, VPS e auditorias.
- `ARQUIVO_HISTORICO/` — site antigo, protótipos e configurações históricas.
- `ARQUIVO_SENSIVEL/` — cópia do homedir e das configurações do cPanel.

> **Atenção:** `ARQUIVO_SENSIVEL/` e alguns arquivos em `BACKUPS/` podem conter e-mails, certificados, credenciais antigas, tokens, bancos de dados e dados pessoais. Não publicar, anexar ou enviar essas pastas a terceiros.

O relatório completo da migração está em `DOCUMENTACAO/CONSOLIDACAO_2026-08-03.md`.

## Versionamento e publicação

O repositório oficial do projeto é:

`https://github.com/lisandro-adv/SOS-CONSUMIDOR`

As alterações devem ser feitas nesta pasta canônica, revisadas, registradas em
um commit e enviadas para a branch `main` antes da publicação. O deploy no
servidor continua sendo uma etapa separada por SSH; o GitHub é o histórico e a
fonte de referência do código versionado. Arquivos sensíveis e dados de
produção permanecem excluídos pelo `.gitignore`.
