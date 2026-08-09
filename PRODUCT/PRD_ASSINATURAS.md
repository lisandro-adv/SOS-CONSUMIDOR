# Assinaturas SOS Consumidor e SOS Pro

Status: hipótese de produto para validação  
Data: 3 de agosto de 2026

## 1. Decisão de produto

Criar dois produtos claramente separados sob a mesma operação:

1. **SOS Consumidor+**, para pessoas comuns, com assinatura inicial de R$ 29,90/mês.
2. **SOS Pro**, em domínio ou subdomínio próprio, para advogados com uso leve ou moderado de IA.

A separação é obrigatória porque linguagem, fontes, limites, privacidade, responsabilidade e testes são diferentes. A IA do fórum público não será usada como assistente profissional e respostas de advogados não serão mostradas ao consumidor sem adaptação e revisão.

## 2. Evidência inicial

- O acervo do site contém milhares de dúvidas acumuladas e 371 respostas ativas; as páginas mais acessadas concentram milhões de visualizações.
- O usuário fundador atua há mais de 20 anos em direito do consumidor, o que permite revisão jurídica humana real.
- Captura apresentada em 3/8/2026 mostra oferta individual do Jusbrasil de R$ 108,90/mês por 150 mensagens no plano Profissional e R$ 208,90/mês no Premium. A comparação deverá ser confirmada periodicamente e nunca usada em publicidade sem registrar data, condições e público da oferta.
- Hipótese a validar: grande parte dos advogados quer qualidade em uso ocasional e prefere um plano menor a pagar por capacidade ociosa.

## 3. SOS Consumidor+

### Problema

Consumidores endividados ou com problemas de consumo encontram informação fragmentada, técnica ou alarmista. Precisam de orientação didática, organização e próximos passos, mas não necessariamente de atendimento individual por advogado em todas as situações.

### Promessa

**Entenda seus direitos, organize sua vida financeira e saiba qual é o próximo passo.**

### MVP - R$ 29,90/mês

- fórum de IA com respostas mais amplas, histórico privado e perguntas de acompanhamento;
- respostas ilimitadas quando recuperadas do banco previamente aprovado;
- franquia mensal para respostas personalizadas por IA, com limite de tamanho e custo;
- organizador financeiro simples: receitas, despesas, dívidas, juros, vencimentos e metas;
- plano de saída das dívidas com prioridades e simulações, sem recomendar empréstimo ou investimento;
- cartilhas curtas sobre orçamento, renegociação, superendividamento, golpes, crédito e bets;
- biblioteca de vídeos curtos e objetivos;
- alertas de golpes, mudanças legais e assuntos que afetam o bolso;
- exportação de um resumo das dívidas e providências já tomadas.

### Fora do MVP

- movimentação bancária automática ou Open Finance;
- concessão, intermediação ou recomendação de crédito;
- promessa de redução de dívida ou indenização;
- consulta individual automática apresentada como parecer jurídico;
- contato automático com credores em nome do assinante.

### Histórias principais

- Como consumidor endividado, quero reunir minhas dívidas e vencimentos para enxergar minha situação sem planilhas complexas.
- Como consumidor com uma cobrança ou golpe, quero uma explicação simples e uma lista de providências para agir com segurança.
- Como assinante, quero continuar uma pergunta anterior sem repetir toda a história.
- Como pessoa em situação sensível, quero saber quando a IA não consegue responder e quando devo procurar Procon, Defensoria ou advogado.

## 4. SOS Pro para advogados

### Problema

Advogados de uso leve pagam por pacotes grandes e ferramentas extensas que pouco utilizam. Eles precisam de tarefas pontuais - resumir autos, estruturar argumentos, revisar uma minuta ou localizar fontes oficiais - com previsibilidade de custo.

### Posicionamento

**IA jurídica para quem quer pagar pelo que realmente usa.**

O MVP não tentará reproduzir o acervo de doutrina, livros e jurisprudência proprietária de concorrentes. O foco inicial será trabalhar bem com documentos enviados pelo advogado, legislação oficial, precedentes verificáveis e fluxos especializados.

### Planos de lançamento propostos

Os valores são hipóteses e só devem ser publicados após teste real de custo por tarefa.

| Plano | Preço | Uso mensal inicial | Documentos | Indicado para |
|---|---:|---:|---|---|
| Essencial | R$ 29,90 | 30 interações curtas | até 5 análises, 15 páginas cada | uso eventual |
| Prata | R$ 39,90 | 60 interações curtas | até 12 análises, 30 páginas cada | uso semanal |
| Ouro | R$ 49,90 | 100 interações curtas | até 20 análises, 50 páginas cada | uso frequente individual |

Uma interação longa, geração de peça ou análise de vários documentos consumirá mais de uma unidade. A interface deverá mostrar o custo antes da execução. Não oferecer uso ilimitado no lançamento.

### Recursos do MVP

