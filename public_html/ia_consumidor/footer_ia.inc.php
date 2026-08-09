<?php include PROJECT_PATH . 'ajudinha-widget.inc.php'; ?>
<footer class="border-t border-gray-200 mt-8 py-6 bg-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-400">
            <p>&copy; <?php echo date('Y'); ?> SOS Consumidor &mdash; Serviço de Orientação ao Consumidor</p>
            <div class="flex gap-4">
                <a href="<?php echo PROJECT_ROOT; ?>institucional/#politica-de-privacidade"
                   class="hover:text-gray-600 transition-colors">Privacidade</a>
                <a href="<?php echo PROJECT_ROOT; ?>contatos"
                   class="hover:text-gray-600 transition-colors">Contato</a>
            </div>
        </div>
    </div>
</footer>
<!-- Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-87653757-1"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','UA-87653757-1');</script>
