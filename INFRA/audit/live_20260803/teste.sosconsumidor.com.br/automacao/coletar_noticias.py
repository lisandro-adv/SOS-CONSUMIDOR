#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Automação de coleta e publicação de notícias - SOS Consumidor
Coleta notícias de G1, Folha, Estadão via RSS
e insere no banco de dados do site de teste.
"""

import os
import re
import html
import hashlib
import json
import logging
import unicodedata

import feedparser
import requests
import mysql.connector
from dotenv import load_dotenv

load_dotenv(os.path.join(os.path.dirname(os.path.abspath(__file__)), '.env'))
from datetime import datetime, timedelta
from bs4 import BeautifulSoup
from urllib.parse import urlparse

# === CONFIGURAÇÃO ===
DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'user': os.getenv('DB_USER'),
    'password': os.getenv('DB_PASSWORD'),
    'database': os.getenv('DB_NAME'),
    'charset': os.getenv('DB_CHARSET', 'latin1'),
    'use_unicode': True,
}

# Diretório base do script
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
LOG_FILE = os.path.join(BASE_DIR, 'coletar_noticias.log')
CACHE_FILE = os.path.join(BASE_DIR, 'noticias_cache.json')

# Configurar logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] %(message)s',
    handlers=[
        logging.FileHandler(LOG_FILE, encoding='utf-8'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

# Mapeamento de fontes RSS -> fonte_id no banco
FONTES_RSS = {
    'G1': {
        'fonte_id': 17,
        'feeds': [
            'https://g1.globo.com/rss/g1/economia/',
            'https://g1.globo.com/rss/g1/',
        ]
    },
    'Folha Online': {
        'fonte_id': 5,
        'feeds': [
            'https://feeds.folha.uol.com.br/mercado/rss091.xml',
            'https://feeds.folha.uol.com.br/cotidiano/rss091.xml',
        ]
    },

    'Estadao': {
        'fonte_id': 20,
        'feeds': [
            'https://www.estadao.com.br/arc/outboundfeeds/rss/?outputType=xml',
        ]
    },
    'R7': {
        'fonte_id': 58,
        'feeds': [
            'https://noticias.r7.com/feed.xml',
        ]
    },
    'CNN Brasil': {
        'fonte_id': 148,
        'feeds': [
            'https://www.cnnbrasil.com.br/economia/feed/',
        ]
    },
    'Jovem Pan': {
        'fonte_id': 155,
        'feeds': [
            'https://jovempan.com.br/feed',
        ]
    },
    'Valor Economico': {
        'fonte_id': 125,
        'feeds': [
            'https://pox.globo.com/rss/valor/',
        ]
    },
}

# Palavras-chave de interesse (direito do consumidor, economia, etc.)
PALAVRAS_INTERESSE = [
    'consumidor', 'consumo', 'procon', 'recall', 'direito', 'justica',
    'tribunal', 'indenizacao', 'dano moral', 'cobranca', 'banco',
    'financeiro', 'credito', 'emprestimo', 'cartao', 'tarifa',
    'plano de saude', 'saude', 'operadora', 'seguro', 'previdencia',
    'aposentadoria', 'inss', 'trabalhista', 'clt', 'salario',
    'aluguel', 'imovel', 'locacao', 'condominio',
    'telefonia', 'internet', 'telecomunicacao', 'anatel',
    'energia', 'agua', 'saneamento', 'concessionaria',
    'compra', 'venda', 'e-commerce', 'online', 'fraude', 'golpe',
    'produto', 'servico', 'garantia', 'troca', 'devolucao',
    'inflacao', 'preco', 'reajuste', 'economia', 'pib',
    'imposto', 'tributacao', 'reforma', 'governo', 'lei',
    'codigo de defesa', 'anvisa', 'vigilancia', 'medicamento',
    'alimento', 'transporte', 'aereo', 'passagem', 'viagem',
    'stj', 'stf', 'decisao', 'jurisprudencia', 'sumula',
    'selic', 'juros', 'divida', 'inadimplencia', 'negativacao',
    'serasa', 'spc', 'score', 'nome limpo',
    'pix', 'pagamento', 'transferencia', 'conta', 'agencia',
    'investimento', 'poupanca', 'rendimento', 'fgts',
    'educacao financeira', 'orcamento', 'planejamento',
    'importacao', 'exportacao', 'dolar', 'mercado',
]

MAX_NOTICIAS = 15
MIN_TEXTO_CHARS = 300
DIAS_SEM_REPETIR = 5  # Nao repetir noticias similares dos ultimos N dias
MIN_SCORE = 1  # Score minimo para aceitar a noticia (0 = sem relacao com o tema)
HEADERS = {
    'User-Agent': 'Mozilla/5.0 (compatible; SOSConsumidorBot/1.0)'
}

IMAGES_DIR = '/home/user/web/teste.sosconsumidor.com.br/public_html/images/'
# Quantas noticias de destaque devem ter imagem (DESTAQUE_1 + DESTAQUE_2)
NOTICIAS_COM_IMAGEM = 2

# Mapeamento de palavras-chave para termos de busca de imagem (em ingles)
CATEGORIAS_IMAGEM = [
    (['consumidor', 'procon', 'compra', 'produto', 'servico', 'garantia', 'troca', 'devolucao', 'recall', 'codigo de defesa'],
     'consumer,shopping,store'),
    (['justica', 'tribunal', 'stj', 'stf', 'decisao', 'jurisprudencia', 'indenizacao', 'dano moral', 'sumula'],
     'justice,court,law'),
    (['fraude', 'golpe', 'crime', 'policia', 'pf', 'operacao'],
     'cybersecurity,fraud,hacker'),
    (['banco', 'financeiro', 'credito', 'emprestimo', 'cartao', 'pix', 'pagamento', 'conta', 'tarifa'],
     'banking,finance,credit'),
    (['economia', 'mercado', 'dolar', 'selic', 'juros', 'inflacao', 'pib', 'investimento', 'poupanca', 'rendimento'],
     'economy,stock,market'),
    (['imposto', 'tributacao', 'reforma', 'governo', 'lei', 'politica'],
     'government,taxes,politics'),
    (['saude', 'plano de saude', 'medicamento', 'anvisa', 'hospital'],
     'health,hospital,medicine'),
    (['trabalho', 'trabalhista', 'salario', 'clt', 'emprego', 'aposentadoria', 'inss', 'previdencia', 'fgts'],
     'work,employment,office'),
    (['imovel', 'aluguel', 'condominio', 'locacao'],
     'realestate,house,apartment'),
    (['transporte', 'aereo', 'passagem', 'viagem', 'aviao'],
     'airplane,travel,airport'),
    (['telefonia', 'internet', 'telecomunicacao', 'anatel', 'tecnologia'],
     'technology,smartphone,internet'),
    (['energia', 'agua', 'saneamento', 'concessionaria'],
     'energy,electricity,water'),
    (['educacao', 'escola', 'universidade'],
     'education,school,university'),
    (['seguro', 'carro', 'veiculo', 'automovel'],
     'car,automobile,insurance'),
    (['divida', 'inadimplencia', 'negativacao', 'serasa', 'spc', 'nome limpo'],
     'debt,bills,money'),
]
IMAGEM_PADRAO = 'business,newspaper,economy'


def sanitizar_latin1(texto):
    """Remove caracteres que não são suportados pelo latin1 (emojis, etc.)"""
    if not texto:
        return ''
    # Remove emojis e caracteres fora do BMP
    texto = re.sub(r'[\U00010000-\U0010ffff]', '', texto)
    # Normaliza unicode
    texto = unicodedata.normalize('NFC', texto)
    # Tenta converter para latin1, removendo caracteres inválidos
    try:
        texto = texto.encode('latin-1', errors='ignore').decode('latin-1')
    except:
        texto = texto.encode('ascii', errors='ignore').decode('ascii')
    return texto


def carregar_cache():
    """Carrega cache de URLs já processadas"""
    if os.path.exists(CACHE_FILE):
        try:
            with open(CACHE_FILE, 'r', encoding='utf-8') as f:
                return json.load(f)
        except:
            pass
    return {'urls': [], 'titulos': []}


def salvar_cache(cache):
    """Salva cache (mantém últimos 500)"""
    cache['urls'] = cache['urls'][-500:]
    cache['titulos'] = cache['titulos'][-500:]
    with open(CACHE_FILE, 'w', encoding='utf-8') as f:
        json.dump(cache, f, ensure_ascii=False, indent=2)


def limpar_html(texto):
    """Remove tags HTML e limpa o texto"""
    if not texto:
        return ''
    soup = BeautifulSoup(texto, 'lxml')
    texto_limpo = soup.get_text(separator=' ')
    texto_limpo = re.sub(r'\s+', ' ', texto_limpo).strip()
    texto_limpo = html.unescape(texto_limpo)
    return texto_limpo


def gerar_hash_titulo(titulo):
    """Hash do título para detectar duplicatas"""
    titulo_normalizado = re.sub(r'[^\w\s]', '', titulo.lower())
    titulo_normalizado = re.sub(r'\s+', ' ', titulo_normalizado).strip()
    return hashlib.md5(titulo_normalizado.encode('utf-8')).hexdigest()


def texto_para_entidades_html(texto):
    """Converte caracteres acentuados para entidades HTML.
    Isso garante compatibilidade com banco latin1 e segue o padrao do site."""
    if not texto:
        return ''
    # Mapeamento dos caracteres mais comuns em portugues
    mapa = {
        'á': '&aacute;', 'à': '&agrave;', 'â': '&acirc;', 'ã': '&atilde;',
        'é': '&eacute;', 'è': '&egrave;', 'ê': '&ecirc;',
        'í': '&iacute;', 'ì': '&igrave;', 'î': '&icirc;',
        'ó': '&oacute;', 'ò': '&ograve;', 'ô': '&ocirc;', 'õ': '&otilde;',
        'ú': '&uacute;', 'ù': '&ugrave;', 'û': '&ucirc;',
        'ç': '&ccedil;',
        'Á': '&Aacute;', 'À': '&Agrave;', 'Â': '&Acirc;', 'Ã': '&Atilde;',
        'É': '&Eacute;', 'È': '&Egrave;', 'Ê': '&Ecirc;',
        'Í': '&Iacute;', 'Ì': '&Igrave;', 'Î': '&Icirc;',
        'Ó': '&Oacute;', 'Ò': '&Ograve;', 'Ô': '&Ocirc;', 'Õ': '&Otilde;',
        'Ú': '&Uacute;', 'Ù': '&Ugrave;', 'Û': '&Ucirc;',
        'Ç': '&Ccedil;',
        'ü': '&uuml;', 'Ü': '&Uuml;',
        '\u00b0': '&deg;', '\u00b2': '&sup2;', '\u00b3': '&sup3;',
        '\u201c': '&ldquo;', '\u201d': '&rdquo;',
        '\u2018': '&lsquo;', '\u2019': '&rsquo;',
        '\u2013': '&ndash;', '\u2014': '&mdash;',
    }
    for char, entity in mapa.items():
        texto = texto.replace(char, entity)
    # Remover caracteres que nao sao latin1
    texto = re.sub(r'[\U00010000-\U0010ffff]', '', texto)
    # Safety net: garantir compatibilidade com latin1
    try:
        texto = texto.encode('latin-1', errors='xmlcharrefreplace').decode('latin-1')
    except:
        texto = texto.encode('ascii', errors='xmlcharrefreplace').decode('ascii')
    return texto


# Padroes de frases de propaganda/auto-promocao para remover
PADROES_PROPAGANDA = [
    'siga o canal',
    'siga no whatsapp',
    'siga o g1',
    'siga a folha',
    'siga o uol',
    'siga no instagram',
    'siga no twitter',
    'siga no facebook',
    'siga no telegram',
    'entre no canal',
    'participe do canal',
    'clique aqui para seguir',
    'inscreva-se',
    'assista aos videos',
    'assista ao video',
    'veja os videos',
    'videos que estao em alta',
    'videos mais vistos',
    'veja tambem',
    'leia tambem',
    'leia mais',
    'saiba mais',
    'confira tambem',
    'veja mais',
    'outra materia',
    'g1 no whatsapp',
    'g1 no telegram',
    'rede clube',
    'rede amazonica',
    'rede globo',
    'globoplay',
    '\u2014 foto:',
    '-- foto:',
    'foto:',
    'imagem:',
    'reproducao/',
    'arte/g1',
    'arquivo pessoal',
    'divulgacao/',
    'leia a reportagem completa',
    'clique para assinar',
    'conteudo exclusivo',
    'baixe o app',
    'download do app',
    'receba notificacoes',
    'ative as notificacoes',
    'compartilhe esta noticia',
    'compartilhe no',
    'envie por email',
    'curta nossa pagina',
    'podcast',
    'newsletter',
    # Caixa de comentarios dos sites
    'deixe seu comentario',
    'deixe um comentario',
    'o autor da mensagem',
    'responsavel pelo comentario',
    'regras de uso',
    'termos de uso',
    'leia as regras',
    'comentarios nao representam',
    'opiniao do portal',
    'opiniao do site',
    'os comentarios abaixo',
    'os comentarios sao de responsabilidade',
    'os comentarios nao representam',
    'comentar esta materia',
    'faca seu comentario',
    'envie seu comentario',
    'comente esta noticia',
    # Botoes de compartilhamento
    'whatsappfacebooktwitter',
    'whatsapp facebook twitter',
    'copiar link',
    'compartilhar no whatsapp',
    'compartilhar no facebook',
    'compartilhar no twitter',
    'share on',
    # Sugestoes de reportagem / chamadas dos portais
    'sugestao de reportagem',
    'alguma sugestao de reportagem',
    'mande para o g1',
    'mande para o uol',
    'mande para a folha',
    'envie sua pauta',
    'envie sua sugestao',
    'mande sua sugestao',
    'colabore com o',
    'envie sua denuncia',
    'participe da cobertura',
    'voce viu algo',
    'envie fotos e videos',
    'mande fotos',
    'mande sua historia',
    'conte sua historia',
    'fale com a redacao',
    'entre em contato com a redacao',
    'envie para a redacao',
    # Chamadas de video/programa de TV
    'veja a reportagem exibida',
    'veja a reportagem no',
    'veja as reportagens do',
    'videos: veja as reportagens',
    'acesse + tv tem',
    'acesse + tv',
    'nosso campo',
    'bom dia cidade',
    'programacao | videos | redes sociais',
    'programacao|videos|redes sociais',
]


def paragrafo_e_propaganda(texto):
    """Verifica se um paragrafo e propaganda/auto-promocao da fonte."""
    import unicodedata
    texto_norm = unicodedata.normalize('NFKD', texto.lower())
    texto_norm = ''.join(c for c in texto_norm if not unicodedata.combining(c))
    for padrao in PADROES_PROPAGANDA:
        if padrao in texto_norm:
            return True
    # Detectar padroes com emoji no inicio
    if any(texto.startswith(c) for c in ['\u2705', '\ud83d', '\ud83c', '\u27a1', '\u26a0']):
        return True
    # Frases muito curtas que parecem legenda de foto
    if len(texto) < 80 and ('foto' in texto_norm or 'imagem' in texto_norm or 'reproducao' in texto_norm):
        return True
    return False


def extrair_texto_folha(url):
    """Tenta extrair texto da Folha usando seletores especificos e versao AMP."""
    urls_tentar = [url]
    # Tentar versao AMP da Folha (menos restritiva)
    if 'folha.uol.com.br' in url and '/amp/' not in url:
        amp_url = url.replace('www1.folha.uol.com.br/', 'www1.folha.uol.com.br/amp/')
        if amp_url == url:
            # Tentar inserir /amp/ apos o dominio
            amp_url = url.replace('.uol.com.br/', '.uol.com.br/amp/', 1)
        urls_tentar.append(amp_url)

    for try_url in urls_tentar:
        try:
            response = requests.get(try_url, headers=HEADERS, timeout=15)
            response.raise_for_status()
            response.encoding = 'utf-8'
            soup = BeautifulSoup(response.text, 'lxml')

            # Seletores especificos da Folha
            conteudo = (
                soup.find('div', class_='c-news__body') or
                soup.find('div', class_='c-news__content') or
                soup.find('div', {'data-block-type': 'paragraph'}) or
                soup.find('article') or
                soup.find('div', class_=re.compile(r'(content-text|article-text|news-body)', re.I))
            )

            if conteudo:
                paragrafos = conteudo.find_all('p')
                textos = []
                for p in paragrafos:
                    t = p.get_text().strip()
                    if len(t) < 20:
                        continue
                    t_lower = t.lower()
                    if any(x in t_lower for x in ['assinante', 'assine', 'login', 'acessos por dia', 'recurso exclusivo', 'leia mais (']):
                        continue
                    if paragrafo_e_propaganda(t):
                        continue
                    textos.append(t)

                if len(textos) >= 3:
                    html_parts = []
                    # Primeiro paragrafo como headline
                    html_parts.append(
                        '<p style="text-align: justify;"><strong>'
                        + texto_para_entidades_html(textos[0])
                        + '</strong></p>'
                    )
                    for t in textos[1:]:
                        html_parts.append(
                            '<p style="text-align: justify;">'
                            + texto_para_entidades_html(t)
                            + '</p>'
                        )
                    resultado = '\n<br />\n'.join(html_parts)
                    if len(resultado) > 500:
                        logger.info(f"Folha: extraido {len(resultado)} chars via seletores especificos")
                        return pos_processar_html(resultado[:16000])

        except Exception as e:
            continue

    return ''


def pos_processar_html(html):
    """Limpa e padroniza o HTML final da noticia."""
    if not html:
        return html

    # Remover tags de publicidade
    html = re.sub(r'\[GOOGLE_ADSENSE\]', '', html)
    html = re.sub(r'\[AD\]', '', html)

    # Remover botoes de compartilhamento (WhatsAppFacebookTwitter/XCopiar link)
    html = re.sub(r'WhatsApp\s*Facebook\s*Twitter/?X?\s*Copiar\s*link', '', html, flags=re.IGNORECASE)
    html = re.sub(r'Compartilhar?\s*(no|via|em)?\s*(WhatsApp|Facebook|Twitter|X|LinkedIn|Telegram|Email)', '', html, flags=re.IGNORECASE)

    # Remover &nbsp; soltos
    html = html.replace('&nbsp;', ' ')

    # Remover atributos desnecessarios (class, data-*, etc) mas manter style
    html = re.sub(r'\s+class="[^"]*"', '', html)
    html = re.sub(r'\s+data-[a-z-]+="[^"]*"', '', html)

    # Remover links <a> mantendo o texto
    html = re.sub(r'<a[^>]*>(.*?)</a>', r'\1', html)

    # Remover listas (<ul>/<ol>) que contenham propaganda
    propaganda_li = [
        'sugest.*?reportagem', 'mande para o g1', 'mande para o uol',
        'siga o canal', 'siga no whatsapp', 'siga o g1', 'siga a folha',
        'inscreva-se', 'entre no canal', 'participe do canal',
        'baixe o app', 'download do app', 'receba notifica',
        'deixe seu coment', 'envie sua sugest', 'envie sua pauta',
    ]
    for padrao in propaganda_li:
        html = re.sub(r'<ul[^>]*>.*?' + padrao + r'.*?</ul>', '', html, flags=re.DOTALL|re.IGNORECASE)
        html = re.sub(r'<li[^>]*>.*?' + padrao + r'.*?</li>', '', html, flags=re.DOTALL|re.IGNORECASE)

    # Limpar listas vazias que sobraram
    html = re.sub(r'<ul[^>]*>\s*</ul>', '', html)
    html = re.sub(r'<ol[^>]*>\s*</ol>', '', html)

    # Garantir <br /> entre paragrafos: </p> seguido de <p> sem <br /> no meio
    html = re.sub(r'</p>\s*<p', '</p>\n<br />\n<p', html)

    # Remover paragrafos vazios ou so com espacos
    html = re.sub(r'<p[^>]*>\s*</p>', '', html)
    html = re.sub(r'<p[^>]*>\s*<br\s*/?>\s*</p>', '', html)

    # Remover <br /> duplicados
    html = re.sub(r'(<br\s*/?>\s*){2,}', '<br />', html)

    # Limpar espacos extras
    html = re.sub(r'  +', ' ', html)

    return html.strip()


def converter_tabela_html(table_elem):
    """Converte uma tag <table> do BeautifulSoup em tabela HTML simples e formatada."""
    rows = table_elem.find_all('tr')
    if not rows:
        return ''

    html = '<table style="border-collapse: collapse; width: 100%; margin: 10px 0;">'
    for i, row in enumerate(rows):
        cells = row.find_all(['th', 'td'])
        if not cells:
            continue
        html += '<tr>'
        for cell in cells:
            tag = 'th' if cell.name == 'th' or i == 0 else 'td'
            style = 'border: 1px solid #ccc; padding: 6px 10px; text-align: left;'
            if tag == 'th' or i == 0:
                style += ' background-color: #f0f0f0; font-weight: bold;'
            texto = texto_para_entidades_html(cell.get_text(strip=True))
            html += f'<{tag} style="{style}">{texto}</{tag}>'
        html += '</tr>'
    html += '</table>'
    return html


def converter_lista_html(list_elem):
    """Converte uma tag <ul> ou <ol> do BeautifulSoup em lista HTML formatada."""
    items = list_elem.find_all('li')
    if not items:
        return ''

    filtered_items = []
    for item in items:
        texto = item.get_text(strip=True)
        if len(texto) > 10 and not paragrafo_e_propaganda(texto):
            # Filtrar bylines e datas (ex: "PorJovem Pan", "02/03/2026 01h00")
            texto_lower = texto.lower()
            if re.match(r'^por\s*[A-Z]', texto, re.IGNORECASE):
                continue
            if re.match(r'^\d{2}/\d{2}/\d{4}\s+\d{2}h\d{2}', texto):
                continue
            filtered_items.append(texto)

    if not filtered_items:
        return ''

    tag = 'ol' if list_elem.name == 'ol' else 'ul'
    html = f'<{tag} style="text-align: justify; margin: 10px 0; padding-left: 25px;">'
    for texto in filtered_items:
        html += '<li>' + texto_para_entidades_html(texto) + '</li>'
    html += f'</{tag}>'
    return html


def extrair_texto_url(url):
    """Extrai texto completo de uma noticia pela URL, retornando HTML formatado."""
    # Tentar extracao especifica para Folha
    if 'folha.uol.com.br' in url:
        resultado = extrair_texto_folha(url)
        if resultado:
            return resultado

    try:
        response = requests.get(url, headers=HEADERS, timeout=15)
        response.raise_for_status()
        response.encoding = 'utf-8'
        soup = BeautifulSoup(response.text, 'lxml')

        for tag in soup.find_all(['script', 'style', 'nav', 'footer', 'header', 'aside', 'iframe']):
            tag.decompose()

        # Remover elementos de paywall/login
        for sel in soup.find_all(class_=re.compile(r'(paywall|signwall|login|subscriber|premium-content|gate)', re.I)):
            sel.decompose()
        for sel in soup.find_all('div', id=re.compile(r'(paywall|signwall|login)', re.I)):
            sel.decompose()

        # Tentar extrair subtitulo/headline
        subtitulo = ''
        for sel in [
            soup.find('h2', class_=re.compile(r'(subtitle|sub-title|chapeu|lead)', re.I)),
            soup.find('p', class_=re.compile(r'(subtitle|sub-title|lead|destaque)', re.I)),
            soup.find('meta', property='og:description'),
        ]:
            if sel:
                if sel.name == 'meta':
                    subtitulo = sel.get('content', '').strip()
                else:
                    subtitulo = sel.get_text().strip()
                if subtitulo and len(subtitulo) > 20:
                    break
                subtitulo = ''

        # Extrair conteudo do artigo
        article = (
            soup.find('article') or
            soup.find('div', class_=re.compile(r'(content|article|post|materia|texto|body)', re.I)) or
            soup.find('div', {'itemprop': 'articleBody'}) or
            soup.find('div', class_=re.compile(r'(entry-content|post-content)', re.I))
        )

        paragrafos_raw = []
        if article:
            # Extrair paragrafos, subtitulos e tabelas do artigo
            for elem in article.find_all(['p', 'h2', 'h3', 'h4', 'table', 'ul', 'ol']):
                # Tabelas: converter para HTML formatado
                if elem.name == 'table':
                    tabela_html = converter_tabela_html(elem)
                    if tabela_html:
                        paragrafos_raw.append(('tabela', tabela_html))
                    continue
                # Listas: converter para texto com marcadores
                if elem.name in ['ul', 'ol']:
                    lista_html = converter_lista_html(elem)
                    if lista_html:
                        paragrafos_raw.append(('lista', lista_html))
                    continue
                texto_elem = elem.get_text().strip()
                if len(texto_elem) < 20:
                    continue
                # Filtrar conteudo de paywall/assinatura
                texto_lower = texto_elem.lower()
                if any(t in texto_lower for t in [
                    'assinante', 'assine', 'assinatura',
                    'login', 'cadastre', 'acessos por dia',
                    'conteudo exclusivo', 'exclusivo para',
                    'faca seu login', 'entre ou cadastre',
                    'continue lendo', 'leia mais acessando',
                    'recurso exclusivo',
                ]):
                    continue
                if paragrafo_e_propaganda(texto_elem):
                    continue
                if elem.name in ['h2', 'h3', 'h4']:
                    paragrafos_raw.append(('subtitulo', texto_elem))
                else:
                    paragrafos_raw.append(('paragrafo', texto_elem))
        else:
            for p in soup.find_all('p'):
                texto_p = p.get_text().strip()
                if len(texto_p) > 40:
                    texto_lower = texto_p.lower()
                    if any(t in texto_lower for t in [
                        'assinante', 'assine', 'login', 'cadastre',
                        'acessos por dia', 'exclusivo para', 'recurso exclusivo',
                    ]):
                        continue
                    if not paragrafo_e_propaganda(texto_p):
                        paragrafos_raw.append(('paragrafo', texto_p))

        if not paragrafos_raw:
            return ''

        # Montar HTML formatado
        html_parts = []

        # Subtitulo/headline no topo em negrito
        if subtitulo:
            html_parts.append(
                '<p style="text-align: justify;"><strong>'
                + texto_para_entidades_html(subtitulo)
                + '</strong></p>'
            )

        for tipo, texto in paragrafos_raw:
            if tipo == 'tabela' or tipo == 'lista':
                # Tabelas e listas ja vem em HTML formatado
                html_parts.append(texto)
            elif tipo == 'subtitulo':
                texto_html = texto_para_entidades_html(texto)
                html_parts.append(
                    '<p style="text-align: justify;"><strong>'
                    + texto_html
                    + '</strong></p>'
                )
            else:
                texto_html = texto_para_entidades_html(texto)
                html_parts.append(
                    '<p style="text-align: justify;">'
                    + texto_html
                    + '</p>'
                )

        resultado = '\n<br />\n'.join(html_parts)
        resultado = pos_processar_html(resultado)
        return pos_processar_html(resultado[:16000]) if resultado else ''

    except Exception as e:
        logger.warning(f"Erro ao extrair texto de {url}: {e}")
        return ''


def extrair_imagem(entry, url=''):
    """Extrai URL da imagem de uma entrada RSS"""
    if hasattr(entry, 'media_content') and entry.media_content:
        for media in entry.media_content:
            if 'image' in media.get('type', '') or media.get('url', '').endswith(('.jpg', '.jpeg', '.png', '.webp')):
                return media['url']
    if hasattr(entry, 'media_thumbnail') and entry.media_thumbnail:
        return entry.media_thumbnail[0].get('url', '')
    if hasattr(entry, 'enclosures') and entry.enclosures:
        for enc in entry.enclosures:
            if 'image' in enc.get('type', ''):
                return enc.get('href', enc.get('url', ''))
    content = ''
    if hasattr(entry, 'content') and entry.content:
        content = entry.content[0].get('value', '')
    elif hasattr(entry, 'summary'):
        content = entry.summary or ''
    if content:
        soup = BeautifulSoup(content, 'lxml')
        img = soup.find('img')
        if img and img.get('src'):
            return img['src']
    return ''


def extrair_imagem_pagina(url):
    """Extrai imagem og:image ou twitter:image da pagina da noticia."""
    if not url:
        return ''
    try:
        resp = requests.get(url, headers=HEADERS, timeout=15)
        resp.raise_for_status()
        soup = BeautifulSoup(resp.text, 'lxml')

        # Tentar og:image (mais comum)
        og = soup.find('meta', property='og:image')
        if og and og.get('content'):
            return og['content']

        # Tentar twitter:image
        tw = soup.find('meta', attrs={'name': 'twitter:image'})
        if tw and tw.get('content'):
            return tw['content']

        # Tentar primeira imagem grande no article
        article = soup.find('article') or soup.find('div', class_=re.compile(r'(content|article|materia)', re.I))
        if article:
            img = article.find('img', src=True)
            if img and img.get('src'):
                src = img['src']
                if src.startswith('http') and any(ext in src.lower() for ext in ['.jpg', '.jpeg', '.png', '.webp']):
                    return src

        return ''
    except Exception as e:
        logger.warning(f"Erro ao extrair og:image de {url}: {e}")
        return ''


def noticia_relevante(titulo, resumo=''):
    """Verifica se a noticia e relevante usando o score de relevancia."""
    score = calcular_score_relevancia(titulo, resumo)
    return score >= MIN_SCORE


def carregar_titulos_recentes(cursor):
    """Carrega titulos dos ultimos dias para verificacao de similaridade."""
    cursor.execute(
        "SELECT titulo FROM noticias WHERE ativo = 1 AND data >= DATE_SUB(CURDATE(), INTERVAL %s DAY)",
        (DIAS_SEM_REPETIR,)
    )
    return [row['titulo'] for row in cursor.fetchall()]


def verificar_duplicata_bd(cursor, titulo, titulos_recentes=None):
    """Verifica duplicata exata e por similaridade no banco."""
    # 1. Verificacao exata (titulo igual)
    titulo_busca = titulo[:100]
    cursor.execute(
        "SELECT COUNT(*) as total FROM noticias WHERE titulo LIKE %s AND ativo = 1",
        (f"%{titulo_busca}%",)
    )
    result = cursor.fetchone()
    if result and result['total'] > 0:
        return True

    # 2. Verificacao por similaridade contra ultimos dias
    if titulos_recentes:
        for titulo_antigo in titulos_recentes:
            if titulos_similares(titulo, titulo_antigo):
                logger.info(f"Similar a noticia recente: '{titulo[:50]}' ~ '{titulo_antigo[:50]}'")
                return True

    return False


def gerar_resumo(texto, max_chars=250):
    """Gera resumo"""
    if not texto:
        return ''
    texto_limpo = limpar_html(texto)
    if len(texto_limpo) <= max_chars:
        return texto_limpo
    resumo = texto_limpo[:max_chars]
    ultimo_espaco = resumo.rfind(' ')
    if ultimo_espaco > 0:
        resumo = resumo[:ultimo_espaco]
    return resumo + '...'


def gerar_palavras_chave(titulo, resumo=''):
    """Gera palavras-chave"""
    texto = unicodedata.normalize('NFKD', f"{titulo} {resumo}".lower())
    texto = ''.join(c for c in texto if not unicodedata.combining(c))
    palavras_encontradas = []
    for palavra in PALAVRAS_INTERESSE:
        if palavra.lower() in texto:
            palavras_encontradas.append(palavra)
    return ', '.join(palavras_encontradas[:10])


def palavras_significativas(texto):
    """Extrai palavras significativas de um texto, removendo stopwords curtas."""
    import unicodedata
    texto_norm = unicodedata.normalize('NFKD', texto.lower())
    texto_norm = ''.join(c for c in texto_norm if not unicodedata.combining(c))
    palavras = re.findall(r'[a-z]{3,}', texto_norm)
    stopwords = {'que', 'para', 'com', 'por', 'uma', 'dos', 'das', 'nos', 'nas',
                 'tem', 'ser', 'foi', 'sao', 'como', 'mais', 'sobre', 'apos',
                 'pode', 'isso', 'esta', 'esse', 'pela', 'pelo', 'entre', 'tambem',
                 'ainda', 'quando', 'muito', 'sua', 'seu', 'ela', 'ele', 'nao',
                 'mas', 'diz', 'disse', 'vai', 'ano', 'dia', 'vez'}
    return set(p for p in palavras if p not in stopwords)


def titulos_similares(titulo1, titulo2):
    """Verifica se dois titulos tratam do mesmo assunto usando similaridade de palavras."""
    p1 = palavras_significativas(titulo1)
    p2 = palavras_significativas(titulo2)
    if not p1 or not p2:
        return False
    intersecao = p1 & p2
    menor = min(len(p1), len(p2))
    if menor == 0:
        return False
    similaridade = len(intersecao) / menor
    return similaridade >= 0.5


def deduplicar_noticias(noticias):
    """Remove noticias duplicadas sobre o mesmo assunto, mantendo a de maior score."""
    resultado = []
    for noticia in noticias:
        duplicada = False
        for aceita in resultado:
            if titulos_similares(noticia['titulo'], aceita['titulo']):
                logger.info(f"Dedup: removendo '{noticia['titulo'][:60]}' (similar a '{aceita['titulo'][:60]}')")
                duplicada = True
                break
        if not duplicada:
            resultado.append(noticia)
    return resultado


def calcular_score_relevancia(titulo, resumo=''):
    """Calcula score de relevancia baseado nas palavras-chave do universo do consumidor."""
    texto = unicodedata.normalize('NFKD', (titulo + ' ' + resumo).lower())
    texto = ''.join(c for c in texto if not unicodedata.combining(c))

    # Palavras negativas - assuntos que NAO interessam ao leitor
    negativos = [
        # Esportes
        'futebol', 'campeonato', 'brasileirao', 'libertadores', 'copa do mundo',
        'selecao brasileira', 'gol ', 'goleiro', 'artilheiro', 'rodada',
        'serie a', 'serie b', 'champions', 'premier league',
        'olimpiada', 'medalha de ouro', 'medalha de prata', 'medalha de bronze',
        'atletismo', 'natacao', 'tenis de mesa',
        'basquete', 'volei', 'formula 1',
        'grande premio', 'mma', 'ufc', 'boxe',
        # Times de futebol
        'corinthians', 'palmeiras', 'flamengo', 'fluminense',
        'botafogo', 'vasco', 'gremio', 'cruzeiro', 'atletico-mg',
        'atletico-pr', 'santos fc', 'sao paulo fc', 'internacional ',
        'novorizontino', 'ponte preta', 'guarani',
        # Campeonatos regionais
        'paulistao', 'cariocao', 'gauchao', 'mineirato',
        'semifinal', 'quartas de final', 'oitavas de final',
        'classico ', 'derby', 'derbi',
        # Entretenimento / Celebridades
        'bbb', 'big brother', 'reality show', 'novela',
        'oscar ', 'grammy', 'tapete vermelho',
        'show de ', 'turne',
        # Crime comum / Policial
        'homicidio', 'assassinato', 'assassino', 'latrocinio',
        'estupro', 'feminicidio', 'sequestro', 'trafico de drogas',
        'narcotraficante', 'tiroteio', 'bala perdida',
        'milicia', 'maus-tratos', 'maus tratos',
        'preso em flagrante', 'mandado de prisao',
        'corpo encontrado', 'desaparecido',
        # Politica eleitoral pura
        'reeleicao', 'campanha eleitoral', 'candidatura',
        'pesquisa eleitoral', 'intencao de voto',
        'cpmi', 'cpi ', 'quebra de sigilo', 'sigilo bancario',
        'impeachment', 'cassacao',
        # Desastres naturais
        'terremoto', 'tsunami', 'erupcao', 'vulcao',
        'furacao', 'tufao', 'ciclone',
        # Guerra / Conflitos
        'guerra ', 'conflito armado', 'bombardeio', 'missil',
        # Animais / Meio ambiente
        'zoologico', 'animal silvestre', 'girafa', 'bioparque',
        'desmatamento', 'queimada',
        # Mercado de capitais / Especulacao (nao interessa ao leitor comum)
        'bolsa de valores', 'ibovespa', 'b3 ', 'wall street', 'nasdaq',
        'acoes da ', 'acao da ', 'papel da ', 'pregao',
        'investidor', 'investidores', 'acionista',
        'startup', 'venture capital', 'ipo ',
        'criptomoeda', 'bitcoin', 'ethereum', 'blockchain',
        # Agronegocio / Commodities
        'commodities', 'commodity', 'arroba de boi', 'soja ', 'milho ',
        'barril de petroleo', 'opep',
        # Tecnologia generica
        'inteligencia artificial', 'tecnologia verde',
    ]

    # Peso alto - termos especificos de defesa do consumidor
    peso_alto = [
        'consumidor', 'procon', 'recall', 'codigo de defesa',
        'indenizacao', 'dano moral', 'danos morais',
        'cobranca indevida', 'cobranca abusiva', 'venda casada',
        'propaganda enganosa', 'publicidade enganosa',
        'negativacao', 'serasa', 'spc', 'nome limpo', 'nome sujo',
        'inadimplencia', 'superendividamento', 'renegociacao de divida',
        'plano de saude', 'operadora de saude', ' ans ',
        'anvisa', 'anatel', 'anac ', 'aneel',
        'direito do consumidor', 'relacao de consumo',
        'produto defeituoso', 'vicio do produto',
        'garantia estendida', 'assistencia tecnica',
        'prazo de entrega', 'compra online', 'e-commerce',
        'direito de arrependimento',
    ]

    # Peso medio-alto - fraudes e protecao de dados
    peso_medio_alto = [
        'fraude', 'golpe', 'estelionato', 'piramide financeira',
        'golpe do pix', 'golpe do whatsapp', 'golpe do boleto',
        'vazamento de dados', 'lgpd', 'dados pessoais', 'privacidade',
        'divida', 'endividamento', 'cobranca',
        'reajuste abusivo', 'aumento abusivo',
        'acao civil publica', 'acao coletiva',
        'multa aplicada', 'auto de infracao',
    ]

    # Peso medio - bolso do consumidor no dia a dia
    peso_medio = [
        'conta de luz', 'conta de agua', 'energia eletrica', 'tarifa de energia',
        'tarifa bancaria', 'juros abusivos', 'juros do cartao',
        'salario minimo', 'piso salarial',
        'aposentadoria', 'inss', 'previdencia',
        'fgts', ' pis', 'pasep', 'abono salarial',
        'imposto de renda', 'restituicao', 'isencao',
        'aluguel', 'reajuste do aluguel', 'igp-m', 'ipca',
        'condominio', 'financiamento imobiliario',
        'telefonia', 'banda larga', 'plano de celular',
        'passagem aerea', 'transporte publico',
        'gasolina', 'etanol', 'combustivel', 'preco do gas',
        'cesta basica', 'preco dos alimentos', 'supermercado',
        'medicamento', 'remedio', 'farmacia', 'generico',
        'emprestimo consignado', 'cartao de credito',
        'pix', 'conta digital',
        'seguro auto', 'seguro de vida', 'seguro residencial',
        'inflacao', 'custo de vida',
    ]

    # Peso baixo - economia e financas de interesse geral
    peso_baixo = [
        # Economia macro (interessa ao leitor)
        'economia', 'selic', 'copom', 'banco central',
        'reforma tributaria', 'carga tributaria',
        'aumento de imposto', 'imposto', 'tributacao',
        'pib ', 'politica monetaria', 'politica fiscal',
        'dolar', 'cambio', 'taxa de cambio',
        'deficit publico', 'divida publica',
        'balanca comercial', 'exportacao', 'importacao',
        'petroleo',
        # Termos genericos uteis com contexto
        'preco', 'reajuste', 'aumento',
        'reclamacao', 'denuncia', 'fiscalizacao',
        'produto', 'servico', 'empresa',
        'pagamento', 'tarifa', 'taxa', 'juros',
        'mercado', 'credito', 'banco',
        'justica', 'tribunal', 'decisao',
        'direito', 'lei ', 'decreto',
        'cartao', 'conta', 'seguro',
    ]

    score = 0

    # Verificar negativos primeiro
    for p in negativos:
        if p in texto:
            score -= 10

    if score <= -20:
        return score

    for p in peso_alto:
        if p in texto:
            score += 5
    for p in peso_medio_alto:
        if p in texto:
            score += 3
    for p in peso_medio:
        if p in texto:
            score += 2
    for p in peso_baixo:
        if p in texto:
            score += 1

    return score


def coletar_feeds():
    """Coleta notícias de todos os feeds RSS"""
    noticias = []
    cache = carregar_cache()

    for fonte_nome, config in FONTES_RSS.items():
        fonte_id = config['fonte_id']

        for feed_url in config['feeds']:
            logger.info(f"Coletando feed: {fonte_nome} - {feed_url}")
            try:
                feed = feedparser.parse(feed_url)

                if feed.bozo and not feed.entries:
                    logger.warning(f"Feed com erro: {feed_url}")
                    continue

                for entry in feed.entries[:15]:
                    try:
                        titulo = limpar_html(entry.get('title', ''))
                        if not titulo or len(titulo) < 10:
                            continue

                        link = entry.get('link', '')

                        hash_titulo = gerar_hash_titulo(titulo)
                        if link in cache['urls'] or hash_titulo in cache['titulos']:
                            continue

                        resumo_feed = ''
                        if hasattr(entry, 'summary'):
                            resumo_feed = limpar_html(entry.summary)
                        elif hasattr(entry, 'description'):
                            resumo_feed = limpar_html(entry.description)

                        if not noticia_relevante(titulo, resumo_feed):
                            continue

                        data_pub = None
                        if hasattr(entry, 'published_parsed') and entry.published_parsed:
                            data_pub = datetime(*entry.published_parsed[:6])
                        elif hasattr(entry, 'updated_parsed') and entry.updated_parsed:
                            data_pub = datetime(*entry.updated_parsed[:6])
                        else:
                            data_pub = datetime.now()

                        if data_pub < datetime.now() - timedelta(days=3):
                            continue

                        imagem_url = extrair_imagem(entry, link)

                        noticia = {
                            'titulo': sanitizar_latin1(titulo[:255]),
                            'chamada': sanitizar_latin1(titulo[:100]),
                            'resumo': sanitizar_latin1(gerar_resumo(resumo_feed)),
                            'texto': sanitizar_latin1(resumo_feed),
                            'link': link,
                            'fonte_nome': fonte_nome,
                            'fonte_id': fonte_id,
                            'data_publicacao': data_pub.strftime('%Y-%m-%d'),
                            'hora_publicacao': data_pub.strftime('%H:%M:%S'),
                            'imagem_url': imagem_url,
                            'hash_titulo': hash_titulo,
                        }

                        noticias.append(noticia)
                        cache['urls'].append(link)
                        cache['titulos'].append(hash_titulo)

                    except Exception as e:
                        logger.warning(f"Erro ao processar entrada: {e}")
                        continue

            except Exception as e:
                logger.error(f"Erro ao coletar feed {feed_url}: {e}")
                continue

    salvar_cache(cache)
    logger.info(f"Total de noticias relevantes coletadas: {len(noticias)}")
    # Ordenar por relevancia tematica (mais relevantes primeiro)
    for n in noticias:
        n['score'] = calcular_score_relevancia(n['titulo'], n.get('resumo', ''))
    noticias.sort(key=lambda x: x['score'], reverse=True)

    # Deduplicar noticias sobre o mesmo assunto de fontes diferentes
    noticias = deduplicar_noticias(noticias)

    # Filtrar por score minimo
    antes = len(noticias)
    noticias = [n for n in noticias if n['score'] >= MIN_SCORE]
    if antes > len(noticias):
        logger.info(f"Score minimo: {antes - len(noticias)} noticias descartadas (score < {MIN_SCORE})")

    logger.info("Top 5 por relevancia:")
    for n in noticias[:5]:
        logger.info("  [score=%d] %s" % (n['score'], n['titulo'][:70]))

    return noticias[:MAX_NOTICIAS]


def enriquecer_noticia(noticia):
    """Busca texto completo da noticia com formatacao HTML"""
    if noticia.get('link'):
        texto_completo = extrair_texto_url(noticia['link'])
        if texto_completo and '<p style' in texto_completo:
            # Texto veio formatado em HTML - sempre preferir
            noticia['texto'] = texto_completo
            if not noticia['resumo'] or len(noticia['resumo']) < 50:
                texto_limpo = limpar_html(texto_completo)
                noticia['resumo'] = texto_para_entidades_html(
                    sanitizar_latin1(gerar_resumo(texto_limpo))
                )
        elif texto_completo and len(texto_completo) > len(noticia.get('texto', '')):
            # Fallback: texto sem HTML mas maior que o RSS
            noticia['texto'] = texto_completo
    # Se o texto nao tem HTML, formatar o que temos do RSS
    if noticia.get('texto') and '<p style' not in noticia.get('texto', ''):
        texto_rss = noticia['texto']
        paragrafos = [p.strip() for p in texto_rss.split('\n') if p.strip()]
        if len(paragrafos) <= 1:
            # Texto sem quebra de linha - dividir por sentencas longas
            sentencas = re.split(r'(?<=[.!?])\s+', texto_rss)
            paragrafos = []
            bloco = ''
            for s in sentencas:
                bloco += s + ' '
                if len(bloco) > 200:
                    paragrafos.append(bloco.strip())
                    bloco = ''
            if bloco.strip():
                paragrafos.append(bloco.strip())
        html_parts = []
        for i, p in enumerate(paragrafos):
            p_html = texto_para_entidades_html(sanitizar_latin1(p))
            if i == 0 and len(paragrafos) > 1:
                html_parts.append('<p style="text-align: justify;"><strong>' + p_html + '</strong></p>')
            else:
                html_parts.append('<p style="text-align: justify;">' + p_html + '</p>')
        noticia['texto'] = '\n<br />\n'.join(html_parts)
    return noticia


def inserir_noticia(cursor, noticia):
    """Insere notícia no banco de dados"""
    agora = datetime.now()

    foto = noticia.get('foto', '') or ''
    foto_salva = noticia.get('foto_salva', '') or ''

    sql = """
        INSERT INTO noticias (
            chamada, titulo, texto, resumo, palavras_chave,
            autor_materia, fonte_id, data_publicacao, hora_publicacao,
            data, hora, ativo, area_id,
            user_id, destaque, img_newsletter,
            foto, foto_salva,
            sis_user_criar, sis_data_criar, sis_hora_criar,
            sis_user_editar, sis_data_editar, sis_hora_editar
        ) VALUES (
            %s, %s, %s, %s, %s,
            %s, %s, %s, %s,
            %s, %s, 1, 0,
            1, 0, 0,
            %s, %s,
            1, %s, %s,
            0, %s, %s
        )
    """

    params = (
        sanitizar_latin1(noticia['chamada']),
        sanitizar_latin1(noticia['titulo']),
        noticia.get('texto', ''),
        sanitizar_latin1(noticia.get('resumo', '')),
        gerar_palavras_chave(noticia['titulo'], noticia.get('resumo', '')),
        noticia['fonte_nome'],
        noticia['fonte_id'],
        noticia['data_publicacao'],
        noticia['hora_publicacao'],
        agora.strftime('%Y-%m-%d'),
        agora.strftime('%H:%M:%S'),
        foto,
        foto_salva,
        agora.strftime('%Y-%m-%d'),
        agora.strftime('%H:%M:%S'),
        agora.strftime('%Y-%m-%d'),
        agora.strftime('%H:%M:%S'),
    )

    cursor.execute(sql, params)
    return cursor.lastrowid


def buscar_termo_imagem(titulo, resumo=''):
    """Determina o melhor termo de busca de imagem baseado nas palavras da noticia."""
    texto = unicodedata.normalize('NFKD', (titulo + ' ' + resumo).lower())
    texto = ''.join(c for c in texto if not unicodedata.combining(c))

    melhor_match = None
    melhor_count = 0

    for palavras, termo_busca in CATEGORIAS_IMAGEM:
        count = sum(1 for p in palavras if p in texto)
        if count > melhor_count:
            melhor_count = count
            melhor_match = termo_busca

    return melhor_match or IMAGEM_PADRAO


def buscar_imagem_stock(titulo, resumo=''):
    """Busca imagem stock gratuita relacionada ao tema da noticia via LoremFlickr."""
    termo = buscar_termo_imagem(titulo, resumo)
    url = f"https://loremflickr.com/800/600/{termo}"

    try:
        resp = requests.get(url, headers=HEADERS, timeout=15, allow_redirects=True)
        resp.raise_for_status()

        content_type = resp.headers.get('Content-Type', '')
        if 'image' not in content_type:
            logger.warning(f"LoremFlickr nao retornou imagem: {content_type}")
            return None

        if len(resp.content) < 5000:
            logger.warning(f"Imagem stock muito pequena ({len(resp.content)}b)")
            return None

        logger.info(f"Imagem stock encontrada: termo='{termo}' ({len(resp.content)//1024}KB)")
        return resp.content

    except Exception as e:
        logger.warning(f"Erro ao buscar imagem stock: {e}")
        return None


def salvar_imagem_url(url_imagem, indice):
    """Baixa imagem da URL e salva no diretorio de imagens do site.
    Retorna (foto, foto_salva) ou (None, None) se falhar."""
    if not url_imagem:
        return None, None
    try:
        resp = requests.get(url_imagem, headers=HEADERS, timeout=15, stream=True)
        resp.raise_for_status()

        content_type = resp.headers.get('Content-Type', '')
        if 'image' not in content_type and not url_imagem.lower().endswith(('.jpg', '.jpeg', '.png', '.webp', '.gif')):
            return None, None

        ext = '.png' if ('png' in content_type or url_imagem.lower().endswith('.png')) else '.jpg'

        agora = datetime.now()
        foto_salva = agora.strftime('%Y%m%d%H%M%S') + str(indice % 10) + ext
        foto_path = os.path.join(IMAGES_DIR, foto_salva)

        with open(foto_path, 'wb') as f:
            for chunk in resp.iter_content(chunk_size=8192):
                f.write(chunk)

        os.chmod(foto_path, 0o644)
        file_size = os.path.getsize(foto_path)

        if file_size < 1000:
            os.remove(foto_path)
            return None, None

        foto_nome = os.path.basename(urlparse(url_imagem).path) or foto_salva
        logger.info(f"Imagem salva: {foto_salva} ({file_size//1024}KB)")
        return sanitizar_latin1(foto_nome[:200]), foto_salva

    except Exception as e:
        logger.warning(f"Erro ao baixar imagem {url_imagem}: {e}")
        return None, None


def criar_edicao_homepage(conn, noticias_ids):
    """Cria edicao da homepage (noticias_home + noticias_home_detalhes).

    Distribui as noticias:
    - 1a noticia: DESTAQUE_1 (home=1) - destaque principal grande
    - 2a: DESTAQUE_2 (home=2) - card secundario
    - restante: DESTAQUE_N (home=3) - lista de ultimas noticias
    """
    if not noticias_ids:
        return

    DESTAQUE_1 = 1
    DESTAQUE_2 = 2
    DESTAQUE_N = 3

    try:
        conn.ping(reconnect=True, attempts=3, delay=1)
    except:
        pass

    cursor = conn.cursor(dictionary=True)
    agora = datetime.now()
    data_hoje = agora.strftime('%Y-%m-%d')
    hora_agora = agora.strftime('%H:%M:%S')

    # Verificar se ja existe edicao para hoje
    cursor.execute(
        "SELECT id FROM noticias_home WHERE data = %s AND ativo = 1",
        (data_hoje,)
    )
    existente = cursor.fetchone()

    if existente:
        # Ja existe edicao hoje - remover detalhes antigos e recriar
        noticias_home_id = existente['id']
        cursor.execute(
            "DELETE FROM noticias_home_detalhes WHERE noticias_home_id = %s",
            (noticias_home_id,)
        )
        logger.info(f"Edicao homepage #{noticias_home_id} ja existia, atualizando...")
    else:
        # Criar nova edicao
        cursor.execute(
            "INSERT INTO noticias_home (data, ativo, usuario_id, data_cadastro, hora_cadastro) "
            "VALUES (%s, 1, 1, %s, %s)",
            (data_hoje, data_hoje, hora_agora)
        )
        noticias_home_id = cursor.lastrowid
        logger.info(f"Nova edicao homepage #{noticias_home_id} criada para {data_hoje}")

    # Distribuir noticias por secao
    # Com 2 imagens: ambas em DESTAQUE_2 (lado a lado, mesmo tamanho)
    # Com 3 imagens: 1 em DESTAQUE_1 (grande) + 2 em DESTAQUE_2 (menores)
    n_imgs = NOTICIAS_COM_IMAGEM
    count_d1 = 0
    count_d2 = 0
    count_dn = 0
    for ordem, noticia_id in enumerate(noticias_ids):
        if n_imgs == 2:
            # 2 imagens: ambas vao para DESTAQUE_2 (lado a lado)
            if ordem <= 1:
                home = DESTAQUE_2
                count_d2 += 1
            else:
                home = DESTAQUE_N
                count_dn += 1
        else:
            # 3 imagens: 1 grande + 2 menores
            if ordem == 0:
                home = DESTAQUE_1
                count_d1 += 1
            elif ordem <= 2:
                home = DESTAQUE_2
                count_d2 += 1
            else:
                home = DESTAQUE_N
                count_dn += 1

        cursor.execute(
            "INSERT INTO noticias_home_detalhes (noticias_home_id, noticia_id, ordem, home) "
            "VALUES (%s, %s, %s, %s)",
            (noticias_home_id, noticia_id, ordem, home)
        )

    conn.commit()
    cursor.close()
    logger.info(f"Homepage atualizada: {count_d1} destaque principal, {count_d2} cards, {count_dn} na lista")


def main():
    """Função principal"""
    logger.info("=" * 60)
    logger.info("Iniciando coleta automatica de noticias")
    logger.info("=" * 60)

    # 1. Coletar notícias dos feeds RSS
    noticias = coletar_feeds()

    if not noticias:
        logger.info("Nenhuma noticia nova encontrada.")
        return

    # 2. Conectar ao banco de dados
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor(dictionary=True)
    except Exception as e:
        logger.error(f"Erro ao conectar ao banco: {e}")
        return

    inseridas = 0
    duplicadas = 0
    erros = 0
    noticias_inseridas_ids = []

    # Carregar titulos recentes para verificacao de similaridade
    titulos_recentes = carregar_titulos_recentes(cursor)
    logger.info(f"Titulos recentes carregados: {len(titulos_recentes)} dos ultimos {DIAS_SEM_REPETIR} dias")

    try:
        for idx, noticia in enumerate(noticias):
            try:
                if verificar_duplicata_bd(cursor, noticia['titulo'], titulos_recentes):
                    duplicadas += 1
                    continue

                noticia = enriquecer_noticia(noticia)

                # Verificar se o texto tem tamanho minimo
                texto_limpo = limpar_html(noticia.get('texto', ''))
                if len(texto_limpo) < MIN_TEXTO_CHARS:
                    logger.warning(f"Texto muito curto ({len(texto_limpo)} chars), pulando: {noticia['titulo'][:60]}")
                    continue

                # Baixar imagem para as noticias de destaque (top 3)
                if idx < NOTICIAS_COM_IMAGEM:
                    img_url = noticia.get('imagem_url', '')
                    # Se RSS nao tem imagem, buscar og:image da pagina
                    if not img_url and noticia.get('link'):
                        img_url = extrair_imagem_pagina(noticia['link'])
                    if img_url:
                        foto, foto_salva = salvar_imagem_url(img_url, idx)
                        if foto and foto_salva:
                            noticia['foto'] = foto
                            noticia['foto_salva'] = foto_salva
                
                # Reconectar se a conexao caiu
                try:
                    conn.ping(reconnect=True, attempts=3, delay=1)
                except:
                    conn = mysql.connector.connect(**DB_CONFIG)
                    cursor = conn.cursor(dictionary=True)
                
                noticia_id = inserir_noticia(cursor, noticia)
                conn.commit()
                inseridas += 1
                noticias_inseridas_ids.append(noticia_id)
                logger.info(f"[+] #{noticia_id} - {noticia['titulo'][:80]} ({noticia['fonte_nome']})")

            except Exception as e:
                erros += 1
                logger.error(f"Erro: '{noticia.get('titulo', '')[:50]}': {e}")
                continue

    except Exception as e:
        pass
        logger.error(f"Erro geral: {e}")

    logger.info("-" * 60)
    logger.info(f"Resumo: {inseridas} inseridas | {duplicadas} duplicadas | {erros} erros")

    # 3. Criar edicao da homepage com as noticias inseridas
    if noticias_inseridas_ids:
        try:
            conn.ping(reconnect=True, attempts=3, delay=1)
        except:
            conn = mysql.connector.connect(**DB_CONFIG)
        criar_edicao_homepage(conn, noticias_inseridas_ids)

    cursor.close()
    conn.close()
    logger.info("Coleta finalizada.")


if __name__ == '__main__':
    main()
