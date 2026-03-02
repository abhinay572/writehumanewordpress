(function($) {
    'use strict';

    $(document).ready(function() {
        var statusDiv = $('#whah-classic-status');

        function getEditor() {
            if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                return tinymce.get('content');
            }
            return null;
        }

        function getContent() {
            var editor = getEditor();
            if (editor && !editor.isHidden()) {
                return editor.getContent();
            }
            return $('#content').val() || '';
        }

        function setContent(text) {
            var editor = getEditor();
            if (editor && !editor.isHidden()) {
                editor.setContent(text);
            } else {
                $('#content').val(text);
            }
        }

        function getSelection() {
            var editor = getEditor();
            if (editor && !editor.isHidden()) {
                return editor.selection.getContent();
            }
            return '';
        }

        function replaceSelection(text) {
            var editor = getEditor();
            if (editor && !editor.isHidden()) {
                editor.selection.setContent(text);
            }
        }

        function showStatus(msg, type) {
            statusDiv
                .text(msg)
                .removeClass('whah-status-success whah-status-error whah-status-loading')
                .addClass('whah-status-' + type)
                .show();
        }

        function humanize(text, callback) {
            var mode = $('#whah-classic-mode').val();
            var tone = $('#whah-classic-tone').val();

            showStatus('Humanizing... This may take a moment.', 'loading');

            $.ajax({
                url: whahEditor.restUrl + 'humanize',
                method: 'POST',
                headers: { 'X-WP-Nonce': whahEditor.nonce },
                contentType: 'application/json',
                data: JSON.stringify({ text: text, mode: mode, tone: tone }),
                success: function(data) {
                    if (data.success) {
                        callback(data.text);
                        showStatus('Done! ' + data.input_words + ' words in → ' + data.output_words + ' words out.', 'success');
                    } else {
                        showStatus(data.message || 'Error occurred.', 'error');
                    }
                },
                error: function(xhr) {
                    var msg = 'Connection error.';
                    try {
                        var resp = JSON.parse(xhr.responseText);
                        msg = resp.message || resp.data?.message || msg;
                    } catch(e) {}
                    showStatus(msg, 'error');
                }
            });
        }

        // Humanize Full Content
        $('#whah-classic-full').on('click', function() {
            var content = getContent();
            if (!content.trim()) {
                showStatus('No content to humanize.', 'error');
                return;
            }
            humanize(content, function(result) {
                setContent(result);
            });
        });

        // Humanize Selected Text
        $('#whah-classic-selection').on('click', function() {
            var selected = getSelection();
            if (!selected.trim()) {
                showStatus('Please select some text in the editor first.', 'error');
                return;
            }
            humanize(selected, function(result) {
                replaceSelection(result);
            });
        });
    });
})(jQuery);
