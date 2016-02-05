$(document).ready(function () {
    var init;
    (init = function (context) {
    $(context || 'body').find('select')
        .chosen({
            allow_single_deselect:     true,
            no_results_text:           Translator.trans('chosen.no_results_text'),
            placeholder_text_multiple: Translator.trans('chosen.placeholder_text_multiple'),
            placeholder_text_single:   Translator.trans('chosen.placeholder_text_single')
        })
        .change(function () {
            $(this).trigger('chosen:updated');
        });
    })();

    $(document).on('propertyFormSubmit', function (e, $form) {
        init($form);
    });
});
