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
import shutil
import subprocess
import unicodedata

import feedparser


def remover_acentos(texto):
    """Remove acentos de uma string."""
    nfkd = unicodedata.normalize('NFKD', texto)
    return ''.join(c for c in nfkd if not unicodedata.combining(c))
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
    # R7 removido - feed fora do ar
    # CNN Brasil removido - feed fora do ar
    'Agencia Brasil': {
        'fonte_id': 183,
        'feeds': [
            'https://agenciabrasil.ebc.com.br/rss/ultimasnoticias/feed.xml',
        ]
    },
    'Conjur': {
        'fonte_id': 184,
        'feeds': [
            'https://www.conjur.com.br/rss.xml',
        ]
    },
    'Jovem Pan': {
        'fonte_id': 155,
        'feeds': [
            'https://jovempan.com.br/feed',
        ]
    },
    # Valor Economico removido - paywall impede extracao do conteudo completo
    # STJ removido - feed bloqueado por WAF/firewall
    'TRF4': {
        'fonte_id': 181,
        'feeds': [
            'https://www.trf4.jus.br/trf4/noticias.xml',
        ]
    },
    'CJF': {
        'fonte_id': 182,
        'feeds': [
            'https://www.cjf.jus.br/cjf/noticias/ultimas-noticias/RSS',
        ]
    },
    'Valor Economico': {
        'fonte_id': 125,
        'feeds': [
            'https://pox.globo.com/rss/valor/',
        ]
    },
    'CNN Brasil': {
        'fonte_id': 148,
        'feeds': [
            'https://www.cnnbrasil.com.br/rss/',
        ]
    },
    'Metropoles': {
        'fonte_id': 161,
        'feeds': [
            'https://www.metropoles.com/feed',
        ]
    },
    'BBC Brasil': {
        'fonte_id': 55,
        'feeds': [
            'https://www.bbc.com/portuguese/index.xml',
        ]
    },
    'InfoMoney': {
        'fonte_id': 24,
        'feeds': [
            'https://www.infomoney.com.br/feed/',
        ]
    },
    'Exame': {
        'fonte_id': 13,
        'feeds': [
            'https://exame.com/feed/',
        ]
    },
    'Poder360': {
        'fonte_id': 162,
        'feeds': [
            'https://www.poder360.com.br/feed/',
        ]
    },
    'Procon SP': {
        'fonte_id': 36,
        'feeds': [
            'https://www.procon.sp.gov.br/rss/',
        ]
    },
    'Correio Braziliense': {
        'fonte_id': 190,
        'feeds': [
            'https://www.correiobraziliense.com.br/feed/',
        ]
    },
    'Carta Capital': {
        'fonte_id': 191,
        'feeds': [
            'https://www.cartacapital.com.br/feed/',
        ]
    },
    # ISTOÉ Dinheiro removido - feed RSS desativado pelo site
    'R7': {
        'fonte_id': 58,
        'feeds': [
            'https://noticias.r7.com/arc/outboundfeeds/rss/',
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
    'dolar',
]

MAX_NOTICIAS = 15
MAX_POR_FONTE = 3   # Maximo de noticias por fonte para diversificar
MIN_NOTICIAS = 6  # Minimo de noticias por coleta
MIN_TEXTO_CHARS = 500
DIAS_SEM_REPETIR = 5  # Nao repetir noticias similares dos ultimos N dias
MIN_SCORE = 2  # Score minimo para aceitar a noticia (0 = sem relacao com o tema)

# Temas complementares para completar quando houver poucas noticias
TEMAS_COMPLEMENTARES = [
    'inss', 'aposentadoria', 'pensao', 'beneficio',
    'bolsa familia', 'bolsa fam', 'auxilio brasil',
    'bpc', 'loas', 'seguro desemprego',
    'concurso', 'concurso publico',
    'salario minimo', 'piso salarial',
    'fgts', 'pis', 'pasep', 'abono salarial',
    'caixa tem', 'calendario de pagamento',
]
HEADERS = {
    'User-Agent': 'Mozilla/5.0 (compatible; SOSConsumidorBot/1.0)'
}

IMAGES_DIR = '/home/user/web/sosconsumidor.com.br/public_html/images/'
# Biblioteca local de imagens da RevisaAqui (sincronizada do Mac via rsync 07:50)
REVISAAQUI_DIR = '/home/user/scripts/sosconsumidor-automacao/revisaaqui_images/'
# Quantas noticias de destaque D2 devem ter imagem
NOTICIAS_COM_IMAGEM = 2

# Padronizacao das fotos. A caixa do destaque no site e 420x280 (proporcao 1.5);
# salvamos em 2x para nao ficar mole em tela retina.
FOTO_LARGURA = 840
FOTO_ALTURA = 560
FOTO_LARGURA_MIN = 500      # abaixo disso a foto borra ao ser ampliada na caixa
FOTO_PROPORCAO_MIN = 1.2    # mais alta que isso e retrato: o corte central come a imagem
FOTO_PROPORCAO_MAX = 2.4    # mais larga que isso costuma ser tarja/logo, nao foto

# Padrão de feed de telejornal/TV — não publicar. Aplicado em título normalizado (sem acento, lower).
RE_FEED_TV = re.compile(
    r'assista\s+a\s+integra'
    r'|\bjr\s*24\s*(?:horas|h)\b'
    r'|confira\s+nesta\s+edicao'
    r'|\d+\s*[aºo]?\s+edicao\s+do\b'
    r'|o\s+assunto\s*#?\d+'
    r'|\bjr\s*mundo\b'
    r'|jornal\s+da\s+record'
    r'|integra\s+do\s+jornal\s+da\s+record',
    re.IGNORECASE
)



# Padrão de resultado financeiro de empresa — não publicar. Ex: "MRV&Co tem alta de 3,5% nas vendas no 2º tri"
RE_RESULTADO_EMPRESA = re.compile(
    r'(?:tem|registra|apresenta|reporta|anuncia)\s+(?:alta|queda|crescimento|recuo|aumento|redu\w+)\s+de\s+\d'
    r'|vendas?\s+de\s+incorpora\w+'
    r'|\d+[oº]\s*tri(?:mestre)?.*vendas?'
    r'|vendas?.*\d+[oº]\s*tri(?:mestre)?'
    r'|resultado[s]?\s+do\s+\d+[oº]\s*tri'
    r'|lucro\s+(?:l[íi]quido|bruto|operacional).{0,40}(?:sobe|cai|cresce|recua|avan\w+)',
    re.IGNORECASE
)
def eh_feed_tv(titulo):
    """True se o título parece feed/chamada de telejornal."""
    if not titulo:
        return False
    return bool(RE_FEED_TV.search(remover_acentos(titulo).lower()))

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
    # Converter aspas tipograficas para aspas normais
    texto = texto.replace('“', '"').replace('”', '"')
    texto = texto.replace('‘', "'").replace('’', "'")
    texto = texto.replace('–', '-').replace('—', '-')
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
    'receba noticias',
    'fique por dentro de tudo',
    'basta acessar o canal',
    'canal de noticias do',
    'canal de noticias no',
    'metropoles no whatsapp',
    'metropoles no telegram',
    'compartilhar noticia',
    'baixe o app',
    'baixe nosso app',
    'download do app',
    'reporter de',
    'repórter de',
    'publicado em',
    'atualizado em',
    'por redacao',
    'por redação',
    'continua depois da publicidade',
    'continue lendo',
    'the post',
    'appeared first on',
    'plantao de ultimas noticias',
    'videos com as noticias',
    'veja o plantao',
    'tudo sobre',
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
    # Elementos de UI/acessibilidade de sites
    'alto contraste',
    'modo escuro',
    'aumentar fonte',
    'diminuir fonte',
    'tamanho da fonte',
    'acessibilidade',
    # Metadados editoriais que vazam no texto
    'noticias & releases',
    'noticias &amp; releases',
    'leia aqui o resumo',
    'confira imagens',
    'confira as imagens',
    'veja as fotos',
    'veja fotos',
    'confira a galeria',
    # Legendas repetidas de imagens
    'viagem de luxo feita por',
    'obra de arte encontrada',
    # Assinaturas/creditos
    'coluna buscou contato',
    'a reportagem buscou contato',
    'procurada pela reportagem',
    'a coluna procurou',
    'nao houve retorno',
    # R7 - textos promocionais
    'adicione como fonte preferencial',
    'adicione o r7',
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
                    resultado = '\n'.join(html_parts)
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

    # Remover rodapes promocionais de portais (ex: "Leia outras noticias do estado no g1 Roraima.")
    html = re.sub(r'<p[^>]*>\s*Leia outras not[^<]*</p>', '', html, flags=re.IGNORECASE)
    html = re.sub(r'<p[^>]*>\s*Veja (mais|outras) not[^<]*no g1[^<]*</p>', '', html, flags=re.IGNORECASE)
    html = re.sub(r'<p[^>]*>\s*Acompanhe (o |as |a )?not[^<]*(g1|folha|estadao|r7|cnn)[^<]*</p>', '', html, flags=re.IGNORECASE)

    # Remover CTAs de WhatsApp/Telegram de portais
    html = re.sub(r'<ul[^>]*>\s*<li[^>]*>\s*Clique aqui e entre no grupo[^<]*</li>\s*</ul>', '', html, flags=re.IGNORECASE)
    html = re.sub(r'<(ul|li|p)[^>]*>[^<]*(entre no grupo|nosso canal).*(WhatsApp|Telegram)[^<]*</(ul|li|p)>', '', html, flags=re.IGNORECASE)

    # Remover elementos de UI/acessibilidade de sites fonte
    html = re.sub(r'<(ul|li|p|span|div)[^>]*>[^<]*(Alto\s+contraste|Modo\s+escuro|Aumentar\s+fonte|Diminuir\s+fonte|Tamanho\s+da\s+fonte)[^<]*</(ul|li|p|span|div)>', '', html, flags=re.IGNORECASE)

    # Remover metadados editoriais
    html = re.sub(r'<(p|li|strong)[^>]*>[^<]*(not[ií]cias?\s*&amp;\s*releases|not[ií]cias?\s*&\s*releases)[^<]*</(p|li|strong)>', '', html, flags=re.IGNORECASE)
    html = re.sub(r'<(p|strong)[^>]*>\s*(LEIA\s+AQUI\s+O\s+RESUMO|RESUMO\s+DA\s+NOT[IÍ]CIA)[^<]*</(p|strong)>', '', html, flags=re.IGNORECASE)
    html = re.sub(r'<(p|li)[^>]*>[^<]*(Confira\s+(as\s+)?imagens|Veja\s+(as\s+)?fotos)[^<]*</(p|li)>', '', html, flags=re.IGNORECASE)
    html = re.sub(r'<(p|span)[^>]*>\s*Publicado\s+em\s+[^<]{0,80}</(p|span)>', '', html, flags=re.IGNORECASE)

    # Remover legendas de fotos repetidas (mesmo texto em sequência)
    html = re.sub(r'(<p[^>]*>[^<]{5,80}</p>)\s*(\1\s*){2,}', r'\1', html)

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
        'globopop', 'isso é fantástico', 'isso e fantastico',
    ]
    for padrao in propaganda_li:
        html = re.sub(r'<ul[^>]*>.*?' + padrao + r'.*?</ul>', '', html, flags=re.DOTALL|re.IGNORECASE)
        html = re.sub(r'<li[^>]*>.*?' + padrao + r'.*?</li>', '', html, flags=re.DOTALL|re.IGNORECASE)

    # Limpar listas vazias que sobraram
    html = re.sub(r'<ul[^>]*>\s*</ul>', '', html)
    html = re.sub(r'<ol[^>]*>\s*</ol>', '', html)

    # Remover paragrafos vazios ou so com espacos
    html = re.sub(r'<p[^>]*>\s*</p>', '', html)
    html = re.sub(r'<p[^>]*>\s*<br\s*/?>\s*</p>', '', html)

    # Remover <br /> entre paragrafos (espacamento ja vem do <p>)
    html = re.sub(r'</p>\s*<br\s*/?>\s*<p', '</p>\n<p', html)

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

    # Detectar listas que sao apenas links para outras materias
    links_count = sum(1 for item in items if item.find('a'))
    if links_count == len(items) and len(items) >= 2:
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

        # Fallback: se article existe mas sem paragrafos, buscar <p> na pagina toda
        if not paragrafos_raw and article:
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

        resultado = '\n'.join(html_parts)
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
        "SELECT titulo FROM noticias WHERE data >= DATE_SUB(CURDATE(), INTERVAL %s DAY)",
        (DIAS_SEM_REPETIR,)
    )
    return [row['titulo'] for row in cursor.fetchall()]


