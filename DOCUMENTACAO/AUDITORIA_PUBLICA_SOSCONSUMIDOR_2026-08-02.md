# Auditoria pública inicial — SOSConsumidor

**Data:** 02/08/2026  
**Escopo:** inspeção externa, não invasiva, das páginas públicas do site. Não houve acesso à VPS, CMS, Analytics, Search Console, banco de dados ou listas de e-mail.  
**Objetivo:** identificar os bloqueios mais urgentes à monetização ética e à medição do funil.

## Resultado executivo

O SOSConsumidor tem uma fundação valiosa: domínio consolidado, site disponível por HTTPS/HTTP/2, sitemap funcional com **5.379 URLs**, páginas prioritárias rápidas na leitura externa, conteúdo atualizado e boa indexabilidade básica. O principal problema não é infraestrutura; é a ausência de uma jornada de conversão mensurável e adequada para o público de dívidas.

**Prioridade absoluta:** instalar/confirmar GA4 e Search Console, reformular a captação de newsletter sob LGPD e inserir uma oferta gratuita contextual nas páginas que já atraem visitantes. Sem isso, não é possível medir audiência, origem de vendas ou retorno de qualquer investimento.

## Evidências observadas

| Área | Evidência | Leitura |
| --- | --- | --- |
| Disponibilidade | Página inicial e artigo prioritário responderam com HTTP 200 | Site está acessível e estável no teste |
| Desempenho de resposta | Tempo até o primeiro byte: 0,17–0,31 s; HTML: 49–73 KB | Boa base para conversão; auditoria completa de Core Web Vitals ainda depende de teste em navegador real |
| Segurança de transporte | HTTPS, HTTP/2, HSTS, `X-Content-Type-Options`, `X-Frame-Options` e `Referrer-Policy` | Boa configuração inicial |
| Indexação | `robots.txt` aponta para `sitemap.php`; sitemap retornou XML com 5.379 URLs; canonical e Schema.org presentes na home | Boa base de SEO técnico |
| Conteúdo | Páginas de dívida, negativação, SPC/Serasa e golpes aparecem com atualizações recentes | Há aderência entre a audiência existente e a oferta proposta |
| Conversão | O artigo de alto interesse exibe apenas CTA genérica de newsletter | Tráfego não está sendo conduzido a uma ferramenta/lead magnet específico |

## Achados e plano de correção

### P0 — fazer antes de qualquer lançamento

#### 1. Medição aparentemente baseada em Universal Analytics

**Evidência:** o código público usa `analytics.js` e a interface Universal Analytics (`ga`). Não foi identificado código GA4 público na home inspecionada.

**Risco:** propriedades Universal Analytics padrão deixaram de processar novos dados em 01/07/2023. Se não houver uma instalação de GA4 fora do trecho identificado, decisões sobre tráfego e receita estão sendo tomadas sem dados atuais.

**Ação:**

1. Confirmar no Google Analytics se existe propriedade GA4 recebendo dados.
2. Caso não exista, criar GA4 e instalar via Google Tag Manager ou diretamente no site.
3. Configurar os eventos: `view_checklist`, `newsletter_signup`, `click_kit_sos`, `begin_checkout`, `purchase`, `refund_request`.
4. Vincular GA4 ao Search Console e registrar uma linha de base de 90 dias.

**Critério de aceite:** visualização em tempo real no GA4 e registro de teste de cada evento.

#### 2. Newsletter não está pronta para ser o centro do funil

**Evidência:** formulário próprio envia nome e e-mail a `newsletter_ler_db.php`, com reCAPTCHA, mas a tela analisada não apresenta consentimento específico, finalidade, periodicidade, link de privacidade ao lado do envio ou informação de descadastro.

**Risco:** baixa conversão por falta de proposta de valor e risco de governança/LGPD ao ampliar a base.

**Ação:**

