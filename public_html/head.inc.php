<?php
/* Metadados SEO comuns. O host e a query string nunca devem definir o
   canonical: a versão pública é sempre HTTPS + www, sem parâmetros. */
$seo_title = trim((string) ($titulo_site ?? TITULO_SITE_DEFAULT));
$seo_description = trim((string) ($description_site ?? 'Direitos do consumidor, dívidas, crédito e orientação clara para resolver problemas de consumo.'));
$seo_description = html_entity_decode(strip_tags($seo_description), ENT_QUOTES | ENT_HTML5, 'UTF-8');
$seo_description = preg_replace('/\s+/u', ' ', $seo_description);
$seo_description = trim($seo_description);
if ($seo_description === '') {
    $seo_description = 'Direitos do consumidor, dívidas, crédito e orientação clara para resolver problemas de consumo.';
}
$seo_path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
$seo_request_canonical = 'https://www.sosconsumidor.com.br' . (strpos($seo_path, '/') === 0 ? $seo_path : '/' . $seo_path);
$seo_canonical = !empty($canonical_url) ? (string) $canonical_url : $seo_request_canonical;
$seo_robots = !empty($seo_noindex) ? 'noindex,follow' : 'index,follow';
$seo_og_type = !empty($og_type) ? $og_type : 'website';
$seo_og_url = !empty($og_url) ? $og_url : $seo_canonical;
$seo_og_image = !empty($og_image) ? $og_image : 'https://www.sosconsumidor.com.br/img/logo_twitter.jpg';
?>
<title><?php echo htmlspecialchars($seo_title, ENT_QUOTES, 'UTF-8'); ?></title>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="<?php echo htmlspecialchars($seo_description, ENT_QUOTES, 'UTF-8'); ?>">
<meta name="robots" content="<?php echo $seo_robots; ?>">
<meta name="author" content="SOS Consumidor">

<!-- Open Graph / Redes Sociais -->
<meta property="og:type" content="<?php echo htmlspecialchars($seo_og_type, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:site_name" content="SOS Consumidor">
<meta property="og:title" content="<?php echo htmlspecialchars($seo_title, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($seo_description, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:url" content="<?php echo htmlspecialchars($seo_og_url, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:image" content="<?php echo htmlspecialchars($seo_og_image, ENT_QUOTES, 'UTF-8'); ?>">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo htmlspecialchars($seo_title, ENT_QUOTES, 'UTF-8'); ?>">
<meta name="twitter:description" content="<?php echo htmlspecialchars($seo_description, ENT_QUOTES, 'UTF-8'); ?>">
<meta name="twitter:image" content="<?php echo htmlspecialchars($seo_og_image, ENT_QUOTES, 'UTF-8'); ?>">

<!-- Canonical -->
<link rel="canonical" href="<?php echo htmlspecialchars($seo_canonical, ENT_QUOTES, 'UTF-8'); ?>">

<!-- Favicon -->
<link rel="shortcut icon" href="<?php echo PROJECT_ROOT; ?>images/galeria/favicon.ico">
<link rel="icon" type="image/x-icon" href="<?php echo PROJECT_ROOT; ?>images/galeria/favicon.ico">
<?php if (!empty($lcp_image_url)): ?>
<link rel="preload" as="image" href="<?php echo htmlspecialchars($lcp_image_url, ENT_QUOTES, 'UTF-8'); ?>" fetchpriority="high">
<?php endif; ?>

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap"></noscript>

<!--
    Não carregamos Tailwind globalmente nesta camada. O cabeçalho atual é
    legado e o preflight do CDN é injetado de forma assíncrona, provocando um
    reset tardio (body, títulos, imagens e box-sizing) que fazia o cabeçalho
    aparecer e desaparecer após o primeiro paint. Componentes novos devem
    carregar seus estilos de forma isolada.
-->

<!-- CSS legado do site (mantido para compatibilidade durante transição) -->
<?php if (empty($sem_css_legado)): ?>
<link rel="preload" as="style" href="<?php echo PROJECT_ROOT; ?>assets/css/app.css?v=20260810-perf2" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" type="text/css" href="<?php echo PROJECT_ROOT; ?>assets/css/app.css?v=20260810-perf2"></noscript>
<?php endif; ?>
