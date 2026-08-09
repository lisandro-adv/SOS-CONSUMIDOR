# SOS Consumidor - Documento de transição

Data: 3 de agosto de 2026  
Projeto: `www.sosconsumidor.com.br`  
Objetivo: recuperar relevância do portal, corrigir conteúdo e infraestrutura, implantar IA de fórum para consumidores, criar rotina editorial segura e validar novos produtos de assinatura.

> **Segurança:** este documento não contém senhas, chaves SSH, tokens de API nem credenciais. O acesso técnico já existe no ambiente de trabalho, mas credenciais nunca devem ser copiadas para conversas, arquivos ou prompts.

---

## 1. Visão e decisões estratégicas

O fundador é advogado, atua há mais de 20 anos em direito do consumidor e criou o SOS Consumidor. O site ficou anos com pouca manutenção e perdeu relevância; o objetivo é reerguê-lo, gerar audiência útil e monetização ética.

Princípios definidos:

- O fórum público responde **apenas** dúvidas de direito do consumidor e temas diretamente ligados ao cotidiano financeiro do cidadão.
- A resposta deve ser juridicamente fundamentada, mas em linguagem simples, objetiva e sem juridiquês.
- O site não promete resultado, indenização ou solução automática.
- O conteúdo deve acolher quem está endividado, sem culpabilizar a pessoa.
- A experiência profissional real do fundador deve ser o diferencial editorial e de revisão, sem atribuir autoria pública a ele sem consentimento.
- A IA serve para triagem, pesquisa, recuperação de conteúdo aprovado e rascunhos; não substitui revisão humana em materiais de risco.

Temas editoriais permanentes:

- direito do consumidor;
- endividamento e superendividamento;
- crédito, juros, empréstimos e consignado;
- impostos que afetam o cidadão;
- inflação, aumento de preços, tarifas, custo de vida, combustível, energia e aluguel;
- golpes, fraudes, Pix, falso boleto e roubo de celular;
- bets, apostas, gastos e redução de danos;
- economia quando houver impacto concreto no bolso ou na rotina da população brasileira.

---

## 2. Infraestrutura e acessos já confirmados

### VPS do site em produção

- Hospedagem: Hostinger/Hestia.
- Site público: `https://www.sosconsumidor.com.br/`.
- Diretório de produção: `/home/user/web/sosconsumidor.com.br/public_html`.
- Diretório de testes: `/home/user/web/teste.sosconsumidor.com.br/public_html`.
- Stack: PHP 8.3 e MySQL 8.0.
- Acesso root e de hospedagem via SSH já foi confirmado no ambiente local. Não pedir ou expor credenciais.

### VPS da IA

- VPS próprio separado para a IA do fórum.
- API restrita a `127.0.0.1:8000`; não está publicamente lançada.
- Modelo local: `qwen3:4b-instruct` via Ollama.
- Mantido aquecido com `OLLAMA_KEEP_ALIVE=-1` e pré-aquecimento na inicialização para evitar partida fria longa.
- A API ainda não foi liberada para perguntas reais do site.

### Backups feitos antes de alterações

- Backup integral do usuário do Hestia criado em 03/08/2026 e validado por TAR e SHA-256.
- Cópia retida no VPS e cópia externa validada no Dropbox em `BACKUPS/VPS_AUTOMATICOS/SITE/`.
- Houve um aviso não bloqueante de empacotamento de e-mail no Hestia, mas o backup terminou com sucesso e foi validado.
- Sempre criar backup por arquivo/tabela antes de qualquer alteração adicional, embora o backup completo já exista.

---

## 3. Alteração já aplicada em produção

### Correção do calendário PHP

Problema encontrado:

- O arquivo `busca/calendarPT.php` aceitava `month` e `year` como strings e as passava para `mktime()`.
- Em PHP 8.3, requisições malformadas geravam erro fatal e páginas incompletas.
- O mesmo arquivo construía URL a partir de `HTTP_HOST`, o que criava risco de header host injection/XSS.

