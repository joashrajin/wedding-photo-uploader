<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Wedding_Photo_Uploader
 * @subpackage Wedding_Photo_Uploader/includes
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for the admin area.
 *
 * @package    Wedding_Photo_Uploader
 * @subpackage Wedding_Photo_Uploader/includes
 */
class WPU_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_photo_actions'));
        add_action('admin_notices', array($this, 'display_admin_notices'));
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        // Only enqueue on plugin admin pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'wedding-photo-uploader') === false) {
            return;
        }

        wp_enqueue_style(
            $this->plugin_name,
            WPU_PLUGIN_URL . 'assets/css/styles.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Add menu items for the admin area.
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Wedding Media', 'wedding-photo-uploader'),
            __('Wedding Media', 'wedding-photo-uploader'),
            'manage_options',
            'wedding-photo-uploader',
            array($this, 'render_admin_page'),
            'dashicons-camera',
            30
        );

        add_submenu_page(
            'wedding-photo-uploader',
            __('Settings', 'wedding-photo-uploader'),
            __('Settings', 'wedding-photo-uploader'),
            'manage_options',
            'wedding-photo-uploader-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Handle photo actions from admin page
     */
    public function handle_photo_actions() {
        if (!isset($_GET['action']) || !isset($_GET['photo_id']) || !isset($_GET['_wpnonce'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wedding-photo-uploader'));
        }

        // Verify nonce
        if (!wp_verify_nonce($_GET['_wpnonce'], 'wpu_photo_action')) {
            wp_die(__('Invalid nonce verification', 'wedding-photo-uploader'));
        }

        $photo_id = absint($_GET['photo_id']);
        $action = sanitize_text_field($_GET['action']);
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'pending';

        if (!in_array($action, array('approve', 'reject'))) {
            wp_die(__('Invalid action', 'wedding-photo-uploader'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'wedding_photos';

        // Get the current status before updating
        $current_status = $wpdb->get_var($wpdb->prepare(
            "SELECT status FROM $table_name WHERE id = %d",
            $photo_id
        ));

        // Only update if status is different
        if ($current_status !== ($action === 'approve' ? 'approved' : 'rejected')) {
            // Update photo status
            $result = $wpdb->update(
                $table_name,
                array('status' => $action === 'approve' ? 'approved' : 'rejected'),
                array('id' => $photo_id),
                array('%s'),
                array('%d')
            );

            if ($result === false) {
                wp_die(__('Database error occurred', 'wedding-photo-uploader'));
            }
        }

        // Determine which tab to redirect to
        // If we're in a specific tab (approved/rejected), stay there after action
        // Otherwise go to the appropriate tab based on the action
        $redirect_tab = $current_tab;
        if ($current_tab === 'pending') {
            $redirect_tab = $action === 'approve' ? 'approved' : 'rejected';
        }

        // Redirect back to the admin page with status and tab
        wp_safe_redirect(add_query_arg(
            array(
                'page' => 'wedding-photo-uploader',
                'tab' => $redirect_tab,
                'message' => $action === 'approve' ? 'approved' : 'rejected'
            ),
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Display admin notices
     */
    public function display_admin_notices() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'wedding-photo-uploader') {
            return;
        }

        if (isset($_GET['message'])) {
            $message = '';
            $type = 'success';

            switch ($_GET['message']) {
                case 'approved':
                    $message = __('Item approved successfully!', 'wedding-photo-uploader');
                    break;
                case 'rejected':
                    $message = __('Item rejected successfully!', 'wedding-photo-uploader');
                    break;
            }

            if ($message) {
                printf(
                    '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                    esc_attr($type),
                    esc_html($message)
                );
            }
        }
    }

    /**
     * Render the main admin page.
     *
     * @since    1.0.0
     */
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wedding-photo-uploader'));
        }

        // The moderation table is built in admin-interface.php, which runs its own
        // status-filtered queries.

        include WPU_PLUGIN_DIR . 'includes/admin-interface.php';
    }

    /**
     * Render the settings page.
     *
     * @since    1.0.0
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_GET['settings-updated'])) {
            add_settings_error('wpu_messages', 'wpu_message', __('Settings Saved', 'wedding-photo-uploader'), 'updated');
        }

        settings_errors('wpu_messages');
        ?>
        <div class="wrap wpu-settings-page">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <div class="wpu-settings-card">
                <form action="options.php" method="post">
                    <?php
                    settings_fields('wpu_settings_group');
                    do_settings_sections('wpu_settings_group');
                    submit_button(__('Save Settings', 'wedding-photo-uploader'));
                    ?>
                </form>
            </div>
        </div>
        <style>
            /* Presentation-only polish, scoped to this settings screen. */
            .wpu-settings-page .wpu-settings-card {
                max-width: 760px;
                margin-top: 16px;
                padding: 4px 28px 20px;
                background: #fff;
                border: 1px solid #e0e0e6;
                border-radius: 10px;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            }
            .wpu-settings-page .wpu-settings-card h2 {
                font-size: 1.1rem;
                padding-top: 16px;
                margin-bottom: 0;
            }
            .wpu-settings-page .form-table th {
                padding: 22px 10px 22px 0;
                font-weight: 600;
            }
            @media (min-width: 783px) {
                .wpu-settings-page .form-table th {
                    width: 240px;
                }
            }
            .wpu-settings-page .form-table td {
                padding: 16px 10px;
            }
            .wpu-settings-page .form-table input[type="email"],
            .wpu-settings-page .form-table input[type="number"] {
                border: 1px solid #d6d6de;
                border-radius: 8px;
                padding: 8px 12px;
                box-shadow: none;
                transition: border-color 0.15s ease, box-shadow 0.15s ease;
            }
            .wpu-settings-page .form-table input[type="email"] {
                width: 360px;
                max-width: 100%;
            }
            .wpu-settings-page .form-table input:focus {
                border-color: #2271b1;
                box-shadow: 0 0 0 3px rgba(34, 113, 177, 0.18);
                outline: none;
            }
            .wpu-settings-page .form-table .description {
                color: #646970;
                margin-top: 6px;
            }
            .wpu-settings-page .submit {
                padding-top: 4px;
                margin-top: 8px;
                border-top: 1px solid #f0f0f4;
            }
        </style>
        <?php
    }

    /**
     * Register settings for the plugin.
     *
     * Operates on the 'wpu_settings' option — the same option the uploader reads
     * for its size and file-count limits — so admin changes actually take effect.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        register_setting('wpu_settings_group', 'wpu_settings', array(
            'type'              => 'array',
            'sanitize_callback' => array($this, 'sanitize_settings'),
        ));

        add_settings_section(
            'wpu_main_section',
            __('Upload Settings', 'wedding-photo-uploader'),
            array($this, 'render_section_description'),
            'wpu_settings_group'
        );

        add_settings_field(
            'wpu_notification_email',
            __('Notification Email', 'wedding-photo-uploader'),
            array($this, 'render_notification_email_field'),
            'wpu_settings_group',
            'wpu_main_section'
        );

        add_settings_field(
            'wpu_max_file_size',
            __('Maximum Photo Size (MB)', 'wedding-photo-uploader'),
            array($this, 'render_max_file_size_field'),
            'wpu_settings_group',
            'wpu_main_section'
        );

        add_settings_field(
            'wpu_video_max_size',
            __('Maximum Video Size (MB)', 'wedding-photo-uploader'),
            array($this, 'render_video_max_size_field'),
            'wpu_settings_group',
            'wpu_main_section'
        );

        add_settings_field(
            'wpu_max_files',
            __('Maximum Files Per Uploader', 'wedding-photo-uploader'),
            array($this, 'render_max_files_field'),
            'wpu_settings_group',
            'wpu_main_section'
        );
    }

    /**
     * Sanitize and validate submitted settings, merging into the existing
     * 'wpu_settings' option so gallery/other keys are preserved.
     *
     * @since    1.1.7
     * @param    array    $input    Submitted settings.
     * @return   array              Sanitized settings to store.
     */
    public function sanitize_settings($input) {
        $existing = get_option('wpu_settings', array());
        if (!is_array($existing)) {
            $existing = array();
        }
        $out = $existing;

        if (!is_array($input)) {
            return $out;
        }

        if (isset($input['notification_email'])) {
            $email = sanitize_email($input['notification_email']);
            $out['notification_email'] = $email ? $email : get_option('admin_email');
        }
        if (isset($input['max_file_size'])) {
            $out['max_file_size'] = max(1, min(500, absint($input['max_file_size'])));
        }
        if (isset($input['video_max_size'])) {
            $out['video_max_size'] = max(1, min(2000, absint($input['video_max_size'])));
        }
        if (isset($input['max_files'])) {
            $out['max_files'] = max(1, min(1000, absint($input['max_files'])));
        }

        return $out;
    }

    /**
     * Render the section description.
     *
     * @since    1.0.0
     */
    public function render_section_description() {
        echo '<p>' . esc_html__('Configure upload limits for the Wedding Media Uploader. These limits are enforced on every upload, including anonymous guest uploads.', 'wedding-photo-uploader') . '</p>';
    }

    /**
     * Render the notification email field.
     *
     * @since    1.0.0
     */
    public function render_notification_email_field() {
        $options = get_option('wpu_settings', array());
        $value = isset($options['notification_email']) ? $options['notification_email'] : get_option('admin_email');
        ?>
        <input type="email" name="wpu_settings[notification_email]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php esc_html_e('Email address associated with uploads.', 'wedding-photo-uploader'); ?></p>
        <?php
    }

    /**
     * Render the maximum photo size field.
     *
     * @since    1.1.7
     */
    public function render_max_file_size_field() {
        $options = get_option('wpu_settings', array());
        $value = isset($options['max_file_size']) ? absint($options['max_file_size']) : 200;
        ?>
        <input type="number" name="wpu_settings[max_file_size]" value="<?php echo esc_attr($value); ?>" min="1" max="500" class="small-text">
        <p class="description"><?php esc_html_e('Maximum size in megabytes for an uploaded photo (1–500).', 'wedding-photo-uploader'); ?></p>
        <?php
    }

    /**
     * Render the maximum video size field.
     *
     * @since    1.1.7
     */
    public function render_video_max_size_field() {
        $options = get_option('wpu_settings', array());
        $value = isset($options['video_max_size']) ? absint($options['video_max_size']) : 500;
        ?>
        <input type="number" name="wpu_settings[video_max_size]" value="<?php echo esc_attr($value); ?>" min="1" max="2000" class="small-text">
        <p class="description"><?php esc_html_e('Maximum size in megabytes for an uploaded video (1–2000).', 'wedding-photo-uploader'); ?></p>
        <?php
    }

    /**
     * Render the maximum files-per-uploader field.
     *
     * @since    1.1.7
     */
    public function render_max_files_field() {
        $options = get_option('wpu_settings', array());
        $value = isset($options['max_files']) ? absint($options['max_files']) : 200;
        ?>
        <input type="number" name="wpu_settings[max_files]" value="<?php echo esc_attr($value); ?>" min="1" max="1000" class="small-text">
        <p class="description"><?php esc_html_e('Maximum number of files a single uploader name may submit (1–1000).', 'wedding-photo-uploader'); ?></p>
        <?php
    }
} 