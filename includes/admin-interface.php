<?php
/**
 * Admin interface for managing wedding photos
 *
 * @package Wedding_Photo_Uploader
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit('Direct access not permitted.');
}

// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'wedding-photo-uploader'));
}

// Verify it's a valid admin page request
if (!is_admin()) {
    wp_die(esc_html__('Invalid request.', 'wedding-photo-uploader'));
}

// Get photos from database
global $wpdb;
$table_name = $wpdb->prefix . 'wedding_photos';

    // Single-item approve/reject actions are handled by WPU_Admin::handle_photo_actions()
    // (hooked on admin_init, which runs and redirects before this page renders), so the
    // duplicate single-item handler that previously lived here was dead, shadowed code and
    // has been removed to avoid divergent authorization logic. Delete is available via the
    // bulk actions below.

// Handle bulk actions
if (isset($_POST['bulk_action']) && isset($_POST['bulk_nonce'])) {
    
    // Verify nonce
    if (!wp_verify_nonce(sanitize_key($_POST['bulk_nonce']), 'wpu_bulk_action')) {
        wp_die(esc_html__('Security check failed.', 'wedding-photo-uploader'));
    }

    // Verify user capabilities
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to perform this action.', 'wedding-photo-uploader'));
    }

    $bulk_action = sanitize_key($_POST['bulk_action']);
    $photo_ids = isset($_POST['photo_ids']) ? array_map('absint', $_POST['photo_ids']) : array();
    $current_tab = sanitize_key($_POST['current_tab'] ?? 'pending');
    
    // Handle invalid bulk action selection
    if ($bulk_action === '-1' || $bulk_action === '') {
        $error_message = esc_html__('Please select a bulk action.', 'wedding-photo-uploader');
        $redirect_url = admin_url('admin.php?page=wedding-photo-uploader');
        
        echo '<div class="wrap">';
        echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';
        echo '<div class="notice notice-error">';
        echo '<p>' . $error_message . ' ' . esc_html__('Redirecting...', 'wedding-photo-uploader') . '</p>';
        echo '</div>';
        echo '</div>';
        
        echo '<script type="text/javascript">';
        echo 'setTimeout(function() {';
        echo 'window.location.href = "' . esc_url($redirect_url) . '";';
        echo '}, 2000);';
        echo '</script>';
        exit;
    }
    
    // Handle no items selected
    if (empty($photo_ids)) {
        $error_message = esc_html__('Please select at least one item.', 'wedding-photo-uploader');
        $redirect_url = admin_url('admin.php?page=wedding-photo-uploader');
        
        echo '<div class="wrap">';
        echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';
        echo '<div class="notice notice-error">';
        echo '<p>' . $error_message . ' ' . esc_html__('Redirecting...', 'wedding-photo-uploader') . '</p>';
        echo '</div>';
        echo '</div>';
        
        echo '<script type="text/javascript">';
        echo 'setTimeout(function() {';
        echo 'window.location.href = "' . esc_url($redirect_url) . '";';
        echo '}, 2000);';
        echo '</script>';
        exit;
    }
    
    if (in_array($bulk_action, array('approve', 'reject', 'delete'))) {
        $updated_count = 0;
        $error_count = 0;
        
        foreach ($photo_ids as $photo_id) {
            // Validate photo ID
            if (!is_numeric($photo_id) || $photo_id <= 0) {
                $error_count++;
                continue;
            }
            
            switch ($bulk_action) {
                case 'approve':
                    $result = $wpdb->update(
                        $table_name,
                        array('status' => 'approved'),
                        array('id' => $photo_id),
                        array('%s'),
                        array('%d')
                    );
                    if ($result !== false && $result > 0) {
                        $updated_count++;
                    } else {
                        $error_count++;
                    }
                    break;
                    
                case 'reject':
                    $result = $wpdb->update(
                        $table_name,
                        array('status' => 'rejected'),
                        array('id' => $photo_id),
                        array('%s'),
                        array('%d')
                    );
                    if ($result !== false && $result > 0) {
                        $updated_count++;
                    } else {
                        $error_count++;
                    }
                    break;
                    
                case 'delete':
                    // Get file path before deleting
                    $photo = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}wedding_photos WHERE id = %d",
                        $photo_id
                    ));
                    
                    if ($photo) {
                        // Use WordPress upload directory for better security
                        $upload_dir = wp_upload_dir();
                        if (is_array($upload_dir) && isset($upload_dir['basedir'])) {
                            $file_path = $upload_dir['basedir'] . '/wedding-photos/' . sanitize_file_name($photo->filename);
                            
                            // Verify file is within expected directory to prevent directory traversal
                            $real_path = realpath($file_path);
                            $expected_dir = realpath($upload_dir['basedir'] . '/wedding-photos/');
                            
                            if ($real_path && $expected_dir && strpos($real_path, $expected_dir) === 0) {
                                if (file_exists($file_path) && is_file($file_path)) {
                                    wp_delete_file($file_path);
                                }
                            }
                        }
                        
                        $result = $wpdb->delete(
                            $table_name,
                            array('id' => $photo_id),
                            array('%d')
                        );
                        
                        if ($result !== false && $result > 0) {
                            $updated_count++;
                        } else {
                            $error_count++;
                        }
                    } else {
                        $error_count++;
                    }
                    break;
            }
        }
        
        // Instead of immediate redirect, show success message and use JavaScript delayed redirect
        $success_message = sprintf(
            /* translators: %1$d: number of items, %2$s: action performed */
            esc_html__('Successfully %2$s %1$d item(s). Redirecting...', 'wedding-photo-uploader'),
            $updated_count,
            $bulk_action === 'approve' ? 'approved' : ($bulk_action === 'reject' ? 'rejected' : 'deleted')
        );
        
        $redirect_url = admin_url('admin.php?page=wedding-photo-uploader');
        
        echo '<div class="wrap">';
        echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';
        echo '<div class="notice notice-success">';
        echo '<p>' . $success_message . '</p>';
        echo '</div>';
        echo '<div id="wpu-loading" style="text-align: center; padding: 20px;">';
        echo '<div class="spinner is-active" style="visibility: visible; margin: 10px auto; float: none;"></div>';
        echo '<p>' . esc_html__('Processing...', 'wedding-photo-uploader') . '</p>';
        echo '</div>';
        echo '</div>';
        
        echo '<script type="text/javascript">';
        echo 'setTimeout(function() {';
        echo 'window.location.href = "' . esc_url($redirect_url) . '";';
        echo '}, 2000);'; // 2 second delay
        echo '</script>';
        exit;
    } else {
        // Invalid bulk action - redirect with error
        $error_message = esc_html__('Invalid bulk action selected.', 'wedding-photo-uploader');
        $redirect_url = admin_url('admin.php?page=wedding-photo-uploader');
        
        echo '<div class="wrap">';
        echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';
        echo '<div class="notice notice-error">';
        echo '<p>' . $error_message . ' ' . esc_html__('Redirecting...', 'wedding-photo-uploader') . '</p>';
        echo '</div>';
        echo '</div>';
        
        echo '<script type="text/javascript">';
        echo 'setTimeout(function() {';
        echo 'window.location.href = "' . esc_url($redirect_url) . '";';
        echo '}, 2000);';
        echo '</script>';
        exit;
    }
}