def verificar_duplicata_bd(cursor, titulo, titulos_recentes=None):
    """Verifica duplicata exata e por similaridade no banco."""
    # 1. Verificacao exata (titulo igual)
    titulo_busca = titulo[:100]
    cursor.execute(
        "SELECT COUNT(*) as total FROM noticias WHERE titulo LIKE %s",
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


def limpar_resumo(texto):
    """Remove padrões indesejados do resumo (metadados editoriais, UI etc)."""
    if not texto:
        return texto
    # Remover metadados editoriais comuns
    texto = re.sub(r'not[ií]cias?\s*&\s*releases\s*', '', texto, flags=re.IGNORECASE)
    texto = re.sub(r'Publicado\s+em\s+[^,.]{1,50}(,\s*\w+\s+de\s+\d{4}\s*[-–]?\s*)?', '', texto, flags=re.IGNORECASE)
    texto = re.sub(r'Atualizado\s+em\s+\d{1,2}/\d{1,2}/\d{2,4}\s*', '', texto, flags=re.IGNORECASE)
    texto = re.sub(r'LEIA\s+AQUI\s+O\s+RESUMO\s+DA\s+NOT[IÍ]CIA\s*', '', texto, flags=re.IGNORECASE)
    texto = re.sub(r'Alto\s+contraste\s*', '', texto, flags=re.IGNORECASE)
    # Limpar espaços extras
    texto = re.sub(r'\s+', ' ', texto).strip()
    # Remover traço ou hífen no início
    texto = re.sub(r'^[-–—]\s*', '', texto)
    return texto


def gerar_resumo(texto, max_chars=250):
    """Gera resumo cortando na ultima frase completa"""
    if not texto:
        return ""
    texto_limpo = limpar_html(texto)
    texto_limpo = limpar_resumo(texto_limpo)
    if len(texto_limpo) <= max_chars:
        return texto_limpo
    trecho = texto_limpo[:max_chars]
    ultimo_ponto = max(trecho.rfind(". "), trecho.rfind("! "), trecho.rfind("? "))
    if ultimo_ponto > 80:
        return trecho[:ultimo_ponto + 1]
    ultimo_espaco = trecho.rfind(" ")
    if ultimo_espaco > 0:
        trecho = trecho[:ultimo_espaco]
    return trecho + "..."


def gerar_palavras_chave(titulo, resumo=""):
    """Gera palavras-chave"""
    texto = unicodedata.normalize("NFKD", f"{titulo} {resumo}".lower())
    texto = "".join(c for c in texto if not unicodedata.combining(c))
    palavras_encontradas = []
    for palavra in PALAVRAS_INTERESSE:
        if palavra.lower() in texto:
            palavras_encontradas.append(palavra)
    return ", ".join(palavras_encontradas[:10])



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


def extrair_entidades(texto):
    """Extrai nomes proprios (empresas, pessoas) de um titulo."""
    import unicodedata
    # Palavras que comecam com maiuscula (exceto inicio de frase)
    palavras = texto.split()
    entidades = set()
    for i, p in enumerate(palavras):
        # Limpar pontuacao
        limpo = re.sub(r'[^a-zA-ZÀ-ú]', '', p)
        if not limpo or len(limpo) < 3:
            continue
        # Palavra com maiuscula que nao esta no inicio da frase
        if limpo[0].isupper() and i > 0:
            # Normalizar removendo acentos
            norm = unicodedata.normalize('NFKD', limpo.lower())
            norm = ''.join(c for c in norm if not unicodedata.combining(c))
            entidades.add(norm)
    return entidades


def titulos_similares(titulo1, titulo2):
    """Verifica se dois titulos tratam do mesmo assunto.
    Usa duas estrategias:
    1. Similaridade de palavras (limiar 0.45)
    2. Entidades em comum (empresas/pessoas) + tema similar (limiar 0.3)
    """
    p1 = palavras_significativas(titulo1)
    p2 = palavras_significativas(titulo2)
    if not p1 or not p2:
        return False
    intersecao = p1 & p2
    menor = min(len(p1), len(p2))
    if menor == 0:
        return False
    similaridade = len(intersecao) / menor

    # Similaridade alta = duplicata certa
    if similaridade >= 0.45:
        return True

    # Entidades em comum (empresas, pessoas) + alguma similaridade de tema
    ent1 = extrair_entidades(titulo1)
    ent2 = extrair_entidades(titulo2)
    entidades_comuns = ent1 & ent2
    if entidades_comuns and similaridade >= 0.25:
        return True

    return False


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
        # Propaganda / Clickbait financeiro
        'renda passiva', 'ganhe dinheiro', 'fique rico',
        'por mes em renda', 'mil por mes', 'trabalhar de casa',
        'segredo para', 'metodo infalivel', 'hack financeiro',
        'aqui vai o meu', 'como eu ganho', 'como ela ganha',
        'como ele ganha', 'dicas para ficar rico',
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
        # Geopolitica / Internacional (sem impacto direto no consumidor BR)
        'putin', 'zelensky', 'netanyahu', 'xi jinping', 'kim jong',
        'otan ', 'pentagono', 'kremlin', 'casa branca',
        'parlamento europeu', 'uniao europeia',
        'coreia do norte', 'afeganistao', 'taliba',
        'exercito americano', 'exercito russo', 'exercito israelense',
        'apagao em cuba', 'conflito no ira', 'crise na ucrania',
        'gaza', 'faixa de gaza', 'cisjordania',
        # Paises / regioes internacionais (bloqueio direto)
        ' cuba ', ' iran', 'iraque', 'ucrania',
        'palestina', ' israel', ' siria',
        'venezuela', 'colombia', 'mexico ',
        'china ', 'russia ', 'japao ',
        # Entretenimento extra
        'celebridade', 'celebridades', 'famosos',
        'red carpet', 'tapete vermelho',
        # Esportes extras
        'rugby', 'cricket', 'surfe competitivo',
        'copa america', 'eurocopa',
        # Propaganda / Conteudo promocional / Institucional
        'caminhada ecologica', 'caminhada solidaria',
        'orquestra apresenta', 'trilha sonora',
        'faculdade realiza', 'universidade realiza', 'instituto realiza',
        'evento beneficente', 'feira de', 'palestra sobre',
        'workshop de', 'seminario de',
        'premio nacional por obra', 'artista de',
        # Acidentes / Tragédias pessoais (não são direito do consumidor)
        'corpo queimado', 'queimadura', 'incendio residencial',
        'afogamento', 'afogado', 'afogou',
        'atropelamento', 'atropelado',
        'acidente de transito', 'acidente na rodovia', 'capotamento',
        'desabamento', 'desmoronamento', 'soterrado',
        'queda fatal', 'morte por queda',
        'esfaqueado', 'esfaqueamento', 'facada',
        'baleado', 'tiro ', 'tiros ',
        'morre ', 'morreu ', 'morte de ', 'faleceu', 'falecimento',
        'sepultado', 'sepultamento', 'cemiterio', 'velorio',
        'vitima fatal', 'obito',
        'andador', 'cadeira de rodas',
        # Processo criminal / Penal (não é consumidor)
        'crime de maio', 'crimes de maio',
        'prescricao dos crimes', 'prescritibilidade',
        'obices processuais', 'recurso especial',
        'pena de prisao', 'regime fechado', 'regime semiaberto',
        'condenado a ', 'reu ', 'reus ',
        'juri popular', 'tribunal do juri',
        # Ditadura / Historia politica
        'ditadura', 'ditatura', 'regime militar', 'regime autoritario',
        'torturada', 'torturado', 'tortura ',
        'exilada', 'exilado', 'exilio politico',
        'perseguicao politica', 'preso politico',
        'anistia politica',
        # Terrorismo / Ameacas
        'bomba ', 'ameaca de bomba', 'suspeita de bomba',
        'artefato explosivo', 'explosivo',
        'terrorismo', 'terrorista',
        # Seguranca publica / Policia
        'operacao policial', 'policia federal',
        'mandado de busca', 'varredura',
        'desaparecida por dia', 'desaparecidas por dia',
        # LGBT / Costumes (fora do escopo)
        'paradas lgbt', 'parada lgbt', 'orgulho gay',
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
        'desconto indevido', 'descontos no inss',
        'produto recolhido', 'recall de',
        'taxa abusiva', 'clausula abusiva',
        'reembolso', 'ressarcimento', 'restituicao',
        'bloqueio judicial', 'penhora', 'busca e apreensao',
        'bolsa familia', 'beneficio social',
        'golpe do pix', 'pix falso',
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
        'conta de celular', 'plano de celular', 'cobranca de operadora',
        'cancelamento de voo', 'atraso de voo', 'extravio de bagagem',
        'erro medico', 'negativa de cobertura', 'carencia',
        'emprestimo consignado', 'consignado privado',
        'demora no atendimento', 'fila de espera',
        'pix falso', 'falso boleto',
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
        'balanca comercial',
        # Termos genericos uteis com contexto
        'preco', 'reajuste', 'aumento',
        'reclamacao', 'denuncia', 'fiscalizacao',
        'produto', 'servico',
        'pagamento', 'tarifa', 'taxa', 'juros',
        'credito', 'banco',
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

    # Penalidade para noticias internacionais sem conexao com consumidor BR
    paises_estrangeiros = [
        'cuba', 'ira ', 'iran', 'iraque', 'ucrania', 'russia',
        'china', 'japao', 'india', 'coreia', 'israel', 'palestina',
        'siria', 'afeganistao', 'venezuela', 'argentina',
        'estados unidos', ' eua', 'europa',
    ]
    termos_brasil = [
        'brasil', 'brasileiro', 'consumidor', 'procon',
        'reais', 'r$', 'real', 'exportacao', 'importacao',
        'tarifa', 'preco', 'impacto',
    ]
    eh_internacional = any(p in texto for p in paises_estrangeiros)
    tem_contexto_br = any(t in texto for t in termos_brasil)
    if eh_internacional and not tem_contexto_br:
        score -= 5

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

                for entry in feed.entries[:50]:
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

                        # Janela de coleta: segunda=sexta 8h ate hoje 8h, demais=D-1 8h ate D 8h
                        agora = datetime.now()
                        hora_corte = agora.replace(hour=8, minute=0, second=0)
                        if agora.weekday() == 0:  # Segunda-feira: pega desde sexta 8h
                            limite_inferior = hora_corte - timedelta(days=3)
                        else:
                            limite_inferior = hora_corte - timedelta(days=1)
                        if data_pub < limite_inferior:
                            continue

                        # Guardar data original da fonte
                        data_fonte_original = data_pub.strftime('%Y-%m-%d')

                        # Forcar data de hoje para noticias coletadas hoje
                        hoje = datetime.now().date()
                        if data_pub.date() < hoje:
                            data_pub = datetime.now()

                        # Rejeitar conteudo patrocinado/publicitario
                        if any(x in link.lower() for x in ['/patrocinado/', '/dino/', '/publi/', '/branded/', '/sponsor/', '/publieditorial/']):
                            continue

                        imagem_url = extrair_imagem(entry, link)

                        noticia = {
                            'titulo': sanitizar_latin1(titulo[:255]),
                            'chamada': sanitizar_latin1(titulo[:255]),
                            'resumo': sanitizar_latin1(gerar_resumo(re.sub(r'\s*The post.*$', '', resumo_feed).strip())),
                            'texto': sanitizar_latin1(resumo_feed),
                            'link': link,
                            'fonte_nome': fonte_nome,
                            'fonte_id': fonte_id,
                            'data_publicacao': data_pub.strftime('%Y-%m-%d'),
                            'data_fonte': data_fonte_original,
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
    aprovadas = [n for n in noticias if n['score'] >= MIN_SCORE]
    descartadas = [n for n in noticias if n['score'] < MIN_SCORE]
    if antes > len(aprovadas):
        logger.info(f"Score minimo: {antes - len(aprovadas)} noticias descartadas (score < {MIN_SCORE})")

    # Complementar com temas populares se tiver menos que o minimo
    if len(aprovadas) < MIN_NOTICIAS and descartadas:
        faltam = MIN_NOTICIAS - len(aprovadas)
        complementares = []
        for n in descartadas:
            texto_busca = (n['titulo'] + ' ' + n.get('resumo', '')).lower()
            texto_busca = remover_acentos(texto_busca)
            if any(tema in texto_busca for tema in TEMAS_COMPLEMENTARES):
                complementares.append(n)
        if complementares:
            complementares.sort(key=lambda x: x['score'], reverse=True)
            adicionadas = complementares[:faltam]
            aprovadas.extend(adicionadas)
            logger.info(f"Complemento: {len(adicionadas)} noticias adicionadas por tema popular")
            for n in adicionadas:
                logger.info(f"  [complemento score={n['score']}] {n['titulo'][:70]}")

    # Aplicar limite por fonte para diversificar origens
    contagem_fonte = {}
    aprovadas_diversas = []
    excedentes = []
    for n in aprovadas:  # ja ordenado por score
        fid = n.get('fonte_id')
        contagem_fonte[fid] = contagem_fonte.get(fid, 0) + 1
        if contagem_fonte[fid] <= MAX_POR_FONTE:
            aprovadas_diversas.append(n)
        else:
            excedentes.append(n)
    if len(aprovadas_diversas) < MIN_NOTICIAS and excedentes:
        faltam = MIN_NOTICIAS - len(aprovadas_diversas)
        aprovadas_diversas.extend(excedentes[:faltam])
        logger.info(f"Limite por fonte relaxado: {faltam} noticias extras adicionadas para atingir minimo")
    aprovadas = aprovadas_diversas

    noticias = aprovadas

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
        noticia['texto'] = '\n'.join(html_parts)
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
            data_fonte,
            sis_user_criar, sis_data_criar, sis_hora_criar,
            sis_user_editar, sis_data_editar, sis_hora_editar
        ) VALUES (
            %s, %s, %s, %s, %s,
            %s, %s, %s, %s,
            %s, %s, 0, 0,
            1, 0, 0,
            %s, %s,
            %s,
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
        noticia.get('data_fonte', noticia['data_publicacao']),
        agora.strftime('%Y-%m-%d'),
        agora.strftime('%H:%M:%S'),
        agora.strftime('%Y-%m-%d'),
        agora.strftime('%H:%M:%S'),
    )

    cursor.execute(sql, params)
    return cursor.lastrowid


def classificar_especialidade(titulo, resumo=''):
    """Classifica a noticia em uma especialidade baseado no titulo e resumo.
    Retorna o ID da especialidade:
      1=Geral, 2=Economia, 3=Saude, 4=Telecomunicacoes,
      5=Dano Moral, 7=Direitos, 11=Concursos e Empregos
    """
    texto = (titulo + ' ' + resumo).lower()
    texto = remover_acentos(texto)

    # Telecomunicacoes (4)
    if any(t in texto for t in [
        'telefon', 'celular', 'internet', 'banda larga', 'anatel',
        'operadora', 'telecom', '5g ', 'fibra optica', 'roaming',
        'plano de dados', 'conta de celular',
    ]):
        return 4

    # Saude (3)
    if any(t in texto for t in [
        'saude', 'plano de saude', 'hospital', 'medic', 'anvisa',
        'sus ', 'remedio', 'farmacia', 'vacina', 'malaria',
        'tratamento', 'doenca', 'cirurgia', 'erro medico',
        'negativa de cobertura', 'carencia',
    ]):
        return 3

    # Concursos e Empregos (11)
    if any(t in texto for t in [
        'concurso', 'emprego', 'desemprego', 'salario', 'clt',
        'trabalhist', 'trabalhador', 'carteira de trabalho',
        'jornada de trabalho', '6x1', 'escala 6',
    ]):
        return 11

    # Dano Moral (5)
    if any(t in texto for t in [
        'dano moral', 'indeniza', 'danos morais', 'assedio',
        'negativacao indevida', 'nome sujo', 'cobranca indevida',
        'desconto indevido', 'fraude', 'golpe', 'estelionato',
    ]):
        return 5

    # Direitos (7)
    if any(t in texto for t in [
        'direito do consumidor', 'codigo de defesa', 'procon',
        'consumidor', 'recall', 'garantia', 'devolucao',
        'direito de arrependimento', 'clausula abusiva',
        'taxa abusiva', 'stj', 'stf', 'judicial',
        'passageiro', 'bagagem', 'voo cancelado', 'atraso de voo',
        'contribuinte', 'estatuto',
    ]):
        return 7

    # Economia (2)
    if any(t in texto for t in [
        'economi', 'inflacao', 'pib', 'juros', 'selic',
        'banco', 'credito', 'financ', 'imposto', 'tribut',
        'receita federal', 'divida', 'orcamento', 'fiscal',
        'tarifa', 'preco', 'reajuste', 'energia', 'combustivel',
        'petroleo', 'petrobras', 'aluguel', 'imovel', 'iptu',
        'inss', 'previdencia', 'aposentadoria', 'fgts',
        'bolsa familia', 'consignado', 'pix',
    ]):
        return 2

    # Default: Geral (1)
    return 1


def inserir_especialidade(cursor, noticia_id, titulo, resumo=''):
    """Insere a classificacao de especialidade para uma noticia."""
    espec_id = classificar_especialidade(titulo, resumo)
    agora = datetime.now()
    cursor.execute("""
        INSERT INTO noticias_x_especialidades (noticia_id, espec_id, user_id, data, hora)
        VALUES (%s, %s, 1, %s, %s)
    """, (noticia_id, espec_id, agora.strftime('%Y-%m-%d'), agora.strftime('%H:%M:%S')))
    return espec_id


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


def _dimensoes_imagem(caminho):
    """Le (largura, altura) via ImageMagick. Retorna (None, None) se nao der para ler."""
    try:
        saida = subprocess.run(
            ['identify', '-format', '%w %h', caminho + '[0]'],
            capture_output=True, text=True, timeout=20
        )
        if saida.returncode != 0:
            return None, None
        largura, altura = saida.stdout.strip().split()[:2]
        return int(largura), int(altura)
    except Exception:
        return None, None


def _normalizar_imagem(caminho):
    """Recorta no centro e redimensiona para FOTO_LARGURA x FOTO_ALTURA, no lugar.
    Retorna True se a versao normalizada foi gravada."""
    try:
        saida = subprocess.run(
            ['convert', caminho + '[0]',
             '-auto-orient',
             '-resize', f'{FOTO_LARGURA}x{FOTO_ALTURA}^',
             '-gravity', 'center',
             '-extent', f'{FOTO_LARGURA}x{FOTO_ALTURA}',
             '-quality', '85',
             caminho],
            capture_output=True, text=True, timeout=60
        )
        if saida.returncode != 0:
            logger.warning(f"convert falhou em {os.path.basename(caminho)}: {saida.stderr.strip()[:200]}")
            return False
        return os.path.getsize(caminho) > 0
    except Exception as e:
        logger.warning(f"Erro ao normalizar {os.path.basename(caminho)}: {e}")
        return False


def salvar_imagem_url(url_imagem, indice):
    """Baixa imagem da URL e salva no diretorio de imagens do site.
    Descarta o que nao serve como foto (pequena demais, retrato, tarja) e
    padroniza o resto em FOTO_LARGURA x FOTO_ALTURA.
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
        base_nome = agora.strftime('%Y%m%d%H%M%S') + str(indice % 10)
        foto_salva = base_nome + ext
        foto_path = os.path.join(IMAGES_DIR, foto_salva)

        # O nome so tem precisao de segundo, entao duas imagens baixadas no mesmo
        # segundo com o mesmo indice%10 colidem. Antes isso sobrescrevia; agora, como
        # imagem reprovada e apagada, a colisao apagaria a foto boa de outra noticia.
        sufixo = 0
        while os.path.exists(foto_path):
            sufixo += 1
            foto_salva = f'{base_nome}_{sufixo}{ext}'
            foto_path = os.path.join(IMAGES_DIR, foto_salva)

        with open(foto_path, 'wb') as f:
            for chunk in resp.iter_content(chunk_size=8192):
                f.write(chunk)

        os.chmod(foto_path, 0o644)
        file_size = os.path.getsize(foto_path)

        if file_size < 1000:
            os.remove(foto_path)
            return None, None

        largura, altura = _dimensoes_imagem(foto_path)
        if not largura or not altura:
            os.remove(foto_path)
            logger.warning(f"Imagem descartada, dimensoes ilegiveis: {url_imagem}")
            return None, None

        proporcao = largura / altura
        if largura < FOTO_LARGURA_MIN or not (FOTO_PROPORCAO_MIN <= proporcao <= FOTO_PROPORCAO_MAX):
            os.remove(foto_path)
            logger.warning(
                f"Imagem descartada por formato: {largura}x{altura} "
                f"(proporcao {proporcao:.2f}) — {url_imagem}"
            )
            return None, None

        if not _normalizar_imagem(foto_path):
            os.remove(foto_path)
            logger.warning(f"Imagem descartada, falha ao normalizar: {url_imagem}")
            return None, None

        os.chmod(foto_path, 0o644)
        file_size = os.path.getsize(foto_path)

        foto_nome = os.path.basename(urlparse(url_imagem).path) or foto_salva
        logger.info(
            f"Imagem salva: {foto_salva} ({file_size//1024}KB) — "
            f"origem {largura}x{altura} normalizada para {FOTO_LARGURA}x{FOTO_ALTURA}"
        )
        return sanitizar_latin1(foto_nome[:200]), foto_salva

    except Exception as e:
        logger.warning(f"Erro ao baixar imagem {url_imagem}: {e}")
        return None, None


def buscar_foto_db(cursor, area_id, dias=60):
    """Fallback 1: reusa foto_salva de uma notícia recente da mesma area_id.
    Retorna (foto, foto_salva) ou (None, None)."""
    if not area_id:
        return None, None
    try:
        cursor.execute(
            "SELECT foto, foto_salva FROM noticias "
            "WHERE area_id = %s "
            "  AND foto_salva IS NOT NULL AND foto_salva <> '' "
            "  AND ativo = 1 "
            "  AND data_publicacao >= DATE_SUB(CURDATE(), INTERVAL %s DAY) "
            "ORDER BY data_publicacao DESC, id DESC LIMIT 1",
            (area_id, dias)
        )
        row = cursor.fetchone()
        if row and row.get('foto_salva'):
            foto_path = os.path.join(IMAGES_DIR, row['foto_salva'])
            if os.path.isfile(foto_path):
                logger.info(f"Foto reaproveitada do DB (area_id={area_id}): {row['foto_salva']}")
                return row.get('foto') or row['foto_salva'], row['foto_salva']
    except Exception as e:
        logger.warning(f"buscar_foto_db falhou: {e}")
    return None, None


def _padronizar_para_caixa(origem, destino):
    """Gera versao FOTO_LARGURA x FOTO_ALTURA sem cortar nada: encaixa a imagem
    inteira e preenche a sobra com uma copia borrada dela mesma.
    Usado nos banners da RevisaAqui — sao pecas fechadas, e o corte central
    arrisca comer texto ou logo perto da borda. Retorna True se gravou."""
    try:
        saida = subprocess.run(
            ['convert', origem + '[0]', '-auto-orient',
             '(', '-clone', '0',
                  '-resize', f'{FOTO_LARGURA}x{FOTO_ALTURA}^',
                  '-gravity', 'center', '-extent', f'{FOTO_LARGURA}x{FOTO_ALTURA}',
                  '-blur', '0x25', ')',
             '(', '-clone', '0', '-resize', f'{FOTO_LARGURA}x{FOTO_ALTURA}', ')',
             '-delete', '0',
             '-gravity', 'center', '-composite',
             '-background', 'white', '-alpha', 'remove', '-alpha', 'off',
             '-quality', '88', destino],
            capture_output=True, text=True, timeout=60
        )
        if saida.returncode != 0:
            logger.warning(
                f"convert falhou ao padronizar '{os.path.basename(origem)}': {saida.stderr.strip()[:200]}"
            )
            return False
        return os.path.isfile(destino) and os.path.getsize(destino) > 0
    except Exception as e:
        logger.warning(f"Erro ao padronizar '{os.path.basename(origem)}': {e}")
        return False


def buscar_foto_revisaaqui(titulo, palavras_chave=''):
    """Fallback 2: match por keyword na biblioteca local RevisaAqui.
    Copia arquivo escolhido para IMAGES_DIR com nome novo.
    Retorna (foto, foto_salva) ou (None, None)."""
    if not os.path.isdir(REVISAAQUI_DIR):
        return None, None
    texto = remover_acentos(((titulo or '') + ' ' + (palavras_chave or '')).lower())
    # Tokens do texto da notícia (>=4 chars, alfanuméricos)
    tokens_texto = set(t for t in re.findall(r'[a-z0-9]+', texto) if len(t) >= 4)
    if not tokens_texto:
        return None, None

    candidatos = []
    try:
        for fname in os.listdir(REVISAAQUI_DIR):
            ext = os.path.splitext(fname)[1].lower()
            if ext not in ('.png', '.jpg', '.jpeg', '.webp'):
                continue
            # Pular capturas de tela genéricas (não dão match útil por keyword)
            if fname.lower().startswith(('captura de tela', 'screenshot')):
                continue
            base = remover_acentos(os.path.splitext(fname)[0].lower())
            tokens_arq = set(t for t in re.findall(r'[a-z0-9]+', base) if len(t) >= 4)
            comum = tokens_arq & tokens_texto
            if comum:
                score = sum(len(t) for t in comum)
                candidatos.append((score, fname))
    except Exception as e:
        logger.warning(f"buscar_foto_revisaaqui erro listando dir: {e}")
        return None, None

    if not candidatos:
        return None, None

    candidatos.sort(reverse=True)
    escolhido = candidatos[0][1]
    src = os.path.join(REVISAAQUI_DIR, escolhido)
    carimbo = datetime.now().strftime('%Y%m%d%H%M%S%f')
    novo_nome = 'ra_' + carimbo + '.jpg'
    dst = os.path.join(IMAGES_DIR, novo_nome)
    try:
        if _padronizar_para_caixa(src, dst):
            medida = f'{FOTO_LARGURA}x{FOTO_ALTURA}'
        else:
            # Padronizacao falhou: melhor a copia crua do que destaque sem foto.
            if os.path.exists(dst):
                os.remove(dst)
            ext = os.path.splitext(escolhido)[1].lower()
            novo_nome = 'ra_' + carimbo + ext
            dst = os.path.join(IMAGES_DIR, novo_nome)
            shutil.copy2(src, dst)
            medida = 'tamanho original (padronizacao falhou)'
        os.chmod(dst, 0o644)
        logger.info(
            f"Foto reaproveitada da biblioteca RevisaAqui: '{escolhido}' -> {novo_nome} [{medida}]"
        )
        return sanitizar_latin1(escolhido[:200]), novo_nome
    except Exception as e:
        logger.warning(f"Erro ao copiar foto RevisaAqui '{escolhido}': {e}")
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
    # As primeiras NOTICIAS_COM_IMAGEM vao para DESTAQUE_2 (lado a lado, mesmo tamanho)
    # Restante vai para DESTAQUE_N (lista de ultimas noticias)
    n_imgs = NOTICIAS_COM_IMAGEM

    # Guard: SEMPRE colocar em destaque apenas noticias com foto_salva.
    # Reordenar a lista para que IDs com foto_salva venham primeiro (preservando ordem relativa).
    try:
        format_ids = ','.join(['%s'] * len(noticias_ids))
        cursor.execute(
            f"SELECT id, foto_salva FROM noticias WHERE id IN ({format_ids})",
            tuple(noticias_ids)
        )
        com_foto_set = {row['id'] for row in cursor.fetchall() if row.get('foto_salva')}
    except Exception as e:
        logger.warning(f"Falha ao checar foto_salva para reordenar destaques: {e}")
        com_foto_set = set(noticias_ids)  # fallback conservador

    com_foto = [nid for nid in noticias_ids if nid in com_foto_set]
    sem_foto = [nid for nid in noticias_ids if nid not in com_foto_set]
    rebaixadas = len(noticias_ids[:n_imgs]) - len([nid for nid in noticias_ids[:n_imgs] if nid in com_foto_set])
    if rebaixadas > 0:
        logger.warning(f"Guard destaque: {rebaixadas} candidata(s) sem foto rebaixada(s) para lista")
    noticias_ids = com_foto + sem_foto

    count_d1 = 0
    count_d2 = 0
    count_dn = 0
    for ordem, noticia_id in enumerate(noticias_ids):
        if ordem < n_imgs and noticia_id in com_foto_set:
            # Apenas noticias com foto entram como DESTAQUE_2 (lado a lado)
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

                # Bloqueio de feed de TV/telejornal (JR 24h, "Assista à íntegra", etc.)
                if eh_feed_tv(noticia.get('titulo', '')):
                    logger.info(f"Feed TV bloqueado: {noticia['titulo'][:80]}")
                    continue

                noticia = enriquecer_noticia(noticia)

                # Verificar se o texto tem tamanho minimo
                texto_limpo = limpar_html(noticia.get('texto', ''))
                if len(texto_limpo) < MIN_TEXTO_CHARS:
                    logger.warning(f"Texto muito curto ({len(texto_limpo)} chars), pulando: {noticia['titulo'][:60]}")
                    continue

                # Baixar imagem para as noticias de destaque (top 2-3)
                if inseridas < NOTICIAS_COM_IMAGEM:
                    img_url = noticia.get('imagem_url', '')
                    if not img_url and noticia.get('link'):
                        img_url = extrair_imagem_pagina(noticia['link'])
                    if img_url:
                        foto, foto_salva = salvar_imagem_url(img_url, inseridas)
                        if foto and foto_salva:
                            noticia['foto'] = foto
                            noticia['foto_salva'] = foto_salva

                    # Guard: destaque SEMPRE com foto. Fallbacks em ordem:
                    #  1) reusa foto_salva de notícia recente da mesma area_id
                    #  2) match na biblioteca local RevisaAqui por keyword
                    if not noticia.get('foto_salva'):
                        foto, foto_salva = buscar_foto_db(
                            cursor, noticia.get('area_id')
                        )
                        if foto and foto_salva:
                            noticia['foto'] = foto
                            noticia['foto_salva'] = foto_salva

                    if not noticia.get('foto_salva'):
                        foto, foto_salva = buscar_foto_revisaaqui(
                            noticia.get('titulo', ''),
                            noticia.get('palavras_chave', '')
                        )
                        if foto and foto_salva:
                            noticia['foto'] = foto
                            noticia['foto_salva'] = foto_salva

                    if not noticia.get('foto_salva'):
                        logger.warning("DESTAQUE sem imagem (todos fallbacks falharam): " + noticia['titulo'][:60])
                
                # Reconectar se a conexao caiu
                try:
                    conn.ping(reconnect=True, attempts=3, delay=1)
                except:
                    conn = mysql.connector.connect(**DB_CONFIG)
                    cursor = conn.cursor(dictionary=True)
                
                noticia_id = inserir_noticia(cursor, noticia)
                espec_id = inserir_especialidade(cursor, noticia_id, noticia['titulo'], noticia.get('resumo', ''))
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

    # 3. Atualizar homepage com as noticias do dia
    if noticias_inseridas_ids:
        criar_edicao_homepage(conn, noticias_inseridas_ids)

    cursor.close()
    conn.close()
    logger.info("Coleta finalizada.")


if __name__ == '__main__':
    main()
