(function () {
    'use strict';

    function whenReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
        } else {
            callback();
        }
    }

    function splitText(text, maxLength) {
        var paragraphs = text
            .split(/\n+/)
            .map(function (paragraph) { return paragraph.trim(); })
            .filter(Boolean);
        var chunks = [];

        paragraphs.forEach(function (paragraph) {
            var sentences = paragraph.match(/[^.!?]+(?:[.!?]+|$)/g) || [paragraph];
            var current = '';

            sentences.forEach(function (sentence) {
                sentence = sentence.trim();
                if (!sentence) return;

                if ((current + ' ' + sentence).trim().length <= maxLength) {
                    current = (current + ' ' + sentence).trim();
                    return;
                }

                if (current) chunks.push(current);

                if (sentence.length <= maxLength) {
                    current = sentence;
                    return;
                }

                var words = sentence.split(/\s+/);
                current = '';
                words.forEach(function (word) {
                    if ((current + ' ' + word).trim().length <= maxLength) {
                        current = (current + ' ' + word).trim();
                    } else {
                        if (current) chunks.push(current);
                        current = word;
                    }
                });
            });

            if (current) chunks.push(current);
        });

        return chunks;
    }

    function getArticleText(titleElement, contentElement) {
        var contentCopy = contentElement.cloneNode(true);
        contentCopy.querySelectorAll('script, style, noscript, button, [hidden]').forEach(function (element) {
            element.remove();
        });

        var contentText = (contentCopy.innerText || contentCopy.textContent || '')
            .replace(/[ \t]+/g, ' ')
            .replace(/\s*\n\s*/g, '\n')
            .trim();
        var titleText = (titleElement.innerText || titleElement.textContent || '').trim();

        return [titleText, contentText].filter(Boolean).join('\n');
    }

    function createReader(titleElement, contentElement) {
        var reader = document.createElement('section');
        reader.className = 'sos-news-reader';
        reader.setAttribute('aria-labelledby', 'sos-news-reader-title');
        reader.innerHTML = [
            '<div class="sos-news-reader__header">',
            '  <div>',
            '    <strong class="sos-news-reader__title" id="sos-news-reader-title">Ouça esta matéria</strong>',
            '    <span class="sos-news-reader__hint">Leitura automática, direto no seu navegador.</span>',
            '  </div>',
            '  <div class="sos-news-reader__controls">',
            '    <button type="button" data-sos-reader-action="play">▶ Ouvir matéria</button>',
            '    <button type="button" data-sos-reader-action="pause" disabled>⏸ Pausar</button>',
            '    <button type="button" data-sos-reader-action="stop" disabled>■ Parar</button>',
            '    <label class="sos-news-reader__speed">Velocidade',
            '      <select data-sos-reader-action="speed" aria-label="Velocidade da leitura">',
            '        <option value="0.8">0,8x</option>',
            '        <option value="1" selected>1x</option>',
            '        <option value="1.2">1,2x</option>',
            '        <option value="1.5">1,5x</option>',
            '      </select>',
            '    </label>',
            '  </div>',
            '</div>',
            '<p class="sos-news-reader__status" data-sos-reader-status role="status" aria-live="polite">Pronto para ler a matéria.</p>'
        ].join('');

        contentElement.parentNode.insertBefore(reader, contentElement);

        var playButton = reader.querySelector('[data-sos-reader-action="play"]');
        var pauseButton = reader.querySelector('[data-sos-reader-action="pause"]');
        var stopButton = reader.querySelector('[data-sos-reader-action="stop"]');
        var speedSelect = reader.querySelector('[data-sos-reader-action="speed"]');
        var status = reader.querySelector('[data-sos-reader-status]');
    var speech = ('speechSynthesis' in window) ? window.speechSynthesis : null;
        var chunks = splitText(getArticleText(titleElement, contentElement), 220);
        var currentChunk = 0;
        var utterance = null;
        var isReading = false;
        var isPaused = false;

        function setStatus(message) {
            status.textContent = message;
        }

        function setButtons(reading, paused) {
            pauseButton.disabled = !reading;
            stopButton.disabled = !reading;
            pauseButton.textContent = paused ? '▶ Continuar' : '⏸ Pausar';
        }

        function speakNext() {
            if (!isReading || isPaused) return;
            if (currentChunk >= chunks.length) {
                isReading = false;
                isPaused = false;
                setButtons(false, false);
                setStatus('Leitura concluída.');
                return;
            }

            utterance = new SpeechSynthesisUtterance(chunks[currentChunk]);
            utterance.lang = 'pt-BR';
            utterance.rate = Number(speedSelect.value);
            utterance.onstart = function () {
                setStatus('Lendo trecho ' + (currentChunk + 1) + ' de ' + chunks.length + '.');
            };
            utterance.onend = function () {
                currentChunk += 1;
                speakNext();
            };
            utterance.onerror = function (event) {
                if (event.error === 'canceled' || event.error === 'interrupted') return;
                isReading = false;
                isPaused = false;
                setButtons(false, false);
                setStatus('Não foi possível continuar a leitura neste navegador.');
            };
            speech.speak(utterance);
        }

        playButton.addEventListener('click', function () {
            if (!chunks.length) {
                setStatus('Esta matéria não tem conteúdo para leitura.');
                return;
            }

            speech.cancel();
            currentChunk = 0;
            isReading = true;
            isPaused = false;
            setButtons(true, false);
            speakNext();
        });

        pauseButton.addEventListener('click', function () {
            if (!isReading) return;
            if (isPaused) {
                isPaused = false;
                speech.resume();
                setButtons(true, false);
                setStatus('Leitura retomada.');
            } else {
                isPaused = true;
                speech.pause();
                setButtons(true, true);
                setStatus('Leitura pausada.');
            }
        });

        stopButton.addEventListener('click', function () {
            speech.cancel();
            isReading = false;
            isPaused = false;
            currentChunk = 0;
            setButtons(false, false);
            setStatus('Leitura parada.');
        });

        speedSelect.addEventListener('change', function () {
            if (!isReading) return;
            speech.cancel();
            isPaused = false;
            setButtons(true, false);
            setStatus('Alterando a velocidade…');
            speakNext();
        });

    window.addEventListener('beforeunload', function () {
      if (speech) speech.cancel();
    });

        if (!('speechSynthesis' in window) || !('SpeechSynthesisUtterance' in window)) {
            playButton.disabled = true;
            pauseButton.disabled = true;
            stopButton.disabled = true;
            setStatus('A leitura de áudio não é compatível com este navegador.');
        }
    }

    whenReady(function () {
        if (!/\/noticias(?:-|\/|$)/i.test(window.location.pathname)) return;

        var titleElement = document.querySelector('h1.o-tit');
        var contentElement = document.querySelector('.desize-texto-conteudo');
        if (!titleElement || !contentElement || document.querySelector('.sos-news-reader')) return;

        createReader(titleElement, contentElement);
    });
}());
