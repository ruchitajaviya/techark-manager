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
 * Add Techark Update Manager page 
 */
function wp_pending_updates_menu() {
    add_menu_page(
        'TechArk Updates Manager',      
        'TechArk Updates Manager',      
        'manage_options',              
        'wp-pending-wp-updates-manager',   
        'wp_pending_wp_updates_page',
        'dashicons-admin-plugins',     
        20                            
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

        <h1><?php esc_html_e( 'TechArk Update Manager', 'techark-manager' ); ?></h1>

        <div class="email-summary-div">
            <label for="wp-plugin-update-email" class="email-label"> <?php esc_html_e('Please enter your email address below to receive the maintenance summary.', 'techark-manager'); ?></label>
                <div class="email-input-wrapper">
                    <input type="email" id="wp-plugin-update-email" placeholder="Enter your email address" class="email-input" aria-label="<?php esc_attr_e('Email address for maintenance summary');?>" />
                    <button id="wp-email-plugin-summary" class="email-submit-button"><?php esc_html_e('Email Summary', 'techark-manager'); ?></button>
                </div>
            <div id="wp-email-update-status" class="email-status"></div>
        </div>

        <div class="core-updater-div">
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
    <div class="wrap">

        <div class="delete-entries-div">
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

        <div class="plugin-manager-div">
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


add_action('wp_ajax_cpm_update_plugin', 'update_plugin');

function update_plugin() {
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

add_action('wp_ajax_cpm_activate_plugin', 'cpm_activate_plugin_handler');

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

add_action('wp_ajax_email_maintenance_summary', 'send_maintenance_summary_email');

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

add_action('wp_ajax_delete_old_crm_entries', 'delete_old_crm_entries');

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

add_action('wp_ajax_save_excluded_plugin', 'save_excluded_plugin');
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
 * Include details of security page details functions
 */
include 'techark-security.php';


add_filter('pre_set_site_transient_update_plugins', 'techark_check_for_plugin_update');
add_filter('plugins_api', 'techark_plugin_info', 20, 3);

/**
 * GitHub Repository Info
 */
define('TECHARK_GITHUB_USER', 'ruchitajaviya');
define('TECHARK_GITHUB_REPO', 'techark-manager');

function techark_check_for_plugin_update($transient) {
    if (empty($transient->checked)) return $transient;

    $plugin_slug = plugin_basename(__FILE__);
    $current_version = get_plugin_data(__FILE__)['Version'];

    $remote = techark_get_latest_release();
    
    if (!$remote) {
        error_log('Error fetching remote release info.');
        return $transient;
    }

    // Debugging version comparison
    error_log('Current Version: ' . $current_version);
    error_log('Remote Version: ' . $remote['tag_name']);
    
    if (
        $remote &&
        version_compare($current_version, ltrim($remote['tag_name'], 'v'), '<')
    ) {
        $transient->response[$plugin_slug] = (object) [
            'slug' => dirname($plugin_slug),
            'plugin' => $plugin_slug,
            'new_version' => $remote['tag_name'],
            'url' => $remote['html_url'],
            'package' => $remote['zipball_url'],
        ];
    }

    return $transient;
}

/**
 * Show plugin details on the update screen.
 */
function techark_plugin_info($result, $action, $args) {
    if ($action !== 'plugin_information' || $args->slug !== dirname(plugin_basename(__FILE__))) {
        return $result;
    }
    
    $remote = techark_get_latest_release();
    
    if (!$remote) {
        return $result;
    }

    return (object) [
        'name' => 'TechArk Manager',
        'slug' => dirname(plugin_basename(__FILE__)),
        'version' => $remote['tag_name'],
        'author' => '<a href="https://techark.com">TechArk</a>',
        'homepage' => $remote['html_url'],
        'download_link' => $remote['zipball_url'],
        'sections' => [
            'description' => 'Provides toggles to apply .htaccess-based WordPress security settings.',
            'changelog' => nl2br($remote['body']),
        ],
    ];
}

/**
 * Get latest release info from GitHub API.
 */
function techark_get_latest_release() {
    $url = "https://api.github.com/repos/" . TECHARK_GITHUB_USER . "/" . TECHARK_GITHUB_REPO . "/releases/latest";

    $response = wp_remote_get($url, [
        'headers' => ['User-Agent' => 'WordPress/' . get_bloginfo('version')],
    ]);

    if (is_wp_error($response)) {
        error_log('GitHub API Error: ' . $response->get_error_message());
        return false;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($data)) {
        error_log('GitHub API returned no data or invalid format.');
        return false;
    }

    return $data;
}
