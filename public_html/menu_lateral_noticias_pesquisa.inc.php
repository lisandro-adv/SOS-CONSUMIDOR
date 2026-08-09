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
            <a href="<?php echo NEWSLETTER; ?>"
               class="c-aside-links__btn c-aside-links__btn--newsletter">Assine
                a Newsletter</a></li>
        <li class="c-aside-links__item">
            <a href="<?php echo FORUM_ADVOGADO; ?>"
               class="c-aside-links__btn c-aside-links__btn--attorney">Área
                do Advogado</a></li>
        <li class="c-aside-links__item">
            <a href="https://www.sosconsumidor.com.br/ia-consumidor"
               class="c-aside-links__btn c-aside-links__btn--consumer">Área
                do Consumidor</a></li>
    </ul>

    <!-- Form pesquisa -->
    <br />
    <h4>Pesquisar Notícias</h4>
    <hr>
    <?php
    $dataPesquisa = (isset($_REQUEST['dataPesquisa'])) ? $_REQUEST['dataPesquisa'] : '';
    require dirname(__FILE__) . '/busca/calendarPT.php';
    Calendar($dataPesquisa);
    ?>
    <form action="<?php echo NOTICIAS_MAIS; ?>" method="post" class="form-horizontal" id="pesquisa">
        <input type="hidden" value="pesquisa" name="acao">

        <div class="form-group">
            <div class="col-md-12">
                <label>Título</label>
                <input type="text" class="form-control input-sm" placeholder="Título" name="txttitulo" id="txttitulo"
                       value="">
            </div>
        </div>

        <div class="form-group">

            <div class="col-md-6 date">
                <label>Data início</label>
                <input type="text" value="" id="txtdatade" name="txtdatade" placeholder=""
                       class="form-control input-sm datepicker">

            </div>
            <div class="col-md-6 date">
                <label>Data fim</label>
                <input type="text" value="" id="txtdataate" name="txtdataate" placeholder=""
                       class="form-control input-sm datepicker">

            </div>
        </div>

        <!-- Button -->
        <div class="form-group">

            <div class="col-md-12">
                <button class="o-btn o-btn--primary o-btn--sm" name="" id="" type="submit" data-original-title=""
                        title="">Pesquisar
                </button>
            </div>
        </div>

    </form>
    <!-- Form pesquisa -->


    <?php foreach ($banners_lateral_array as $banner_lateral_array) {
        $link = Banners::getUrl($banner_lateral_array['id'], $banner_lateral_array['tipo_id'], 1);
        ?>
        
    <?php } ?>
    
</aside>
