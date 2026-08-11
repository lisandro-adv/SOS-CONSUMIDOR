<style id="sos-global-header-standard">
:root {
    --sos-header-logo-block-space: 14px;
    --sos-header-rule-block-space: 12px;
}

/* Padrão global: reduz a folga externa sem alterar a distância entre banners. */
.c-main-header .c-logo {
    margin-block: var(--sos-header-logo-block-space);
}
.c-main-header .c-logo img {
    max-width: 100%;
    height: auto;
}
.c-main-header .c-main-header__date {
    padding-top: 0;
    margin: 0;
}
.c-main-header .c-main-header__hr {
    margin-block: var(--sos-header-rule-block-space);
}

@media (min-width: 768px) {
    /* A faixa superior funciona como uma única linha: logo/data e acessos
       ficam alinhados pelo centro, em vez de os botões encostarem ao topo. */
    .c-main-header > .container-fluid > .l-content-block > .row {
        display: flex;
        align-items: center;
    }
    .c-main-header .col-md-7 > .row {
        display: flex;
        align-items: center;
    }
}
</style>
<style id="sos-header-buttons-modern">
    /* Acessos do cabeçalho: compactos, legíveis e sem a troca automática de textos. */
    .c-main-header .c-forum-nav {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
        min-height: 0;
        background: transparent !important;
    }
    .c-main-header .c-main-header__btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: auto !important;
        min-width: 0;
        max-width: 190px;
        height: 42px;
        margin: 0 !important;
        padding: 8px 13px 8px 42px;
        border: 1px solid transparent;
        border-radius: 999px;
        background-position: 12px center !important;
        background-size: 23px 23px !important;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.15;
        letter-spacing: .01em;
        text-align: left;
        white-space: nowrap;
        box-shadow: 0 4px 10px rgba(9, 44, 73, .14);
        transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
    }
    .c-main-header .c-main-header__btn p {
        margin: 0 !important;
        font-size: inherit;
        line-height: inherit;
    }
    .c-main-header .c-main-header__btn p.news::before {
        content: 'Newsletter' !important;
        animation: none !important;
        opacity: 1 !important;
        font-size: inherit !important;
    }
    .c-main-header .c-main-header__btn p.consumidor::before {
        content: 'Dúvidas e perguntas' !important;
        animation: none !important;
        opacity: 1 !important;
        font-size: inherit !important;
    }
    .c-main-header .c-main-header__btn--news {
        background-color: #a50006 !important;
    }
    .c-main-header .c-main-header__btn--consumer {
        background-color: #0085b2 !important;
    }
    .c-main-header .c-main-header__btn:hover,
    .c-main-header .c-main-header__btn:focus-visible {
        transform: translateY(-1px);
        box-shadow: 0 6px 14px rgba(9, 44, 73, .22);
        filter: brightness(1.04);
    }
    @media (max-width: 767px) {
        .c-main-header .c-forum-nav {
            /* No celular, estes acessos ficam dentro do menu aberto pelos
               três pontos. Mantemos o elemento no DOM para que o JS legado
               possa alternar a classe .active, mas o estado fechado não
               ocupa espaço nem recebe foco/cliques. */
            position: absolute;
            top: 70px;
            left: 0;
            right: 0;
            z-index: 20;
            width: 100%;
            height: 0;
            max-height: 0;
            min-height: 0;
            justify-content: center;
            gap: 7px;
            padding: 0 12px;
            overflow: hidden;
            visibility: hidden;
            opacity: 0;
            pointer-events: none;
            transition: max-height .2s ease, padding .2s ease, opacity .2s ease, visibility .2s ease;
        }
        .c-main-header .c-forum-nav.active {
            height: auto;
            max-height: 90px;
            min-height: 58px;
            padding: 10px 12px;
            visibility: visible;
            opacity: 1;
            pointer-events: auto;
            background: #fff !important;
            box-shadow: 0 6px 14px rgba(9, 44, 73, .18);
        }
        .c-main-header .c-main-header__btn {
            flex: 0 1 auto;
            max-width: none;
            height: 38px;
            padding: 7px 8px 7px 34px;
            background-position: 8px center !important;
            background-size: 20px 20px !important;
            font-size: 11px;
        }
    }
