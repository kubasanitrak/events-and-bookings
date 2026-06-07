(function ($) {
    'use strict';

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function statusIcon(ok) {
        return ok
            ? '<span class="eab-gopay-test__ok">✓</span>'
            : '<span class="eab-gopay-test__fail">✗</span>';
    }

    function renderResult(data) {
        var oauth = data.oauth || {};
        var success = data.payment_success_page || {};
        var failed = data.payment_failed_page || {};
        var creds = data.credentials || {};
        var mode = data.test_mode
            ? eabGopayTest.i18n.sandbox
            : eabGopayTest.i18n.production;

        var html = '<div class="eab-gopay-test__result">';
        html += '<h3>' + escHtml(eabGopayTest.i18n.results) + '</h3>';

        html += '<p><strong>' + escHtml(eabGopayTest.i18n.oauth) + ':</strong> ';
        html += statusIcon(oauth.ok) + ' ' + escHtml(oauth.message || '');
        if (oauth.expires_in) {
            html += ' <span class="description">(' + escHtml(eabGopayTest.i18n.expires) + ' ' + oauth.expires_in + 's)</span>';
        }
        html += '</p>';

        html += '<table class="widefat striped eab-gopay-test__table"><tbody>';
        html += row(eabGopayTest.i18n.mode, mode);
        html += row(eabGopayTest.i18n.api, data.api_url);
        html += row(eabGopayTest.i18n.site, data.site_url);
        html += row(eabGopayTest.i18n.notify, data.notification_url);
        html += row(eabGopayTest.i18n.return, data.return_url_sample);
        html += row(eabGopayTest.i18n.failed, data.failed_url_sample);
        html += row(
            eabGopayTest.i18n.successPage,
            (success.url || '—') + ' ' + statusIcon(success.ok)
        );
        html += row(
            eabGopayTest.i18n.failedPage,
            (failed.url || '—') + ' ' + statusIcon(failed.ok)
        );
        html += row(eabGopayTest.i18n.goid, creds.goid || '—');
        html += row(
            eabGopayTest.i18n.checkout,
            data.checkout_ready
                ? eabGopayTest.i18n.checkoutReady
                : eabGopayTest.i18n.checkoutNotReady
        );
        html += '</tbody></table>';

        html += '<p class="description">' + escHtml(eabGopayTest.i18n.docs) + '</p>';
        html += '</div>';

        return html;
    }

    function row(label, value) {
        return (
            '<tr><th scope="row">' +
            escHtml(label) +
            '</th><td><code>' +
            escHtml(value) +
            '</code></td></tr>'
        );
    }

    $(function () {
        var $btn = $('#eab-gopay-test-connectivity');
        var $out = $('#eab-gopay-test-output');

        if (!$btn.length) {
            return;
        }

        $btn.on('click', function () {
            $btn.prop('disabled', true);
            $out.html('<p>' + escHtml(eabGopayTest.i18n.running) + '</p>');

            $.post(eabGopayTest.ajaxUrl, {
                action: 'eab_gopay_test_connectivity',
                nonce: eabGopayTest.nonce,
            })
                .done(function (res) {
                    if (res.success && res.data) {
                        $out.html(renderResult(res.data));
                    } else {
                        $out.html(
                            '<p class="eab-gopay-test__fail">' +
                                escHtml(
                                    (res.data && res.data.message) ||
                                        eabGopayTest.i18n.error
                                ) +
                                '</p>'
                        );
                    }
                })
                .fail(function () {
                    $out.html(
                        '<p class="eab-gopay-test__fail">' +
                            escHtml(eabGopayTest.i18n.error) +
                            '</p>'
                    );
                })
                .always(function () {
                    $btn.prop('disabled', false);
                });
        });
    });
})(jQuery);
