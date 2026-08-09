<?php
/* O componente oficial do Ajudinha é o mesmo exibido na lateral de /juros/. */
if (defined('SOS_AJUDINHA_RENDERED')) {
    return;
}

$ajudinha_sidebar_mode = !empty($ajudinha_sidebar);
if ($ajudinha_sidebar_mode) {
    echo '<div class="sos-ajudinha-test-placement">';
}

include PROJECT_PATH . 'include/ajudinha-widget-ab.inc.php';

if ($ajudinha_sidebar_mode) {
    echo '</div>';
}
