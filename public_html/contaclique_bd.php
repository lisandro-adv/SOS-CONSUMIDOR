<?php

require dirname(__FILE__) . '/include/config_font.inc.php';
$bc_link = PROJECT_ROOT;
try {
    $id = AjustaInteiroGravar(get('id'));
    $tipo = AjustaInteiroGravar(get('tipo'));
    $area = AjustaInteiroGravar(get('area'));
#htacess antigo não tinha área
    if ($area == 0)
        $area = 1;


    if (($id > 0) && ($tipo > 0)) {

        $pdo = new PDOConfig();
        $banners = new Banners($pdo);
        $banners->setDados($id);
        $bc_link = $banners->getLink();

        $contador_banner = new ContadorBanner($pdo);

        $contador_banner->contar_clique($id, $tipo, $area);
    } else {
        $bc_link = PROJECT_ROOT;
    }
} catch (Exception $e) {
    #die($e->getMessage());
    Msg::add($e->getMessage());
}
header("location:" . $bc_link);
exit;

