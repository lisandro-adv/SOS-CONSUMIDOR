#!/usr/bin/env python3
"""
Agente Curador de Notícias - SOS Consumidor
Analisa notícias coletadas usando Claude API e decide:
- AUTO APROVAR (ativo=1) para notícias relevantes e de qualidade
- AUTO REJEITAR (mantém ativo=0) para spam/irrelevantes
- SINALIZAR para revisão humana nos casos duvidosos
"""

import os
import sys
import json
import re
import argparse
import logging
import unicodedata
from datetime import datetime, timedelta
from decimal import Decimal

import anthropic
import mysql.connector
from dotenv import load_dotenv

# Carregar variáveis de ambiente
load_dotenv(os.path.join(os.path.dirname(__file__), '.env'))

# ============================================================
# CONFIGURAÇÃO
# ============================================================

VERSAO = '1.0.0'

# Modelos Claude
MODELO_TRIAGEM = 'claude-haiku-4-5-20251001'
# Confirmado na API Anthropic em 03/08/2026. O identificador anterior
# (claude-sonnet-4-20250514) foi descontinuado e retornava HTTP 404.
MODELO_ANALISE = 'claude-sonnet-4-6'

# Limites de tokens
MAX_TOKENS_TRIAGEM = 300
MAX_TOKENS_ANALISE = 2500

# Thresholds de decisão
LIMIAR_APROVACAO_CONFIANCA = 0.80
LIMIAR_REJEICAO_CONFIANCA = 0.85
LIMIAR_QUALIDADE_MINIMA = 6.0
LIMIAR_COERENCIA_MINIMA = 6.0

# Limites operacionais
BATCH_SIZE = 50
CUSTO_ALERTA_USD = 5.0
DELAY_ENTRE_CHAMADAS = 0.5  # segundos

# Banco de dados
DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'user': os.getenv('DB_USER'),
    'password': os.getenv('DB_PASSWORD'),
    'database': os.getenv('DB_NAME'),
    'charset': os.getenv('DB_CHARSET', 'latin1'),
    'use_unicode': True,
}

# Score mínimo (do coletar_noticias.py)
MIN_SCORE = 2
MIN_TEXTO_CHARS = 500

# Logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] %(message)s',
    handlers=[
        logging.StreamHandler(sys.stdout),
    ]
)
log = logging.getLogger('curador')

# ============================================================
# PALAVRAS-CHAVE PARA PRÉ-FILTRO (do coletar_noticias.py)
# ============================================================

PALAVRAS_POSITIVAS = [
    'consumidor', 'procon', 'recall', 'codigo de defesa', 'cdc',
    'indenizacao', 'dano moral', 'negativacao', 'spc', 'serasa',
    'plano de saude', 'anvisa', 'anatel', 'golpe', 'fraude', 'pix',
    'lgpd', 'protecao de dados', 'vazamento de dados', 'privacidade',
    'banco central', 'taxa de juros', 'selic', 'credito', 'financiamento',
    'cartao de credito', 'emprestimo', 'consignado', 'endividamento',
    'inadimplencia', 'cobranca', 'tarifa', 'telefonia', 'internet',
    'energia eletrica', 'agua', 'saneamento', 'combustivel', 'gasolina',
    'diesel', 'etanol', 'preco', 'inflacao', 'ipca', 'cesta basica',
    'alimento', 'medicamento', 'remedio', 'anvisa', 'vigilancia sanitaria',
    'stj', 'stf', 'tribunal', 'decisao judicial', 'jurisprudencia',
    'direito do consumidor', 'relacao de consumo', 'contrato',
    'propaganda enganosa', 'venda casada', 'pratica abusiva',
    'imposto de renda', 'restituicao', 'declaracao ir', 'receita federal',
    'iptu', 'ipva', 'generico', 'similar', 'medicamento generico',
    'recuperacao judicial', 'recuperacao extrajudicial',
    'inss', 'aposentadoria', 'aposentado', 'pensionista', 'pensao',
    '13o salario', 'decimo terceiro', 'bpc', 'loas', 'auxilio doenca',
    'fgts', 'seguro desemprego', 'beneficio previdenciario',
]

PALAVRAS_NEGATIVAS = [
    'futebol', 'campeonato', 'olimpiada', 'copa do mundo',
    'novela', 'bbb', 'big brother', 'celebridade', 'famoso',
    'guerra', 'terrorismo', 'atentado', 'bombardeio',
    'renda passiva', 'ganhe dinheiro', 'fique rico', 'metodo infalivel',
]

PADROES_PROPAGANDA = [
    'siga no whatsapp', 'siga no instagram', 'siga no twitter',
    'inscreva-se', 'compartilhe', 'baixe o app', 'download do app',
    'veja tambem', 'leia tambem', 'leia mais', 'saiba mais',
    'continua depois da publicidade', 'continue lendo',
    'the post', 'appeared first on', 'receba noticias',
    'fique por dentro', 'basta acessar o canal',
]

# Padrões de lixo no final dos artigos (headlines de outras matérias, CTAs editoriais)
PADROES_LIXO_FINAL = [
    r'jornalismo cr.tico e inteligente',
    r'apoie o jornalismo',
    r'todos os dias,? no seu e-?mail',
    r'do micro ao macro',
    r'a editoria de .+ de \w+',
    r'como nossos fi.is leitores',
    r'estamos aqui,? h. mais de \d+ anos',
    r'o compromisso de \w+ com os princ.pios',
    r'o combate . desigualdade nos importa',
    r'assine .{0,20}(carta|folha|estadao|valor|uol)',
    r'receba .{0,30} no seu (e-?mail|whatsapp|telegram)',
    r'newsletter',
    r'cadastre-se',
    r'clique aqui para',
    r'acesse .{0,20} conte.do exclusivo',
    r'seja assinante',
    r'experimente gr.tis',
    r'teste gr.tis',
    r'assine agora',
    r'exclusivo para assinantes',
]

