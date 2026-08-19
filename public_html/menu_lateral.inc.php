<?php
$banners_lateral_array = array();
try {
    $banners_lateral_array = Banners::getAll($db, 2);
} catch (Exception $e) {
    die($e->getMessage());
}
?>

<aside class="c-side-bar">
    <?php
    $ajudinha_sidebar = true;
    include PROJECT_PATH . 'ajudinha-widget.inc.php';
    unset($ajudinha_sidebar);
    ?>
    <ul class="c-aside-links">
        <li class="c-aside-links__item">
            <a href="<?php echo NEWSLETTER; ?>" class="c-aside-links__btn c-aside-links__btn--newsletter">Assine a Newsletter</a></li>
        <!--li class="c-aside-links__item">
            <a target="_blank" href="<?php echo FORUM_ADVOGADO; ?>" class="c-aside-links__btn c-aside-links__btn--attorney">Área do Advogado</a></li -->
        <li class="c-aside-links__item">
            <a target="_blank" href="https://www.sosconsumidor.com.br/ia-consumidor" class="c-aside-links__btn c-aside-links__btn--consumer">Área do Consumidor</a></li>
        <li class="c-aside-links__item">
            <a href="https://www.sosconsumidor.com.br/perguntas-e-respostas-detalhes-como-consultar-spc-serasa-ou-scpc-5" class="c-aside-links__btn c-aside-links__btn--consumer">Como consultar SPC, SERASA ou SCPC?</a></li>
    </ul>

    <?php foreach ($banners_lateral_array as $banner_lateral_array) {
        $link = Banners::getUrl($banner_lateral_array['id'], $banner_lateral_array['tipo_id'], 1);
        ?>
        <div class="o-advertising">
            <a target="_blank" href="<?php echo $link; ?>">
                <img src="<?php echo PROJECT_ROOT ?>images/<?php echo $banner_lateral_array['imagem_salva']; ?>"
                     alt="Publicidade">
            </a>
        </div>
    <?php } ?>
</aside>
