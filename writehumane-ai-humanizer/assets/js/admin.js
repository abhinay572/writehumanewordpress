(function($) {
    'use strict';

    $(document).ready(function() {
        // ==== API Test Connection (Settings page) ====
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

        // ==== Domain Connection Test (Connect page) ====
        $('#whah-test-domain-btn').on('click', function() {
            var btn = $(this);
            var resultBox = $('#whah-domain-test-result');
            var url = $('#whah_domain_url').val();
            var key = $('#whah_domain_api_key').val();

            btn.prop('disabled', true);
            btn.find('.dashicons').addClass('whah-spinning');

            resultBox
                .removeClass('whah-result-success whah-result-error')
                .addClass('whah-result-loading')
                .html('<span class="dashicons dashicons-update whah-spinning"></span> Testing connection...')
                .show();

            $.ajax({
                url: whahAdmin.restUrl + 'domain/test',
                method: 'POST',
                headers: { 'X-WP-Nonce': whahAdmin.nonce },
                contentType: 'application/json',
                data: JSON.stringify({ url: url, key: key }),
                success: function(data) {
                    resultBox.removeClass('whah-result-loading');
                    if (data.success) {
                        resultBox
                            .addClass('whah-result-success')
                            .html('<span class="dashicons dashicons-yes-alt"></span> ' + data.message);
                    } else {
                        resultBox
                            .addClass('whah-result-error')
                            .html('<span class="dashicons dashicons-warning"></span> ' + data.message);
                    }
                },
                error: function(xhr) {
                    resultBox.removeClass('whah-result-loading');
                    var msg = 'Connection failed. Please check the URL.';
                    try {
                        var resp = JSON.parse(xhr.responseText);
                        msg = resp.message || resp.data?.message || msg;
                    } catch(e) {}
                    resultBox
                        .addClass('whah-result-error')
                        .html('<span class="dashicons dashicons-warning"></span> ' + msg);
                },
                complete: function() {
                    btn.prop('disabled', false);
                    btn.find('.dashicons').removeClass('whah-spinning');
                }
            });
        });

        // ==== Disconnect Domain ====
        $('#whah-disconnect-btn').on('click', function() {
            if (!confirm('Are you sure you want to disconnect this domain?')) {
                return;
            }

            var btn = $(this);
            btn.prop('disabled', true);

            $.ajax({
                url: whahAdmin.restUrl + 'domain/disconnect',
                method: 'POST',
                headers: { 'X-WP-Nonce': whahAdmin.nonce },
                success: function() {
                    location.reload();
                },
                error: function() {
                    alert('Failed to disconnect. Please try again.');
                    btn.prop('disabled', false);
                }
            });
        });

        // ==== Password Toggle ====
        $('.whah-toggle-pass').on('click', function() {
            var input = $(this).siblings('input');
            var icon = $(this).find('.dashicons');
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
            } else {
                input.attr('type', 'password');
                icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
            }
        });
    });
})(jQuery);
