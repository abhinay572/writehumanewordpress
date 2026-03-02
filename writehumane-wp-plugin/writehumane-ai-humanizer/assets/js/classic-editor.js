(function($) {
    'use strict';

    function getEditorContent() {
        if (typeof tinymce !== 'undefined' && tinymce.activeEditor && !tinymce.activeEditor.isHidden()) {
            return tinymce.activeEditor.getContent();
        }
        return $('#content').val() || '';
    }

    function setEditorContent(content) {
        if (typeof tinymce !== 'undefined' && tinymce.activeEditor && !tinymce.activeEditor.isHidden()) {
            tinymce.activeEditor.setContent(content);
        }
        $('#content').val(content);
    }

    function getSelectedText() {
        if (typeof tinymce !== 'undefined' && tinymce.activeEditor && !tinymce.activeEditor.isHidden()) {
            return tinymce.activeEditor.selection.getContent({ format: 'html' });
        }
        var textarea = document.getElementById('content');
        if (textarea) {
            return textarea.value.substring(textarea.selectionStart, textarea.selectionEnd);
        }
        return '';
    }

    function replaceSelectedText(newText) {
        if (typeof tinymce !== 'undefined' && tinymce.activeEditor && !tinymce.activeEditor.isHidden()) {
            tinymce.activeEditor.selection.setContent(newText);
        } else {
            var textarea = document.getElementById('content');
            if (textarea) {
                var start = textarea.selectionStart;
                var end = textarea.selectionEnd;
                var value = textarea.value;
                textarea.value = value.substring(0, start) + newText + value.substring(end);
            }
        }
    }

    function showStatus(html) {
        $('#whah-classic-status').html(html);
    }

    function humanize(text, isSelection) {
        if (!text.trim()) {
            showStatus('<div class="whah-error">No text to humanize.</div>');
            return;
        }

        var mode = $('#whah-mode').val();
        var tone = $('#whah-tone').val();

        showStatus('<div style="color:#6366f1;">Humanizing... please wait.</div>');
        $('#whah-humanize-full, #whah-humanize-selected').prop('disabled', true);

        $.ajax({
            url: whahClassic.restUrl + 'humanize',
            method: 'POST',
            headers: {
                'X-WP-Nonce': whahClassic.nonce,
            },
            contentType: 'application/json',
            data: JSON.stringify({
                text: text,
                mode: mode,
                tone: tone,
                post_id: whahClassic.postId,
            }),
            success: function(response) {
                if (response.success) {
                    if (isSelection) {
                        replaceSelectedText(response.text);
                    } else {
                        setEditorContent(response.text);
                    }
                    showStatus('<div class="whah-success">Done! ' + response.input_words + ' words → ' + response.output_words + ' words (' + response.provider + ')</div>');
                } else {
                    showStatus('<div class="whah-error">' + (response.message || 'Failed.') + '</div>');
                }
            },
            error: function(xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Request failed.';
                showStatus('<div class="whah-error">' + msg + '</div>');
            },
            complete: function() {
                $('#whah-humanize-full, #whah-humanize-selected').prop('disabled', false);
            }
        });
    }

    $(document).on('click', '#whah-humanize-full', function() {
        humanize(getEditorContent(), false);
    });

    $(document).on('click', '#whah-humanize-selected', function() {
        var selected = getSelectedText();
        if (!selected.trim()) {
            showStatus('<div class="whah-error">No text selected. Select text in the editor first.</div>');
            return;
        }
        humanize(selected, true);
    });

})(jQuery);
