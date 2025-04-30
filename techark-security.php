<?php

/**
 * Techark Security Page
 */
function techark_security_menu() {
    add_menu_page(
        'TechArk Security', 
        'TechArk Security', 
        'manage_options', 
        'techark-security-settings', 
        'techark_security_settings_page', 
        'dashicons-shield', 
        25
    );
}
add_action('admin_menu', 'techark_security_menu');

// Register Settings
function techark_register_settings() {
    $current_user = wp_get_current_user();

    $options = [
        'block_xmlrpc' => ['label' => 'Block XML-RPC', 'desc' => 'Disables XML-RPC to prevent brute force attacks.', 'check'=> true, 'url'=> 'xmlrpc.php', 'data_name'=>'block_xmlrpc','use_desc'=>'Disables the XML-RPC functionality in WordPress, preventing remote connections and attacks that exploit this feature. This helps to block brute force attacks and unauthorized access attempts.','more_info' => 'https://wordpress.org/support/article/xml-rpc/'],
        'disable_pingbacks' => ['label' => 'Disable Pingbacks', 'desc' => 'Removes pingback functionality to mitigate DDoS risks.', 'check'=> true, 'url'=> 'xmlrpc.php', 'data_name'=>'disable_pingbacks','use_desc'=>'DDisables pingbacks, which are used to notify other websites when a link to them is made from your WordPress site. Disabling this reduces the risk of DDoS attacks and spam.','more_info' => 'https://wordpress.org/support/article/pingbacks-and-trackbacks/'],
        'disable_file_editing' => ['label' => 'Disable File Editing', 'desc' => 'Prevents file changes via the WP admin.', 'check'=> true, 'url'=> '', 'data_name'=>'disable_file_editing','use_desc'=>'PPrevents users from editing theme and plugin files from the WordPress dashboard. This helps reduce security risks, as it prevents attackers from modifying your site’s code through the admin area.','more_info' => 'https://developer.wordpress.org/advanced-administration/security/hardening/#disable-file-editing'],
        'disable_script_concat' => ['label' => 'Disable Script Concatenation', 'desc' => 'Disables script concatenation in the admin for better debugging.', 'check'=> true, 'url'=> '', 'data_name'=>'disable_script_concat','use_desc'=>'Disables script concatenation in WordPress, ensuring that each JavaScript file is loaded individually. This can improve security and troubleshooting by preventing multiple scripts from being bundled together.','more_info' => 'https://developer.wordpress.org/reference/functions/script_concat_settings/'],
        'block_php_in_includes' => ['label' => 'Block PHP in wp-includes', 'desc' => 'Blocks direct access to PHP files in wp-includes.', 'check'=> true, 'url'=> 'wp-includes/pluggable.php', 'data_name'=>'block_php_in_includes','use_desc'=>'Prevents the execution of PHP scripts in the wp-includes directory. This is an important security measure, as it blocks the possibility of exploiting PHP files within this directory for malicious purposes.','more_info' => 'https://developer.wordpress.org/advanced-administration/security/hardening/#securing-wp-includes'],
        'block_php_in_uploads' => ['label' => 'Block PHP in uploads', 'desc' => 'Blocks direct access to PHP files in the uploads directory.', 'check'=> false,'use_desc'=>'Blocks the execution of PHP scripts in the uploads directory. This stops attackers from uploading and running malicious PHP files, which is a common vector for attacks.','more_info' => 'https://www.wpbeginner.com/wordpress-security/#fileexecution'],
        'restrict_scripting_lang' => ['label' => 'Restrict Scripting Languages', 'desc' => 'Restricts .py, .pl, .cgi, .sh, .rb execution.', 'check'=> false,'use_desc'=>'Restricts the use of certain scripting languages (e.g., PHP, JavaScript) on your WordPress site, limiting the potential for malicious code execution and enhancing overall security.','more_info' => 'https://developer.wordpress.org/advanced-administration/security/hardening/#database-security'],
        'bot_protection' => ['label' => 'Enable Bot Protection', 'desc' => 'Blocks bad bots based on user-agent.', 'check'=> false,'use_desc'=>'Enables bot protection features, such as blocking malicious bots or preventing certain automated actions on your site. This protects your site from scraping, spam, and brute-force attacks.','more_info' => 'https://solidwp.com/blog/stop-bad-bots/'],
        'block_sensitive_files' => ['label' => 'Block Sensitive Files', 'desc' => 'Prevents access to wp-config, readme, and license files.', 'check'=> true, 'url'=> 'readme.html', 'data_name'=>'block_sensitive_files', 'note'=>'If the selected option is enabled but the changes are not reflected on your site, please try clearing your server cache or any caching plugin once.','use_desc'=>'Blocks access to sensitive files like .htaccess, wp-config.php, and others that are critical to your WordPress site’s security. This prevents unauthorized users from viewing or modifying these files.','more_info' => 'https://developer.wordpress.org/advanced-administration/security/hardening/#securing-wp-config-php'],
        'block_htaccess_access' => ['label' => 'Block .htaccess Access', 'desc' => 'Prevents access to .htaccess and .htpasswd files.', 'check'=> true, 'url'=> '.htaccess', 'data_name'=>'block_htaccess_access','use_desc'=>'Blocks access to the .htaccess file, which controls server configuration. Preventing unauthorized access ensures that the critical security settings in this file cannot be modified.','more_info' => 'https://www.malcare.com/blog/how-to-restrict-access-to-wordpress-files-using-htaccess/'],
        'block_author_scan' => ['label' => 'Prevent Author Scans', 'desc' => 'Blocks enumeration of author IDs via URL.', 'check'=> true, 'url'=> '?author='.$current_user->ID, 'data_name'=>'block_author_scan','use_desc'=>'Prevents attackers from scanning your WordPress site for author usernames, which is a common tactic used to gain access to accounts via brute force. This helps to secure user data and prevent unauthorized access attempts.','more_info' => 'https://www.malcare.com/blog/wordpress-user-enumeration/']
    ];

    add_settings_section('techark_main_section', '', null, 'techark-security-settings');

    foreach ($options as $key => $data) {
        register_setting('techark_security_group', 'techark_' . $key);
        if ($key === 'block_php_in_includes' || $key === 'block_php_in_uploads') {
            register_setting('techark_security_group', 'techark_' . $key.'_server');
        }

        add_settings_field('techark_' . $key, '', function () use ($key, $data) {
            $value = get_option('techark_' . $key);
            $value_server = get_option('techark_' . $key . '_server') ? : 'other';
            ?>
            <div class="techark-setting-item" style="margin-bottom: 0; padding: 15px;">
                <div class="techark-setting-item-label"> 
                    <?php echo esc_html($data['label']); ?>
                    <?php if (!empty($data['check'])): ?>
                    <div class="check-wrap">
                        <a href="javascript:void(0)" class="check-url-response button button-secondary"
                           data-check-link="<?php echo esc_url(site_url($data['url'])); ?>"
                           data-option-name="<?php echo esc_attr($data['data_name']); ?>">
                           <?php esc_html_e('Verify Option', 'techark-manager'); ?>
                        </a>
                    </div>
                <?php endif; ?>
                </div>
                <div class="techark-setting-item-checkbox">
                    <div class="checkbox-label">
                        <input type="checkbox" name="techark_<?php echo esc_attr($key); ?>" id="techark_<?php echo esc_attr($key); ?>" value="1" <?php checked(1, $value); ?>>
                    </div>
                    <div class="techark-setting-item-notes">
                        <label for="techark_<?php echo esc_attr($key); ?>" class="checkbox-label-item"><strong><?php echo esc_html($data['desc']); ?></strong></label>

                        <?php if ($key === 'block_php_in_includes' || $key === 'block_php_in_uploads'): ?>
                            <div class="server-select" style="margin-bottom: 10px;margin-top: 10px;">
                                <label style="margin-right: 10px;">
                                    <input type="radio" name="techark_<?php echo esc_attr($key); ?>_server" value="other" <?php checked('other', $value_server); ?>>
                                    Other
                                </label>
                                <label>
                                    <input type="radio" name="techark_<?php echo esc_attr($key); ?>_server" value="wpengine" <?php checked('wpengine', $value_server); ?>>
                                    WP Engine
                                </label>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($data['use_desc'])): ?>
                            <div class="use-desc-wrap" style="margin-top: 0;">
                                <?php echo esc_html($data['use_desc']); ?>
                                <?php if(!empty($data['more_info'])) { ?> <a href="<?php echo $data['more_info'] ?>" target="_target" class="more-info">[More info]</a><?php } ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($data['note'])): ?>
                            <div class="note-wrap" style="margin-top: 10px; color: #d63638;">
                                <strong><?php esc_html_e('Note', 'techark-manager'); ?>:</strong> <?php echo esc_html($data['note']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="response-msg" style="display:none"></div>
                    </div>
                </div>
                

                
            </div>
            <?php
        }, 'techark-security-settings', 'techark_main_section');
    }
    register_setting('techark_security_group', 'techark_security_option');
}