// Get current tab with validation
    $valid_tabs = array('pending', 'approved', 'rejected');
    $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'pending';
    if (!in_array($current_tab, $valid_tabs, true)) {
        $current_tab = 'pending';
    }

    // Get photos and videos by status using prepared statements
    $pending_photos = $wpdb->get_results($wpdb->prepare(
        "SELECT wp.* 
        FROM {$wpdb->prefix}wedding_photos wp 
        WHERE wp.status = %s 
        ORDER BY wp.date_uploaded DESC",
        'pending'
    ));

    $approved_photos = $wpdb->get_results($wpdb->prepare(
        "SELECT wp.* 
        FROM {$wpdb->prefix}wedding_photos wp 
        WHERE wp.status = %s 
        ORDER BY wp.date_uploaded DESC",
        'approved'
    ));

    $rejected_photos = $wpdb->get_results($wpdb->prepare(
        "SELECT wp.* 
        FROM {$wpdb->prefix}wedding_photos wp 
        WHERE wp.status = %s 
        ORDER BY wp.date_uploaded DESC",
        'rejected'
    ));

    // Count photos
    $pending_count = count($pending_photos);
    $approved_count = count($approved_photos);
    $rejected_count = count($rejected_photos);

    // Ensure all URLs are properly escaped
    $admin_page_url = admin_url('admin.php');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php if (isset($_GET['updated'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Items updated successfully.', 'wedding-photo-uploader'); ?></p>
        </div>
    <?php endif; ?>



    <?php if (isset($_GET['error'])): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php 
                $error = sanitize_key($_GET['error']);
                switch ($error) {
                    case 'no_action':
                        esc_html_e('Please select a bulk action.', 'wedding-photo-uploader');
                        break;
                    case 'no_items':
                        esc_html_e('Please select at least one item.', 'wedding-photo-uploader');
                        break;
                    case 'invalid_action':
                        esc_html_e('Invalid bulk action selected.', 'wedding-photo-uploader');
                        break;
                    default:
                        esc_html_e('An error occurred. Please try again.', 'wedding-photo-uploader');
                }
            ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['bulk_updated']) && isset($_GET['bulk_action'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php 
                $count = absint($_GET['bulk_updated']);
                $action = sanitize_key($_GET['bulk_action']);
                $action_text = '';
                
                switch ($action) {
                    case 'approve':
                        $action_text = _n('item approved', 'items approved', $count, 'wedding-photo-uploader');
                        break;
                    case 'reject':
                        $action_text = _n('item rejected', 'items rejected', $count, 'wedding-photo-uploader');
                        break;
                    case 'delete':
                        $action_text = _n('item deleted', 'items deleted', $count, 'wedding-photo-uploader');
                        break;
                }
                
                printf(
                    esc_html__('Successfully %s %d %s.', 'wedding-photo-uploader'),
                    $action,
                    $count,
                    $action_text
                );
            ?></p>
        </div>
    <?php endif; ?>

    <nav class="nav-tab-wrapper">
        <?php foreach ($valid_tabs as $tab): ?>
            <a href="<?php echo esc_url(add_query_arg(array('page' => 'wedding-photo-uploader', 'tab' => $tab), $admin_page_url)); ?>" 
               class="nav-tab <?php echo $current_tab === $tab ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html(ucfirst($tab)); ?> 
                <span class="count">(<?php echo absint(${$tab . '_count'}); ?>)</span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="tab-content">
        <?php 
        $photos = ${$current_tab . '_photos'};
        $empty_message = sprintf(
            /* translators: %s: Media status (pending, approved, or rejected) */
            esc_html__('No %s media.', 'wedding-photo-uploader'),
            $current_tab
        );
        ?>
        
        <?php if (empty($photos)): ?>
            <p><?php _e('No items uploaded yet.', 'wedding-photo-uploader'); ?></p>
        <?php else: ?>
            <form method="post" action="">
                <?php wp_nonce_field('wpu_bulk_action', 'bulk_nonce'); ?>
                <input type="hidden" name="current_tab" value="<?php echo esc_attr($current_tab); ?>">
                
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <select name="bulk_action">
                            <option value="-1"><?php esc_html_e('Bulk Actions', 'wedding-photo-uploader'); ?></option>
                            <?php if ($current_tab === 'pending'): ?>
                                <option value="approve"><?php esc_html_e('Approve', 'wedding-photo-uploader'); ?></option>
                                <option value="reject"><?php esc_html_e('Reject', 'wedding-photo-uploader'); ?></option>
                            <?php endif; ?>
                            <?php if ($current_tab === 'approved'): ?>
                                <option value="reject"><?php esc_html_e('Reject', 'wedding-photo-uploader'); ?></option>
                            <?php endif; ?>
                            <?php if ($current_tab === 'rejected'): ?>
                                <option value="approve"><?php esc_html_e('Approve', 'wedding-photo-uploader'); ?></option>
                            <?php endif; ?>
                            <option value="delete"><?php esc_html_e('Delete', 'wedding-photo-uploader'); ?></option>
                        </select>
                        <input type="submit" class="button action" value="<?php esc_attr_e('Apply', 'wedding-photo-uploader'); ?>">
                    </div>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox" id="cb-select-all-1">
                            </td>
                            <th><?php _e('Media', 'wedding-photo-uploader'); ?></th>
                            <th><?php _e('Type', 'wedding-photo-uploader'); ?></th>
                            <th><?php _e('Uploader', 'wedding-photo-uploader'); ?></th>
                            <th><?php _e('Upload Date', 'wedding-photo-uploader'); ?></th>
                            <th><?php _e('Status', 'wedding-photo-uploader'); ?></th>
                            <th><?php _e('Actions', 'wedding-photo-uploader'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($photos as $photo): ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="photo_ids[]" value="<?php echo esc_attr($photo->id); ?>">
                                </th>
                                <td>
                                    <?php 
                                    $file_type = isset($photo->file_type) ? $photo->file_type : 'photo';
                                    if ($file_type === 'video') {
                                        // Handle video display
                                        $video_url = wp_get_attachment_url($photo->image_id);
                                        if ($video_url):
                                    ?>
                                        <div class="wpu-video-preview">
                                            <video width="100" height="100" controls style="object-fit: cover;">
                                                <source src="<?php echo esc_url($video_url); ?>" type="<?php echo esc_attr(get_post_mime_type($photo->image_id)); ?>">
                                                <?php _e('Your browser does not support the video tag.', 'wedding-photo-uploader'); ?>
                                            </video>
                                        </div>
                                    <?php 
                                        endif;
                                    } else {
                                        // Handle photo display
                                        $thumbnail = wp_get_attachment_image_src($photo->image_id, 'thumbnail');
                                        $full_image = wp_get_attachment_image_src($photo->image_id, 'full');
                                        if ($thumbnail && $full_image): 
                                    ?>
                                        <a href="javascript:void(0);" class="wpu-thumbnail-preview" data-full="<?php echo esc_url($full_image[0]); ?>">
                                            <img src="<?php echo esc_url($thumbnail[0]); ?>" 
                                                 width="100" 
                                                 height="100" 
                                                 alt="<?php echo esc_attr(sprintf(__('Photo by %s', 'wedding-photo-uploader'), $photo->uploader_name)); ?>"
                                                 style="object-fit: cover;">
                                        </a>
                                    <?php 
                                        endif;
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="dashicons dashicons-<?php echo $file_type === 'video' ? 'video-alt3' : 'format-image'; ?>"></span>
                                    <?php echo esc_html(ucfirst($file_type)); ?>
                                </td>
                                <td><?php echo esc_html($photo->uploader_name); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($photo->date_uploaded))); ?></td>
                                <td>
                                    <span class="status-<?php echo esc_attr($photo->status); ?>">
                                        <?php echo esc_html(ucfirst($photo->status)); ?>
                                    </span>
                                </td>
                                <td>
                                                        <?php if ($photo->status !== 'approved'): ?>
                        <a href="<?php echo wp_nonce_url(
                            add_query_arg(
                                array(
                                    'action' => 'approve',
                                    'photo_id' => $photo->id,
                                    'tab' => $current_tab,
                                    'page' => 'wedding-photo-uploader'
                                ),
                                admin_url('admin.php')
                            ),
                            'wpu_photo_action',
                            '_wpnonce'
                        ); ?>" 
                           class="button button-primary">
                            <?php _e('Approve', 'wedding-photo-uploader'); ?>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($photo->status !== 'rejected'): ?>
                        <a href="<?php echo wp_nonce_url(
                            add_query_arg(
                                array(
                                    'action' => 'reject',
                                    'photo_id' => $photo->id,
                                    'tab' => $current_tab,
                                    'page' => 'wedding-photo-uploader'
                                ),
                                admin_url('admin.php')
                            ),
                            'wpu_photo_action',
                            '_wpnonce'
                        ); ?>" 
                           class="button button-secondary">
                            <?php _e('Reject', 'wedding-photo-uploader'); ?>
                        </a>
                    <?php endif; ?>
                                    
                                    <?php if ($photo->status === 'pending'): ?>
                                        <span class="status-pending-note" style="display: block; margin-top: 5px; font-style: italic; font-size: 11px; color: #888;">
                                            <?php _e('This photo is pending review', 'wedding-photo-uploader'); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Photo Modal -->
<div id="wpu-photo-modal" class="wpu-modal">
    <span class="wpu-modal-close" role="button" tabindex="0" aria-label="<?php esc_attr_e('Close modal', 'wedding-photo-uploader'); ?>">&times;</span>
    <img id="wpu-modal-image" class="wpu-modal-content" alt="<?php esc_attr_e('Full size photo', 'wedding-photo-uploader'); ?>">
</div>

<style>
.nav-tab-wrapper {
    margin-bottom: 20px;
}

.nav-tab .count {
    display: inline-block;
    padding: 0 6px;
    border-radius: 10px;
    background: #e5e5e5;
    font-size: 11px;
    font-weight: 600;
    margin-left: 5px;
}

.nav-tab-active .count {
    background: #fff;
}

.wpu-no-photos {
    text-align: center;
    padding: 40px;
    background: #f5f5f5;
    border-radius: 8px;
    font-style: italic;
    color: #666;
    grid-column: 1 / -1;
}

.wpu-photos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.wpu-photo-item {
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 5px;
    background: #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}

.wpu-photo-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.wpu-photo-preview {
    position: relative;
    margin-bottom: 10px;
    aspect-ratio: 1;
    overflow: hidden;
    border-radius: 4px;
}

.wpu-photo-thumbnail {
    width: 100%;
    height: 100%;
    object-fit: cover;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.wpu-photo-thumbnail:hover {
    transform: scale(1.05);
}

.wpu-photo-info {
    margin: 10px 0;
    font-size: 14px;
    line-height: 1.4;
}

.wpu-photo-info p {
    margin: 5px 0;
}

.wpu-photo-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
    justify-content: flex-end;
}

.wpu-photo-actions .button {
    margin: 0;
}

/* Modal styles */
.wpu-modal {
    display: none;
    position: fixed;
    z-index: 999999;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.9);
    backdrop-filter: blur(5px);
}

.wpu-modal-content {
    margin: auto;
    display: block;
    max-width: 90%;
    max-height: 90vh;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    box-shadow: 0 0 20px rgba(0,0,0,0.3);
}

.wpu-modal-close {
    position: absolute;
    right: 25px;
    top: 10px;
    color: #f1f1f1;
    font-size: 40px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.2s ease;
    z-index: 1000000;
}

.wpu-modal-close:hover {
    color: #fff;
}

@media screen and (max-width: 782px) {
    .wpu-photos-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }
    
    .wpu-photo-actions {
        flex-direction: column;
    }
    
    .wpu-photo-actions .button {
        width: 100%;
        text-align: center;
    }
}

.status-pending {
    color: #f0ad4e;
}
.status-approved {
    color: #5cb85c;
}
.status-rejected {
    color: #d9534f;
}

.wpu-thumbnail-preview {
    cursor: pointer;
    display: block;
    transition: opacity 0.3s ease;
}

.wpu-thumbnail-preview:hover {
    opacity: 0.8;
}

/* Bulk action styles */
.tablenav {
    margin: 8px 0;
}

.bulkactions {
    float: left;
}

.bulkactions select {
    margin-right: 8px;
}

.check-column {
    width: 2.2em;
    text-align: center;
}

.check-column input[type="checkbox"] {
    margin: 0;
}

.column-cb {
    width: 2.2em;
    text-align: center;
}

.column-cb input[type="checkbox"] {
    margin: 0;
}

/* ===== Modernized moderation UI (presentation only) ===== */
.wrap .wp-list-table {
    border: 1px solid #e0e0e6;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
}

.wrap .wp-list-table thead th,
.wrap .wp-list-table thead td {
    background: #f6f7f7;
    border-bottom: 1px solid #e0e0e6;
    font-weight: 600;
}

.wrap .wp-list-table td,
.wrap .wp-list-table th {
    vertical-align: middle;
    padding-top: 14px;
    padding-bottom: 14px;
}

.wrap .wp-list-table tbody tr:hover {
    background: #f0f6fc;
}

/* Thumbnails / video previews */
.wpu-thumbnail-preview img,
.wpu-video-preview video {
    border-radius: 6px;
    border: 1px solid #e0e0e6;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
}

/* Status as pill badges (overrides the colour-only rules above). */
.status-pending,
.status-approved,
.status-rejected {
    display: inline-block;
    padding: 3px 11px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    line-height: 1.7;
}

.status-pending {
    background: #fcf3e3;
    color: #8a6116;
}

.status-approved {
    background: #e6f4ea;
    color: #15662c;
}

.status-rejected {
    background: #fdeaea;
    color: #b42318;
}

/* Type column icon alignment */
.wrap .wp-list-table .dashicons {
    color: #646970;
    vertical-align: text-bottom;
}

/* Bulk-actions toolbar spacing */
.tablenav.top {
    margin-bottom: 12px;
}

.bulkactions select {
    border-radius: 6px;
    min-height: 32px;
}

/* Action buttons: even spacing in the Actions column */
.wp-list-table td .button {
    margin-right: 6px;
}

/* Tab bar: a touch more breathing room */
.nav-tab-wrapper {
    margin-bottom: 24px;
}

</style>

<script>
jQuery(document).ready(function($) {
    // Modal functionality for admin image preview
    const modal = $('#wpu-photo-modal');
    const modalImg = $('#wpu-modal-image');
    const closeBtn = $('.wpu-modal-close');
    
    // Close modal function
    function closeModal() {
        modal.css('display', 'none');
        // Return focus to the last clicked thumbnail
        if (lastFocusedThumbnail) {
            lastFocusedThumbnail.focus();
        }
    }
    
    let lastFocusedThumbnail = null;
    
    // Open modal when clicking on thumbnail
    $('.wpu-thumbnail-preview').click(function(e) {
        const fullSizeUrl = $(this).data('full');
        lastFocusedThumbnail = $(this);
        
        modal.css('display', 'block');
        modalImg.attr('src', fullSizeUrl);
        
        // Set focus to close button
        closeBtn.focus();
    });

    // Close modal with close button
    closeBtn.on('click keypress', function(e) {
        if (e.type === 'click' || (e.type === 'keypress' && (e.key === 'Enter' || e.key === ' '))) {
            closeModal();
        }
    });

    // Close modal with Escape key
    $(document).keydown(function(e) {
        if (e.key === 'Escape' && modal.is(':visible')) {
            closeModal();
        }
    });

    // Close modal when clicking outside the image
    modal.click(function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    // Bulk action functionality
    $('#cb-select-all-1').on('change', function() {
        $('input[name="photo_ids[]"]').prop('checked', this.checked);
    });

    // Update select all checkbox when individual checkboxes change
    $('input[name="photo_ids[]"]').on('change', function() {
        const totalCheckboxes = $('input[name="photo_ids[]"]').length;
        const checkedCheckboxes = $('input[name="photo_ids[]"]:checked').length;
        
        if (checkedCheckboxes === 0) {
            $('#cb-select-all-1').prop('indeterminate', false).prop('checked', false);
        } else if (checkedCheckboxes === totalCheckboxes) {
            $('#cb-select-all-1').prop('indeterminate', false).prop('checked', true);
        } else {
            $('#cb-select-all-1').prop('indeterminate', true);
        }
    });

    // Handle bulk actions form submission
    $('form').on('submit', function(e) {
        const form = $(this);
        const bulkAction = $('select[name="bulk_action"]').val();
        const selectedPhotos = $('input[name="photo_ids[]"]:checked').length;
        
        // Only handle bulk action forms
        if (!form.find('select[name="bulk_action"]').length) {
            return true;
        }
        
        if (bulkAction === '-1' || bulkAction === '') {
            e.preventDefault();
            alert('<?php esc_html_e('Please select a bulk action.', 'wedding-photo-uploader'); ?>');
            return false;
        }
        
        if (selectedPhotos === 0) {
            e.preventDefault();
            alert('<?php esc_html_e('Please select at least one item.', 'wedding-photo-uploader'); ?>');
            return false;
        }
        
        let confirmMessage = '';
        switch (bulkAction) {
            case 'approve':
                confirmMessage = '<?php esc_html_e('Are you sure you want to approve the selected items?', 'wedding-photo-uploader'); ?>';
                break;
            case 'reject':
                confirmMessage = '<?php esc_html_e('Are you sure you want to reject the selected items?', 'wedding-photo-uploader'); ?>';
                break;
            case 'delete':
                confirmMessage = '<?php esc_html_e('Are you sure you want to delete the selected items? This action cannot be undone.', 'wedding-photo-uploader'); ?>';
                break;
        }
        
        if (confirmMessage && !confirm(confirmMessage)) {
            e.preventDefault();
            return false;
        }
        
        return true;
    });
});
</script> 