# Relatório de conformidade jurídica do conteúdo — SOS Consumidor

3 de agosto de 2026 · Banco `user_sos`, tabela `perguntas_e_respostas` · 365 FAQs ativos

---

## 1. Resumo

Foram auditadas as páginas de maior tráfego do portal contra a legislação vigente e a
jurisprudência atual do STJ e do STF. O resultado principal:

> **Duas das páginas mais acessadas do site orientam o consumidor em sentido contrário
> a precedente vinculante do STJ.** Não se trata de texto desatualizado no detalhe: a
> orientação central está invertida.

Somados, os quatro FAQs corrigidos respondem por **2,35 milhões de acessos**.

| Situação | Registros | Ação |
|---|---:|---|
| Corrigidos (reescrita pronta) | 4 | `faqs_criticos_v1.sql` |
| Corrigido anteriormente, pendente de execução | 1 (ID 11) | `faq_11_v2.sql` |
| Propostos para supressão | 25 | `faqs_supressao_v1.sql` |
| **Executado em produção** | **0** | **bloqueado — ver seção 5** |

---

## 2. Erros jurídicos corrigidos

### 2.1 FAQ 28 e FAQ 362 — protesto após o prazo de apresentação

**O que o site diz hoje.** Que o protesto de cheque após o prazo de apresentação é
indevido e ilegal, e que gera direito a indenização por danos morais. O FAQ 28 afirma
isso já no título. Ambos se apoiam em acórdãos do TJRS reproduzidos na página.

**O que o STJ decidiu.** No **Tema Repetitivo 945**, em recurso repetitivo, a tese
fixada é que *sempre será possível, no prazo para a execução cambial, o protesto
cambiário de cheque, com a indicação do emitente como devedor*. Passar do prazo de
apresentação **não** torna o protesto ilegal; o que encerra a possibilidade é a
prescrição da execução.

**Gravidade.** Os acórdãos do TJRS citados são anteriores e foram superados pelo
repetitivo. O site orienta o consumidor a processar por algo que o STJ considera
legítimo — expondo o leitor a ajuizar ação perdida, com custas e sucumbência.

**Segundo erro, no mesmo tema.** O título do FAQ 28 promete indenização. O
**REsp 1.536.035/PR** decidiu que o protesto irregular de título prescrito **não gera
dano moral automático**. Some-se a **Súmula 385 do STJ**: quem já tem anotação legítima
anterior não tem direito a dano moral por inscrição indevida adicional.

**Correção.** Título reescrito para "Protesto de dívida antiga: quando é legal, quando
é abuso e quando cabe indenização". O texto passa a distinguir o protesto legítimo do
abuso real — a prática de gerar letra de câmbio com data recente para reabrir prazo
encerrado, que o texto original denunciava corretamente e foi mantida.

### 2.2 FAQ 278 — penhora de salário

**O que o site diz hoje.** "O salário não pode ser penhorado para o pagamento de
dívidas, salvo em caso de pensão alimentícia."

**O que mudou.** Em 19/04/2023, a **Corte Especial** do STJ, no **EREsp 1.874.222/DF**,
firmou que é possível relativizar a impenhorabilidade salarial para dívida **não
alimentar**, em percentual que não comprometa a subsistência digna do devedor e de sua
família. A regra deixou de ser absoluta. O Tema 1230 segue pendente, sem tese
definitiva; até lá vale o EREsp, aplicado caso a caso.

**Gravidade.** Um leitor endividado que confie no texto atual pode deixar de se
defender por acreditar que seu salário é intocável.

**Outros defeitos corrigidos no mesmo texto:**

- Afirmava que o art. 833 do CPC foi "modificado pela Lei 13.105/2015" — a Lei
  13.105/2015 **é** o próprio CPC.
- A lista do art. 833 pulava o **inciso VI** (seguro de vida).
- Especulava que instituições financeiras "não costumam entrar com ações de cobrança",
  levando o leitor a subestimar o risco. Removido.
