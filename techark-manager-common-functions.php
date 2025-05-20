<?php
class TechArk_Manager_Common_Security_Settings {
    private $options;

    public function __construct() {
        // Hook into WordPress initialization actions
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('init', [$this, 'apply_security_features']);
        add_action('wp_ajax_techark_update_security_options', [$this, 'update_security_options']);
        add_action('wp_ajax_nopriv_techark_update_security_options', [$this, 'update_security_options']);
    }
    
    /**
     * Enqueue custom admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook == 'techark-manager_page_techark-header-security-settings' || $hook === 'techark-manager_page_techark-login-security-settings' || $hook == 'techark-manager_page_techark-security-settings') {
            wp_enqueue_style('wp-update-styles', plugin_dir_url(__FILE__) . 'css/techark-security.css');
            wp_enqueue_script('techark-header_security-common-js', plugin_dir_url(__FILE__) . 'js/common-security.js', ['jquery'], true);
            wp_localize_script('techark-header_security-common-js', 'techarkData', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('techark_nonce'),
            ]);
        }
    }

    /**
     * Add or Removed rules from htaccess
     */
    public function apply_security_features() {
        $rules = [];       
        $options = [
            'block_xmlrpc', 'disable_pingbacks', 'disable_file_editing', 'disable_script_concat',
            'block_php_in_includes', 'block_php_in_uploads', 'restrict_scripting_lang',
            'bot_protection', 'block_sensitive_files', 'block_htaccess_access', 'block_author_scan','strong_password_enforcement', 'header_security'
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
            'block_author_scan' => '/RewriteCond\s+\%\{QUERY_STRING\}\s+author=\\\d\s+RewriteRule\s+\^\s+-\s+\[F\]\s*/i',
            'permissions_policy_block' => '/<IfModule\s+mod_headers\.c>\s*<FilesMatch\s+"\\\\\.\(php\|html\)\$">\s*Header\s+set\s+X-Frame-Options\s+"SAMEORIGIN"\s*Header\s+set\s+Permissions-Policy\s+"[^"]*"\s*<\/FilesMatch>\s*<\/IfModule>\s*/is',
            'header_security' => '/<ifModule\s+mod_headers\.c>\s*Header\s+set\s+Strict-Transport-Security.*?Header\s+set\s+Content-Security-Policy.*?<\/ifModule>/is'
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
                 define('DISALLOW_FILE_EDIT', true);
                break;

            case 'disable_script_concat':
                define('CONCATENATE_SCRIPTS', false);
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
                if (defined('WPE_APIKEY') && $server == 'wpengine') {
                    $rules[] = "<Directory {$dir}>
<FilesMatch \"\\.php$\">
    Order Allow,Deny
    Deny from all
</FilesMatch>
</Directory>";
                } else if ($server == 'other') {
                    $rules[] = "<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} ^.*{$dir}.*\.php$ [NC]
    RewriteRule .* - [F,L]
</IfModule>";
                }   
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
            case 'header_security':
                if (!defined('WPE_APIKEY')) {
                    $rules[] = '<ifModule mod_headers.c>
                    Header set Strict-Transport-Security "max-age=31536000" env=HTTPS
                    Header set X-XSS-Protection "1; mode=block"
                    Header set X-Content-Type-Options nosniff
                    Header set X-Frame-Options DENY
                    Header set Referrer-Policy: no-referrer-when-downgrade
                    Header set Content-Security-Policy: upgrade-insecure-requests
                    </ifModule>
                    <IfModule mod_headers.c>
                      <FilesMatch "\.(php|html)$">
                        Header set X-Frame-Options "SAMEORIGIN"
                        Header set Permissions-Policy "accelerometer=(), ambient-light-sensor=(), autoplay=(), battery=(), camera=(), cross-origin-isolated=(), display-capture=(), document-domain=(), encrypted-media=(), execution-while-not-rendered=(), execution-while-out-of-viewport=(), fullscreen=(), geolocation=(), gyroscope=(), interest-cohort=(), layout-animations=(), legacy-image-formats=(), magnetometer=(), microphone=(), midi=(), navigation-override=(), oversized-images=(), payment=(), picture-in-picture=(), publickey-credentials-get=(), screen-wake-lock=(), sync-script=(), sync-xhr=(), usb=(), vertical-scroll=(), web-share=(), wake-lock=(), xr-spatial-tracking=()"
                      </FilesMatch>
                    </IfModule>';
                }
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
                    $this->remove_htaccess_rules($htaccess_patterns[$pattern_key]);
                    break;
                case 'block_php_in_includes':
                    $pattern_key = "{$key}_" . ($server === 'wpengine' ? 'wpengine' : 'other');
                    if ($server == 'other') {
                        $this->remove_htaccess_rules($htaccess_patterns[$pattern_key]);
                    }
                    break;
                case 'restrict_scripting_lang':
                    $this->remove_htaccess_rules($htaccess_patterns[$key]);
                    if ( ! defined( 'ALLOW_UNFILTERED_UPLOADS' ) ) {
                        define( 'ALLOW_UNFILTERED_UPLOADS', true );
                    }
                    add_filter('upload_mimes', [$this, 'allow_script_file_uploads']);
                    break;

                case 'bot_protection':
                case 'block_sensitive_files':
                case 'block_htaccess_access':
                case 'block_author_scan':
                    $this->remove_htaccess_rules($htaccess_patterns[$key]);
                    break;

                case 'header_security':
                    $this->remove_htaccess_rules($htaccess_patterns[$key]);
                    $this->remove_htaccess_rules($htaccess_patterns['permissions_policy_block']);

                    break;
    
                case 'block_xmlrpc':
                case 'disable_pingbacks':
                    if (!get_option('techark_block_xmlrpc') && !get_option('techark_disable_pingbacks')) {
                        $this->remove_htaccess_rules($htaccess_patterns['block_xmlrpc']);
                    }
                    break;
            }
        }
        update_option('techark_last_header_updated', current_time('mysql'));
    }

   

    /**
     * Remove Rules from Htaccess file
     */
    public function remove_htaccess_rules($pattern) {
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
     * Allow to file
     */
    public function allow_script_file_uploads($mimes) {
        $mimes['py']  = 'text/x-python';
        $mimes['pl']  = 'text/x-perl';
        $mimes['sh']  = 'application/x-sh';
        $mimes['rb']  = 'application/x-ruby';
        return $mimes;
    }
     /**
     * Update option on ajax change
     */
    public function update_security_options() {
        $name = $_POST['name'];
        $data_name = $_POST['data_name'];
        $value = $_POST['value'];
        update_option($name, $value);
        update_option('techark_last_updated', current_time('mysql'));

        $this->apply_security_features();
        $message = '<div id="setting-error-techark_security_message" class="notice notice-success settings-error is-dismissible"> 
                    <p><strong>The '.$data_name.' setting has been saved successfully.</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
                </div>';
                $message .= '<script type="text/javascript">
        jQuery(document).ready(function($) {
            $(".notice-dismiss").on("click", function() {
                $(this).closest(".notice").fadeOut();
            });
        });
      </script>';

        echo json_encode([
            'message' => $message
        ]);
        wp_die();
    }
    
}

$techArkSecuritySettings = new TechArk_Manager_Common_Security_Settings();
