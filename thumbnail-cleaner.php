<?php
/**
 * Plugin Name: Thumbnails Cleaner
 * Description: Disables WordPress from generating extra image sizes (thumbnails) and provides an admin tool to clean up existing old thumbnails safely.
 * Version: 1.1
 * Author: Steven Hill
 * License: GPLv2 or later
 */

// ===== 1. Disable Default Thumbnail Sizes =====

add_filter('intermediate_image_sizes_advanced', function($sizes) {
    unset($sizes['thumbnail'], $sizes['medium'], $sizes['large']);
    return $sizes;
});

register_activation_hook(__FILE__, function () {
    update_option('thumbnail_size_w', 0);
    update_option('thumbnail_size_h', 0);
    update_option('medium_size_w', 0);
    update_option('medium_size_h', 0);
    update_option('large_size_w', 0);
    update_option('large_size_h', 0);
});

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
        wp_die(__('You do not have permission to access this page.'));
    }

    // Show preview count
    $preview = rtc_count_thumbnails();
    echo '<div class="notice notice-info"><p>';
    if ($preview['count'] > 0) {
        echo '<strong>Found ' . $preview['count'] . ' thumbnail files using ' . size_format($preview['size']) . ' of space.</strong>';
    } else {
        echo '<strong>No thumbnail files found.</strong>';
    }
    echo '</p></div>';

    if (isset($_POST['rtc_delete_thumbs']) && check_admin_referer('rtc_delete_thumbs_action')) {
        $result = rtc_delete_thumbnails();
        $deleted_files = $result['files'];
        $total_size = $result['size'];
        $count = count($deleted_files);
        echo '<div class="notice notice-success"><p><strong>' . $count . ' thumbnail file(s) deleted, freeing up ' . size_format($total_size) . ' of space.</strong></p></div>';

        if ($count > 0) {
            echo '<h2>Deleted Files:</h2><ul style="max-height:300px;overflow:auto;font-family:monospace;">';
            foreach ($deleted_files as $file) {
                echo '<li>' . esc_html($file) . '</li>';
            }
            echo '</ul>';
        } else {
            echo '<div class="notice notice-warning"><p>No thumbnails found matching the deletion pattern.</p></div>';
        }
    }

    // Only show the form if there are thumbnails to delete
    if ($preview['count'] > 0) {
        echo '<form method="post">';
        wp_nonce_field('rtc_delete_thumbs_action');
        submit_button('Delete Thumbnails Now', 'primary', 'rtc_delete_thumbs');
        echo '</form>';

        echo '<div class="notice notice-warning"><p><strong>Warning:</strong> This action cannot be undone. Please backup your media files before proceeding.</p></div>';
        echo '<script>
        document.querySelector("form").addEventListener("submit", function(e) {
            if(!confirm("Are you sure you want to delete ' . $preview['count'] . ' thumbnail files? This cannot be undone.")) {
                e.preventDefault();
            }
        });
        </script>';
    }

    echo '</div>';
}

// Add this function before rtc_delete_thumbnails()
function rtc_count_thumbnails() {
    $upload_dir = wp_upload_dir();
    $base_dir = $upload_dir['basedir'];
    $count = 0;
    $total_size = 0;

    if (!is_dir($base_dir)) return ['count' => 0, 'size' => 0];

    $dir = new RecursiveDirectoryIterator($base_dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($dir);

    foreach ($iterator as $file) {
        if (!$file->isFile()) continue;

        $filename = $file->getFilename();
        if (preg_match('/-\d+x\d+\.(jpg|jpeg|png|gif|webp)$/i', $filename)) {
            $count++;
            $total_size += filesize($file->getPathname());
        }
    }

    return ['count' => $count, 'size' => $total_size];
}

// Modify the rtc_delete_thumbnails() function to track space saved
function rtc_delete_thumbnails() {
    $upload_dir = wp_upload_dir();
    $base_dir = $upload_dir['basedir'];
    $deleted = [];
    $total_size = 0;
    $log_file = $base_dir . '/thumbnails-deleted.log';

    // Add to the beginning of rtc_delete_thumbnails()
    if (!function_exists('wp_upload_dir')) {
        return ['files' => [], 'size' => 0, 'error' => 'WordPress upload functions not available'];
    }

    // Add error checking for directory permissions
    if (!is_writable($base_dir)) {
        return ['files' => [], 'size' => 0, 'error' => 'Upload directory is not writable'];
    }

    if (!is_dir($base_dir)) return ['files' => $deleted, 'size' => 0];

    $dir = new RecursiveDirectoryIterator($base_dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($dir);

    foreach ($iterator as $file) {
        if (!$file->isFile()) continue;

        $filename = $file->getFilename();
        // Skip essential thumbnail sizes
        if (preg_match('/-(?:150x150|300x300)\.(jpg|jpeg|png|gif|webp)$/i', $filename)) {
            continue;
        }
        if (preg_match('/-\d+x\d+\.(jpg|jpeg|png|gif|webp)$/i', $filename)) {
            if (is_writable($filepath)) {
                $total_size += filesize($filepath);
                @unlink($filepath);
                $relative_path = str_replace($base_dir, '', $filepath);
                $deleted[] = $relative_path;
            }
        }
    }

    // Log to file
    if (!empty($deleted)) {
        $log_entry = date('[Y-m-d H:i:s]') . " Deleted thumbnails:\n" . implode("\n", $deleted) . "\n\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }

    return ['files' => $deleted, 'size' => $total_size];
}