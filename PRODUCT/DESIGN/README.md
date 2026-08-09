# DESIGN — modelos de site SOS Consumidor

Material de design do portal, reunido em 08/08/2026. Antes disso estava solto em
`~/Downloads/SOS Consumidor/` no Mac, fora de qualquer backup.

As telas de **planos e assinaturas** da Geração 2 conversam diretamente com
`../PRD_ASSINATURAS.md` (SOS Consumidor+ e SOS Pro).

## Como ver

- **Do celular / Dropbox app:** abra as pastas `renders/`. São imagens prontas, sem
  precisar de navegador ou internet no site.
  - `geracao-1-home/renders/` — 16 JPEG, página inteira de cada rascunho
  - `geracao-2-stitch/renders/` — 43 PNG, achatados e prefixados por família
    (`1-home__`, `2-chat-ia__`, `3-painel__`, `4-planos__`)
- **Do computador:** abra `galeria.html`. Indexa tudo com miniaturas ao vivo e
  botões para abrir cada modelo em tamanho real.

## Estrutura

```
galeria.html                  índice de todos os 59 modelos
geracao-1-home/               16 rascunhos de home, HTML autocontido
  renders/                    render de página inteira de cada um (JPEG 1440px)
geracao-2-stitch/
  telas/                      43 telas, cada uma com code.html + screen.png
  renders/                    os mesmos screen.png, achatados e agrupados
  proposta_de_melhorias_sos_consumidor.html
```

## Geração 1 — redesenho da home (25/02 a 09/03/2026)

Rascunhos independentes, cada um em um único HTML que abre sozinho. O CSS é inline;
só as fotos das notícias vêm do site em produção.

| Versão | Mudança |
|---|---|
| `redesign` | primeira proposta |
| `home-redesign`, `home-v2` | header vinho/vermelho, logo "SOS Consumidor" |
| `home-v3` … `home-v3_9` | logo "S.O.S Consumidor", busca Google, faixa de números, coluna lateral |
| `home-v4` … `home-v4_3` | variação enxuta |
| `home-v5`, `home-v5_1` | versão mais elaborada da série |

## Geração 2 — Stitch "Justice Core" (24/03/2026)

Conceito de portal com camada de IA, em quatro famílias: home/landing, chat de IA
jurídica, painel do usuário, planos e assinaturas. As variantes `*_logo_oficial_v2_3`
são as mais acabadas. Os `code.html` dependem de Tailwind CDN e Google Fonts — sem
internet eles abrem sem estilo, por isso os `renders/`.

Alguns `screen.png` saíram degenerados (`home_com_novo_logo` com 77px de largura,
`home_com_not_cias` com 87px); nesses, use o `code.html`.

`proposta_de_melhorias_sos_consumidor.html` descreve o conceito: hero com busca por IA,
widget de chat flutuante, seção de planos (Gratuito vs Pro) e sugestão de IA ao fim de
cada notícia.

## Decisão em aberto

A proposta termina com uma pergunta que nunca foi respondida:

> manter o **azul e laranja** do site atual, ou adotar o **azul-marinho e roxo** do
> Justice Core?

## Onde mais isto existe

- Versionado em `github.com/lisandro-adv/teste-sosconsumidor` (privado), commit `6ae15f1`,
  junto com uma cópia PHP do site de teste.
- Originais intactos em `~/Downloads/SOS Consumidor/` no Mac.
