<?php
declare(strict_types=1);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
$siteConfig = dirname(__DIR__) . '/include/config_font.inc.php';
if (is_readable($siteConfig)) {
    require_once $siteConfig;
    $db = new PDOConfig();
    $perguntas_e_respostas_menu = PerguntasERespostasAreas::getGrupoAreasAllMenu($db);
    try {
        $noticias_sidebar = HistoricoHome::getNoticiasHomeLerLateral($db, date('Y-m-d'));
        $perguntas_sidebar = PerguntasERespostas::getAllLer($db, 0, 5);
    } catch (Throwable $e) {
        $noticias_sidebar = [];
        $perguntas_sidebar = [];
    }
}
$titulo_site = 'Comparador de juros | SOS Consumidor';
$meta_keywords = 'juros abusivos, taxa média Banco Central, empréstimo, financiamento';
$description_site = 'Compare a taxa do seu contrato com a média oficial do Banco Central para a mesma modalidade e período.';
$defaultReference = date('Y-m', strtotime('-2 months'));
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <?php if (defined('PROJECT_PATH')): ?>
        <?php include PROJECT_PATH . 'head.inc.php'; ?>
    <?php else: ?>
        <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Compare os juros do seu contrato — SOS Consumidor</title>
    <?php endif; ?>
    <style>
        :root { color-scheme: light; --blue: #07557f; --green: #087f5b; --ink: #1e293b; --muted: #536273; }
        * { box-sizing: border-box; }
        body { margin: 0; background: radial-gradient(circle at 10% 0%, #e7f5fb 0, transparent 34%), #f4f7fb; color: var(--ink); font: 17px/1.55 Arial, sans-serif; }
        .interest-shell { max-width: 1120px; margin: 14px auto; padding: 0 18px 32px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin: 0 4px 10px; color: #536273; font-size: 13px; }
        .topbar strong { color: var(--blue); letter-spacing: .06em; }
        .topbar a { color: var(--blue); text-decoration: none; font-weight: 700; }
        .hero-copy { position: relative; overflow: hidden; padding: 20px 28px 24px; border-radius: 16px; background: linear-gradient(120deg, #073c5d, #08759b 64%, #087f5b); color: #fff; box-shadow: 0 9px 20px #07557f24; }
        .hero-copy:after { content: '％'; position: absolute; right: 5%; top: 2px; color: #ffffff1f; font-size: 72px; font-weight: 900; line-height: 1; transform: rotate(-10deg); }
        .eyebrow { position: relative; z-index: 1; display: inline-flex; gap: 7px; align-items: center; margin-bottom: 5px; color: #f8d477; font-size: 11px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
        .interest-shell h1 { position: relative; z-index: 1; margin: 0; color: #fff; font-size: clamp(25px, 3.2vw, 36px); line-height: 1.08; letter-spacing: -.02em; max-width: 760px; }
        .interest-shell h2 { color: var(--blue); font-size: 24px; margin: 26px 0 10px; }
        .lead { position: relative; z-index: 1; margin: 7px 0 0; color: #e5f4f8; font-size: 16px; line-height: 1.4; max-width: 760px; }
        .tool-card { position: relative; z-index: 2; margin: 12px 12px 0; padding: 24px; border: 1px solid #e2ebf0; border-radius: 16px; background: #fff; box-shadow: 0 12px 26px #1e293b1a; }
        .section-label { margin: 0 0 18px; color: var(--blue); font-size: 14px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
        .notice { background: #fff7df; border-left: 4px solid #d98200; padding: 13px 16px; margin: 0 0 22px; border-radius: 0 8px 8px 0; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .interest-shell label { display: block; font-weight: 700; }
        .interest-shell select, .interest-shell input, .interest-shell button { width: 100%; margin-top: 7px; padding: 13px 14px; border: 1px solid #b8c9d4; border-radius: 9px; background: #fff; font: inherit; }
        .input-shell { position: relative; display: flex; align-items: center; margin-top: 7px; }
        .input-shell input { margin-top: 0; }
        .input-shell .prefix, .input-shell .suffix { color: #536273; font-weight: 700; pointer-events: none; white-space: nowrap; }
        .input-shell .prefix { position:absolute; left:14px; z-index:1; }
        .input-shell .suffix { position:absolute; right:12px; z-index:1; }
        .input-shell.has-prefix input { padding-left: 43px; }
        .input-shell.has-suffix input { padding-right: 30px; }
        .input-shell.has-suffix input.rate-input { text-align: right; }
        .interest-shell select:focus, .interest-shell input:focus { outline: 3px solid #0085b233; border-color: var(--blue); }
        .interest-shell button { margin-top: 22px; border: 0; background: linear-gradient(100deg, #087f5b, #0a966e); color: #fff; font-weight: 800; cursor: pointer; box-shadow: 0 8px 16px #087f5b2b; }
        .interest-shell button[disabled] { opacity: .65; cursor: wait; }
        .optional { color: var(--muted); font-size: 14px; font-weight: 400; }
        .result { display: none; margin-top: 26px; padding: 24px; border: 1px solid #c7e5da; border-radius: 14px; background: linear-gradient(135deg, #edf9f4, #f4fbf9); }
        .result.error { background: #fff0f0; color: #991b1b; }
        .cards { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin: 16px 0; }
        .card { padding: 14px; border-radius: 10px; background: #fff; border: 1px solid #d5e3eb; }
        .card small { display: block; color: var(--muted); }
        .card strong { display: block; font-size: 22px; margin-top: 4px; }
        .headline { font-size: 24px; font-weight: 800; color: var(--blue); }
        .warning { margin-top: 16px; padding: 13px; background: #fff7df; border-left: 4px solid #d98200; }
        .small { color: var(--muted); font-size: 14px; }
        .interest-shell a { color: #0645d8; }
        @media (max-width: 680px) { .interest-shell { margin: 0; padding: 10px 12px 26px; } .topbar { margin: 0 3px 8px; } .hero-copy { padding: 16px 18px 19px; border-radius: 13px; } .hero-copy:after { right: -4%; top: 2px; font-size: 58px; } .tool-card { margin: 12px 2px 0; padding: 18px 15px; } .grid, .cards { grid-template-columns: 1fr; } .headline { font-size: 21px; } }
    </style>
    <style>
        .interest-page{padding-top:0!important}
        .interest-shell{width:100%;max-width:none;margin:0;padding:24px 0 28px}
        .interest-shell .hero-copy{margin:0 0 12px;padding:16px 24px 17px;border-radius:15px}
        .interest-site-sidebar{padding:0 0 20px}
        .interest-sidebar-block{margin:0 0 18px;padding:16px;border:1px solid #dce7ed;border-radius:10px;background:#fff}
        .interest-sidebar-block h3{margin:0 0 8px;color:#07557f;font-size:20px;font-weight:800}
        .interest-sidebar-block p{margin:0 0 10px;color:#5d7080;font-size:14px;line-height:1.45}
        .interest-sidebar-block a{color:#a50006;font-weight:700;text-decoration:none}
        .interest-sidebar-block a:hover{text-decoration:underline}
        .interest-sidebar-list{margin:0 0 10px;padding:0;list-style:none}.interest-sidebar-list li{margin:0;padding:8px 0;border-bottom:1px solid #e8eef1}.interest-sidebar-list li:last-child{border-bottom:0}.interest-sidebar-list a{display:block;color:#183247;font-size:14px;line-height:1.35}.interest-sidebar-list a:hover{color:#a50006}
        @media(max-width:767px){.interest-site-sidebar{padding-top:16px}}
        @media(max-width:680px){.interest-shell{padding-top:16px}.interest-shell .hero-copy{padding:15px 18px 16px}.interest-shell .tool-card{margin:12px 2px 0}}
    </style>
    <?php if (defined('PROJECT_PATH')): ?>
        <?php include PROJECT_PATH . 'include/sos-tool-visual.inc.php'; ?>
    <?php endif; ?>
    <style id="sos-juros-compact">
        /* Escala compacta: preserva leitura e reduz a rolagem do formulário. */
        .interest-page { font-size: 16px; line-height: 1.4; }
        .interest-page .hero-copy { padding: 14px 22px 15px; margin-bottom: 10px; }
        .interest-page .hero-copy h1 { font-size: clamp(24px, 2.2vw, 29px) !important; }
        .interest-page .hero-copy .lead { display: block; width: 100%; max-width: 100%; height: auto; margin-top: 4px; font-size: 13px; line-height: 1.3; white-space: normal !important; overflow-wrap: anywhere; word-break: normal; }
        .interest-page .tool-card { padding: 18px 20px; border-radius: 14px; }
        .interest-page .section-label { margin-bottom: 12px; font-size: 13px; }
        .interest-page .notice { padding: 10px 12px; margin-bottom: 16px; line-height: 1.35; }
        .interest-page .grid { gap: 12px; }
        .interest-page label { font-size: 15px; line-height: 1.3; }
        /* Azul institucional também nos textos digitados e rótulos do formulário. */
        .interest-page label,
        .interest-page select,
        .interest-page input,
        .interest-page .input-shell { color: var(--blue); }
        .interest-page .input-shell .prefix,
        .interest-page .input-shell .suffix { color: var(--blue); }
        .interest-page input::placeholder { color: #6b8798; opacity: 1; }
        .interest-page .optional { color: #6b8798; }
        .interest-page select,
        .interest-page input,
        .interest-page button { min-height: 44px; margin-top: 5px; padding: 10px 12px; font-size: 16px; }
        .interest-page .input-shell { margin-top: 5px; }
        .interest-page .input-shell input { margin-top: 0; }
        .interest-page .input-shell .prefix { left: 12px; }
        .interest-page .input-shell .suffix { right: 11px; }
        .interest-page .input-shell.has-prefix input { padding-left: 39px; }
        .interest-page .input-shell.has-suffix input { padding-right: 28px; }
        .interest-page .input-shell.has-suffix input.rate-input { text-align: left; }
        .interest-page .input-shell.has-suffix:focus-within { outline: 3px solid #0085b233; border-color: var(--blue); }
        .interest-page .input-shell.has-suffix { display: flex; align-items: center; min-height: 44px; padding: 0 12px; border: 1px solid #b8c9d4; border-radius: 9px; background: #fff; }
        .interest-page .input-shell.has-suffix input.rate-input { flex: 0 0 7ch; width: 7ch; min-height: 0; margin: 0; padding: 0; border: 0; background: transparent; outline: 0; }
        .interest-page .input-shell.has-suffix .suffix { position: static; margin-left: 2px; }
        .interest-page .month-picker { position: relative; margin-top: 5px; }
        .interest-page .month-picker #referencia { margin-top: 0; padding-right: 52px; }
        .interest-page .month-picker-button { position: absolute; z-index: 2; top: 0; right: 0; width: 44px !important; height: 44px; min-height: 44px; margin: 0 !important; padding: 0 !important; border: 0 !important; border-radius: 0 9px 9px 0 !important; background: transparent !important; color: var(--blue); box-shadow: none !important; font-size: 22px; line-height: 1; cursor: pointer; }
        .interest-page .month-picker-button:focus { outline: 3px solid #0085b233; outline-offset: -2px; }
        .interest-page .month-picker-popover { position: absolute; z-index: 20; top: calc(100% + 6px); left: 0; width: min(310px, 100%); padding: 12px; border: 1px solid #b8c9d4; border-radius: 12px; background: #fff; box-shadow: 0 12px 28px #12324a26; }
        .interest-page .month-picker-popover[hidden] { display: none; }
        .interest-page .month-picker-year { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 10px; color: var(--blue); font-weight: 700; }
        .interest-page .month-picker-year button { width: 36px !important; min-height: 36px; margin: 0 !important; padding: 0 !important; border: 1px solid #c6d8e1; border-radius: 8px !important; background: #f7fbfd !important; color: var(--blue); box-shadow: none !important; font-size: 20px; line-height: 1; }
        .interest-page .month-picker-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 7px; }
        .interest-page .month-picker-grid button { min-height: 38px; margin: 0 !important; padding: 6px 4px !important; border: 1px solid #d1e0e7; border-radius: 8px !important; background: #fff !important; color: var(--ink); box-shadow: none !important; font-size: 14px; }
        .interest-page .month-picker-grid button:hover, .interest-page .month-picker-grid button:focus { border-color: var(--blue); background: #eef8fb !important; }
        .interest-page .month-picker-grid button.is-selected { border-color: var(--blue); background: var(--blue) !important; color: #fff; }
        .interest-page .small { font-size: 13px; line-height: 1.35; }
        .interest-page button { margin-top: 16px; }
        .interest-page .result { margin-top: 18px; padding: 18px; }
        .interest-page .simulation { margin-top: 18px; }
        .interest-page .simulation h3 { margin: 0 0 10px; color: var(--blue); font-size: 19px; }
        .interest-page .simulation-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .interest-page .simulation-card { padding: 15px; border: 1px solid #cfe1e8; border-radius: 12px; background: #fff; }
        .interest-page .simulation-card.bcb { border-color: #b9dfd0; background: #f4fcf8; }
        .interest-page .simulation-card small { display: block; color: var(--muted); font-size: 13px; }
        .interest-page .simulation-card strong { display: block; margin-top: 4px; color: var(--blue); font-size: 22px; }
        .interest-page .simulation-card p { margin: 5px 0 0; color: var(--muted); font-size: 13px; }

        @media (max-width: 680px) {
            .interest-page .hero-copy { padding: 14px 17px 15px; }
            .interest-page .hero-copy h1 { font-size: 23px !important; }
            .interest-page .hero-copy .lead { font-size: 12.5px; line-height: 1.35; }
            .interest-page .tool-card { padding: 15px 13px; }
            .interest-page .grid { gap: 11px; }
            .interest-page label { font-size: 14px; }
            .interest-page select,
            .interest-page input,
            .interest-page button { font-size: 16px; }
            .interest-page .simulation-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<?php if (defined('PROJECT_PATH')): ?>
    <?php include PROJECT_PATH . 'header.inc.php'; ?>
    <div class="c-breadcrumb"><div class="l-content-block"><div class="c-breadcrumb__list"><div class="c-breadcrumb__item"><a href="<?php echo PROJECT_ROOT; ?>">Página Inicial</a></div><div class="c-breadcrumb__item active">Comparador de juros</div></div></div></div>
    <div class="container-fluid"><div class="l-content-block"><div class="row">
        <div class="col-sm-9"><main class="c-main-content interest-page">
<?php endif; ?>
<div class="interest-shell">
    <section class="hero-copy">
        <div class="eyebrow">● Ferramenta gratuita · dados oficiais</div>
        <h1>Compare os juros do seu contrato</h1>
        <p class="lead">Veja quanto a taxa do seu empréstimo ou financiamento ficou acima ou abaixo da média do Banco Central para a mesma modalidade e no mesmo mês da contratação.</p>
    </section>
    <div class="tool-card">
    <p class="section-label">Preencha os dados da operação</p>
    <div class="notice"><strong>Importante:</strong> a referência é a taxa média das novas operações de crédito <strong>com recursos livres</strong> divulgada pelo Banco Central para a mesma modalidade e mês. É uma comparação estatística: não declara, sozinha, que os juros são abusivos e não substitui a análise do contrato por um profissional.</div>
    <form id="compare-form">
        <div class="grid">
            <label>Qual é o tipo de contrato?
                <select id="modalidade" required>
                    <option value="">Escolha uma opção</option>
                    <option value="credito-pessoal-nao-consignado">Empréstimo normal</option>
                    <option value="consignado-trabalhador-privado">Empréstimo consignado para trabalhador privado</option>
                    <option value="consignado-servidor-publico">Empréstimo consignado para servidor público</option>
                    <option value="consignado-inss">Empréstimo consignado para aposentados e pensionistas do INSS</option>
                    <option value="financiamento-veiculo">Financiamento de veículo</option>
                    <option value="credito-pessoal-total">Crédito pessoal total</option>
                    <option value="credito-livre-pf-total">Todas as operações de crédito livre para pessoa física</option>
                </select>
            </label>
            <label>Em que mês foi assinado? <span class="optional">(mês/ano — MM/AAAA)</span>
                <span class="month-picker">
                    <input id="referencia" type="text" inputmode="numeric" placeholder="MM/AAAA" value="<?= htmlspecialchars(date('m/Y', strtotime($defaultReference . '-01')), ENT_QUOTES, 'UTF-8') ?>" aria-label="Mês e ano da assinatura do contrato — formato mês e ano" autocomplete="off" maxlength="7" required>
                    <button id="referencia-calendar" class="month-picker-button" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="Abrir calendário para escolher mês e ano" title="Abrir calendário">📅</button>
                    <div id="referencia-popover" class="month-picker-popover" role="dialog" aria-label="Escolher mês e ano" hidden>
                        <div class="month-picker-year"><button id="referencia-prev-year" type="button" aria-label="Ano anterior">‹</button><span id="referencia-year"></span><button id="referencia-next-year" type="button" aria-label="Próximo ano">›</button></div>
                        <div id="referencia-months" class="month-picker-grid"></div>
                    </div>
                </span>
            </label>
            <label>Qual era a taxa mensal? <span class="optional">(preencha esta ou a anual)</span>
                <span class="input-shell has-suffix"><input id="taxa-mensal" class="rate-input" inputmode="decimal" value="0,00" aria-label="Taxa mensal em porcentagem" autocomplete="off"><span class="suffix">%</span></span>
            </label>
            <label>Ou qual era a taxa anual? <span class="optional">(preencha esta ou a mensal)</span>
                <span class="input-shell has-suffix"><input id="taxa-anual" class="rate-input" inputmode="decimal" value="0,00" aria-label="Taxa anual em porcentagem" autocomplete="off"><span class="suffix">%</span></span>
            </label>
            <label>Valor contratado
                <span class="input-shell has-prefix"><span class="prefix">R$</span><input id="valor" inputmode="decimal" placeholder="5.000,00" aria-label="Valor contratado em reais — formato R$ 5.000,00" autocomplete="off" required></span>
            </label>
            <label>Número de parcelas
                <input id="parcelas" type="number" min="1" max="480" placeholder="Ex.: 24" aria-label="Número de parcelas" required>
            </label>
        </div>
        <p class="small">Não envie CPF, número de contrato, foto, PDF ou qualquer documento. Basta informar os números.</p>
        <button id="submit" type="submit">Comparar meus juros</button>
    </form>
    <section id="result" class="result" aria-live="polite"></section>
    </div>
    <p class="small">Fonte: Banco Central do Brasil — Sistema de Séries Temporais (SGS). As médias são ponderadas pelas novas operações do período e podem não refletir as condições específicas de cada consumidor.</p>
</div>
<?php if (defined('PROJECT_PATH')): ?>
        </main></div>
        <div class="col-sm-3">
            <div class="sos-ajudinha-test-placement">
                <?php include PROJECT_PATH . 'include/ajudinha-widget-ab.inc.php'; ?>
            </div>
            <aside class="interest-site-sidebar">
                <section class="interest-sidebar-block"><h3>Notícias</h3><?php if (!empty($noticias_sidebar)): ?><ul class="interest-sidebar-list"><?php $sidebar_news_date = ''; foreach (array_slice($noticias_sidebar, 0, 5) as $noticia): ?><li><?php if (!empty($noticia['data_publicacao']) && $sidebar_news_date !== $noticia['data_publicacao']): ?><span class="interest-sidebar-date"><?php echo ajustaDataMostrar($noticia['data_publicacao']); ?></span><?php $sidebar_news_date = $noticia['data_publicacao']; endif; ?><a href="<?php echo htmlspecialchars(Noticias::getURL($noticia['titulo'], $noticia['id']), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($noticia['titulo'], ENT_QUOTES, 'UTF-8'); ?></a></li><?php endforeach; ?></ul><?php endif; ?><a href="<?php echo NOTICIAS_MAIS; ?>">Ver todas as notícias</a></section>
                <section class="interest-sidebar-block"><h3>Perguntas e Respostas</h3><?php if (!empty($perguntas_sidebar)): ?><ul class="interest-sidebar-list"><?php foreach ($perguntas_sidebar as $pergunta): ?><li><a href="<?php echo htmlspecialchars(PerguntasERespostas::getURL($pergunta['pergunta'], $pergunta['id']), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($pergunta['pergunta'], ENT_QUOTES, 'UTF-8'); ?></a></li><?php endforeach; ?></ul><?php endif; ?><a href="<?php echo PERGUNTAS_E_RESPOSTAS_MAIS; ?>">Ver todas as perguntas</a></section>
                <?php include PROJECT_PATH . 'menu_lateral.inc.php'; ?>
            </aside>
        </div>
    </div></div></div>
    <?php include PROJECT_PATH . 'footer.inc.php'; ?>
<?php endif; ?>
<script>
const form = document.getElementById('compare-form');
const button = document.getElementById('submit');
const result = document.getElementById('result');
const esc = (value) => { const node = document.createElement('div'); node.textContent = String(value ?? ''); return node.innerHTML; };
const sosVisitanteId = () => { const cookie = document.cookie.match(/(?:^|; )sos_anon_visitor=([^;]+)/); let id = cookie ? decodeURIComponent(cookie[1]) : ''; if (!id) { try { id = localStorage.getItem('sos_anon_visitor') || ''; } catch (error) {} } if (!/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i.test(id)) { id = ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, c => (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)); } try { localStorage.setItem('sos_anon_visitor', id); } catch (error) {} document.cookie = 'sos_anon_visitor=' + encodeURIComponent(id) + '; Max-Age=31536000; Path=/; SameSite=Lax'; return id; };
const sosRegistrarResposta = () => { fetch('/ia_consumidor/usage_event.php', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ferramenta: 'juros', visitante_id: sosVisitanteId() }), keepalive: true }).catch(() => {}); };
const rateValue = (input) => { const text = input.value.trim(); if (!text || text === '0,00' || text === '0' || text === '0,0' || text === '0,000') return ''; return text; };
const numberValue = (id) => { const input = document.getElementById(id); const value = input.classList.contains('rate-input') ? rateValue(input) : input.value.trim(); return value.replace(/\./g, '').replace(',', '.'); };
const pct = (value) => Number(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%';
const brl = (value) => Number(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
const monthlyInput = document.getElementById('taxa-mensal');
const annualInput = document.getElementById('taxa-anual');
const referenceInput = document.getElementById('referencia');
const referenceCalendar = document.getElementById('referencia-calendar');
const referencePopover = document.getElementById('referencia-popover');
const referenceYearLabel = document.getElementById('referencia-year');
const referenceMonths = document.getElementById('referencia-months');
const referencePrevYear = document.getElementById('referencia-prev-year');
const referenceNextYear = document.getElementById('referencia-next-year');
const monthNames = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
const monthShortNames = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
const currentYear = new Date().getFullYear();
let pickerYear = currentYear;
const formatReference = (value) => {
    const match = String(value || '').match(/^(\d{4})-(0[1-9]|1[0-2])$/);
    return match ? `${match[2]}/${match[1]}` : '';
};
const normalizeReference = (value) => {
    const digits = String(value || '').replace(/\D/g, '').slice(0, 6);
    return digits.length > 2 ? `${digits.slice(0, 2)}/${digits.slice(2)}` : digits;
};
const selectedReference = () => {
    const match = referenceInput.value.match(/^(0[1-9]|1[0-2])\/(\d{4})$/);
    return match ? { month: Number(match[1]), year: Number(match[2]) } : null;
};
const renderReferenceMonths = () => {
    const selected = selectedReference();
    pickerYear = Math.min(currentYear + 1, Math.max(2000, pickerYear));
    referenceYearLabel.textContent = String(pickerYear);
    referenceMonths.innerHTML = monthNames.map((name, index) => {
        const month = index + 1;
        const isSelected = selected && selected.year === pickerYear && selected.month === month;
        return `<button type="button" data-month="${String(month).padStart(2, '0')}" class="${isSelected ? 'is-selected' : ''}" aria-label="${name} de ${pickerYear}" aria-pressed="${isSelected ? 'true' : 'false'}">${monthShortNames[index]}</button>`;
    }).join('');
    referenceMonths.querySelectorAll('button').forEach((monthButton) => monthButton.addEventListener('click', () => {
        referenceInput.value = `${monthButton.dataset.month}/${pickerYear}`;
        referenceInput.dispatchEvent(new Event('change', { bubbles: true }));
        referencePopover.hidden = true;
        referenceCalendar.setAttribute('aria-expanded', 'false');
        referenceInput.focus();
    }));
};
referenceInput.addEventListener('input', () => { referenceInput.value = normalizeReference(referenceInput.value); });
const openReferencePicker = () => {
    const selected = selectedReference();
    pickerYear = selected ? selected.year : currentYear;
    renderReferenceMonths();
    referencePopover.hidden = false;
    referenceCalendar.setAttribute('aria-expanded', 'true');
};
referenceCalendar.addEventListener('click', () => {
    if (referencePopover.hidden) openReferencePicker();
    else {
        referencePopover.hidden = true;
        referenceCalendar.setAttribute('aria-expanded', 'false');
    }
});
referenceInput.addEventListener('click', openReferencePicker);
referenceInput.addEventListener('keydown', (event) => {
    if ((event.key === 'Enter' || event.key === 'ArrowDown') && referencePopover.hidden) {
        event.preventDefault();
        openReferencePicker();
        referenceMonths.querySelector('button')?.focus();
    }
});
referenceCalendar.setAttribute('aria-expanded', 'false');
referencePrevYear.addEventListener('click', () => { pickerYear -= 1; renderReferenceMonths(); });
referenceNextYear.addEventListener('click', () => { pickerYear += 1; renderReferenceMonths(); });
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !referencePopover.hidden) {
        referencePopover.hidden = true;
        referenceCalendar.setAttribute('aria-expanded', 'false');
        referenceCalendar.focus();
    }
});
document.addEventListener('click', (event) => {
    if (!referencePopover.hidden && !referencePopover.contains(event.target) && event.target !== referenceCalendar && event.target !== referenceInput) {
        referencePopover.hidden = true;
        referenceCalendar.setAttribute('aria-expanded', 'false');
    }
});
const formatRate = (value) => {
    const digits = String(value || '').replace(/\D/g, '').slice(0, 10);
    const normalized = digits.replace(/^0+(?=\d)/, '') || '0';
    const padded = normalized.padStart(3, '0');
    return `${padded.slice(0, -2)},${padded.slice(-2)}`;
};
const valueInput = document.getElementById('valor');
const normalizeMoneyTyping = (value) => {
    const raw = String(value || '').replace(/[^\d,]/g, '');
    if (!raw) return '';
    const comma = raw.indexOf(',');
    const integerDigits = (comma >= 0 ? raw.slice(0, comma) : raw)
        .replace(/\D/g, '')
        .replace(/^0+(?=\d)/, '') || '0';
    const integer = Number(integerDigits).toLocaleString('pt-BR');
    if (comma < 0) return integer;
    const decimals = raw.slice(comma + 1).replace(/\D/g, '').slice(0, 2);
    return `${integer},${decimals}`;
};
const formatMoneyBlur = (value) => {
    const normalized = normalizeMoneyTyping(value);
    if (!normalized) return '';
    const [integer, decimals = ''] = normalized.split(',');
    return `${integer},${(decimals + '00').slice(0, 2)}`;
};
valueInput.addEventListener('input', () => {
    valueInput.value = normalizeMoneyTyping(valueInput.value);
});
valueInput.addEventListener('focus', () => {
    if (valueInput.value === '0,00') valueInput.select();
});
const commitMoneyValue = () => {
    valueInput.value = formatMoneyBlur(valueInput.value);
};
valueInput.addEventListener('blur', commitMoneyValue);
valueInput.addEventListener('change', commitMoneyValue);
form.addEventListener('submit', commitMoneyValue);
[monthlyInput, annualInput].forEach((input) => {
    input.addEventListener('input', () => { input.value = formatRate(input.value); });
    input.addEventListener('focus', () => { if (input.value === '0,00') input.select(); });
    input.addEventListener('blur', () => {
        const value = rateValue(input);
        if (value === '') { input.value = '0,00'; return; }
        const normalized = Number(value.replace(',', '.'));
        if (Number.isFinite(normalized)) input.value = normalized.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    });
});
monthlyInput.addEventListener('input', () => { annualInput.disabled = rateValue(monthlyInput) !== ''; });
annualInput.addEventListener('input', () => { monthlyInput.disabled = rateValue(annualInput) !== ''; });

form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const reference = referenceInput.value.trim();
    const match = reference.match(/^(0[1-9]|1[0-2])\/(\d{4})$/);
    if (!match) {
        result.className = 'result error'; result.style.display = 'block';
        result.textContent = 'Escolha o mês e o ano no calendário.';
        return;
    }
    const mes = match[1];
    const ano = match[2];
    if ((rateValue(monthlyInput) === '') === (rateValue(annualInput) === '')) {
        result.className = 'result error'; result.style.display = 'block';
        result.textContent = 'Informe apenas uma taxa: a mensal ou a anual.';
        return;
    }
    result.className = 'result';
    result.style.display = 'block';
    result.textContent = 'Consultando a média do Banco Central…';
    button.disabled = true;
    try {
        const response = await fetch('api.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ modalidade: document.getElementById('modalidade').value, ano, mes, taxa_mensal: numberValue('taxa-mensal'), taxa_anual: numberValue('taxa-anual'), valor: numberValue('valor'), parcelas: document.getElementById('parcelas').value })
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'Não foi possível fazer a comparação.');
        const diffMonthly = Number(data.diferenca_percentual_mensal ?? data.diferenca_percentual);
        const diffAnnual = Number(data.diferenca_percentual_anual ?? data.diferenca_percentual);
        const diffText = (value) => value >= 0 ? `${pct(value)} acima da média` : `${pct(Math.abs(value))} abaixo da média`;
        let html = `<div class="headline">${esc(data.faixa)}: mensal ${esc(diffText(diffMonthly))}; anual ${esc(diffText(diffAnnual))}</div>`;
        html += `<p>Comparação para <strong>${esc(data.modalidade)}</strong>, em <strong>${esc(data.referencia)}</strong>.</p>`;
        html += '<div class="cards">';
        html += `<div class="card"><small>Sua taxa mensal</small><strong>${pct(data.taxa_contrato_mensal)}</strong></div>`;
        html += `<div class="card"><small>Média BCB mensal</small><strong>${pct(data.taxa_media_bcb_mensal)}</strong></div>`;
        html += `<div class="card"><small>Sua taxa anual</small><strong>${pct(data.taxa_contrato_anual_efetiva)}</strong></div>`;
        html += `<div class="card"><small>Média BCB anual oficial</small><strong>${pct(data.taxa_media_bcb_anual)}</strong></div>`;
        html += '</div>';
        if (data.simulacao) {
            html += '<section class="simulation" aria-label="Comparação do total do contrato"><h3>Quanto você pagaria no contrato?</h3><div class="simulation-grid">';
            html += `<div class="simulation-card"><small>Com a taxa do seu contrato</small><strong>${brl(data.simulacao.total_contrato)}</strong><p>${esc(data.simulacao.parcelas)} parcelas de ${brl(data.simulacao.valor_parcela_contrato)}</p></div>`;
            html += `<div class="simulation-card bcb"><small>Com a taxa média do Banco Central</small><strong>${brl(data.simulacao.total_bcb)}</strong><p>${esc(data.simulacao.parcelas)} parcelas de ${brl(data.simulacao.valor_parcela_bcb)}</p></div>`;
            html += '</div><p class="small">Estimativa pelo sistema de parcelas fixas, usando o valor contratado e o número de parcelas informados.</p></section>';
        }
        html += '<div class="warning">Essa diferença é um sinal para buscar orientação, mas não prova, sozinha, que os juros sejam abusivos. A análise jurídica considera o contrato e as circunstâncias do caso.</div>';
        html += `<p class="small">Séries BCB mensal ${esc(data.fonte.serie_mensal)} e anual ${esc(data.fonte.serie_anual)} — <a target="_blank" rel="noopener" href="${esc(data.fonte.url)}">consultar fonte oficial</a>.</p>`;
        result.innerHTML = html;
        sosRegistrarResposta();
    } catch (error) {
        result.className = 'result error';
        result.textContent = error.message;
    } finally { button.disabled = false; }
});
</script>
</body>
</html>
