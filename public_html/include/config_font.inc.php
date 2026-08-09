<?php
require_once dirname(__FILE__) . '/../admsite/classes/config.inc.php';
#print_r2($_SERVER);
require PROJECT_PATH . 'admsite/classes/noticias/noticias.inc.php';
require PROJECT_PATH . 'admsite/classes/perguntas_e_respostas/perguntas_e_respostas.inc.php';
require PROJECT_PATH . 'admsite/classes/outros_servicos/outros_servicos.inc.php';
require PROJECT_PATH . 'admsite/classes/banners/banners.inc.php';
require PROJECT_PATH . 'admsite/classes/institucional/institucional.inc.php';

/**
 * $request_url
 * 1= está na pagina institucional
 * 0= não está
 */
$institucional_url_base=1;