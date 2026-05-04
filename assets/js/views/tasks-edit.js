jQuery(document).ready(function($) {
    if (typeof SimplePersianDatePicker !== 'undefined') {
        if (document.getElementById('deadline-jalali')) {
            new SimplePersianDatePicker(
                document.getElementById('deadline-jalali'),
                document.getElementById('deadline-gregorian'),
                { defaultToday: false }
            );
        }
        if (document.getElementById('start-date-jalali')) {
            new SimplePersianDatePicker(
                document.getElementById('start-date-jalali'),
                document.getElementById('start-date-gregorian'),
                { defaultToday: false }
            );
        }
    }
});