<?php
/**
 * Fired during plugin activation
 */
class WPU_Activator {
    /**
     * Create necessary database tables and set up initial plugin options
     * Also handles version upgrades while preserving existing data
     */
    public static function activate() {
        global $wpdb;
        
        // Check if this is an upgrade from a previous version
        $current_version = get_option('wpu_version', '');
        $is_upgrade = !empty($current_version) && version_compare($current_version, WPU_VERSION, '<');
        
        // Create custom database table for wedding photos
        $table_name = $wpdb->prefix . 'wedding_photos';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            image_id bigint(20) NOT NULL,
            uploader_name varchar(100) NOT NULL,
            filename varchar(255) NOT NULL,
            file_type varchar(20) DEFAULT 'photo' NOT NULL,
            date_uploaded datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            status varchar(20) DEFAULT 'pending' NOT NULL,
            PRIMARY KEY  (id),
            KEY image_id (image_id),
            KEY status (status),
            KEY file_type (file_type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Handle version-specific upgrades
        if ($is_upgrade) {
            self::handle_version_upgrade($current_version);
        }
        
        // Set default options (only if not already set)
        $existing_settings = get_option('wpu_settings');
        if (!$existing_settings) {
            $default_options = array(
                'max_file_size' => 200, // MB
                'max_files' => 200,
                'allowed_types' => array('jpg', 'jpeg', 'png', 'heif', 'heic', 'mp4', 'mov', 'avi', 'mkv', 'webm'),
                'auto_approve' => false,
                'notification_email' => get_option('admin_email'),
                'upload_path' => 'wedding-photos',
                'gallery_columns' => 3,
                'gallery_gutter' => 20,
                'show_uploader_info' => true,
                'enable_video_uploads' => true,
                'video_max_size' => 500, // MB - larger limit for videos
                'default_gallery_filter' => 'both', // photos, videos, or both
                'default_gallery_sort' => 'date_desc' // date_desc, date_asc, name_asc, name_desc
            );
            
            add_option('wpu_settings', $default_options);
        }
        
        // Update the version option
        update_option('wpu_version', WPU_VERSION);
        
        // Create upload directory
        $upload_dir = wp_upload_dir();
        $wpu_upload_dir = $upload_dir['basedir'] . '/wedding-photos';
        
        if (!file_exists($wpu_upload_dir)) {
            wp_mkdir_p($wpu_upload_dir);
        }
        
        // Add .htaccess protection to upload directory
        $htaccess_content = "Options -Indexes\n";
        file_put_contents($wpu_upload_dir . '/.htaccess', $htaccess_content);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Handle version-specific upgrades
     */
    private static function handle_version_upgrade($from_version) {
        // Upgrade from 1.0.0 to 1.0.1
        if (version_compare($from_version, '1.0.1', '<')) {
            // No database schema changes needed for 1.0.1
            // Data preservation is handled by removing deletion from deactivator
            
            // Log the upgrade for debugging
            error_log('Wedding Photo Uploader: Upgraded from ' . $from_version . ' to ' . WPU_VERSION);
        }
        
        // Upgrade from 1.0.1 to 1.0.2
        if (version_compare($from_version, '1.0.2', '<')) {
            // No database schema changes needed for 1.0.2
            // Added upload progress tracking feature
            
            // Log the upgrade for debugging
            error_log('Wedding Photo Uploader: Upgraded from ' . $from_version . ' to ' . WPU_VERSION);
        }
        
        // Upgrade from 1.0.2 to 1.0.3
        if (version_compare($from_version, '1.0.3', '<')) {
            // No database schema changes needed for 1.0.3
            // Fixed admin page conflicts by adding proper script/style restrictions
            
            // Log the upgrade for debugging
            error_log('Wedding Photo Uploader: Upgraded from ' . $from_version . ' to ' . WPU_VERSION);
        }
        
        // Upgrade from 1.0.3 to 1.0.4
        if (version_compare($from_version, '1.0.4', '<')) {
            // No database schema changes needed for 1.0.4
            // Improved upload progress tracking with individual file uploads
            
            // Log the upgrade for debugging
            error_log('Wedding Photo Uploader: Upgraded from ' . $from_version . ' to ' . WPU_VERSION);
        }
        
        // Upgrade from 1.0.4 to 1.0.5
        if (version_compare($from_version, '1.0.5', '<')) {
            // No database schema changes needed for 1.0.5
            // Fixed JSON parse errors and improved error handling
            
            // Log the upgrade for debugging
            error_log('Wedding Photo Uploader: Upgraded from ' . $from_version . ' to ' . WPU_VERSION);
        }
        
        // Upgrade from 1.0.5 to 1.0.6
        if (version_compare($from_version, '1.0.6', '<')) {
            // Add file_type column to existing tables
            global $wpdb;
            $table_name = $wpdb->prefix . 'wedding_photos';
            
            // Check if column already exists
            // Note: Table names cannot be parameterized in prepared statements
            $column_exists = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM `$table_name` LIKE %s", 'file_type'));
            
            if (empty($column_exists)) {
                // Add file_type column
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN file_type varchar(20) DEFAULT 'photo' NOT NULL AFTER filename");
                
                // Add index for file_type
                $wpdb->query("ALTER TABLE $table_name ADD INDEX file_type (file_type)");
                
                // Update existing records to have 'photo' as file_type (since they're all photos)
                $wpdb->query("UPDATE $table_name SET file_type = 'photo' WHERE file_type = 'photo'");
                
                // Log the upgrade
                error_log('Wedding Photo Uploader: Added file_type column during upgrade to ' . WPU_VERSION);
            }
            
            // Update settings to include video support
            $current_settings = get_option('wpu_settings', array());
            $new_settings = array_merge($current_settings, array(
                'allowed_types' => array('jpg', 'jpeg', 'png', 'heif', 'heic', 'mp4', 'mov', 'avi', 'mkv', 'webm'),
                'enable_video_uploads' => true,
                'video_max_size' => 500,
                'default_gallery_filter' => 'both',
                'default_gallery_sort' => 'date_desc'
            ));
            update_option('wpu_settings', $new_settings);
            
            // Log the upgrade for debugging
            error_log('Wedding Photo Uploader: Upgraded from ' . $from_version . ' to ' . WPU_VERSION . ' with video support');
        }
        
        // Upgrade from 1.0.6 to 1.0.7
        if (version_compare($from_version, '1.0.7', '<')) {
            // Version 1.0.7 includes:
            // - Security enhancements (path traversal protection, improved database queries)
            // - UI text updates (photos -> media terminology)
            // - Enhanced file path validation
            // - WordPress.org security compliance improvements
            
            // No database schema changes needed for 1.0.7
            // All improvements are code-level security and UX enhancements
            
            // Log the upgrade for debugging
            error_log('Wedding Photo Uploader: Upgraded from ' . $from_version . ' to ' . WPU_VERSION . ' with security enhancements');
        }
        
        // Upgrade from 1.0.7 to 1.0.8
        if (version_compare($from_version, '1.0.8', '<')) {
            // Version 1.0.8 includes:
            // - Complete terminology update from "photos" to "media" throughout the plugin
            // - Fixed admin interface redirect issues (approve/reject buttons)
            // - Enhanced upload success messages with proper file/media terminology
            // - Improved admin navigation and tab handling
            
            // No database schema changes needed for 1.0.8
            // All improvements are UI/UX enhancements and bug fixes
            
            // Log the upgrade for debugging
            error_log('Wedding Photo Uploader: Upgraded from ' . $from_version . ' to ' . WPU_VERSION . ' with UI improvements and bug fixes');
        }
    }
} 