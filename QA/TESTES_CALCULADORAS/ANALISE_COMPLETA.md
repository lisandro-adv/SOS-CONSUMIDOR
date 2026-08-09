# ANÁLISE COMPLETA - CALCULADORAS SOS CONSUMIDOR

## 1. ARQUITETURA GERAL

A página usa uma arquitetura cliente-servidor:
- **Frontend**: HTML + JavaScript vanilla (sem frameworks)
- **Backend**: PHP (arquivo `/calculos/api.php`)
- Comunicação via JSON POST/GET

### Arquivos Identificados:
- HTML: https://www.sosconsumidor.com.br/calculos/
- API: https://www.sosconsumidor.com.br/calculos/api.php
- JS externo: https://www.sosconsumidor.com.br/assets/js/app.js (jQuery minificado)

---

## 2. CÓDIGO JAVASCRIPT FRONTEND (INLINE - EXTRAÍDO)

```javascript
// Funções de formatação
const brl = value => Number(value || 0).toLocaleString('pt-BR',{style:'currency',currency:'BRL'});
const pct = value => Number(value || 0).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:4})+'%';
const number = value => { 
  let s=String(value??'').trim().replace(/[^0-9,.-]/g,''); 
  if(s.includes(',')&&s.includes('.')) s=s.replace(/\./g,''); 
  s=s.replace(',','.'); 
  return s===''?null:Number(s); 
};
const esc = value => { 
  const n=document.createElement('div'); 
  n.textContent=String(value??''); 
  return n.innerHTML; 
};

// Event listeners para seleção de calculadora
document.querySelectorAll('.calc-choice').forEach(choice=>choice.addEventListener('click',()=>{
  document.querySelectorAll('.calc-choice').forEach(x=>x.classList.remove('active'));
  document.querySelectorAll('.calc-panel').forEach(x=>x.classList.remove('active'));
  choice.classList.add('active');
  document.getElementById('panel-'+choice.dataset.panel).classList.add('active');
}));

// Event listeners para inputs com máscara de moeda
document.querySelectorAll('input[inputmode="decimal"]').forEach(input=>{
    const percent = /%/.test(input.getAttribute('placeholder')||'');
    const shell = document.createElement('span'); shell.className='mask-shell';
    const affix = document.createElement('span'); affix.className='mask-affix';
    const suffix = document.createElement('span'); suffix.className='mask-suffix';
    if(percent){ suffix.textContent='%'; } else { affix.textContent='R$'; }
    input.parentNode.insertBefore(shell,input); 
    shell.appendChild(affix); 
    shell.appendChild(input); 
    shell.appendChild(suffix);
    if(!input.value) input.value='0,00';
    input.addEventListener('focus',()=>input.select());
    input.addEventListener('input',()=>{
      input.value=input.value.replace(/[^0-9,]/g,'');
      const i=input.value.indexOf(',');
      if(i>=0)input.value=input.value.slice(0,i+1)+input.value.slice(i+1).replace(/,/g,'');
    });
    input.addEventListener('blur',()=>{if(input.value==='') input.value='0,00';});
});

// Função para exibir resultado
function showResult(result, html, error=false){
  result.className='result show'+(error?' error':'');
  result.innerHTML=html;
  result.scrollIntoView({behavior:'smooth',block:'nearest'});
}

// Função para renderizar métricas
function metrics(items){
  return '<div class="metrics">'+items.map(x=>`<div class="metric"><small>${esc(x[0])}</small><strong>${x[1]}</strong></div>`).join('')+'</div>';
}

// Função para renderizar tabela de amortização
function memory(rows){
  if(!rows||!rows.length)return '';
  return '<table class="memory"><thead><tr><th>Parcela</th><th>Valor</th><th>Amortização</th><th>Juros</th><th>Saldo</th></tr></thead><tbody>'+
    rows.map(x=>`<tr><td>${x.parcela}</td><td>${brl(x.valor)}</td><td>${brl(x.amortizacao)}</td><td>${brl(x.juros)}</td><td>${brl(x.saldo)}</td></tr>`).join('')+
    '</tbody></table><p class="note">A tabela mostra as primeiras parcelas e a última parcela.</p>';
}

// Função para renderizar resultado baseado no tipo
function render(data){
  if(data.type==='update_value')
    return `<h3>Valor atualizado</h3>${metrics([['Valor original',brl(data.valor_original)],['Valor atualizado',brl(data.valor_atualizado)],['Variação do índice',pct(data.variacao_indice)]])}<p>Índice <strong>${esc(data.indice)}</strong>, período <strong>${esc(data.periodo)}</strong>, em ${data.meses} mês(es). Fator aplicado: <strong>${data.fator}</strong>.</p><p class="note">Fonte: ${esc(data.fonte)}. O resultado é uma estimativa matemática e não define, sozinho, o valor jurídico da dívida.</p>`;
  
  if(data.type==='boleto')
    return `<h3>Boleto recalculado</h3>${metrics([['Dias em atraso',data.dias_atraso],['Multa',brl(data.multa)],['Juros',brl(data.juros)],['Total',brl(data.total)]])}<p class="note">${esc(data.observacao)}</p>`;
  
  if(data.type==='interest')
    return `<h3>Resultado dos juros ${esc(data.modo)}</h3>${metrics([['Valor inicial',brl(data.principal)],['Juros',brl(data.juros)],['Montante',brl(data.montante)]])}<p>Taxa: <strong>${pct(data.taxa)}</strong> por período · ${data.periodos} período(s).</p>`;
  
  if(data.type==='rate_convert')
    return `<h3>Conversão com juros compostos</h3>${metrics([['Taxa mensal equivalente',pct(data.mensal)],['Taxa anual equivalente',pct(data.anual)]])}<p>${esc(data.explicacao)}</p><div class="formula"><strong>${esc(data.formula)}</strong><br><br>12% ao mês aplicado por 12 meses resulta em aproximadamente <strong>289,60% ao ano</strong>. Os 144% são apenas o resultado de multiplicar 12 por 12, sem considerar a capitalização.</div>`;
  
  if(data.type==='thirteenth')
    return `<h3>Estimativa do 13º salário</h3>${metrics([['13º bruto',brl(data.bruto)],['1ª parcela estimada',brl(data.primeira_parcela)],['2ª parcela bruta',brl(data.segunda_parcela_bruta)]])}<p>${data.meses} mês(es) contabilizado(s) sobre salário de ${brl(data.salario)}.</p><p class="note">${esc(data.observacao)}</p>`;
  
  if(data.type==='vacation')
    return `<h3>Estimativa de férias</h3>${metrics([['Remuneração das férias',brl(data.remuneracao)],['1/3 constitucional',brl(data.terco_constitucional)],['Total bruto',brl(data.total_bruto)]])}<p>${data.dias} dia(s) de férias sobre salário de ${brl(data.salario)}.</p><p class="note">${esc(data.observacao)}</p>`;
  
  if(data.type==='loan')
    return `<h3>Simulação ${esc(data.sistema)}</h3>${metrics([['Primeira parcela',brl(data.primeira_parcela)],['Total pago',brl(data.total)],['Juros totais',brl(data.juros_total)]])}${memory(data.schedule)}<p class="note">Simulação matemática: não inclui tarifas, seguros, impostos ou outros componentes do CET.</p>`;
  
  return `<h3>Comparação concluída</h3>${metrics([['Total parcelado',brl(data.total_parcelado)],['Valor presente',brl(data.valor_presente_parcelado)],['Diferença nominal',brl(data.diferenca_nominal)],['Diferença no valor presente',brl(data.diferenca_valor_presente)]])}<p>Menor total nominal: <strong>${esc(data.melhor_nominal)}</strong>. Menor valor presente: <strong>${esc(data.melhor_valor_presente)}</strong>.</p><p class="note">O valor presente depende da taxa de comparação informada.</p>`;
}

// Event listener para submissão de formulários
document.querySelectorAll('form[data-action]').forEach(form=>form.addEventListener('submit',async event=>{
  event.preventDefault();
  const button=form.querySelector('button');
  const result=form.nextElementSibling;
  button.disabled=true;
  showResult(result,'<span class="loading">Calculando…</span>');
  
  const payload={action:form.dataset.action};
  form.querySelectorAll('input,select').forEach(input=>{
    payload[input.name]=input.inputMode==='decimal'?number(input.value):input.value;
  });
  
  try{
    const response=await fetch('api.php',{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify(payload)
    });
    const data=await response.json();
    if(!response.ok)throw new Error(data.error||'Não foi possível calcular.');
    showResult(result,render(data));
  }catch(error){
    showResult(result,esc(error.message),true);
  }finally{
    button.disabled=false;
  }
}));

// Carregar catálogo de índices
fetch('api.php?action=catalog').then(r=>r.json()).then(data=>{
  const series=data.series||[];
  document.getElementById('source-status').textContent=series.some(x=>x.collected_at)?'última sincronização registrada':'consulta sob demanda';
  document.getElementById('source-list').innerHTML=series.slice(0,4).map(x=>`<span class="source-chip">${esc(x.name)}${x.last_period?' · '+esc(x.last_period.slice(0,7)):''}</span>`).join('');
}).catch(()=>document.getElementById('source-status').textContent='fonte disponível sob consulta');
```

