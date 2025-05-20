<?php
class TechArk_Security_Settings {
    private $options;

    public function __construct() {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        
        // Build the SecurityHeaders.com scan URL
        $targetUrl = urlencode($scheme . '://' . $host);
        $securityHeadersUrl = "https://securityheaders.com/?q={$targetUrl}&followRedirects=on";
        

        $this->options =  [
            'block_xmlrpc' => ['label' => 'Disable XML-RPC', 'desc' => 'Disables XML-RPC to prevent brute force attacks.', 'check'=> true, 'url'=> 'xmlrpc.php', 'data_name'=>'block_xmlrpc','use_desc'=>'Disables the XML-RPC functionality in WordPress, preventing remote connections and blocking attacks that exploit this feature. This helps protect against brute force attempts and unauthorized access.','more_info' => 'https://www.hostinger.com/in/tutorials/xmlrpc-wordpress'],
            'disable_pingbacks' => ['label' => 'Disable Pingbacks', 'desc' => 'Removes pingback functionality to mitigate DDoS risks.', 'check'=> true, 'url'=> 'xmlrpc.php', 'data_name'=>'disable_pingbacks','use_desc'=>'Removes pingback functionality, which is used to notify other websites when you link to them. Disabling this reduces the risk of DDoS attacks and spam.','more_info' => 'https://wordpress.org/documentation/article/trackbacks-and-pingbacks/'],
            'disable_file_editing' => ['label' => 'Prevent File Changes via Admin', 'desc' => 'Disables the ability to edit files through the WordPress admin dashboard.', 'check'=> true, 'url'=> '', 'data_name'=>'disable_file_editing','use_desc'=>'Prevents users from editing theme and plugin files from the WordPress dashboard. This helps reduce security risks, as it prevents attackers from modifying your site’s code through the admin area.','more_info' => 'https://developer.wordpress.org/advanced-administration/security/hardening/#disable-file-editing'],
            'disable_script_concat' => ['label' => 'Disable Script Concatenation in Admin', 'desc' => 'Turns off script concatenation in the admin area to assist with debugging.', 'check'=> true, 'url'=> '', 'data_name'=>'disable_script_concat','use_desc'=>'Disables script concatenation in WordPress, ensuring that each JavaScript file is loaded individually. This can improve security and troubleshooting by preventing multiple scripts from being bundled together.','more_info' => 'https://developer.wordpress.org/reference/functions/script_concat_settings/'],
            'block_php_in_includes' => ['label' => 'Block Direct Access to PHP Files in wp-includes', 'desc' => 'Prevents users from directly accessing PHP files located in the wp-includes directory.', 'check'=> true, 'url'=> 'wp-includes/pluggable.php', 'data_name'=>'block_php_in_includes','use_desc'=>'Prevents the execution of PHP scripts in the wp-includes directory. This is an important security measure, as it blocks the possibility of exploiting PHP files within this directory for malicious purposes.','more_info' => 'https://developer.wordpress.org/advanced-administration/security/hardening/#securing-wp-includes'],
            'block_php_in_uploads' => ['label' => 'Block Direct Access to PHP Files in the Uploads Directory', 'desc' => 'Restricts direct access to PHP files uploaded to the wp-content/uploads directory.', 'check'=> false,'use_desc'=>'Blocks the execution of PHP scripts in the uploads directory. This stops attackers from uploading and running malicious PHP files, which is a common vector for attacks.','more_info' => 'https://www.wpbeginner.com/wordpress-security/#fileexecution'],
            'restrict_scripting_lang' => ['label' => 'Restrict Execution of Scripting Files', 'desc' => 'Prevents execution of script files such as .py, .pl, .cgi, .sh, and .rb.', 'check'=> false,'use_desc'=>'Restricts the use of certain scripting languages (e.g., PHP, JavaScript) on your WordPress site, limiting the potential for malicious code execution and enhancing overall security.','more_info' => 'https://developer.wordpress.org/advanced-administration/security/hardening/#database-security'],
            'bot_protection' => ['label' => 'Block Malicious Bots', 'desc' => 'Blocks known bad bots based on their user-agent strings.', 'check'=> false,'use_desc'=>'Enables bot protection features, such as blocking malicious bots or preventing certain automated actions on your site. This protects your site from scraping, spam, and brute-force attacks.','more_info' => 'https://solidwp.com/blog/stop-bad-bots/'],
            'block_sensitive_files' => ['label' => 'Protect Sensitive WordPress Files', 'desc' => 'Prevents access to sensitive files such as wp-config.php, readme.html, and license.txt.', 'check'=> true, 'url'=> 'readme.html', 'data_name'=>'block_sensitive_files', 'note'=>"If an enabled option doesn't appear to take effect on your site, please try clearing your server cache or any caching plugin you may be using.",'use_desc'=>'Blocks access to sensitive files like .htaccess, wp-config.php, and others that are critical to your WordPress site’s security. This prevents unauthorized users from viewing or modifying these files.','more_info' => 'https://developer.wordpress.org/advanced-administration/security/hardening/#securing-wp-config-php'],
            'block_htaccess_access' => ['label' => 'Prevent Access to .htaccess and .htpasswd Files', 'desc' => 'Blocks unauthorized access to .htaccess and .htpasswd files to enhance security. ', 'check'=> true, 'url'=> '.htaccess', 'data_name'=>'block_htaccess_access','use_desc'=>'Blocks access to the .htaccess file, which controls server configuration. Preventing unauthorized access ensures that the critical security settings in this file cannot be modified.','more_info' => 'https://www.malcare.com/blog/how-to-restrict-access-to-wordpress-files-using-htaccess/'],
            'block_author_scan' => ['label' => 'Block Author ID Enumeration', 'desc' => 'Prevents attackers from enumerating author IDs via URL queries, reducing the risk of user enumeration attacks.', 'check'=> true, 'url'=> '?author=', 'data_name'=>'block_author_scan','use_desc'=>'Prevents attackers from scanning your WordPress site for author usernames, which is a common tactic used to gain access to accounts via brute force. This helps to secure user data and prevent unauthorized access attempts.','more_info' => 'https://www.malcare.com/blog/wordpress-user-enumeration/'],
            'strong_password_enforcement' => [
                'label' => 'Enforce Strong Passwords',
                'desc' => 'Requires the use of strong passwords for improved account security.',
                'check' => false,
                'data_name' => 'strong_password_enforcement',
                'use_desc' => 'Helps protect accounts from being easily compromised by enforcing complex passwords.',
                'more_info' => 'https://melapress.com/wordpress-password-policy/',
            ],
            'header_security' => [
                'label' => 'Header Security',
                'desc'  => 'Enhance site security by enabling important HTTP security headers.',
                'check' => true,
                'redirect_check' => true,
                'url' => $securityHeadersUrl,
                'data_name' => 'header_security',
                'note'=>"If an enabled option doesn't reflect changes on your site, consider clearing your server or plugin cache.",
                'use_desc' => 'Enables a set of response headers that protect against common vulnerabilities like XSS, clickjacking, content sniffing, and insecure resource loading.',
                'more_info' => 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers',
            ],
        ];

        // Hook into WordPress initialization actions
        add_action('admin_menu', [$this, 'add_security_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('admin_notices', [$this, 'admin_notice']);
        add_action('wp_ajax_techark_reset_security_options', [$this, 'reset_security_options']);
        add_action('wp_ajax_nopriv_techark_reset_security_options', [$this, 'reset_security_options']);
        
        add_action('init', [$this, 'disable_xmlrpc_option'], 1);
        add_filter('registration_errors', [$this, 'enforce_strong_password'], 10, 3);
        add_action('user_profile_update_errors', [$this, 'validate_profile_update'], 10, 3);
    }
     /**
     * Add the Security submenu page
     */
    public function add_security_page() {
        $main_slug = 'wp-pending-wp-updates-manager';
        add_submenu_page(
            $main_slug,                             
            'Security',               
            'Security',                      
            'manage_options',
            'techark-security-settings',
            [$this, 'settings_page']
        );
    }

    /**
     * Register the security settings
     */
    public function register_settings() {
        add_settings_section('techark_main_section', '', null, 'techark-security-settings');

        foreach ($this->options as $key => $data) {
            register_setting('techark_security_group', 'techark_' . $key);
            if ($key === 'block_php_in_includes' || $key === 'block_php_in_uploads') {
                register_setting('techark_security_group', 'techark_' . $key . '_server');
            }
            add_settings_field('techark_' . $key, '', function () use ($key, $data) {
                $value = get_option('techark_' . $key);
                $value_server = get_option('techark_' . $key . '_server') ? : 'other';
                $wpengin_server =  ( defined('WPE_APIKEY') && $key == 'header_security') ? 'disable_option' : '';
                
                ?>
                
                <div class="techark-setting-item <?php echo $wpengin_server;?>" style="margin-bottom: 0; padding: 15px;">
                    <div class="techark-setting-item-label"> 
                        <?php echo esc_html($data['label']); ?>
                        <?php if (!empty($data['check'])): ?>
                        <div class="check-wrap">
                            <?php if(isset($data['redirect_check']) && $data['redirect_check']) { ?>
                            <a href="<?php echo esc_url($data['url']); ?>" class="check-url-redirect-response button button-secondary" target="_blank"
                               data-option-name="<?php echo esc_attr($data['data_name']); ?>">
                               <?php esc_html_e('Test Option', 'techark-manager'); ?>
                            </a>
                            <?php } else { ?>
                            <a href="javascript:void(0)" class="check-url-response button button-secondary"
                               data-check-link="<?php echo esc_url(site_url($data['url'])); ?>"
                               data-option-name="<?php echo esc_attr($data['data_name']); ?>">
                               <?php esc_html_e('Test Option', 'techark-manager'); ?>
                            </a>
                            <?php } ?>
                        </div>
                    <?php endif; ?>
                    </div>
                    <div class="techark-setting-item-checkbox">
                        <div class="checkbox-label">
                            <input type="checkbox" class="techark_security_changes" name="techark_<?php echo esc_attr($key); ?>" id="techark_<?php echo esc_attr($key); ?>" value="1" <?php checked(1, $value); ?> data-name="<?php echo esc_html($data['label']); ?>">
                        </div>
                        <div class="techark-setting-item-notes">
                            <label for="techark_<?php echo esc_attr($key); ?>" class="checkbox-label-item"><strong><?php echo esc_html($data['desc']); ?></strong></label>
    
                            <?php if ($key === 'block_php_in_includes' || $key === 'block_php_in_uploads'): ?>
                                <div class="server-select" style="margin-bottom: 10px;margin-top: 10px;">
                                    <label style="margin-right: 10px;">
                                        <input type="radio" name="techark_<?php echo esc_attr($key); ?>_server" class="techark_security_changes" value="other" <?php checked('other', $value_server); ?> data-name="<?php echo esc_html($data['label']); ?> on Other Server">
                                        Other
                                    </label>
                                    <label>
                                        <input type="radio" name="techark_<?php echo esc_attr($key); ?>_server" class="techark_security_changes" value="wpengine" <?php checked('wpengine', $value_server); ?> data-name="<?php echo esc_html($data['label']); ?> on Wp-Engine Server">
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
                                    <?php if(defined('WPE_APIKEY') && ( $key == 'header_security' || $key == 'block_php_in_includes')) {
                                        echo '<br> WP Engine does not allow .htaccess modifications directly, as all server-level configurations (including security headers) are managed by their support team.';
                                    } ?>
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
    

    /**
     * Enqueue custom admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook == 'techark-manager_page_techark-security-settings') {
            wp_enqueue_script('techark-security-admin-js', plugin_dir_url(__FILE__) . 'js/techark-security-script.js', ['jquery'], true);
            wp_localize_script('techark-security-admin-js', 'techarkData', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('techark_nonce'),
            ]);
        }
    }

    /**
     * Display admin notice messages
     */
    public function admin_notice() {
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
            if(isset($_GET['page']) && $_GET['page'] === 'techark-login-security-settings') {
                add_settings_error('techark_security_group', 'techark_security_message', ' Login security setting saved successfully!', 'updated');
            } else {
                add_settings_error('techark_security_group', 'techark_security_message', 'Settings saved successfully!', 'updated');

            }
        }
        if (get_transient('techark_reset_notice')) {
            add_settings_error('techark_security_group', 'techark_security_message', 'Settings have been reset to default.', 'updated');
            delete_transient('techark_reset_notice');
        }

        settings_errors('techark_security_group');
    }

    /**
     * Render the settings page
     */
    public function settings_page() {
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
                    <h3 class="techark-security-h1"><span class="dashicons dashicons-shield"></span> <?php esc_html_e('Security Settings', 'techark-manager'); ?></h3>
                    <div class="techark-button-wrap">
                        <input type="button" name="reset_options" id="reset_options" class="button button-secondary custom-reset-button" value="Reset Options">
                    </div>
                </div>
                <div class="techark-security-notes-wrap">
                    <p>Changes are saved automatically—there’s no need to click a separate Save button.</p>
                </div>
                <h1 class="techark-security-h1" style="display:none;"><span class="dashicons dashicons-shield"></span> <?php esc_html_e('Security Settings', 'techark-manager'); ?></h1>
                
                <?php 
                settings_fields('techark_security_group'); 
                ?>
                <div class="techark-ajax-sucess-msg"></div>
                
                <div class="techark-section open">
                    <div class="techark-section-wrap">
                        <div class="techark-left-section">
                            <h2><span class="dashicons dashicons-admin-tools"></span> Security Options<?php echo $last_updated ? "<span class='techark-log-time'><strong>Last Updated:</strong> " . esc_html($last_updated) . "</span>" : ''; ?></h2>
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
     * Reset Security Options
     */
    public function reset_security_options() {
        $options = [
            'block_xmlrpc', 'disable_pingbacks', 'disable_file_editing', 'disable_script_concat',
            'block_php_in_includes', 'block_php_in_uploads', 'restrict_scripting_lang',
            'bot_protection', 'block_sensitive_files', 'block_htaccess_access', 'block_author_scan','strong_password_enforcement','header_security'
        ];
        foreach ($options as $key) {
            delete_option("techark_$key");
            if ($key === 'block_php_in_includes' || $key === 'block_php_in_uploads') {
                delete_option('techark_' . $key . '_server');
            }
        }
        set_transient('techark_reset_notice', true, 30);

        wp_send_json_success();
        wp_die();
    }
   

    /**
     * Disable access to xmlrpc.php based on security options
     */
    public function disable_xmlrpc_option() {
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
     /** Strong Password msg */
     public function enforce_strong_password($errors, $sanitized_user_login, $user_email) {
        $password = $_POST['user_pass'] ?? '';
        $enabled = get_option('techark_strong_password_enforcement');
        if (!$enabled) return $errors;

        if (!$this->is_password_strong($password)) {
            $errors->add('weak_password', __('Password must be at least 8 characters long and include uppercase, lowercase, number, and special character.', 'techark-security'));
        }

        return $errors;
    }
    /** Valid Strong Password msg */
    public function validate_profile_update($errors, $update, $user) {
        if (!$update || empty($_POST['pass1'])) return;

        $enabled = get_option('techark_strong_password_enforcement');
        if (!$enabled) return;

        if (!$this->is_password_strong($_POST['pass1'])) {
            $errors->add('weak_password', __('Password must be at least 8 characters long and include uppercase, lowercase, number, and special character.', 'techark-security'));
        }
    }
    /** Check password text */
    private function is_password_strong($password) {
        return strlen($password) >= 8 &&
               preg_match('/[A-Z]/', $password) &&
               preg_match('/[a-z]/', $password) &&
               preg_match('/[0-9]/', $password) &&
               preg_match('/[\W]/', $password);
    }
}

$techArkSecuritySettings = new TechArk_Security_Settings();
