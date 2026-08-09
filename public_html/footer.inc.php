<?php
$outros_servicos_array = OutrosServicos::getAll($db);
?>

<footer class="bg-gray-900 text-gray-300 mt-12">

    <!-- Links do footer -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">

            <!-- Perguntas e Respostas -->
            <?php if ($perguntas_e_respostas_menu): ?>
            <div>
                <h3 class="text-white font-bold text-sm uppercase tracking-wider mb-4">
                    Perguntas e Respostas
                </h3>
                <ul class="space-y-2">
                    <?php foreach ($perguntas_e_respostas_menu as $menu_key => $menu_val):
                        $url_grupo = PerguntasERespostasGrupo::getUrlGrupo($menu_key);
                    ?>
                    <li>
                        <a href="<?php echo htmlspecialchars($url_grupo); ?>"
                           class="text-sm text-gray-400 hover:text-white transition-colors">
                            <?php echo htmlspecialchars($menu_val['descricao']); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Outros Serviços -->
            <?php if ($outros_servicos_array): ?>
            <div>
                <h3 class="text-white font-bold text-sm uppercase tracking-wider mb-4">
                    Outros Serviços
                </h3>
                <ul class="space-y-2">
                    <?php foreach ($outros_servicos_array as $servico): ?>
                    <li>
                        <a href="<?php echo htmlspecialchars($servico['link']); ?>"
                           <?php echo $servico['link_target'] ? 'target="_blank" rel="noopener"' : ''; ?>
                           class="text-sm text-gray-400 hover:text-white transition-colors">
                            <?php echo htmlspecialchars($servico['titulo']); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Institucional -->
            <div>
                <h3 class="text-white font-bold text-sm uppercase tracking-wider mb-4">
                    Institucional
                </h3>
                <ul class="space-y-2">
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
                           class="text-sm text-gray-400 hover:text-white transition-colors">
                            <?php echo $label; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Acesso Rápido -->
            <div>
                <h3 class="text-white font-bold text-sm uppercase tracking-wider mb-4">
                    Acesso Rápido
                </h3>
                <ul class="space-y-2">
                    <li><a href="<?php echo NOTICIAS_MAIS; ?>" class="text-sm text-gray-400 hover:text-white transition-colors">Notícias</a></li>
                    <li><a href="<?php echo CONTATO; ?>" class="text-sm text-gray-400 hover:text-white transition-colors">Contato</a></li>
                    <li><a href="<?php echo NEWSLETTER; ?>/" class="text-sm text-gray-400 hover:text-white transition-colors">Newsletter</a></li>
                    <li>
                        <a href="<?php echo FORUM_CONSUMIDOR; ?>/" target="_blank" rel="noopener"
                           class="text-sm text-gray-400 hover:text-white transition-colors">
                            Fórum do Consumidor
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo FORUM_ADVOGADO; ?>/" target="_blank" rel="noopener"
                           class="text-sm text-gray-400 hover:text-white transition-colors">
                            Área do Advogado
                        </a>
                    </li>
                </ul>
            </div>

        </div>
    </div>

    <!-- Barra inferior -->
    <div class="border-t border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">

                <img src="<?php echo PROJECT_ROOT; ?>img/logo-footer.png"
                     alt="SOS Consumidor"
                     class="h-10 w-auto opacity-80">

                <p class="text-sm text-gray-500 text-center">
                    &copy; <?php echo date('Y'); ?> SOS Consumidor &mdash; Serviço de Orientação ao Consumidor
                </p>

                <div class="flex items-center gap-4 text-xs text-gray-600">
                    <a href="<?php echo isset($inst_url) ? $inst_url . '#politica-de-privacidade' : PROJECT_ROOT . 'institucional/#politica-de-privacidade'; ?>"
                       class="hover:text-gray-400 transition-colors">
                        Privacidade
                    </a>
                    <span>&middot;</span>
                    <a href="<?php echo isset($inst_url) ? $inst_url . '#politica-de-publicidade' : PROJECT_ROOT . 'institucional/#politica-de-publicidade'; ?>"
                       class="hover:text-gray-400 transition-colors">
                        Publicidade
                    </a>
                </div>

            </div>
        </div>
    </div>

</footer>

<?php include PROJECT_PATH . 'ajudinha-widget.inc.php'; ?>
<link rel="stylesheet" href="<?php echo PROJECT_ROOT; ?>assets/css/leitor-noticias-20260809.css">
<script defer src="<?php echo PROJECT_ROOT; ?>assets/js/leitor-noticias-20260809.js"></script>
<?php include PROJECT_PATH . 'footer_js.inc.php'; ?>