---

## 3. DESCRIÇÃO DAS 8 CALCULADORAS

### 1. **Boleto Vencido**
- **Endpoint**: `action=boleto`
- **Parâmetros**:
  - `valor` (decimal): Valor original
  - `multa` (decimal): Multa em %
  - `vencimento` (date): Data de vencimento
  - `pagamento` (date): Data de pagamento
  - `juros_dia` (decimal): Juros por dia em %

- **Fórmula**:
  - Dias de atraso = data_pagamento - data_vencimento
  - Multa = valor * (multa% / 100)
  - Juros = valor * (juros_dia% / 100) * dias_atraso
  - Total = valor + multa + juros

- **Validações Identificadas**:
  ✓ Rejeita data de pagamento antes de vencimento
  ✓ Aceita pagamento no mesmo dia (0 dias de atraso)
  ✓ Calcula apenas a multa (sem juros) em 0 dias

### 2. **Atualizar Valor ou Dívida**
- **Endpoint**: `action=update_value`
- **Parâmetros**:
  - `valor` (decimal): Valor original
  - `indice` (string): ipca, inpc, igpm, igpdi
  - `inicio` (month): Mês inicial (YYYY-MM)
  - `fim` (month): Mês final (YYYY-MM)
  - `juros_mensais` (decimal): Juros mensais (opcional)

