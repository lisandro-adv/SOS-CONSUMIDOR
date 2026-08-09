<?php
declare(strict_types=1);
require_once __DIR__ . '/csrf.php';
$csrfToken = sos_csrf_token();
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
$titulo_site = 'Cálculos | SOS Consumidor';
$meta_keywords = 'cálculos financeiros, aluguel, dívidas, férias, décimo terceiro, juros compostos';
$description_site = 'Ferramentas de cálculo financeiro, aluguel, dívidas vencidas e direitos trabalhistas.';
$defaultMonth = date('Y-m');
$defaultMonthDisplay = date('m/Y');
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <?php if (defined('PROJECT_PATH')): ?>
        <?php include PROJECT_PATH . 'head.inc.php'; ?>
    <?php else: ?>
        <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Cálculos SOS Consumidor</title>
    <?php endif; ?>
    <style>
        :root { --blue:#07557f; --blue-2:#0b789d; --green:#087f5b; --gold:#f2b83f; --ink:#183247; --muted:#5d7080; --line:#dce7ed; }
        *{box-sizing:border-box} body{margin:0;background:#f4f8fb;color:var(--ink);font:16px/1.5 Arial,sans-serif}.calculations-shell{max-width:1180px;margin:0 auto;padding:14px 18px 40px}.topbar{display:flex;justify-content:space-between;align-items:center;margin:0 4px 12px;font-size:13px}.topbar strong{color:var(--blue);letter-spacing:.07em}.topbar a{color:var(--blue);font-weight:700;text-decoration:none}.hero{padding:24px 30px 26px;border-radius:16px;background:linear-gradient(118deg,#073c5d,#08759b 64%,#087f5b);color:#fff;box-shadow:0 10px 24px #07557f22}.hero .eyebrow{margin:0 0 4px;color:#f6d47a;font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase}.hero h1{margin:0;font-size:clamp(28px,4vw,42px);line-height:1.06}.hero p{max-width:780px;margin:9px 0 0;color:#e8f5f7;font-size:17px}.privacy{margin:12px 0 0;color:#617788;font-size:13px}.source-strip{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:16px 0 12px;padding:10px 14px;border:1px solid var(--line);border-radius:10px;background:#fff;color:var(--muted);font-size:13px}.source-strip strong{color:var(--green)}.source-list{display:flex;gap:8px;flex-wrap:wrap}.source-chip{padding:3px 8px;border-radius:999px;background:#edf8f3;color:#087f5b}.layout{display:grid;grid-template-columns:280px minmax(0,1fr);gap:16px;align-items:start}.catalog,.workspace{border:1px solid var(--line);border-radius:14px;background:#fff;box-shadow:0 8px 22px #1832470d}.catalog{padding:12px}.catalog h2{margin:4px 7px 10px;color:var(--blue);font-size:16px}.catalog-group{margin:15px 7px 5px;padding-top:8px;border-top:1px solid #e5eef2;color:var(--blue);font-size:12px;font-weight:800;letter-spacing:.06em;text-transform:uppercase}.catalog-group:first-of-type{margin-top:4px;border-top:0;padding-top:0}.calc-choice{display:block;width:100%;margin:6px 0;padding:12px 13px;border:1px solid #d9e6ec;border-radius:9px;background:#fff;color:var(--ink);text-align:left;font:700 14px/1.25 Arial;cursor:pointer}.calc-choice:hover,.calc-choice.active{border-color:var(--green);background:#edf9f4}.calc-choice small{display:block;margin-top:3px;color:var(--muted);font-size:12px;font-weight:400}.workspace{padding:24px}.calc-panel{display:none}.calc-panel.active{display:block}.calc-panel h2{margin:0;color:var(--blue);font-size:25px;line-height:1.15}.calc-panel .intro{margin:7px 0 20px;color:var(--muted)}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}.field{display:block;font-weight:700}.field.full{grid-column:1/-1}.field small{display:block;color:var(--muted);font-size:12px;font-weight:400}.field input,.field select{width:100%;margin-top:6px;padding:12px 13px;border:1px solid #b8cbd6;border-radius:8px;background:#fff;color:var(--ink);font:inherit}.field input:focus,.field select:focus{outline:3px solid #087f5b22;border-color:var(--green)}.suffix{color:var(--muted);font-size:13px;font-weight:400}.primary{width:auto;min-width:220px;margin-top:20px;padding:13px 18px;border:0;border-radius:8px;background:linear-gradient(100deg,#087f5b,#0a966e);color:#fff;font:800 15px Arial;cursor:pointer;box-shadow:0 6px 13px #087f5b26}.primary:disabled{opacity:.65;cursor:wait}.result{display:none;margin-top:22px;padding:17px;border:1px solid #c4e4d6;border-radius:10px;background:#f0faf5}.result.show{display:block}.result.error{border-color:#f1c3c3;background:#fff1f1;color:#8c2020}.result h3{margin:0 0 9px;color:var(--blue);font-size:20px}.metrics{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:9px;margin:12px 0}.metric{padding:11px;border-radius:8px;background:#fff;border:1px solid #dce9ee}.metric small{display:block;color:var(--muted)}.metric strong{display:block;margin-top:3px;font-size:20px}.memory{width:100%;border-collapse:collapse;margin-top:14px;background:#fff;font-size:13px}.memory th,.memory td{padding:7px;border:1px solid #dce7ed;text-align:right}.memory th:first-child,.memory td:first-child{text-align:left}.note{margin-top:12px;color:var(--muted);font-size:13px}.formula{margin-top:12px;padding:10px 12px;border-left:3px solid var(--gold);background:#fff9e8;font-size:13px}.loading{color:var(--muted)}@media(max-width:820px){.layout{grid-template-columns:1fr}.catalog{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:6px}.catalog h2,.catalog-group{grid-column:1/-1}.calc-choice{margin:0}.workspace{padding:19px}}@media(max-width:580px){.calculations-shell{padding:10px 12px 30px}.hero{padding:19px 18px 21px}.hero h1{font-size:29px}.hero p{font-size:15px}.catalog{display:block}.calc-choice{margin:6px 0}.workspace{padding:17px 14px}.form-grid,.metrics{grid-template-columns:1fr}.field.full{grid-column:auto}.primary{width:100%}}
    </style>
    <style>
        .calculations-page{padding-top:0!important}
        .calculations-shell{width:100%;max-width:none;padding:0 0 28px}
        .calculations-site-sidebar{padding:0 0 20px}
        .site-sidebar-block{margin:0 0 18px;padding:16px;border:1px solid #dce7ed;border-radius:10px;background:#fff}
        .site-sidebar-block h3{margin:0 0 8px;color:#07557f;font-size:20px;font-weight:800}
        .site-sidebar-block p{margin:0 0 10px;color:#5d7080;font-size:14px;line-height:1.45}
        .site-sidebar-block a{color:#a50006;font-weight:700;text-decoration:none}
        .site-sidebar-block a:hover{text-decoration:underline}
        .site-sidebar-list{margin:0 0 10px;padding:0;list-style:none}.site-sidebar-list li{margin:0;padding:8px 0;border-bottom:1px solid #e8eef1}.site-sidebar-list li:last-child{border-bottom:0}.site-sidebar-list a{display:block;color:#183247;font-size:14px;line-height:1.35}.site-sidebar-list a:hover{color:#a50006}
        @media(max-width:767px){.calculations-site-sidebar{padding-top:16px}}
    </style>
    <style>
        /* Refinamento visual da ferramenta, mantendo os componentes do site. */
        .calculations-page{background:transparent!important}
        .calculations-shell{padding-top:24px}
        .calculations-shell .hero{position:relative;margin-bottom:12px;padding:16px 24px 17px;border-radius:15px;overflow:hidden}
        .calculations-shell .hero:after{content:'＋';position:absolute;right:27px;top:-17px;color:#ffffff22;font-size:94px;font-weight:900;line-height:1;transform:rotate(12deg);pointer-events:none}
        .calculations-shell .hero h1,.calculations-shell .hero p,.calculations-shell .hero .eyebrow{position:relative;z-index:1}
        .calculations-shell .hero .eyebrow{margin-bottom:3px;font-size:10px;letter-spacing:.08em}
        .calculations-shell .hero h1{max-width:700px;margin:0;font-size:clamp(23px,2.4vw,31px)!important;line-height:1.1;letter-spacing:-.015em}
        .calculations-shell .hero p{max-width:100%;margin:5px 0 0;font-size:14px;line-height:1.35;white-space:normal;overflow-wrap:break-word;word-break:normal}
        .calculations-shell .privacy{margin:0 3px 15px;font-size:12px}
        .calculations-shell .source-strip{margin:0 0 20px;padding:12px 15px;border-left:4px solid var(--green);box-shadow:0 4px 12px #18324708}
        .calculations-shell .layout{grid-template-columns:250px minmax(0,1fr);gap:20px}
        .calculations-shell .catalog{padding:16px;border-radius:16px;box-shadow:0 8px 22px #18324710}
        .calculations-shell .catalog h2{margin:1px 4px 14px;font-size:17px}
        .calculations-shell .catalog-group{margin:17px 4px 6px;padding-top:10px;font-size:11px;letter-spacing:.08em}
        .calculations-shell .catalog-group:first-of-type{margin-top:2px}
        .calculations-shell .calc-choice{position:relative;margin:7px 0;padding:11px 11px 11px 14px;border-color:#e0eaef;border-radius:10px;transition:transform .16s ease,box-shadow .16s ease,border-color .16s ease}
        .calculations-shell .calc-choice{display:grid;grid-template-columns:50px minmax(0,1fr) 16px;align-items:center;gap:10px;text-align:left}
        .calculations-shell .calc-choice-icon{display:grid;place-items:center;width:50px;height:50px;border-radius:50%;background:#eaf4fb;color:#0764b1;font-size:0;line-height:1}
        .calculations-shell .calc-choice-icon svg{display:block;width:35px;height:35px;overflow:visible;fill:none;stroke:currentColor;stroke-width:2.7;stroke-linecap:round;stroke-linejoin:round}
        .calculations-shell .calc-choice-icon svg .icon-fill{fill:currentColor;stroke:none}
        .calculations-shell .calc-choice-icon svg .icon-paper{fill:#fff;stroke:currentColor}
        .calculations-shell .calc-choice-icon.icon-percent svg{width:36px;height:36px}
        .calculations-shell .calc-choice-icon.icon-thirteenth svg{width:37px;height:37px}
        @media(max-width:580px){.calculations-shell .calc-choice{grid-template-columns:46px minmax(0,1fr) 16px}.calculations-shell .calc-choice-icon{width:46px;height:46px}.calculations-shell .calc-choice-icon svg{width:32px;height:32px}}
        .calculations-shell .calc-choice-copy{min-width:0}
        .calculations-shell .calc-choice-title{display:block;color:var(--ink);font-weight:800;font-size:13px;line-height:1.18}
        .calculations-shell .calc-choice-arrow{display:block;color:#183247;font-size:27px;font-weight:400;line-height:1;justify-self:end;transition:transform .16s ease}
        .calculations-shell .calc-choice:hover .calc-choice-arrow,.calculations-shell .calc-choice:focus-visible .calc-choice-arrow{transform:translateX(2px)}
        .calculations-shell .calc-choice:focus-visible{outline:3px solid #087f5b33;outline-offset:2px}
        .calculations-shell .calc-choice.active .calc-choice-icon{background:#eaf4fb;color:#0764b1}
        .calculations-shell .calc-choice.active{padding-left:11px}
        .calculations-shell .calc-choice small{margin-top:4px;font-size:11px;line-height:1.2}
        /* Mesmo padrão dos campos da página /juros/. */
        .calculations-shell .field{color:var(--blue);font-size:15px;line-height:1.3}
        .calculations-shell .field input,.calculations-shell .field select{min-height:44px;margin-top:5px;padding:10px 12px;border:1px solid #b8c9d4;border-radius:9px;background:#fff;color:var(--blue);font-size:16px}
        .calculations-shell .field input::placeholder{color:#6b8798;opacity:1}
        .calculations-shell .field input:focus,.calculations-shell .field select:focus{outline:3px solid #0085b233;border-color:var(--blue)}
        .calculations-shell .mask-shell{display:flex;align-items:center;min-height:44px;margin-top:5px;padding:0 12px;border:1px solid #b8c9d4;border-radius:9px;background:#fff;overflow:hidden;transition:border-color .16s ease,box-shadow .16s ease}
        .calculations-shell .mask-shell:focus-within{border-color:var(--blue);box-shadow:0 0 0 3px #0085b233}
        .calculations-shell .mask-shell input{flex:1;min-width:0;min-height:42px;margin:0;padding:10px 0;border:0!important;border-radius:0!important;background:transparent;color:var(--blue);outline:0!important;box-shadow:none!important}
        .calculations-shell .mask-affix,.calculations-shell .mask-suffix{padding:0;color:var(--blue);font-weight:700;white-space:nowrap;pointer-events:none}
        .calculations-shell .mask-shell.has-prefix input{padding-left:4px}
        .calculations-shell .mask-shell.has-suffix input{flex:0 1 7ch;width:7ch;min-height:0;padding:0}
        .calculations-shell .mask-shell.has-suffix .mask-suffix{margin-left:2px}
        .calculations-shell .month-picker{position:relative;display:block;margin-top:5px}
        .calculations-shell .month-picker .month-display{margin-top:0;padding-right:52px}
        .calculations-shell .month-picker-button{position:absolute;z-index:2;top:0;right:0;width:44px;height:44px;min-height:44px;margin:0;padding:0;border:0;border-radius:0 9px 9px 0;background:transparent;color:var(--blue);box-shadow:none;font-size:22px;line-height:1;cursor:pointer}
        .calculations-shell .month-picker-button:focus{outline:3px solid #0085b233;outline-offset:-2px}
        .calculations-shell .month-picker-popover{position:absolute;z-index:20;top:calc(100% + 6px);left:0;width:min(310px,100%);padding:12px;border:1px solid #b8c9d4;border-radius:12px;background:#fff;box-shadow:0 12px 28px #12324a26}
        .calculations-shell .month-picker-popover[hidden]{display:none}
        .calculations-shell .month-picker-year{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:10px;color:var(--blue);font-weight:700}
        .calculations-shell .month-picker-year button{width:36px;min-height:36px;margin:0;padding:0;border:1px solid #c6d8e1;border-radius:8px;background:#f7fbfd;color:var(--blue);font-size:20px;line-height:1;cursor:pointer}
        .calculations-shell .month-picker-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:7px}
        .calculations-shell .month-picker-grid button{min-height:38px;margin:0;padding:6px 4px;border:1px solid #d1e0e7;border-radius:8px;background:#fff;color:var(--ink);font-size:14px;cursor:pointer}
        .calculations-shell .month-picker-grid button:hover,.calculations-shell .month-picker-grid button:focus{border-color:var(--blue);background:#eef8fb}
        .calculations-shell .month-picker-grid button.is-selected{border-color:var(--blue);background:var(--blue);color:#fff}
        .calculations-shell input[type="date"]::-webkit-calendar-picker-indicator{cursor:pointer}
        .calculations-shell .calc-choice:hover{transform:translateY(-1px);box-shadow:0 5px 12px #087f5b14}
        .calculations-shell .calc-choice.active{border-color:#a9dcc8;border-left:4px solid var(--green);padding-left:11px;box-shadow:0 5px 12px #087f5b12}
        .calculations-shell .workspace{position:sticky;top:18px;align-self:start;min-height:520px;padding:30px;border-radius:16px;box-shadow:0 8px 22px #18324712}
        .calculations-shell .calc-panel h2{font-size:27px;letter-spacing:-.015em}
        .calculations-shell .calc-panel .intro{max-width:720px;margin:8px 0 23px;font-size:15px}
        .calculations-shell .field input:not(:focus):hover,.calculations-shell .field select:not(:focus):hover{border-color:#91b5c3}
        .calculations-shell .primary{border-radius:10px;letter-spacing:.01em;transition:transform .16s ease,box-shadow .16s ease}
        .calculations-shell .primary:hover{transform:translateY(-1px);box-shadow:0 8px 16px #087f5b35}
        .calculations-shell .result{animation:calc-result-in .22s ease-out}
        @keyframes calc-result-in{from{opacity:0;transform:translateY(5px)}to{opacity:1;transform:translateY(0)}}
        @media(max-width:820px){
            .calculations-shell .catalog,.calculations-shell .workspace{position:static}
            .calculations-shell .layout{grid-template-columns:minmax(0,1fr);gap:14px}
            .calculations-shell .hero p{white-space:normal}
        }
        @media(max-width:580px){
            .calculations-shell{padding-top:16px}
            .calculations-shell .hero{padding:15px 18px 16px}
            .calculations-shell .hero:after{right:-2px;top:-12px;font-size:72px}
            .calculations-shell .hero h1{font-size:24px!important}
            .calculations-shell .hero p{font-size:13px}
            .calculations-shell .source-strip{font-size:12px}
            .calculations-shell .layout{grid-template-columns:minmax(0,1fr)}
            .calculations-shell .catalog,.calculations-shell .workspace{width:100%;min-width:0}
            .calculations-shell .workspace{min-height:0;padding:21px 17px}
            .calculations-shell .form-grid,.calculations-shell .metrics{grid-template-columns:minmax(0,1fr)}
            .calculations-shell .field.full{grid-column:auto}
            .calculations-shell .primary{width:100%;min-width:0}
            .calculations-shell .calc-panel h2{font-size:24px}
        }
        /* Mobile hardening: keep every grid child inside the viewport and make
           the amortization table scrollable instead of widening the page. */
        .calculations-shell .layout,
        .calculations-shell .catalog,
        .calculations-shell .workspace,
        .calculations-shell .calc-panel,
        .calculations-shell .result,
        .calculations-shell .source-strip,
        .calculations-site-sidebar,
        .sos-ajudinha-test-placement{min-width:0;max-width:100%}
        .calculations-shell .hero h1,
        .calculations-shell .hero p,
        .calculations-shell .calc-panel h2,
        .calculations-shell .calc-panel .intro,
        .calculations-shell .field,
        .calculations-shell .source-strip{overflow-wrap:anywhere}
        .calculations-shell .memory-scroll{max-width:100%;overflow-x:auto;overflow-y:hidden;-webkit-overflow-scrolling:touch}
        .calculations-shell .memory{min-width:520px}
        @media(max-width:767px){
            /* app.css fixes the header to the viewport on phones. Reserve its
               actual mobile height so the breadcrumb/hero are not covered. */
            body{padding-top:128px}
            .calculations-site-sidebar{overflow:visible}
            .sos-ajudinha-test-placement{width:100%;overflow:visible}
            .sos-ajudinha-test-placement .sos-ajudinha{width:100%;max-width:100%}
        }
        @media(max-width:400px){body{padding-top:76px}}
    </style>
    <?php if (defined('PROJECT_PATH')): ?>
        <?php include PROJECT_PATH . 'include/sos-tool-visual.inc.php'; ?>
    <?php endif; ?>
</head>
<body>
<?php if (defined('PROJECT_PATH')): ?>
    <?php include PROJECT_PATH . 'header.inc.php'; ?>
    <div class="c-breadcrumb"><div class="l-content-block"><div class="c-breadcrumb__list"><div class="c-breadcrumb__item"><a href="<?php echo PROJECT_ROOT; ?>">Página Inicial</a></div><div class="c-breadcrumb__item active">Cálculos</div></div></div></div>
    <div class="container-fluid"><div class="l-content-block"><div class="row">
        <div class="col-sm-9"><main class="c-main-content calculations-page">
<?php endif; ?>
<div class="calculations-shell">
    <section class="hero">
        <p class="eyebrow">Ferramentas gratuitas · fontes oficiais</p>
        <h1>Cálculos SOS Consumidor</h1>
        <p>Escolha o que você precisa calcular. Informe apenas os números do caso e veja o resultado com a memória do cálculo.</p>
    </section>
    <div class="layout">
        <aside class="catalog" aria-label="Escolha uma calculadora">
            <h2>O que você quer calcular?</h2>
            <div class="catalog-group">Dívidas vencidas</div>
            <button class="calc-choice active" data-panel="boleto"><span class="calc-choice-icon" aria-hidden="true"><svg viewBox="0 0 48 48"><path class="icon-paper" d="M12 6h23v36l-4-3-4 3-4-3-4 3-7-3z"/><path d="M17 14h13M17 20h13M17 26h8"/><circle cx="34" cy="35" r="7"/><path d="M34 31v8m-3-4h6"/></svg></span><span class="calc-choice-copy"><span class="calc-choice-title">Boleto vencido</span><small>Multa, juros e total</small></span><span class="calc-choice-arrow" aria-hidden="true">›</span></button>
            <button class="calc-choice" data-panel="update"><span class="calc-choice-icon" aria-hidden="true"><svg viewBox="0 0 48 48"><rect x="10" y="5" width="28" height="38" rx="3"/><rect x="15" y="10" width="18" height="8" rx="1"/><path d="M16 24h3m5 0h3m5 0h1M16 31h3m5 0h3m5 0h1M16 38h3m5 0h10"/></svg></span><span class="calc-choice-copy"><span class="calc-choice-title">Atualizar uma dívida</span><small>Índice + período + juros</small></span><span class="calc-choice-arrow" aria-hidden="true">›</span></button>
            <div class="catalog-group">Cálculos financeiros</div>
            <button class="calc-choice" data-panel="loan"><span class="calc-choice-icon" aria-hidden="true"><svg viewBox="0 0 48 48"><path d="m6 18 18-11 18 11"/><path d="M9 19h30M12 19v18m8-18v18m8-18v18m8-18v18M7 41h34"/></svg></span><span class="calc-choice-copy"><span class="calc-choice-title">Empréstimo ou financiamento</span><small>Price ou SAC</small></span><span class="calc-choice-arrow" aria-hidden="true">›</span></button>
            <button class="calc-choice" data-panel="purchase"><span class="calc-choice-icon" aria-hidden="true"><svg viewBox="0 0 48 48"><rect class="icon-paper" x="6" y="12" width="28" height="19" rx="2"/><circle cx="20" cy="21.5" r="4"/><path d="M10 16h2m16 11h2"/><circle cx="35" cy="34" r="7"/><path d="M35 30v8m-3-4h6"/></svg></span><span class="calc-choice-copy"><span class="calc-choice-title">À vista ou parcelado?</span><small>Compare o custo</small></span><span class="calc-choice-arrow" aria-hidden="true">›</span></button>
            <button class="calc-choice" data-panel="interest"><span class="calc-choice-icon icon-percent" aria-hidden="true"><svg viewBox="0 0 48 48"><circle cx="24" cy="24" r="16"/><path d="m18 30 12-12"/><path d="M19 19h.01M29 29h.01"/></svg></span><span class="calc-choice-copy"><span class="calc-choice-title">Juros e valor futuro</span><small>Simples ou compostos</small></span><span class="calc-choice-arrow" aria-hidden="true">›</span></button>
            <div class="catalog-group">Aluguéis</div>
            <button class="calc-choice" data-panel="update"><span class="calc-choice-icon" aria-hidden="true"><svg viewBox="0 0 48 48"><path d="m6 22 18-15 18 15"/><path d="M10 20v21h28V20"/><path d="M19 41V28h10v13M33 14V8h5v10"/></svg></span><span class="calc-choice-copy"><span class="calc-choice-title">Reajuste de aluguel</span><small>Índice + período</small></span><span class="calc-choice-arrow" aria-hidden="true">›</span></button>
            <div class="catalog-group">Trabalhistas</div>
            <button class="calc-choice" data-panel="thirteenth"><span class="calc-choice-icon icon-thirteenth" aria-hidden="true"><svg viewBox="0 0 48 48"><rect class="icon-paper" x="6" y="12" width="28" height="19" rx="2"/><circle cx="20" cy="21.5" r="4"/><path d="M10 16h2m16 11h2"/><circle cx="35" cy="34" r="7"/><path d="M35 30v8m-3-4h6"/></svg></span><span class="calc-choice-copy"><span class="calc-choice-title">13º salário</span><small>Estimativa proporcional</small></span><span class="calc-choice-arrow" aria-hidden="true">›</span></button>
            <button class="calc-choice" data-panel="vacation"><span class="calc-choice-icon" aria-hidden="true"><svg viewBox="0 0 48 48"><rect x="9" y="10" width="30" height="29" rx="3"/><path d="M9 18h30M16 6v8m16-8v8"/><path d="m24 23 2 4 4 .5-3 3 .8 4.5-3.8-2-3.8 2 .8-4.5-3-3 4-.5z"/></svg></span><span class="calc-choice-copy"><span class="calc-choice-title">Férias + 1/3</span><small>Estimativa bruta</small></span><span class="calc-choice-arrow" aria-hidden="true">›</span></button>
            <div class="catalog-group">Conversores</div>
            <button class="calc-choice" data-panel="rate-converter"><span class="calc-choice-icon" aria-hidden="true"><svg viewBox="0 0 48 48"><rect x="11" y="5" width="26" height="38" rx="3"/><rect x="16" y="10" width="16" height="7" rx="1"/><path d="M17 24h3m4 0h3m4 0h3M17 31h3m4 0h3m4 0h3M17 38h3m4 0h9"/><path d="M6 21h5m-2-3 3 3-3 3M42 27h-5m2-3-3 3 3 3"/></svg></span><span class="calc-choice-copy"><span class="calc-choice-title">Conversor de juros</span><small>Mensal ↔ anual composto</small></span><span class="calc-choice-arrow" aria-hidden="true">›</span></button>
        </aside>
        <section class="workspace">
            <section class="calc-panel" id="panel-update">
                <h2>Atualizar valor ou dívida</h2><p class="intro">Veja como um valor pode variar entre dois meses usando um índice oficial. Os juros mensais são opcionais.</p>
                <form data-action="update_value">
                    <div class="form-grid">
                        <label class="field">Valor original<input name="valor" inputmode="decimal" data-mask="money" placeholder="0,00" autocomplete="off" required></label>
                        <label class="field">Índice<select name="indice"><option value="ipca">IPCA</option><option value="inpc">INPC</option><option value="igpm">IGP-M</option><option value="igpdi">IGP-DI</option><option value="tr">TR</option><option value="selic">Selic mensal</option></select></label>
                        <label class="field">Mês inicial <span class="suffix">(MM/AAAA)</span><span class="month-picker" data-month-picker>
                            <input class="month-display" type="text" inputmode="numeric" placeholder="MM/AAAA" value="<?= htmlspecialchars($defaultMonthDisplay, ENT_QUOTES, 'UTF-8') ?>" aria-label="Mês inicial no formato MM/AAAA" autocomplete="off" maxlength="7" pattern="(?:0[1-9]|1[0-2])/[0-9]{4}" required>
                            <input name="inicio" type="hidden" value="<?= htmlspecialchars($defaultMonth, ENT_QUOTES, 'UTF-8') ?>">
                            <button class="month-picker-button" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="Abrir calendário do mês inicial" title="Abrir calendário">📅</button>
                            <span class="month-picker-popover" role="dialog" aria-label="Escolher mês inicial" hidden><span class="month-picker-year"><button class="month-prev-year" type="button" aria-label="Ano anterior">‹</button><span class="month-year-label"></span><button class="month-next-year" type="button" aria-label="Próximo ano">›</button></span><span class="month-picker-grid"></span></span>
                        </span></label>
                        <label class="field">Mês final <span class="suffix">(MM/AAAA)</span><span class="month-picker" data-month-picker>
                            <input class="month-display" type="text" inputmode="numeric" placeholder="MM/AAAA" value="<?= htmlspecialchars($defaultMonthDisplay, ENT_QUOTES, 'UTF-8') ?>" aria-label="Mês final no formato MM/AAAA" autocomplete="off" maxlength="7" pattern="(?:0[1-9]|1[0-2])/[0-9]{4}" required>
                            <input name="fim" type="hidden" value="<?= htmlspecialchars($defaultMonth, ENT_QUOTES, 'UTF-8') ?>">
                            <button class="month-picker-button" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="Abrir calendário do mês final" title="Abrir calendário">📅</button>
                            <span class="month-picker-popover" role="dialog" aria-label="Escolher mês final" hidden><span class="month-picker-year"><button class="month-prev-year" type="button" aria-label="Ano anterior">‹</button><span class="month-year-label"></span><button class="month-next-year" type="button" aria-label="Próximo ano">›</button></span><span class="month-picker-grid"></span></span>
                        </span></label>
                        <label class="field">Juros mensais <span class="suffix">(opcional)</span><input name="juros_mensais" inputmode="decimal" data-mask="percent" placeholder="0,00" autocomplete="off"></label>
                    </div>
                    <button class="primary" type="submit">Calcular atualização</button>
                </form><div class="result" aria-live="polite"></div>
            </section>
            <section class="calc-panel active" id="panel-boleto">
                <h2>Boleto vencido</h2><p class="intro">Calcule o acréscimo informado no boleto. Confira sempre as regras do documento e do contrato.</p>
                <form data-action="boleto"><div class="form-grid">
                    <label class="field">Valor original<input name="valor" inputmode="decimal" data-mask="money" placeholder="0,00" autocomplete="off" required></label>
                    <label class="field">Multa<input name="multa" inputmode="decimal" data-mask="percent" placeholder="0,00" value="2" autocomplete="off"></label>
                    <label class="field">Data de vencimento <span class="suffix">(DD/MM/AAAA)</span><input name="vencimento" type="date" lang="pt-BR" aria-label="Data de vencimento no formato DD/MM/AAAA" required></label>
                    <label class="field">Data de pagamento <span class="suffix">(DD/MM/AAAA)</span><input name="pagamento" type="date" lang="pt-BR" aria-label="Data de pagamento no formato DD/MM/AAAA" required></label>
                    <label class="field">Juros por dia<input name="juros_dia" inputmode="decimal" data-mask="percent" placeholder="0,00" autocomplete="off"></label>
                </div><button class="primary" type="submit">Calcular boleto</button></form><div class="result" aria-live="polite"></div>
            </section>
            <section class="calc-panel" id="panel-loan">
                <h2>Empréstimo ou financiamento</h2><p class="intro">Compare a evolução das parcelas e descubra quanto será pago em juros, sem enviar contrato ou documento.</p>
                <form data-action="loan"><div class="form-grid">
                    <label class="field">Valor recebido ou financiado<input name="valor" inputmode="decimal" data-mask="money" placeholder="0,00" autocomplete="off" required></label>
                    <label class="field">Taxa mensal<input name="taxa" inputmode="decimal" data-mask="percent" placeholder="0,00" autocomplete="off" required></label>
                    <label class="field">Número de parcelas<input name="parcelas" type="number" min="1" max="480" placeholder="0" required></label>
                    <label class="field">Sistema<select name="sistema"><option value="price">Tabela Price (parcelas iguais)</option><option value="sac">SAC (parcelas decrescentes)</option></select></label>
                </div><button class="primary" type="submit">Simular contrato</button></form><div class="result" aria-live="polite"></div>
            </section>
            <section class="calc-panel" id="panel-purchase">
                <h2>Compra à vista ou parcelada?</h2><p class="intro">Compare o total nominal e o valor presente das parcelas. A taxa de comparação representa o rendimento ou custo alternativo informado.</p>
                <form data-action="cash_vs_installments"><div class="form-grid">
                    <label class="field">Preço à vista<input name="vista" inputmode="decimal" data-mask="money" placeholder="0,00" autocomplete="off" required></label>
                    <label class="field">Entrada<input name="entrada" inputmode="decimal" data-mask="money" placeholder="0,00" value="0" autocomplete="off"></label>
                    <label class="field">Valor de cada parcela<input name="parcela" inputmode="decimal" data-mask="money" placeholder="0,00" autocomplete="off" required></label>
                    <label class="field">Número de parcelas<input name="parcelas" type="number" min="1" max="480" placeholder="0" required></label>
                    <label class="field">Taxa mensal de comparação <span class="suffix">(opcional)</span><input name="taxa_comparacao" inputmode="decimal" data-mask="percent" placeholder="0,00" value="0" autocomplete="off"></label>
                </div><button class="primary" type="submit">Comparar opções</button></form><div class="result" aria-live="polite"></div>
            </section>
            <section class="calc-panel" id="panel-interest">
                <h2>Juros e valor futuro</h2><p class="intro">Calcule o montante de um valor aplicado ou de uma dívida, diferenciando juros simples e compostos.</p>
                <form data-action="interest"><div class="form-grid">
                    <label class="field">Valor inicial<input name="valor" inputmode="decimal" data-mask="money" placeholder="0,00" autocomplete="off" required></label>
                    <label class="field">Taxa por período<input name="taxa" inputmode="decimal" data-mask="percent" placeholder="0,00" autocomplete="off" required></label>
                    <label class="field">Quantidade de períodos<input name="periodos" type="number" min="1" max="1200" placeholder="0" required></label>
                    <label class="field">Tipo de juros<select name="modo"><option value="compound">Compostos</option><option value="simple">Simples</option></select></label>
                </div><button class="primary" type="submit">Calcular juros</button></form><div class="result" aria-live="polite"></div>
            </section>
            <section class="calc-panel" id="panel-rate-converter">
                <h2>Conversor de juros mensais e anuais</h2><p class="intro">Informe apenas uma taxa. A conversão usa juros compostos, como ocorre quando os juros de cada período passam a fazer parte do saldo.</p>
                <form data-action="rate_convert"><div class="form-grid">
                    <label class="field">Taxa mensal <span class="suffix">(opcional)</span><input name="mensal" inputmode="decimal" data-mask="percent" placeholder="0,00" autocomplete="off"></label>
                    <label class="field">Taxa anual <span class="suffix">(opcional)</span><input name="anual" inputmode="decimal" data-mask="percent" placeholder="0,00" autocomplete="off"></label>
                </div><button class="primary" type="submit">Converter taxa</button></form><div class="result" aria-live="polite"></div>
                <div class="formula"><strong>Fórmula:</strong> anual = (1 + mensal)<sup>12</sup> − 1. No caminho inverso: mensal = (1 + anual)<sup>1/12</sup> − 1.<br><br><strong>Por que 12% ao mês não é 144% ao ano?</strong> Multiplicar 12% por 12 dá uma conta simples. Com juros compostos, cada mês incide também sobre os juros anteriores: (1,12)<sup>12</sup> − 1 = aproximadamente <strong>289,60% ao ano</strong>.</div>
            </section>
            <section class="calc-panel" id="panel-thirteenth">
                <h2>13º salário</h2><p class="intro">Faça uma estimativa do 13º proporcional. Cada mês com pelo menos 15 dias trabalhados conta como 1/12.</p>
                <form data-action="thirteenth"><div class="form-grid">
                    <label class="field">Salário bruto<input name="salario" inputmode="decimal" data-mask="money" placeholder="0,00" autocomplete="off" required></label>
                    <label class="field">Meses trabalhados<input name="meses" type="number" min="1" max="12" placeholder="12" required></label>
                </div><button class="primary" type="submit">Calcular 13º</button></form><div class="result" aria-live="polite"></div>
            </section>
            <section class="calc-panel" id="panel-vacation">
                <h2>Férias + 1/3</h2><p class="intro">Estime a remuneração bruta das férias. O valor final pode mudar com médias, abono e descontos legais.</p>
                <form data-action="vacation"><div class="form-grid">
                    <label class="field">Salário bruto<input name="salario" inputmode="decimal" data-mask="money" placeholder="0,00" autocomplete="off" required></label>
                    <label class="field">Dias de férias<input name="dias" type="number" min="1" max="30" value="30" required></label>
                </div><button class="primary" type="submit">Calcular férias</button></form><div class="result" aria-live="polite"></div>
            </section>
        </section>
    </div>
    <p class="privacy">Não informe CPF, número de contrato, foto, PDF ou qualquer documento. Os dados digitados são usados somente para gerar o resultado.</p>
    <div class="source-strip"><strong>Índices oficiais</strong><span>Banco Central do Brasil — SGS</span><span id="source-status" class="loading">verificando atualização…</span><div id="source-list" class="source-list"></div></div>
</div>
<?php if (defined('PROJECT_PATH')): ?>
        </main></div>
        <div class="col-sm-3">
            <div class="sos-ajudinha-test-placement">
                <?php include PROJECT_PATH . 'include/ajudinha-widget-ab.inc.php'; ?>
            </div>
            <aside class="calculations-site-sidebar">
                <section class="site-sidebar-block"><h3>Notícias</h3><?php if (!empty($noticias_sidebar)): ?><ul class="site-sidebar-list"><?php $sidebar_news_date = ''; foreach (array_slice($noticias_sidebar, 0, 5) as $noticia): ?><li><?php if (!empty($noticia['data_publicacao']) && $sidebar_news_date !== $noticia['data_publicacao']): ?><span class="site-sidebar-date"><?php echo ajustaDataMostrar($noticia['data_publicacao']); ?></span><?php $sidebar_news_date = $noticia['data_publicacao']; endif; ?><a href="<?php echo htmlspecialchars(Noticias::getURL($noticia['titulo'], $noticia['id']), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($noticia['titulo'], ENT_QUOTES, 'UTF-8'); ?></a></li><?php endforeach; ?></ul><?php endif; ?><a href="<?php echo NOTICIAS_MAIS; ?>">Ver todas as notícias</a></section>
                <section class="site-sidebar-block"><h3>Perguntas e Respostas</h3><?php if (!empty($perguntas_sidebar)): ?><ul class="site-sidebar-list"><?php foreach ($perguntas_sidebar as $pergunta): ?><li><a href="<?php echo htmlspecialchars(PerguntasERespostas::getURL($pergunta['pergunta'], $pergunta['id']), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($pergunta['pergunta'], ENT_QUOTES, 'UTF-8'); ?></a></li><?php endforeach; ?></ul><?php endif; ?><a href="<?php echo PERGUNTAS_E_RESPOSTAS_MAIS; ?>">Ver todas as perguntas</a></section>
                <?php include PROJECT_PATH . 'menu_lateral.inc.php'; ?>
            </aside>
        </div>
    </div></div></div>
    <?php include PROJECT_PATH . 'footer.inc.php'; ?>
<?php endif; ?>
<script>
const CSRF_TOKEN = <?php echo json_encode($csrfToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const brl = value => Number(value || 0).toLocaleString('pt-BR',{style:'currency',currency:'BRL'});
const pct = value => Number(value || 0).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:4})+'%';
const number = value => { let s=String(value??'').trim().replace(/[^0-9,.-]/g,''); if(s.includes(',')&&s.includes('.')) s=s.replace(/\./g,''); s=s.replace(',','.'); return s===''?null:Number(s); };
const esc = value => { const n=document.createElement('div'); n.textContent=String(value??''); return n.innerHTML; };
document.querySelectorAll('.calc-choice').forEach(choice=>choice.addEventListener('click',()=>{document.querySelectorAll('.calc-choice').forEach(x=>x.classList.remove('active'));document.querySelectorAll('.calc-panel').forEach(x=>x.classList.remove('active'));choice.classList.add('active');document.getElementById('panel-'+choice.dataset.panel).classList.add('active');}));
document.querySelectorAll('input[inputmode="decimal"]').forEach(input=>{
    const percent = input.dataset.mask === 'percent';
    const shell = document.createElement('span'); shell.className='mask-shell '+(percent?'has-suffix':'has-prefix');
    const affix = document.createElement('span'); affix.className='mask-affix';
    const suffix = document.createElement('span'); suffix.className='mask-suffix';
    if(percent){ suffix.textContent='%'; } else { affix.textContent='R$'; }
    input.parentNode.insertBefore(shell,input); shell.appendChild(affix); shell.appendChild(input); shell.appendChild(suffix);
    input.addEventListener('focus',()=>input.select());
    input.addEventListener('input',()=>{input.value=input.value.replace(/[^0-9,]/g,'');const i=input.value.indexOf(',');if(i>=0)input.value=input.value.slice(0,i+1)+input.value.slice(i+1).replace(/,/g,'');});
    const formatValue=()=>{if(input.value.trim()==='')return;const parsed=number(input.value);if(Number.isFinite(parsed))input.value=parsed.toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2});};
    input.addEventListener('blur',formatValue);
    formatValue();
});
const monthNames=['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
const monthShortNames=['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
const normalizeMonth=value=>{const digits=String(value||'').replace(/\D/g,'').slice(0,6);return digits.length>2?`${digits.slice(0,2)}/${digits.slice(2)}`:digits;};
const parseMonth=value=>{const match=String(value||'').match(/^(0[1-9]|1[0-2])\/(\d{4})$/);return match?{month:Number(match[1]),year:Number(match[2])}:null;};
document.querySelectorAll('[data-month-picker]').forEach((picker,index)=>{
    const display=picker.querySelector('.month-display');
    const hidden=picker.querySelector('input[type="hidden"]');
    const calendar=picker.querySelector('.month-picker-button');
    const popover=picker.querySelector('.month-picker-popover');
    const yearLabel=picker.querySelector('.month-year-label');
    const months=picker.querySelector('.month-picker-grid');
    const prev=picker.querySelector('.month-prev-year');
    const next=picker.querySelector('.month-next-year');
    const dialogId=`month-picker-${index+1}`;
    popover.id=dialogId;calendar.setAttribute('aria-controls',dialogId);
    let pickerYear=parseMonth(display.value)?.year||new Date().getFullYear();
    const sync=()=>{const selected=parseMonth(display.value);hidden.value=selected?`${selected.year}-${String(selected.month).padStart(2,'0')}`:'';display.setCustomValidity(display.value&&!selected?'Informe o mês no formato MM/AAAA.':'');return selected;};
    const close=focusCalendar=>{popover.hidden=true;calendar.setAttribute('aria-expanded','false');if(focusCalendar)calendar.focus();};
    const renderMonths=()=>{const selected=parseMonth(display.value);yearLabel.textContent=String(pickerYear);months.innerHTML=monthNames.map((name,i)=>{const month=i+1;const active=selected&&selected.year===pickerYear&&selected.month===month;return `<button type="button" data-month="${String(month).padStart(2,'0')}" class="${active?'is-selected':''}" aria-label="${name} de ${pickerYear}" aria-pressed="${active?'true':'false'}">${monthShortNames[i]}</button>`;}).join('');months.querySelectorAll('button').forEach(button=>button.addEventListener('click',()=>{display.value=`${button.dataset.month}/${pickerYear}`;sync();close(false);display.focus();}));};
    const open=()=>{document.querySelectorAll('[data-month-picker] .month-picker-popover:not([hidden])').forEach(other=>{if(other!==popover){other.hidden=true;other.parentElement.querySelector('.month-picker-button')?.setAttribute('aria-expanded','false');}});pickerYear=parseMonth(display.value)?.year||new Date().getFullYear();renderMonths();popover.hidden=false;calendar.setAttribute('aria-expanded','true');};
    display.addEventListener('input',()=>{display.value=normalizeMonth(display.value);sync();});
    display.addEventListener('blur',sync);
    display.addEventListener('click',open);
    display.addEventListener('keydown',event=>{if((event.key==='Enter'||event.key==='ArrowDown')&&popover.hidden){event.preventDefault();open();months.querySelector('button')?.focus();}});
    calendar.addEventListener('click',()=>popover.hidden?open():close(false));
    prev.addEventListener('click',()=>{pickerYear-=1;renderMonths();});
    next.addEventListener('click',()=>{pickerYear+=1;renderMonths();});
    document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!popover.hidden)close(true);});
    document.addEventListener('click',event=>{if(!popover.hidden&&!picker.contains(event.target))close(false);});
    sync();
});
function sosVisitanteId(){const cookie=document.cookie.match(/(?:^|; )sos_anon_visitor=([^;]+)/);let id=cookie?decodeURIComponent(cookie[1]):'';if(!id){try{id=localStorage.getItem('sos_anon_visitor')||'';}catch(error){} }if(!/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i.test(id)){id=([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g,c=>(c^crypto.getRandomValues(new Uint8Array(1))[0]&15>>c/4).toString(16));}try{localStorage.setItem('sos_anon_visitor',id);}catch(error){}document.cookie='sos_anon_visitor='+encodeURIComponent(id)+'; Max-Age=31536000; Path=/; SameSite=Lax';return id;}
function sosRegistrarResposta(ferramenta){fetch('/ia_consumidor/usage_event.php',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify({ferramenta,visitante_id:sosVisitanteId()}),keepalive:true}).catch(()=>{});}
function showResult(result, html, error=false){result.className='result show'+(error?' error':'');result.innerHTML=html;result.scrollIntoView({behavior:'smooth',block:'nearest'});}
function metrics(items){return '<div class="metrics">'+items.map(x=>`<div class="metric"><small>${esc(x[0])}</small><strong>${esc(x[1])}</strong></div>`).join('')+'</div>';}
function memory(rows){if(!Array.isArray(rows)||!rows.length)return '';return '<div class="memory-scroll" role="region" aria-label="Memória do cálculo" tabindex="0"><table class="memory"><thead><tr><th>Parcela</th><th>Valor</th><th>Amortização</th><th>Juros</th><th>Saldo</th></tr></thead><tbody>'+rows.map(x=>`<tr><td>${esc(x.parcela)}</td><td>${esc(brl(x.valor))}</td><td>${esc(brl(x.amortizacao))}</td><td>${esc(brl(x.juros))}</td><td>${esc(brl(x.saldo))}</td></tr>`).join('')+'</tbody></table></div><p class="note">A tabela mostra as primeiras parcelas e a última parcela.</p>';}
function render(data){if(!data||typeof data!=='object'||typeof data.type!=='string')throw new Error('Resposta inválida do servidor.');
if(data.type==='update_value')return `<h3>Valor atualizado</h3>${metrics([['Valor original',brl(data.valor_original)],['Valor atualizado',brl(data.valor_atualizado)],['Variação do índice',pct(data.variacao_indice)]])}<p>Índice <strong>${esc(data.indice)}</strong>, período <strong>${esc(data.periodo)}</strong>, em ${esc(data.meses)} mês(es). Fator aplicado: <strong>${esc(data.fator)}</strong>.</p><p class="note">Fonte: ${esc(data.fonte)}. O resultado é uma estimativa matemática e não define, sozinho, o valor jurídico da dívida.</p>`;
if(data.type==='boleto')return `<h3>Boleto recalculado</h3>${metrics([['Dias em atraso',data.dias_atraso],['Multa',brl(data.multa)],['Juros',brl(data.juros)],['Total',brl(data.total)]])}<p class="note">${esc(data.observacao)}</p>`;
if(data.type==='interest')return `<h3>Resultado dos juros ${esc(data.modo)}</h3>${metrics([['Valor inicial',brl(data.principal)],['Juros',brl(data.juros)],['Montante',brl(data.montante)]])}<p>Taxa: <strong>${esc(pct(data.taxa))}</strong> por período · ${esc(data.periodos)} período(s).</p>`;
if(data.type==='rate_convert')return `<h3>Conversão com juros compostos</h3>${metrics([['Taxa mensal equivalente',pct(data.mensal)],['Taxa anual equivalente',pct(data.anual)]])}<p>${esc(data.explicacao)}</p><div class="formula"><strong>${esc(data.formula)}</strong><br><br>12% ao mês aplicado por 12 meses resulta em aproximadamente <strong>289,60% ao ano</strong>. Os 144% são apenas o resultado de multiplicar 12 por 12, sem considerar a capitalização.</div>`;
if(data.type==='thirteenth')return `<h3>Estimativa do 13º salário</h3>${metrics([['13º bruto',brl(data.bruto)],['1ª parcela estimada',brl(data.primeira_parcela)],['2ª parcela bruta',brl(data.segunda_parcela_bruta)]])}<p>${esc(data.meses)} mês(es) contabilizado(s) sobre salário de ${esc(brl(data.salario))}.</p><p class="note">${esc(data.observacao)}</p>`;
if(data.type==='vacation')return `<h3>Estimativa de férias</h3>${metrics([['Remuneração das férias',brl(data.remuneracao)],['1/3 constitucional',brl(data.terco_constitucional)],['Total bruto',brl(data.total_bruto)]])}<p>${esc(data.dias)} dia(s) de férias sobre salário de ${esc(brl(data.salario))}.</p><p class="note">${esc(data.observacao)}</p>`;
if(data.type==='loan')return `<h3>Simulação ${esc(data.sistema)}</h3>${metrics([['Primeira parcela',brl(data.primeira_parcela)],['Total pago',brl(data.total)],['Juros totais',brl(data.juros_total)]])}${memory(data.schedule)}<p class="note">Simulação matemática: não inclui tarifas, seguros, impostos ou outros componentes do CET.</p>`;
if(data.type==='cash_vs_installments')return `<h3>Comparação concluída</h3>${metrics([['Total parcelado',brl(data.total_parcelado)],['Valor presente',brl(data.valor_presente_parcelado)],['Diferença nominal',brl(data.diferenca_nominal)],['Diferença no valor presente',brl(data.diferenca_valor_presente)]])}<p>Menor total nominal: <strong>${esc(data.melhor_nominal)}</strong>. Menor valor presente: <strong>${esc(data.melhor_valor_presente)}</strong>.</p><p class="note">O valor presente depende da taxa de comparação informada.</p>`;
throw new Error('Tipo de resposta desconhecido.');}
document.querySelectorAll('form[data-action]').forEach(form=>form.addEventListener('submit',async event=>{event.preventDefault();const button=form.querySelector('button[type="submit"]');const result=form.nextElementSibling;button.disabled=true;showResult(result,'<span class="loading">Calculando…</span>');const payload={action:form.dataset.action};form.querySelectorAll('input[name],select[name]').forEach(input=>{payload[input.name]=input.inputMode==='decimal'?number(input.value):input.value;});try{const response=await fetch('api.php',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF_TOKEN},body:JSON.stringify(payload)});const data=await response.json();if(!response.ok)throw new Error(data.error||'Não foi possível calcular.');showResult(result,render(data));sosRegistrarResposta('calculos');}catch(error){showResult(result,esc(error instanceof Error?error.message:'Não foi possível calcular.'),true);}finally{button.disabled=false;}}));
fetch('api.php?action=catalog',{credentials:'same-origin'}).then(r=>{if(!r.ok)throw new Error();return r.json();}).then(data=>{const series=Array.isArray(data.series)?data.series:[];document.getElementById('source-status').textContent=series.some(x=>x&&x.collected_at)?'última sincronização registrada':'consulta sob demanda';document.getElementById('source-list').innerHTML=series.filter(x=>x&&typeof x==='object').slice(0,6).map(x=>`<span class="source-chip">${esc(x.name)}${typeof x.last_period==='string'?' · '+esc(x.last_period.slice(0,7)):''}</span>`).join('');}).catch(()=>document.getElementById('source-status').textContent='fonte disponível sob consulta');
</script>
</body>
</html>