- Trocar a promessa genérica “receba notícias” por uma oferta específica: “Baixe gratuitamente o Checklist SOS: o que conferir antes de negociar uma dívida”.
- Solicitar apenas e-mail na primeira etapa; nome é opcional.
- Incluir caixa não pré-marcada: “Quero receber conteúdos e ofertas do SOSConsumidor por e-mail. Posso cancelar a qualquer momento.”
- Exibir link para política de privacidade e explicar finalidade, frequência e descadastro junto ao botão.
- Confirmar double opt-in, registro do consentimento, rota de descadastro e política de retenção antes de importar/usar a base atual.

**Critério de aceite:** cadastro, confirmação, recebimento, descadastro e exclusão testados ponta a ponta.

#### 3. Não iniciar venda antes da revisão editorial e legal do produto

**Risco:** as dores abordadas envolvem consumo, crédito, negativação e potencial assistência jurídica. Textos do kit/curso/IA devem evitar promessa de resultado e recomendação individualizada.

**Ação:** instituir responsável editorial/jurídico, data de revisão e fontes para cada material; inserir aviso educativo claro nas páginas de venda, nos materiais e na IA.

### P1 — executar nos primeiros 30 dias

#### 4. Construir o primeiro funil nas páginas de maior intenção

**Evidência:** em artigo prioritário sobre consulta a SPC/Serasa/SCPC, o único CTA observável é a newsletter genérica no cabeçalho/rodapé.

**Ação:** inserir bloco contextual após a resposta principal e antes de conteúdos relacionados:

> “Está com dívidas ou não sabe por onde começar? Baixe o Checklist SOS gratuito e monte seu mapa de dívidas em 15 minutos.”

O botão deve levar a uma landing page própria, com evento rastreável. Após o cadastro, entregar o checklist e iniciar a sequência de e-mails do plano de 90 dias.

#### 5. Corrigir títulos e descrições para intenção específica

**Evidência:** página de newsletter e artigo consultado usam a mesma descrição institucional genérica.

**Risco:** perda de clareza no resultado de busca e menor taxa de clique; a descrição não reforça a intenção de cada página.

**Ação:** escrever título, descrição, H1 e CTA específicos para as 20 páginas de maior tráfego. Exemplo de descrição para a página de consulta: “Veja como consultar gratuitamente possíveis restrições em SPC, Serasa e SCPC e quais cuidados tomar para não cair em golpes.”

#### 6. Organizar o acervo por jornadas, não apenas por notícias

**Evidência:** a home mescla conteúdos amplos de notícias com temas diretamente monetizáveis de consumo e endividamento.

**Ação:** criar hubs permanentes e visíveis:

- Dívidas e negativação.
- Cartão, empréstimos e juros.
- Golpes e fraudes.
- Apostas e reorganização financeira.
- Ferramentas SOS.

Cada hub deve ter explicação, artigos relacionados, ferramenta gratuita e CTA para o checklist. Notícias gerais continuam úteis, mas não devem competir com essas jornadas no caminho para a conversão.

#### 7. Substituir dependências externas antigas por ferramentas próprias

**Evidência:** há links para calculadoras externas, incluindo URL em `http`.

**Ação:** manter temporariamente como referência, mas criar a Calculadora SOS dentro do domínio e tornar essa ferramenta o CTA principal. Isto aumenta confiança, rastreabilidade e captura de lead sem reter dados desnecessários.

### P2 — melhoria técnica e privacidade antes de adicionar conta/IA

#### 8. Sessão anônima e cache

**Evidência:** páginas públicas enviam `PHPSESSID` e usam `Cache-Control: no-store, no-cache`; o cookie observado não contém os atributos `Secure`, `HttpOnly` e `SameSite` no cabeçalho.

**Impacto:** piora a capacidade de cache de conteúdo público e merece revisão de segurança antes de guardar dados em ferramentas próprias.

