(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        var widgets = document.querySelectorAll('.whah-widget');

        widgets.forEach(function(widget) {
            var input = widget.querySelector('.whah-input');
            var output = widget.querySelector('.whah-output');
            var outputArea = widget.querySelector('.whah-output-area');
            var humanizeBtn = widget.querySelector('.whah-humanize-btn');
            var btnText = widget.querySelector('.whah-btn-text');
            var spinner = widget.querySelector('.whah-spinner');
            var copyBtn = widget.querySelector('.whah-copy-btn');
            var errorDiv = widget.querySelector('.whah-error');
            var loadingDiv = widget.querySelector('.whah-loading');
            var modeSelect = widget.querySelector('.whah-mode-select');
            var inputCount = widget.querySelector('.whah-input-count');
            var outputStats = widget.querySelector('.whah-output-stats');
            var defaultMode = widget.getAttribute('data-mode') || 'balanced';
            var defaultTone = widget.getAttribute('data-tone') || 'professional';

            function countWords(text) {
                return text.trim().split(/\s+/).filter(function(w) { return w; }).length;
            }

            input.addEventListener('input', function() {
                var count = input.value.trim() ? countWords(input.value) : 0;
                inputCount.textContent = count;
            });

            humanizeBtn.addEventListener('click', function() {
                var text = input.value.trim();

                if (!text) {
                    showError(whahFrontend.i18n.empty);
                    return;
                }

                var words = countWords(text);
                if (words > whahFrontend.maxWords) {
                    showError(whahFrontend.i18n.tooLong);
                    return;
                }

                hideError();
                setLoading(true);

                fetch(whahFrontend.restUrl + 'humanize', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': whahFrontend.nonce,
                    },
                    body: JSON.stringify({
                        text: text,
                        mode: modeSelect.value || defaultMode,
                        tone: defaultTone,
                    }),
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        output.value = data.text;
                        outputArea.style.display = 'block';
                        outputStats.textContent = data.input_words + ' ' + whahFrontend.i18n.wordsLabel + ' \u2192 ' + data.output_words + ' ' + whahFrontend.i18n.wordsLabel;
                    } else {
                        showError(data.message || whahFrontend.i18n.error);
                    }
                })
                .catch(function() {
                    showError(whahFrontend.i18n.error);
                })
                .finally(function() {
                    setLoading(false);
                });
            });

            copyBtn.addEventListener('click', function() {
                navigator.clipboard.writeText(output.value).then(function() {
                    copyBtn.textContent = whahFrontend.i18n.copied;
                    setTimeout(function() {
                        copyBtn.textContent = whahFrontend.i18n.copy;
                    }, 2000);
                });
            });

            function setLoading(isLoading) {
                humanizeBtn.disabled = isLoading;
                if (isLoading) {
                    btnText.textContent = whahFrontend.i18n.humanizing;
                    spinner.style.display = 'inline-block';
                    loadingDiv.style.display = 'flex';
                } else {
                    btnText.textContent = whahFrontend.i18n.humanize;
                    spinner.style.display = 'none';
                    loadingDiv.style.display = 'none';
                }
            }

            function showError(msg) {
                errorDiv.textContent = msg;
                errorDiv.style.display = 'block';
            }

            function hideError() {
                errorDiv.style.display = 'none';
            }
        });
    });
})();
