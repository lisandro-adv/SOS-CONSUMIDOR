<?php
require dirname(__FILE__) . '/include/config_font.inc.php';
$db = new PDOConfig();
try {

    $parametros = new Parametros($db);
    $parametros->setDados(1);

    $data = date("Y-m-d");

    $paginacao = new PDOPaginacaoFrontend($db);

    $perguntas_e_respostas_id = AjustaInteiroGravar(request('perguntas_e_respostas_id'));

    $banners_central_array = Banners::getAll($db, 1);

    $perguntas_e_respostas_obj = new PerguntasERespostas($db);
    if ($perguntas_e_respostas_id > 0) {
        $perguntas_e_respostas_obj->setDados($perguntas_e_respostas_id);
    }
    if ($perguntas_e_respostas_obj->getId() < 1) {
        throw new Exception('Registro inválido.');
    }
    if($perguntas_e_respostas_obj->getLink()){
        redirect($perguntas_e_respostas_obj->getLink());
    }

    $areas_obj = new PerguntasERespostasAreas($db);
    $grupos_obj = new PerguntasERespostasGrupo($db);

    $url_voltar = PERGUNTAS_E_RESPOSTAS_MAIS;
    if ($perguntas_e_respostas_obj->getAreaId() > 0) {
        $areas_obj->setDados($perguntas_e_respostas_obj->getAreaId());
        if ($areas_obj->getGrupoId() > 0) {
            $grupos_obj->setDados($areas_obj->getGrupoId());
        }
        $url_voltar = PerguntasERespostasAreas::getUrlAreas($areas_obj->getGrupoId(), $perguntas_e_respostas_obj->getAreaId());
    }


    $perguntas_e_respostas_array = PerguntasERespostas::getAllLer($db, $perguntas_e_respostas_id);


    $perguntas_e_respostas_obj->setAcessos($perguntas_e_respostas_obj->getAcessos() + 1);
    $perguntas_e_respostas_obj->saveAcessos($perguntas_e_respostas_obj->getId(), $perguntas_e_respostas_obj->getAcessos());

    $url_noticia = PerguntasERespostas::getURL($perguntas_e_respostas_obj->getPergunta(), $perguntas_e_respostas_obj->getId());
    $url_encurtada = $perguntas_e_respostas_obj->getLinkBitly();

    $foto_salva = PROJECT_ROOT.'img/logo_twitter.jpg';
    if($perguntas_e_respostas_obj->getFotoSalva()){
        $foto_salva = PROJECT_ROOT.'images/'.$perguntas_e_respostas_obj->getFotoSalva();
    }


    $titulo_site = $perguntas_e_respostas_obj->getPergunta() . ' | Perguntas e Respostas | ' . TITULO_SITE_DEFAULT;


    $perguntas_e_respostas_area_array = array();
    if ($perguntas_e_respostas_obj->getAreaId() > 0) {
        $perguntas_e_respostas_area_array = PerguntasERespostas::getAllArea($db, $perguntas_e_respostas_id, $perguntas_e_respostas_obj->getAreaId());
    }

    $PerguntasERespostas_relacionadas_palavras_chave_array = array();
    if ($perguntas_e_respostas_obj->getPalavrasChave() != '') {
        $PerguntasERespostas_relacionadas_palavras_chave_array = $db->query(PerguntasERespostas::getSqlPesquisaPalavrasChave($perguntas_e_respostas_obj->getId(),$perguntas_e_respostas_obj->getPalavrasChave(),10), PDO::FETCH_ASSOC)->fetchAll();
    }

    $titulo = $perguntas_e_respostas_obj->getPergunta();
    $texto = $perguntas_e_respostas_obj->getResposta();

    $mostrarMenuRedesSociais = 'id="MostrarFonte" style="display:show"';
    $mostrarVideo = 'id="MostrarVideo" style="display:show"';
    $mostrarComentarios= 'id="MostrarComentarios" style="display:show"';

    //coloca o texto em 2 blocos para ocultar uma parte e colocar o botão continuar lendo.
    if(count(explode('[CONTINUAR_LENDO]',$texto)) > 1){
        $texto_2blocos = explode('[CONTINUAR_LENDO]',$texto);
        $mostrarMenuRedesSociais =  'id="MostrarRedesSociais" style="display:none"';
        $mostrarVideo =  'id="MostrarRedesVideo" style="display:none"';
        $mostrarComentarios =  'id="MostrarComentarios" style="display:none"';

    }else{
        $texto_2blocos = "";

    }


    $meta_keywords = '';
    $description_site = ResumoTexto(limpa_tags_total($texto), 155);
    $og_type = 'article';
    $og_url = $url_noticia;
    $og_description = $description_site;
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
    <meta property="fb:app_id" content="500960907080311"/>
    <script type="application/ld+json">
    <?php echo json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => limpa_tags_total($titulo),
        'description' => $description_site,
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url_noticia],
        'image' => [$foto_salva],
        'author' => ['@type' => 'Organization', 'name' => 'SOS Consumidor'],
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'SOS Consumidor',
            'logo' => ['@type' => 'ImageObject', 'url' => 'https://www.sosconsumidor.com.br/img/logo.png']
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
    </script>

    <style>
        .desize-texto-conteudo p { margin-bottom: 12pt; }
    </style>
