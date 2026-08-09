#!/usr/bin/env python3
"""
Revisor automático de notícias - SOS Consumidor
Detecta e remove padrões conhecidos de conteúdo indevido,
faz backup e aprova artigos limpos.
"""

import mysql.connector
import re
import sys
import unicodedata
from datetime import date

# Padrões de propaganda/auto-promoção que devem ser removidos de parágrafos curtos
_PROPAGANDA = [
    'adicione como fonte preferencial',
    'adicione o r7',
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
    'inscreva-se no canal',
    'receba noticias',
    'fique por dentro',
    'baixe o app',
    'download do app',
    'continua depois da publicidade',
    'continue lendo apos',
    'continue lendo após',
    'globopop',
    'isso e fantastico',
    'isso é fantástico',
    'appeared first on',
    'assista a integra do jornal da record',
    'assista a integra do jr',
    'the post',
    'reporter especial em',
    'repórter especial em',
]

def _norm(texto):
    """Normaliza texto para comparação: minúsculo, sem acentos."""
    t = unicodedata.normalize('NFKD', texto.lower())
    return ''.join(c for c in t if not unicodedata.combining(c))

DB_CONFIG = {
    'host': 'localhost',
    'user': 'debian-sys-maint',
    'password': 'w5WACa9lBUQJYGa5',
    'database': 'user_sos'
}

def backup(cursor, noticia_id):
    cursor.execute(
        'INSERT INTO noticias_texto_backup (noticia_id, texto, motivo) '
        'SELECT id, texto, %s FROM noticias WHERE id=%s',
        ('auto_limpeza_revisor', noticia_id)
    )