Correção aplicada e validada:

- Arquivo de referência local corrigido: `INFRA/audit/live_20260803/sosconsumidor.com.br/public_html/busca/calendarPT.php`.
- Meses e anos agora são validados como inteiros dentro de faixa aceitável; valores inválidos usam o mês/ano atual.
- Links agora usam caminho local seguro (`SCRIPT_NAME` escapado), sem confiar em `HTTP_HOST`.
- Teste primeiro em `teste.sosconsumidor.com.br`, depois em produção.
- Foram testadas entradas normais, texto no mês/ano e valores extremos; todos retornaram HTTP 200.
- Após a janela de cache do PHP, três novos testes inválidos não criaram novas linhas de erro.
- Backups individuais criados junto aos arquivos antes de cada troca, com sufixo `pre-validation` e `pre-fix`.

Não houve alteração de layout, HTML visual, CSS ou dimensões do calendário.

---

## 4. Inventário de conteúdo e banco de dados

Banco relevante: `user_sos`.

### Dados levantados

- `forum_consumidor`: 9.450 registros, sendo 4.718 tópicos iniciais e 4.732 respostas; datas de 2015-11-12 a 2025-12-01.
- `perguntas_e_respostas`: 1.201 registros, 371 ativos, 369 respondidos e mais de 25 milhões de acessos acumulados.
- `noticias`: 19.466 registros, 19.079 ativos e mais de 32 milhões de acessos acumulados.
- A tabela `historico_ia` está vazia; não há histórico aproveitável das respostas da antiga IA.

### Principais páginas por acessos e risco inicial

| ID | Acessos | Tema | Situação |
|---:|---:|---|---|
| 11 | 1.442.587 | Prazo de nome no SPC/Serasa | Crítico; reescrita pronta, ainda não executada no banco |
| 278 | 1.248.436 | Bens impenhoráveis | Revisão integral necessária |
| 215 | 832.852 | Quando pode negativar | Revisar comunicação prévia e obrigações |
| 28 | 698.118 | Protesto de dívida prescrita | Crítico; título promete indenização de modo incorreto |
| 367 | 647.208 | Acordo/primeira parcela e negativação | Consolidar com ID 432 |
| 368 | 611.054 | Baixa de cheque | Orientação operacional desatualizada |
| 23 | 516.731 | Endividamento | Reescrever em tom acolhedor, incluir Lei do Superendividamento |
| 362 | 487.063 | Protesto e prazos de títulos | Crítico; generalizações incorretas |
| 479 | 470.246 | Cartão perdido/clonado | Atualizar canais e procedimentos |
| 222 | 462.549 | Manutenção após pagamento | Corrigir afirmações absolutas sobre dano moral |
| 267 | 461.484 | Prisão por dívida | Crítico; confunde dívida civil com crime |

Relatório local: `INFRA/audit/CONTENT_AUDIT_20260803.md`.

---

## 5. Auditoria jurídica inicial já confirmada

Fontes oficiais consultadas:

- CDC, especialmente arts. 42, 43, 51, 52 e 54-A a 54-G.
- CPC, art. 833.
- Lei 8.009/1990 (bem de família).
- STJ, REsp 2.095.414/SP: prazo de cinco anos de registro negativo conta do primeiro dia seguinte ao vencimento.
- STJ, Súmulas 323, 385 e 548; Tema Repetitivo 922.
- STJ, REsp 1.536.035/PR: protesto irregular de cheque prescrito não gera dano moral automático se ainda houver outra via de cobrança.
- STJ, Tema Repetitivo 1264: ainda afetado, sem tese definitiva, sobre cobrança extrajudicial de dívida prescrita e plataformas de negociação.
- STF, Súmula Vinculante 25: prisão civil do depositário infiel é ilícita; na prática, a hipótese atual de prisão civil é a dívida alimentar nas condições legais.

### FAQ ID 11: reescrita pronta, mas pendente de execução

Problema atual:

