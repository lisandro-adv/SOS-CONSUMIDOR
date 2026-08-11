<script>
    var PROJECT_ROOT = '<?php echo PROJECT_ROOT; ?>';
</script>
<script defer type="text/javascript" src="<?php echo PROJECT_ROOT; ?>assets/js/app.js?v=20260810-perf2"></script>
<script defer src="<?php echo PROJECT_ROOT; ?>assets/js/ferramentas-funil-20260811.js?v=20260811-1"></script>
<script>
    (function () {
        var paginaAtual = String(window.location.pathname || '/').replace(/\/+$/, '') || '/';
        var paginas = {
            '/ia-consumidor': 'pagina_ia_consumidor',
            '/juros': 'pagina_juros',
            '/calculos': 'pagina_calculos'
        };
        if (!paginas[paginaAtual]) return;

        var endpoint = '/ia_consumidor/usage_event.php';
        var uuidPattern = /^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i;

        function visitanteId() {
            var cookie = document.cookie.match(/(?:^|; )sos_anon_visitor=([^;]+)/);
            var id = cookie ? decodeURIComponent(cookie[1]) : '';
            if (!uuidPattern.test(id)) {
                try { id = localStorage.getItem('sos_anon_visitor') || ''; } catch (error) {}
            }
            if (!uuidPattern.test(id)) {
                id = ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, function (c) {
                    return (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16);
                });
            }
            try { localStorage.setItem('sos_anon_visitor', id); } catch (error) {}
            document.cookie = 'sos_anon_visitor=' + encodeURIComponent(id) + '; Max-Age=31536000; Path=/; SameSite=Lax';
            return id;
        }

        function registrar(evento, ferramenta) {
            var payload = JSON.stringify({ evento: evento, ferramenta: ferramenta, visitante_id: visitanteId() });
            if (navigator.sendBeacon) {
                try {
                    if (navigator.sendBeacon(endpoint, new Blob([payload], { type: 'application/json' }))) return;
                } catch (error) {}
            }
            fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: payload,
                keepalive: true
            }).catch(function () {});
        }

        function iniciar() {
            registrar('page_view', paginas[paginaAtual]);
            var root = document.getElementById('sos-ajudinha');
            var toggle = document.getElementById('sos-ajudinha-toggle');
            if (!root || !toggle) return;
            toggle.addEventListener('click', function () {
                if (toggle.getAttribute('aria-expanded') !== 'true') registrar('ajudinha_open', 'ajudinha');
            });
            root.querySelectorAll('a[href]').forEach(function (link) {
                link.addEventListener('click', function () {
                    var destino = String(new URL(link.href, window.location.href).pathname || '/').replace(/\/+$/, '') || '/';
                    var ferramenta = {
                        '/ia-consumidor': 'ajudinha_link_ia_consumidor',
                        '/juros': 'ajudinha_link_juros',
                        '/calculos': 'ajudinha_link_calculos'
                    }[destino];
                    if (ferramenta) registrar('ajudinha_link_click', ferramenta);
                });
            });
        }

        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar);
        else iniciar();
    })();
</script>
<script>
    (function (i, s, o, g, r, a, m) {
        i['GoogleAnalyticsObject'] = r;
        i[r] = i[r] || function () {
            (i[r].q = i[r].q || []).push(arguments)
        }, i[r].l = 1 * new Date();
        a = s.createElement(o),
            m = s.getElementsByTagName(o)[0];
        a.async = 1;
        a.src = g;
        m.parentNode.insertBefore(a, m)
    })(window, document, 'script', 'https://www.google-analytics.com/analytics.js', 'ga');

    ga('create', 'UA-87653757-1', 'auto');
    ga('send', 'pageview');

</script>

<?php Msg::check(); ?>