def limpar(texto):
    removals = []

    # ── 1. Ri7a: bullets de resumo no topo ──────────────────────────────
    # Só remove se "Produzido pela Ri7a" também estiver no texto
    if 'Ri7a' in texto or 'ri7a' in texto:
        match = re.search(r'<ul[^>]*>.*?</ul>', texto, re.DOTALL)
        if match and match.start() < 1000 and 'href' not in match.group(0):
            texto = texto[:match.start()] + texto[match.end():]
            removals.append('Bullets Ri7a (resumo topo)')

    # ── 2. "Produzido pela Ri7a" ─────────────────────────────────────────
    for p in [
        'Produzido pela Ri7a - a Intelig&ecirc;ncia Artificial do R7',
        'Produzido pela Ri7a - a Inteligência Artificial do R7',
    ]:
        pattern = f'<p style="text-align: justify;">{p}</p>'
        if pattern in texto:
            texto = texto.replace(pattern, '')
            removals.append('Assinatura Ri7a')
            break

    # ── 3. Footer Google News / X (Twitter) / Link ───────────────────────
    match = re.search(r'<ul[^>]*>\s*<li>Google News</li>.*?</ul>', texto, re.DOTALL)
    if match:
        texto = texto[:match.start()] + texto[match.end():]
        removals.append('Footer Google News / X / Link')

    # ── 4. "Vídeos em alta no g1" ─────────────────────────────────────────
    for v in [
        '<p style="text-align: justify;">V&iacute;deos em alta no g1</p>',
        '<p style="text-align: justify;">Vídeos em alta no g1</p>',
    ]:
        if v in texto:
            texto = texto.replace(v, '')
            removals.append('G1: Vídeos em alta')
            break

    # ── 5. CTAs newsletter (Folha, Metrópoles e outros) ──────────────────
    # Cobre versão sem negrito: <p>Receba no seu email...</p>
    # e versão com negrito:     <p><strong>Receba no seu email...</strong></p>
    match = re.search(
        r'<p style="text-align: justify;">(?:<strong>)?Receba no seu email[^<]*(?:</strong>)?</p>', texto
    )
    if match:
        texto = texto[:match.start()] + texto[match.end():]
        removals.append('CTA newsletter (Receba no seu email)')
    # Remove parágrafo "Frequência de envio" que segue o CTA do Metrópoles
    match = re.search(
        r'<p style="text-align: justify;">Frequ(?:&ecirc;|ê)ncia de envio[^<]*</p>', texto
    )
    if match:
        texto = texto[:match.start()] + texto[match.end():]
        removals.append('CTA newsletter (Frequência de envio)')

    # ── 6. "Plataforma de conteúdo" ───────────────────────────────────────
    for p in [
        '<p style="text-align: justify;">Plataforma de conte&uacute;do</p>',
        '<p style="text-align: justify;">Plataforma de conteúdo</p>',
    ]:
        if p in texto:
            texto = texto.replace(p, '')
            removals.append('Plataforma de conteúdo')
            break

    # ── 7. "Mais de Esfera Brasil" + links ────────────────────────────────
    for marker in [
        '<p style="text-align: justify;"><strong>Mais de Esfera Brasil</strong></p>',
    ]:
        if marker in texto:
            pos = texto.find(marker)
            texto = texto[:pos]
            removals.append('Mais de Esfera Brasil + links relacionados')
            break

    # ── 8. Nota editorial "Matéria ampliada às..." e "Última atualização em..." ──
    for pattern_nota in [
        r'<p style="text-align: justify;">Mat&eacute;ria ampliada[^<]*</p>',
        r'<p style="text-align: justify;">&Uacute;ltima atualiza[^<]*</p>',
        r'<p style="text-align: justify;">Última atualiza[^<]*</p>',
    ]:
        match = re.search(pattern_nota, texto)
        if match:
            texto = texto[:match.start()] + texto[match.end():]
            removals.append('Nota editorial (matéria ampliada / última atualização)')

    # ── 9. Assinatura estagiária/repórter R7 ──────────────────────────────
    match = re.search(
        r'<p style="text-align: justify;">\*Estagi[^<]*</p>', texto
    )
    if match:
        texto = texto[:match.start()] + texto[match.end():]
        removals.append('Assinatura estagiária R7')

    # ── 10. Assinatura "Assessoria de Comunicação Social" ─────────────────
    match = re.search(
        r'<p style="text-align: justify;">Assessoria de Comunica[^<]*</p>', texto
    )
    if match:
        texto = texto[:match.start()] + texto[match.end():]
        removals.append('Assinatura assessoria de comunicação')

    # ── 10b. Assinatura de colaborador/redator de portal ──────────────────
    # Ex: "Colaboradora na Exame", "Colaborador no InfoMoney", "Redatora na..."
    match = re.search(
        r'<p style="text-align: justify;">Colaboradora?[^<]{0,60}</p>', texto
    )
    if match:
        texto = texto[:match.start()] + texto[match.end():]
        removals.append('Assinatura colaborador/redator')

    # ── 11. Tagline institucional (ex: Procon) ────────────────────────────
    for p in [
        'Equilibrio e harmonia nas rela&ccedil;&otilde;es entre consumidores e fornecedores.',
    ]:
        pattern = f'<p style="text-align: justify;"><strong>{p}</strong></p>'
        if pattern in texto:
            texto = texto.replace(pattern, '')
            removals.append('Tagline institucional (Procon/outro)')

    # ── 12. Lead/subtítulo duplicado ──────────────────────────────────────
    # Usa [^<] para evitar match guloso entre parágrafos distintos
    match = re.search(
        r'(<p style="text-align: justify;"><strong>[^<]{20,}</strong></p>)', texto
    )
    if match:
        subtitulo = match.group(1)
        if texto.count(subtitulo) >= 2:
            pos1 = texto.index(subtitulo)
            pos2 = texto.index(subtitulo, pos1 + 1)
            texto = texto[:pos2] + texto[pos2 + len(subtitulo):]
            removals.append('Lead/subtítulo duplicado')

    # ── 12b. Subtítulo duplicado sem negrito ─────────────────────────────
    # Detecta quando <p><strong>texto</strong></p> aparece também como <p>texto</p>
    subtitulos = re.findall(
        r'<p style="text-align: justify;"><strong>([^<]{20,})</strong></p>', texto
    )
    for sub_texto in subtitulos:
        plain = f'<p style="text-align: justify;">{sub_texto}</p>'
        if plain in texto:
            texto = texto.replace(plain, '', 1)
            removals.append('Subtítulo duplicado (versão sem negrito)')
            break

    # ── 12c. Legenda de foto (crédito fotográfico) ────────────────────────
    # Remove parágrafos que terminam com crédito de foto: (Divulgação), (Foto: X), etc.
    match = re.search(
        r'<p style="text-align: justify;">[^<]{10,400}\((?:Divulga[^)]{0,30}|Foto:[^)]{0,60}|Imagem:[^)]{0,60}|[^)]{0,40}/Divulga[^)]{0,30})\)</p>',
        texto
    )
    if match:
        texto = texto[:match.start()] + texto[match.end():]
        removals.append('Legenda de foto (crédito fotográfico)')

    # ── 13. Footer CartaCapital ───────────────────────────────────────────
    match = re.search(
        r'<p style="text-align: justify;">CartaCapital.*', texto, re.DOTALL
    )
    if match:
        texto = texto[:match.start()]
        removals.append('Footer CartaCapital')

    # ── 14. "Acesse aqui a pesquisa na íntegra" (link solto) ──────────────
    for p in [
        'Acesse aqui a pesquisa na &iacute;ntegra.',
        'Acesse aqui a pesquisa na íntegra.',
    ]:
        pattern = f'<p style="text-align: justify;">{p}</p>'
        if pattern in texto:
            texto = texto.replace(pattern, '')
            removals.append('Link solto (Acesse aqui a pesquisa)')
            break

    # ── 15. Links relacionados no padrão <p><strong>Título</strong></p> ───
    # 15a: bloco no FINAL — corta a partir do primeiro do cluster
    paragrafos_strong = list(re.finditer(
        r'<p style="text-align: justify;"><strong>([^<]{10,300})</strong></p>',
        texto
    ))
    if len(paragrafos_strong) >= 2:
        tamanho = len(texto)
        ultimos = [m for m in paragrafos_strong if m.start() > tamanho * 0.5]
        primeiro_strong = paragrafos_strong[0].start() if paragrafos_strong else 0
        if len(ultimos) >= 2 and ultimos[0].start() > primeiro_strong + 300:
            texto = texto[:ultimos[0].start()]
            removals.append(f'Links relacionados finais ({len(ultimos)}x <p><strong>)')

    # 15b: bloco no MEIO — 2+ <strong> consecutivos após o lead
    # Cobre portais que inserem links relacionados no meio do artigo (não só no final)
    cluster_mid = re.search(
        r'(?:<p style="text-align: justify;"><strong>[^<]{10,300}</strong></p>\r?\n?){2,}',
        texto
    )
    if cluster_mid and cluster_mid.start() > 200:
        tem_conteudo_antes = bool(re.search(
            r'<p[^>]*>[^<]{80,}</p>', texto[:cluster_mid.start()]
        ))
        tem_conteudo_depois = len(re.sub(r'<[^>]+>', '', texto[cluster_mid.end():]).strip()) > 100
        if tem_conteudo_antes and tem_conteudo_depois:
            count = len(re.findall(r'<strong>', texto[cluster_mid.start():cluster_mid.end()]))
            texto = texto[:cluster_mid.start()] + texto[cluster_mid.end():]
            removals.append(f'Links relacionados no meio ({count}x <p><strong> consecutivos)')

    # ── 16. Parágrafos de propaganda/auto-promoção ───────────────────────
    # Verifica parágrafos curtos (até 180 chars de texto limpo) contra lista
    # de padrões conhecidos. Itera de trás pra frente para não deslocar índices.
    matches_p = list(re.finditer(r'<p[^>]*>(.*?)</p>', texto, re.DOTALL))
    for m in reversed(matches_p):
        texto_p = re.sub(r'<[^>]+>', '', m.group(1)).strip()
        if len(texto_p) > 180:
            continue
        norma = _norm(texto_p)
        for padrao in _PROPAGANDA:
            if padrao in norma:
                texto = texto[:m.start()] + texto[m.end():]
                removals.append(f'Propaganda: "{texto_p[:70]}"')
                break

    return texto, removals

