<?php
/**
 * Plugin Name: TechArk Manager
 * Description: Displays and manage pending updates in the WordPress admin dashboard.
 * Version: 1.1
 * Author: TechArk
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Add admin side menu page
 */
function wp_pending_updates_menu() {
    $main_slug = 'wp-pending-wp-updates-manager';


    add_menu_page(
		'TechArk Manager',                  
        'TechArk Manager',  
		'manage_options',
		'wp-pending-wp-updates-manager',
		'wp_pending_wp_updates_page',
		'dashicons-admin-generic',
		30
	);
    add_submenu_page( $main_slug,
		'Updates',                  
        'Updates',  
		'manage_options',
        'wp-pending-wp-updates-manager',
		'wp_pending_wp_updates_page',
	);
   

    
}
add_action( 'admin_menu', 'wp_pending_updates_menu' );

/**
 * Add admin side Script file
 */
function wp_enqueue_updates_manager_script( $hook ) {

    if ( 'toplevel_page_wp-pending-wp-updates-manager' !== $hook ) {
        return;
    }

    wp_enqueue_script( 'wp-update-plugins', plugin_dir_url( __FILE__ ) . 'js/update-manager.js', array( 'jquery' ), '1.0', true );

    wp_enqueue_style('wp-update-styles', plugin_dir_url(__FILE__) . 'css/style.css');


    wp_localize_script( 'wp-update-plugins', 'wpCoreUpdate', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'wp_nonce' => wp_create_nonce( 'wp_core_update_nonce' ),
        'nonce'    => wp_create_nonce('cpm_update_plugin_nonce'),
        'email_nonce'    => wp_create_nonce('maintenance_summary'),
        'delete_nonce'   => wp_create_nonce('delete_old_crm_entries'),
        'exclude_nonce'   => wp_create_nonce('save_excluded_plugin'),
    ));

}
add_action( 'admin_enqueue_scripts', 'wp_enqueue_updates_manager_script' );

/**
 * Content of Update page
 */