- **Fórmula**:
  - Valor atualizado = valor * fator_indice * (1 + juros_mensais%)^meses
  - Fator_indice obtido do Banco Central do Brasil

- **Validações Identificadas**:
  ✗ Erro: "A fonte ainda não possui todos os meses escolhidos para esse índice"
  (Depende da disponibilidade de dados do BCB)

### 3. **Empréstimo/Financiamento (PRICE e SAC)**
- **Endpoint**: `action=loan`
- **Parâmetros**:
  - `valor` (decimal): Valor do empréstimo
  - `taxa` (decimal): Taxa mensal em %
  - `parcelas` (int): Número de parcelas
  - `sistema` (string): "price" ou "sac"

#### **Sistema PRICE (Tabela Price)**
- Fórmula: PMT = PV * [i*(1+i)^n] / [(1+i)^n - 1]
- Parcelas iguais
- Juros decrescentes
- Amortização crescente

Resultado de teste (10000, 1.5%, 12 parcelas):
- Primeira parcela: R$ 916,80
- Total: R$ 11.001,60
- Juros totais: R$ 1.001,60

#### **Sistema SAC (Amortização Constante)**
- Amortização constante = valor / parcelas
- Juros decrementais
- Parcelas decrescentes

Resultado de teste (10000, 1.5%, 12 parcelas):
- Primeira parcela: R$ 983,33
- Total: R$ 10.975,00
- Juros totais: R$ 975,00

- **Validações Identificadas**:
  ✓ Rejeita taxa negativa
  ✓ Rejeita taxa = 0, retorna valor sem juros
  ✓ Calcula corretamente SAC e PRICE
  ✓ Retorna apenas primeiras e última parcela na tabela

### 4. **Compra À Vista vs Parcelada**
- **Endpoint**: `action=cash_vs_installments`
- **Parâmetros**:
  - `vista` (decimal): Preço à vista
  - `entrada` (decimal): Valor da entrada
  - `parcela` (decimal): Valor de cada parcela
  - `parcelas` (int): Número de parcelas
  - `taxa_comparacao` (decimal): Taxa mensal para desconto (opcional)

