<?php
/**
 * Check if the environment meets the plugin requirements
 *
 * @return bool
 */
function wpu_check_requirements() {
    $requirements_met = true;
    $messages = array();

    // Check PHP version
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        $requirements_met = false;
        $messages[] = sprintf(
            __('Wedding Photo Uploader requires PHP version 7.4 or higher. Current version: %s', 'wedding-photo-uploader'),
            PHP_VERSION
        );
    }

    // Check WordPress version
    if (version_compare($GLOBALS['wp_version'], '5.8', '<')) {
        $requirements_met = false;
        $messages[] = sprintf(
            __('Wedding Photo Uploader requires WordPress version 5.8 or higher. Current version: %s', 'wedding-photo-uploader'),
            $GLOBALS['wp_version']
        );
    }

    // Check for required PHP extensions
    $required_extensions = array('gd', 'exif');
    foreach ($required_extensions as $ext) {
        if (!extension_loaded($ext)) {
            $requirements_met = false;
            $messages[] = sprintf(
                __('Wedding Photo Uploader requires the PHP %s extension to be installed.', 'wedding-photo-uploader'),
                $ext
            );
        }
    }

    // Display admin notices if requirements are not met
    if (!$requirements_met) {
        add_action('admin_notices', function() use ($messages) {
            ?>
            <div class="notice notice-error">
                <p><strong><?php _e('Wedding Photo Uploader Requirements Not Met', 'wedding-photo-uploader'); ?></strong></p>
                <ul>
                    <?php foreach ($messages as $message): ?>
                        <li><?php echo esc_html($message); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php
        });
    }

    return $requirements_met;
} 