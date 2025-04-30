jQuery(document).ready(function($) {
    $(document).on('click','.check-url-response', function(event) {
        event.preventDefault(); // Prevent default link action
        var link = $(this).data('check-link');
        var option_name = $(this).data('option-name');
        var responseBox = $(this).closest('tr').find('.response-msg');
        $.ajax({
            type: 'POST',
            url: techarkData.ajax_url,
            data: {
                link: link,
                option_name: option_name,
                action: 'get_techark_check_url_status',
            },
            dataType: 'json',
            beforeSend: function () {
                responseBox.css('display','block');
                responseBox.html('<p class="wait">Please wait, checking the setting....</p>');
             },
            success: function (response) {
                responseBox.html(response.message);
                setTimeout(function () {
                    responseBox.html('');
                    responseBox.css('display','none');

                }, 5000);
            }
        });
    });
});