- Acrescentadas as Súmulas **364** (bem de família protege pessoa solteira, separada ou
  viúva) e **486** (imóvel único alugado, com renda destinada à subsistência, segue
  protegido), e a Lei 14.181/2021.

### 2.3 FAQ 267 — prisão por dívidas

**O que o site diz hoje.** Que há prisão "em casos especiais, como a dívida de pensão
alimentícia ou de estelionatários".

**O erro.** A frase funde duas coisas de natureza distinta: a **prisão civil** por
alimentos (CF, art. 5º, LXVII; CPC, art. 528) e a **responsabilidade penal** por
estelionato (CP, art. 171). Estelionato não é dívida — é crime, e exige fraude desde o
início. Apresentá-lo como "prisão por dívida" alimenta exatamente o medo que cobradores
abusivos exploram.

**Correção.** O texto passa a afirmar de saída que dívida comum não prende, isola a
pensão alimentícia como única hipótese de prisão civil, registra a **Súmula Vinculante
25 do STF** (ilícita a prisão do depositário infiel) e explica a diferença entre não
pagar e fraudar. Acrescenta que ameaçar o consumidor de prisão para cobrar dívida é
**crime** — art. 71 do CDC, detenção de três meses a um ano.

**Tom.** O texto original atribuía o endividamento a "consumismo exagerado" e
"utilização não racional do crédito". Reescrito em tom acolhedor, conforme o princípio
editorial definido no handoff.

### 2.4 FAQ 11 — prazo no SPC/Serasa (pendente de execução)

Revisado em etapa anterior. Confunde o limite de cinco anos da negativação com a
prescrição da cobrança judicial. Reescrita pronta em `faq_11_v2.sql`. O texto informa
que o **Tema 1264** segue sem tese fixada — confirmado como correto em 03/08/2026.

---

## 3. Conteúdo proposto para supressão

Critério: está em desacordo com fato, lei ou jurisprudência **e não pode ser corrigido**.
Supressão aqui é `ativo = 0`, nunca `DELETE` — reversível, com URL e acessos preservados.

| Grupo | IDs | Motivo |
|---|---|---|
| **A. Duplicatas exatas** | 369, 426, 432 | Cópias de 368, 427 e 367 |
| **B. Série "novas regras de tarifas"** | 459–472 (14) | Datadas por natureza |
| **C. Fora do escopo** | 238, 300, 527, 533, 297 | Não é direito do consumidor |
| **D. Notícia datada como FAQ** | 482, 494, 359 | Norma superada ou conjuntura |

