# Procedimento de alteração de conteúdo — SOS Consumidor

Versão 1 — 3 de agosto de 2026.
Aplica-se a toda alteração em `perguntas_e_respostas` e `noticias` no banco `user_sos`.

Este procedimento existe porque as páginas em revisão somam milhões de acessos.
Um erro de encoding ou um `UPDATE` sem filtro em produção é público e imediato.

---

## Regra de encoding — leia antes de escrever qualquer SQL

A tabela `perguntas_e_respostas` é **`latin1`**, e o conteúdo armazenado é latin1
legítimo, não UTF-8 cru. Isso foi verificado em 03/08/2026 pela análise de bytes do
dump histórico: 1.293 sequências UTF-8 limpas na exportação e **zero** assinaturas de
duplo-encode (`c3 83 c2 xx`). Se a base guardasse UTF-8 cru dentro de latin1, o
`mysqldump` com `SET NAMES utf8mb4` teria produzido exatamente esse duplo-encode.

Consequências práticas:

- Escreva os arquivos `.sql` em **UTF-8** e abra a sessão com **`SET NAMES utf8mb4`**.
  O servidor converte UTF-8 → latin1 na gravação, alinhado ao conteúdo existente.
- **Nunca** rode com `--default-character-set=latin1` enviando arquivo UTF-8. Isso
  grava bytes crus e gera mojibake na página.
- Antes de gravar, confirme que o texto cabe no repertório latin1. Aspas curvas
  (`" "`), travessão (`—`) e reticências (`…`) **não existem em latin1** e viram `?`.
  Use aspas retas e hífen. Verificação:

  ```bash
  python3 -c "
  import sys
  d=open(sys.argv[1],encoding='utf-8').read()
  ruins={c for c in d if not c.isascii() and (c.encode('latin-1','replace')==b'?')}
  print('PROBLEMA:',ruins) if ruins else print('OK: cabe em latin1')
  " arquivo.sql
  ```

- A evidência acima vem do dump de 2016. O passo `[1]` e `[3]` do pré-voo reconfirmam
  o charset **na produção atual** a cada alteração. Não pule.

---

## Ciclo obrigatório por alteração

### 1. Backup

O backup integral do VPS (03/08/2026, validado por TAR e SHA-256) **não dispensa** o
backup por linha. Toda alteração copia a linha original para a tabela de auditoria do
dia antes de tocar no conteúdo:

```sql
CREATE TABLE IF NOT EXISTS audit_backup_<tabela>_<AAAAMMDD> LIKE <tabela>;
INSERT IGNORE INTO audit_backup_<tabela>_<AAAAMMDD>
SELECT * FROM <tabela> WHERE id = <id>;
```

`INSERT IGNORE` é intencional: se houver backup de uma tentativa anterior, o
**original** é preservado e não sobrescrito pela versão já editada.

### 2. Pré-voo somente leitura

Rodar o `*_PREFLIGHT.sql` correspondente e conferir, no mínimo:

| Checagem | Esperado | Se falhar |
|---|---|---|
| charset da coluna | `latin1` | parar e reavaliar o script |
| linha existe e `ativo = 1` | 1 linha | o `UPDATE` afetaria 0 linhas em silêncio |
| duplo-encode nos dados | `0` | parar: `SET NAMES utf8mb4` corromperia o texto |
| tipo da coluna | `longtext` | conferir capacidade |

### 3. Teste antes da produção

Aplicar primeiro em `teste.sosconsumidor.com.br`, conferir a página renderizada e só
então repetir em produção. Foi assim na correção do `calendarPT.php`.

### 4. Execução com commit manual

**Sessão interativa, nunca `mysql < arquivo.sql`.** Os scripts terminam sem `COMMIT`
por design. O operador lê a saída e decide:

- `linhas_afetadas` tem de ser exatamente **1**. Se for 0, `ROLLBACK;`.
- A amostra de acentuação tem de mostrar acentos corretos. Se aparecer `Ã` ou `?` no
  lugar de acento, `ROLLBACK;`.
- `acessos` e `id` preservados, URL intacta.

Só então `COMMIT;`.

### 5. Validação pós-alteração

1. Abrir a página pública e conferir texto e acentuação no navegador.
2. Conferir que o marcador `[GOOGLE_ADSENSE]` continua sendo processado.
3. Checar o log de erros PHP — sem novas linhas após a janela de cache.
4. Registrar a alteração na seção abaixo.

### 6. Reversão

```sql
START TRANSACTION;
UPDATE <tabela> t
JOIN audit_backup_<tabela>_<AAAAMMDD> b ON b.id = t.id
SET t.resposta = b.resposta,
    t.sis_data_editar = b.sis_data_editar,
    t.sis_hora_editar = b.sis_hora_editar,
    t.sis_user_editar = b.sis_user_editar
WHERE t.id = <id>;
SELECT ROW_COUNT() AS linhas_afetadas;   -- tem de ser 1
-- conferir e então: COMMIT;  ou  ROLLBACK;
```

As tabelas `audit_backup_*` **não devem ser removidas**. São o registro de auditoria.

---

## Padrão editorial das reescritas

Conforme `CONTENT_AUDIT_20260803.md`:

- responder a dúvida no primeiro parágrafo;
- separar regra geral, exceções e passos práticos;
- não prometer indenização nem resultado;
- explicar o termo jurídico quando for indispensável usá-lo;
- informar a data da revisão e vincular fontes oficiais;
- preservar URL, `id` e histórico de `acessos`;
- manter o marcador `[GOOGLE_ADSENSE]`;
- acolher quem está endividado, sem culpabilizar.

**Jurisprudência com status móvel:** teses ainda não fixadas precisam ser reconferidas
na fonte oficial antes da publicação. Um texto que afirma "o STJ ainda analisa" fica
factualmente errado no instante em que a tese é fixada — e o erro fica público numa
página de alto tráfego. Vale para qualquer tema afetado, súmula em elaboração ou
recurso repetitivo pendente citado nos FAQs.

Status conferido:

| Precedente | Status | Conferido em | Onde é citado |
|---|---|---|---|
| STJ, Tema Repetitivo 1264 | **afetado, sem tese fixada** | 03/08/2026 | FAQ 11 |

---

## Registro de alterações

| Data | Tabela | ID | Alteração | Backup | Executado por | Validado |
|---|---|---:|---|---|---|---|
| — | `perguntas_e_respostas` | 11 | Reescrita: separa prazo de negativação de prescrição da cobrança | `audit_backup_perguntas_e_respostas_20260803` | **pendente** | **pendente** |

> A linha do ID 11 permanece como *pendente*: em 03/08/2026 o `faq_11_v2.sql` foi
> revisado e liberado, mas **não foi executado em produção**. Preencher data,
> executor e validação no momento da aplicação.
