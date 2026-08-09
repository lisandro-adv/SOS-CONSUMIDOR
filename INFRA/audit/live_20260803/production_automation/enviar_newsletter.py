#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Newsletter Semanal - SOS Consumidor
Busca noticias da semana no banco e envia via Brevo API.
Executar toda terca-feira as 9h via cron.
"""

import os
import sys
import re
import unicodedata
import html as html_mod
import mysql.connector
import sib_api_v3_sdk
from sib_api_v3_sdk.rest import ApiException
from datetime import datetime, timedelta
from dotenv import load_dotenv

# Carregar .env do diretorio do script
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
load_dotenv(os.path.join(SCRIPT_DIR, '.env'))

# Configuracoes
API_KEY_FILE = '/root/.brevo_api_key'
LISTA_ID = 3  # Newsletter SOS Consumidor
SENDER_NAME = "SOS Consumidor"
SENDER_EMAIL = "contato@sosconsumidor.com.br"
SITE_URL = "https://www.sosconsumidor.com.br"

DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'user': os.getenv('DB_USER'),
    'password': os.getenv('DB_PASSWORD'),
    'database': os.getenv('DB_NAME'),
    'charset': os.getenv('DB_CHARSET', 'latin1'),
}


def get_api_key():
    with open(API_KEY_FILE) as f:
        return f.read().strip()


def buscar_noticias(dias=7, limite=10):
    """Busca noticias publicadas nos ultimos N dias"""
    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor(dictionary=True)

    data_inicio = (datetime.now() - timedelta(days=dias)).strftime('%Y-%m-%d')

    cursor.execute("""
        SELECT id, titulo, resumo, chamada, foto_home, data_publicacao, acessos
        FROM noticias
        WHERE ativo = 1
          AND data_publicacao >= %s
        ORDER BY acessos DESC
        LIMIT %s
    """, (data_inicio, limite))

    noticias = cursor.fetchall()
    cursor.close()
    conn.close()
    return noticias


def gerar_url_noticia(noticia):
    """Gera URL da noticia no formato do site: noticias-{id}-{slug}"""
    nid = noticia.get('id', '')
    titulo = noticia.get('titulo', '')
    titulo = html_mod.unescape(titulo)
    # Remove acentos
    titulo = unicodedata.normalize('NFKD', titulo)
    titulo = ''.join(c for c in titulo if not unicodedata.combining(c))
    # Lowercase, remove pontuacao
    titulo = titulo.lower()
    titulo = re.sub(r'[^a-z0-9\s]', '', titulo)
    titulo = titulo.strip()
    # Remover palavras sem valor SEO
    palavras_remover = [
        'a', 'um', 'de', 'o', 'e', 'com', 'pode', 'da', 'do', 'porque',
        'nao', 'no', 'na', 'em', 'por', 'para', 'que', 'se', 'os', 'as',
        'dos', 'das', 'uns', 'ao', 'pelo', 'pela', 'nos', 'nas'
    ]
    palavras = [p for p in titulo.split() if p not in palavras_remover]
    slug = '-'.join(palavras)[:100]
    return f"{SITE_URL}/noticias-{nid}-{slug}"


def gerar_resumo_curto(noticia, max_chars=200):
    """Gera resumo curto cortando na frase completa"""
    resumo = noticia.get('resumo') or noticia.get('chamada') or ''
    if len(resumo) <= max_chars:
        return resumo
    # Cortar na ultima frase completa
    p = max(resumo[:max_chars].rfind('. '), resumo[:max_chars].rfind('! '), resumo[:max_chars].rfind('? '))
    if p > 80:
        return resumo[:p + 1]
    return resumo[:max_chars - 3] + '...'


def montar_html(noticias):
    """Monta HTML da newsletter - Layout Novo"""
    data_formatada = datetime.now().strftime('%d/%m/%Y')

    # Montar linhas de noticias
    rows = ''
    for i, n in enumerate(noticias):
        titulo = n['titulo']
        resumo = gerar_resumo_curto(n)
        url = gerar_url_noticia(n)
        data_pub = n.get('data_publicacao', '')
        if hasattr(data_pub, 'strftime'):
            data_pub = data_pub.strftime('%d/%m/%Y')
        bg = '#ffffff' if i % 2 == 0 else '#f8f9fa'

        rows += f'''<tr><td style="padding:18px 30px;background-color:{bg};">
  <a href="{url}" style="color:#1e3a5f;text-decoration:none;">
    <p style="font-size:16px;font-weight:700;line-height:1.4;margin:0 0 6px;color:#1e3a5f;">{titulo}</p>
  </a>
  <p style="color:#64748b;font-size:12px;margin:0 0 6px;">{data_pub}</p>
  <p style="color:#475569;font-size:14px;line-height:1.5;margin:0 0 10px;">{resumo}</p>
  <a href="{url}" style="color:#2563eb;font-size:13px;font-weight:600;text-decoration:none;">Leia mais &rarr;</a>
</td></tr>
'''

    html = f'''<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#f0f2f5;font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0f2f5;">
<tr><td align="center" style="padding:20px 0;">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);">

<!-- HEADER -->
<tr><td style="padding:20px 30px 10px;text-align:center;background-color:#ffffff;">
  <a href="{SITE_URL}" style="text-decoration:none;">
    <img src="{SITE_URL}/img/logo.png" alt="SOS Consumidor" width="320" style="max-width:100%;height:auto;" />
  </a>
</td></tr>

<!-- BARRA DATA -->
<tr><td style="background-color:#2563eb;padding:10px 30px;text-align:center;">
  <p style="color:#ffffff;margin:0;font-size:14px;font-weight:bold;">Newsletter Semanal &bull; {data_formatada}</p>
</td></tr>

<!-- INTRO -->
<tr><td style="padding:25px 30px 15px;">
  <p style="color:#333;font-size:15px;line-height:1.6;margin:0;">
    Confira as not&iacute;cias mais lidas da semana sobre <strong>direitos do consumidor</strong>,
    finan&ccedil;as pessoais e servi&ccedil;os p&uacute;blicos.
  </p>
</td></tr>

<!-- DIVISOR -->
<tr><td style="padding:0 30px;"><hr style="border:none;border-top:2px solid #e2e8f0;margin:0;"></td></tr>

<!-- NOTICIAS -->
{rows}

<!-- FOOTER VERMELHO -->
<tr><td style="background-color:#c0392b;padding:25px 30px;text-align:center;">
  <a href="{SITE_URL}" style="text-decoration:none;">
    <img src="{SITE_URL}/img/logo-footer.png" alt="SOS Consumidor" width="200" style="max-width:100%;height:auto;margin-bottom:10px;" />
  </a>
  <p style="margin:10px 0 5px;">
    <a href="https://www.facebook.com/endividado" style="text-decoration:none;margin:0 5px;">
      <img src="{SITE_URL}/img/ico-facebook.png" alt="Facebook" width="28" height="28" style="display:inline-block;border-radius:4px;" />
    </a>
    <a href="https://twitter.com/sos_consumidor" style="text-decoration:none;margin:0 5px;">
      <img src="{SITE_URL}/img/ico-twitter.png" alt="Twitter" width="28" height="28" style="display:inline-block;border-radius:4px;" />
    </a>
  </p>
  <p style="color:#f8d7da;font-size:11px;margin:10px 0 0;">
    Voc&ecirc; recebe este informativo por ser cadastrado no SOS Consumidor.
  </p>
  <p style="color:#f8d7da;font-size:11px;margin:5px 0 0;">
    <a href="{{{{ unsubscribe }}}}" style="color:#f8d7da;text-decoration:underline;">Descadastrar-se</a>
  </p>
</td></tr>

</table>
</td></tr>
</table>
</body>
</html>'''

    return html


def enviar_campanha(noticias):
    """Cria e envia campanha no Brevo"""
    api_key = get_api_key()
    configuration = sib_api_v3_sdk.Configuration()
    configuration.api_key['api-key'] = api_key

    api_instance = sib_api_v3_sdk.EmailCampaignsApi(
        sib_api_v3_sdk.ApiClient(configuration)
    )

    hoje = datetime.now()
    assunto = f"SOS Consumidor - Noticias da Semana ({hoje.strftime('%d/%m')})"
    html_content = montar_html(noticias)

    campaign = sib_api_v3_sdk.CreateEmailCampaign(
        name=f"Newsletter {hoje.strftime('%Y-%m-%d')}",
        subject=assunto,
        sender={"name": SENDER_NAME, "email": SENDER_EMAIL},
        html_content=html_content,
        recipients={"listIds": [LISTA_ID]},
    )

    try:
        result = api_instance.create_email_campaign(campaign)
        campaign_id = result.id
        print(f"Campanha criada: ID={campaign_id}")

        api_instance.send_email_campaign_now(campaign_id)
        print(f"Campanha enviada com sucesso!")
        return campaign_id

    except ApiException as e:
        print(f"Erro ao criar/enviar campanha: {e}")
        return None


def main():
    print("=" * 50)
    print(f"NEWSLETTER SOS CONSUMIDOR - {datetime.now().strftime('%d/%m/%Y %H:%M')}")
    print("=" * 50)

    # Buscar noticias
    print("\n1. Buscando noticias da semana...")
    noticias = buscar_noticias(dias=7, limite=10)

    if len(noticias) < 6:
        print(f"   Apenas {len(noticias)} noticias nos ultimos 7 dias (minimo: 6).")
        print("   Tentando ultimos 14 dias...")
        noticias = buscar_noticias(dias=14, limite=10)

    if len(noticias) < 6:
        print(f"   Apenas {len(noticias)} noticias encontradas (minimo: 6). Abortando envio.")
        sys.exit(1)

    print(f"   {len(noticias)} noticias encontradas")
    for n in noticias:
        print(f"   - [{n['acessos']} views] {n['titulo'][:60]}...")

    # Enviar campanha
    print(f"\n2. Enviando newsletter para lista ID={LISTA_ID}...")
    campaign_id = enviar_campanha(noticias)

    if campaign_id:
        print(f"\n{'=' * 50}")
        print(f"SUCESSO! Campanha #{campaign_id} enviada.")
        print(f"{'=' * 50}")
    else:
        print("\nFALHA no envio da campanha.")
        sys.exit(1)


if __name__ == '__main__':
    main()