def verificar_conteudo(texto, titulo):
    """Verifica se o artigo tem conteúdo mínimo após limpeza."""
    alertas = []
    texto_sem_tags = re.sub(r'<[^>]+>', '', texto).strip()

    # Texto muito curto após limpeza
    if len(texto_sem_tags) < 200:
        alertas.append('🚨 TEXTO VAZIO OU QUASE VAZIO — REJEITAR')
    elif len(texto_sem_tags) < 400:
        alertas.append('⚠️  TEXTO MUITO CURTO — verificar manualmente')

    # Sem corpo real: remove subtítulos (<p><strong>...</strong></p>) e verifica o que sobra
    sem_subtitulos = re.sub(r'<p[^>]*>\s*<strong>.*?</strong>\s*</p>', '', texto, flags=re.DOTALL)
    paragrafos_restantes = re.findall(r'<p[^>]*>(.*?)</p>', sem_subtitulos, re.DOTALL)
    legenda_markers = ('Divulga', 'AFP', 'Getty', 'Reuters', '/Ag', 'Foto:', 'Imagem:')
    corpo_real = [
        re.sub(r'<[^>]+>', '', p).strip()
        for p in paragrafos_restantes
        if len(re.sub(r'<[^>]+>', '', p).strip()) > 200
        and not any(m in p for m in legenda_markers)
    ]
    if not corpo_real and '🚨 TEXTO VAZIO OU QUASE VAZIO — REJEITAR' not in alertas:
        alertas.append('🚨 SEM CORPO DE TEXTO — apenas subtítulo/legenda — REJEITAR')

    # Detectar se parece feed de TV (JR 24h, telejornais)
    if re.search(r'JR 24 Horas|telejornal|edi&ccedil;&atilde;o do JR|Jornal da Record|JR Mundo|integra do Jornal', titulo + texto, re.IGNORECASE):
        alertas.append('⚠️  POSSÍVEL CONTEÚDO DE TV — verificar manualmente')

    # Detectar muitos parágrafos curtos (padrão de feed de manchetes)
    paragrafos_curtos = re.findall(r'<p[^>]*>([^<]{10,100})</p>', texto)
    paragrafos_longos = re.findall(r'<p[^>]*>([^<]{150,})</p>', texto)
    if len(paragrafos_curtos) >= 4 and len(paragrafos_longos) == 0:
        alertas.append('⚠️  SÓ PARÁGRAFOS CURTOS — possível feed de manchetes, verificar')

    # Detectar legenda de foto no texto (padrão: texto curto com "/" ou "Getty" ou "AFP")
    if re.search(r'<p[^>]*>[^<]{5,80}(Getty|AFP|Reuters|Agência|\/[A-Z])[^<]{0,60}</p>', texto):
        alertas.append('⚠️  POSSÍVEL LEGENDA DE FOTO no texto — verificar manualmente')

    # Detectar cluster de parágrafos curtos sem bold entre conteúdo longo (possíveis legendas)
    paras_all = list(re.finditer(r'<p[^>]*>(.*?)</p>', texto, re.DOTALL))
    for idx in range(len(paras_all) - 2):
        grupo = []
        j = idx
        while j < len(paras_all):
            conteudo_p = re.sub(r'<[^>]+>', '', paras_all[j].group(1)).strip()
            if 15 < len(conteudo_p) < 120 and '<strong>' not in paras_all[j].group(0):
                grupo.append(j)
                j += 1
            else:
                break
        if len(grupo) >= 3:
            tem_longo_antes = any(
                len(re.sub(r'<[^>]+>', '', paras_all[k].group(1)).strip()) > 150
                for k in range(max(0, idx - 3), idx)
            )
            tem_longo_depois = any(
                len(re.sub(r'<[^>]+>', '', paras_all[k].group(1)).strip()) > 150
                for k in range(j, min(len(paras_all), j + 3))
            )
            if tem_longo_antes and tem_longo_depois:
                alertas.append(f'⚠️  POSSÍVEL CLUSTER DE LEGENDAS ({len(grupo)} parágrafos curtos no meio) — verificar manualmente')
            break

    return alertas

