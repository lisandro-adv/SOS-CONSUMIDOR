<?php
require dirname(__FILE__) . '/include/config_font.inc.php';
require_once PROJECT_PATH . 'admsite/classes/noticias_especialidades/noticias_especialidades.inc.php';
$db = new PDOConfig();
try {

    $parametros = new Parametros($db);
    $parametros->setDados(1);

    $data = date("Y-m-d");

    $area_id = AjustaInteiroGravar(request('area_id'));

    $grupo_id = AjustaInteiroGravar(request('grupo_id'));

    #print_r2($_REQUEST);

    $areas_obj = new PerguntasERespostasAreas($db);

    if ($area_id) {
        $areas_obj->setDados($area_id);
    }


    $titulo_site = 'Perguntas e Respostas | ' . TITULO_SITE_DEFAULT;

    $meta_keywords = $parametros->getMetaKeywords();
    $description_site = $parametros->getMetaDescription();
} catch (Exception $e) {
    #die($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <?php include PROJECT_PATH . 'head.inc.php'; ?>
</head>
<body>
<?php include PROJECT_PATH . 'header.inc.php'; ?>
<div class="c-breadcrumb">
    <div class="l-content-block">
        <div class="c-breadcrumb__list">
            <div class="c-breadcrumb__item"><a href="<?php echo PROJECT_ROOT; ?>">Página Inicial</a></div>
            <div class="c-breadcrumb__item active">Perguntas e Respostas</div>
        </div>
    </div>
</div>
<div class="container-fluid">
    <div class="l-content-block">
        <div class="row">
            <div class="col-sm-9">
                <main class="c-main-content">
                    <?php foreach (PerguntasERespostasAreas::getGrupoAreasAllMenu($db, $grupo_id) as $grupo_areas_key => $grupo_areas_val) { ?>
                        <h1 class="o-tit o-tit--22"><?php echo $grupo_areas_val['descricao']; ?></h1>
                        <div id="accordion" role="tablist" aria-multiselectable="true"
                             class="panel-group c-panel-group">
                            <?php foreach ($grupo_areas_val['aresas'] as $aresas_key => $aresas_val) { ?>
                                <div class="panel panel-default">
                                    <div id="headingThree" role="tab" class="panel-heading">
                                        <h4 class="panel-title">
                                            <a class="<?php echo(($aresas_key == $area_id) ? '' : 'collapsed'); ?>"
                                               role="button" data-toggle="collapse" data-parent="#accordion"
                                               href="#collapse<?php echo $aresas_key; ?>" aria-expanded="true"
                                               aria-controls="collapse<?php echo $aresas_key; ?>"><?php echo $aresas_val; ?></a>
                                        </h4>
                                    </div>
                                    <div id="collapse<?php echo $aresas_key; ?>" role="tabpanel"
                                         aria-labelledby="heading<?php echo $aresas_key; ?>"
                                         class="panel-collapse collapse<?php echo(($aresas_key == $area_id) ? ' in' : ''); ?>">
                                        <div class="panel-body">
                                            <?php
                                            #print_r2(PerguntasERespostas::getAll($db, $aresas_key));
                                            foreach (PerguntasERespostas::getAll($db, $aresas_key) as $perguntas_e_respostas_val) {
                                                $link = PerguntasERespostas::getURL($perguntas_e_respostas_val['pergunta'], $perguntas_e_respostas_val['id']);


                                                $link_target = "";
                                                if ($perguntas_e_respostas_val["link"]) {
                                                    $link = $perguntas_e_respostas_val["link"];
                                                    if ($perguntas_e_respostas_val["link_target"]) {
                                                        $link_target = 'target = "_blank"';
                                                    }
                                                }

                                                ?>
                                                <a href="<?php echo $link; ?>" <?php echo $link_target; ?> >
                                                    <?php echo $perguntas_e_respostas_val['pergunta']; ?> <br><br>
                                                </a>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>
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