add_action('admin_init', 'techark_register_settings');

/**
 * Add Custom Admin Script Fi;e
 */
function techark_security_custom_admin_script($hook) {
    // Example: Load only on post edit screen
    if ($hook !== 'toplevel_page_techark-security-settings') {
        return;
    }
    wp_enqueue_style('wp-update-styles', plugin_dir_url(__FILE__) . 'css/techark-security.css');

    // Register and enqueue your script
    wp_enqueue_script('techark-security-admin-js',plugin_dir_url(__FILE__) . 'js/techark-security-admin-script.js',array('jquery'),true );
    wp_localize_script('techark-security-admin-js', 'techarkData', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('techark_nonce'),
    ));
}
add_action('admin_enqueue_scripts', 'techark_security_custom_admin_script');

// Settings Page with Collapsible Sections and Logs
function techark_security_settings_page() {
    $last_updated = get_option('techark_last_updated');
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.techark-section h2').forEach(header => {
                header.addEventListener('click', () => {
                    header.parentElement.classList.toggle('open');
                });
            });
        });
    </script>
    <div class="wrap">
        <form method="post" action="options.php">
            <div class="techark-security-h1-wrap">
                <h3 class="techark-security-h1"><span class="dashicons dashicons-shield"></span> TechArk Security Settings</h3>
                <input type="submit" name="submit" id="submit" class="button button-primary custom-submit-button" value="Save Changes">
            </div>
            <h1 class="techark-security-h1" style="display:none;"><span class="dashicons dashicons-shield"></span> TechArk Security Settings</h1>
            <?php 
            settings_fields('techark_security_group'); 
            $last_updated_text = '';
            if ($last_updated) {
                $last_updated_text = "<span class='techark-log-time'><strong>Last Updated:</strong> ".esc_html($last_updated)."</span>";
            } ?>

            <div class="techark-section open">
                <div class="techark-section-wrap">
                    <div class="techark-left-section">
                        <h2><span class="dashicons dashicons-admin-tools"></span> Security Options<?php echo $last_updated_text; ?></h2>
                    </div>
                    <div class="techark-right-section">
                        
                    </div>
                </div>
                <div class="techark-content">
                    <?php do_settings_sections('techark-security-settings'); ?>
                </div>
            </div>
        </form>        
    </div>
    <?php
}
/**
 * Admin notice for setting page
 */