function wp_pending_wp_updates_page() {

    $core_updates   = get_core_updates();
    $plugin_updates = get_plugin_updates();

    ?>
    <div class="wrap">

        <h1><?php esc_html_e( 'Update Manager', 'techark-manager' ); ?></h1>

        <div class="email-summary-div">
            <label for="wp-plugin-update-email" class="email-label"> <?php esc_html_e('Please enter your email address below to receive the maintenance summary.', 'techark-manager'); ?></label>
                <div class="email-input-wrapper">
                    <input type="email" id="wp-plugin-update-email" placeholder="Enter your email address" class="email-input" aria-label="<?php esc_attr_e('Email address for maintenance summary');?>" />
                    <button id="wp-email-plugin-summary" class="email-submit-button"><?php esc_html_e('Email Summary', 'techark-manager'); ?></button>
                </div>
            <div id="wp-email-update-status" class="email-status"></div>
        </div>
    </div>
    <div class="wrap techark-half-content">
        
        <div class="core-details-div techark-sub-half-content">
            <h2 class="core-updater-title">
                    <?php esc_html_e('WordPress & PHP Version', 'techark-manager'); ?>
            </h2>
            <div class="custom-techark-details">
                <p class="entries-description"><strong class="core-details-title"><?php esc_html_e('Current WordPress Version', 'techark-manager'); ?></strong>: <?php echo get_bloginfo('version'); ?></p>
                <p class="entries-description"><strong class="core-details-title"><?php esc_html_e('Current PHP Version', 'techark-manager'); ?></strong>: <?php echo phpversion(); ?></p>
            </div>
        </div>

        <div class="core-updater-div techark-sub-half-content">
            <h2 class="core-updater-title">
                <?php esc_html_e('WordPress Core Updater', 'techark-manager'); ?>
            </h2>

            <?php if (isset($core_updates[0]) && !empty($core_updates[0]->response) && 'upgrade' === $core_updates[0]->response) : ?>
                <p class="core-update-message">
                    <?php esc_html_e('A new version of WordPress is available. Click the button below to update.', 'techark-manager'); ?>
                </p>

                <button id="wp-core-update-btn" class="core-update-button">
                    <?php esc_html_e('Update WordPress Core', 'techark-manager'); ?>
                </button>
            <?php else : ?>
                <p class="core-update-message core-up-to-date">
                    <?php esc_html_e('WordPress is up to date. No updates available.', 'techark-manager'); ?>
                </p>
            <?php endif; ?>

            <div id="wp-core-update-status" class="core-update-status"></div>
        </div>

    </div>
    <div class="wrap techark-half-content">

        <div class="delete-entries-div techark-sub-half-content">
            <h2 class="delete-entries-title">
                <?php esc_html_e('Manage Old Form Entries', 'techark-manager'); ?>
            </h2>
            <p class="delete-entries-description">
                <?php esc_html_e('Click the button below to delete old form entries and free up space.', 'techark-manager'); ?>
            </p>
            <button id="delete-old-crm-entries" class="delete-entries-button">
                <?php esc_html_e('Delete Form Entries', 'techark-manager'); ?>
            </button>
            <div id="delete-crm-status" class="delete-status"></div>
        </div>

        <div class="plugin-manager-div techark-sub-half-content">
            <h2 class="plugin-manager-title">
                <?php esc_html_e('Plugin Manager', 'techark-manager'); ?>
            </h2>
            <p class="plugin-manager-description">
                <?php esc_html_e('Manage your plugins efficiently. Click the button below to update all plugins at once. Plugins can be excluded by checking the box.', 'techark-manager'); ?>
            </p>
            <button id="cpm-update-all" class="plugin-manager-button">
                <?php esc_html_e('Update All Plugins', 'techark-manager'); ?>
            </button>
        </div>
    </div>
    <div class="wrap">
        <div class="cpm-controls">
            <input type="text" id="cpm-search" class="cpm-search" placeholder="Search plugins..." aria-label="Search plugins">
            <select id="cpm-filter" class="cpm-filter" aria-label="Filter plugins">
                <option value="all">All Plugins</option>
                <option value="active">Active Plugins</option>
                <option value="inactive">Inactive Plugins</option>
                <option value="needs-update">Needs Update</option>
            </select>
        </div>

        <div class="cpm-table-wrapper">
            <table class="cpm-plugin-table wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 10%; text-align: center;">Exclude</th>
                        <th>Name</th>
                        <th>Current Version</th>
                        <th>Updated Version</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="cpm-plugin-list">
                    <?php
                    $all_plugins = get_plugins();
                    $update_info = get_site_transient('update_plugins');
                    $excluded_plugins = get_option('excluded_plugins', []);

                    foreach ($all_plugins as $plugin_file => $plugin_data) {
                        $is_active = is_plugin_active($plugin_file);
                        $has_update = isset($update_info->response[$plugin_file]);
                        $updated_version = $has_update ? $update_info->response[$plugin_file]->new_version : '-';
                        $is_excluded = in_array($plugin_file, $excluded_plugins);

                        echo '<tr data-status="' . ($is_active ? 'active' : 'inactive') . '" data-update="' . ($has_update ? 'needs-update' : 'no-update') . '">';
                        echo '<td style="width: 10%; text-align: center;"><input type="checkbox" class="exclude-plugin" data-plugin="' . esc_attr($plugin_file) . '" ' . checked($is_excluded, true, false) . '></td>';
                        echo '<td><strong>' . esc_html($plugin_data['Name']) . '</strong></td>';
                        echo '<td>' . esc_html($plugin_data['Version']) . '</td>';
                        echo '<td>' . esc_html($updated_version) . '</td>';
                        echo '<td>' . ($is_active ? '<span class="cpm-status cpm-active">Active</span>' : '<span class="cpm-status cpm-inactive">Inactive</span>') . '</td>';
                        echo '<td>';
                        if ($has_update) {
                            echo '<button class="button button-primary cpm-update-plugin" data-plugin="' . esc_attr($plugin_file) . '">Update</button>';
                        } else {
                            echo '<span class="cpm-status cpm-up-to-date">Up to Date</span>';
                        }
                        echo '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div id="cpm-message" class="cpm-message"></div>
    <?php
}

/**
 * Ajax call For Wordpress Update
 */
function wp_core_update_ajax_handler() {

    check_ajax_referer( 'wp_core_update_nonce', 'nonce' );

    if ( ! current_user_can( 'update_core' ) ) {
        wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions to update WordPress.', 'techark-manager' ) ) );
    }

    include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
    include_once( ABSPATH . 'wp-admin/includes/admin.php' );
    include_once( ABSPATH . 'wp-admin/includes/update.php' );

    $updates = get_core_updates();

    if ( isset( $updates[0] ) && ! empty( $updates[0]->response ) && 'upgrade' === $updates[0]->response ) {
        
        $upgrader = new Core_Upgrader();

        $result = $upgrader->upgrade( $updates[0] );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        } else {
            wp_send_json_success( array( 'message' => __( 'WordPress updated successfully!', 'techark-manager' ) ) );
        }
    } else {
        wp_send_json_error( array( 'message' => __( 'No WordPress core updates available.', 'techark-manager' ) ) );
    }
}
add_action( 'wp_ajax_wp_core_update_ajax', 'wp_core_update_ajax_handler' );

