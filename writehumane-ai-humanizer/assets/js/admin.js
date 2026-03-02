(function($) {
    'use strict';

    $(document).ready(function() {
        // Test Connection
        $('#whah-test-btn').on('click', function() {
            var btn = $(this);
            var result = $('#whah-test-result');

            btn.prop('disabled', true).text(whahAdmin.i18n.testing);
            result.text('').removeClass('whah-test-success whah-test-error');

            $.ajax({
                url: whahAdmin.restUrl + 'test',
                method: 'POST',
                headers: { 'X-WP-Nonce': whahAdmin.nonce },
                success: function(data) {
                    if (data.success) {
                        result.text(whahAdmin.i18n.testSuccess).addClass('whah-test-success');
                    } else {
                        result.text(whahAdmin.i18n.testFailed + (data.message || 'Unknown error')).addClass('whah-test-error');
                    }
                },
                error: function(xhr) {
                    var msg = 'Unknown error';
                    try {
                        var resp = JSON.parse(xhr.responseText);
                        msg = resp.message || resp.data?.message || msg;
                    } catch(e) {}
                    result.text(whahAdmin.i18n.testFailed + msg).addClass('whah-test-error');
                },
                complete: function() {
                    btn.prop('disabled', false).text(whahAdmin.i18n.testBtn);
                }
            });
        });
    });
})(jQuery);
