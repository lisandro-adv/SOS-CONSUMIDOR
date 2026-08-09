# Leitura integral dos FAQs — achados

3 de agosto de 2026 · corpus baixado da produção (365 páginas ativas, 1,21 milhão de caracteres)

**Progresso: 118 de 365 lidos integralmente.** Este documento é acumulativo — cada bloco
lido acrescenta achados. Os IDs entre colchetes são os do final da URL pública, que **não
coincidem** com o `id` do banco (ex.: FAQ do banco 267 responde pela URL `...-162`). Antes
de qualquer `UPDATE`, mapear URL → `id` real via `SELECT id, link`.

---

## A. Erros jurídicos graves (corrigir ou suprimir com prioridade)

### A1. Busca e apreensão — regra dos 40% revogada · **[80]**

O texto afirma que, se o devedor "já pagou ao menos 40% do contrato", pode purgar a mora
e colocar as parcelas em dia. **Essa regra foi revogada.** A redação original do
art. 3º, § 1º, do DL 911/69 foi alterada pela Lei 10.931/2004, e o STJ firmou em
repetitivo (**Tema 722**, REsp 1.418.593/MS) que o devedor deve pagar a **integralidade
da dívida** — todas as parcelas vencidas e vincendas — em cinco dias, para reaver o bem.

**Consequência prática:** um consumidor que confie no texto perde o carro. Ele deixará de
pagar o total acreditando que os 40% bastam.

### A2. Ação monitória — prazo e artigo errados · **[448]**

Dois erros no mesmo texto:

- Diz que o prazo é "5 anos a contar da data de vencimento". A **Súmula 503 do STJ** fixa
  o prazo em cinco anos **a contar do dia seguinte à data de emissão** estampada no
  cheque. Contar do vencimento pode fazer o leitor perder o prazo.
- Cita o **art. 1.102.a do CPC**, dispositivo do CPC de 1973, **revogado**. A monitória
  hoje está nos arts. 700 a 702 do CPC/2015.

### A3. Prisão civil de depositário infiel — apresentada como válida · **[201]**, **[224]**

Ambos afirmam que a prisão civil por dívida é possível "nos casos que envolvem a falta de
pagamento voluntária e inescusável de alimentos **e de depositários infiéis**".

A segunda hipótese não existe mais desde 2009: **Súmula Vinculante 25 do STF** — é ilícita
a prisão civil de depositário infiel, qualquer que seja a modalidade do depósito. Mesmo
erro já identificado no FAQ 267 do banco, agora replicado em mais dois registros.

### A4. Prescrição de cinco anos generalizada · **[441]**, **[227]**, **[434]**

Repetem o erro central do FAQ 11: afirmam que toda dívida prescreve em cinco anos "conforme
o Código Civil". A prescrição varia conforme a natureza da dívida, o documento e os fatos
interruptivos. O **[441]** ainda acrescenta que a inscrição após cinco anos gera direito a
"indenização pelos danos morais", como se fosse automático.

### A5. Dano moral apresentado como automático · **[434]**, **[441]**, **[431]**, **[433]**, **[438]**, **[214]**, **[443]**

Vários textos afirmam que a irregularidade "dá direito a indenização por danos morais",
sem a ressalva da **Súmula 385 do STJ** (havendo inscrição legítima anterior, não cabe
indenização, apenas o cancelamento). O **[443]**, sobre fila de banco, é o mais frágil:
trata como certa uma indenização que a jurisprudência concede de forma restritiva.

### A6. Leasing — devolução do veículo não quita a dívida · **[457]**, **[458]**

Textos quase idênticos afirmam que, devolvendo o veículo e pagando as parcelas vencidas,
"o contrato estará quitado". Isso não é exato: a devolução no arrendamento mercantil
envolve acerto do VRG e eventual saldo, e a jurisprudência não trata a entrega como
quitação automática. Orientação que pode levar o consumidor a parar de pagar acreditando
estar quitado.

---

## B. Normas revogadas ou superadas citadas como vigentes

| ID | Norma citada | Situação |
|---|---|---|
| [543] | Resolução BCB 2.025/1993 (conta inativa) | Superada pela regulamentação posterior de contas de depósito |
| [435], [359] | Circular BCB 3.334/2006 (CCF) | Norma antiga; conferir vigência antes de manter |
| [448] | CPC/1973, art. 1.102.a | Revogado pelo CPC/2015 |
| [80] | DL 911/69 na redação anterior | Alterado pela Lei 10.931/2004 |
| [328] | "poupança rende TR + 6% ao ano" | Alterado pela Lei 12.703/2012 (70% da Selic quando Selic ≤ 8,5%) |
| [494] | MP do cadastro positivo | Regime atual: Lei 12.414/2011 com LC 166/2019 |
| [459]–[472] | Resolução CMN 3.518 / Circular 3.371 | Série inteira datada de 2007-2008 |