def main():
    # Aceita IDs específicos como argumentos ou processa todos os artigos do dia
    ids_especificos = [int(x) for x in sys.argv[1:] if x.isdigit()]

    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor()

    if ids_especificos:
        placeholders = ','.join(['%s'] * len(ids_especificos))
        cursor.execute(
            f'SELECT id, titulo, texto, fonte_id FROM noticias '
            f'WHERE id IN ({placeholders}) AND status_curadoria NOT LIKE "rejeitado%" '
            f'ORDER BY id DESC',
            ids_especificos
        )
    else:
        # Processa revisao_humana E aprovado_ia — limpa tudo antes de publicar
        cursor.execute("""
            SELECT id, titulo, texto, fonte_id
            FROM noticias
            WHERE status_curadoria IN ('revisao_humana', 'aprovado_ia')
            AND data_publicacao = CURDATE()
            ORDER BY id DESC
        """)

    artigos = cursor.fetchall()

    if not artigos:
        print('Nenhum artigo em revisão hoje.')
        conn.close()
        return

    total = len(artigos)
    limpos = 0
    alertas_total = 0

    print(f'\n{"="*60}')
    print(f'REVISOR AUTOMÁTICO — {date.today().strftime("%d/%m/%Y")}')
    print(f'{"="*60}')
    print(f'{total} artigo(s) em revisão\n')

    for artigo_id, titulo, texto, fonte_id in artigos:
        print(f'[{artigo_id}] {titulo[:55]}...' if len(titulo) > 55 else f'[{artigo_id}] {titulo}')

        texto_limpo, removals = limpar(texto)
        alertas = verificar_conteudo(texto_limpo, titulo)

        # Não aprovar automaticamente se texto vazio ou feed de manchetes
        tem_alerta_critico = any('🚨' in a for a in alertas)

        if tem_alerta_critico:
            # Apenas reportar — não aprovar, não alterar status
            print(f'  🚨 NÃO APROVADO — requer revisão manual')
            for r in removals:
                print(f'     — removido: {r}')
        elif removals:
            backup(cursor, artigo_id)
            cursor.execute(
                'UPDATE noticias SET texto=%s, ativo=1, status_curadoria="aprovado" WHERE id=%s',
                (texto_limpo, artigo_id)
            )
            limpos += 1
            print(f'  ✅ Limpo e aprovado')
            for r in removals:
                print(f'     — {r}')
        else:
            cursor.execute(
                'UPDATE noticias SET ativo=1, status_curadoria="aprovado" WHERE id=%s',
                (artigo_id,)
            )
            print(f'  ✅ Aprovado (sem remoções)')

        for a in alertas:
            print(f'  {a}')
            alertas_total += 1

        print()

    conn.commit()
    conn.close()

    print(f'{"="*60}')
    print(f'Resultado: {total} processados | {limpos} limpos | {alertas_total} alertas')
    print(f'{"="*60}\n')

if __name__ == '__main__':
    main()
