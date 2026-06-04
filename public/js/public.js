(function ($) {
    'use strict';

    $(document).on('click', '.eab-btn--book[data-eab-book]', function (e) {
        e.preventDefault();
        var msg = (window.eab_public && eab_public.i18n && eab_public.i18n.booking_soon)
            ? eab_public.i18n.booking_soon
            : '';
        if (msg) {
            window.alert(msg);
        }
    });
})(jQuery);