**Ação:** com acesso à VPS/CMS, impedir abertura de sessão PHP para visitantes anônimos quando não for necessária; configurar cookies com `Secure`, `HttpOnly` e `SameSite=Lax` ou política justificada; habilitar cache público para conteúdo não personalizado. Testar login, fóruns e formulários depois da mudança.

#### 9. Preparar LGPD e dados financeiros

**Ação:** antes de lançar gerenciador ou IA, criar inventário de dados, bases legais, política por produto, prazo de retenção, exportação/exclusão e contratos com fornecedores. A primeira calculadora deve funcionar no navegador sem conta e não pedir CPF, dados bancários, senhas ou documentos.

#### 10. IA somente como orientador educativo com base revisada

**Ação:** lançar depois do kit e do curso. A IA deverá responder apenas com base documental revisada, recusar dados sensíveis, exibir limites claros, citar a página do SOS correspondente e encaminhar para Procon/Defensoria/advogado de livre escolha em casos individuais.

## O que não é problema hoje

- A VPS é própria, está ativa e tem horizonte suficiente para hospedar as próximas etapas; não há motivo para migração agora.
- A resposta externa das páginas avaliadas é rápida. Não recomendo gastar o primeiro orçamento em redesign ou troca de hospedagem.
- O sitemap e os elementos básicos de indexação estão presentes. A prioridade é melhorar páginas de intenção e medição, não reconstruir todo o SEO.

## Ordem recomendada de execução

1. GA4 + Search Console + eventos de conversão.
2. Novo formulário/lead magnet e conformidade da newsletter.
3. Checklist gratuito, landing page e sequência de cinco e-mails.
4. CTAs contextuais nas 5–20 páginas prioritárias.
5. Kit SOS pago.
6. Curso.
7. Gerenciador financeiro e IA.

## Acessos necessários para a auditoria fechada

Não compartilhar senhas por chat. Quando o usuário autorizar a fase de implementação, o acesso deve ser concedido por usuário individual, permissão mínima e, de preferência, temporariamente.

- Google Analytics 4: função de leitor para auditoria; editor somente para implementação aprovada.
- Google Search Console: permissão de proprietário/usuário completo para leitura de desempenho e cobertura.
- CMS/repositório: leitura para mapear temas, plugins, formulários e pontos de inserção; escrita apenas para alterações aprovadas.
- Plataforma de e-mail: leitura de lista, origem dos contatos, taxas, automações e descadastros.
- VPS: não é necessária para auditoria pública; será necessária apenas para revisão técnica e implantação aprovada.

## Resultado esperado em 30 dias

Uma base de dados confiável, uma captação que respeita o usuário, uma ferramenta gratuita contextual nas páginas mais visitadas e a primeira oferta pronta para teste. O sucesso inicial será medido por visitas → cadastro → compra, não por número de páginas publicadas.

## Atualização da auditoria interna — 02/08/2026

### Acesso e cópia externa

- O acesso por chave SSH/SFTP ao usuário de hospedagem foi validado.
- Foi criada uma cópia local dos arquivos recuperáveis do domínio principal, com manifesto SHA-256, em `BACKUP_SOSCONSUMIDOR_2026-08-02_0910/`.
- O backup integral gerenciável pelo Hestia (arquivos, bancos, DNS, e-mail e cron) ainda depende da liberação do comando administrativo `v-backup-user`; nenhuma alteração de banco de dados foi feita.

### Correção crítica aplicada no ambiente de teste

Foi identificado que `https://teste.sosconsumidor.com.br/admsite.zip` respondia publicamente com HTTP 200. O arquivo tinha 866.528.340 bytes e cache público de longa duração.

**Medida aplicada:**

