<?php
require dirname(__FILE__) . '/include/config_font.inc.php';
require_once PROJECT_PATH . 'admsite/classes/noticias_especialidades/noticias_especialidades.inc.php';
$db = new PDOConfig();
try {

    $parametros = new Parametros($db);
    $parametros->setDados(1);

    $data = date("Y-m-d");

    $paginacao = new PDOPaginacaoFrontend($db);

    $paginacao->setLimite($parametros->getPaginacao());
    #$paginacao->setSql(Noticias::getSqlMais());

//    [acao] => pesquisa
//    [txttitulo] =>
//    [txtdatade] =>
//    [txtdataate] =>
    $titulo = ajustaStringBD(post('txttitulo'));
    $txtdatade = ajustaDataBD(post('txtdatade'));
    $txtdataate = ajustaDataBD(post('txtdataate'));

    $especialidade_id = 0;
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'noticias-1,1,0,Noticias.html')) {
        $especialidade_id = NoticiasEspecialidades::DANO_MORAL;
    }

    if (get('dataPesquisa') != '') {
        $txtdatade = ajustaDataBD(ajustaDataMostrar(get('dataPesquisa')));
        $txtdataate = ajustaDataBD(ajustaDataMostrar(get('dataPesquisa')));
    }

    //limpa_sql=1&dataPesquisa=2016-09-05&&month=09&year=2016


    if (get('page') == '' || get('limpa_sql') != '') {
        $_SESSION[Noticias::SESSION_PESQUISA_FRONT] = '';
    }

    if (!isset($_SESSION[Noticias::SESSION_PESQUISA_FRONT]) || $_SESSION[Noticias::SESSION_PESQUISA_FRONT] == '') {
        $_SESSION[Noticias::SESSION_PESQUISA_FRONT] = Noticias::getSqlMaisPesquisa($titulo, $txtdatade, $txtdataate, 0, $especialidade_id);

    }

    $paginacao->setSql($_SESSION[Noticias::SESSION_PESQUISA_FRONT]);

    $noticias_stm = $db->query($paginacao->sql(), PDO::FETCH_ASSOC);

    $titulo_site = 'Notícias | ' . TITULO_SITE_DEFAULT;

    $meta_keywords = $parametros->getMetaKeywords();
    $description_site = $parametros->getMetaDescription();
} catch (Exception $e) {
    #die($e->getMessage());
    Msg::add($e->getMessage());
    redirect(PROJECT_ROOT);
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
            <?php if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'noticias-1,1,0,Noticias.html')) { ?>
                <!-- se a url desta pagina contiver "noticias-1,1,0,Noticias.html" acessa aqui -->
                <div class="c-breadcrumb__item"><a href="<?php
                    echo((isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : PERGUNTAS_E_RESPOSTAS_MAIS); ?>">Perguntas
                        e Respostas</a></div>
                <div class="c-breadcrumb__item active">Dano Moral - Dano Moral</div>
            <?php } else { ?>
                <div class="c-breadcrumb__item active">Notícias</div>
            <?php } ?>
        </div>
    </div>
</div>
<div class="container-fluid">
    <div class="l-content-block">
        <div class="row">
            <div class="col-sm-9">
                <main class="c-main-content">
                    <h1 class="o-tit o-tit--22">Notícias</h1>

                    <?php
                    if (!$noticias_stm->rowCount()) {
                        echo '<p>Nenhum resultado encontrado</p>';
                    }
                    $data_loop = "";
                    while ($noticia_array = $noticias_stm->fetch(PDO::FETCH_ASSOC)) {
                        #print_r2($noticia_array);
                        $url = Noticias::getURL($noticia_array['titulo'], $noticia_array['id']);
                        if ($noticia_array['resumo'])
                            $resumo = limpa_tags_total($noticia_array['resumo']);
                        else
                            $resumo = isset($noticia_array['texto']) ? ResumoTexto(limpa_tags_total($noticia_array['texto']), 300) . "..." : '';

                        $colunas = 12;

                        ?>
                        <div class="c-post">
                            <div class="row">
                                <?php if ($noticia_array['foto_salva']) {
                                    $colunas = 7;
                                    ?>
                                    <div class="col-md-5">
                                        <a href="<?php echo $url; ?>">
                                            <img
                                                    src="<?php echo PROJECT_ROOT ?>images/<?php echo $noticia_array['foto_salva']; ?>"
                                                    alt="<?php echo $noticia_array['foto']; ?>" class="c-post__img">
                                        </a>
                                    </div>
                                <?php } ?>
                                <div class="col-md-<?php echo $colunas; ?>"><a href="<?php echo $url; ?>">
                                        <?php if ($data_loop != $noticia_array['data_publicacao']) {
                                            $data_loop = $noticia_array['data_publicacao'];
                                            ?>
                                            <span
                                                    class="c-post__date"><?php echo ajustaDataMostrar($noticia_array['data_publicacao']); ?></span>
                                        <?php } ?>

                                        <h2 class="c-post__tit"><?php echo $noticia_array['titulo']; ?></h2>
                                        <p class="c-post__excerpt"><?php echo $resumo; ?></p></a></div>
                            </div>
                        </div>
                    <?php } ?>


                    <ul class="o-links-list o-links-list--news">
                    </ul>
                    <nav class="text-center">
                        <?php $paginacao->imprimeBarraNavegacao(); ?>
                    </nav>
                </main>
            </div>
            <div class="col-sm-3">
                <?php include PROJECT_PATH . 'menu_lateral_noticias_pesquisa.inc.php'; ?>
            </div>
        </div>
    </div>
</div>
<?php include PROJECT_PATH . 'footer.inc.php'; ?>
</body>
</html>
