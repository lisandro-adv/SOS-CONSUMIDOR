# Auditoria de código — SOS Consumidor

Data: 02/08/2026  
Escopo: código PHP em produção e em `teste.sosconsumidor.com.br`; inspeção somente leitura nesta etapa.

## Resultado executivo

O site funciona em produção, mas a base principal é uma aplicação PHP legada que passou a rodar em PHP 8.3. Há incompatibilidades já identificadas, dependências antigas, ausência de versionamento e uma divergência de configuração que deixa o ambiente de teste indisponível (`HTTP 500`).

Antes de qualquer alteração, há um backup completo Hestia validado:

- arquivo: `/backup/user.2026-08-02_13-21-28.tar`
- tamanho aproximado: 3,6 GB
- SHA-256: `bc10c9f4a8793af3ae5923720823b1d4e1a48c51c4f0f97bb98d9a57a64354fe`

## Achados e prioridade

| Prioridade | Achado | Evidência | Ação recomendada |
|---|---|---|---|
| P0 | Ambiente de teste com erro 500 | `teste.sosconsumidor.com.br` retorna 500. O PHP tenta incluir `.../desize/init.php` fora do `open_basedir` permitido. | Corrigir a estrutura/configuração somente no teste e validar toda a navegação antes de publicar qualquer alteração. |
| P0 | Componente de upload incompatível com PHP 8.3 | `admsite/upload_foto/upload2.0.3.2/JSON.php`, linha 156, usa acesso a array com chaves `{}`, removido no PHP moderno. O mesmo arquivo existe nos dois ambientes. | Substituir o upload legado por biblioteca atual ou aplicar correção mínima compatível, testada no subdomínio de teste. |
| P1 | Dependências obsoletas | O `composer.lock` do administrativo aponta `swiftmailer/swiftmailer v5.4.12`, `sendpulse/rest-api 1.0.6` e `snipe/banbuilder 2.3.0`. | Inventariar os usos; migrar o envio de e-mail para Symfony Mailer/API do provedor e atualizar ou retirar bibliotecas sem uso. |
| P1 | Erros de validação no calendário | Logs mostram `TypeError` em `busca/calendarPT.php:58` quando parâmetros de mês/ano são inválidos. O código envia valores de URL diretamente a `mktime()`. | Validar e converter mês/ano para inteiros dentro de faixa válida; tratar parâmetros inválidos sem fatal error. |
| P1 | Configuração executável acessível por URL | `admsite/classes/PDOConfig.php` responde `HTTP 200` mas com corpo vazio. Não houve exposição de conteúdo nesta verificação, mas arquivo de configuração não deve ser acessível diretamente. | Bloquear acesso web à pasta de classes/configuração via Nginx/Apache, preservando os includes internos. |
| P1 | Registro temporário do módulo IA ainda ativo | `ia_consumidor/chat_api.php` escreve logs de diagnóstico em `/tmp/chat_api_debug.log`. | Remover log temporário ou trocar por registro estruturado, sem mensagens de usuários, tokens ou dados pessoais; definir retenção. |
| P2 | Sem repositório Git nos dois ambientes | Não há `.git` em produção nem no teste. | Criar repositório privado fora do diretório público, com deploy versionado produção/teste e arquivo de configuração excluído. |
| P2 | Código legado e cópias antigas | Há mais de 800 arquivos PHP por ambiente e vários diretórios antigos de upload/bibliotecas. | Mapear rotas usadas, retirar cópias não utilizadas do webroot e manter uma única árvore de dependências. |

## Verificações realizadas

- PHP CLI: **8.3.30**.
- Arquivos PHP analisados fora de bibliotecas/arquivos históricos: 866 na produção e 815 no teste.
- Uma falha de sintaxe/compatibilidade em cada ambiente: o JSON do uploader antigo.
- Não foram encontrados usos ativos de `mysql_*`, `create_function()` ou `preg_replace` com modificador `/e` nas áreas analisadas.
- Os poucos usos de `eval()`/`unserialize()` identificados estão em biblioteca de imagem, SwiftMailer ou no JSON do uploader; ainda requerem revisão/atualização quando essas bibliotecas forem tratadas.
- A página inicial de produção retorna HTTP 200. Os diagnósticos internos `_diag_tmpdir.php` e `_diag_ia.php` retornam 403; `PDOConfig.php` retorna 200 vazio.

## Ordem segura de implementação

1. Restaurar o funcionamento do teste corrigindo o `open_basedir`/caminho de `desize` **somente nele**.
2. Corrigir e testar o calendário e o uploader em teste.
3. Bloquear URLs diretas de classes/configurações e remover os logs temporários da IA.
4. Testar páginas públicas, administrativo, formulário, newsletter e IA.
5. Publicar alterações pequenas e versionadas, com novo backup antes de cada lote.
6. Atualizar dependências e modernizar o administrativo por etapas, nunca em uma mudança única.

## Observação de segurança

Os arquivos de backup expostos anteriormente já foram retirados do acesso público e existe uma regra persistente que bloqueia extensões de backup/arquivo. Como um arquivo compactado antigo ficou publicamente acessível, é recomendável trocar credenciais que possam ter estado nele (banco de dados, integrações de e-mail, CAPTCHA, encurtador, painel administrativo e chaves de IA) antes da monetização do módulo IA.