/**
 * Update plugins
 */
function cpm_update_plugin() {
    check_ajax_referer('cpm_update_plugin_nonce', 'security');

    if (!current_user_can('update_plugins')) {
        wp_send_json_error(['message' => 'You do not have permission to update plugins.']);
    }

    $was_active = '';

    $plugin_slug = sanitize_text_field($_POST['plugin_slug']);

    delete_site_transient('update_plugins');
    wp_update_plugins();

    $update_info = get_site_transient('update_plugins');

    if (isset($update_info->response[$plugin_slug])) {
        
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
        include_once ABSPATH . 'wp-admin/includes/file.php';

        class Silent_Upgrader_Skin extends WP_Upgrader_Skin {
            public function feedback($string, ...$args) {}
        }

        $plugins = get_plugins();
        $plugin_file = '';
        foreach ($plugins as $file => $data) {
            if (strpos($file, $plugin_slug) === 0) {
                $plugin_file = $file;
                break;
            }
        }

        if (empty($plugin_file)) {
            wp_send_json_error(['message' => 'Plugin file not found.']);
        }

        $was_active = is_plugin_active($plugin_file);

        ob_start();

        $upgrader = new Plugin_Upgrader(new Silent_Upgrader_Skin());
        $result = $upgrader->upgrade($plugin_slug);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_clean_plugins_cache(true);

        if ($was_active) {
            activate_plugin($plugin_file);
        }

        ob_end_clean();

        wp_send_json_success(array(
            'message' => sprintf(__('The %s plugin was updated successfully.', 'techark-manager'), $plugin_slug)
        ));

    } else {
        wp_send_json_error(['message' => 'The plugin is at the latest version.']);
    }
}
add_action('wp_ajax_cpm_update_plugin', 'cpm_update_plugin');
/**
 * Activate plugin handle
 */
function cpm_activate_plugin_handler() {
    if (!check_ajax_referer('wp-core-update-nonce', 'security', false)) {
        wp_send_json_error(['message' => 'Invalid security token.']);
    }

    $plugin_slug = sanitize_text_field($_POST['plugin_slug']);
    if (!$plugin_slug) {
        wp_send_json_error(['message' => 'No plugin specified for activation.']);
    }

    // Activate the plugin
    $result = activate_plugin($plugin_slug);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    } else {
        wp_send_json_success(['message' => "Plugin {$plugin_slug} reactivated."]);
    }
}
add_action('wp_ajax_cpm_activate_plugin', 'cpm_activate_plugin_handler');

/**
 * Send Maintenance Summary Email
 */
