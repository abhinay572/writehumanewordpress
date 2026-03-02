(function($) {
    'use strict';

    // Provider field toggle
    function toggleProviderFields() {
        var provider = $('#whah-api-provider').val();
        $('.whah-provider-fields').hide();
        $('#whah-' + provider + '-fields').show();
    }

    $('#whah-api-provider').on('change', toggleProviderFields);
    toggleProviderFields();

    // Test connection
    $('#whah-test-connection').on('click', function() {
        var $btn = $(this);
        var $result = $('#whah-test-result');

        $btn.prop('disabled', true).text(whahAdmin.i18n.testing);
        $result.html('');

        $.ajax({
            url: whahAdmin.restUrl + 'test',
            method: 'POST',
            headers: {
                'X-WP-Nonce': whahAdmin.nonce,
            },
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    $result.html('<div class="whah-test-success">' + whahAdmin.i18n.success + ' Provider: ' + response.provider + ', Words: ' + response.input_words + ' → ' + response.output_words + '</div>');
                } else {
                    $result.html('<div class="whah-test-error">' + whahAdmin.i18n.failed + ' ' + response.error + '</div>');
                }
            },
            error: function(xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Unknown error';
                $result.html('<div class="whah-test-error">' + whahAdmin.i18n.failed + ' ' + msg + '</div>');
            },
            complete: function() {
                $btn.prop('disabled', false).text(whahAdmin.i18n.testBtn);
            }
        });
    });

})(jQuery);
