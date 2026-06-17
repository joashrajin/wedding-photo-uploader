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
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Only enqueue on plugin admin pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'wedding-photo-uploader') === false) {
            return;
        }

        wp_enqueue_script(
            $this->plugin_name,
            WPU_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            $this->version,
            false
        );

        wp_localize_script($this->plugin_name, 'wpuAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpu_admin_nonce'),
            'i18n' => array(
                'confirmDelete' => __('Are you sure you want to delete this item?', 'wedding-photo-uploader'),
                'confirmApprove' => __('Are you sure you want to approve this item?', 'wedding-photo-uploader'),
                'confirmReject' => __('Are you sure you want to reject this item?', 'wedding-photo-uploader'),
                'error' => __('An error occurred. Please try again.', 'wedding-photo-uploader'),
                'success' => __('Operation completed successfully.', 'wedding-photo-uploader')
            )
        ));
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

        global $wpdb;
        $table_name = $wpdb->prefix . 'wedding_photos';
        
        // Get all photos ordered by upload date
        $photos = $wpdb->get_results(
            "SELECT wp.*, p.guid as image_url 
            FROM $table_name wp 
            LEFT JOIN {$wpdb->posts} p ON wp.image_id = p.ID 
            ORDER BY wp.date_uploaded DESC"
        );

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
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('wpu_options');
                do_settings_sections('wpu_options');
                submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register settings for the plugin.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        register_setting('wpu_options', 'wpu_options');

        add_settings_section(
            'wpu_main_section',
            __('Main Settings', 'wedding-photo-uploader'),
            array($this, 'render_section_description'),
            'wpu_options'
        );

        add_settings_field(
            'wpu_notification_email',
            __('Notification Email', 'wedding-photo-uploader'),
            array($this, 'render_notification_email_field'),
            'wpu_options',
            'wpu_main_section'
        );

        add_settings_field(
            'wpu_max_upload_size',
            __('Maximum Upload Size (MB)', 'wedding-photo-uploader'),
            array($this, 'render_max_upload_size_field'),
            'wpu_options',
            'wpu_main_section'
        );
    }

    /**
     * Render the section description.
     *
     * @since    1.0.0
     */
    public function render_section_description() {
        echo '<p>' . esc_html__('Configure the main settings for the Wedding Media Uploader plugin.', 'wedding-photo-uploader') . '</p>';
    }

    /**
     * Render the notification email field.
     *
     * @since    1.0.0
     */
    public function render_notification_email_field() {
        $options = get_option('wpu_options');
        $value = isset($options['notification_email']) ? $options['notification_email'] : get_option('admin_email');
        ?>
        <input type="email" name="wpu_options[notification_email]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php esc_html_e('Email address to receive notifications when new media is uploaded.', 'wedding-photo-uploader'); ?></p>
        <?php
    }

    /**
     * Render the maximum upload size field.
     *
     * @since    1.0.0
     */
    public function render_max_upload_size_field() {
        $options = get_option('wpu_options');
        $value = isset($options['max_upload_size']) ? $options['max_upload_size'] : 200;
        ?>
        <input type="number" name="wpu_options[max_upload_size]" value="<?php echo esc_attr($value); ?>" min="1" max="500" class="small-text">
        <p class="description"><?php esc_html_e('Maximum file size in megabytes that users can upload.', 'wedding-photo-uploader'); ?></p>
        <?php
    }
} 