# ============================================================
# PROMPTS DO CLAUDE
# ============================================================

SYSTEM_TRIAGEM = """Voce e um curador editorial do site SOS Consumidor, especializado em direitos do consumidor no Brasil.

Analise o titulo e resumo da noticia. Responda APENAS com JSON valido (sem markdown, sem ```):

{"relevante": true/false, "relevancia": "alta"|"media"|"baixa", "confianca": 0.0-1.0, "categoria": "string curta", "motivo": "uma frase"}

Temas RELEVANTES: direitos do consumidor, LGPD, protecao de dados, credito, bancos, financas pessoais, Procon, CDC, STJ/STF decisoes consumeristas, recalls, fraudes, telecomunicacoes, planos de saude, seguros, aviacao, comercio eletronico, precos, inflacao, combustiveis, energia, agua, tarifas, inadimplencia, cobrancas, imposto de renda, restituicao IR, declaracao IR, impostos que afetam o cidadao, IPTU, IPVA, taxas e tributos, recuperacao judicial de empresas que afetam consumidores, medicamentos genericos e similares, preco de remedios, beneficios previdenciarios (INSS, aposentadoria, 13o salario, pensao, BPC/LOAS, auxilio-doenca), FGTS, seguro-desemprego, direitos trabalhistas que afetam o cidadao.

Temas IRRELEVANTES: politica partidaria pura, esportes, celebridades, entretenimento, crimes violentos sem relacao com consumo, guerras, terrorismo, politica fiscal macro sem impacto direto no consumidor, mercado de capitais e investimentos sem impacto direto no consumidor."""

SYSTEM_ANALISE = """Voce e o curador editorial do site SOS Consumidor. Analise esta noticia e retorne APENAS JSON valido (sem markdown, sem ```).
IMPORTANTE: Seja CONCISO nas justificativas e motivos (maximo 1 frase curta). Listas de categorias com no maximo 3 itens. Listas de erros e contradicoes vazias se nao houver.

{
  "relevancia": {"nivel": "alta"|"media"|"baixa", "score": 0.0-10.0, "categorias": ["lista"], "justificativa": "string"},
  "qualidade": {"score": 0.0-10.0, "erros_gramaticais": ["lista ou vazia"], "clickbait": false, "clickbait_motivo": null, "propaganda_paragrafos": [], "linguagem_tendenciosa": false},
  "coerencia": {"score": 0.0-10.0, "titulo_alinhado": true, "contradicoes": [], "fontes_identificadas": true},
  "decisao": {"recomendacao": "aprovado"|"rejeitado"|"revisao", "confianca": 0.0-1.0, "motivo": "explicacao concisa", "notas_editor": null}
}

CRITERIOS:
1. RELEVANCIA: Trata de direitos do consumidor, relacoes de consumo, economia que afeta consumidor, financas pessoais, imposto de renda, restituicao, IPTU, IPVA, taxas, medicamentos/genericos, recuperacao judicial de empresas relevantes ao consumidor, beneficios previdenciarios (INSS, 13o, aposentadoria, pensao, BPC/LOAS), FGTS, seguro-desemprego?
2. QUALIDADE: Erros gramaticais? Titulo condiz com conteudo (clickbait)? Propaganda disfarçada? Linguagem tendenciosa?
3. COERENCIA: Titulo alinhado com texto? O corpo do texto realmente aborda o assunto prometido no titulo? Se o titulo fala de um tema especifico mas o corpo trata de outro assunto ou apenas menciona o tema de passagem, o score de coerencia deve ser BAIXO (< 5.0). Contradicoes internas? Fontes identificadas?
4. DECISAO: "aprovado" se relevante+qualidade boa+titulo coerente com corpo. "rejeitado" se irrelevante/propaganda/baixa qualidade. "revisao" se duvidoso ou titulo nao condiz com o conteudo real do texto.

CRITERIO DE FONTE E CAUTELA:
- O SOS Consumidor aceita noticias de fontes diversas e confiaveis: jornais, portais, veiculos regionais e fontes oficiais. NAO exija fonte oficial para aprovar uma noticia geral util sobre inflacao, emprego, preco, energia, credito, golpes, reclamacoes ou direitos do consumidor.
- A fonte e um fator de confianca, nunca um bloqueio automatico. Reportagem jornalistica clara, com dados e contexto, pode ser aprovada normalmente.
- Seja mais cauteloso em informacoes sensiveis ou acionaveis: valor/tarifa de servico publico, recall, regra de beneficio, decisao judicial, mudanca regulatoria, prazo, multa, direito especifico ou orientacao que possa levar o leitor a tomar uma decisao.
- Nesses casos, se o texto nao identifica com clareza o orgao, empresa, decisao, norma ou dado que sustenta a afirmacao, escolha "revisao" e explique em "notas_editor" o que deve ser conferido. Nao afirme que houve confirmacao externa: voce deve analisar somente o material recebido.
- Conteudo patrocinado, promocional ou baseado somente na fala interessada de uma empresa deve ir para "revisao" ou "rejeitado", conforme a falta de valor informativo.

IMPORTANTE sobre propaganda:
- Quando um executivo (CEO, diretor) e citado como FONTE de informacao relevante ao consumidor, isso NAO e propaganda. Ex: CEO anuncia lancamento de medicamento generico mais barato = noticia relevante.
- Propaganda e quando o texto inteiro e promocional para vender produto/servico, sem valor informativo.
- "propaganda_paragrafos" deve listar apenas paragrafos genuinamente promocionais (ex: "baixe nosso app", "assine agora"), NAO citacoes de executivos dando informacao factual."""

