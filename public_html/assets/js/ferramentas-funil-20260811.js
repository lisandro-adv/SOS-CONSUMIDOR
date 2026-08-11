(function () {
    'use strict';

    var endpoint = '/ia_consumidor/usage_event.php';
    var uuidPattern = /^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i;

    function visitorId() {
        var match = document.cookie.match(/(?:^|; )sos_anon_visitor=([^;]+)/);
        var id = match ? decodeURIComponent(match[1]) : '';
        if (!id) {
            try { id = localStorage.getItem('sos_anon_visitor') || ''; } catch (error) {}
        }
        if (!uuidPattern.test(id)) {
            if (window.crypto && window.crypto.randomUUID) {
                id = window.crypto.randomUUID();
            } else {
                id = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (character) {
                    var random = Math.random() * 16 | 0;
                    return (character === 'x' ? random : (random & 3 | 8)).toString(16);
                });
            }
        }
        try { localStorage.setItem('sos_anon_visitor', id); } catch (error) {}
        document.cookie = 'sos_anon_visitor=' + encodeURIComponent(id) + '; Max-Age=31536000; Path=/; SameSite=Lax';
        return id;
    }

    function track(tool, eventName, source) {
        if (!/^(ia|juros|calculos)$/.test(tool) || !eventName) return;
        var payload = JSON.stringify({ ferramenta: tool, evento: eventName, visitante_id: visitorId() });
        try {
            fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: payload,
                keepalive: true
            }).catch(function () {});
        } catch (error) {}
        if (typeof window.ga === 'function') {
            window.ga('send', 'event', 'ferramentas_gratuitas', eventName, tool + ':' + (source || 'direto'));
        }
    }

    window.SOSFerramentas = { track: track, visitorId: visitorId };

    var seenKey = 'sos_tool_impressions_' + new Date().toISOString().slice(0, 10);
    var seen = {};
    try { seen = JSON.parse(sessionStorage.getItem(seenKey) || '{}') || {}; } catch (error) {}

    function registerImpression(element) {
        var tool = element.getAttribute('data-sos-tool');
        var source = element.getAttribute('data-sos-source') || 'pagina';
        var key = tool + ':' + source;
        if (!tool || seen[key]) return;
        seen[key] = true;
        try { sessionStorage.setItem(seenKey, JSON.stringify(seen)); } catch (error) {}
        track(tool, 'impressao', source);
    }

    var impressionElements = document.querySelectorAll('[data-sos-impression][data-sos-tool]');
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    registerImpression(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.25 });
        impressionElements.forEach(function (element) { observer.observe(element); });
    } else {
        impressionElements.forEach(registerImpression);
    }

    document.addEventListener('click', function (event) {
        var link = event.target.closest('[data-sos-tool][data-sos-source]');
        if (link) track(link.getAttribute('data-sos-tool'), 'clique', link.getAttribute('data-sos-source'));
    });

    document.querySelectorAll('[data-sos-tool-page]').forEach(function (element) {
        track(element.getAttribute('data-sos-tool-page'), 'inicio', 'pagina_ferramenta');
    });

    function installIaDemo() {
        if (!/^\/ia-consumidor\/?/.test(window.location.pathname)) return;
        var demo = document.querySelector('.ia-demo-chat');
        if (!demo) return;

        demo.id = 'demonstracao';
        demo.style.setProperty('display', 'block', 'important');
        demo.innerHTML = '';
        demo.setAttribute('data-sos-tool-page', 'ia');

        var style = document.createElement('style');
        style.textContent = '.sos-ia-demo{margin:22px 0;padding:22px;border:1px solid #cfe2ea;border-radius:16px;background:#f5fafc;color:#183247}.sos-ia-demo h2{margin:0 0 6px;color:#07557f}.sos-ia-demo>p{margin:0 0 15px;color:#536879}.sos-ia-prompts{display:flex;flex-wrap:wrap;gap:8px}.sos-ia-prompts button{margin:0;padding:9px 12px;border:1px solid #8fb9cb;border-radius:999px;background:#fff;color:#07557f;font:700 13px Arial;cursor:pointer}.sos-ia-prompts button:hover,.sos-ia-prompts button:focus{border-color:#087f5b;background:#edf8f3;outline:none}.sos-ia-answer{margin-top:15px;padding:15px;border-left:4px solid #087f5b;border-radius:8px;background:#fff;line-height:1.5}.sos-ia-answer[hidden]{display:none}.sos-ia-answer strong{display:block;margin-bottom:5px;color:#07557f}.sos-ia-demo-cta{display:inline-block;margin-top:14px;padding:11px 16px;border-radius:8px;background:#087f5b;color:#fff!important;font-weight:800;text-decoration:none}.sos-ia-demo-note{display:block;margin-top:10px;color:#667b89;font-size:12px}';
        document.head.appendChild(style);

        var wrapper = document.createElement('section');
        wrapper.className = 'sos-ia-demo';
        wrapper.setAttribute('aria-labelledby', 'sos-ia-demo-title');
        wrapper.innerHTML = '<h2 id="sos-ia-demo-title">Veja como o SOS Responde ajuda</h2>' +
            '<p>Escolha uma dúvida de exemplo. Esta demonstração não envia informações ao servidor.</p>' +
            '<div class="sos-ia-prompts"></div>' +
            '<div class="sos-ia-answer" aria-live="polite" hidden></div>' +
            '<a class="sos-ia-demo-cta" href="#form-cadastro" data-sos-tool="ia" data-sos-source="demo_cta">Criar conta gratuita</a>' +
            '<small class="sos-ia-demo-note">A resposta real depende dos fatos informados e tem caráter educativo; não substitui análise profissional.</small>';
        demo.appendChild(wrapper);
        if (window.location.hash === '#demonstracao') {
            window.setTimeout(function () { demo.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 0);
        }

        var examples = [
            { question: 'Meu nome apareceu no SPC. O que devo verificar?', answer: 'Confira qual empresa fez o registro, a origem e o vencimento da dívida e se houve comunicação prévia. Guarde consultas, mensagens e comprovantes. Com esses fatos organizados, fica mais fácil identificar se cabe contestação.' },
            { question: 'Os juros do meu empréstimo parecem altos.', answer: 'Localize no contrato a taxa mensal ou anual, o mês da contratação, o valor financiado e o número de parcelas. Depois, compare a taxa com a média do Banco Central para a mesma modalidade e período.' },
            { question: 'Paguei um boleto atrasado. Como conferir o valor?', answer: 'Separe o valor original, a data de vencimento, a data do pagamento e as regras de multa e juros indicadas no boleto ou contrato. A calculadora pode estimar o total para você conferir a cobrança.' }
        ];
        var prompts = wrapper.querySelector('.sos-ia-prompts');
        var answer = wrapper.querySelector('.sos-ia-answer');
        examples.forEach(function (example) {
            var button = document.createElement('button');
            button.type = 'button';
            button.textContent = example.question;
            button.addEventListener('click', function () {
                answer.innerHTML = '';
                var title = document.createElement('strong');
                title.textContent = 'Como a Ajudinha organizaria a questão:';
                var copy = document.createElement('span');
                copy.textContent = example.answer;
                answer.appendChild(title);
                answer.appendChild(copy);
                answer.hidden = false;
                track('ia', 'demo_resultado', 'demo_pergunta');
            });
            prompts.appendChild(button);
        });
        track('ia', 'inicio', 'demo');

        var form = document.getElementById('form-cadastro');
        if (form) {
            form.addEventListener('submit', function () { track('ia', 'cadastro', 'formulario'); });
        }
        var successDialog = document.querySelector('#ia-alert-modal:not([hidden]) #ia-alert-dialog.ia-alert-dialog--success');
        var successMessage = successDialog ? successDialog.querySelector('#ia-alert-message') : null;
        if (successMessage && /cadastro realizado/i.test(successMessage.textContent || '')) {
            track('ia', 'cadastro_criado', 'retorno_cadastro');
        }
    }

    installIaDemo();
}());