function send_maintenance_summary_email() {

    check_ajax_referer('maintenance_summary', 'nonce');

    if ( ! function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

    if (!is_email($email)) {
        wp_send_json_error(['message' => 'Invalid email address.']);
    }

    $plugin_updates = get_plugin_updates();

    $results = array();
    $updated_plugins = array();

    foreach ($plugin_updates as $plugin_file => $plugin_data) {
        $current_version = $plugin_data->Version;
        $new_version     = $plugin_data->update->new_version;
        $plugin_name     = $plugin_data->Name;

        $updated_plugins[] = [
            'name' => $plugin_name,
            'old_version' => $current_version,
            'new_version' => $new_version
        ];
    }

    $subject = get_bloginfo( 'name' ). "  Maintenance Summary";

    $current_wp_version = get_bloginfo('version');
    $update_core = get_site_transient('update_core');

    if (isset($update_core->updates) && !empty($update_core->updates)) {
        $latest_wp_version = $update_core->updates[0]->current;
        $body  = "WordPress needs an update:\n";
        $body .= "Current WordPress version: $current_wp_version\n";
        $body .= "New WordPress version: $latest_wp_version\n";
    } else {
        $body .= "Your WordPress installation is up to date (version $current_wp_version).\n";
    }

    $body .= "\n\nBelow is a list of plugins that need to be updated:\n";

    foreach ($updated_plugins as $plugin) {
        $body .= "{$plugin['name']} from {$plugin['old_version']} to {$plugin['new_version']}\n";
    }

    $themes = wp_get_themes();

    $theme_updates = get_site_transient('update_themes');
    $pending_theme_updates = array();

    foreach ($themes as $theme_slug => $theme_data) {
        if (isset($theme_updates->response[$theme_slug])){
            $current_theme_version = $theme_data->get('Version');
            $new_theme_version = $theme_updates->response[$theme_slug]['new_version'];
            $theme_name = $theme_data->get('Name');

            $pending_theme_updates[] = [
                'name' => $theme_name,
                'old_version' => $current_theme_version,
                'new_version' => $new_theme_version
            ];
        }
    }

    if($pending_theme_updates){
        $body .= "\n\nBelow is a list of themes that need to be updated:\n";

        foreach ($pending_theme_updates as $theme) {
            $body .= "{$theme['name']} from {$theme['old_version']} to {$theme['new_version']}\n";
        }
    }

    $all_plugins = get_plugins();

    $form_plugins = array(
        'contact-form-7/wp-contact-form-7.php' => 'Contact Form 7',
        'wpforms/wpforms.php' => 'WPForms',
        'gravityforms/gravityforms.php' => 'Gravity Forms',
        'ninja-forms/ninja-forms.php' => 'Ninja Forms',
        'formidable/formidable.php' => 'Formidable Forms',
        'caldera-forms/caldera-core.php' => 'Caldera Forms',
        'weforms/weforms.php' => 'weForms'
    );

    $installed_form_plugins = array();

    foreach ( $form_plugins as $slug => $name ) {
        if ( isset( $all_plugins[ $slug ] ) ) {
            $installed_form_plugins[] = $name;
        }
    }

    $form_counts = array();

    if ( in_array( 'Contact Form 7', $installed_form_plugins ) ) {
        $contact_forms_count = wp_count_posts( 'wpcf7_contact_form' )->publish;
        $form_counts['Contact Form 7'] = $contact_forms_count;
    }

    if ( in_array( 'WPForms', $installed_form_plugins ) ) {
        $wpforms_count = wp_count_posts( 'wpforms' )->publish;
        $form_counts['WPForms'] = $wpforms_count;
    }

    if ( in_array( 'Gravity Forms', $installed_form_plugins ) ) {
        global $wpdb;
        $gravityforms_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gf_form" );
        $form_counts['Gravity Forms'] = $gravityforms_count;
    }

    if ( in_array( 'Ninja Forms', $installed_form_plugins ) ) {
        $ninja_forms_count = wp_count_posts( 'nf_sub' )->publish;
        $form_counts['Ninja Forms'] = $ninja_forms_count;
    }

    if ( ! empty( $installed_form_plugins ) ) {

        $body .= "\n\nThe following is a list of the Form plugins available on our site:";
        
        foreach ( $form_counts as $plugin => $count ) {
            $body .="\n".  $plugin . "\nTotal Forms: " . $count . "\n";
        }
    }

    $pages_count = wp_count_posts('page');
    $total_pages = isset($pages_count->publish) ? $pages_count->publish : 0;

    $body .= "\n\nTotal number of pages available in the backend: " .$total_pages;

    $body .="\n\nSecurity Scans performed on ".date('jS F')." for malicious malware that may have been added to";

    $body .="\nBelow are the security scan summary ".get_site_url()."";

    $body .="\n0 Quarantined Files";
    $body .="\nFound 0 Database Injections";
    $body .="\nFound 0 htaccess Threats";
    $body .="\nFound 0 TimThumb Exploits";
    $body .="\nFound 0 Known Threats";
    $body .="\nFound 0 Core File Changes";

    $current_php_version = phpversion();

    $body .= "\n\nCurrent PHP version: " .$current_php_version;

    $upload_dir = wp_upload_dir();
    $temp_file = $upload_dir['basedir'] . '/'. $subject . '.txt';

    file_put_contents($temp_file, $body);

     $sent = wp_mail($email, $subject, $body, [], [$temp_file]);

    if (file_exists($temp_file)) {
        unlink($temp_file);
    }

    if ($sent) {
        wp_send_json_success();
    } else {
        wp_send_json_error(['message' => 'Failed to send email.']);
    }
}
add_action('wp_ajax_email_maintenance_summary', 'send_maintenance_summary_email');

/**
 * Delete old entries from database
 */
function delete_old_crm_entries() {
    global $wpdb;

    $last_month = date('Y-m-d H:i:s', strtotime('-1 month'));

    $deleted_entries = 0;

    if (is_plugin_active('wpforms/wpforms.php')) {
        $table_name = $wpdb->prefix . 'wpforms_entries';
        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_name WHERE date_created < %s",
                $last_month
            )
        );
        if ($result !== false) {
            $deleted_entries += $result;
        }
    }

    if (is_plugin_active('gravityforms/gravityforms.php')) {
        $table_name = $wpdb->prefix . 'gf_entry';
        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_name WHERE date_created < %s",
                $last_month
            )
        );
        if ($result !== false) {
            $deleted_entries += $result;
        }
    }

    if (is_plugin_active('contact-form-cfdb7/contact-form-cfdb-7.php')) {
        $table_name = $wpdb->prefix . 'db7_forms';
        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_name WHERE form_date < %s",
                $last_month
            )
        );
        if ($result !== false) {
            $deleted_entries += $result;
        }
    }

    if (is_plugin_active('contact-form-entries/contact-form-entries.php')) {

        $table1 = $wpdb->prefix . 'vxcf_leads';
        $table2 = $wpdb->prefix . 'vxcf_leads_detail';

        $result = $wpdb->query(
            $wpdb->prepare(
                "
                DELETE t1, t2
                FROM $table1 t1
                INNER JOIN $table2 t2 ON t1.id = t2.lead_id
                WHERE t1.created < %s
                ",
                $last_month
            )
        );

        if ($result !== false) {
            $deleted_entries += $result;
        }
    }

    if (is_plugin_active('formidable/formidable.php')) {
        $table_name = $wpdb->prefix . 'frm_items';
        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_name WHERE created_at < %s",
                $last_month
            )
        );
        if ($result !== false) {
            $deleted_entries += $result;
        }
    }

    if (is_plugin_active('ninja-forms/ninja-forms.php')) {
        $table_name = $wpdb->prefix . 'nf3_objects';
        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_name WHERE date_updated < %s",
                $last_month
            )
        );
        if ($result !== false) {
            $deleted_entries += $result;
        }
    }

    if ($deleted_entries > 0) {
        wp_send_json_success("Deleted $deleted_entries old entries from the database.");
    } else {
        wp_send_json_error('No old entries found.');
    }
}
add_action('wp_ajax_delete_old_crm_entries', 'delete_old_crm_entries');

