<?php
/**
 * Fired when the plugin is uninstalled.
 * 
 * IMPORTANT: This uninstall script preserves all uploaded media files.
 * Only plugin settings and database tables are removed.
 * 
 * The wedding photos and videos remain in /wp-content/uploads/wedding-photos/
 * so you don't lose your valuable media when uninstalling the plugin.
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

global $wpdb;

// Remove plugin from active plugins
$active_plugins = get_option('active_plugins');
if (is_array($active_plugins)) {
    $active_plugins = array_diff($active_plugins, array('wedding-photo-uploader/wedding-photo-uploader.php'));
    update_option('active_plugins', array_values($active_plugins));
}

// Remove plugin options and settings
delete_option('wpu_version');
delete_option('wpu_settings');

// Remove any transients or cached data
delete_transient('wpu_photo_counts');
delete_transient('wpu_gallery_cache');

// Remove plugin tables
$table_name = $wpdb->prefix . 'wedding_photos';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// Clear any cached data
wp_cache_flush();

// Flush rewrite rules
flush_rewrite_rules();

// Log the preservation of media files
error_log('WPU: Plugin uninstalled. Media files preserved in /wp-content/uploads/wedding-photos/');

/**
 * MEDIA FILES PRESERVATION NOTICE:
 * 
 * All uploaded wedding photos and videos have been preserved in:
 * /wp-content/uploads/wedding-photos/
 * 
 * These files are NOT deleted during uninstallation to prevent data loss.
 * 
 * If you want to remove the media files as well, you can:
 * 1. Manually delete the /wp-content/uploads/wedding-photos/ directory
 * 2. Or use the code below (uncomment to enable):
 */

/*
// UNCOMMENT THIS BLOCK ONLY IF YOU WANT TO DELETE ALL MEDIA FILES
// WARNING: This will permanently delete all uploaded photos and videos!

$upload_dir = wp_upload_dir();
$wpu_upload_dir = $upload_dir['basedir'] . '/wedding-photos';
if (is_dir($wpu_upload_dir)) {
    // Get all files in the directory
    $files = glob("$wpu_upload_dir/*.*");
    
    // Delete all files
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    
    // Remove the directory
    rmdir($wpu_upload_dir);
    
    error_log('WPU: Media files deleted from /wp-content/uploads/wedding-photos/');
}
*/ 