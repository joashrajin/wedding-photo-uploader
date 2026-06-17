<?php
/**
 * Plugin Name: Wedding Photo Uploader
 * Plugin URI: https://github.com/joashrajin/wedding-photo-uploader
 * Description: A WordPress plugin that allows wedding guests to upload their photos and videos. Features include photo/video moderation, gallery display with filtering, and email notifications.
 * Version: 1.1.8
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Joash Rajin
 * Author URI: https://joashrajin.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wedding-photo-uploader
 * Domain Path: /languages
 *
 * This plugin allows wedding guests to upload their photos and videos, which can be moderated
 * by the admin before being displayed in a beautiful gallery. Features include:
 * - Photo and video upload with size and type restrictions
 * - Admin moderation interface for both photos and videos
 * - Gallery filtering options (photos, videos, or both)
 * - Gallery sorting options (date, name, filename)
 * - Email notifications for approved content
 * - Lightbox gallery display for photos
 * - Mobile-responsive design
 * - HEIF/HEIC support
 * - Real-time upload progress tracking
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Plugin version
define('WPU_VERSION', '1.1.8');

// Plugin directory path and URL
define('WPU_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPU_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_wedding_photo_uploader() {
    require_once WPU_PLUGIN_DIR . 'includes/class-wpu-activator.php';
    WPU_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_wedding_photo_uploader() {
    require_once WPU_PLUGIN_DIR . 'includes/class-wpu-deactivator.php';
    WPU_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_wedding_photo_uploader');
register_deactivation_hook(__FILE__, 'deactivate_wedding_photo_uploader');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once WPU_PLUGIN_DIR . 'includes/class-wpu-loader.php';
require_once WPU_PLUGIN_DIR . 'includes/class-wpu-i18n.php';
require_once WPU_PLUGIN_DIR . 'includes/class-wpu-admin.php';
require_once WPU_PLUGIN_DIR . 'includes/class-wpu-gallery.php';
require_once WPU_PLUGIN_DIR . 'includes/class-wpu-uploader.php';

/**
 * Begins execution of the plugin.
 */
function run_wedding_photo_uploader() {
    $plugin = new WPU_Loader();
    $plugin->run();
}

run_wedding_photo_uploader();

// Check requirements
require_once WPU_PLUGIN_DIR . 'includes/requirements.php';
if (!wpu_check_requirements()) {
    return;
}

// Note: this plugin intentionally does NOT override server PHP limits
// (upload_max_filesize, post_max_size, memory_limit, max_execution_time) at
// runtime. Those are environment concerns; overriding them via ini_set() is
// disallowed by the WordPress.org plugin guidelines (and two of the directives
// cannot be changed at runtime anyway). Configure them at the server/host level
// if large uploads require higher limits.

// Check if an uploader has reached the maximum number of allowed files.
if (!function_exists('wpu_check_uploader_limit')) {
    function wpu_check_uploader_limit($uploader_name) {
        global $wpdb;
        $settings = get_option('wpu_settings', array());
        $max_files = (is_array($settings) && isset($settings['max_files']))
            ? absint($settings['max_files'])
            : 200;
        if ($max_files < 1) {
            $max_files = 200;
        }
        $table_name = $wpdb->prefix . 'wedding_photos';
        $count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE uploader_name = %s",
            $uploader_name
        ));
        return $count < $max_files;
    }
}

// Add filter to increase upload size limit in WordPress
if (!function_exists('wpu_filter_upload_size_limit')) {
    function wpu_filter_upload_size_limit($size) {
        return 200 * 1024 * 1024; // 200MB in bytes
    }
    add_filter('upload_size_limit', 'wpu_filter_upload_size_limit', 20);
}

// Allow the media types this plugin supports. Merge into WordPress's existing
// allowed types rather than replacing the whole map, so the plugin does not
// silently disable other upload types (e.g. PDF) site-wide.
if (!function_exists('wpu_upload_mimes')) {
function wpu_upload_mimes($mimes) {
    $wpu_mimes = array(
        'heif|heic'    => 'image/heif',
        'mp4'          => 'video/mp4',
        'mov'          => 'video/quicktime',
        'avi'          => 'video/x-msvideo',
        'mkv'          => 'video/x-matroska',
        'webm'         => 'video/webm'
    );
    return array_merge((array) $mimes, $wpu_mimes);
}
add_filter('upload_mimes', 'wpu_upload_mimes', 10, 1);
}

// Additional security check for uploaded files
if (!function_exists('wpu_check_file_type')) {
function wpu_check_file_type($file, $filename, $mimes) {
    if (empty($file['ext']) || empty($file['type'])) {
        return $file;
    }
    
    $valid_extensions = array('jpg', 'jpeg', 'png', 'heif', 'heic', 'mp4', 'mov', 'avi', 'mkv', 'webm');
    
    if (!in_array($file['ext'], $valid_extensions)) {
        $file['error'] = 'Sorry, this file type is not permitted for security reasons.';
    }
    
    return $file;
}
add_filter('wp_check_filetype_and_ext', 'wpu_check_file_type', 10, 3);
}