- A resposta de maior tráfego mistura o limite de cinco anos da negativação com prescrição de cobrança judicial.
- Afirma que toda dívida perde o direito de cobrança na Justiça em cinco anos, o que é incorreto: a prescrição varia conforme natureza, documento e fatos interruptivos/suspensivos.

Arquivo SQL pronto:

- `INFRA/audit/updates/faq_11.sql`.

O script:

- cria uma cópia da linha original na tabela `audit_backup_perguntas_e_respostas_20260803`;
- atualiza somente o ID 11, preservando URL, ID e acessos;
- registra data/hora de edição;
- explica em linguagem simples a diferença entre negativação e prescrição;
- informa o Tema 1264 pendente;
- inclui passos práticos e fontes oficiais;
- mantém o marcador `[GOOGLE_ADSENSE]`.

**Importante:** o arquivo SQL ainda NÃO foi executado na produção. Antes de executar, revisar o texto uma última vez, copiar o SQL ao VPS e rodar com transação. Depois validar a página pública, encoding e log de erros.

---

## 6. IA do Fórum do Consumidor

Arquivos principais:

- `INFRA/sos_ia/api/app/main.py`
- `INFRA/sos_ia/api/app/legal_knowledge.json`
- `INFRA/sos_ia/forum_tests/cases.json`
- `INFRA/sos_ia/scripts/run_forum_eval.py`
- `INFRA/sos_ia/RESPONSE_BANK_PLAN.md`

Melhorias já feitas:

- prompt consumidor-only, linguagem simples e foco em direito do consumidor;
- filtro de escopo e de dados pessoais;
- fontes jurídicas oficiais em JSON;
- saída de fontes determinística;
- 12 testes locais passando;
- timeout da resposta reduzido para 45 segundos;
- resposta limitada a aproximadamente 450 tokens;
- pré-aquecimento do modelo e `keep_alive` indefinido.

Situação:

- O modelo local Qwen 4B serve como classificador, recuperação de respostas aprovadas e primeira camada barata.
- O Qwen não “aprende” automaticamente a cada conversa. A estratégia correta é banco de respostas aprovado + recuperação (RAG) e, futuramente, fine-tuning offline, se fizer sentido.
- Não lançar a IA pública antes de nova bateria de testes de velocidade, precisão e recusa fora de escopo.
- Um teste anterior mostrou partida fria excessiva; foi corrigido por prewarm/keep alive, mas a avaliação completa precisa ser repetida.

### Estratégia de custo

- Orçamento inicial de IA: até US$ 50/mês.
- Modelo pago considerado para rascunhos/avaliação: Gemini 2.5 Flash-Lite, já compatível com módulo existente e de custo baixo.
- Não reutilizar chave Gemini antiga: uma chave exposta em arquivos históricos deve ser rotacionada e removida/quarentenada.
- Controles necessários: banco de respostas primeiro, limite de caracteres/tokens, uma tentativa de repetição no máximo, teto diário e mensal, e desligamento automático ao atingir orçamento.
- Não usar pesquisa web paga em toda pergunta.

---

## 7. Rotina editorial e notícias

### Problema encontrado na automação anterior

A automação ativa em produção foi pausada de forma reversível, exceto a newsletter.

Motivos:

- Coletor copiava textos completos de veículos de imprensa, criando risco autoral/editorial.
- Curador usava modelo antigo que retornava 404; itens com falha eram marcados para revisão humana.
- Revisor posterior aprovava automaticamente esses itens, inclusive materiais potencialmente irrelevantes.
- Houve publicação de textos longos de terceiros.

Crontab root anterior foi salvo em backup. Linhas do coletor, curador e revisor foram comentadas com marcador reversível `SOS_AUDIT_PAUSED_20260803`. A newsletter não foi pausada.

### Nova rotina local, ainda não implantada

Arquivos:

- `INFRA/editorial/daily_article.py`
- `INFRA/editorial/sources.json`
- `INFRA/editorial/test_daily_article.py`
- `INFRA/editorial/README.md`

