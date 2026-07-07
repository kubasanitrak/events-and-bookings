(function ($) {
    'use strict';

    var cfg = window.eab_public || {};

    function post(action, data, callback) {
        data = data || {};
        data.action = action;
        data.nonce = cfg.nonce;
        $.post(cfg.ajax_url, data).done(function (res) {
            callback(res && res.success ? res.data : null, res && !res.success ? res.data : null);
        }).fail(function () {
            callback(null, { message: 'Network error' });
        });
    }

    // Book from detail
    $(document).on('click', '.eab-btn--book[data-eab-book]', function (e) {
        e.preventDefault();
        var postId = $(this).data('eab-book');
        var spots = parseInt($('#eab-spots-' + postId).val(), 10) || 1;

        post('eab_add_to_basket', { post_id: postId, spots: spots }, function (data, err) {
            if (err && err.message) {
                window.alert(err.message);
                return;
            }
            if (data && data.checkout_url) {
                window.location.href = data.checkout_url;
            } else if (data && data.message) {
                window.alert(data.message);
            }
        });
    });

    // Remove line
    $(document).on('click', '.eab-remove-line', function () {
        var postId = $(this).data('post-id');
        post('eab_remove_from_basket', { post_id: postId }, function () {
            window.location.reload();
        });
    });

    // Invoice toggle
    $(document).on('change', '#eab_want_invoice', function () {
        $('.eab-invoice-fields').toggle($(this).is(':checked'));
    });

    function renderAttendees($container, spots, fieldDefs, existing) {
        existing = existing || [];
        var $list = $container.find('.eab-attendees-list').empty();
        var defs = [];
        try {
            defs = JSON.parse($container.attr('data-field-defs') || '[]');
        } catch (e) {
            defs = fieldDefs || [];
        }

        for (var i = 0; i < spots; i++) {
            var row = existing[i] || {};
            var $block = $('<div class="eab-attendee-block" data-index="' + i + '"></div>');
            $block.append('<h5>Účastník ' + (i + 1) + '</h5>');
            defs.forEach(function (field) {
                var key = field.field_key || field.name;
                var label = field.label || key;
                var type = field.field_type || 'text';
                var req = field.required ? ' required' : '';
                var val = row[key] || '';
                var $row = $('<div class="eab-auth-form__row"></div>');
                $row.append('<label>' + label + '</label>');
                if (type === 'textarea') {
                    $row.append('<textarea name="' + key + '"' + req + '>' + val + '</textarea>');
                } else if (type === 'date') {
                    $row.append('<input type="date" name="' + key + '" value="' + val + '"' + req + '>');
                } else {
                    $row.append('<input type="text" name="' + key + '" value="' + val + '"' + req + '>');
                }
                $block.append($row);
            });
            $list.append($block);
        }
    }

    function collectLineMeta($line) {
        var postId = $line.data('post-id');
        var spots = parseInt($line.find('.eab-spots-input').val(), 10) || 1;
        var services = [];
        $line.find('.eab-service-cb:checked').each(function () {
            services.push($(this).val());
        });
        var attendees = [];
        $line.find('.eab-attendee-block').each(function () {
            var row = {};
            $(this).find('input, textarea, select').each(function () {
                var n = $(this).attr('name');
                if (n) {
                    row[n] = $(this).val();
                }
            });
            attendees.push(row);
        });
        return {
            post_id: postId,
            line_meta: {
                spots: spots,
                services: services,
                attendees: attendees
            }
        };
    }

    function collectAllLines() {
        var lines = [];
        $('.eab-checkout-line').each(function () {
            lines.push(collectLineMeta($(this)));
        });
        return lines;
    }

    function initCheckout() {
        var $checkout = $('#eab-checkout');
        if (!$checkout.length) {
            return;
        }

        $('.eab-checkout-attendees').each(function () {
            var $att = $(this);
            var $line = $att.closest('.eab-checkout-line');
            var spots = parseInt($line.find('.eab-spots-input').val(), 10) || 1;
            renderAttendees($att, spots);
        });

        $(document).on('change', '.eab-spots-input', function () {
            var $line = $(this).closest('.eab-checkout-line');
            var spots = parseInt($(this).val(), 10) || 1;
            renderAttendees($line.find('.eab-checkout-attendees'), spots);
        });
    }

    $('#eab-checkout-form').on('submit', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $('#eab-checkout-submit').prop('disabled', true);
        var lines = collectAllLines();

        var payload = {
            lines: JSON.stringify(lines),
            payment_method: $form.find('input[name="payment_method"]:checked').val(),
            agree_terms: $form.find('input[name="agree_terms"]').is(':checked') ? 1 : 0,
            want_invoice: $form.find('#eab_want_invoice').is(':checked') ? 1 : 0,
            save_invoice_to_profile: $form.find('#eab_save_invoice_to_profile').is(':checked') ? 1 : 0,
            invoice_company_name: $('#eab_invoice_company_name').val(),
            invoice_street: $('#eab_invoice_street').val(),
            invoice_street_number: $('#eab_invoice_street_number').val(),
            invoice_city: $('#eab_invoice_city').val(),
            invoice_zip: $('#eab_invoice_zip').val(),
            invoice_ic: $('#eab_invoice_ic').val(),
            invoice_dic: $('#eab_invoice_dic').val()
        };

        post('eab_process_checkout', payload, function (data, err) {
            $btn.prop('disabled', false);
            if (err && err.message) {
                window.alert(err.message);
                return;
            }
            if (data && data.redirect) {
                window.location.href = data.redirect;
            }
        });
    });

    $(function () {
        initCheckout();
        initFilterPills();
    });

    function initFilterPills() {
        $(document).on('click', '.eab-filter-pill', function (e) {
            var href = $(this).attr('href');
            if (!href) {
                return;
            }

            e.preventDefault();
            window.location.href = href;
        });
    }
})(jQuery);