function techark_security_admin_notice() {
    if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
        add_settings_error('techark_security_group', 'techark_security_message', 'Settings saved successfully!', 'updated');
    }
    settings_errors('techark_security_group');

}
add_action('admin_notices', 'techark_security_admin_notice');

// Apply Settings
function techark_apply_security_features() {
    $rules = [];
    $options = [
        'block_xmlrpc', 'disable_pingbacks', 'disable_file_editing', 'disable_script_concat',
        'block_php_in_includes', 'block_php_in_uploads', 'restrict_scripting_lang',
        'bot_protection', 'block_sensitive_files', 'block_htaccess_access', 'block_author_scan'
    ];

    $htaccess_patterns = [
        'block_xmlrpc' => '/<Files\s+xmlrpc\.php>.*?<\/Files>\s*/is',
        'disable_pingbacks' => '/<Files\s+xmlrpc\.php>.*?<\/Files>\s*/is',
        'block_php_in_uploads_wpengine' => '/<Directory\s+\/wp-content\/uploads\/>.*?<FilesMatch\s+"\\\\.php\$">.*?<\/FilesMatch>.*?<\/Directory>\s*/is',
        'block_php_in_uploads_other' => '/<IfModule mod_rewrite\.c>.*?uploads.*?\.php.*?<\/IfModule>/is',
        'block_php_in_includes_other' => '/<IfModule mod_rewrite\.c>.*?wp-includes.*?\.php.*?<\/IfModule>/is',
        'restrict_scripting_lang' => '/<FilesMatch\s+"\\\\\.\(py\|pl\|cgi\|sh\|rb\)\$">.*?<\/FilesMatch>/is',
        'bot_protection' => '/RewriteEngine\s+On.*?RewriteCond.*?HTTP_USER_AGENT.*?RewriteRule.*?\[F,L\]/is',
        'block_sensitive_files' => '/<FilesMatch\s+"?\(wp-config\.php\|readme\.html\|license\.txt\)"?>.*?<\/FilesMatch>\s*/is',
        'block_htaccess_access' => '/<Files\s+~\s+"\^.*?\\.\\(\[Hh\]\[Tt\]\[Aa\]\\)">.*?<\/Files>/is',
        'block_author_scan' => '/RewriteCond\s+\%\{QUERY_STRING\}\s+author=\\\d\s+RewriteRule\s+\^\s+-\s+\[F\]\s*/i'
    ];

    foreach ($options as $key) {
        if (!get_option("techark_$key")) continue;

        switch ($key) {
            case 'block_xmlrpc':
                add_filter('xmlrpc_enabled', '__return_false');
                $rules[] = '<Files xmlrpc.php>
    Order Deny,Allow
    Deny from all
</Files>';
                break;

            case 'disable_pingbacks':
                $rules[] = '<Files xmlrpc.php>
    Order Deny,Allow
    Deny from all
</Files>';
                add_filter('xmlrpc_methods', fn($methods) => array_diff_key($methods, ['pingback.ping' => '']));
                add_filter('wp_headers', fn($headers) => array_diff_key($headers, ['X-Pingback' => '']));
                break;

            case 'disable_file_editing':
                defined('DISALLOW_FILE_EDIT') || define('DISALLOW_FILE_EDIT', true);
                defined('DISALLOW_FILE_MODS') || define('DISALLOW_FILE_MODS', true);
                break;

            case 'disable_script_concat':
                defined('CONCATENATE_SCRIPTS') || define('CONCATENATE_SCRIPTS', false);
                break;

            case 'block_php_in_includes':
                $dir = '/wp-includes/';
                $server = get_option("techark_{$key}_server");

                $rules[] = $server === 'other'
                    ? "<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} ^.*{$dir}.*\.php$ [NC]
    RewriteRule .* - [F,L]
</IfModule>"
                    : "";
                break;

            case 'block_php_in_uploads':
                $dir = '/wp-content/uploads/';
                $server = get_option("techark_{$key}_server");

                $rules[] = $server === 'other'
                    ? "<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} ^.*{$dir}.*\.php$ [NC]
    RewriteRule .* - [F,L]
</IfModule>"
                    : "<Directory {$dir}>
<FilesMatch \"\\.php$\">
    Order Allow,Deny
    Deny from all
</FilesMatch>
</Directory>";
                break;

            case 'restrict_scripting_lang':
                $rules[] = '<FilesMatch "\.(py|pl|cgi|sh|rb)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>';
                break;

            case 'bot_protection':
                $rules[] = 'RewriteEngine On
RewriteCond %{HTTP_USER_AGENT} (evilbot|badbot|spambot) [NC]
RewriteRule .* - [F,L]';
                break;

            case 'block_sensitive_files':
                $rules[] = '<FilesMatch "(wp-config.php|readme.html|license.txt)">
    Order Allow,Deny
    Deny from all
</FilesMatch>';
                break;

            case 'block_htaccess_access':
                $rules[] = '<Files ~ "^.*\.([Hh][Tt][Aa])">
    Order Allow,Deny
    Deny from all
    Satisfy All
</Files>';
                break;

            case 'block_author_scan':
                $rules[] = 'RewriteCond %{QUERY_STRING} author=\d
RewriteRule ^ - [F]';
                break;
        }
    }

    // Write .htaccess rules
    if (!empty($rules)) {
        $htaccess_path = ABSPATH . '.htaccess';
        if (file_exists($htaccess_path) && is_writable($htaccess_path)) {
            if (!function_exists('insert_with_markers')) {
                require_once ABSPATH . 'wp-admin/includes/misc.php';
            }
            insert_with_markers($htaccess_path, 'TechArk Security Rules', $rules);
        }
    }

    // Remove rules when unchecked
    foreach ($options as $key) {
        if (get_option("techark_$key")) continue;

        $server = get_option("techark_{$key}_server");

        switch ($key) {
            case 'block_php_in_uploads':
                $pattern_key = "{$key}_" . ($server === 'wpengine' ? 'wpengine' : 'other');
                remove_htaccess_rules($htaccess_patterns[$pattern_key]);
                break;
            case 'block_php_in_includes':
                $pattern_key = "{$key}_" . ($server === 'wpengine' ? 'wpengine' : 'other');

                if($server == 'other') {
                    remove_htaccess_rules($htaccess_patterns[$pattern_key]);
                }
                break;
            case 'restrict_scripting_lang':
                remove_htaccess_rules($htaccess_patterns[$key]);
                define('ALLOW_UNFILTERED_UPLOADS', true);
                add_filter('upload_mimes', 'allow_script_file_uploads');
                break;

            case 'bot_protection':
            case 'block_sensitive_files':
            case 'block_htaccess_access':
            case 'block_author_scan':
                remove_htaccess_rules($htaccess_patterns[$key]);
                break;

            case 'block_xmlrpc':
            case 'disable_pingbacks':
                if (!get_option('techark_block_xmlrpc') && !get_option('techark_disable_pingbacks')) {
                    remove_htaccess_rules($htaccess_patterns['block_xmlrpc']);
                }
                break;
        }
    }

    update_option('techark_last_updated', current_time('mysql'));
}