- **Fórmula**:
  - Total parcelado = entrada + (parcela * parcelas)
  - Valor presente = entrada + Σ(parcela / (1+taxa)^i) para cada parcela
  - Diferença = total_parcelado - preço_vista
  - Diferença VP = valor_presente - preço_vista

- **Resultado de teste** (R$1000 vista vs 12x R$100 com 1% a.m.):
  - Total parcelado: R$ 1.200
  - Valor presente: R$ 1.125,51
  - Diferença nominal: R$ 200
  - Melhor opção: À vista

### 5. **Juros Simples/Compostos**
- **Endpoint**: `action=interest`
- **Parâmetros**:
  - `valor` (decimal): Valor inicial (principal)
  - `taxa` (decimal): Taxa por período em %
  - `periodos` (int): Número de períodos
  - `modo` (string): "compound" ou "simple"

#### **Juros Compostos**
- Fórmula: M = P * (1 + i)^n
- Resultado teste (R$1000, 2%, 12 períodos):
  - Juros: R$ 268,24
  - Montante: R$ 1.268,24

#### **Juros Simples**
- Fórmula: M = P + (P * i * n) = P * (1 + i*n)
- Resultado idêntico ao composto para os mesmos valores

- **Validações Identificadas**:
  ✓ Taxa = 0 retorna montante = principal
  ✓ Rejeita valor = 0 ou negativo
  ✓ Suporta números muito grandes (overflow em números extremos)

### 6. **Conversor de Taxas (Mensal ↔ Anual Composto)**
- **Endpoint**: `action=rate_convert`
- **Parâmetros**:
  - `mensal` (decimal): Taxa mensal em % (opcional)
  - `anual` (decimal): Taxa anual em % (opcional)

- **Fórmula de Conversão**:
  - De mensal para anual: i_anual = (1 + i_mensal)^12 - 1
  - De anual para mensal: i_mensal = (1 + i_anual)^(1/12) - 1

- **Exemplos verificados**:
  - 1% mensal = 12,68% anual
  - 12% mensal = 289,60% anual
  - 100% mensal = 409.500% anual
  - 0% mensal = 0% anual

### 7. **13º Salário**
- **Endpoint**: `action=thirteenth`
- **Parâmetros**:
  - `salario` (decimal): Salário bruto mensal
  - `meses` (int): Número de meses trabalhados (1-12)

- **Fórmula**:
  - 13º bruto = salario * meses / 12
  - Primeira parcela = 13º bruto / 2
  - Segunda parcela = 13º bruto - primeira parcela

- **Resultado teste** (R$5000 × 12 meses):
  - 13º bruto: R$ 5.000
  - 1ª parcela: R$ 2.500
  - 2ª parcela: R$ 2.500

- **Observação**: "INSS e IRPF não foram descontados nesta estimativa"

### 8. **Férias + 1/3**
- **Endpoint**: `action=vacation`
- **Parâmetros**:
  - `salario` (decimal): Salário bruto mensal
  - `dias` (int): Número de dias de férias (1-30)

- **Fórmula**:
  - Remuneração = salário * (dias / 30)
  - Terço constitucional = remuneração / 3
  - Total bruto = remuneração + terço constitucional

- **Resultado teste** (R$5000 × 30 dias):
  - Remuneração: R$ 5.000
  - 1/3 constitucional: R$ 1.666,67
  - Total bruto: R$ 6.666,67

---

## 4. PROBLEMAS E BUGS IDENTIFICADOS

### 🔴 BUGS CRÍTICOS

1. **Overflow em Números Extremos**
   - Taxa 50% mensal, 100 períodos: resultado = 4.0656117712865406E+26
   - Sem limite de valor máximo
   - Sem tratamento de NaN ou Infinity
   - Risco: crash do frontend ou valores absurdos

2. **Arredondamento Inconsistente (SAC)**
   - Teste: 10000, 1.5%, 12 parcelas SAC
   - Última parcela: R$ 845,83 (deveria ser R$ 841,67)
   - Acumula erro de arredondamento ao longo das parcelas
   - Saldo final: 0 (correto), mas parcela individual errada

### 🟡 PROBLEMAS MODERADOS

3. **Falta de Validação de Range**
   - Aceita `periodos` até 1200 (100 anos!)
   - Aceita `parcelas` até 480 (40 anos!)
   - Sem limite máximo de juros anual (409.500%)
   - Sem limite mínimo de salário ou valor