**Grupo B** — a série numerada de 1 a 14 sobre um pacote de medidas do Banco Central
inclui perguntas como *"11. As novas regras passam a valer a partir de quando?"* e
*"12. Quem vai fiscalizar as novas normas?"*. Não têm conserto: o referente ("as novas
regras") é ato normativo antigo, já alterado. Corrigir exigiria reescrever do zero como
matéria única sobre tarifas bancárias vigentes — o que recomendo fazer depois.

**Grupo D** — o ID 494 publica o texto de uma **medida provisória** sobre cadastro
positivo. O regime vigente é o da Lei 12.414/2011 com as alterações da **LC 166/2019**,
que inverteu a lógica para adesão automática. O ID 359 reproduz circular do Banco
Central sobre o CCF, sujeita a alterações posteriores.

**O ID 297** ("Aprenda a mandar cartas por 1 centavo") merece nota à parte: além de
provavelmente não funcionar mais, ensina a explorar brecha de tarifa postal. É
desalinhado de um site que se apresenta como referência de conduta correta.

### Alerta de SEO

Suprimir duplicata sem redirecionar descarta o tráfego acumulado. Para o Grupo A, o
correto é **301 do suprimido para o canônico** no `.htaccess`, antes de desativar:

| Suprimir | Redirecionar para |
|---|---|
| 369 | 368 |
| 426 | 427 |
| 432 | 367 |

---

## 4. Limites deste relatório

Sejam explícitos os limites do método, para que ninguém trate a lista como exaustiva:

- A triagem dos 365 FAQs ativos foi feita **por título**, não por leitura integral.
  Apenas os IDs 11, 28, 267, 278 e 362 foram lidos por inteiro e confrontados com
  fontes oficiais.
- Os grupos B, C e D estão marcados `[NÃO LIDO]` no SQL e **exigem confirmação
  editorial** antes da supressão.
- Restam sem auditoria os demais FAQs ativos e os **19.079 registros ativos de
  `noticias`**, onde é razoável esperar densidade ainda maior de conteúdo datado.
- Os FAQs 215, 367/432, 368, 23, 479, 222 e 411, apontados como críticos no
  `CONTENT_AUDIT_20260803.md`, **ainda não foram reescritos**.

---

## 5. Situação de execução

**Nada foi alterado em produção.** Os arquivos SQL estão prontos, com pré-voo, backup e
commit manual, mas não foram executados.

O SSH ao VPS funciona para comandos simples. Comandos **MySQL remotos** são bloqueados
pelo classificador de permissões desta sessão — tanto leitura quanto escrita. Sem isso
não há como rodar o pré-voo nem aplicar os `UPDATE`.

Para destravar: autorizar no Claude Code uma regra de Bash que permita `ssh ... mysql`
para este host. Alternativamente, o procedimento pode ser executado à mão — os arquivos
foram escritos para uso interativo e trazem as instruções no cabeçalho.

### Ordem recomendada

1. `faq_11_PREFLIGHT.sql` → `faq_11_v2.sql` (menor risco, já revisado)
2. `faqs_criticos_PREFLIGHT.sql` → `faqs_criticos_v1.sql` (os quatro erros jurídicos)
3. Redirecionamentos 301 do Grupo A no `.htaccess`
4. `faqs_supressao_v1.sql`, **após** confirmação editorial dos grupos B, C e D

Aplicar primeiro em `teste.sosconsumidor.com.br` em todos os casos. Registrar cada
alteração na tabela de `PROCEDIMENTO_ALTERACAO_CONTEUDO.md`.

---

## 6. Fontes oficiais consultadas

- [STJ, Tema Repetitivo 945](https://processo.stj.jus.br/repetitivos/temas_repetitivos/pesquisa.jsp?cod_tema_inicial=945&novaConsulta=true&tipo_pesquisa=T) — protesto de cheque no prazo da execução cambial
- [STJ, REsp 1.536.035/PR](https://informativos.trilhante.com.br/julgados/stj-resp-1536035-pr) — dano moral não automático no protesto irregular
- [STJ, EREsp 1.874.222/DF, Corte Especial](https://scon.stj.jus.br/jurisprudencia/externo/informativo/?aplicacao=informativo&acao=pesquisar&livre=@CNOT=%27019894%27) — relativização da impenhorabilidade salarial
- [STJ, Súmula 503](https://scon.stj.jus.br/SCON/sumstj/doc.jsp?livre=%22503%22.num.&b=SUMU) — ação monitória de cheque, prazo quinquenal
- STJ, Súmulas 364, 385, 486 e 548
- [STF, Súmula Vinculante 25](https://portal.stf.jus.br/jurisprudencia/sumariosumulas.asp?base=26)
- [CDC](https://www.planalto.gov.br/ccivil_03/leis/l8078compilado.htm), arts. 42, 43 e 71
- [CPC](https://www.planalto.gov.br/ccivil_03/_ato2015-2018/2015/lei/l13105.htm), arts. 528 e 833
- [Lei 8.009/1990](https://www.planalto.gov.br/ccivil_03/leis/l8009.htm) · [Lei 7.357/1985](https://www.planalto.gov.br/ccivil_03/leis/l7357.htm) · [Lei 5.474/1968](https://www.planalto.gov.br/ccivil_03/leis/l5474.htm) · Lei 14.181/2021