add_action('init', 'techark_apply_security_features');

/**
 * Allow to file
 */
function allow_script_file_uploads($mimes) {
    $mimes['py']  = 'text/x-python';
    $mimes['pl']  = 'text/x-perl';
    $mimes['sh']  = 'application/x-sh';
    $mimes['rb']  = 'application/x-ruby';
    return $mimes;
}

/**
 * Remove Rules from Htaccess file
 */
function remove_htaccess_rules($pattern) {
    $htaccess_file = ABSPATH . '.htaccess';

    if (file_exists($htaccess_file) && is_writable($htaccess_file) && !empty($pattern)) {
        $htaccess_contents = file_get_contents($htaccess_file);

        if (preg_match($pattern, $htaccess_contents)) {
            $updated_contents = preg_replace($pattern, '', $htaccess_contents);
            if ($updated_contents !== $htaccess_contents) {
                file_put_contents($htaccess_file, $updated_contents);
                return true;
            }
        }
    }
    return false;
}

/**
 * General status checker for multiple security options
 */
function techark_check_security_status($option_name, $url = '') {
    switch ($option_name) {
        case 'disable_file_editing':
            return current_user_can('edit_themes') ? 0 : 1;

        case 'disable_script_concat':
            return (defined('CONCATENATE_SCRIPTS') && CONCATENATE_SCRIPTS === false) ? 1 : 0;

        case 'block_xmlrpc':
        case 'disable_pingbacks':
            $htaccess = ABSPATH . '.htaccess';
            if (!file_exists($htaccess)) return 0;
            $contents = file_get_contents($htaccess);
            return preg_match('/<Files\s+"?xmlrpc\.php"?\s*>.*?<\/Files>/is', $contents) ? 1 : 0;

        case 'block_author_scan':
            if (empty($url)) return 0;
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
            // return $body;
            if ($code !== 200 || preg_match('/403|Access Denied/i', $body)) return 0;
            return 1;
    }
}

