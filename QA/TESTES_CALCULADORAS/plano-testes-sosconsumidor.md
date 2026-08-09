# Plano de Testes - SOS Consumidor Calculadoras
**URL:** https://www.sosconsumidor.com.br/calculos/
**Data:** 2026-08-08

---

## 📋 TESTES DE CÁLCULOS MATEMÁTICOS

### ✅ TESTE 1: Juros Simples
**Calculadora:** Juros Simples/Compostos

| Campo | Valor |
|-------|-------|
| Capital Inicial | R$ 1.000,00 |
| Taxa de Juros | 10% ao mês |
| Período | 3 meses |
| Tipo | **Juros Simples** |

**Fórmula:** J = C × i × n
**Cálculo:** J = 1.000 × 0,10 × 3 = 300
**Montante Esperado:** R$ 1.300,00

**Resultado Obtido:** _______________________
**Erro encontrado:** □ Sim □ Não
**Observações:** _____________________________

---

### ✅ TESTE 2: Juros Compostos
**Calculadora:** Juros Simples/Compostos

| Campo | Valor |
|-------|-------|
| Capital Inicial | R$ 1.000,00 |
| Taxa de Juros | 10% ao mês |
| Período | 3 meses |
| Tipo | **Juros Compostos** |

**Fórmula:** M = C × (1 + i)^n
**Cálculo:** M = 1.000 × (1,10)³ = 1.000 × 1,331 = 1.331
**Montante Esperado:** R$ 1.331,00

**Resultado Obtido:** _______________________
**Erro encontrado:** □ Sim □ Não
**Observações:** _____________________________

---

### ✅ TESTE 3: Conversor de Juros - Mensal para Anual
**Calculadora:** Conversor Juros Mensais/Anuais

| Campo | Valor |
|-------|-------|
| Taxa Mensal | 2% |
| Taxa Anual | (deixar em branco - calcular) |

**Fórmula:** i_anual = (1 + i_mensal)^12 - 1
**Cálculo:** (1,02)^12 - 1 = 1,26824 - 1 = 0,26824 = **26,824%**
**Taxa Anual Esperada:** 26,82% (ou 26,824%)

**Resultado Obtido:** _______________________
**Erro encontrado:** □ Sim □ Não
**Observações:** _____________________________

---

### ✅ TESTE 4: Conversor de Juros - Anual para Mensal
**Calculadora:** Conversor Juros Mensais/Anuais

| Campo | Valor |
|-------|-------|
| Taxa Anual | 26,82% |
| Taxa Mensal | (deixar em branco - calcular) |

**Fórmula:** i_mensal = (1 + i_anual)^(1/12) - 1
**Cálculo:** (1,2682)^(1/12) - 1 ≈ 0,02 = **2%**
**Taxa Mensal Esperada:** 2,00% (ou muito próximo)

**Resultado Obtido:** _______________________
**Erro encontrado:** □ Sim □ Não
**Observações:** _____________________________

---

### ✅ TESTE 5: Empréstimo PRICE (Amortização Francesa)
**Calculadora:** Empréstimo/Financiamento

| Campo | Valor |
|-------|-------|
| Valor Financiado | R$ 10.000,00 |
| Taxa Mensal | 1% |
| Número de Parcelas | 12 |
| Sistema | **PRICE** |

**Fórmula:** PMT = P × [i(1+i)^n] / [(1+i)^n - 1]

**Cálculo Detalhado:**
```
P = 10.000
i = 0,01
n = 12

Numerador = 0,01 × (1,01)^12 = 0,01 × 1,12682503 = 0,0112682503
Denominador = (1,01)^12 - 1 = 1,12682503 - 1 = 0,12682503

PMT = 10.000 × (0,0112682503 / 0,12682503)
PMT = 10.000 × 0,08884901
PMT ≈ R$ 888,49
```

**Valor da Parcela Esperado:** R$ 888,49

**Resultado Obtido (1ª Parcela):** _______________________
**Resultado Obtido (Última Parcela):** _______________________
**Total de Juros Esperado:** ~R$ 661,88 (12 × 888,49 - 10.000)
**Total de Juros Obtido:** _______________________
**Erro encontrado:** □ Sim □ Não
**Observações:** _____________________________

---

### ✅ TESTE 6: Empréstimo SAC (Amortização Constante)
**Calculadora:** Empréstimo/Financiamento

| Campo | Valor |
|-------|-------|
| Valor Financiado | R$ 10.000,00 |
| Taxa Mensal | 1% |
| Número de Parcelas | 12 |
| Sistema | **SAC** |

