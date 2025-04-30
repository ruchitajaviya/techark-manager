jQuery(document).ready(function($) {


    $('#wp-core-update-btn').on('click', function () {
        const button = $(this);
        const statusDiv = $('#wp-core-update-status');

        statusDiv.removeClass('success error').hide();

        button.prop('disabled', true).text('Updating...');

        button.css('pointer-events', 'none');

        $.post(wpCoreUpdate.ajax_url, {
            action: 'wp_core_update_ajax',
            nonce: wpCoreUpdate.wp_nonce
        })
        .done(function (response) {
            statusDiv
                    .addClass('success')
                    .text('The WordPress site has been successfully updated.')
                    .fadeIn();
        })
        .fail(function () {
            statusDiv
                .addClass('error')
                .text('A server error occurred. Please try again later.')
                .fadeIn();
        })
        .always(function () {
            button.prop('disabled', false).text('Update WordPress Core');
        });
    });

    $('#cpm-search').on('keyup', function () {
        const searchTerm = $(this).val().toLowerCase();
        $('#cpm-plugin-list tr').each(function () {
            const pluginName = $(this).find('td:nth-child(2)').text().toLowerCase();
            $(this).toggle(pluginName.includes(searchTerm));
        });
    });

    $('#cpm-filter').on('change', function () {
        const filter = $(this).val();
        $('#cpm-search').val('');

        $('#cpm-plugin-list tr').each(function () {
            const status = $(this).data('status');
            const update = $(this).data('update');

            const isActive = status === 'active';
            const isInactive = status === 'inactive';
            const needsUpdate = update === 'needs-update';

            if (filter === 'all') {
                $(this).show();
            } else if (filter === 'active' && isActive) {
                $(this).show();
            } else if (filter === 'inactive' && isInactive) {
                $(this).show();
            } else if (filter === 'needs-update' && needsUpdate) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    function displayMessage(type, message) {
        const messageBox = $('#cpm-message');
        messageBox
            .removeClass('cpm-success cpm-error')
            .addClass(type === 'success' ? 'cpm-success' : 'cpm-error')
            .html(`<span>${message}</span> <a class="cpm-close-btn">&times;</a>`)
            .fadeIn();

        setTimeout(() => {
            messageBox.fadeOut();
        }, 5000);

        $('.cpm-close-btn').on('click', function () {
            messageBox.fadeOut();
        });
    }

    $('.cpm-update-plugin').on('click', function (e) {
        e.preventDefault();

        const button = $(this);
        const pluginSlug = button.data('plugin');

        $('.cpm-update-plugin').prop('disabled', true);

        button.text('Updating...');

        $.ajax({
            url: wpCoreUpdate.ajax_url,
            method: 'POST',
            data: {
                action: 'cpm_update_plugin',
                security: wpCoreUpdate.nonce,
                plugin_slug: pluginSlug
            },
            success: function (response) {
                if (response.success) {
                    button.closest('td').html('<span style="color: green;">Updated</span>');
                    displayMessage('success', 'Plugin updated successfully!');
                } else {
                    button.text('Update').prop('disabled', false);
                    displayMessage('error', response.data.message);
                }
            },
            error: function (xhr, status, error) {
                button.text('Update').prop('disabled', false);
                displayMessage('error', `An error occurred: ${error}`);
            },
            complete: function () {
                $('.cpm-update-plugin').not(button).prop('disabled', false);
            }
        });
    });

    function getExcludedPlugins() {
        const excludedPlugins = [];
        $('.exclude-plugin:checked').each(function () {
            excludedPlugins.push($(this).data('plugin'));
        });
        return excludedPlugins;
    }

    $('#cpm-update-all').on('click', function (e) {
        e.preventDefault();

        const button = $(this);
        button.prop('disabled', true).text('Updating Plugins...');
        button.css('pointer-events', 'none');

        const excludedPlugins = getExcludedPlugins();

        const plugins = $('.cpm-update-plugin').map(function () {
            const pluginSlug = $(this).data('plugin');
            if (excludedPlugins.includes(pluginSlug)) {
                return null; // Skip excluded plugins
            }
            return {
                slug: pluginSlug,
                isActive: $(this).data('status') === 'active'
            };
        }).get().filter(Boolean);

        let currentIndex = 0;

        function updateNextPlugin() {
            if (currentIndex >= plugins.length) {
                displayMessage('success', 'All selected plugins updated successfully!');
                button.text('Update All Plugins').prop('disabled', false);
                location.reload();
                return;
            }

            const plugin = plugins[currentIndex];
            button.text(`Updating Plugin ${currentIndex + 1} of ${plugins.length}...`);

            $.ajax({
                url: wpCoreUpdate.ajax_url,
                method: 'POST',
                data: {
                    action: 'cpm_update_plugin',
                    security: wpCoreUpdate.nonce,
                    plugin_slug: plugin.slug
                },
                success: function () {
                    currentIndex++;
                    updateNextPlugin();
                },
                error: function () {
                    displayMessage('error', `Failed to update plugin ${plugin.slug}`);
                    currentIndex++;
                    updateNextPlugin();
                }
            });
        }

        updateNextPlugin();
    });

    $('#wp-email-plugin-summary').on('click', function (e) {
        e.preventDefault();

        const emailInput = $('#wp-plugin-update-email');
        const statusDiv = $('#wp-email-update-status');
        const button = $(this);
        const email = emailInput.val().trim();

        // Clear any existing error styles
        emailInput.removeClass('error-msg');
        statusDiv.removeClass('success error').hide();

        // Validate email address
        if (!email) {
            emailInput.addClass('error-msg');
            showMessage('error', 'Email address is required.', statusDiv);
            return;
        }

        if (!validateEmail(email)) {
            emailInput.addClass('error-msg');
            showMessage('error', 'Please enter a valid email address.', statusDiv);
            return;
        }

        // Disable the button and show a loading state
        button.prop('disabled', true).text('Sending...');

        $.ajax({
            url: wpCoreUpdate.ajax_url,
            method: 'POST',
            data: {
                action: 'email_maintenance_summary',
                email: email,
                nonce: wpCoreUpdate.email_nonce
            },
            success: function (response) {
                if (response && response.success) {
                    showMessage('success', 'Maintenance summary has been successfully sent.', statusDiv);
                    resetForm(emailInput, statusDiv);
                } else {
                    showMessage('error', response.data.message || 'An error occurred.', statusDiv);
                }
            },
            error: function () {
                showMessage('error', 'A server error occurred. Please try again later.', statusDiv);
            },
            complete: function () {
                button.prop('disabled', false).text('Email Summary');
            }
        });
    });

    function showMessage(type, message, statusDiv) {
        statusDiv
            .removeClass('success error')
            .addClass(type === 'success' ? 'success' : 'error')
            .text(message)
            .fadeIn();
    }

    function validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function resetForm(emailInput, statusDiv) {
        setTimeout(() => {
            emailInput.val('');
            statusDiv.fadeOut();
        }, 3000);
    }

    $('#delete-old-crm-entries').on('click', function () {
        const button = $(this);
        const statusDiv = $('#delete-crm-status');

        statusDiv.removeClass('success error').hide();

        button.prop('disabled', true).text('Deleting...');

        button.css('pointer-events', 'none');

        $.post(wpCoreUpdate.ajax_url, {
            action: 'delete_old_crm_entries',
            nonce: wpCoreUpdate.delete_nonce
        })
        .done(function (response) {
            if (response && response.success) {
                statusDiv
                    .addClass('success')
                    .text(response.data || 'Old form entries deleted successfully.')
                    .fadeIn();
            } else {
                statusDiv
                    .addClass('error')
                    .text(response.data || 'Failed to delete form entries. Please try again.')
                    .fadeIn();
            }
        })
        .fail(function () {
            statusDiv
                .addClass('error')
                .text('A server error occurred. Please try again later.')
                .fadeIn();
        })
        .always(function () {
            button.prop('disabled', false).text('Delete Form Entries');
        });
    });

    $('.exclude-plugin').on('change', function () {
        const row = $(this).closest('tr');
        const plugin = $(this).data('plugin');
        const exclude = $(this).is(':checked');
        const checkbox = $(this);

        if ($(this).is(':checked')) {
            row.addClass('checked-row');
        } else {
            row.removeClass('checked-row');
        }

        $.ajax({
            url: wpCoreUpdate.ajax_url,
            method: 'POST',
            data: {
                action: 'save_excluded_plugin',
                security: wpCoreUpdate.exclude_nonce,
                plugin: plugin,
                exclude: exclude,
            },
            success: function (response) {
                if (response.success) {
                    displayMessage('success', response.data.message || 'Exclusion settings updated successfully!');
                } else {
                    checkbox.prop('checked', !exclude); // Revert checkbox state on error
                    displayMessage('error', response.data.message || 'Failed to update exclusion settings.');
                }
            },
            error: function (xhr, status, error) {
                checkbox.prop('checked', !exclude); // Revert checkbox state on error
                displayMessage('error', `An error occurred: ${error}`);
            },
        });
    });
});