- conversa jurídica com seleção de área e objetivo;
- resumo estruturado de documentos enviados;
- extração de fatos, pedidos, fundamentos e prazos aparentes;
- rascunhos e roteiros de peças, nunca marcados como prontos para protocolo sem revisão;
- verificação de citações e links para fontes oficiais quando disponíveis;
- aviso explícito quando uma fonte não puder ser confirmada;
- histórico por caso, com exclusão e prazo de retenção configurável;
- medidor de consumo e limite rígido do plano;
- bloqueio de treinamento com documentos de clientes.

### Requisitos de segurança P0

- criptografia em trânsito e em repouso para documentos;
- isolamento entre contas e entre casos;
- nenhum documento usado para treinar modelo sem autorização específica e separada;
- remoção automática de arquivos conforme política de retenção;
- logs de acesso e exclusão;
- proteção contra citação ou jurisprudência inventada;
- resposta recusada quando faltar base documental ou fonte confiável;
- termos que deixem claro o dever de revisão profissional pelo advogado;
- avaliação de LGPD, sigilo profissional e regras da OAB antes do beta externo.

## 5. Arquitetura e custo

- autenticação, cobrança, cotas e dados separados para consumidor e advogado;
- banco de respostas aprovado atende primeiro as dúvidas repetidas e reduz chamadas pagas;
- modelo local faz classificação, busca, anonimização e tarefas simples;
- modelo pago é usado somente quando a complexidade ou qualidade exigir;
- tarefas jurídicas longas usam fila, limite de páginas e estimativa de consumo;
- todo plano tem teto financeiro interno por usuário e por dia;
- ao atingir 70%, 90% e 100% da cota, o usuário recebe avisos claros;
- excedentes não são cobrados automaticamente no MVP.

Meta de margem inicial: custo variável de IA e processamento menor que 20% da receita líquida do plano. Se uma faixa ultrapassar a meta por dois meses, reduzir franquia, otimizar o roteamento ou reajustar o preço antes de escalar.

## 6. Métricas de validação

### Antes de construir tudo

- pelo menos 100 interessados em cada lista de espera;
- pelo menos 20 entrevistas curtas com consumidores e 20 com advogados;
- intenção de compra declarada de 15% ou mais ao preço exibido;
- 10 usuários pagantes em beta por produto antes de ampliar o escopo.

### Após o beta

- 60% dos assinantes completam a ação principal na primeira semana;
- 40% retornam em quatro semanas;
- menos de 3% das respostas jurídicas auditadas contêm erro material; meta obrigatória posterior: menos de 1%;
- zero citação inventada apresentada como confirmada;
- custo variável médio abaixo de 20% da receita líquida;
- cancelamentos e motivo registrados de forma estruturada.

## 7. Sequência recomendada

1. estabilizar o site, corrigir conteúdo de alto tráfego e testar a IA do fórum;
2. lançar página de interesse, sem cobrança, para medir demanda dos dois produtos;
3. testar SOS Consumidor+ com organizador manual e banco de respostas aprovado;
4. testar SOS Pro com 10 a 20 advogados convidados e tarefas limitadas;
5. medir custo real por tarefa durante 30 dias;
6. somente então confirmar preços, franquias e cobrança recorrente.

## 8. Questões ainda abertas

- Qual será a marca pública da área profissional?
- Quais áreas jurídicas entram no primeiro beta do SOS Pro? A recomendação é começar apenas com direito do consumidor e ampliar depois.
- Qual política de retenção de documentos os advogados aceitarão?
- A revisão humana do fundador será anunciada em todos os conteúdos ou apenas nos conteúdos editoriais selecionados?
- Qual meio de pagamento oferece melhor equilíbrio entre recorrência, Pix, chargeback e custo total?

## 9. Limite de pesquisa em concorrentes

Uma assinatura individual de concorrente pode ser utilizada para avaliação funcional e uso profissional legítimo, mas não para formar o acervo comercial do SOS Pro por extração em massa.

Nos termos do Jusbrasil modificados em 7 de abril de 2026:

- scraping, cópia, reprodução e exploração do conteúdo da plataforma dependem de consentimento prévio e expresso;
- o conteúdo gerado pelo Jus IA é licenciado para uso pessoal ou profissional da pessoa assinante e não pode ser comercializado ou distribuído a terceiros sem autorização expressa;
- obras de doutrina são licenciadas para busca, visualização, leitura e interação, permanecendo os direitos com autores e editoras.

Uso permitido recomendado para benchmarking:

- executar um conjunto limitado de perguntas próprias e casos fictícios;
- medir tempo, cobertura, facilidade de uso e qualidade aparente das fontes;
- conferir individualmente as citações em fontes oficiais;
- registrar somente métricas, funcionalidades observadas e conclusões próprias;
- não copiar respostas, trechos de livros, modelos, base de dados ou estrutura proprietária.

O banco do SOS Pro deverá ser formado por conteúdo próprio revisado, legislação oficial, dados públicos legitimamente acessíveis, decisões obtidas em fontes oficiais e documentos enviados pelos próprios usuários sob política de privacidade adequada.
