<?php
/**
 * Fired during plugin deactivation
 */
class WPU_Deactivator {
    /**
     * Clean up temporary plugin data on deactivation
     * NOTE: We preserve the wedding_photos table and uploaded files
     * to ensure data persistence during updates and deactivation
     */
    public static function deactivate() {
        // Clear any cached data
        wp_cache_flush();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Note: We no longer drop the wedding_photos table or remove settings
        // This ensures data is preserved during plugin updates and deactivation
        // Data is only removed during explicit uninstallation via uninstall.php
    }
} 