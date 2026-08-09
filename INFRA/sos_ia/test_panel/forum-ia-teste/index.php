<?php
declare(strict_types=1);

session_start();
if (empty($_SESSION['forum_ia_test_csrf'])) {
    $_SESSION['forum_ia_test_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['forum_ia_test_csrf'];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Teste interno — SOS Responde</title>
    <style>
        body { margin: 0; font: 17px/1.55 Arial, sans-serif; color: #1e293b; background: #f4f7fb; }
        main { max-width: 820px; margin: 42px auto; padding: 28px; background: #fff; border-radius: 14px; box-shadow: 0 8px 25px #1e293b18; }
        h1 { margin: 0 0 8px; color: #094f77; font-size: 28px; }
        .notice { background: #fff7df; border-left: 4px solid #d98200; padding: 12px 14px; margin: 20px 0; }
        label, textarea, button { display: block; width: 100%; box-sizing: border-box; }
        textarea { min-height: 135px; margin: 8px 0 12px; padding: 12px; border: 1px solid #aab8c6; border-radius: 8px; font: inherit; }
        button { width: auto; padding: 11px 18px; border: 0; border-radius: 8px; background: #087f5b; color: #fff; font-weight: bold; cursor: pointer; }
        button[disabled] { opacity: .65; cursor: wait; }
        #result { display: none; margin-top: 24px; padding: 18px; border-radius: 8px; background: #edf7f3; white-space: pre-wrap; }
        #sources { margin: 12px 0 0; padding-left: 18px; white-space: normal; }
        .error { background: #fff0f0 !important; color: #991b1b; }
        .small { color: #536273; font-size: 14px; }
    </style>
</head>
<body>
<main>
    <h1>SOS Responde — painel de teste</h1>
    <p>Este painel é fechado, temporário e serve para avaliar clareza, segurança e utilidade das respostas. A pergunta não é gravada pelo painel nem pela API de teste.</p>
    <div class="notice"><strong>Não informe dados pessoais ou sigilosos.</strong> Não envie CPF, senhas, números de cartão, dados bancários, processos ou documentos.</div>
    <form id="question-form">
        <input type="hidden" id="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <label for="question"><strong>Qual é a sua dúvida de direito do consumidor?</strong></label>
        <textarea id="question" maxlength="2000" minlength="10" required placeholder="Ex.: Comprei um produto com defeito. O que devo fazer?"></textarea>
        <button id="submit" type="submit">Gerar resposta de teste</button>
    </form>
    <div id="result" aria-live="polite"></div>
    <p class="small">A resposta é educativa, não substitui análise individual. Use este painel apenas para testes internos.</p>
</main>
<script>
const form = document.getElementById('question-form');
const button = document.getElementById('submit');
const result = document.getElementById('result');
function esc(value) { const node = document.createElement('div'); node.textContent = value; return node.innerHTML; }
form.addEventListener('submit', async (event) => {
    event.preventDefault();
    result.className = '';
    result.style.display = 'block';
    result.textContent = 'Preparando resposta…';
    button.disabled = true;
    try {
        const response = await fetch('perguntar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ question: document.getElementById('question').value, csrf: document.getElementById('csrf').value })
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'Não foi possível gerar a resposta.');
        let html = esc(data.answer || 'Resposta vazia.').replace(/\n/g, '<br>');
        if (Array.isArray(data.sources) && data.sources.length) {
            html += '<h3>Fontes oficiais</h3><ul id="sources">' + data.sources.map((source) =>
                '<li><a target="_blank" rel="noopener noreferrer" href="' + esc(source.url) + '">' + esc(source.title) + '</a> — ' + esc(source.legal_basis) + '</li>'
            ).join('') + '</ul>';
        }
        result.innerHTML = html;
    } catch (error) {
        result.className = 'error';
        result.textContent = error.message;
    } finally {
        button.disabled = false;
    }
});
</script>
</body>
</html>