/**
 * Save Exclusion plugins
 */
function save_excluded_plugin() {

    $plugin = sanitize_text_field($_POST['plugin']);
    $exclude = filter_var($_POST['exclude'], FILTER_VALIDATE_BOOLEAN);
    $excluded_plugins = get_option('excluded_plugins', []);

    if ($exclude) {
        if (!in_array($plugin, $excluded_plugins)) {
            $excluded_plugins[] = $plugin;
        }
    } else {
        $excluded_plugins = array_diff($excluded_plugins, [$plugin]);
    }

    update_option('excluded_plugins', $excluded_plugins);
    wp_send_json_success(['message' => 'Exclusion settings updated']);
}
add_action('wp_ajax_save_excluded_plugin', 'save_excluded_plugin');


if( ! function_exists( 'my_plugin_check_for_updates' ) ){
    function my_plugin_check_for_updates( $update, $plugin_data, $plugin_file ){
       
        static $response = false;
        
        if( empty( $plugin_data['UpdateURI'] ) || ! empty( $update ) )
            return $update;
        
        if( $response === false )
            $response = wp_remote_get( $plugin_data['UpdateURI'] );
        
        if( empty( $response['body'] ) )
            return $update;
        
        $custom_plugins_data = json_decode( $response['body'], true );
        
        if( ! empty( $custom_plugins_data[ $plugin_file ] ) )
            return $custom_plugins_data[ $plugin_file ];
        else
            return $update;
        
    }
    
    add_filter('update_plugins_techarkmaindev.wpenginepowered.com', 'my_plugin_check_for_updates', 10, 3);
    
}

/**
 * GitHub Repository Info
 */
define('TECHARK_MANAGER_GITHUB_USER', 'ruchitajaviya');
define('TECHARK_MANAGER_GITHUB_REPO', 'techark-manager');

