<?php

class TechArk_Login_Security {

    private static $instance = null;
    private $options;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->options = [
            'custom_login_url' => [
                'label' => 'Custom Admin Login URL',
                'desc' => 'Change the default login URL (wp-login.php & wp-admin) to something custom.',
                'check' => false,
                'data_name' => 'custom_login_url',
                'use_desc' => 'Helps protect your site from automated login bots and brute-force attacks by hiding the default login URL.',
                'more_info' => 'https://blog.sucuri.net/2024/01/how-to-find-change-protect-the-wordpress-login-url-a-beginners-guide.html',
                'note' => 'If left empty, the default wp-login.php will be used. Set a unique slug to better protect your login page.'
            ],
            
        ];

        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('init', [$this, 'apply_features']);
        add_action('wp_ajax_techark_reset_login_security_options', [$this, 'reset_options']);
        add_action('wp_ajax_nopriv_techark_reset_login_security_options', [$this, 'reset_options']);
        add_action('wp_ajax_techark_sent_login_mail', [$this, 'sent_login_mail']);
        add_action('wp_ajax_nopriv_techark_sent_login_mail', [$this, 'sent_login_mail']);
        
    }
    // Added submenu
    public function add_menu() {
        add_submenu_page(
            'wp-pending-wp-updates-manager',
            'Login Security',
            'Login Security',
            'manage_options',
            'techark-login-security-settings',
            [$this, 'render_settings_page']
        );
    }
    /** Register Settings */
    public function register_settings() {
        add_settings_section('techark_login_security_main_section', '', null, 'techark-login-security-settings');

        foreach ($this->options as $key => $data) {
            register_setting('techark_login_security_group', "techark_{$key}");
            register_setting('techark_login_security_group', "techark_{$key}_value");

            add_settings_field('techark_' . $key, '', function () use ($key, $data) {
                $value = get_option('techark_' . $key);
                if($key == 'custom_login_url') {
                    $login_url = get_option('techark_' . $key . '_value') ? : '';
                }
    
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
                            <!-- <input type="checkbox" class="techark_login_security_chnages" name="techark_<?php echo esc_attr($key); ?>" id="techark_<?php echo esc_attr($key); ?>" value="1" <?php checked(1, $value); ?>> -->
                        </div>
                        <div class="techark-setting-item-notes">
                            <label for="techark_<?php echo esc_attr($key); ?>" class="checkbox-label-item"><strong><?php echo esc_html($data['desc']); ?></strong></label>
                            <?php if ($key === 'custom_login_url'): ?>
                                <div class="server-select" style="margin-bottom: 10px;margin-top: 10px;">
                                    <label style="margin-right: 0px; font-weight:bold;" for="techark_<?php echo esc_attr($key); ?>_value"><?php echo get_site_url().'/'; ?></label>
                                    <input type="text" name="techark_<?php echo esc_attr($key); ?>_value" id="techark_<?php echo esc_attr($key); ?>_value" value="<?php echo $login_url; ?>" placeholder="wp-admin" class="techark_login_security_changes">
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
            }, 'techark-login-security-settings', 'techark_login_security_main_section');
        }

        register_setting('techark_login_security_group', 'techark_login_security_option');
    }

    /** Added fields */
    public function render_settings_page() {
        $last_updated = get_option('techark_login_security_last_updated');
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
                    <h3 class="techark-security-h1"><span class="dashicons dashicons-shield"></span> <?php esc_html_e('Login Security Settings', 'techark-manager'); ?></h3>
                    <div class="techark-button-wrap">
                        <input type="button" name="save_button" class="button button-primary custom-submit-button" value="Save Changes">
                        <input type="button" name="reset_options" id="reset_options" class="button button-secondary custom-reset-button" value="Reset Options">
                        <input type="submit" name="submit" id="submit" class="" value="Save Changes" style="display:none;">

                    </div>
                </div>
                <h1 class="techark-security-h1" style="display:none;"><span class="dashicons dashicons-shield"></span> <?php esc_html_e('TechArk Security Settings', 'techark-manager'); ?></h1>
    
                <?php 
                settings_fields('techark_login_security_group'); 
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
                        <?php do_settings_sections('techark-login-security-settings'); ?>
                    </div>
                </div>
            </form>     
            <div class="techark_user_role_modal">
                <div class="techark_user_role_wrap">
                    <div class="role_modal_header_wrap">
                        <h2><span class="dashicons dashicons-email-alt"></span> Send Email Notification</h2>
                    </div>
                    <div class="role_modal_content_wrap">
                        <p class="error-notes"><b>Note:</b>This action will notify all selected user roles that the login URL is being changed.</p>
                        <div class="role_modal_actions">
                            <input type="button" id="select_all_roles" class="button" value="Select All">
                            <input type="button" id="deselect_all_roles" class="button" value="Deselect All">
                        </div>
                        <div class="role_checkbox_list">
                            <?php 
                            global $wp_roles;
                            if ( ! isset( $wp_roles ) ) {
                                $wp_roles = new WP_Roles();
                            }
                            
                            $all_roles = $wp_roles->roles;
                            
                            foreach ( $all_roles as $role_key => $role_data ) {
                                echo '<div class="techark_user_role">';
                                echo '<label>';
                                echo '<input type="checkbox" name="user_roles[]" value="' . esc_attr($role_key) . '"> ';
                                echo esc_html($role_data['name']);
                                echo '</label>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                        
                    </div>
                    <div class="role_modal_footer_wrap">
                        <div id="mail_response_message" style="margin-top: 10px; display:block;width:100%;"></div>
                        <input type="button" id="sent_mail" class="button" value="Save & notify">
                        <input type="button" id="cancel_mail" class="button button-red" value="Discard">
                    </div>
                </div>   
            </div>   
        </div>
        
        <?php
    }
    /** Register Script */
    public function enqueue_assets($hook) {
        if ($hook === 'techark-manager_page_techark-login-security-settings') {
            wp_enqueue_style('wp-login-security-styles', plugin_dir_url(__FILE__) . 'css/login-security.css');
            wp_enqueue_script('techark-security-login-js', plugin_dir_url(__FILE__) . 'js/techark-login-security-script.js', ['jquery'], null, true);
            wp_localize_script('techark-security-login-js', 'techarkData', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('techark_nonce'),
            ]);
        }
    }
    /** Reset value */
    public function reset_options() {
        foreach (array_keys($this->options) as $key) {
            delete_option("techark_{$key}_value");
        }

        set_transient('techark_reset_notice', true, 30);
        wp_send_json_success();
    }

    /**
     * Sent Email Notification
     */
    public function sent_login_mail() {
        $roles = isset($_POST['roles']) ? (array) $_POST['roles'] : array();
        $techark_custom_login_url_value = $_POST['techark_custom_login_url_value'];
        $old_login_url = get_option('techark_custom_login_url_value');

        $login_url = $techark_custom_login_url_value ?? 'wp-login.php';
        $login_url = get_site_url() .'/'.$login_url;
        $site_name = $site_name ?: get_bloginfo( 'name' );

        if(!empty($roles)) {
            $users = get_users(array(
                'role__in' => $roles
            ));
            if (!empty($users)) {
                foreach ($users as $user) {
                    $user_email = $user->user_email;
                    // $user_email = 'rkamani.techark@gmail.com';
                    if(!empty($user_email)) {
                        if ( !empty($_POST['techark_custom_login_url_value']) ) {
                            if(!empty($old_login_url)) {
                                $old_login_url = get_site_url() .'/'.$old_login_url;
                                $old_login_url_html ="<p><strong>Old Login URL:</strong> {$old_login_url}</p>";
                            } else {
                                $old_login_url = wp_login_url();
                                $old_login_url_html ="<p><strong>Old Login URL:</strong> {$old_login_url}</p>";
                            }
                            $subject = 'Notice: Your WordPress Login URL Has Been Updated';
                            $message = "
                            <p>Hello,</p>
                            <p>We’re writing to let you know that the login URL for your WordPress website has recently been updated to enhance security.</p>
                            {$old_login_url_html}
                            <p><strong>New Login URL:</strong> <a href=\"{$login_url}\">{$login_url}</a></p>
                            <p>Please update any saved bookmarks and use this new URL for future logins. The previous login address (such as <code>{$old_login_url}</code>) may no longer work.</p>
                            <p>If you did not request this change or believe it was made in error, please contact the site administrator immediately.</p>
                            <p>Thank you,<br>
                            The {$site_name} Team</p>
                            ";
                            $headers = array( 'Content-Type: text/html; charset=UTF-8' );
            
                            $status = wp_mail( $user_email, $subject, $message, $headers );
            
                        } else {
                            if(!empty($old_login_url)) {
                                $old_login_url = get_site_url() .'/'.$old_login_url;
                                $old_login_url_html ="<p><strong>Old Login URL:</strong> {$old_login_url}</p>";
                            } else {
                                $old_login_url = wp_login_url();
                                $old_login_url_html ="<p><strong>Old Login URL:</strong> {$old_login_url}</p>";
                            }
    
                            $subject = 'Notice: WordPress Login URL Reverted to Default';
                            $login_url = wp_login_url();
                            $message = "
                            <p>Hello,</p>
                            <p>This is to inform you that the custom login URL for your WordPress website has been reverted to the default login address.</p>
                            {$old_login_url_html}
                            <p><strong>New Login URL:</strong> <a href=\"{$login_url}\">{$login_url}</a></p>
                            <p>You can now access your login page using the standard WordPress URL (e.g., <code>{$login_url}</code>).</p>
                            <p>If this change was not intentional or you have any questions, please contact your website administrator.</p>
                            <p>Thank you,<br>
                            The {$site_name} Team</p>
                            ";
                            $headers = array( 'Content-Type: text/html; charset=UTF-8' );
                            $status = wp_mail( $user_email, $subject, $message, $headers );
                        }
                    }
                }
            }

        }

        if ( $status ) {
            wp_send_json_success( array(
                'message' => 'Email sent successfully.',
                'status'  => true
            ) );
        } else {
            wp_send_json_success( array(
                'message' => 'Failed to send email.',
                'status'  => false
            ) );
        }
    }
    /**
     * Save last login time
     */
    public function apply_features() {
        update_option('techark_login_security_last_updated', current_time('mysql'));
    }  
}

TechArk_Login_Security::get_instance();