O bloco **[459]–[472]** confirma-se insalvável: o **[469]** lista como novidade datas de
"10 de dezembro de 2007", "3 de março de 2008" e "30 de abril de 2008", e o **[466]** fala
em vigência "começando em 31 de março de 2008".

---

## C. Páginas quebradas — no ar, sem conteúdo

**[490]** e **[424]** retornam página completa com **zero caractere** de resposta. Estão
ativas e indexáveis. Devem ser suprimidas ou preenchidas.

---

## D. Fora do escopo editorial

| ID | Conteúdo | Observação |
|---|---|---|
| [503] | **Lei do Protocolo de Avaliação do Frênulo da Língua em Bebês** | Lei 13.002/2014, íntegra publicada como FAQ. Nada a ver com direito do consumidor |
| [320] | Vírus de e-mail da Gol | Notícia da Folha Online de **23/01/2007** |
| [351] | Golpe por telefone da **Brasil Telecom** | Operadora não existe mais; telefones 10314/1053 inválidos |
| [133] | Dicas sobre cartões virtuais de e-mail | Orientação de segurança de internet de outra época |
| [238], [300] | Emagrecer, dicas de alimentação | Fora de escopo |
| [527], [533] | Milhas Smiles, formulário DS-160 | Fora de escopo |
| [297] | "Cartas por 1 centavo" | Ensina a explorar brecha de tarifa postal |
| [505] | Portal do Ministério do Trabalho | Órgão reestruturado; link morto |

---

## E. Links institucionais mortos (correção mecânica, alto volume)

Domínios oficiais mudaram e os textos ainda apontam para os antigos:

| Citado | Correto hoje | Ocorre em |
|---|---|---|
| `bacen.gov.br` | `bcb.gov.br` | [185], [117], [62] e outros |
| `stj.gov.br` | `stj.jus.br` | [121], [214] |
| `tj.rs.gov.br` | `tjrs.jus.br` | [121], [124] |
| `inss.gov.br` | `gov.br/inss` | [146], [151] |
| `portaldoconsumidor.gov.br` | `consumidor.gov.br` | [163] |

O **[163]** ainda recomenda o **ReclameAqui**, empresa privada, ao lado de canais oficiais.

---

## F. Dependência de fonte de terceiros

Vários textos trazem no rodapé **"fonte: Site www.endividado.com.br"** — [426], [427],
[443], [444], [287], [358], [442], [227], [320]. Além do risco autoral já levantado na
auditoria da automação de notícias, são exatamente os textos com as afirmações jurídicas
mais frágeis. Recomendo tratá-los como bloco: reescrever com fundamentação própria ou
suprimir.

---

## G. Duplicatas confirmadas por leitura

Não são títulos parecidos — o texto é **idêntico**:

| Par | Situação |
|---|---|
| [426] e [427] | Texto integralmente igual, inclusive a fonte citada |
| [457] e [458] | Diferem apenas por uma frase final sobre defensoria |
| [201] e [224] | Mesma resposta sobre prisão civil, ambas com o erro do depositário infiel |
| [213] e [194] | Texto idêntico, um para SPC/Serasa e outro para CADIN |
| [99] e [116] | Texto idêntico, um para cartão e outro para cheque especial |

---

## H. Conteúdo correto — registrar para não mexer

Nem tudo está errado, e vale registrar o que resistiu à conferência:

- **[312]** prazos do art. 26 do CDC (30 e 90 dias) e vício oculto — correto.
- **[311]** art. 18 do CDC, prazo de 30 dias para sanar o vício — correto.
- **[123]**, **[91]** limite de 2% de multa (CDC, art. 52, § 1º) — correto.
- **[125]** desconto proporcional na quitação antecipada (art. 52, § 2º) — correto.
- **[98]** venda casada de seguro no cartão e devolução em dobro (arts. 39, I, e 42, § único) — correto.
- **[226]**, **[227]** alerta contra "kits para limpar o nome" — correto e socialmente útil.
- **[284]** Juizados: teto de 40 salários mínimos, advogado a partir de 20 — correto.

---

## Próximos blocos a ler

Restam **247 registros**: 72 do bloco de 400–1500 caracteres, 103 do bloco de
1500–4000 e 72 acima de 4000. Os textos longos concentram o maior risco, porque são os
que trazem transcrição de lei e jurisprudência — exatamente onde a desatualização é mais
provável e mais difícil de perceber.