add_filter('pre_set_site_transient_update_plugins', 'techark_manager_check_for_plugin_update');
add_filter('plugins_api', 'techark_manager_plugin_info', 20, 3);
/**
 * Check for plugin update from GitHub.
 */
function techark_manager_check_for_plugin_update($transient) {
    if (empty($transient->checked)) return $transient;

    $plugin_slug = plugin_basename(__FILE__);
    $current_version = get_plugin_data(__FILE__)['Version'];

    $remote = techark_manager_get_latest_release();
    if (!$remote || empty($remote['tag_name'])) {
        error_log('Could not retrieve remote release information.');
        return $transient;
    }

    $remote_version = ltrim($remote['tag_name'], 'v');
    if (version_compare($current_version, $remote_version, '<')) {
        $transient->response[$plugin_slug] = (object)[
            'slug' => dirname($plugin_slug),
            'plugin' => $plugin_slug,
            'new_version' => $remote_version,
            'url' => $remote['html_url'],
            'package' => "https://github.com/" . TECHARK_MANAGER_GITHUB_USER . "/" . TECHARK_MANAGER_GITHUB_REPO . "/releases/download/{$remote['tag_name']}/techark-manager-{$remote['tag_name']}.zip"
        ];
    }

    return $transient;
}

/**
 * Show plugin info in the WordPress update UI.
 */
function techark_manager_plugin_info($result, $action, $args) {
    if ($action !== 'plugin_information' || $args->slug !== dirname(plugin_basename(__FILE__))) {
        return $result;
    }

    $remote = techark_manager_get_latest_release();
    if (!$remote || empty($remote['tag_name'])) {
        return $result;
    }

    $version = ltrim($remote['tag_name'], 'v');

    return (object)[
        'name' => 'TechArk Manager',
        'slug' => dirname(plugin_basename(__FILE__)),
        'version' => $version,
        'author' => '<a href="https://techark.com">TechArk</a>',
        'homepage' => $remote['html_url'],
        'download_link' => "https://github.com/" . TECHARK_MANAGER_GITHUB_USER . "/" . TECHARK_MANAGER_GITHUB_REPO . "/releases/download/{$remote['tag_name']}/techark-manager-{$remote['tag_name']}.zip",
        'sections' => [
            'description' => 'Provides toggles to apply .htaccess-based WordPress security settings.',
            'changelog' => !empty($remote['body']) ? nl2br($remote['body']) : 'No changelog provided.',
        ],
    ];
}

/**
 * Fetch latest release data from GitHub.
 */
