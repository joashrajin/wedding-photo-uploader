<?php
/**
 * Plugin Name: Wedding Photo Uploader
 * Plugin URI: https://example.com/wedding-photo-uploader
 * Description: A WordPress plugin that allows wedding guests to upload their photos and videos. Features include photo/video moderation, gallery display with filtering, and email notifications.
 * Version: 1.1.6
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
define('WPU_VERSION', '1.1.6');

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

// Increase PHP limits for file uploads
if (!function_exists('wpu_increase_upload_limits')) {
function wpu_increase_upload_limits() {
    @ini_set('upload_max_filesize', '200M');
    @ini_set('post_max_size', '200M');
    @ini_set('memory_limit', '512M');
    @ini_set('max_input_time', '300');
    @ini_set('max_execution_time', '300');
}
add_action('init', 'wpu_increase_upload_limits');
}

// Check if uploader has reached the maximum number of photos
if (!function_exists('wpu_check_uploader_limit')) {
    function wpu_check_uploader_limit($uploader_name) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wedding_photos';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE uploader_name = %s",
            $uploader_name
        ));
        return $count < 200;
    }
}

// Add filter to increase upload size limit in WordPress
if (!function_exists('wpu_filter_upload_size_limit')) {
    function wpu_filter_upload_size_limit($size) {
        return 200 * 1024 * 1024; // 200MB in bytes
    }
    add_filter('upload_size_limit', 'wpu_filter_upload_size_limit', 20);
}

// Filter uploaded file types
if (!function_exists('wpu_upload_mimes')) {
function wpu_upload_mimes($mimes) {
    return array(
        'jpg|jpeg|jpe' => 'image/jpeg',
        'png'          => 'image/png',
        'heif|heic'    => 'image/heif',
        'mp4'          => 'video/mp4',
        'mov'          => 'video/quicktime',
        'avi'          => 'video/x-msvideo',
        'mkv'          => 'video/x-matroska',
        'webm'         => 'video/webm'
    );
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

// Check HEIF support
if (!function_exists('wpu_check_heif_support')) {
function wpu_check_heif_support() {
    $has_imagemagick = class_exists('Imagick');
    $has_exif = function_exists('exif_read_data');
    $php_version_ok = version_compare(PHP_VERSION, '7.4.0', '>=');
    
    return $has_imagemagick && $has_exif && $php_version_ok;
}
} 