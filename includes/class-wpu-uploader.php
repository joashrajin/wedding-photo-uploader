<?php
/**
 * The uploader block functionality
 */
class WPU_Uploader {
    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        add_action('init', array($this, 'register_block'));
        add_action('wp_ajax_wpu_upload_photos', array($this, 'handle_upload'));
        add_action('wp_ajax_nopriv_wpu_upload_photos', array($this, 'handle_upload'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Register the uploader block
     */
    public function register_block() {
        register_block_type(WPU_PLUGIN_DIR . 'blocks/uploader', array(
            'render_callback' => array($this, 'render_block')
        ));
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Only enqueue on frontend pages that might have the uploader block
        if (is_admin()) {
            return;
        }

        wp_enqueue_style('dashicons');
        wp_enqueue_style('wpu-styles', WPU_PLUGIN_URL . 'assets/css/styles.css', array(), WPU_VERSION);
        wp_enqueue_script('wpu-scripts', WPU_PLUGIN_URL . 'assets/js/scripts.js', array('jquery'), WPU_VERSION, true);
        
        wp_localize_script('wpu-scripts', 'wpu_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpu_upload_nonce')
        ));
    }

    /**
     * Server-side render callback for the block
     */
    public function render_block($attributes, $content) {
        // Get settings
        $settings = get_option('wpu_settings', array());
        
        // Prepare styles
        $wrapper_styles = array();
        if (!empty($attributes['backgroundColor'])) {
            $wrapper_styles[] = 'background-color: ' . esc_attr($attributes['backgroundColor']);
        }
        if (!empty($attributes['textColor'])) {
            $wrapper_styles[] = 'color: ' . esc_attr($attributes['textColor']);
        }
        if (!empty($attributes['fontSize'])) {
            $wrapper_styles[] = 'font-size: ' . esc_attr($attributes['fontSize']) . 'px';
        }
        if (!empty($attributes['padding'])) {
            $padding = $attributes['padding'];
            $wrapper_styles[] = 'padding: ' . esc_attr($padding['top']) . 'px ' . 
                              esc_attr($padding['right']) . 'px ' . 
                              esc_attr($padding['bottom']) . 'px ' . 
                              esc_attr($padding['left']) . 'px';
        }
        if (!empty($attributes['borderRadius'])) {
            $wrapper_styles[] = 'border-radius: ' . esc_attr($attributes['borderRadius']) . 'px';
        }
        
        $style_attribute = !empty($wrapper_styles) ? ' style="' . esc_attr(implode('; ', $wrapper_styles)) . '"' : '';
        
        // Start output buffering
        ob_start();
        ?>
        <div class="wedding-photo-uploader"<?php echo $style_attribute; ?>>
            <h2 class="wedding-photo-uploader-title"><?php echo esc_html($attributes['title'] ?? __('Share Your Wedding Media', 'wedding-photo-uploader')); ?></h2>
            <p class="wedding-photo-uploader-description"><?php echo esc_html($attributes['description'] ?? __('Please upload your wedding photos and videos here. They will be reviewed before being added to the gallery.', 'wedding-photo-uploader')); ?></p>
            
            <form id="wedding-photo-form" class="wpu-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="uploader_name"><?php esc_html_e('Your Name *', 'wedding-photo-uploader'); ?></label>
                    <input type="text" id="uploader_name" name="uploader_name" required>
                </div>
                
                <div class="form-group">
                    <label for="photo_upload"><?php esc_html_e('Select Photos & Videos *', 'wedding-photo-uploader'); ?></label>
                    <div class="drag-drop-area">
                        <div class="drag-drop-message">
                            <i class="dashicons dashicons-upload"></i>
                            <p><?php esc_html_e('Drag & drop photos and videos here or click to select', 'wedding-photo-uploader'); ?></p>
                            <p class="small"><?php 
                                $accepted_formats = $settings['allowed_types'] ?? array('jpg', 'jpeg', 'png');
                                $photo_size = $settings['max_file_size'] ?? 200;
                                $video_size = $settings['video_max_size'] ?? 500;
                                printf(
                                    esc_html__('Accepted formats: %s (Photos: max %dMB, Videos: max %dMB, %d files max)', 'wedding-photo-uploader'),
                                    esc_html(implode(', ', $accepted_formats)),
                                    esc_html($photo_size),
                                    esc_html($video_size),
                                    esc_html($settings['max_files'] ?? 200)
                                );
                            ?></p>
                        </div>
                        <input type="file" id="photo_upload" name="photo_upload[]" multiple 
                               accept="<?php echo esc_attr($this->get_accept_attribute($settings)); ?>" required>
                    </div>
                </div>
                
                <?php wp_nonce_field('wpu_upload_nonce', 'wpu_nonce'); ?>
                
                <div class="form-group">
                    <button type="submit" id="submit-photos" class="wpu-submit-button">
                        <?php echo esc_html($attributes['submitButtonText'] ?? __('Upload Files', 'wedding-photo-uploader')); ?>
                    </button>
                </div>
                
                <div id="upload-messages" class="wpu-status-message"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle photo upload
     */
    public function handle_upload() {
        // Start output buffering to prevent any accidental output
        ob_start();
        
        try {
            // Clear any previous output that might interfere with JSON
            if (ob_get_length()) {
                ob_clean();
            }
            
            // Verify nonce
            if (!isset($_POST['wpu_nonce']) || !wp_verify_nonce($_POST['wpu_nonce'], 'wpu_upload_nonce')) {
                throw new Exception('Security check failed. Please try again.');
            }

            // Validate required fields
            if (empty($_POST['uploader_name'])) {
                throw new Exception('Please provide your name.');
            }

            // Check if files were uploaded
            if (empty($_FILES['photo_upload'])) {
                throw new Exception('Please select at least one file to upload.');
            }

            $uploader_name = sanitize_text_field($_POST['uploader_name']);
            $uploaded_files = array();
            $errors = array();

            // Get plugin settings with error handling
            $settings = get_option('wpu_settings', array());
            if (!is_array($settings)) {
                $settings = array();
            }

            // Handle both single file and multiple file uploads
            $files_to_process = array();
            
            if (is_array($_FILES['photo_upload']['name'])) {
                // Multiple files upload (legacy support)
                $file_count = count($_FILES['photo_upload']['name']);
                for ($i = 0; $i < $file_count; $i++) {
                    $files_to_process[] = array(
                        'name' => $_FILES['photo_upload']['name'][$i],
                        'type' => $_FILES['photo_upload']['type'][$i],
                        'tmp_name' => $_FILES['photo_upload']['tmp_name'][$i],
                        'error' => $_FILES['photo_upload']['error'][$i],
                        'size' => $_FILES['photo_upload']['size'][$i]
                    );
                }
            } else {
                // Single file upload (new approach)
                $files_to_process[] = array(
                    'name' => $_FILES['photo_upload']['name'],
                    'type' => $_FILES['photo_upload']['type'],
                    'tmp_name' => $_FILES['photo_upload']['tmp_name'],
                    'error' => $_FILES['photo_upload']['error'],
                    'size' => $_FILES['photo_upload']['size']
                );
            }

            // Process each file
            foreach ($files_to_process as $file_data) {
                if ($file_data['error'] !== UPLOAD_ERR_OK) {
                    $errors[] = sprintf(
                        'Error uploading "%s": %s',
                        $file_data['name'],
                        $this->get_upload_error_message($file_data['error'])
                    );
                    continue;
                }

                $file = array(
                    'name' => sanitize_file_name($file_data['name']),
                    'type' => sanitize_mime_type($file_data['type']),
                    'tmp_name' => $file_data['tmp_name'],
                    'error' => $file_data['error'],
                    'size' => intval($file_data['size'])
                );

                // Determine file type (photo or video)
                $file_type = $this->get_file_type($file['type']);
                
                // Validate file size using settings (different limits for photos vs videos)
                $max_file_size = $this->get_max_file_size($settings, $file_type);
                if ($file['size'] > $max_file_size) {
                    $max_size_mb = $max_file_size / (1024 * 1024);
                    $errors[] = sprintf('File "%s" is too large. Maximum size for %ss is %dMB.', $file['name'], $file_type, $max_size_mb);
                    continue;
                }

                // Validate file type
                $allowed_types = $this->get_allowed_mime_types($settings);
                if (!in_array($file['type'], $allowed_types)) {
                    $errors[] = sprintf('File "%s" is not a valid file format. Allowed formats: %s', $file['name'], implode(', ', $this->get_allowed_extensions($settings)));
                    continue;
                }

                // Additional file type validation
                if (function_exists('finfo_open')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    if ($finfo) {
                        $mime_type = finfo_file($finfo, $file['tmp_name']);
                        finfo_close($finfo);

                        if ($mime_type && !in_array($mime_type, $allowed_types)) {
                            $errors[] = sprintf('File "%s" appears to be corrupted or not a valid file.', $file['name']);
                            continue;
                        }
                    }
                }

                // Upload file with error handling
                $upload = wp_handle_upload($file, array('test_form' => false));

                if (isset($upload['error'])) {
                    $errors[] = sprintf('Error uploading "%s": %s', $file['name'], $upload['error']);
                    continue;
                }

                if (!isset($upload['file']) || !isset($upload['url'])) {
                    $errors[] = sprintf('Error uploading "%s": Invalid upload response.', $file['name']);
                    continue;
                }

                // Create attachment
                $attachment = array(
                    'post_mime_type' => $file['type'],
                    'post_title' => preg_replace('/\.[^.]+$/', '', $file['name']),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );

                $attach_id = wp_insert_attachment($attachment, $upload['file']);

                if (is_wp_error($attach_id)) {
                    $errors[] = sprintf('Error creating attachment for "%s": %s', $file['name'], $attach_id->get_error_message());
                    continue;
                }

                if (!$attach_id || $attach_id < 1) {
                    $errors[] = sprintf('Error creating attachment for "%s": Invalid attachment ID.', $file['name']);
                    continue;
                }

                // Generate metadata and thumbnails
                if (file_exists(ABSPATH . 'wp-admin/includes/image.php')) {
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
                    wp_update_attachment_metadata($attach_id, $attach_data);
                }

                // Save to custom table with error handling
                global $wpdb;
                $table_name = $wpdb->prefix . 'wedding_photos';
                
                // Verify table exists
                // Note: Table names cannot be parameterized in prepared statements
                $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
                if ($table_exists != $table_name) {
                    $errors[] = sprintf('Database table missing. Please deactivate and reactivate the plugin.');
                    wp_delete_attachment($attach_id, true);
                    continue;
                }
                
                $result = $wpdb->insert(
                    $table_name,
                    array(
                        'image_id' => $attach_id,
                        'uploader_name' => $uploader_name,
                        'filename' => basename($upload['file']),
                        'file_type' => $file_type,
                        'status' => 'pending',
                        'date_uploaded' => current_time('mysql')
                    ),
                    array('%d', '%s', '%s', '%s', '%s', '%s')
                );

                if ($result === false) {
                    $wpdb_error = $wpdb->last_error ? $wpdb->last_error : 'Unknown database error';
                    error_log('Wedding Photo Uploader - Database Error: ' . $wpdb_error);
                    $errors[] = sprintf('Error saving "%s" to database. Please try again.', $file['name']);
                    wp_delete_attachment($attach_id, true);
                    continue;
                }

                $uploaded_files[] = $file['name'];
            }

            if (!empty($errors)) {
                throw new Exception(implode('<br>', $errors));
            }

            if (empty($uploaded_files)) {
                throw new Exception('No files were uploaded successfully.');
            }

            // Clear output buffer before sending JSON
            ob_clean();

            // Return success message
            $success_message = sprintf(
                'Successfully uploaded %d file(s). They will be reviewed by an administrator.',
                count($uploaded_files)
            );

            wp_send_json_success($success_message);

        } catch (Exception $e) {
            // Clear output buffer before sending error JSON
            ob_clean();
            
            // Log the error for debugging
            error_log('Wedding Photo Uploader - Upload Error: ' . $e->getMessage());
            
            wp_send_json_error($e->getMessage());
        } catch (Throwable $e) {
            // Catch any other PHP errors
            ob_clean();
            
            // Log the error for debugging
            error_log('Wedding Photo Uploader - Fatal Error: ' . $e->getMessage());
            
            wp_send_json_error('A system error occurred. Please try again.');
        }
        
        // Cleanup output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
    }

    /**
     * Get upload error message
     */
    private function get_upload_error_message($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk.';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload.';
            default:
                return 'Unknown upload error.';
        }
    }
    