Características:

- coleta apenas metadados RSS, não texto integral de páginas;
- cria pacote de pesquisa com até 5 pautas, deduplicado;
- pode fazer no máximo uma chamada de IA por execução;
- gera apenas rascunho, nunca publica;
- valida tamanho, linguagem, promessas, fontes e possível cópia extensa;
- estados: `research_packet`, `awaiting_human_review` e `rejected_by_validator`;
- testes unitários e `py_compile` passam.

Padrão editorial definido:

- 350 a 650 palavras, máximo absoluto de 700;
- título forte e verdadeiro, até 75 caracteres;
- começar pela informação principal;
- no máximo três subtítulos;
- seções obrigatórias: `O que isso significa para você` e `O que fazer agora`;
- sem juridiquês, alarmismo, promessa de resultado ou cópia de fonte;
- em bets, abordagem de redução de danos e indicação de ajuda profissional quando houver perda de controle;
- todo artigo com fontes consultadas.

Próximos passos:

1. testar RSS no ambiente de testes;
2. verificar e ampliar fontes oficiais (Senacon, Procons, Banco Central, Receita, Anatel, Aneel, STJ, STF etc.);
3. implantar em modo somente-pesquisa/fila;
4. estabelecer revisão humana antes de qualquer publicação;
5. criar importador separado para banco com `ativo=0` e publicação editorial explícita.

---

## 8. Produtos de assinatura em estudo

Documento: `PRODUCT/PRD_ASSINATURAS.md`.

### SOS Consumidor+

Hipótese de preço: R$ 29,90/mês.

MVP proposto:

- fórum de IA com histórico privado e perguntas de acompanhamento;
- respostas ilimitadas quando recuperadas de banco aprovado e franquia para respostas personalizadas;
- gerenciador financeiro simples: receitas, gastos, dívidas, juros e vencimentos;
- plano de saída das dívidas e simulações sem recomendar crédito ou investimento;
- cartilhas e vídeos curtos;
- alertas de golpes, direitos e temas econômicos;
- exportação de resumo de dívidas e providências.

Fora do MVP:

- Open Finance;
- intermediação/concessão de crédito;
- promessa de resultado;
- atendimento jurídico individual apresentado como parecer;
- contato automático com credores.

### SOS Pro para advogados

Não deve tentar replicar uma biblioteca proprietária de doutrina e jurisprudência. Começar com documentos enviados pelo advogado, legislação oficial, precedentes verificáveis e fluxos de produtividade.

Hipótese de planos, sujeita a teste de custo real:

| Plano | Preço | Uso inicial |
|---|---:|---|
| Essencial | R$ 29,90 | 30 interações curtas; 5 análises de até 15 páginas |
| Prata | R$ 39,90 | 60 interações curtas; 12 análises de até 30 páginas |
| Ouro | R$ 49,90 | 100 interações curtas; 20 análises de até 50 páginas |

Regras críticas:

- Não prometer uso ilimitado a esse preço.
- Tarefas longas, análise de documentos e geração de peça devem consumir mais créditos.
- Mostrar consumo antes da execução.
- Ter teto financeiro interno por usuário/dia/mês.
- Segurança P0: isolamento por conta/caso, criptografia, retenção configurável, exclusão, logs, não treinar com documentos sem autorização, e verificador de citações.
- Avaliar LGPD, sigilo profissional e regras da OAB antes de beta externo.

Sequência recomendada:

1. estabilizar site, conteúdo e fórum;
2. landing pages/lista de interesse para consumidor e advogado;
3. beta pequeno do SOS Consumidor+;
4. beta do SOS Pro com 10 a 20 advogados e escopo limitado;
5. medir custo real por tarefa durante 30 dias;
6. só então fechar preço, franquias e cobrança recorrente.

---

## 9. Benchmark do Jusbrasil: limite jurídico e estratégia correta

