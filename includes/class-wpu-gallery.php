<?php
/**
 * Gallery block handler
 */
class WPU_Gallery {
    /**
     * Initialize the gallery block
     */
    public function __construct() {
        add_action('init', array($this, 'register_block'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }

    /**
     * Register the gallery block
     */
    public function register_block() {
        if (!function_exists('register_block_type')) {
            return;
        }

        // Register block script
        wp_register_script(
            'wedding-photo-uploader-gallery-editor',
            esc_url(WPU_PLUGIN_URL . 'blocks/gallery/build/index.js'),
            array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'),
            WPU_VERSION
        );

        // Register block editor styles
        wp_register_style(
            'wedding-photo-uploader-gallery-editor',
            esc_url(WPU_PLUGIN_URL . 'blocks/gallery/build/index.css'),
            array('wp-edit-blocks'),
            WPU_VERSION
        );

        // Register block styles
        wp_register_style(
            'wedding-photo-uploader-gallery',
            esc_url(WPU_PLUGIN_URL . 'blocks/gallery/build/style-index.css'),
            array(),
            WPU_VERSION
        );

        // Register the block
        register_block_type(WPU_PLUGIN_DIR . 'blocks/gallery/block.json', array(
            'render_callback' => array($this, 'render_block')
        ));
    }

    /**
     * Enqueue editor assets
     */
    public function enqueue_editor_assets() {
        // Only enqueue in block editor context
        if (!current_user_can('edit_posts') || !is_admin()) {
            return;
        }

        // Additional check to ensure we're in the block editor context
        $screen = get_current_screen();
        if (!$screen || (!$screen->is_block_editor() && $screen->base !== 'post')) {
            return;
        }

        // Enqueue block editor script
        wp_enqueue_script('wedding-photo-uploader-gallery-editor');
        
        // Enqueue block editor styles
        wp_enqueue_style('wedding-photo-uploader-gallery-editor');
        
        // Localize script with any data needed in the editor
        wp_localize_script('wedding-photo-uploader-gallery-editor', 'wpuGalleryData', array(
            'imageSizes' => $this->get_available_image_sizes(),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpu_gallery_nonce'),
            'canEdit' => current_user_can('edit_posts')
        ));
    }

    /**
     * Enqueue frontend assets for lightbox
     */
    public function enqueue_frontend_assets() {
        // Only enqueue on frontend pages that might have the gallery block
        if (is_admin()) {
            return;
        }

        // SimpleLightbox CSS (bundled locally — WordPress.org requires assets served
        // from the plugin, not a third-party CDN).
        wp_enqueue_style(
            'simplelightbox',
            WPU_PLUGIN_URL . 'assets/css/simple-lightbox.min.css',
            array(),
            '2.14.2'
        );

        // SimpleLightbox JS (bundled locally).
        wp_enqueue_script(
            'simplelightbox',
            WPU_PLUGIN_URL . 'assets/js/simple-lightbox.min.js',
            array('jquery'),
            '2.14.2',
            true
        );

        // Custom lightbox initialization and filtering functionality
        wp_add_inline_script('simplelightbox', '
            jQuery(document).ready(function($) {
                // Initialize SimpleLightbox for photos
                new SimpleLightbox(".wedding-gallery a.gallery-image", {
                    captionPosition: "bottom",
                    animationSpeed: 250,
                    fadeSpeed: 300,
                    showCounter: true,
                    closeText: "×",
                    navText: ["←","→"],
                    alertError: "The image could not be loaded"
                });
                
                // Create video modal if it doesn\'t exist
                if ($("#wpu-video-modal").length === 0) {
                    $("body").append(`
                        <div id="wpu-video-modal" class="video-modal" style="display: none; position: fixed; z-index: 999999; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.9); backdrop-filter: blur(5px);">
                            <div class="video-modal-content" style="margin: auto; display: block; max-width: 90%; max-height: 90vh; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); box-shadow: 0 0 20px rgba(0,0,0,0.3);">
                                <span class="video-modal-close" style="position: absolute; right: 25px; top: 10px; color: #f1f1f1; font-size: 40px; font-weight: bold; cursor: pointer; transition: color 0.2s ease; z-index: 1000000;">&times;</span>
                                <video id="wpu-modal-video" controls style="width: 100%; height: auto; max-height: 90vh;">
                                    <source src="" type="">
                                    Your browser does not support the video tag.
                                </video>
                                <div class="video-modal-caption" style="color: white; text-align: center; padding: 20px; font-size: 16px;"></div>
                            </div>
                        </div>
                    `);
                }
                
                // Video modal functionality
                var videoModal = $("#wpu-video-modal");
                var modalVideo = $("#wpu-modal-video");
                var modalCaption = $(".video-modal-caption");
                var closeBtn = $(".video-modal-close");
                
                // Open video modal when clicking on video container
                $(document).on("click", ".video-container", function() {
                    var videoUrl = $(this).data("video-url");
                    var videoType = $(this).data("video-type");
                    var caption = $(this).data("caption");
                    
                    // Pause all other videos first (thumbnails and modal)
                    $("video").each(function() {
                        if (!this.paused) {
                            this.pause();
                            this.currentTime = 0;
                        }
                    });
                    
                    // Set video source
                    modalVideo.find("source").attr("src", videoUrl).attr("type", videoType);
                    modalVideo[0].load(); // Reload video element
                    
                    // Set caption
                    modalCaption.text(caption);
                    
                    // Show modal
                    videoModal.fadeIn(300);
                    
                    // Auto-play the video
                    modalVideo[0].play();
                });
                
                // Close video modal
                function closeVideoModal() {
                    videoModal.fadeOut(300);
                    modalVideo[0].pause();
                    modalVideo[0].currentTime = 0;
                }
                
                // Close modal when clicking close button
                $(document).on("click", ".video-modal-close", closeVideoModal);
                
                // Close modal when clicking outside video
                $(document).on("click", "#wpu-video-modal", function(e) {
                    if (e.target === this) {
                        closeVideoModal();
                    }
                });
                
                // Close modal with Escape key
                $(document).keydown(function(e) {
                    if (e.key === "Escape" && videoModal.is(":visible")) {
                        closeVideoModal();
                    }
                });
                
                // Global video management - pause other videos when any video starts playing
                $(document).on("play", "video", function() {
                    var playingVideo = this;
                    
                    // Pause all other videos
                    $("video").each(function() {
                        if (this !== playingVideo && !this.paused) {
                            this.pause();
                        }
                    });
                });
                
                // Prevent thumbnail videos from showing controls when clicked
                $(document).on("click", ".video-thumbnail video", function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                });
                
                // Gallery filtering
                $(document).on("click", ".gallery-filters .filter-btn", function() {
                    var filterType = $(this).data("filter");
                    var galleryContainer = $(this).closest(".wedding-gallery-container");
                    
                    // Update active button
                    galleryContainer.find(".gallery-filters .filter-btn").removeClass("active");
                    $(this).addClass("active");
                    
                    // Filter gallery items
                    if (filterType === "all") {
                        galleryContainer.find(".gallery-item").show();
                    } else {
                        galleryContainer.find(".gallery-item").hide();
                        galleryContainer.find(".gallery-item[data-type=\"" + filterType + "\"]").show();
                    }
                });
            });
        ');
    }

    /**
     * Get available image sizes
     */
    private function get_available_image_sizes() {
        $sizes = array();
        foreach (get_intermediate_image_sizes() as $size) {
            $sizes[$size] = sanitize_key($size);
        }
        $sizes['full'] = 'full';
        return $sizes;
    }

    /**
     * Render the gallery block
     */
    public function render_block($attributes, $content) {
        // Sanitize and validate attributes
        $attributes = $this->sanitize_attributes($attributes);
        
        // Get approved photos and videos
        global $wpdb;
        $table_name = $wpdb->prefix . 'wedding_photos';
        
        // Build WHERE clause for filtering
        $where_conditions = array("wp.status = 'approved'");
        $query_params = array();
        
        // Add file type filter
        if ($attributes['filterType'] !== 'both') {
            $where_conditions[] = "wp.file_type = %s";
            $query_params[] = $attributes['filterType'];
        }
        
        // Build ORDER BY clause
        $order_by = $this->get_order_by_clause($attributes['sortBy']);
        
        // Build complete query
        $query = "SELECT wp.id, wp.image_id, wp.uploader_name, wp.date_uploaded, wp.file_type 
                 FROM $table_name wp 
                 WHERE " . implode(' AND ', $where_conditions) . " 
                 ORDER BY " . $order_by;
        
        // Execute query
        if (!empty($query_params)) {
            $items = $wpdb->get_results($wpdb->prepare($query, $query_params));
        } else {
            $items = $wpdb->get_results($query);
        }

        if (empty($items)) {
            return '<p class="no-items">' . esc_html__('No approved items yet.', 'wedding-photo-uploader') . '</p>';
        }

        // Start output buffering
        ob_start();
        ?>
        <div class="wedding-gallery-container">
            <?php if ($attributes['showFilters']): ?>
                <div class="gallery-filters">
                    <button class="filter-btn active" data-filter="all"><?php esc_html_e('All', 'wedding-photo-uploader'); ?></button>
                    <button class="filter-btn" data-filter="photo"><?php esc_html_e('Photos', 'wedding-photo-uploader'); ?></button>
                    <button class="filter-btn" data-filter="video"><?php esc_html_e('Videos', 'wedding-photo-uploader'); ?></button>
                </div>
            <?php endif; ?>
            
            <div class="wedding-gallery columns-<?php echo esc_attr($attributes['columns']); ?>" 
                 style="--gutter: <?php echo esc_attr($attributes['gutter']); ?>px;">
                <?php 
                foreach ($items as $item): 
                    // Handle both photos and videos
                    if ($item->file_type === 'video') {
                        // For videos, get the video URL
                        $video_url = wp_get_attachment_url($item->image_id);
                        $video_meta = wp_get_attachment_metadata($item->image_id);
                        
                        if (!$video_url) {
                            continue;
                        }
                        
                        $caption = esc_attr(sprintf(
                            /* translators: %s: Uploader's name */
                            __('Video by %s', 'wedding-photo-uploader'),
                            $item->uploader_name
                        ));
                    ?>
                        <div class="gallery-item video-item" data-type="video">
                                                    <div class="video-container" 
                             data-video-url="<?php echo esc_url($video_url); ?>"
                             data-video-type="<?php echo esc_attr($this->get_browser_compatible_mime_type(get_post_mime_type($item->image_id))); ?>"
                             data-caption="<?php echo $caption; ?>">
                            <div class="video-thumbnail" style="width: 100%; height: 100%; background: #000; position: relative; cursor: pointer;">
                                <video preload="metadata" style="width: 100%; height: 100%; object-fit: cover;" muted onclick="return false;">
                                    <source src="<?php echo esc_url($video_url); ?>" type="<?php echo esc_attr($this->get_browser_compatible_mime_type(get_post_mime_type($item->image_id))); ?>">
                                    <?php esc_html_e('Your browser does not support the video tag.', 'wedding-photo-uploader'); ?>
                                </video>
                                    <div class="video-overlay">
                                        <div class="play-button">
                                            <i class="dashicons dashicons-controls-play" aria-hidden="true"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php if ($attributes['showUploaderInfo']): ?>
                                <div class="item-meta">
                                    <span class="uploader-name"><?php echo esc_html($item->uploader_name); ?></span>
                                    <span class="upload-date"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($item->date_uploaded))); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php
                    } else {
                        // For photos, use the existing logic
                        $image = wp_get_attachment_image_src($item->image_id, $attributes['imageSize']);
                        $full_image = wp_get_attachment_image_src($item->image_id, 'full');
                        if (!$image || !wp_attachment_is_image($item->image_id)) {
                            continue;
                        }
                        
                        // Verify image is from our plugin
                        $attachment_meta = wp_get_attachment_metadata($item->image_id);
                        if (empty($attachment_meta)) {
                            continue;
                        }

                        $caption = esc_attr(sprintf(
                            /* translators: %s: Uploader's name */
                            __('Photo by %s', 'wedding-photo-uploader'),
                            $item->uploader_name
                        ));
                    ?>
                        <div class="gallery-item photo-item" data-type="photo">
                            <a href="<?php echo esc_url($full_image[0]); ?>" 
                               class="gallery-image"
                               data-caption="<?php echo $caption; ?>">
                                <img src="<?php echo esc_url($image[0]); ?>" 
                                     alt="<?php echo $caption; ?>"
                                     width="<?php echo esc_attr($image[1]); ?>"
                                     height="<?php echo esc_attr($image[2]); ?>"
                                     loading="lazy">
                            </a>
                            <?php if ($attributes['showUploaderInfo']): ?>
                                <div class="item-meta">
                                    <span class="uploader-name"><?php echo esc_html($item->uploader_name); ?></span>
                                    <span class="upload-date"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($item->date_uploaded))); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php
                    } // End of photo/video if-else
                    endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Sanitize block attributes
     */
    private function sanitize_attributes($attributes) {
        return array(
            'columns' => absint($attributes['columns'] ?? 3),
            'gutter' => absint($attributes['gutter'] ?? 20),
            'imageSize' => sanitize_key($attributes['imageSize'] ?? 'medium'),
            'showUploaderInfo' => (bool) ($attributes['showUploaderInfo'] ?? true),
            'filterType' => sanitize_key($attributes['filterType'] ?? 'both'),
            'sortBy' => sanitize_key($attributes['sortBy'] ?? 'date_desc'),
            'showFilters' => (bool) ($attributes['showFilters'] ?? true)
        );
    }
    
    /**
     * Get ORDER BY clause for different sorting options
     */
    private function get_order_by_clause($sort_by) {
        switch ($sort_by) {
            case 'date_asc':
                return 'wp.date_uploaded ASC';
            case 'date_desc':
                return 'wp.date_uploaded DESC';
            case 'name_asc':
                return 'wp.uploader_name ASC';
            case 'name_desc':
                return 'wp.uploader_name DESC';
            case 'filename_asc':
                return 'wp.filename ASC';
            case 'filename_desc':
                return 'wp.filename DESC';
            default:
                return 'wp.date_uploaded DESC';
        }
    }
    
    /**
     * Get browser-compatible MIME type for video playback
     * 
     * @param string $mime_type Original MIME type from WordPress
     * @return string Browser-compatible MIME type
     */
    private function get_browser_compatible_mime_type($mime_type) {
        // Convert server-side MIME types to browser-compatible ones
        $mime_type_map = array(
            'video/quicktime' => 'video/mp4',  // .mov files work better as video/mp4 in browsers
            'video/x-msvideo' => 'video/mp4',  // .avi files work better as video/mp4 in browsers
            'video/x-matroska' => 'video/webm', // .mkv files work better as video/webm in browsers
        );
        
        return isset($mime_type_map[$mime_type]) ? $mime_type_map[$mime_type] : $mime_type;
    }
} 