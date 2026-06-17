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
    
    /**
     * Get translated strings for JavaScript
     */
    public function get_js_translations() {
        return array(
            'uploadSuccess' => __('File uploaded successfully!', 'wedding-photo-uploader'),
            'uploadError' => __('Error uploading file. Please try again.', 'wedding-photo-uploader'),
            'fileTooLarge' => __('File is too large. Maximum size is %sMB.', 'wedding-photo-uploader'),
            'invalidFileType' => __('Invalid file type. Allowed types: %s', 'wedding-photo-uploader'),
            'maxFilesReached' => __('Maximum number of files reached (%d).', 'wedding-photo-uploader'),
            'dragDropText' => __('Drag & drop photos and videos here or click to select', 'wedding-photo-uploader'),
            'selectFiles' => __('Select Files', 'wedding-photo-uploader'),
            'removeFile' => __('Remove', 'wedding-photo-uploader'),
            'uploading' => __('Uploading...', 'wedding-photo-uploader'),
            'uploadComplete' => __('Upload Complete', 'wedding-photo-uploader'),
            'nameRequired' => __('Please enter your name', 'wedding-photo-uploader'),
            'emailRequired' => __('Please enter your email', 'wedding-photo-uploader'),
            'invalidEmail' => __('Please enter a valid email address', 'wedding-photo-uploader'),
            'selectPhotos' => __('Please select at least one file', 'wedding-photo-uploader'),
            'confirmDelete' => __('Are you sure you want to delete this item?', 'wedding-photo-uploader'),
            'approveConfirm' => __('Are you sure you want to approve this item?', 'wedding-photo-uploader'),
            'rejectConfirm' => __('Are you sure you want to reject this item?', 'wedding-photo-uploader'),
            'noPhotos' => __('No media available', 'wedding-photo-uploader'),
            'loading' => __('Loading...', 'wedding-photo-uploader'),
            'error' => __('Error', 'wedding-photo-uploader'),
            'success' => __('Success', 'wedding-photo-uploader'),
            'close' => __('Close', 'wedding-photo-uploader')
        );
    }
} 