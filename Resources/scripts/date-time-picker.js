$(document).ready(function () {
    var locale = 'en' !== LOCALE ? LOCALE : '';
    var translations = {
        datepicker: $.datepicker.regional['undefined' !== typeof $.datepicker.regional[locale] ? locale : ''],
        timepicker: $.timepicker.regional['undefined' !== typeof $.timepicker.regional[locale] ? locale : '']
    };

    var options = $.extend({}, translations.datepicker, translations.timepicker, {
        dateFormat: 'dd.mm.yy'
    });

    var init;
    (init = function (context) {
        ['date', 'datetime', 'time'].map(function (type) {
            $(context || 'body').find('input.' + type)[type + 'picker'](options);
        });
    })();

    $(document)
        .on('ajaxSuccess', init)
        .on('formCollectionAdd', function (e, $newElement) {
            init($newElement);
        });
});