SYSTEM_SEO = """Voce e um especialista em SEO para o site SOS Consumidor, portal brasileiro de direitos do consumidor.

Com base no titulo e texto da noticia, gere um resumo e uma chamada otimizados para SEO. Responda APENAS com JSON valido (sem markdown, sem ```):

{"resumo": "string de 140-160 caracteres", "chamada": "string de 80-120 caracteres"}

REGRAS para o RESUMO (meta description):
- Entre 140 e 160 caracteres (ideal para Google)
- Responde: o que aconteceu + impacto direto para o consumidor brasileiro
- Inclui palavras-chave naturais do tema (ex: Procon, INSS, recall, juros, etc.)
- Sem clickbait, sem "saiba mais", sem "clique aqui"
- Termina com ponto final

REGRAS para a CHAMADA (subtitulo na capa):
- Entre 80 e 120 caracteres
- Frase direta e informativa, que complementa o titulo
- Destaca o dado mais relevante ou o impacto ao consumidor
- Sem ponto de exclamacao, sem sensacionalismo"""


# ============================================================
# FUNÇÕES AUXILIARES
# ============================================================

def sanitizar_latin1(texto):
    """Remove caracteres incompatíveis com latin1."""
    if not texto:
        return ''
    texto = texto.replace('\u201c', '"').replace('\u201d', '"')
    texto = texto.replace('\u2018', "'").replace('\u2019', "'")
    texto = texto.replace('\u2013', '-').replace('\u2014', '-')
    texto = re.sub(r'[\U00010000-\U0010ffff]', '', texto)
    return texto.encode('latin1', errors='replace').decode('latin1')


def limpar_html(texto):
    """Remove tags HTML e decodifica entities para texto puro."""
    if not texto:
        return ''
    import html
    texto = re.sub(r'<[^>]+>', ' ', texto)
    texto = html.unescape(texto)
    texto = re.sub(r'\s+', ' ', texto).strip()
    return texto


def limpar_lixo_final(texto_html):
    """Remove headlines soltas, CTAs e propaganda editorial do final do artigo.
    Trabalha com o HTML original (antes de limpar tags)."""
    if not texto_html:
        return texto_html

    import html as html_mod

    # Dividir em parágrafos
    paragrafos = re.split(r'(<p[^>]*>.*?</p>)', texto_html, flags=re.DOTALL | re.IGNORECASE)
    paragrafos = [p for p in paragrafos if p.strip()]

    # Encontrar onde começa o lixo: percorrer de trás para frente
    ultimo_bom = len(paragrafos)
    consecutivos_lixo = 0

    for i in range(len(paragrafos) - 1, -1, -1):
        p = paragrafos[i]
        # Extrair texto puro do parágrafo
        texto_puro = re.sub(r'<[^>]+>', '', p).strip()
        texto_puro = html_mod.unescape(texto_puro)
        texto_lower = texto_puro.lower()

        eh_lixo = False

        # Verificar padrões de lixo
        for padrao in PADROES_LIXO_FINAL:
            if re.search(padrao, texto_lower):
                eh_lixo = True
                break

        # Headline solta: parágrafo curto (<120 chars) que é só negrito, sem contexto
        if not eh_lixo and len(texto_puro) < 120:
            # Verificar se é só texto em <strong> (headline solta)
            conteudo_strong = re.findall(r'<strong>(.*?)</strong>', p, re.DOTALL | re.IGNORECASE)
            texto_fora_strong = re.sub(r'<strong>.*?</strong>', '', p, flags=re.DOTALL | re.IGNORECASE)
            texto_fora_strong = re.sub(r'<[^>]+>', '', texto_fora_strong).strip()
            if conteudo_strong and not texto_fora_strong:
                eh_lixo = True

        if eh_lixo:
            consecutivos_lixo += 1
            ultimo_bom = i
        else:
            # Se encontrou conteúdo bom, parar
            break

    if consecutivos_lixo > 0:
        log.info(f'  Lixo removido: {consecutivos_lixo} parágrafo(s) do final')
        return ''.join(paragrafos[:ultimo_bom])

    return texto_html


def calcular_score_local(titulo, resumo=''):
    """Score rápido de relevância baseado em palavras-chave."""
    texto = unicodedata.normalize('NFKD', f"{titulo} {resumo}".lower())
    texto = ''.join(c for c in texto if not unicodedata.combining(c))

    score = 0
    for palavra in PALAVRAS_POSITIVAS:
        if palavra in texto:
            score += 2
    for palavra in PALAVRAS_NEGATIVAS:
        if palavra in texto:
            score -= 5
    return score


def similaridade_titulos(titulo1, titulo2):
    """Calcula similaridade entre dois títulos (0.0 a 1.0) usando palavras em comum."""
    def normalizar(t):
        t = unicodedata.normalize('NFKD', t.lower())
        t = ''.join(c for c in t if not unicodedata.combining(c))
        # Remover palavras curtas (artigos, preposições)
        palavras = set(p for p in re.split(r'\W+', t) if len(p) > 3)
        return palavras

    p1 = normalizar(titulo1)
    p2 = normalizar(titulo2)
    if not p1 or not p2:
        return 0.0
    intersecao = p1 & p2
    uniao = p1 | p2
    return len(intersecao) / len(uniao) if uniao else 0.0


def detectar_duplicatas(noticias, limiar=0.55):
    """Detecta pares de notícias com títulos muito similares.
    Retorna dict {noticia_id: noticia_id_similar} para as duplicatas."""
    duplicatas = {}
    for i in range(len(noticias)):
        if noticias[i]['id'] in duplicatas:
            continue
        for j in range(i + 1, len(noticias)):
            if noticias[j]['id'] in duplicatas:
                continue
            sim = similaridade_titulos(
                noticias[i].get('titulo', ''),
                noticias[j].get('titulo', '')
            )
            if sim >= limiar:
                # Marcar a segunda como duplicata da primeira
                duplicatas[noticias[j]['id']] = noticias[i]['id']
                log.info(f'  Duplicata detectada: #{noticias[j]["id"]} similar a #{noticias[i]["id"]} ({sim:.0%})')
    return duplicatas


