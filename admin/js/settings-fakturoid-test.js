(function ($) {
    'use strict';

    $(function () {
        var $btn = $('#eab-fakturoid-test-connectivity');
        var $out = $('#eab-fakturoid-test-output');
        if (!$btn.length || typeof eabFakturoidTest === 'undefined') {
            return;
        }

        $btn.on('click', function () {
            $btn.prop('disabled', true);
            $out.html('<p>' + eabFakturoidTest.i18n.running + '</p>');

            $.post(eabFakturoidTest.ajaxUrl, {
                action: 'eab_fakturoid_test_connectivity',
                nonce: eabFakturoidTest.nonce
            })
                .done(function (res) {
                    var data = res && res.data ? res.data : null;
                    if (!data) {
                        $out.html('<p class="eab-fakturoid-test__fail">' + eabFakturoidTest.i18n.error + '</p>');
                        return;
                    }
                    var cls = data.ok ? 'eab-fakturoid-test__ok' : 'eab-fakturoid-test__fail';
                    var mark = data.ok ? '✓' : '✗';
                    $out.html(
                        '<p class="' + cls + '"><strong>' + mark + '</strong> ' +
                        $('<div/>').text(data.message || eabFakturoidTest.i18n.error).html() +
                        '</p>'
                    );
                })
                .fail(function () {
                    $out.html('<p class="eab-fakturoid-test__fail">' + eabFakturoidTest.i18n.error + '</p>');
                })
                .always(function () {
                    $btn.prop('disabled', false);
                });
        });
    });
})(jQuery);