4. **Precisão em Ponto Flutuante (PRICE)**
   - Primeiro teste: 10000 + 1.5% × 12
   - Parcela: 916,80 (pode variar por arredondamento)
   - Acumulação de erros: 11.001,60 vs esperado (verificar)
   - Sem garantia de que última parcela fecha saldo para zero

5. **Tratamento de Entrada Nula/Vazia**
   - Frontend converte vazio para "0,00"
   - Backend rejeita valor = 0
   - Erro genérico: "Confira valor, taxa e número de períodos"
   - Não especifica qual campo é inválido

### 🟠 PROBLEMAS DE SEGURANÇA

6. **Injeção de HTML via Campo `observacao`**
   - Frontend usa `esc()` para escapar valores
   - Mas campo retornado do backend pode conter HTML
   - Se backend não escapar corretamente, XSS possível
   - Exemplo: `"observacao": "<img src=x onerror=alert('xss')>"`

7. **Falta de Validação de CSRF**
   - POST via fetch() sem token CSRF
   - Sem validação de origem
   - Requisições podem ser feitas de qualquer site

8. **Dados Sensíveis no LocalStorage/SessionStorage**
   - Não observado, mas possível vulnerabilidade
   - Se dados de cálculo armazenados, risco de exposição

### 🔵 PROBLEMAS DE LÓGICA

9. **Boleto Futuro é Rejeitado**
   - Data de pagamento ANTES de vencimento = erro
   - Cenário válido rejeitado (pagamento adiantado)
   - Deveria permitir com "dias de atraso negativos"

10. **Atualização de Dívida com Juros Não-Compostos**
    - Fórmula: valor * fator_indice * (1 + juros_mensais%)^meses
    - Assume juros compostos, não oferece opção simples
    - Sem documentação clara da fórmula

11. **Taxa de Comparação Obrigatória**
    - Parâmetro "taxa_comparacao" (opcional) mas recomendado
    - Se = 0, não desconta (considera valor presente = valor nominal)
    - Pode gerar confusão para usuário

12. **Truncamento de Cronograma**
    - PRICE/SAC retornam apenas primeiras parcelas + última
    - Usuário não vê todas as parcelas intermediárias
    - Impossível verificar evolução completa

### ⚪ PROBLEMAS DE USABILIDADE

13. **Validação Genérica Demais**
    - Múltiplos campos rejeitados com mesma mensagem
    - Exemplo: "Confira valor, taxa e número de períodos"
    - Impossível saber qual campo está errado

14. **Sem Limite Visual de Input**
    - Usuário pode digitar valores absurdamente grandes
    - `type="number" max="480"` apenas no HTML
    - Backend não rejeita valores > 480

---

## 5. FÓRMULAS MATEMÁTICAS VERIFICADAS

### ✅ CORRETAS
- Boleto (simples): valor + multa + (valor × juros_dia% × dias)
- Juros Simples: M = P × (1 + i×n)
- Juros Compostos: M = P × (1 + i)^n
- Conversão Anual: i_a = (1 + i_m)^12 - 1
- 13º: salário × meses/12
- Férias: salário × dias/30 + terço
- SAC: amortização fixa, juros decrescentes
- PRICE: parcela fixa, juros + amortização variáveis

### ⚠️ IMPLEMENTAÇÃO QUESTIONÁVEL
- PRICE: última parcela pode não fechar exatamente zero
- SAC: arredondamento causa pequenos erros
- Índice: depende de dados do BCB (fora do escopo)

---

## 6. RECOMENDAÇÕES

### 🔥 CRÍTICAS (Fazer AGORA)
1. Adicionar limite máximo de overflow (ex: 1e20)
2. Validar inputs com mensagens específicas
3. Testar CSRF e implementar proteção
4. Escapar corretamente todos os dados do backend

### 📋 IMPORTANTES (Próxima Sprint)
5. Permitir pagamento adiantado de boleto (dias negativos)
6. Mostrar tabela completa de amortização (opção de download?)
7. Adicionar validação `max` no backend (não apenas HTML)
8. Implementar rate limiting na API

### 🎯 MELHORIAS (Roadmap)
9. Suportar juros simples na atualização de dívida
10. Adicionar gráficos de evolução (PRICE vs SAC)
11. Integração com dados em tempo real do BCB
12. Suporte a CPF para salvar cálculos (com consentimento)

---

