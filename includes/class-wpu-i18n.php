<?php
/**
 * Define the internationalization functionality
 */
class WPU_i18n {
    /**
     * Load the plugin text domain for translation.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'wedding-photo-uploader',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
    
} 