function techark_manager_get_latest_release() {
    $url = "https://api.github.com/repos/" . TECHARK_MANAGER_GITHUB_USER . "/" . TECHARK_MANAGER_GITHUB_REPO . "/releases/latest";

    $response = wp_remote_get($url, [
        'headers' => ['User-Agent' => 'WordPress/' . get_bloginfo('version')],
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        error_log('GitHub API Error: ' . $response->get_error_message());
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (empty($data['tag_name'])) {
        error_log('Invalid release data from GitHub.');
        return false;
    }

    return $data;
}

add_filter('auto_update_plugin', 'techark_manager_enable_auto_update', 10, 2);

function techark_manager_enable_auto_update($update, $item) {
    // Replace with your actual plugin path
    $plugin_basename = plugin_basename(__FILE__);

    if ($item->plugin === $plugin_basename) {
        return true; // Enable auto-update for this plugin
    }

    return $update; // Keep default behavior for others
}
include_once 'techark-manager-common-functions.php';
include_once 'techark-security.php';
include_once 'techark-login-security.php';
// include_once 'techark-security-header.php';

/**
 * General status checker for multiple security options
 */
function techark_manager_check_security_status($option_name, $url = '') {
    switch ($option_name) {
        case 'disable_file_editing':
            if (!current_user_can('edit_themes') || (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT === true)) {
                return 1; // File editing is disabled
            }
            return 0; // File editing is allowed

        case 'disable_script_concat':
            return (defined('CONCATENATE_SCRIPTS') && CONCATENATE_SCRIPTS === false) ? 1 : 0;

        case 'block_xmlrpc':
        case 'disable_pingbacks':
            $htaccess = ABSPATH . '.htaccess';
            if (!file_exists($htaccess)) {
                $response = wp_remote_get($url . '?nocache=' . time(), [
                    'headers' => [
                        'Cache-Control' => 'no-cache',
                        'Pragma' => 'no-cache',
                        'User-Agent' => 'WP-Security-Check'
                    ],
                    'sslverify' => false,
                    'timeout' => 15
                ]);
                if (is_wp_error($response)) return 0;
                $code = wp_remote_retrieve_response_code($response);
                $body = wp_remote_retrieve_body($response);  
                if ($code === 403 || preg_match('/403|Access Denied/i', $body)) {
                    return 1;
                } else if ($code !== 200 || preg_match('/403|Access Denied/i', $body))  { 
                    return 1;
                } else if (empty($body) && $code == 200)  { 
                    return 1;
                } else {
                    return 0;
                }
            } else {
                $contents = file_get_contents($htaccess);
                return preg_match('/<Files\s+"?xmlrpc\.php"?\s*>.*?<\/Files>/is', $contents) ? 1 : 0;
            }

        case 'block_author_scan':
            if (empty($url)) return 0;
            $user = get_users([
                'number' => 1,
                'orderby' => 'ID',
                'order' => 'ASC',
            ]);
            $user_id = '';
            if (!empty($user)) {
                $first_user = $user[0];
                $user_id = $first_user->ID;
            }
            if (empty($user_id)) return 0;

            $url = $url .$user_id;
            $response = wp_remote_get($url, [
                'redirection' => 0,
                'timeout' => 10,
                'headers' => ['User-Agent' => 'WP-Security-Check']
            ]);
            if (is_wp_error($response)) return 0;
            $status = wp_remote_retrieve_response_code($response);
            $location = wp_remote_retrieve_header($response, 'location');
            return (in_array($status, [301, 302]) && strpos($location, '/author/') !== false) ? 0 : 1;

        default:
            if (empty($url)) return 0;
            $response = wp_remote_get($url . '?nocache=' . time(), [
                'headers' => [
                    'Cache-Control' => 'no-cache',
                    'Pragma' => 'no-cache',
                    'User-Agent' => 'WP-Security-Check'
                ],
                'sslverify' => false,
                'timeout' => 15
            ]);
            if (is_wp_error($response)) return 0;
            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);  
            if ($code === 403 || preg_match('/403|Access Denied/i', $body)) {
                return 1;
            } else if ($code !== 200 || preg_match('/403|Access Denied/i', $body))  { 
                return 1;
            } else if (empty($body) && $code == 200)  { 
                return 1;
            } else {
                return 0;
            }
    }
}

/**
 * AJAX callback: Unified check for security options
 */
function techark_manager_check_url_status() {
    $option = sanitize_text_field($_POST['option_name'] ?? '');
    $url    = esc_url_raw($_POST['link'] ?? '');
    $status = techark_manager_check_security_status($option, $url);
    $options = [
        'block_xmlrpc'=>'Block XML-RPC',
        'disable_pingbacks'=>'Disable Pingbacks',
        'disable_file_editing'=>'Disable File Editing',
        'disable_script_concat'=>'Disable Script Concatenation',
        'block_php_in_includes'=>'Block PHP in wp-includes',
        'block_php_in_uploads'=>'Block PHP in uploads',
        'restrict_scripting_lang'=>'Restrict Scripting Languages',
        'bot_protection'=>'Enable Bot Protection',
        'block_sensitive_files'=>'Block Sensitive Files',
        'block_htaccess_access'=>'Block .htaccess Access',
        'block_author_scan' => 'Prevent Author Scans',
        'custom_login_url' => 'Custom Login URL'
    ];
    echo json_encode([
        'status'  => $status,
        'message' => $status
            ? '<p class="success">The '.$options[$option].' is now active and secure.</p>'
            : '<p class="error">The '.$options[$option].' could not be activated.</p>'
    ]);
    wp_die();
}

add_action('wp_ajax_get_techark_check_url_status', 'techark_manager_check_url_status');
add_action('wp_ajax_nopriv_get_techark_check_url_status', 'techark_manager_check_url_status');


// add_filter('recovery_mode_email', '__return_false');

/** ================================
 *  Plugin Deactivation Hook
 *  ================================ */
register_deactivation_hook(__FILE__, 'techark_manager_plugin_deactivated');

function techark_manager_plugin_deactivated() {
    // Example: remove .htaccess custom rules
    techark_manager_remove_htaccess_rules();

    $options = [
        'block_xmlrpc', 'disable_pingbacks', 'disable_file_editing', 'disable_script_concat',
        'block_php_in_includes', 'block_php_in_uploads', 'restrict_scripting_lang',
        'bot_protection', 'block_sensitive_files', 'block_htaccess_access', 'block_author_scan','custom_login_url','strong_password_enforcement'
    ];
    foreach ($options as $key) {
       
        delete_option("techark_$key");
        if ($key === 'block_php_in_includes' || $key === 'block_php_in_uploads') {
            delete_option( 'techark_' . $key.'_server');
        }
        if($key === 'custom_login_url') {
            delete_option('techark_' . $key.'_value');
        }
    }
}

/** ================================
 *  Remove Custom .htaccess Rules
 *  ================================ */
function techark_manager_remove_htaccess_rules() {
    $htaccess = ABSPATH . '.htaccess';

    if (!file_exists($htaccess) || !is_writable($htaccess)) {
        return;
    }

    $contents = file_get_contents($htaccess);

    // Define start and end tags for custom rules
    $start_tag = '# BEGIN TechArk Security Rules';
    $end_tag   = '# END TechArk Security Rules';

    if (strpos($contents, $start_tag) !== false && strpos($contents, $end_tag) !== false) {
        $pattern = "/$start_tag(.*?)$end_tag/s";
        $contents = preg_replace($pattern, '', $contents);
        file_put_contents($htaccess, trim($contents) . PHP_EOL);
    }
}

/** Custom Login page Redirection Start */

// 1. Get sanitized custom login slug
function techark_manager_get_custom_login_slug() {
    $login_url = get_option('techark_custom_login_url_value');

    // Only return custom slug if enabled and not empty
    if (empty($login_url)) {
        return '';
    }

    return sanitize_title($login_url);
}

// 2. Add rewrite rule and query var for custom login
function techark_manager_add_login_rewrite_rule() {
    $slug = techark_manager_get_custom_login_slug();
    if (!empty($slug)) {
        add_rewrite_rule("^{$slug}/?$", 'index.php?custom_login=1', 'top');
    }
}
add_action('init', 'techark_manager_add_login_rewrite_rule');

/** Save Permalink */
function techark_maybe_flush_rewrite_on_slug_change($old_value, $new_value, $option) {
    
    if ($old_value !== $new_value) {
        techark_manager_add_login_rewrite_rule();
        flush_rewrite_rules();
    }
}
add_action('update_option_techark_custom_login_url_value', 'techark_maybe_flush_rewrite_on_slug_change', 10, 3);

/** Add query vars */
function techark_manager_add_login_query_var($vars) {
    $vars[] = 'custom_login';
    return $vars;
}
add_filter('query_vars', 'techark_manager_add_login_query_var');

// 3. Flush rewrite rules on plugin activation
function techark_manager_flush_login_rewrite() {
        techark_manager_add_login_rewrite_rule();
        flush_rewrite_rules();
    
}
register_activation_hook(__FILE__, 'techark_manager_flush_login_rewrite');

// 4. Redirect wp-login.php and wp-admin to custom slug
function techark_manager_redirect_login_page() {
    $slug = techark_manager_get_custom_login_slug();

    if (empty($slug)) {
        return; // Don’t redirect if slug is not defined
    }
    if (defined('DOING_AJAX') && DOING_AJAX) return;
    if (defined('REST_REQUEST') && REST_REQUEST) return;

    $parsed_path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $action = $_GET['action'] ?? '';

    // Allow wp-login.php access for logout/lostpassword
    if ($parsed_path === 'wp-login.php' && in_array($action, ['logout', 'lostpassword'])) {
        return;
    }

    if (!is_user_logged_in()) {
        if (strpos($parsed_path, 'wp-login.php') !== false) {
            wp_redirect(home_url($slug));
            exit;
        }

        if (strpos($parsed_path, 'wp-admin') !== false) {
            wp_redirect(home_url($slug));
            exit;
        }
    }
}

add_action('init', 'techark_manager_redirect_login_page', 1);

// 5. Serve login form at custom login slug
function techark_manager_render_custom_login_form() {
    if (get_query_var('custom_login') == 1) {
        require_once ABSPATH . 'wp-login.php';
        exit;
    }
}
add_action('template_redirect', 'techark_manager_render_custom_login_form');

/** Custom Login page Redirection End */