O fundador já possui plano de consulta processual/jurisprudência no Jusbrasil. Foi avaliada uma possível migração para planos mais caros; em tela apresentada em 03/08/2026, o plano Profissional aparecia por R$ 108,90/mês com 150 mensagens de Jus IA e o Premium por R$ 208,90/mês.

Conclusão:

- Há oportunidade para produto jurídico mais barato para uso leve/moderado.
- Os preços de concorrentes são altos para advogados que usam poucas vezes por semana.
- Porém, não se pode estruturar produto concorrente copiando o acervo deles.

Termos do Jusbrasil verificados em abril de 2026:

- proíbem scraping, cópia, reprodução e exploração do conteúdo sem autorização;
- conteúdo gerado pelo Jus IA é para uso pessoal/profissional do assinante e não pode ser comercializado/distribuído sem autorização;
- livros e doutrina têm direitos de autores/editoras.

Uso permitido/recomendado para benchmark:

- conjunto limitado de perguntas próprias e casos fictícios;
- comparação de tempo, cobertura, facilidade de uso e qualidade aparente;
- verificação individual de citações em fontes oficiais;
- registrar somente métricas, funções observadas e conclusões próprias;
- não copiar respostas, trechos de livros, modelos, base de dados ou estrutura proprietária.

Não contratar plano Premium agora apenas para “varrer o acervo”. Se houver contratação futura, usar um mês para benchmark controlado quando o SOS Pro já estiver maduro, com MVP, testes e beta.

### Situação de navegador

- O navegador integrado não estava disponível nesta sessão de Codex/VS Code.
- Não instalar extensões aleatórias do marketplace VS Code; elas não concedem acesso ao navegador para o agente e aumentam risco.
- O usuário entrou no ChatGPT Desktop. Lá, o navegador integrado pode ser aberto numa conversa Work/Codex pelo atalho `Cmd + Shift + B` no Mac.
- O usuário deve entrar no Jusbrasil apenas dentro do navegador, nunca informar senha no chat.
- Prompt seguro para o assistente do ChatGPT Desktop:

```text
Analise somente meu plano atual no Jusbrasil, em modo leitura. Identifique recursos,
limites de uso, pesquisa processual, jurisprudência, doutrina e funcionalidades de IA
disponíveis. Não altere assinatura, não clique em compra, não baixe conteúdo e não faça
extração em massa. Produza um relatório comparando o que já tenho com um possível produto SOS Pro.
```

---

## 10. Próximas ações prioritárias

1. Executar e validar com segurança a atualização do FAQ ID 11 em produção.
2. Reescrever e revisar os FAQs críticos: 28, 267, 278, 362, 367/432, 222, 479 e 23.
3. Criar procedimento de backup por alteração de conteúdo e registro de auditoria.
4. Repetir testes completos da IA do fórum, incluindo latência com modelo já aquecido.
5. Construir exportação anonimizada do fórum e identificar tópicos mais recorrentes para banco de respostas aprovado.
6. Testar nova automação editorial em modo fila, sem publicação automática.
7. Corrigir a automação antiga antes de reativá-la: RSS/metadados, fonte/atribuição, modelo válido, revisão humana real e bloqueio seguro em caso de falha.
8. Fazer página/lista de interesse dos produtos de assinatura somente após estabilizar conteúdo e confiança.
9. Quando o SOS Pro estiver em beta, realizar benchmark manual e juridicamente seguro do plano atual do Jusbrasil.

---

## 11. Prompt de continuidade sugerido

```text
Leia o arquivo HANDOFF_SOS_CONSUMIDOR_2026-08-03.md e continue o projeto SOS Consumidor.
Priorize segurança, backups, auditoria jurídica com fontes oficiais e linguagem para leigos.
Não exponha senhas, tokens ou chaves. Não altere layout sem autorização. Não publique notícias,
respostas ou artigos automaticamente. Antes de alterar produção, teste em teste.sosconsumidor.com.br,
crie backup por arquivo/tabela e valide HTTP/logs após a alteração.

A próxima ação sugerida é revisar e executar com segurança o arquivo
INFRA/audit/updates/faq_11.sql, que ainda não foi aplicado em produção.
```