1. Foi feita cópia externa do arquivo, validada pelo SHA-256 `e20cb3f968e4246e3dc7e66e286a8c338ca41e1f376d63366faabbfc8646ed63`.
2. O arquivo foi movido de `public_html/admsite.zip` para `private/admsite.zip` no mesmo ambiente.
3. A URL pública foi verificada e passou a retornar HTTP 404.
4. O domínio principal foi verificado após a mudança e permaneceu com HTTP 200.

Essa alteração é reversível: basta devolver o arquivo da pasta `private` para a localização original, embora isso não seja recomendado.

### Outra exposição removida no teste

O arquivo de backup de código `head.inc.php.bak3` também respondia com HTTP 200 no ambiente de teste. Ele foi copiado externamente, validado por SHA-256 `58ea0aa05e8f5c8d95be04e6378bde94e3d8debd00c92094187e830ce6796c05` e movido para a pasta `private`. A URL agora responde HTTP 404.

### Risco que exige rotação de credenciais

O arquivo ZIP exposto contém a aplicação administrativa e arquivos de configuração. Como ele ficou público até a correção, devem ser rotacionadas, de forma coordenada, as credenciais que possam estar no pacote: banco de dados, serviço de e-mail/newsletter, reCAPTCHA, chaves de integrações e senhas administrativas. Esta ação requer o backup integral e acesso administrativo; não foi realizada automaticamente para não interromper o site.

## Atualização de produção e hardening — 02/08/2026

### Backup integral validado

Foi criado e validado o backup Hestia completo do usuário de hospedagem:

- Arquivo: `user.2026-08-02_13-21-28.tar`.
- Tamanho em disco: 3,6 GB.
- Validação: leitura completa do catálogo TAR concluída sem erro.
- SHA-256: `bc10c9f4a8793af3ae5923720823b1d4e1a48c51c4f0f97bb98d9a57a64354fe`.

### Exposição corrigida também na produção

O mesmo `admsite.zip` de 866.528.340 bytes estava publicamente disponível no domínio principal. Ele foi movido de `public_html` para `private/admsite.zip`, preservando o SHA-256 `e20cb3f968e4246e3dc7e66e286a8c338ca41e1f376d63366faabbfc8646ed63`.

Também foram retiradas da área pública cópias de código detectadas com HTTP 200:

- `head.inc.php.bak`;
- `admsite/index.php.bak.20260316`;
- `admsite/classes/noticias/Noticias.Class.php.bak.20260316`;
- `forum_consumidor/index.php.bak`;
- `pesquisa_adv.php.bak.2017`;
- `images/202603131208240.png.bak`.

Cada URL passou a responder HTTP 404. A home do domínio principal foi testada após a alteração e permaneceu com HTTP 200.

### Proteção persistente no Nginx

Foram adicionadas regras persistentes por domínio no diretório de configuração do Hestia, tanto em HTTP quanto HTTPS, para negar exposição de extensões de backup, ambiente, banco e pacotes comprimidos (`.bak`, `.old`, `.sql`, `.env`, `.zip`, `.tar`, `.tgz`, `.gz`, `.bz2` e `.7z`).

- A sintaxe do Nginx foi validada antes de cada recarga.
- As regras foram testadas em `teste.sosconsumidor.com.br` antes de chegarem à produção.
- Um arquivo ZIP de dependência antes acessível recebeu HTTP 404 após a regra; a home de produção continuou com HTTP 200.

Os arquivos de configuração substituídos foram preservados em diretórios privados do respectivo domínio, além do backup Hestia integral.

### Pendências de alto risco

1. Rotacionar credenciais potencialmente presentes no arquivo que ficou exposto: usuários/senhas de banco, serviço de newsletter, reCAPTCHA, integrações de URL curta/redes sociais e contas administrativas.
2. Revisar o diretório legado `apagar/` e as bibliotecas antigas, atualmente dentro de `public_html`, para desativação controlada.
3. Corrigir o erro HTTP 500 já existente na página inicial de `teste.sosconsumidor.com.br`; ele foi observado antes da aplicação do hardening e não foi propagado para produção.