def detectar_propaganda_local(texto):
    """Verifica se o texto contém padrões de propaganda."""
    if not texto:
        return False
    texto_norm = unicodedata.normalize('NFKD', texto.lower())
    texto_norm = ''.join(c for c in texto_norm if not unicodedata.combining(c))
    return any(p in texto_norm for p in PADROES_PROPAGANDA)


def parse_json_resposta(texto):
    """Extrai JSON da resposta do Claude, tolerando markdown."""
    texto = texto.strip()
    # Remover blocos de código markdown (usar greedy .* para pegar JSON completo)
    match = re.search(r'```(?:json)?\s*(\{.*\})\s*```', texto, re.DOTALL)
    if match:
        texto = match.group(1)
    # Tentar encontrar o JSON diretamente (greedy para pegar objetos aninhados)
    match = re.search(r'\{.*\}', texto, re.DOTALL)
    if match:
        texto = match.group(0)
    try:
        return json.loads(texto)
    except json.JSONDecodeError:
        # Tentar corrigir problemas comuns
        texto = re.sub(r',\s*}', '}', texto)  # trailing comma
        texto = re.sub(r',\s*]', ']', texto)
        try:
            return json.loads(texto)
        except json.JSONDecodeError:
            return None


# ============================================================
# GERENCIADOR DE BANCO DE DADOS
# ============================================================

class DatabaseManager:
    def __init__(self):
        self.conn = None

    def conectar(self):
        self.conn = mysql.connector.connect(**DB_CONFIG)
        # Modo SQL relaxado para compatibilidade
        cursor = self.conn.cursor()
        cursor.execute("SET sql_mode=''")
        cursor.close()
        return self.conn

    def _garantir_conexao(self):
        """Reconecta ao MySQL se a conexão caiu."""
        try:
            self.conn.ping(reconnect=True, attempts=3, delay=2)
        except Exception:
            logger.warning("Reconectando ao MySQL...")
            self.conectar()

    def desconectar(self):
        if self.conn and self.conn.is_connected():
            self.conn.close()

    def buscar_pendentes(self, dias=7, noticia_id=None):
        """Busca notícias com ativo=0 e status_curadoria='pendente'."""
        self._garantir_conexao()
        cursor = self.conn.cursor(dictionary=True)
        if noticia_id:
            sql = """SELECT id, titulo, chamada, resumo, texto, fonte_id,
                     data_publicacao, palavras_chave, ativo, status_curadoria
                     FROM noticias WHERE id = %s"""
            cursor.execute(sql, (noticia_id,))
        else:
            data_limite = (datetime.now() - timedelta(days=dias)).strftime('%Y-%m-%d')
            sql = """SELECT id, titulo, chamada, resumo, texto, fonte_id,
                     data_publicacao, palavras_chave, ativo, status_curadoria
                     FROM noticias
                     WHERE ativo = 0
                     AND (status_curadoria = 'pendente' OR status_curadoria IS NULL)
                     AND data_publicacao >= %s
                     ORDER BY id DESC
                     LIMIT %s"""
            cursor.execute(sql, (data_limite, BATCH_SIZE))
        resultados = cursor.fetchall()
        cursor.close()
        return resultados

    def atualizar_noticia(self, noticia_id, ativo, status_curadoria):
        """Atualiza status da notícia."""
        self._garantir_conexao()
        cursor = self.conn.cursor()
        cursor.execute("SET sql_mode=''")
        cursor.execute(
            "UPDATE noticias SET ativo = %s, status_curadoria = %s WHERE id = %s",
            (ativo, status_curadoria, noticia_id)
        )
        self.conn.commit()
        cursor.close()

    def atualizar_seo(self, noticia_id, resumo, chamada):
        """Atualiza resumo e chamada com versões otimizadas para SEO."""
        self._garantir_conexao()
        cursor = self.conn.cursor()
        cursor.execute("SET sql_mode=''")
        cursor.execute(
            "UPDATE noticias SET resumo = %s, chamada = %s WHERE id = %s",
            (sanitizar_latin1(resumo), sanitizar_latin1(chamada), noticia_id)
        )
        self.conn.commit()
        cursor.close()

    def atualizar_texto(self, noticia_id, texto_html, motivo='limpeza_curador'):
        """Atualiza o texto da notícia, fazendo backup do original antes."""
        self._garantir_conexao()
        cursor = self.conn.cursor()
        cursor.execute("SET sql_mode=''")
        # Backup do texto original antes de alterar
        cursor.execute(
            """INSERT INTO noticias_texto_backup (noticia_id, texto, motivo)
               SELECT id, texto, %s FROM noticias WHERE id = %s AND texto IS NOT NULL""",
            (motivo, noticia_id)
        )
        cursor.execute(
            "UPDATE noticias SET texto = %s WHERE id = %s",
            (texto_html, noticia_id)
        )
        self.conn.commit()
        cursor.close()

    def inserir_curadoria(self, dados):
        """Insere registro na tabela curadoria_ia."""
        self._garantir_conexao()
        cursor = self.conn.cursor()
        sql = """INSERT INTO curadoria_ia
                 (noticia_id, relevancia, qualidade_score, coerencia_score,
                  clickbait, propaganda, decisao, confianca, motivo,
                  notas_editor, tokens_usados, custo_usd)
                 VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"""
        cursor.execute(sql, (
            dados['noticia_id'],
            dados.get('relevancia'),
            dados.get('qualidade_score'),
            dados.get('coerencia_score'),
            dados.get('clickbait', 0),
            dados.get('propaganda', 0),
            dados['decisao'],
            dados.get('confianca'),
            sanitizar_latin1(dados.get('motivo', '')),
            sanitizar_latin1(dados.get('notas_editor', '')),
            dados.get('tokens_usados', 0),
            dados.get('custo_usd', 0),
        ))
        self.conn.commit()
        cursor.close()

    def buscar_fonte_nome(self, fonte_id):
        """Retorna o nome da fonte pelo ID."""
        if not fonte_id:
            return 'Desconhecida'
        self._garantir_conexao()
        cursor = self.conn.cursor(dictionary=True)
        cursor.execute("SELECT nome FROM noticias_fonte WHERE id = %s", (fonte_id,))
        resultado = cursor.fetchone()
        cursor.close()
        return resultado['nome'] if resultado else 'Desconhecida'