---

## 12. Continuação em 03/08/2026 — revisão do FAQ 11 concluída, execução pendente

### Feito

**Revisão final do `faq_11.sql` (etapa que o handoff exigia antes de executar).**
O texto jurídico foi aprovado sem alteração. O script teve dois defeitos operacionais
corrigidos numa versão nova, `INFRA/audit/updates/faq_11_v2.sql`:

1. **Commit automático.** A v1 terminava em `COMMIT`. Se o `UPDATE` afetasse 0 linhas —
   o que acontece silenciosamente caso `ativo <> 1`, por causa do `WHERE id = 11 AND
   ativo = 1` — a transação era confirmada sem erro e o operador concluiria que deu
   certo. A v2 **não dá commit**: expõe `ROW_COUNT()` e exige `COMMIT;` ou `ROLLBACK;`
   digitado à mão.
2. **`sis_user_editar` não era atualizado.** O registro de auditoria continuaria
   atribuindo a edição ao autor anterior. A v2 grava `@EDITOR_ID`.

O texto jurídico da v2 é **byte-a-byte idêntico** ao da v1 (2.826 caracteres),
verificado por comparação programática. O marcador `[GOOGLE_ADSENSE]` está preservado.

**Risco de encoding investigado e afastado.** A tabela é `latin1` e a página declara
`<meta charset="UTF-8">`, combinação que costuma indicar UTF-8 cru dentro de latin1 —
caso em que `SET NAMES utf8mb4` corromperia a página de maior tráfego do site. A
análise de bytes do dump histórico encontrou 1.293 sequências UTF-8 limpas e **zero**
assinaturas de duplo-encode (`c3 83 c2 xx`), que necessariamente apareceriam se a
patologia existisse. Conclusão: coluna latin1 legítima, e o `SET NAMES utf8mb4` do
script está **correto**. Confirmado também que todo o texto novo cabe no repertório
latin1 — nenhum caractere se perderia.

*Ressalva:* a evidência vem do dump de 2016. Os passos `[1]` e `[3]` do pré-voo
reconfirmam o charset na produção atual.

**Novos artefatos:**

- `INFRA/audit/updates/faq_11_v2.sql` — versão para executar.
- `INFRA/audit/updates/faq_11_PREFLIGHT.sql` — 6 checagens somente leitura.
- `INFRA/audit/PROCEDIMENTO_ALTERACAO_CONTEUDO.md` — atende ao item 3 da lista de
  prioridades; vale para os outros 8 FAQs críticos.

### Não feito — bloqueio de acesso

**O `faq_11_v2.sql` NÃO foi executado.** O SSH para `root@187.77.48.16` foi negado
pelo classificador de permissões da sessão Claude Code. Nenhum comando chegou a rodar
no VPS; nada em produção foi alterado nesta sessão.

Para desbloquear, autorizar a regra de Bash correspondente ao `ssh` do VPS nas
configurações do Claude Code, ou executar o procedimento manualmente.

### Próxima ação

1. Rodar `faq_11_PREFLIGHT.sql` e conferir as 6 checagens.
2. Aplicar `faq_11_v2.sql` primeiro em `teste.sosconsumidor.com.br`.
3. Repetir em produção em sessão interativa, com `COMMIT` manual.
4. Validar página pública, acentuação e log de erros PHP.
5. Preencher o registro de alterações em `PROCEDIMENTO_ALTERACAO_CONTEUDO.md`.

> **Tema 1264 — confirmado pendente em 03/08/2026.** O texto afirma que o STJ *ainda
> analisa* o Tema Repetitivo 1264 e a afirmação está correta: o tema segue afetado, sem
> tese fixada, conforme confirmação do fundador na data. O conteúdo do FAQ 11 está
> liberado sem ressalva jurídica. Se a aplicação em produção for adiada por semanas,
> reconferir — é a única frase do texto cujo valor de verdade pode mudar sozinho.
