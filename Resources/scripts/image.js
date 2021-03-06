$(document).ready(function () {
    $('body')
        .on('click', '.image_delete[data-url]', function (e) {
            e.preventDefault();

            var $link = $(this);

            if ($link.data('submitted') || !confirm(Translator.trans('image.action.delete.confirm'))) {
                return;
            }

            $link.data('submitted', true);

            var $image = $link.closest('.image');

            $.ajax({
                url:  $link.data('url'),
                type: 'post'
            }).done(function () {
                $image.remove();

                notify('image.action.delete.success');
            }).fail(onAjaxFail);
        })
        .on('click', '.image_toggle_enabled[data-disable-title][data-disable-url][data-enable-title][data-enable-url]', function (e) {
            e.preventDefault();

            var $link = $(this);

            if ($link.hasClass('image_disable')) {
                $.ajax({
                    url:  $link.data('disable-url'),
                    type: 'post'
                }).done(function () {
                    $link
                        .removeClass('image_disable')
                        .addClass('image_enable')
                        .attr('title', $link.data('enable-title'));

                    notify('image.action.disable.success');
                }).fail(onAjaxFail);

                return;
            }

            $.ajax({
                url:  $link.data('enable-url'),
                type: 'post'
            }).done(function () {
                $link
                    .removeClass('image_enable')
                    .addClass('image_disable')
                    .attr('title', $link.data('disable-title'));

                notify('image.action.enable.success');
            }).fail(onAjaxFail);
        });

    var $sortable = $('.table_row .images[data-sort-url]');

    if ($sortable.length) {
        $sortable.sortable({
            placeholder: 'ui-state-highlight',
            update:      function () {
                $.ajax({
                    url:  $sortable.data('sort-url'),
                    type: 'post',
                    data: {
                        ids: $sortable.find('.image[data-id]').map(function () {
                            return $(this).data('id');
                        }).get()
                    }
                }).fail(onAjaxFail);
            }
        });
        $sortable.disableSelection();
    }
});