# ============================================================
# ANALISADOR CLAUDE
# ============================================================

class AnalisadorClaude:
    def __init__(self):
        api_key = os.getenv('ANTHROPIC_API_KEY')
        if not api_key:
            raise ValueError("ANTHROPIC_API_KEY não configurada no .env")
        self.client = anthropic.Anthropic(api_key=api_key)
        self.tokens_total = 0
        self.custo_total = 0.0

    def triagem(self, noticia):
        """Tier 1: Triagem rápida com Haiku."""
        user_msg = f"""Titulo: {noticia['titulo']}
Chamada: {noticia.get('chamada', '')}
Resumo: {noticia.get('resumo', '')}"""

        resultado = self._chamar_api(
            system=SYSTEM_TRIAGEM,
            user_msg=user_msg,
            modelo=MODELO_TRIAGEM,
            max_tokens=MAX_TOKENS_TRIAGEM
        )
        return resultado

    def analise_completa(self, noticia, fonte_nome=''):
        """Tier 2: Análise completa com Sonnet."""
        # Limpar lixo do final (headlines, CTAs) antes de analisar
        texto_html = noticia.get('texto', '')
        texto_html = limpar_lixo_final(texto_html)
        texto_limpo = limpar_html(texto_html)
        # Limitar texto a ~4000 chars para controlar custo
        if len(texto_limpo) > 4000:
            texto_limpo = texto_limpo[:4000] + '...'

        user_msg = f"""Titulo: {noticia['titulo']}
Chamada: {noticia.get('chamada', '')}
Resumo: {noticia.get('resumo', '')}
Fonte: {fonte_nome}
Data publicacao: {noticia.get('data_publicacao', '')}
Palavras-chave: {noticia.get('palavras_chave', '')}

Texto completo:
{texto_limpo}"""

        resultado = self._chamar_api(
            system=SYSTEM_ANALISE,
            user_msg=user_msg,
            modelo=MODELO_ANALISE,
            max_tokens=MAX_TOKENS_ANALISE
        )
        return resultado

    def gerar_seo(self, noticia):
        """Gera resumo e chamada otimizados para SEO usando Haiku."""
        texto_limpo = limpar_html(noticia.get('texto', ''))
        if len(texto_limpo) > 2000:
            texto_limpo = texto_limpo[:2000] + '...'

        user_msg = f"""Titulo: {noticia.get('titulo', '')}
Chamada atual: {noticia.get('chamada', '')}
Resumo atual: {noticia.get('resumo', '')}

Texto:
{texto_limpo}"""

        return self._chamar_api(
            system=SYSTEM_SEO,
            user_msg=user_msg,
            modelo=MODELO_TRIAGEM,  # Haiku — rápido e barato
            max_tokens=300
        )

    def _chamar_api(self, system, user_msg, modelo, max_tokens):
        """Faz a chamada à API do Claude com retry."""
        import time
        for tentativa in range(3):
            try:
                response = self.client.messages.create(
                    model=modelo,
                    max_tokens=max_tokens,
                    system=system,
                    messages=[{"role": "user", "content": user_msg}]
                )

                # Contabilizar tokens
                tokens_in = response.usage.input_tokens
                tokens_out = response.usage.output_tokens
                self.tokens_total += tokens_in + tokens_out

                # Estimar custo (preços aproximados)
                if 'haiku' in modelo:
                    custo = (tokens_in * 0.80 + tokens_out * 4.0) / 1_000_000
                else:
                    custo = (tokens_in * 3.0 + tokens_out * 15.0) / 1_000_000
                self.custo_total += custo

                # Verificar se resposta foi truncada
                if response.stop_reason == 'max_tokens':
                    log.warning(f"Resposta truncada (max_tokens={max_tokens}), retentando com mais tokens")
                    if tentativa < 2:
                        max_tokens = int(max_tokens * 1.5)
                        continue

                # Extrair JSON da resposta
                texto_resposta = response.content[0].text
                dados = parse_json_resposta(texto_resposta)

                if dados is None:
                    log.warning(f"JSON inválido na resposta: {texto_resposta[:200]}")
                    return {'erro': 'JSON inválido', 'raw': texto_resposta[:500],
                            'tokens': tokens_in + tokens_out, 'custo': custo}

                dados['_tokens'] = tokens_in + tokens_out
                dados['_custo'] = custo
                return dados

            except anthropic.RateLimitError:
                log.warning(f"Rate limit, aguardando {2 ** tentativa * 2}s...")
                time.sleep(2 ** tentativa * 2)
            except anthropic.APIError as e:
                log.error(f"Erro API (tentativa {tentativa + 1}/3): {e}")
                time.sleep(2 ** tentativa * 2)
            except Exception as e:
                log.error(f"Erro inesperado: {e}")
                return {'erro': str(e), 'tokens': 0, 'custo': 0}

        return {'erro': 'Falha após 3 tentativas', 'tokens': 0, 'custo': 0}


# ============================================================
# MOTOR DE DECISÃO
# ============================================================

