jQuery(document).ready(function($) {
   
    $(document).on('click','#reset_options', function(event) {
        event.preventDefault(); // Prevent default link action
        $.ajax({
            type: 'POST',
            url: techarkData.ajax_url,
            data: {
                action: 'techark_reset_login_security_options',
            },
            dataType: 'json',
            beforeSend: function () {
            },
            success: function (response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    });
    $('#select_all_roles').on('click', function() {
        $('.techark_user_role input[type="checkbox"]').prop('checked', true);
    });

    $('#deselect_all_roles').on('click', function() {
        $('.techark_user_role input[type="checkbox"]').prop('checked', false);
    });
    $(document).on('click','.custom-submit-button', function(e) {
        e.preventDefault(); // Prevent form from submitting immediately
        $('.techark_user_role_modal').fadeIn(); // Show modal
    });

    $(document).on('click','#cancel_mail', function(e) {
        // $('#submit').click(); // Trigger hidden submit
        $('.techark_user_role_modal').fadeOut();
    });
    function toggleSendMailButton() {
        const anyChecked = $('input[name="user_roles[]"]:checked').length > 0;
        $('#sent_mail').prop('disabled', !anyChecked);
    }

    // Run on checkbox change
    $(document).on('change', 'input[name="user_roles[]"]', toggleSendMailButton);

    // Also run on page load to initialize state
    toggleSendMailButton();

    $(document).on('click', '#sent_mail', function(event) {
        event.preventDefault(); // Prevent default behavior
    
        // Get all checked role values
        var selectedRoles = $('input[name="user_roles[]"]:checked').map(function() {
            return $(this).val();
        }).get();
       
        var techark_custom_login_url_value = $('#techark_custom_login_url_value').val();

        $.ajax({
            type: 'POST',
            url: techarkData.ajax_url,
            data: {
                action: 'techark_sent_login_mail',
                roles:selectedRoles,
                techark_custom_login_url_value : techark_custom_login_url_value
            },
            dataType: 'json',
            beforeSend: function () {
                $('#mail_response_message').html('<span style="color: #007cba;">Sending email...</span>');
            },
            success: function (response) {
                if (response.success) {
                    if (response.success && response.data.status) {
                        $('#mail_response_message').html('<span style="color: green;"> ' + response.data.message + '</span>');
                    } else {
                        $('#mail_response_message').html('<span style="color: red;"> ' + response.data.message + '</span>');
                    }
                    setTimeout(function() {
                        $('#submit').click(); // Trigger hidden submit
                    }, 2000);
                    
                }
            }
        });
    });
});