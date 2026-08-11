<?php
$outros_servicos_array = OutrosServicos::getAll($db);
?>

<style id="sos-footer-styles">
    .sos-footer { background:#111827; color:#d1d5db; margin-top:3rem; }
    .sos-footer-inner { max-width:80rem; margin:0 auto; padding:3rem 1rem; }
    .sos-footer-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:2rem; }
    .sos-footer h3 { color:#fff; font-size:.875rem; line-height:1.25rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; margin:0 0 1rem; }
    .sos-footer ul { list-style:none; margin:0; padding:0; }
    .sos-footer li + li { margin-top:.5rem; }
    .sos-footer a { color:#9ca3af; font-size:.875rem; line-height:1.25rem; text-decoration:none; transition:color .15s ease; }
    .sos-footer a:hover, .sos-footer a:focus { color:#fff; }
    .sos-footer-bar { border-top:1px solid #1f2937; }
    .sos-footer-bar-inner { max-width:80rem; margin:0 auto; padding:1.5rem 1rem; }
    .sos-footer-bottom { display:flex; flex-direction:column; align-items:center; justify-content:space-between; gap:1rem; }
    .sos-footer-logo { height:2.5rem; width:auto; opacity:.8; }
    .sos-footer-copy { color:#6b7280; font-size:.875rem; line-height:1.25rem; text-align:center; margin:0; }
    .sos-footer-legal { display:flex; align-items:center; gap:1rem; color:#4b5563; font-size:.75rem; line-height:1rem; }
    .sos-footer-legal a { color:#4b5563; font-size:.75rem; }
    .sos-footer-legal a:hover, .sos-footer-legal a:focus { color:#9ca3af; }
    @media (min-width:640px) {
        .sos-footer-inner, .sos-footer-bar-inner { padding-left:1.5rem; padding-right:1.5rem; }
    }
    @media (min-width:768px) {
        .sos-footer-grid { grid-template-columns:repeat(4,minmax(0,1fr)); }
        .sos-footer-bottom { flex-direction:row; }
    }
    @media (min-width:1024px) {
        .sos-footer-inner, .sos-footer-bar-inner { padding-left:2rem; padding-right:2rem; }
    }
</style>

<footer class="sos-footer">

    <!-- Links do footer -->
    <div class="sos-footer-inner">
        <div class="sos-footer-grid">

            <!-- Perguntas e Respostas -->
            <?php if ($perguntas_e_respostas_menu): ?>
            <div>
                <h3>
                    Perguntas e Respostas
                </h3>
                <ul>
                    <?php foreach ($perguntas_e_respostas_menu as $menu_key => $menu_val):
                        $url_grupo = PerguntasERespostasGrupo::getUrlGrupo($menu_key);
                    ?>
                    <li>
                        <a href="<?php echo htmlspecialchars($url_grupo); ?>"
                           >
                            <?php echo htmlspecialchars($menu_val['descricao']); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Ferramentas gratuitas -->
            <div>
                <h3>
                    Ferramentas gratuitas
                </h3>
                <ul>
                    <li><a href="<?php echo PROJECT_ROOT; ?>ia-consumidor/" data-sos-tool="ia" data-sos-source="footer" data-sos-impression>SOS Responde com IA</a></li>
                    <li><a href="<?php echo PROJECT_ROOT; ?>juros/" data-sos-tool="juros" data-sos-source="footer" data-sos-impression>Comparador de juros</a></li>
                    <li><a href="<?php echo PROJECT_ROOT; ?>calculos/" data-sos-tool="calculos" data-sos-source="footer" data-sos-impression>Calculadoras</a></li>
                    <?php foreach (($outros_servicos_array ?: []) as $servico): ?>
                    <li>
                        <a href="<?php echo htmlspecialchars($servico['link']); ?>"
                           <?php echo $servico['link_target'] ? 'target="_blank" rel="noopener"' : ''; ?>>
                            <?php echo htmlspecialchars($servico['titulo']); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Institucional -->
            <div>
                <h3>
                    Institucional
                </h3>
                <ul>
                    <?php
                    $inst_url = isset($institucional_url_base) ? Institucional::getUrl($institucional_url_base) : PROJECT_ROOT . 'institucional/';
                    $inst_links = [
                        '#quem-somos'              => 'Quem somos',
                        '#na-midia'                => 'Na mídia',
                        '#politica-de-privacidade' => 'Política de Privacidade',
                        '#politica-de-publicidade' => 'Política de Publicidade',
                        '#opinioes'                => 'Opiniões',
                    ];
                    foreach ($inst_links as $anchor => $label):
                    ?>
                    <li>
                        <a href="<?php echo $inst_url . $anchor; ?>"
                           >
                            <?php echo $label; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Acesso Rápido -->
            <div>
                <h3>
                    Acesso Rápido
                </h3>
                <ul>
                    <li><a href="<?php echo NOTICIAS_MAIS; ?>">Notícias</a></li>
                    <li><a href="<?php echo CONTATO; ?>">Contato</a></li>
                    <li><a href="<?php echo NEWSLETTER; ?>/">Newsletter</a></li>
                    <li>
                        <a href="<?php echo FORUM_ADVOGADO; ?>/" target="_blank" rel="noopener"
                           >
                            Área do Advogado
                        </a>
                    </li>
                </ul>
            </div>

        </div>
    </div>

    <!-- Barra inferior -->
    <div class="sos-footer-bar">
        <div class="sos-footer-bar-inner">
            <div class="sos-footer-bottom">

                <picture>
                    <source type="image/webp" srcset="<?php echo PROJECT_ROOT; ?>img/logo-footer.webp">
                    <img src="<?php echo PROJECT_ROOT; ?>img/logo-footer.png"
                         alt="SOS Consumidor"
                         width="270" height="48" loading="lazy" decoding="async"
                         class="sos-footer-logo">
                </picture>

                <p class="sos-footer-copy">
                    &copy; <?php echo date('Y'); ?> SOS Consumidor &mdash; Serviço de Orientação ao Consumidor
                </p>

                <div class="sos-footer-legal">
                    <a href="<?php echo isset($inst_url) ? $inst_url . '#politica-de-privacidade' : PROJECT_ROOT . 'institucional/#politica-de-privacidade'; ?>"
                       >
                        Privacidade
                    </a>
                    <span>&middot;</span>
                    <a href="<?php echo isset($inst_url) ? $inst_url . '#politica-de-publicidade' : PROJECT_ROOT . 'institucional/#politica-de-publicidade'; ?>"
                       >
                        Publicidade
                    </a>
                </div>

            </div>
        </div>
    </div>

</footer>

<?php include PROJECT_PATH . 'ajudinha-widget.inc.php'; ?>
<link rel="stylesheet" href="<?php echo PROJECT_ROOT; ?>assets/css/leitor-noticias-20260809.css?v=20260809-3">
<script defer src="<?php echo PROJECT_ROOT; ?>assets/js/leitor-noticias-20260809.js?v=20260809-2"></script>
<?php include PROJECT_PATH . 'footer_js.inc.php'; ?>
