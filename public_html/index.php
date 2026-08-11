<?php
require dirname(__FILE__) . '/include/config_font.inc.php';
$db = new PDOConfig();

try {
    $home = 1;
    $parametros = new Parametros($db);
    $parametros->setDados(1);

    $data = date("Y-m-d");

    $noticias_array              = HistoricoHome::getNoticiasHome($db, $data);
    $perguntas_e_respostas_array = PerguntasERespostas::getAllLer($db);

    $titulo_site      = 'Direitos do Consumidor, Dívidas e SPC/SERASA | SOS Consumidor';
    $description_site = 'Orientação clara sobre direitos do consumidor, dívidas, crédito, SPC, SERASA e contratos para ajudar você a tomar decisões e resolver problemas de consumo.';
    $og_image         = '';

} catch (Exception $e) {
    Msg::add($e->getMessage());
    redirect(PROJECT_ROOT);
}

// Helpers inline
function _decode_entities($txt) {
    // Aplica html_entity_decode em loop até estabilizar,
    // cobrindo simples (&eacute;) e dupla codificação (&amp;eacute;)
    do {
        $prev = $txt;
        $txt  = html_entity_decode($txt, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    } while ($txt !== $prev);
    return $txt;
}
function _titulo($n) {
    $txt = _decode_entities($n['titulo'] ?: $n['chamada']);
    return htmlspecialchars($txt, ENT_QUOTES, 'UTF-8');
}
function _resumo($n, $len = 200) {
    $txt = $n['resumo'] ?: ResumoTexto(limpa_tags_total($n['texto']), $len);
    $txt = _decode_entities(strip_tags($txt));
    return htmlspecialchars($txt, ENT_QUOTES, 'UTF-8');
}
function _img($n, $root, $class = '', $lazy = true) {
    if (!$n['foto_salva']) return '';
    $load = $lazy ? 'loading="lazy"' : '';
    $alt  = htmlspecialchars($n['foto'] ?: $n['titulo'], ENT_QUOTES, 'UTF-8');
    return "<img src=\"{$root}images/{$n['foto_salva']}\" alt=\"{$alt}\" class=\"{$class}\" {$load}>";
}
function _webp_src($filename, $root) {
    if (!$filename) return '';
    $webp_name = preg_replace('/\.[^.]+$/', '.webp', $filename);
    if (!is_file(PROJECT_PATH . 'images/' . $webp_name)) return '';
    return $root . 'images/' . $webp_name;
}
$lcp_image_url = '';
foreach ((array)$noticias_array as $lcp_grupo) {
    foreach ((array)$lcp_grupo as $lcp_noticia) {
        if (!empty($lcp_noticia['foto_salva'])) {
            $lcp_image_url = _webp_src($lcp_noticia['foto_salva'], PROJECT_ROOT);
            if (!$lcp_image_url) {
                $lcp_image_url = PROJECT_ROOT . 'images/' . $lcp_noticia['foto_salva'];
            }
            break 2;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php include PROJECT_PATH . 'head.inc.php'; ?>
    <!-- Schema.org: Organização -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "SOS Consumidor",
        "description": "Serviço de Orientação ao Consumidor brasileiro. Orientação sobre direitos, dívidas, crédito e legislação.",
        "url": "https://www.sosconsumidor.com.br",
        "logo": "https://www.sosconsumidor.com.br/img/logo.png",
        "sameAs": []
    }
    </script>
    <!-- Schema.org: WebSite com SearchAction -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "SOS Consumidor",
        "url": "https://www.sosconsumidor.com.br",
        "potentialAction": {
            "@type": "SearchAction",
            "target": {
                "@type": "EntryPoint",
                "urlTemplate": "https://www.sosconsumidor.com.br/noticias-cse?q={search_term_string}"
            },
            "query-input": "required name=search_term_string"
        }
    }
    </script>
</head>
<body>

<?php include PROJECT_PATH . 'header.inc.php'; ?>

<div class="container-fluid">
    <div class="l-content-block">
        <div class="row">
            <div class="col-sm-9">
                <main class="c-main-content">
                    <h1 class="o-tit o-tit--22 sos-home-title">Direitos do consumidor, dívidas e orientação</h1>
                    <p class="sos-home-intro">Informação clara para entender seus direitos, organizar dívidas e tomar decisões melhores em contratos e relações de consumo.</p>

                    <?php include PROJECT_PATH . 'include/ferramentas-gratuitas.inc.php'; ?>

                    <?php /* ── DESTAQUE 1 ── */ ?>
                    <?php foreach ((array)($noticias_array[Noticias::DESTAQUE_1] ?? []) as $n):
                        $url   = Noticias::getURL($n['titulo'], $n['id']);
                    ?>
                    <div class="c-post">
                        <a href="<?php echo $url; ?>">
                            <?php if ($n['foto_salva']): ?>
                            <picture>
                                <?php $webp_src = _webp_src($n['foto_salva'], PROJECT_ROOT); ?>
                                <?php if ($webp_src): ?><source type="image/webp" srcset="<?php echo $webp_src; ?>"><?php endif; ?>
                                <img src="<?php echo PROJECT_ROOT; ?>images/<?php echo $n['foto_salva']; ?>"
                                     alt="<?php echo htmlspecialchars($n['foto'] ?: $n['titulo'], ENT_QUOTES, 'UTF-8'); ?>"
                                     width="840" height="560" fetchpriority="high" decoding="async"
                                     class="c-post__img">
                            </picture>
                            <?php endif; ?>
                            <h2 class="c-post__tit"><?php echo _titulo($n); ?></h2>
                            <p class="c-post__excerpt"><?php echo _resumo($n, 300); ?></p>
                        </a>
                    </div>
                    <?php endforeach; ?>

                    <?php /* ── DESTAQUE 2 ── */ ?>
                    <?php if (!empty($noticias_array[Noticias::DESTAQUE_2])): ?>
                    <div class="row">
                        <?php foreach ($noticias_array[Noticias::DESTAQUE_2] as $n):
                            $url = Noticias::getURL($n['titulo'], $n['id']);
                        ?>
                        <div class="col-md-6">
                            <div class="c-post">
                                <a href="<?php echo $url; ?>">
                                    <?php if ($n['foto_salva']): ?>
                                    <picture>
                                        <?php $webp_src = _webp_src($n['foto_salva'], PROJECT_ROOT); ?>
                                        <?php if ($webp_src): ?><source type="image/webp" srcset="<?php echo $webp_src; ?>"><?php endif; ?>
                                        <img src="<?php echo PROJECT_ROOT; ?>images/<?php echo $n['foto_salva']; ?>"
                                             alt="<?php echo htmlspecialchars($n['foto'] ?: $n['titulo'], ENT_QUOTES, 'UTF-8'); ?>"
                                             width="840" height="560"
                                             loading="lazy"
                                             decoding="async"
                                             class="c-post__img">
                                    </picture>
                                    <?php endif; ?>
                                    <h2 class="c-post__tit"><?php echo _titulo($n); ?></h2>
                                    <p class="c-post__excerpt"><?php echo _resumo($n, 150); ?></p>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php /* ── LISTA + PERGUNTAS ── */ ?>
                    <div class="row">
                        <div class="col-md-6">
                            <?php if (!empty($noticias_array[Noticias::DESTAQUE_N])): ?>
                            <h3 class="o-tit o-tit--22 o-tit--upper">Notícias</h3>
                            <ul class="o-links-list o-links-list--news">
                                <?php
                                $data_loop = '';
                                foreach ($noticias_array[Noticias::DESTAQUE_N] as $noticia_data => $noticias_grupo):
                                    foreach ($noticias_grupo as $n):
                                        $n['titulo'] = ajustaStrMostrar($n['titulo']);
                                        $url = Noticias::getURL($n['titulo'], $n['id']);
                                ?>
                                <li class="o-links-list__item">
                                    <?php if ($data_loop !== $noticia_data): ?>
                                    <span><?php echo ajustaDataMostrar($noticia_data); ?></span>
                                    <?php $data_loop = $noticia_data; endif; ?>
                                    <a href="<?php echo $url; ?>"><b><?php echo _titulo($n); ?></b></a>
                                </li>
                                <?php endforeach; endforeach; ?>
                            </ul>
                            <a href="<?php echo NOTICIAS_MAIS; ?>" class="o-btn o-btn--primary o-btn--sm">Ver mais notícias</a>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <?php if ($perguntas_e_respostas_array):
                                include PROJECT_PATH . 'perguntas_e_respostas_lateral.inc.php';
                            endif; ?>
                        </div>
                    </div>

                </main>
            </div>

            <div class="col-sm-3">
                <?php include PROJECT_PATH . 'menu_lateral.inc.php'; ?>
            </div>
        </div>
    </div>
</div>

<?php include PROJECT_PATH . 'footer.inc.php'; ?>
</body>
</html>
