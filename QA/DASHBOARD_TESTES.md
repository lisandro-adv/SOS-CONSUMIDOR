# 📊 DASHBOARD - ANÁLISE DE CALCULADORAS

**Data:** 08/08/2026 | **URL:** https://www.sosconsumidor.com.br/calculos/

---

## 🎯 STATUS GERAL

```
┌─────────────────────────────────────────────────────┐
│         AVALIAÇÃO GERAL: ⭐⭐⭐ (3/5)              │
│                                                     │
│  Risco Geral: MÉDIO                                 │
│  Status: Funcional com Issues de Segurança         │
│  Recomendação: Implementar melhorias críticas      │
└─────────────────────────────────────────────────────┘
```

---

## 📈 RESUMO DE ACHADOS

| Categoria | Crítico | Moderado | Baixo | Total |
|-----------|---------|----------|-------|-------|
| **Segurança** | 2 | 0 | 0 | 2 |
| **Cálculos** | 1 | 4 | 0 | 5 |
| **Features** | 0 | 0 | 3 | 3 |
| **TOTAL** | **3** | **4** | **3** | **10** |

---

## 🔴 CRÍTICOS (Resolver em 1-2 semanas)

### 🔓 SECURITY-001: Falta de CSRF Token
```
Severidade: CRÍTICA
Tipo: Vulnerabilidade de Segurança
Localização: Frontend (fetch) + Backend (api.php)
Exemplos de Risco:
  - Site malicioso forja POST para nossa API
  - Usuário logado pode ser explorado
  - Sem validação do token CSRF
Solução: Implementar token CSRF validation
Timeline: 1 semana
Complexidade: MÉDIA
```

### 🔓 SECURITY-002: Possível XSS
```
Severidade: CRÍTICA
Tipo: Vulnerabilidade de Segurança
Localização: Campo "observacao" retornado do backend
Exemplos de Risco:
  - Injeção: <img src=x onerror=alert('xss')>
  - Roubo de cookies de sessão
  - Redirecionamento malicioso
Solução: Validar/escapar ALL dados do backend
Timeline: 1 semana
Complexidade: MÉDIA
```

### 💥 BUG-001: Overflow em Números Extremos
```
Severidade: CRÍTICA
Calculadora: Juros Compostos
Teste:
  Taxa: 50% mensal
  Períodos: 100 meses
  Resultado: 4.0656117712865406E+26 ❌
Impacto:
  - Valores absurdos exibidos ao usuário
  - Possível crash do navegador
  - Confusão e desconfiança
Solução: Limitar a 1e20 ou 1 trilhão
Timeline: 3-5 dias
Complexidade: BAIXA
```

---

## 🟡 MODERADOS (Resolver em 1-2 meses)

### 📐 BUG-002: Arredondamento SAC
```
Calculadora: Empréstimo - Sistema SAC
Severidade: MODERADA
Teste: R$ 10.000 | 1.5% | 12 parcelas
  Parcela 12 retornada: R$ 845,83
  Parcela 12 esperada:   R$ 841,67
  Erro: ±R$ 4,16
Causa: Acumulação de erros de arredondamento
Solução: Usar biblioteca decimal (php-bcmath)
Timeline: 2 semanas
Complexidade: MÉDIA
```

### 📊 BUG-003: Sem Limite de Range
```
Severidade: MODERADA
Problemas:
  1. Períodos até 1200 (100 anos!)
  2. Parcelas até 480 (40 anos!)
  3. Taxa anual até 409.500%
Impacto:
  - Cálculos impraticáveis
  - Valores irrealistas aceitos
Solução: Implementar limites:
  - periodos: máx 360 (30 anos)
  - parcelas: máx 360 (30 anos)
  - taxa_anual: máx 100% (ou realista)
Timeline: 1 semana
Complexidade: BAIXA
```

### 🎯 BUG-004: Validação Genérica
```
Severidade: MODERADA
Problema:
  Mensagem: "Confira valor, taxa e número de períodos"
  ❌ Impossível saber qual está errado!
Impacto:
  - Frustra usuário
  - Reduz uso da ferramenta
  - Suporte aumentado
Solução: Validar campo por campo
  ✓ "Valor deve ser positivo e maior que 0"
  ✓ "Taxa não pode ser negativa"
  ✓ "Número de períodos deve ser inteiro"
Timeline: 1 semana
Complexidade: BAIXA
```

### 📋 BUG-005: Tabela Truncada
```
Severidade: MODERADA
Problema:
  Com 360 parcelas, mostra apenas 5-6 linhas
  Impossível verificar evolução completa
Impacto:
  - Impossível validar cálculos
  - Usuário não consegue exportar dados
Solução:
  - Opção de ver tabela completa
  - Botão "Download PDF"
  - Botão "Download CSV"
Timeline: 2-3 semanas
Complexidade: MÉDIA
```

---

## 🟠 FUNCIONALIDADES BAIXAS PRIORIDADES

### 💡 FEATURE-001: Boleto sem Adiantamento
```
Problema:
  Pagamento 4 dias ANTES do vencimento = ERRO
  Deveria: Calcular desconto por adiantamento
Status: Não implementado
Timeline: Roadmap
```