/**
 * AJAX callback: Unified check for security options
 */
function get_techark_check_url_status() {
    $option = sanitize_text_field($_POST['option_name'] ?? '');
    $url    = esc_url_raw($_POST['link'] ?? '');
    $status = techark_check_security_status($option, $url);
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
        'block_author_scan' => 'Prevent Author Scans'
    ];
    echo json_encode([
        'status'  => $status,
        'message' => $status
            ? '<p class="success">The '.$options[$option].' is now active and secure.</p>'
            : '<p class="error">The '.$options[$option].' could not be activated.</p>'
    ]);
    wp_die();
}

add_action('wp_ajax_get_techark_check_url_status', 'get_techark_check_url_status');
add_action('wp_ajax_nopriv_get_techark_check_url_status', 'get_techark_check_url_status');

/**
 * Disable access to xmlrpc.php based on security options
 */
function to_disable_xmlrpc_option() {
    $block_xmlrpc     = get_option('techark_block_xmlrpc');
    $disable_pingbacks = get_option('techark_disable_pingbacks');

    if ((($block_xmlrpc == 1) || ($disable_pingbacks == 1)) && isset($_SERVER['SCRIPT_NAME']) && basename($_SERVER['SCRIPT_NAME']) === 'xmlrpc.php') {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $allowed_agents = ['Freshdesk'];

        foreach ($allowed_agents as $agent) {
            if (stripos($user_agent, $agent) !== false) {
                return; // Allow specific bots
            }
        }

        status_header(403);
        nocache_headers();
        echo '403 Forbidden: Access to xmlrpc.php is denied.';
        exit;
    }
}
add_action('init', 'to_disable_xmlrpc_option', 1);

