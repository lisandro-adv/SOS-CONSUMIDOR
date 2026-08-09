# Banco de respostas revisadas — Fórum SOS Consumidor

## Objetivo

Atender rapidamente as dúvidas recorrentes de pessoas comuns sem perder a fundamentação jurídica. O banco não será alimentado automaticamente por conversas de usuários.

## Fluxo

1. A pergunta passa pelos filtros de privacidade e de escopo do Direito do Consumidor.
2. O sistema identifica o tema e procura uma resposta aprovada ou pergunta equivalente.
3. Se houver correspondência segura, entrega a resposta revisada e as fontes oficiais.
4. Se não houver, a IA elabora uma resposta usando somente a base jurídica revisada.
5. Uma resposta nova só entra no banco depois de anonimização, validação jurídica e aprovação humana.

## Estados do conteúdo

- `draft`: gerado ou redigido, ainda não utilizável pelo público;
- `reviewed`: conferido, mas aguardando aprovação final;
- `approved`: disponível para recuperação;
- `retired`: retirado por desatualização ou substituição.

Cada resposta deve registrar tema, pergunta canônica, variações sintéticas, texto aprovado, fundamentos, links oficiais, versão, data da revisão e responsável pela aprovação.

## Uso de uma IA paga

A IA externa pode funcionar como professora na criação de variações, crítica e comparação de respostas. Ela não será fonte jurídica e não aprovará conteúdo sozinha. Apenas exemplos sintéticos ou previamente anonimizados poderão ser enviados. As condições do provedor sobre privacidade, retenção e uso dos resultados para treinamento deverão ser verificadas antes da integração.

## Evolução do Qwen

No início, o Qwen recebe a base revisada e exemplos aprovados no contexto. Quando houver um conjunto representativo e estável, ele poderá passar por ajuste fino offline. Nunca haverá aprendizado automático a partir de toda conversa recebida, evitando dados pessoais, envenenamento da base e propagação de respostas erradas.