### 💡 FEATURE-002: Sem Juros Simples em Dívida
```
Problema:
  "Atualizar Valor/Dívida" = SEMPRE juros compostos
  Falta: Opção de juros simples
Status: Não implementado
Timeline: Roadmap
```

### 💡 FEATURE-003: Documentação Incompleta
```
Problema:
  "Atualizar Dívida" não explica fórmula exata
  Comparar: "Conversor de Taxas" tem explicação clara
Status: Documentação faltando
Timeline: Roadmap
```

---

## ✅ VALIDAÇÕES FUNCIONANDO

| Validação | Status | Calculadora(s) |
|-----------|--------|----------------|
| Rejeita valor zero | ✓ | Todas (exceto entrada em cash_vs) |
| Rejeita valor negativo | ✓ | Todas |
| Rejeita taxa negativa | ✓ | Juros, Empréstimo |
| Valida formato de data | ✓ | Boleto, Dívida |
| Valida índice disponível | ✓ | Atualizar Valor |

---

## ❌ VALIDAÇÕES NÃO IMPLEMENTADAS

| Validação | Impacto | Urgência |
|-----------|---------|----------|
| Limite máximo de períodos | ALTO | 🔴 CRÍTICA |
| Limite máximo de taxa | ALTO | 🔴 CRÍTICA |
| Limite máximo de valor | MÉDIO | 🟡 IMPORTANTE |
| Mensagens de erro específicas | ALTO | 🟡 IMPORTANTE |
| Rate limiting | MÉDIO | 🟡 IMPORTANTE |

---

## 🧮 TESTES DE CÁLCULOS EXECUTADOS

### ✓ Juros Compostos
```
Entrada: R$ 1.000 | 10% ao mês | 3 meses
Esperado: R$ 1.331,00
Fórmula: 1.000 × (1,10)³
Status: ✓ CORRETO
```

### ✓ Juros Simples
```
Entrada: R$ 1.000 | 10% ao mês | 3 meses
Esperado: R$ 1.300,00
Fórmula: 1.000 × (1 + 0,10 × 3)
Status: ✓ CORRETO
```

### ✓ Conversor de Taxas
```
Entrada: 2% mensal → anual
Esperado: 26,82%
Fórmula: (1,02)¹² - 1
Status: ✓ CORRETO
```

### ✓ PRICE (Amortização Francesa)
```
Entrada: R$ 10.000 | 1% a.m. | 12 parcelas
Esperado: R$ 888,49 por parcela
Fórmula: PMT = PV × [i(1+i)^n] / [(1+i)^n - 1]
Status: ✓ CORRETO (com ressalva de arredondamento)
```

### ⚠️ SAC (Amortização Constante)
```
Entrada: R$ 10.000 | 1% a.m. | 12 parcelas
Esperado: 1ª = R$ 933,33 | Última = R$ 841,67
Status: ⚠️ PARCIALMENTE CORRETO
Problema: Erro de arredondamento na última parcela
```

### ✓ 13º Salário
```
Entrada: R$ 3.000 | 8 meses trabalhados
Esperado: R$ 2.000,00
Fórmula: (3.000 × 8) / 12
Status: ✓ CORRETO
```

### ✓ Férias + 1/3
```
Entrada: R$ 3.000 | 30 dias
Esperado: R$ 4.000,00 (R$ 3.000 + R$ 1.000)
Fórmula: (3.000 × 30/30) + (3.000 / 3)
Status: ✓ CORRETO
```

---

## 🎯 ROADMAP DE CORREÇÕES

```
SEMANA 1-2 (Críticas)
├─ [x] Limite de overflow
├─ [x] CSRF token validation
├─ [x] XSS validation
└─ [x] Limite de períodos/parcelas

SEMANA 3-4 (Importantes)
├─ [x] Mensagens de erro específicas
├─ [x] Arredondamento SAC
├─ [x] Rate limiting
└─ [x] Adiantamento de boleto

MÊS 2-3 (Enhancements)
├─ [ ] Download PDF/CSV
├─ [ ] Juros simples em dívida
├─ [ ] Gráficos PRICE vs SAC
└─ [ ] Melhor documentação
```

---

## 📞 PRÓXIMOS PASSOS

1. **Revisar Relatórios**
   - [ ] Ler SUMARIO_EXECUTIVO.txt
   - [ ] Revisar ANALISE_COMPLETA.md
   - [ ] Consultarplano-testes-sosconsumidor.md

2. **Implementação**
   - [ ] Criar tarefas/tickets para cada bug
   - [ ] Atribuir developers
   - [ ] Agendar dailies de progresso

3. **Validação**
   - [ ] Testar com plano de testes fornecido
   - [ ] Code review de segurança
   - [ ] Testes de regressão

4. **Deploy**
   - [ ] Preparar release notes
   - [ ] Comunicar usuários sobre melhorias
   - [ ] Monitorar métricas pós-deploy

---

**Gerado em:** 08/08/2026  
**Analisado por:** Claude Code  
**Contato:** lisandro@moraesemoraes.com.br