    /**
     * Determine file type based on MIME type
     */
    private function get_file_type($mime_type) {
        $photo_types = array('image/jpeg', 'image/png', 'image/heif', 'image/heic', 'image/gif', 'image/webp');
        $video_types = array('video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska', 'video/webm');
        
        if (in_array($mime_type, $photo_types)) {
            return 'photo';
        } elseif (in_array($mime_type, $video_types)) {
            return 'video';
        }
        
        return 'photo'; // default fallback
    }
    
    /**
     * Get maximum file size based on file type
     */
    private function get_max_file_size($settings, $file_type) {
        if ($file_type === 'video') {
            $max_size = isset($settings['video_max_size']) ? $settings['video_max_size'] : 500;
        } else {
            $max_size = isset($settings['max_file_size']) ? $settings['max_file_size'] : 200;
        }
        
        return $max_size * 1024 * 1024; // Convert MB to bytes
    }
    
    /**
     * Get allowed MIME types based on settings
     */
    private function get_allowed_mime_types($settings) {
        $mime_types = array();
        
        // Get allowed types from settings
        $allowed_types = isset($settings['allowed_types']) ? $settings['allowed_types'] : array('jpg', 'jpeg', 'png');
        
        // Map extensions to MIME types
        $extension_to_mime = array(
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'heif' => 'image/heif',
            'heic' => 'image/heic',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'mkv' => 'video/x-matroska',
            'webm' => 'video/webm'
        );
        
        foreach ($allowed_types as $ext) {
            if (isset($extension_to_mime[$ext])) {
                $mime_types[] = $extension_to_mime[$ext];
            }
        }
        
        return $mime_types;
    }
    
    /**
     * Get allowed extensions based on settings
     */
    private function get_allowed_extensions($settings) {
        return isset($settings['allowed_types']) ? $settings['allowed_types'] : array('jpg', 'jpeg', 'png');
    }
    
    /**
     * Generate accept attribute for file input
     */
    private function get_accept_attribute($settings) {
        $allowed_types = $this->get_allowed_extensions($settings);
        $accept_values = array();
        
        // Map extensions to accept attribute values
        $extension_to_accept = array(
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'heif' => 'image/heif',
            'heic' => 'image/heic',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'mkv' => 'video/x-matroska',
            'webm' => 'video/webm'
        );
        
        foreach ($allowed_types as $ext) {
            if (isset($extension_to_accept[$ext])) {
                $accept_values[] = $extension_to_accept[$ext];
            }
        }
        
        return implode(',', $accept_values);
    }
} 