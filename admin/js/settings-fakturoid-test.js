(function ($) {
    'use strict';

    $(function () {
        var $btn = $('#eab-fakturoid-test-connectivity');
        var $out = $('#eab-fakturoid-test-output');
        if (!$btn.length || !$out.length) {
            return;
        }

        $btn.on('click', function () {
            if (typeof window.eabFakturoidTest === 'undefined') {
                $out.html(
                    '<p class="eab-fakturoid-test__fail">Testovací skript se nenačetl. Obnovte stránku (Ctrl/Cmd+Shift+R).</p>'
                );
                return;
            }

            var cfg = window.eabFakturoidTest;
            $btn.prop('disabled', true);
            $out.html('<p>' + cfg.i18n.running + '</p>');

            $.post(cfg.ajaxUrl, {
                action: 'eab_fakturoid_test_connectivity',
                nonce: cfg.nonce
            })
                .done(function (res) {
                    var data = res && res.success && res.data ? res.data : null;
                    if (!data) {
                        var failMsg =
                            (res && res.data && res.data.message) || cfg.i18n.error;
                        $out.html(
                            '<p class="eab-fakturoid-test__fail">' +
                                $('<div/>').text(failMsg).html() +
                                '</p>'
                        );
                        return;
                    }
                    var cls = data.ok ? 'eab-fakturoid-test__ok' : 'eab-fakturoid-test__fail';
                    var mark = data.ok ? '✓' : '✗';
                    $out.html(
                        '<p class="' +
                            cls +
                            '"><strong>' +
                            mark +
                            '</strong> ' +
                            $('<div/>').text(data.message || cfg.i18n.error).html() +
                            '</p>'
                    );
                })
                .fail(function (xhr) {
                    var detail = cfg.i18n.error;
                    if (xhr && xhr.status) {
                        detail += ' (HTTP ' + xhr.status + ')';
                    }
                    $out.html('<p class="eab-fakturoid-test__fail">' + detail + '</p>');
                })
                .always(function () {
                    $btn.prop('disabled', false);
                });
        });
    });
})(jQuery);
