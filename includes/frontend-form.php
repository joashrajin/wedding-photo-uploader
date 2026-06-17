<?php
/**
 * Frontend form handler for wedding photo uploads
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

// Get upload directory
$upload_dir = wp_upload_dir();
$wpu_upload_dir = $upload_dir['basedir'] . '/wedding-photos';

// Ensure upload directory exists and is writable
if (!file_exists($wpu_upload_dir)) {
    wp_mkdir_p($wpu_upload_dir);
}

if (!is_writable($wpu_upload_dir)) {
    error_log('Wedding Photo Uploader: Upload directory is not writable: ' . $wpu_upload_dir);
}

?>
<div class="wpu-upload-form">
    <form id="wpu-upload-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('wpu_upload_nonce', 'wpu_nonce'); ?>
        
        <div class="wpu-form-group">
            <label for="uploader_name"><?php esc_html_e('Your Name', 'wedding-photo-uploader'); ?> *</label>
            <input type="text" id="uploader_name" name="uploader_name" required>
        </div>

        <div class="wpu-form-group">
            <label for="photo_upload"><?php esc_html_e('Select Photos', 'wedding-photo-uploader'); ?> *</label>
            <div class="drag-drop-area">
                <div class="drag-drop-message">
                    <i class="dashicons dashicons-upload"></i>
                    <p><?php esc_html_e('Drag & drop photos here or click to select', 'wedding-photo-uploader'); ?></p>
                    <p class="wpu-help-text"><?php esc_html_e('Maximum file size: 200MB per file. Supported formats: JPG, PNG, HEIF/HEIC', 'wedding-photo-uploader'); ?></p>
                </div>
                <input type="file" id="photo_upload" name="photo_upload[]" multiple 
                       accept="image/jpeg,image/png,image/heif,image/heic" required>
            </div>
        </div>

        <button type="submit" class="wpu-submit-button">
                            <span class="button-text"><?php esc_html_e('Upload Media', 'wedding-photo-uploader'); ?></span>
            <span class="spinner"></span>
        </button>

        <div id="wpu-upload-status" class="wpu-status-message"></div>
    </form>
</div>

<style>
.wpu-upload-form {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.wpu-form-group {
    margin-bottom: 20px;
}

.wpu-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.wpu-form-group input[type="text"] {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.drag-drop-area {
    border: 2px dashed #ddd;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    background: #f9f9f9;
    position: relative;
    transition: all 0.3s ease;
}

.drag-drop-area.is-dragging {
    background: #e9f5ff;
    border-color: #0073aa;
}

.drag-drop-message {
    margin-bottom: 10px;
}

.drag-drop-message .dashicons {
    font-size: 30px;
    width: 30px;
    height: 30px;
    color: #0073aa;
}

.drag-drop-message p {
    margin: 5px 0;
}

.wpu-help-text {
    font-size: 12px;
    color: #666;
}

.drag-drop-area input[type="file"] {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    opacity: 0;
    cursor: pointer;
}

.wpu-submit-button {
    background: #0073aa;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.3s ease;
}

.wpu-submit-button:hover {
    background: #005177;
}

.wpu-submit-button:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.wpu-status-message {
    margin-top: 15px;
    padding: 10px;
    border-radius: 4px;
    display: none;
}

.wpu-status-message.success {
    display: block;
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.wpu-status-message.error {
    display: block;
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style>

<script>
jQuery(document).ready(function($) {
    const form = $('#wpu-upload-form');
    const dragArea = $('.drag-drop-area');
    const statusMessage = $('#wpu-upload-status');
    const submitButton = $('.wpu-submit-button');

    // Drag and drop functionality
    dragArea.on('dragenter dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('is-dragging');
    });

    dragArea.on('dragleave drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('is-dragging');
    });

    // Form submission
    form.on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'wpu_upload_photos');

        // Validate files
        const files = $('#photo_upload')[0].files;
        if (files.length === 0) {
            showMessage('error', '<?php esc_html_e('Please select at least one file to upload.', 'wedding-photo-uploader'); ?>');
            return;
        }

        // Check file size and type
        for (let i = 0; i < files.length; i++) {
            if (files[i].size > 200 * 1024 * 1024) {
                showMessage('error', `${files[i].name} <?php esc_html_e('is too large. Maximum size is 200MB.', 'wedding-photo-uploader'); ?>`);
                return;
            }
            
            const validTypes = ['image/jpeg', 'image/png', 'image/heif', 'image/heic'];
            if (!validTypes.includes(files[i].type)) {
                showMessage('error', `${files[i].name} <?php esc_html_e('is not a valid image format.', 'wedding-photo-uploader'); ?>`);
                return;
            }
        }

        // Disable submit button and show loading state
        submitButton.prop('disabled', true).text('<?php esc_html_e('Uploading...', 'wedding-photo-uploader'); ?>');
        showMessage('', ''); // Clear any previous messages

        // Send AJAX request
        $.ajax({
            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showMessage('success', response.data);
                    form[0].reset();
                } else {
                    showMessage('error', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('Upload error:', error);
                showMessage('error', '<?php esc_html_e('An error occurred while uploading the files. Please try again.', 'wedding-photo-uploader'); ?>');
            },
            complete: function() {
                                    submitButton.prop('disabled', false).text('<?php esc_html_e('Upload Media', 'wedding-photo-uploader'); ?>');
            }
        });
    });

    // Helper function to show messages
    function showMessage(type, text) {
        statusMessage.removeClass('success error').addClass(type).html(text);
        if (type) {
            statusMessage.show();
        } else {
            statusMessage.hide();
        }
    }
});
</script> 