class MotorDecisao:

    @staticmethod
    def decidir_triagem(triagem, score_local):
        """Decide com base na triagem rápida (Tier 1)."""
        if 'erro' in triagem:
            return 'continuar'  # Seguir para Tier 2

        relevante = triagem.get('relevante', True)
        relevancia = triagem.get('relevancia', 'media')
        confianca = float(triagem.get('confianca', 0.5))

        # Rejeitar na triagem se claramente irrelevante
        if not relevante and confianca >= LIMIAR_REJEICAO_CONFIANCA:
            return 'rejeitar'

        if relevancia == 'baixa' and confianca >= LIMIAR_REJEICAO_CONFIANCA and score_local < MIN_SCORE:
            return 'rejeitar'

        return 'continuar'  # Seguir para análise completa

    @staticmethod
    def decidir_analise(analise, score_local):
        """Decide com base na análise completa (Tier 2)."""
        if 'erro' in analise:
            return 'revisao', 'Erro na análise automática'

        try:
            decisao_claude = analise.get('decisao', {})
            relevancia = analise.get('relevancia', {})
            qualidade = analise.get('qualidade', {})
            coerencia = analise.get('coerencia', {})

            recomendacao = decisao_claude.get('recomendacao', 'revisao')
            confianca = float(decisao_claude.get('confianca', 0.5))
            qualidade_score = float(qualidade.get('score', 5.0))
            coerencia_score = float(coerencia.get('score', 5.0))
            nivel_relevancia = relevancia.get('nivel', 'media')
            clickbait = qualidade.get('clickbait', False)
            propaganda_paragrafos = qualidade.get('propaganda_paragrafos', [])
            propaganda = len(propaganda_paragrafos) > 2
            tem_propaganda_parcial = len(propaganda_paragrafos) > 0

            motivo = decisao_claude.get('motivo', '')

            titulo_alinhado = coerencia.get('titulo_alinhado', True)

            # REVISÃO se título não condiz com corpo
            if not titulo_alinhado or coerencia_score < 5.0:
                return 'revisao', f'Título não condiz com o corpo da notícia (coerência: {coerencia_score:.1f})'

            # AUTO APROVAR
            if (recomendacao == 'aprovado'
                    and confianca >= LIMIAR_APROVACAO_CONFIANCA
                    and nivel_relevancia in ('alta', 'media')
                    and qualidade_score >= LIMIAR_QUALIDADE_MINIMA
                    and coerencia_score >= LIMIAR_COERENCIA_MINIMA
                    and not clickbait
                    and not propaganda
                    and not tem_propaganda_parcial):
                return 'aprovado', motivo

            # REVISÃO se tem propaganda parcial mas conteúdo relevante
            if (tem_propaganda_parcial and not propaganda
                    and nivel_relevancia in ('alta', 'media')):
                return 'revisao', f'Conteúdo relevante mas com {len(propaganda_paragrafos)} parágrafo(s) promocional(is) - revisar antes de publicar'

            # AUTO REJEITAR
            if (recomendacao == 'rejeitado' and confianca >= LIMIAR_REJEICAO_CONFIANCA):
                return 'rejeitado', motivo

            if nivel_relevancia == 'baixa' and score_local < MIN_SCORE:
                return 'rejeitado', f'Baixa relevância (score local: {score_local})'

            if propaganda:
                return 'rejeitado', 'Propaganda detectada em 3+ parágrafos'

            if qualidade_score < 3.0:
                return 'rejeitado', f'Qualidade muito baixa: {qualidade_score}'

            # SINALIZAR PARA REVISÃO
            return 'revisao', motivo or 'Caso duvidoso - requer revisão humana'

        except (KeyError, TypeError, ValueError) as e:
            return 'revisao', f'Erro ao processar análise: {e}'


# ============================================================
# GERADOR DE RELATÓRIO
# ============================================================

class GeradorRelatorio:
    def __init__(self):
        self.aprovados = []
        self.rejeitados = []
        self.revisao = []
        self.pre_filtrados = []
        self.erros = []

    def adicionar(self, noticia_id, titulo, decisao, motivo, confianca=0, scores=None):
        entrada = {
            'id': noticia_id,
            'titulo': titulo[:80],
            'motivo': motivo,
            'confianca': confianca,
            'scores': scores or {},
        }
        if decisao == 'aprovado':
            self.aprovados.append(entrada)
        elif decisao == 'rejeitado':
            self.rejeitados.append(entrada)
        elif decisao == 'revisao':
            self.revisao.append(entrada)
        elif decisao == 'pre_filtrado':
            self.pre_filtrados.append(entrada)

    def gerar_texto(self, custo_total=0, tokens_total=0):
        linhas = []
        linhas.append('=' * 65)
        linhas.append('RELATÓRIO DE CURADORIA - SOS Consumidor')
        linhas.append(f'Data: {datetime.now().strftime("%Y-%m-%d %H:%M:%S")}')
        linhas.append(f'Versão: {VERSAO}')
        linhas.append('=' * 65)
        linhas.append('')

        total = len(self.aprovados) + len(self.rejeitados) + len(self.revisao) + len(self.pre_filtrados)
        linhas.append('RESUMO:')
        linhas.append(f'  Total analisados:        {total}')
        linhas.append(f'  Pré-filtrados (sem API):  {len(self.pre_filtrados)}')
        linhas.append(f'  Aprovados (ativo=1):      {len(self.aprovados)}')
        linhas.append(f'  Rejeitados:               {len(self.rejeitados)}')
        linhas.append(f'  Para revisão:             {len(self.revisao)}')
        linhas.append(f'  Custo estimado: ${custo_total:.4f} ({tokens_total} tokens)')
        linhas.append('')

        if self.aprovados:
            linhas.append('APROVADOS AUTOMATICAMENTE:')
            for a in self.aprovados:
                linhas.append(f'  [#{a["id"]}] {a["titulo"]}')
                linhas.append(f'    Confiança: {a["confianca"]:.0%} | {a["motivo"]}')
            linhas.append('')

        if self.revisao:
            linhas.append('PARA REVISÃO HUMANA:')
            for r in self.revisao:
                linhas.append(f'  [#{r["id"]}] {r["titulo"]}')
                linhas.append(f'    Motivo: {r["motivo"]}')
            linhas.append('')

        if self.rejeitados:
            linhas.append('REJEITADOS:')
            for r in self.rejeitados:
                linhas.append(f'  [#{r["id"]}] {r["titulo"]}')
                linhas.append(f'    Motivo: {r["motivo"]}')
            linhas.append('')

        if self.pre_filtrados:
            linhas.append('PRÉ-FILTRADOS (sem API):')
            for p in self.pre_filtrados:
                linhas.append(f'  [#{p["id"]}] {p["motivo"]}')
            linhas.append('')

        linhas.append('=' * 65)
        return '\n'.join(linhas)


