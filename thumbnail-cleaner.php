<?php
/**
 * Plugin Name: Remove Thumbnails Cleaner
 * Description: Disables WordPress from generating extra image sizes (thumbnails) and provides an admin tool to clean up existing old thumbnails safely.
 * Version: 1.1
 * Author: Steven Hill
 * License: GPLv2 or later
 */

// ========== PART 1: Disable WordPress image size generation ==========

// Disable additional image sizes
function rtc_remove_default_image_sizes($sizes) {
    unset($sizes['thumbnail'], $sizes['medium'], $sizes['large']);
    return $sizes;
}
add_filter('intermediate_image_sizes_advanced', 'rtc_remove_default_image_sizes');

// Set all size dimensions to 0 on activation
function rtc_set_image_size_options() {
    update_option('thumbnail_size_w', 0);
    update_option('thumbnail_size_h', 0);
    update_option('medium_size_w', 0);
    update_option('medium_size_h', 0);
    update_option('large_size_w', 0);
    update_option('large_size_h', 0);
}
register_activation_hook(__FILE__, 'rtc_set_image_size_options');

// ========== PART 2: Admin UI for thumbnail cleanup ==========

add_action('admin_menu', function () {
    add_menu_page(
        'Thumbnail Cleaner',
        'Thumbnail Cleaner',
        'manage_options',
        'rtc-thumbnail-cleaner',
        'rtc_thumbnail_cleaner_page',
        'dashicons-trash',
        100
    );
});

function rtc_thumbnail_cleaner_page() {
    echo '<div class="wrap"><h1>Remove Old Thumbnails</h1>';

    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    if (isset($_POST['rtc_delete_thumbs']) && check_admin_referer('rtc_delete_thumbs_action')) {
        $deleted = rtc_delete_thumbnails();
        echo '<div class="notice notice-success"><p>' . esc_html($deleted) . ' thumbnail files were deleted.</p></div>';
    }

    echo '<form method="post">';
    wp_nonce_field('rtc_delete_thumbs_action');
    echo '<p>This will search your <code>wp-content/uploads</code> directory and delete image files that match the typical WordPress thumbnail pattern (e.g., <code>-150x150.jpg</code>, <code>-768x1024.png</code>). Original images will NOT be deleted.</p>';
    submit_button('Delete Thumbnails Now');
    echo '</form></div>';
}

// ========== PART 3: Delete existing thumbnail files ==========

function rtc_delete_thumbnails() {
    $upload_dir = wp_upload_dir();
    $base_dir = $upload_dir['basedir'];
    $count = 0;

    if (!is_dir($base_dir)) return 0;

    $dir = new RecursiveDirectoryIterator($base_dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($dir);

    foreach ($iterator as $file) {
        if (!$file->isFile()) continue;

        $filename = $file->getFilename();
        $filepath = $file->getPathname();

        // Match common WordPress thumbnail pattern
        if (preg_match('/-\d+x\d+\.(jpg|jpeg|png|gif|webp)$/i', $filename)) {
            if (is_writable($filepath)) {
                @unlink($filepath);
                $count++;
            }
        }
    }

    return $count;
}