**Lógica SAC:**
- Amortização mensal (fixa) = 10.000 / 12 = R$ 833,33
- Juros cada mês = Saldo devedor × taxa
- Parcela = Amortização + Juros (DECRESCENTE)

**Valores Esperados:**
```
Mês 1: Saldo = 10.000 | Juros = 100 | Parcela = 933,33
Mês 2: Saldo = 9.166,67 | Juros = 91,67 | Parcela = 925,00
Mês 3: Saldo = 8.333,33 | Juros = 83,33 | Parcela = 916,67
...
Mês 12: Saldo = 833,33 | Juros = 8,33 | Parcela = 841,67
```

**Primeira Parcela Esperada:** R$ 933,33
**Última Parcela Esperada:** R$ 841,67

**Resultado Obtido (1ª Parcela):** _______________________
**Resultado Obtido (Última Parcela):** _______________________
**Total de Juros Esperado:** ~R$ 550,00
**Total de Juros Obtido:** _______________________
**Parcelas são decrescentes?** □ Sim □ Não
**Erro encontrado:** □ Sim □ Não
**Observações:** _____________________________

---

### ✅ TESTE 7: Boleto Vencido (Juros Simples)
**Calculadora:** Boleto Vencido

| Campo | Valor |
|-------|-------|
| Valor Original | R$ 100,00 |
| Percentual de Multa | 2% |
| Data de Vencimento | 01/01/2026 |
| Data de Pagamento | 05/01/2026 |
| Taxa de Juros | 0,1% ao dia |

**Cálculo:**
```
Dias de atraso = 4 dias (01 a 05 janeiro)
Multa = 100 × 0,02 = R$ 2,00
Juros = 100 × 0,001 × 4 = R$ 0,40
Total = 100 + 2 + 0,40 = R$ 102,40
```

**Total Esperado:** R$ 102,40
- Valor Original: R$ 100,00
- Multa: R$ 2,00
- Juros: R$ 0,40

**Resultado Obtido (Total):** _______________________
**Discriminação - Multa:** _______________________
**Discriminação - Juros:** _______________________
**Erro encontrado:** □ Sim □ Não
**Observações:** _____________________________

---

### ✅ TESTE 8: Compra À Vista vs Parcelada
**Calculadora:** Compra À Vista vs Parcelada

| Campo | Valor |
|-------|-------|
| Preço à Vista | R$ 1.000,00 |
| Valor da Parcela | R$ 200,00 |
| Número de Parcelas | 5 |
| Taxa de Comparação | 2% ao mês |

**Cálculo Esperado:**
```
Custo parcelado = 200 × 5 = R$ 1.000
Diferença = 0 (mesmo preço à vista)
Taxa implícita = 0%
```

**Teste 2 - Com Entrada:**

| Campo | Valor |
|-------|-------|
| Preço à Vista | R$ 1.000,00 |
| Entrada | R$ 300,00 |
| Valor da Parcela | R$ 180,00 |
| Número de Parcelas | 4 |

**Cálculo Esperado:**
```
Custo parcelado = 300 + (180 × 4) = 300 + 720 = R$ 1.020
Diferença = 1.020 - 1.000 = R$ 20
Taxa adicional = 2%
```

**Resultado Obtido (Teste 1):** _______________________
**Resultado Obtido (Teste 2):** _______________________
**Erro encontrado:** □ Sim □ Não
**Observações:** _____________________________

---

### ✅ TESTE 9: 13º Salário Proporcional
**Calculadora:** 13º Salário

| Campo | Valor |
|-------|-------|
| Salário Bruto Mensal | R$ 3.000,00 |
| Meses Trabalhados | 8 |

**Fórmula:** 13º = (Salário × Meses) / 12
**Cálculo:** (3.000 × 8) / 12 = 24.000 / 12 = R$ 2.000,00

**13º Esperado:** R$ 2.000,00

**Resultado Obtido:** _______________________
**Erro encontrado:** □ Sim □ Não
**Observações:** _____________________________

---

### ✅ TESTE 10: Férias + 1/3
**Calculadora:** Férias + 1/3

| Campo | Valor |
|-------|-------|
| Salário Bruto Mensal | R$ 3.000,00 |
| Dias de Férias | 30 |

**Fórmula:**
```
Valor diário = Salário / 30
Remuneração férias = Valor diário × Dias
Acréscimo 1/3 = Remuneração × (1/3)
Total = Remuneração + Acréscimo
```

