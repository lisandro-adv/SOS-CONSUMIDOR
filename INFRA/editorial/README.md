# Rotina editorial diária

Esta rotina pesquisa pautas e cria no máximo um rascunho original por dia. Ela nunca publica diretamente no site.

O fluxo possui duas etapas:

1. sem `--generate`, coleta metadados de RSS, pontua a relevância e grava apenas o pacote de pesquisa;
2. com `--generate`, faz uma única chamada ao Gemini, valida tamanho, linguagem, fontes, promessas e possível cópia textual e deixa o resultado como `awaiting_human_review` ou `rejected_by_validator`.

Somente um processo separado, após aprovação humana, poderá importar um rascunho aprovado para o banco do site com `ativo=0`. A publicação continuará sendo uma ação editorial explícita.

O padrão é de 350 a 650 palavras, com teto de 700, linguagem cotidiana, no máximo três seções e fontes consultadas. O título pode ser forte, mas não pode prometer resultado. Pautas permanentes incluem direitos do consumidor, endividamento, crédito e juros, impostos do cotidiano, inflação e aumentos de preços, custo de vida, golpes e fraudes, bets e seus efeitos. Notícias econômicas só entram quando houver impacto concreto no bolso ou na rotina do cidadão.

Exemplo de pesquisa sem custo de IA:

```bash
python3 daily_article.py --queue ./queue
```

A geração exige uma chave nova, exclusiva e limitada:

```bash
GEMINI_API_KEY=... python3 daily_article.py --queue ./queue --generate
```
