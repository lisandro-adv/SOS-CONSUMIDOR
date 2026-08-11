<?php
$ajudinha_article_root = defined('PROJECT_ROOT') ? rtrim((string) PROJECT_ROOT, '/') . '/' : '/';
$ajudinha_article_asset = $ajudinha_article_root . 'assets/img/ajudinha/ajudinha-v5-440-20260809.webp';
$ajudinha_article_url = $ajudinha_article_root . 'ia-consumidor/';
$ajudinha_article_tool = 'ia';
$ajudinha_article_title = 'Precisa de uma ajudinha?';
$ajudinha_article_copy = 'Tire sua dúvida gratuitamente no SOS Responde.';

$ajudinha_article_context = '';
if (isset($noticia_obj) && is_object($noticia_obj) && method_exists($noticia_obj, 'getTitulo')) {
    $ajudinha_article_context = (string) $noticia_obj->getTitulo();
} elseif (isset($perguntas_e_respostas_obj) && is_object($perguntas_e_respostas_obj) && method_exists($perguntas_e_respostas_obj, 'getPergunta')) {
    $ajudinha_article_context = (string) $perguntas_e_respostas_obj->getPergunta();
}
$ajudinha_article_context = html_entity_decode($ajudinha_article_context, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$ajudinha_article_context = function_exists('mb_strtolower')
    ? mb_strtolower($ajudinha_article_context, 'UTF-8')
    : strtolower($ajudinha_article_context);

if (preg_match('/juros|financiamento|empr[eé]stimo|cr[eé]dito|cart[aã]o|banco|taxa|parcela/u', $ajudinha_article_context)) {
    $ajudinha_article_url = $ajudinha_article_root . 'juros/';
    $ajudinha_article_tool = 'juros';
    $ajudinha_article_title = 'Quer conferir os juros cobrados?';
    $ajudinha_article_copy = 'Compare gratuitamente a taxa do contrato com a média do Banco Central.';
}

$ajudinha_calculation_slug = '';
if (preg_match('/boleto|atraso|vencimento/u', $ajudinha_article_context)) {
    $ajudinha_calculation_slug = 'boleto-vencido';
} elseif (preg_match('/aluguel|loca[cç][aã]o/u', $ajudinha_article_context)) {
    $ajudinha_calculation_slug = 'reajuste-aluguel';
} elseif (preg_match('/d[eé]cimo|13[º°o]?|sal[aá]rio/u', $ajudinha_article_context)) {
    $ajudinha_calculation_slug = 'decimo-terceiro';
} elseif (preg_match('/f[eé]rias/u', $ajudinha_article_context)) {
    $ajudinha_calculation_slug = 'ferias-um-terco';
} elseif (preg_match('/d[ií]vida|cobran[cç]a|corre[cç][aã]o|atualiza[cç][aã]o/u', $ajudinha_article_context)) {
    $ajudinha_calculation_slug = 'atualizar-divida';
}
if ($ajudinha_calculation_slug !== '') {
    $ajudinha_article_url = $ajudinha_article_root . 'calculos/' . $ajudinha_calculation_slug . '/';
    $ajudinha_article_tool = 'calculos';
    $ajudinha_article_title = 'Quer conferir esse valor?';
    $ajudinha_article_copy = 'Use a calculadora gratuita e veja a memória do cálculo.';
}
?>
<style>
    .sos-ajudinha-article-banner {
        position: relative;
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 18px 0 22px;
        padding: 10px 46px 10px 12px;
        border-radius: 14px;
        background: linear-gradient(105deg, #07557f, #07876a);
        box-shadow: 0 5px 14px rgba(7, 55, 82, .18);
    }
    .sos-ajudinha-article-banner[hidden] { display: none !important; }
    .sos-ajudinha-article-banner a {
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
        color: #fff;
        text-decoration: none;
    }
    .sos-ajudinha-article-banner img {
        width: 54px;
        height: 54px;
        flex: 0 0 54px;
        object-fit: contain;
        object-position: center;
        border-radius: 50%;
        background: rgba(255,255,255,.92);
    }
    .sos-ajudinha-article-banner strong,
    .sos-ajudinha-article-banner small {
        display: block;
    }
    .sos-ajudinha-article-banner strong { font-size: 17px; line-height: 1.2; }
    .sos-ajudinha-article-banner small { margin-top: 3px; font-size: 14px; line-height: 1.25; }
    .sos-ajudinha-article-banner button {
        position: absolute;
        top: 7px;
        right: 9px;
        width: 28px;
        height: 28px;
        padding: 0;
        border: 0;
        border-radius: 50%;
        background: rgba(255,255,255,.22);
        color: #fff;
        font-size: 22px;
        line-height: 25px;
        cursor: pointer;
    }
    .sos-ajudinha-article-banner button:hover,
    .sos-ajudinha-article-banner button:focus { background: rgba(255,255,255,.38); }
    @media (max-width: 767px) {
        .sos-ajudinha-article-banner { margin: 14px 0 18px; padding: 9px 42px 9px 10px; }
        .sos-ajudinha-article-banner img { width: 46px; height: 46px; flex-basis: 46px; }
        .sos-ajudinha-article-banner strong { font-size: 15px; }
        .sos-ajudinha-article-banner small { font-size: 13px; }
    }
</style>
<aside class="sos-ajudinha-article-banner" data-sos-ajudinha-article-banner>
    <a href="<?php echo htmlspecialchars($ajudinha_article_url, ENT_QUOTES, 'UTF-8'); ?>" data-sos-tool="<?php echo htmlspecialchars($ajudinha_article_tool, ENT_QUOTES, 'UTF-8'); ?>" data-sos-source="article_banner" data-sos-impression>
        <img src="<?php echo htmlspecialchars($ajudinha_article_asset, ENT_QUOTES, 'UTF-8'); ?>" alt="Ajudinha" width="440" height="440" loading="lazy" decoding="async">
        <span>
            <strong><?php echo htmlspecialchars($ajudinha_article_title, ENT_QUOTES, 'UTF-8'); ?></strong>
            <small><?php echo htmlspecialchars($ajudinha_article_copy, ENT_QUOTES, 'UTF-8'); ?></small>
        </span>
    </a>
    <button type="button" aria-label="Fechar Ajudinha" data-sos-ajudinha-close>&times;</button>
</aside>
<script>
(function () {
    var banner = document.querySelector('[data-sos-ajudinha-article-banner]');
    if (!banner) return;
    var close = banner.querySelector('[data-sos-ajudinha-close]');
    if (close) close.addEventListener('click', function () {
        banner.hidden = true;
    });
}());
</script>