# ============================================================
# PIPELINE PRINCIPAL
# ============================================================

RE_FEED_TV = re.compile(
    r'assista\s+a\s+integra'
    r'|\bjr\s*24\s*(?:horas|h)\b'
    r'|confira\s+nesta\s+edicao'
    r'|\d+\s*[aºo]?\s+edicao\s+do\b'
    r'|o\s+assunto\s*#?\d+',
    re.IGNORECASE
)


def _normalizar_titulo(t):
    """Remove acentos e lowercase para matching de padrões."""
    if not t:
        return ''
    nfkd = unicodedata.normalize('NFKD', t)
    return ''.join(c for c in nfkd if not unicodedata.combining(c)).lower()



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
def pre_filtrar(noticia):
    """Rejeita artigos óbvios sem usar API. Retorna motivo ou None."""
    # Feed de telejornal/TV — bloqueio espelhado do coletar_noticias.py (defesa em camadas)
    if RE_FEED_TV.search(_normalizar_titulo(noticia.get('titulo', ''))):
        return 'Feed de TV/telejornal (JR 24h, "Assista à íntegra", "Confira nesta edição")'
    # Resultado financeiro de empresa (earnings report) — fora do escopo do SOS Consumidor
    if RE_RESULTADO_EMPRESA.search(noticia.get('titulo', '')):
        return 'Resultado financeiro de empresa (alta/queda de X% no Nº tri)'


    texto = limpar_html(noticia.get('texto', ''))
    if len(texto) < MIN_TEXTO_CHARS:
        return f'Texto muito curto ({len(texto)} chars)'

    score = calcular_score_local(noticia.get('titulo', ''), noticia.get('resumo', ''))
    if score < -5:
        return f'Score muito negativo ({score})'

    if detectar_propaganda_local(texto):
        return 'Propaganda detectada por padrões locais'

    return None


def processar_noticia(noticia, analisador, db, motor, relatorio, dry_run=False):
    """Processa uma única notícia pelo pipeline completo."""
    import time

    noticia_id = noticia['id']
    titulo = noticia.get('titulo', '')[:80]
    fonte_nome = db.buscar_fonte_nome(noticia.get('fonte_id'))

    log.info(f'Analisando #{noticia_id}: {titulo}')

    # 1. Pré-filtro (sem API)
    motivo_rejeicao = pre_filtrar(noticia)
    if motivo_rejeicao:
        log.info(f'  Pré-filtrado: {motivo_rejeicao}')
        if not dry_run:
            db.atualizar_noticia(noticia_id, 0, 'rejeitado_ia')
            db.inserir_curadoria({
                'noticia_id': noticia_id,
                'decisao': 'rejeitado',
                'motivo': motivo_rejeicao,
                'confianca': 1.0,
            })
        relatorio.adicionar(noticia_id, titulo, 'pre_filtrado', motivo_rejeicao)
        return

    # 2. Triagem (Tier 1 - Haiku)
    score_local = calcular_score_local(noticia.get('titulo', ''), noticia.get('resumo', ''))
    triagem = analisador.triagem(noticia)
    time.sleep(DELAY_ENTRE_CHAMADAS)

    decisao_triagem = motor.decidir_triagem(triagem, score_local)
    if decisao_triagem == 'rejeitar':
        motivo = triagem.get('motivo', 'Irrelevante na triagem')
        log.info(f'  Rejeitado na triagem: {motivo}')
        if not dry_run:
            db.atualizar_noticia(noticia_id, 0, 'rejeitado_ia')
            db.inserir_curadoria({
                'noticia_id': noticia_id,
                'relevancia': triagem.get('relevancia'),
                'decisao': 'rejeitado',
                'confianca': float(triagem.get('confianca', 0)),
                'motivo': motivo,
                'tokens_usados': triagem.get('_tokens', 0),
                'custo_usd': triagem.get('_custo', 0),
            })
        relatorio.adicionar(noticia_id, titulo, 'rejeitado', motivo,
                           float(triagem.get('confianca', 0)))
        return

    # 3. Análise completa (Tier 2 - Sonnet)
    analise = analisador.analise_completa(noticia, fonte_nome)
    time.sleep(DELAY_ENTRE_CHAMADAS)

    # 4. Decisão final
    decisao, motivo = motor.decidir_analise(analise, score_local)

    # Extrair scores para o relatório e banco
    qualidade_score = None
    coerencia_score = None
    relevancia = None
    confianca = 0
    clickbait = 0
    propaganda = 0
    notas = None

    if 'erro' not in analise:
        try:
            qualidade_score = float(analise.get('qualidade', {}).get('score', 0))
            coerencia_score = float(analise.get('coerencia', {}).get('score', 0))
            relevancia = analise.get('relevancia', {}).get('nivel')
            confianca = float(analise.get('decisao', {}).get('confianca', 0))
            clickbait = 1 if analise.get('qualidade', {}).get('clickbait') else 0
            propaganda = 1 if len(analise.get('qualidade', {}).get('propaganda_paragrafos', [])) > 0 else 0
            notas = analise.get('decisao', {}).get('notas_editor')
        except (TypeError, ValueError):
            pass

    tokens = analise.get('_tokens', 0) + triagem.get('_tokens', 0)
    custo = analise.get('_custo', 0) + triagem.get('_custo', 0)

    # 5. Limpar lixo do texto antes de publicar
    texto_original = noticia.get('texto', '')
    texto_limpo_html = limpar_lixo_final(texto_original)
    if texto_limpo_html != texto_original and not dry_run:
        db.atualizar_texto(noticia_id, texto_limpo_html)

    # 6. Gerar SEO para artigos aprovados
    if decisao == 'aprovado':
        import time
        time.sleep(DELAY_ENTRE_CHAMADAS)
        seo = analisador.gerar_seo(noticia)
        if 'erro' not in seo:
            seo_resumo = seo.get('resumo', '')
            seo_chamada = seo.get('chamada', '')
            tokens += seo.get('_tokens', 0)
            custo += seo.get('_custo', 0)
            if seo_resumo and not dry_run:
                db.atualizar_seo(noticia_id, seo_resumo, seo_chamada)
                log.info(f'  SEO gerado: resumo={len(seo_resumo)}chars')
        else:
            log.warning(f'  SEO falhou: {seo.get("erro")}')

    # 7. Aplicar decisão
    if decisao == 'aprovado':
        log.info(f'  ✓ APROVADO (confiança: {confianca:.0%})')
        if not dry_run:
            db.atualizar_noticia(noticia_id, 1, 'aprovado_ia')
    elif decisao == 'rejeitado':
        log.info(f'  ✗ REJEITADO: {motivo}')
        if not dry_run:
            db.atualizar_noticia(noticia_id, 0, 'rejeitado_ia')
    else:
        log.info(f'  ? REVISÃO: {motivo}')
        if not dry_run:
            db.atualizar_noticia(noticia_id, 0, 'revisao_humana')

    # 6. Salvar no banco
    if not dry_run:
        db.inserir_curadoria({
            'noticia_id': noticia_id,
            'relevancia': relevancia,
            'qualidade_score': qualidade_score,
            'coerencia_score': coerencia_score,
            'clickbait': clickbait,
            'propaganda': propaganda,
            'decisao': decisao,
            'confianca': confianca,
            'motivo': motivo,
            'notas_editor': notas,
            'tokens_usados': tokens,
            'custo_usd': custo,
        })

    relatorio.adicionar(noticia_id, titulo, decisao, motivo, confianca)


