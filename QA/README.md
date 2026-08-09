# QA - SOS CONSUMIDOR

Pasta centralizada para testes, análises e relatórios de qualidade.

## 📁 Estrutura

```
QA/
├── README.md (este arquivo)
└── TESTES_CALCULADORAS/
    ├── SUMARIO_EXECUTIVO.txt      (resumo executivo 2 páginas)
    ├── ANALISE_COMPLETA.md        (análise técnica detalhada)
    └── plano-testes-sosconsumidor.md (plano de testes manual)
```

## 📊 Status das Calculadoras

| # | Calculadora | Status | Observação |
|----|------------|--------|-----------|
| 1 | Boleto Vencido | ✓ Funcional | Sem suporte a adiantamento |
| 2 | Atualizar Valor/Dívida | ✓ Funcional | Sem opção de juros simples |
| 3 | Empréstimo PRICE | ✓ Funcional | Possível erro de arredondamento |
| 4 | Empréstimo SAC | ✓ Funcional | Acumula erro de arredondamento |
| 5 | Compra À Vista vs Parcelada | ✓ Funcional | OK |
| 6 | Conversor de Taxas | ✓ Funcional | Melhor documentada |
| 7 | Juros Simples/Compostos | ✓ Funcional | OK |
| 8 | 13º Salário | ✓ Funcional | Sem deduções (INSS, IRPF) |

---

## 🔴 Problemas Críticos Encontrados

### 1. **OVERFLOW em Números Extremos** [BUG-001]
- **Calculadora:** Juros Compostos
- **Problema:** Taxa 50% × 100 períodos = 4.0E+26 (sem limite)
- **Impacto:** Valores absurdos, possível crash
- **Solução:** Implementar limite máximo (ex: 1e20)

### 2. **Falta de Proteção CSRF** [SECURITY-001]
- **Problema:** POST sem validação CSRF token
- **Impacto:** Requisições forjadas possível
- **Solução:** Implementar CSRF token validation

### 3. **Possível XSS** [SECURITY-002]
- **Problema:** Campo `observacao` pode ser explorado se backend não escapar
- **Impacto:** Injeção de código malicioso
- **Solução:** Validar/escapar ALL dados do backend

---

## 🟡 Problemas Moderados

| ID | Problema | Calculadora | Severidade | Timeline |
|----|----------|-------------|-----------|----------|
| BUG-002 | Arredondamento SAC | Empréstimo | MÉDIA | 1-2 sem |
| BUG-003 | Sem limite de períodos | Juros | MÉDIA | 1-2 sem |
| BUG-004 | Erros genéricos | Todas | MÉDIA | 1-2 sem |
| BUG-005 | Tabela truncada | PRICE/SAC | MÉDIA | 1-2 sem |

---

## 📋 Arquivos de Referência

### 1. **SUMARIO_EXECUTIVO.txt** (10 KB)
- Resumo de 2 páginas
- 3 bugs críticos com exemplos
- Validações encontradas
- Recomendações priorizadas
- **Tempo de leitura:** 5-10 min

### 2. **ANALISE_COMPLETA.md** (18 KB)
- Arquitetura técnica completa
- Código JavaScript extraído
- Descrição de cada calculadora
- 8 problemas documentados
- Testes realizados
- **Tempo de leitura:** 30 min

### 3. **plano-testes-sosconsumidor.md** (11 KB)
- 17 testes estruturados
- Valores de teste com resultados esperados
- Checklist para testes manuais
- Fórmulas matemáticas detalhadas
- **Tempo de execução:** 1-2 horas (manual)

---

## 🎯 Recomendações por Prioridade

### 🔥 CRÍTICAS (1-2 semanas)
1. [ ] Implementar limite máximo de overflow (1e20)
2. [ ] Adicionar proteção CSRF token validation
3. [ ] Validar/escapar ALL dados do backend no frontend
4. [ ] Testar cenários XSS

### 📋 IMPORTANTES (1 mês)
5. [ ] Melhorar mensagens de erro (especificar campo)
6. [ ] Limitar períodos ≤ 1200 e parcelas ≤ 480
7. [ ] Resolver arredondamento SAC (usar decimal library)
8. [ ] Permitir pagamento adiantado de boleto
9. [ ] Implementar rate limiting (100 req/min por IP)

### 🎨 ENHANCEMENTS (Roadmap)
10. [ ] Mostrar tabela completa de amortização (ou download PDF)
11. [ ] Adicionar juros simples em "Atualizar Dívida"
12. [ ] Gráficos de comparação PRICE vs SAC
13. [ ] Melhorar documentação de fórmulas
14. [ ] Integração com dados em tempo real do BCB

---

## 📈 Qualidade Geral

**Avaliação:** ⭐⭐⭐ (3 de 5 estrelas)

### Pontos Fortes
✓ Arquitetura limpa (cliente-servidor)  
✓ Cálculos matematicamente corretos (maioria)  
✓ Interface amigável  
✓ Dados de índices atualizados do BCB  
✓ Suporte a CSS responsivo  

### Pontos Fracos
✗ Segurança: Sem CSRF, sem rate limiting  
✗ Robustez: Sem limite de overflow  
✗ Validação: Genérica demais  
✗ Arredondamento: Problemas em SAC  
✗ Documentação: Incompleta em algumas calculadoras  

### Risco Geral: **MÉDIO**
- Sem risco crítico de perda de dados
- Risco de segurança (CSRF, possível XSS)
- Risco de usabilidade (erros genéricos)

---

## 🔍 Como Usar Este Relatório

### Para Desenvolvedores:
1. Leia `SUMARIO_EXECUTIVO.txt` para visão geral (5 min)
2. Consulte `ANALISE_COMPLETA.md` para detalhes técnicos (30 min)
3. Use `plano-testes-sosconsumidor.md` para validar correções

### Para Product Managers:
1. Leia `SUMARIO_EXECUTIVO.txt` (5 min)
2. Use a tabela de prioridades acima para roadmap
3. Aloque timeline: 1-2 semanas para críticos

### Para QA/Testers:
1. Imprima/baixe `plano-testes-sosconsumidor.md`
2. Execute cada teste manualmente seguindo o guia
3. Documente resultados no formulário incluído

---

## 📞 Contato

- **Email:** lisandro@moraesemoraes.com.br
- **Data da Análise:** 2026-08-08
- **URL Analisada:** https://www.sosconsumidor.com.br/calculos/

---

*Última atualização: 2026-08-08*
