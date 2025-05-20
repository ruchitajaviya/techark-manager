jQuery(document).ready(function($) {
   
    $(document).on('click','#reset_options', function(event) {
        event.preventDefault(); // Prevent default link action
        
        $.ajax({
            type: 'POST',
            url: techarkData.ajax_url,
            data: {
                action: 'techark_reset_security_options',
            },
            dataType: 'json',
            beforeSend: function () {
              
             },
            success: function (response) {
                if (response.success) {
                    // Optional: reset form fields before reload (if needed)
    
                    // Reload page to show notice
                    location.reload();
                }
            }
        });
    });
    $(document).on('change','.techark_security_changes', function(event) {
        var name = $(this).attr('name');
        var data_name = $(this).attr('data-name');
        var value = 0;
        var type = $(this).attr('type');
        if(type == 'checkbox') {
            if ($(this).is(':checked')) {
                var value = 1;
            } 
        } else {
            var value = $(this).val();
        }
       

        event.preventDefault(); // Prevent default link action
        $.ajax({
            type: 'POST',
            url: techarkData.ajax_url,
            data: {
                action: 'techark_update_security_options',
                name: name,
                data_name:data_name,
                value: value
            },
            dataType: 'json',
            beforeSend: function () {
              
             },
            success: function (response) {
                $('.techark-ajax-sucess-msg').html(response.message);
            }
        });
    });
});