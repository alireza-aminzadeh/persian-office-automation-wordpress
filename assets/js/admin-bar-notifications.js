/**
 * Office Automation - Admin Bar Notifications
 * Optional enhancements for the notifications dropdown
 */
(function ($) {
    'use strict';

    $(function () {
        var $bar = $('#wp-admin-bar-persian-oa-notifications');
        if (!$bar.length) return;

        // Optional: add class when dropdown is hovered for extra styling
        $bar.on('mouseenter', function () {
            $(this).addClass('persian-oa-ab-open');
        }).on('mouseleave', function () {
            $(this).removeClass('persian-oa-ab-open');
        });
    });
})(jQuery);
