<title><?php echo htmlspecialchars($titulo_site, ENT_QUOTES, 'UTF-8'); ?></title>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="<?php echo htmlspecialchars($description_site, ENT_QUOTES, 'UTF-8'); ?>">
<meta name="keywords" content="<?php echo htmlspecialchars($meta_keywords, ENT_QUOTES, 'UTF-8'); ?>">
<meta name="robots" content="index,follow">
<meta name="author" content="SOS Consumidor">

<!-- Open Graph / Redes Sociais -->
<meta property="og:type" content="website">
<meta property="og:site_name" content="SOS Consumidor">
<meta property="og:title" content="<?php echo htmlspecialchars($titulo_site, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($description_site, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:url" content="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
<?php if (!empty($og_image)): ?>
<meta property="og:image" content="<?php echo htmlspecialchars($og_image, ENT_QUOTES, 'UTF-8'); ?>">
<?php else: ?>
<meta property="og:image" content="<?php echo PROJECT_ROOT; ?>assets/img/sos-consumidor-og.jpg">
<?php endif; ?>

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo htmlspecialchars($titulo_site, ENT_QUOTES, 'UTF-8'); ?>">
<meta name="twitter:description" content="<?php echo htmlspecialchars($description_site, ENT_QUOTES, 'UTF-8'); ?>">

<!-- Canonical -->
<link rel="canonical" href="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?'); ?>">

<!-- Favicon -->
<link rel="shortcut icon" href="<?php echo PROJECT_ROOT; ?>images/galeria/favicon.ico">
<link rel="icon" type="image/x-icon" href="<?php echo PROJECT_ROOT; ?>images/galeria/favicon.ico">

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap">

<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'sos-red': '#a50006',
                    'sos-dark': '#1a1a1a',
                    'sos-gray': '#f5f5f5',
                },
                fontFamily: {
                    sans: ['Open Sans', 'sans-serif'],
                },
            }
        }
    }
</script>

<!-- CSS legado do site (mantido para compatibilidade durante transição) -->
<?php if (empty($sem_css_legado)): ?>
<link rel="stylesheet" type="text/css" href="<?php echo PROJECT_ROOT; ?>assets/css/app.css">
<?php endif; ?>
