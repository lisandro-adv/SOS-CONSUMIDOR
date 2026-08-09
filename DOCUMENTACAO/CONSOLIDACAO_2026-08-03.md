# Consolidação do projeto SOSConsumidor

**Data:** 03/08/2026  
**Pasta canônica:** `/Users/lisandromoraes/Dropbox/_ESCRITORIO/PROJETOS/SOSCONSUMIDOR.COM.BR`

## Resultado

O material do SOSConsumidor que estava dividido entre Dropbox e OneDrive foi consolidado na pasta canônica acima.

- Acervo localizado no OneDrive antes da migração: aproximadamente 15 GB e 37.978 arquivos.
- Pasta canônica após a consolidação: aproximadamente 16 GB e 39.076 arquivos.
- A pasta ativa `OneDrive-Pessoal/___ESCRITORIO/SOS CONSUMIDOR` foi retirada do OneDrive após a validação.
- Backups e documentos que estavam em `AUTOMATIZAÇÃO DO ESCRITÓRIO/CHATGPT_BACKUP_20260716_194500/EPROC_AUTOMACAO` foram movidos para `BACKUPS/AUDITORIA_2026-08-02/`.

## Mapeamento das origens

| Origem antiga | Destino canônico |
|---|---|
| `OneDrive/.../SOS CONSUMIDOR/site/` | `ARQUIVO_HISTORICO/SITE_ANTIGO_ONEDRIVE/` |
| `OneDrive/.../SOS CONSUMIDOR/banco-de-dados/` | `BANCOS_DE_DADOS/LEGADO_ONEDRIVE/` |
| `OneDrive/.../SOS CONSUMIDOR/homedir/` | `ARQUIVO_SENSIVEL/CPANEL_HOMEDIR/` |
| `OneDrive/.../SOS CONSUMIDOR/configuracoes/` | `ARQUIVO_SENSIVEL/CPANEL_CONFIGURACOES/` |
| backups completos de 19/02/2026 | `BACKUPS/SERVIDOR_2026/` |
| `preview/`, `calculoexato-replica/` e livro | `ARQUIVO_HISTORICO/OUTROS_ONEDRIVE/` |
| `.claude/` histórica do OneDrive | `ARQUIVO_HISTORICO/CONFIGURACAO_CLAUDE_ONEDRIVE/` |
| backup da auditoria de 02/08/2026 | `BACKUPS/AUDITORIA_2026-08-02/` |

O código recente que já estava em `public_html/` foi preservado e não foi sobrescrito pelo site histórico.

## Verificação de integridade

Foram executadas comparações `rsync` em modo somente leitura entre cada origem e cada destino. O resultado foi **zero diferenças** para:

- bancos de dados;
- site histórico;
- configurações do cPanel;
- backup expandido de 19/02/2026;
- protótipos e outros materiais;
- configuração histórica do Claude;
- homedir, excetuados dez placeholders defeituosos do OneDrive descritos abaixo.

O backup compactado principal foi conferido por SHA-256 na origem e no destino:

```text
5fad13d6ff9ccc2ff980e734238400b79d7b2d6dc08f4a5c1fc29dc4582bc01e
```

Arquivo: `BACKUPS/SERVIDOR_2026/backup-2.19.2026_08-55-56_sos.tar.gz`.

## Dez arquivos “dataless” do OneDrive

O OneDrive não conseguia materializar individualmente dez mensagens antigas de e-mail e retornava `Operation timed out`. As dez mensagens foram localizadas dentro do backup integral validado pelo SHA-256 acima:

```text
1434655314.H824803P13537.server.tiphost.com.br,S=451200
1436911926.H758872P7603.server.tiphost.com.br,S=857152
1434681124.H903594P23684.server.tiphost.com.br,S=451187
1436888384.H31842P31837.server.tiphost.com.br,S=857174
1431496417.H965556P30332.server.tiphost.com.br,S=230167
1432865879.H88910P29414.server.tiphost.com.br,S=803870
1434123742.H834901P1786.server.tiphost.com.br,S=692192
1279092971.H417662P11644.server.tiphost.com.br,S=724649:2,T
1282255030.H963100P25841.server.tiphost.com.br,S=58618
1425397663.H568624P12759.server.tiphost.com.br,S=474203:2,
```

Assim, o conteúdo continua preservado no backup completo mesmo não tendo sido copiado como dez arquivos avulsos para `CPANEL_HOMEDIR/`.

## Duplicatas e recuperação

Duas cópias integrais preexistentes e comprovadamente idênticas — `ARQUIVO_HISTORICO/ONEDRIVE_2026-08-03` e `ARQUIVO_HISTORICO/BACKUP_VPS_2026-08-02_0910` — foram retiradas da pasta canônica para evitar aproximadamente 15,8 GB de duplicação. Elas foram encaminhadas à Lixeira local durante a consolidação.

A pasta antiga do OneDrive foi retirada somente após as verificações. Eventual restauração deve ser feita pela Lixeira/reciclagem do OneDrive ou pelos backups integrais mantidos em `BACKUPS/`.

## Regra daqui em diante

Esta pasta do Dropbox é a fonte única do projeto. Antes de criar qualquer nova pasta SOSConsumidor em outro local, mover o conteúdo para cá e registrar a finalidade em `DOCUMENTACAO/`.