**Cálculo:**
```
Valor diário = 3.000 / 30 = R$ 100
Remuneração (30 dias) = 100 × 30 = R$ 3.000
Acréscimo 1/3 = 3.000 × 0,3333 = R$ 1.000
Total = R$ 4.000
```

**Total Esperado:** R$ 4.000,00

**Resultado Obtido:** _______________________
**Erro encontrado:** □ Sim □ Não
**Observações:** _____________________________

---

## 🔍 TESTES DE VALIDAÇÃO E UX

### ✅ TESTE 11: Validação de Campos Vazios
**Procedimento:**
1. Abra qualquer calculadora
2. Tente enviar o formulário **sem preencher nenhum campo**
3. Observe a resposta

**Comportamento Esperado:** Mensagem de erro clara indicando quais campos são obrigatórios

**Resultado Obtido:** 
□ Exibe erro claro
□ Não valida (permite cálculo com dados vazios)
□ Comportamento confuso
□ Outro: _______________________

**Observações:** _____________________________

---

### ✅ TESTE 12: Valores Negativos
**Procedimento:**
1. Abra calculadora de Juros
2. Digite Capital = **-1000**
3. Tente calcular

**Comportamento Esperado:** Rejeita ou exibe aviso

**Resultado Obtido:** 
□ Rejeita com erro
□ Aceita e calcula (ERRADO)
□ Outro: _______________________

**Observações:** _____________________________

---

### ✅ TESTE 13: Valores Muito Grandes
**Procedimento:**
1. Capital: **999.999.999.999**
2. Taxa: **50%**
3. Período: **100** meses

**Comportamento Esperado:** Calcula corretamente ou exibe aviso de overflow

**Resultado Obtido:** _______________________
**Observações:** _____________________________

---

### ✅ TESTE 14: Formato de Entrada (Separadores)
**Procedimento:**
1. Tente inserir: **1.000,00** (com ponto e vírgula)
2. Tente inserir: **1000.00** (formato US)
3. Observe qual é aceito

**Formato Aceito:**
□ 1.000,00 (PT-BR)
□ 1000,00
□ 1000.00 (US)
□ Outro: _______________________

**Observações:** _____________________________

---

### ✅ TESTE 15: Responsividade (Mobile)
**Procedimento:**
1. Abra https://www.sosconsumidor.com.br/calculos/ em um smartphone
2. Tente preencher uma calculadora

**Resultado:**
□ Layout se adapta bem
□ Campos ficam muito pequenos
□ Botões difíceis de clicar
□ Páginas scrollam muito
□ Outro: _______________________

**Observações:** _____________________________

---

## ⚠️ TESTES DE CASOS EXTREMOS

### ✅ TESTE 16: IPCA/INPC Atualizado
**Calculadora:** Atualizar Valor ou Dívida

**Procedimento:**
1. Selecione **IPCA**
2. Escolha **data inicial: 01/2024** e **data final: 12/2024**
3. Valor: R$ 1.000
4. Verifique o resultado

**Verificação:**
- Qual é a data de atualização dos índices? (indicado na página?)
- IPCA acumulado 2024 foi **4,5%** (valor oficial)
- Valor esperado aproximado: R$ 1.045,00

**Resultado Obtido:** _______________________
**Data dos Índices Indicada:** _______________________
**Índice está atualizado?** □ Sim □ Não □ Indefinido
**Observações:** _____________________________

---

### ✅ TESTE 17: Datas Inválidas
**Procedimento:**
1. Na calculadora de Boleto Vencido
2. Coloque Data Vencimento = **32/01/2026** (dia inválido)
3. Observe o comportamento

**Resultado:**
□ Rejeita com erro
□ Aceita e calcula (ERRADO)
□ Corrige automaticamente
□ Outro: _______________________

**Observações:** _____________________________

---

## 📊 RESUMO DE ERROS ENCONTRADOS

| # | Calculadora | Tipo de Erro | Severidade | Descrição | Passos para Reproduzir |
|---|-------------|--------------|-----------|-----------|----------------------|
| 1 | | | | | |
| 2 | | | | | |
| 3 | | | | | |
| 4 | | | | | |
| 5 | | | | | |

---

## 🎯 RECOMENDAÇÕES FINAIS

**Crítico:**
- [ ] ___________________________________
- [ ] ___________________________________

**Alto:**
- [ ] ___________________________________
- [ ] ___________________________________

**Médio:**
- [ ] ___________________________________
- [ ] ___________________________________

---

**Tester:** _______________________  
**Data:** _______________________  
**Email para reportar:** lisandro@moraesemoraes.com.br