</style>
<style id="sos-nav-overlay-fix">
    /* O submenu precisa formar uma camada própria: nenhum hero, banner ou
       texto da página deve aparecer através dele. */
    .c-main-header .c-main-nav {
        position: relative;
        z-index: 3000;
        isolation: isolate;
    }
    .c-main-header .c-main-nav__list,
    .c-main-header .c-main-nav__item--drop {
        position: relative;
        z-index: 3001;
    }
    .c-main-header .c-main-nav__item--drop > .c-main-nav-sub {
        z-index: 3010;
        background-color: #fff;
        opacity: 1;
        box-shadow: 0 8px 18px rgba(15, 47, 72, .18);
        isolation: isolate;
    }
    .c-main-header .c-main-nav-sub__item,
    .c-main-header .c-main-nav-sub__item > a {
        position: relative;
        z-index: 3011;
        background-color: #fff;
    }
    .c-main-header .c-main-nav-sub__item > a:hover,
    .c-main-header .c-main-nav-sub__item:hover > a {
        background-color: #e9eef3;
    }
</style>
<header class="c-main-header">
    <div class="container-fluid">
        <div class="l-content-block">
            <div class="row">
                <div class="col-sm-6 col-md-7 col-lg-8">
                    <div class="row">
                        <div class="col-md-7">
                            <div class="row">
                                <div class="col-xs-3 visible-xs-block text-left">
                                    <button class="c-hamburger visible-xs-inline-block js-hamburger"><span></span></button>
                                </div>
                                <div class="col-xs-7 col-sm-12 text-center">
                                    <h1 class="c-logo"><a href="<?php echo PROJECT_ROOT; ?>"><picture><source type="image/webp" srcset="<?php echo PROJECT_ROOT; ?>img/logo.webp"><img src="<?php echo PROJECT_ROOT; ?>img/logo.png" alt="SOS Consumidor" width="400" height="69"></picture></a></h1>
                                </div>
                                <div class="col-xs-2 visible-xs-block text-right">
                                    <button type="button" class="c-hamburger c-hamburger--dots visible-xs-inline-block js-hamburger-dot" aria-label="Abrir menu de acessos" aria-expanded="false" aria-controls="sos-mobile-access-menu"><span></span></button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <p class="c-main-header__date"><?php setlocale(LC_TIME, 'pt_BR.UTF-8', 'pt_BR.utf8', 'portuguese'); echo strftime('%A, %d de %B de %Y', strtotime('today')); ?></p>
                        </div>
                    </div>
                </div>
                <div id="sos-mobile-access-menu" class="col-sm-6 col-md-5 col-lg-4 text-right c-forum-nav" style="background-color: transparent">
                    <!--a target="_blank" href="<?php echo FORUM_ADVOGADO; ?>/" class="o-btn o-btn--primary c-main-header__btn c-main-header__btn--attorney">Área do Advogado</a -->
                    <a href="<?php echo NEWSLETTER; ?>/" class="o-btn o-btn--primary c-main-header__btn c-main-header__btn--news"><p class="news"></p></a>
                    <a href="<?php echo PROJECT_ROOT; ?>ia-consumidor/" class="o-btn o-btn--secondary c-main-header__btn c-main-header__btn--consumer" data-sos-tool="ia" data-sos-source="header_button" data-sos-impression><p class="consumidor"></p></a>
                    <!--a href="<?php echo NEWSLETTER; ?>/" class="o-btn o-btn--tertiary c-main-header__btn c-main-header__btn--news">Newsletter</a-->
                </div>
            </div>
            <hr class="c-main-header__hr">
            <form action="<?php echo PROJECT_ROOT . 'noticias-cse'; ?>" method="get" class="c-search c-search--sm">
                <input name="q" type="text" placeholder="busca" class="form-control form-control--no-border">
                <button class="o-btn o-btn--primary o-btn--sm">Buscar</button>
            </form>


            <div class="c-main-nav">
                <div class="row">
                    <div class="col-md-9 col-lg-8">
                        <ul class="c-main-nav__list">
                            <?php
                            #require PROJECT_PATH . 'admsite/classes/perguntas_e_respostas/perguntas_e_respostas.inc.php';
                            $perguntas_e_respostas_menu = PerguntasERespostasAreas::getGrupoAreasAllMenu($db);
                            #print_r2($perguntas_e_respostas_menu);die;
                            if ($perguntas_e_respostas_menu) {
                                ?>
                                <li class="c-main-nav__item c-main-nav__item--drop">
                                    <a href="<?php echo PERGUNTAS_E_RESPOSTAS_MAIS; ?>">
                                        Perguntas e Respostas
                                    </a>
                                    <ul class="c-main-nav-sub">

                                        <?php foreach ($perguntas_e_respostas_menu as $menu_key => $menu_val) {
                                            $url_grupo = PerguntasERespostasGrupo::getUrlGrupo($menu_key);
                                            ?>
                                            <li class="c-main-nav-sub__item"><a
                                                    href="<?php echo $url_grupo; ?>"><?php echo $menu_val['descricao']; ?></a>
                                                <ul class="c-main-nav-sub c-main-nav-sub--sub hidden-xs">
                                                    <?php

                                                    foreach ($menu_val['aresas'] as $aresas_key => $aresas_val) {
                                                        $destino_area = $menu_val['destinos'][$aresas_key] ?? array();
                                                        $count_perguntas_e_respostas = (int) ($destino_area['quantidade'] ?? 0);
                                                        $link_target = "";
                                                        if($count_perguntas_e_respostas === 1){
                                                            $url_area = PerguntasERespostas::getURL($destino_area['pergunta'], $destino_area['id']);
                                                            if ($destino_area["link"]) {
                                                                $url_area = $destino_area["link"];
                                                                if ($destino_area["link_target"]) {
                                                                    $link_target = 'target = "_blank"';
                                                                }
                                                            }
                                                        }else{
                                                            $url_area = PerguntasERespostasAreas::getUrlAreas($menu_key, $aresas_key);
                                                        }
                                                        ?>
                                                        <li class="c-main-nav-sub__item"><a
                                                                href="<?php echo $url_area; ?>" <?php echo $link_target; ?> ><?php echo $aresas_val; ?></a>
                                                        </li>
                                                    <?php } ?>
                                                </ul>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                </li>
                            <?php } ?>
                            <li class="c-main-nav__item"><a href="<?php echo NOTICIAS_MAIS; ?>" style="color:#a50006">Notícias</a></li>
                            <li class="c-main-nav__item c-main-nav__item--drop">
                                <a href="<?php echo PROJECT_ROOT; ?>calculos/" style="color:#a50006">Ferramentas gratuitas</a>
                                <ul class="c-main-nav-sub">
                                    <li class="c-main-nav-sub__item"><a href="<?php echo PROJECT_ROOT; ?>ia-consumidor/" data-sos-tool="ia" data-sos-source="main_menu" data-sos-impression>SOS Responde com IA</a></li>
                                    <li class="c-main-nav-sub__item"><a href="<?php echo PROJECT_ROOT; ?>juros/" data-sos-tool="juros" data-sos-source="main_menu" data-sos-impression>Comparador de juros</a></li>
                                    <li class="c-main-nav-sub__item"><a href="<?php echo PROJECT_ROOT; ?>calculos/" data-sos-tool="calculos" data-sos-source="main_menu" data-sos-impression>Calculadoras</a></li>
                                    <li class="c-main-nav-sub__item"><a href="<?php echo OUTROS_SERVICOS; ?>">Outros serviços</a></li>
                                </ul>
                            </li>
                            <li class="c-main-nav__item"><a href="<?php echo INSTITUCIONAL_LER; ?>">Institucional</a>
                            </li>
                            <li class="c-main-nav__item"><a href="<?php echo CONTATO; ?>">Contato</a></li>
                        </ul>
                    </div>

                    <div class="col-md-3 col-lg-4">
                        <form action="<?php echo PROJECT_ROOT . 'noticias-cse'; ?>" method="get" class="c-search c-search--md pull-right">
                            <input name="q" type="text" placeholder="Busca no Google" class="form-control form-control--no-border">
                            <button class="o-btn o-btn--primary o-btn--sm">Buscar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
<div id="zxzx3f" style="position:absolute;filter:alpha(opacity=0);opacity:0.001;z-index:10;">


</div>