def main():
    parser = argparse.ArgumentParser(description='Curador de Notícias - SOS Consumidor')
    parser.add_argument('--id', type=int, help='Analisar notícia específica por ID')
    parser.add_argument('--dry-run', action='store_true', help='Analisar sem alterar banco')
    parser.add_argument('--dias', type=int, default=7, help='Analisar pendentes dos últimos N dias')
    parser.add_argument('--relatorio', action='store_true', help='Mostrar último relatório')
    args = parser.parse_args()

    log.info(f'Curador de Notícias v{VERSAO} iniciado')
    if args.dry_run:
        log.info('*** MODO DRY-RUN: nenhuma alteração será feita ***')

    # Conectar ao banco
    db = DatabaseManager()
    try:
        db.conectar()
    except Exception as e:
        log.error(f'Erro ao conectar ao banco: {e}')
        sys.exit(1)

    try:
        # Buscar notícias pendentes
        pendentes = db.buscar_pendentes(dias=args.dias, noticia_id=args.id)
        log.info(f'Notícias pendentes: {len(pendentes)}')

        if not pendentes:
            log.info('Nenhuma notícia pendente para análise.')
            return

        # Inicializar componentes
        analisador = AnalisadorClaude()
        motor = MotorDecisao()
        relatorio = GeradorRelatorio()

        # Detectar duplicatas antes de processar
        duplicatas = detectar_duplicatas(pendentes)
        if duplicatas:
            log.info(f'Duplicatas detectadas: {len(duplicatas)} notícias')

        # Processar cada notícia
        for i, noticia in enumerate(pendentes):
            # Verificar custo acumulado
            if analisador.custo_total >= CUSTO_ALERTA_USD:
                log.warning(f'Custo diário atingiu ${analisador.custo_total:.2f}, parando.')
                break

            # Pular duplicatas
            noticia_id = noticia['id']
            if noticia_id in duplicatas:
                similar_id = duplicatas[noticia_id]
                motivo_dup = f'Duplicata de #{similar_id} - título muito similar'
                log.info(f'  Duplicata #{noticia_id}: pulando (similar a #{similar_id})')
                if not args.dry_run:
                    db.atualizar_noticia(noticia_id, 0, 'rejeitado_ia')
                    db.inserir_curadoria({
                        'noticia_id': noticia_id,
                        'decisao': 'rejeitado',
                        'motivo': motivo_dup,
                        'confianca': 1.0,
                    })
                relatorio.adicionar(noticia_id, noticia.get('titulo', '')[:80], 'rejeitado', motivo_dup)
                continue

            processar_noticia(noticia, analisador, db, motor, relatorio, args.dry_run)
            log.info(f'  Progresso: {i + 1}/{len(pendentes)} | Custo: ${analisador.custo_total:.4f}')

        # Gerar relatório
        texto_relatorio = relatorio.gerar_texto(
            custo_total=analisador.custo_total,
            tokens_total=analisador.tokens_total
        )
        print('\n' + texto_relatorio)

        # Salvar relatório em log
        log_dir = '/var/log/sos'
        if os.path.isdir(log_dir):
            log_file = os.path.join(log_dir, f'curadoria_{datetime.now().strftime("%Y%m%d_%H%M%S")}.log')
            with open(log_file, 'w') as f:
                f.write(texto_relatorio)
            log.info(f'Relatório salvo em {log_file}')

    except Exception as e:
        log.error(f'Erro durante curadoria: {e}')
        raise
    finally:
        db.desconectar()

    log.info('Curadoria finalizada.')


if __name__ == '__main__':
    main()