</head>
<body>
<?php include PROJECT_PATH . 'header.inc.php'; ?>
<div class="c-breadcrumb">
    <div class="l-content-block">
        <div class="c-breadcrumb__list">
            <div class="c-breadcrumb__item"><a href="<?php echo PROJECT_ROOT; ?>">Página Inicial</a></div>
            <div class="c-breadcrumb__item"><a href="<?php
                echo ((isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER']: PERGUNTAS_E_RESPOSTAS_MAIS); ?>">Perguntas e Respostas</a></div>
            <div class="c-breadcrumb__item active"><?php echo $perguntas_e_respostas_obj->getPergunta(); ?></div>
        </div>
    </div>
</div>
<div class="container-fluid">
    <div class="l-content-block">
        <div class="row">
            <div class="col-sm-9">
                <main class="c-main-content">
                    <div class="row">
                        <div class="col-sm-8"><a href="<?php echo $url_voltar; ?>"
                                                 class="o-link-secondary">&lt;
                                Voltar
                                para <?php echo $grupos_obj->getGrupoDesc() . ' - ' . $areas_obj->getAreaDesc(); ?></a>
                            <?php include PROJECT_PATH . 'ajudinha-artigo-banner.inc.php'; ?>
                            <h1 class="o-tit o-tit--22"><?php echo $perguntas_e_respostas_obj->getPergunta(); ?></h1>
                            <div class="o-img-wrapper">
                                <?php if ($perguntas_e_respostas_obj->getFotoSalva()) { ?>
                                    <img
                                        src="<?php echo PROJECT_ROOT ?>images/<?php echo $perguntas_e_respostas_obj->getFotoSalva(); ?>"
                                        alt="<?php echo $perguntas_e_respostas_obj->getFoto(); ?>">
                                    <span
                                        class="o-img-wrapper__legend"><?php echo $perguntas_e_respostas_obj->getFotoLegenda(); ?><?php echo $perguntas_e_respostas_obj->getFotoAutor(); ?></span>
                                <?php } ?>
                            </div>
                            <div class="desize-texto-conteudo">

                                <?php

                                # escreve o texto em 2 blocos com o leia mais
                                if($texto_2blocos){
                                    echo $texto_2blocos[0];
                                    echo '<button type="button" id="MostrarBloco2" class="o-btn o-btn--sm o-btn--secondary" style="width:100%; margin-bottom: 1em" >CONTINUAR LENDO</button>';
                                    echo '<div id="Mensagem" style="display:none">'.$texto_2blocos[1].'</div>';
                                }else{
                                    #echo $noticia_obj->getTexto();
                                    echo $texto;
#                                    echo(($fonte_obj->getNome()) ? '<b>Fonte: ' . $fonte_obj->getNome() . (($noticia_obj->getDataFonte()) ? ' - ' . ajustaDataMostrar($noticia_obj->getDataFonte()) : '') . '</b>' : '');
                                }
                                ?>
                            </div>

                            <p></p>
                            <div class="c-well well well-sm" <?php echo $mostrarMenuRedesSociais;?>>
                                <strong><?php echo $perguntas_e_respostas_obj->getAcessos(); ?></strong>
                                pessoas já leram este conteúdo&nbsp;&nbsp;
                                <div class="pull-right">
                                    <div class="addthis_sharing_toolbox"></div>
                                </div>
                            </div>
                            <p>
                                <?php if ($perguntas_e_respostas_obj->getLink()) { ?>
                                    <a <?php echo(($perguntas_e_respostas_obj->getLinkTarget()) ? 'target="_blank"' : ''); ?>
                                        href="<?php echo $perguntas_e_respostas_obj->getLink(); ?>"
                                        class="o-link-secondary">Leia mais
                                        sobre este assunto</a>
                                <?php } ?>
                            </p>
<!--
                            <div class="fb-comments" data-href="<?php echo PerguntasERespostas::getURL($perguntas_e_respostas_obj->getPergunta(), $perguntas_e_respostas_obj->getId()); ?>" data-width="100%" data-numposts="5"></div>
-->
                            <!-- Publicidade-->
                            
                            <?php foreach ($banners_central_array as $banner_central_array) {
                                $link = Banners::getUrl($banner_central_array['id'], $banner_central_array['tipo_id'], 1);
                                ?>
                                
                            <?php } ?>


                            <div class="row">
                            <div class="col-sm-9">

                                <?php if (($perguntas_e_respostas_area_array) || ($PerguntasERespostas_relacionadas_palavras_chave_array)) { ?>
                                    <h3 class="o-tit o-tit--22 o-tit--upper">Perguntas e Respostas relacionadas</h3>
                                    <ul class="o-links-list o-links-list--news">
                                        <?php foreach ($perguntas_e_respostas_area_array as $perguntas_e_resposta_array) {
                                            $area_obj = new PerguntasERespostasAreas($db);
                                            $area_obj->setDados($perguntas_e_resposta_array['area_id']);

                                            $grupo_obj = new PerguntasERespostasGrupo($db);
                                            $grupo_obj->setDados($area_obj->getGrupoId());

                                            $link = PerguntasERespostas::getURL($perguntas_e_resposta_array['pergunta'], $perguntas_e_resposta_array['id']);
                                            #print_r2($perguntas_e_respostas_val);
                                            #link externo
                                            $link_target = "";
                                            if($perguntas_e_resposta_array["link"]){
                                                $link = $perguntas_e_resposta_array["link"];
                                                if($perguntas_e_resposta_array["link_target"])
                                                    $link_target = 'target = "_blank"';
                                            }

                                            ?>
                                            <li class="o-links-list__item">
                                                <a href="<?php echo $link; ?>" <?php echo $link_target; ?> ><?php echo $perguntas_e_resposta_array['pergunta']; ?></a></li>
                                            </li>
                                        <?php } ?>
                                        <?php foreach ($PerguntasERespostas_relacionadas_palavras_chave_array as $perguntas_e_resposta_array) {
                                            $area_obj = new PerguntasERespostasAreas($db);
                                            $area_obj->setDados($perguntas_e_resposta_array['area_id']);

                                            $grupo_obj = new PerguntasERespostasGrupo($db);
                                            $grupo_obj->setDados($area_obj->getGrupoId());

                                            $link = PerguntasERespostas::getURL($perguntas_e_resposta_array['pergunta'], $perguntas_e_resposta_array['id']);
                                            #print_r2($perguntas_e_respostas_val);
                                            #link externo
                                            $link_target = "";
                                            if($perguntas_e_resposta_array["link"]){
                                                $link = $perguntas_e_resposta_array["link"];
                                                if($perguntas_e_resposta_array["link_target"])
                                                    $link_target = 'target = "_blank"';
                                            }
                                            ?>
                                            <li class="o-links-list__item">
                                                <a href="<?php echo $link; ?> <?php echo $link_target; ?>"><?php echo $perguntas_e_resposta_array['pergunta']; ?></a></li>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                <?php } ?>

                            </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <?php
                            #################################################################

                            include PROJECT_PATH . 'noticias_lateral.inc.php';

                            #################################################################
                            if ($perguntas_e_respostas_array) {
                                include PROJECT_PATH . 'perguntas_e_respostas_lateral.inc.php';
                            }
                            #################################################################
                            ?>
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
<!-- AddThis descontinuado (2023); bloco removido em 19/08/2026 para nao expor a chave bit.ly no HTML. -->

<script type="text/javascript">
    $(document).ready(function() {
        $("#MostrarBloco2").click(MostrarBloco2);
    });

    function MostrarBloco2(){
        $("#Mensagem").show();
        $("#MostrarRedesSociais").show();
        $("#MostrarRedesVideo").show();
        $("#MostrarComentarios").show();

        $("#MostrarBloco2").hide();
        $.ajax({url: "ajax/contaacesso_continarlendo_faq.php?idfaq=<?php echo $perguntas_e_respostas_id; ?>"});
    }
</script>
</script>
<div id="fb-root"></div>
    <script>(function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = 'https://connect.facebook.net/pt_BR/sdk.js#xfbml=1&version=v2.11&appId=500960907080311';
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
</script>
</body>
</html>
