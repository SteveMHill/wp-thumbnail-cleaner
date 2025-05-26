<?php
/**
 * Plugin Name: Thumbnails Manager
 * Description: Manage WordPress thumbnail generation and cleanup existing thumbnails
 * Version: 2.0
 * Author: Steven Hill
 * License: GPLv2 or later
 */

require_once plugin_dir_path(__FILE__) . 'includes/class-thumbnail-settings.php';

class RTC_Thumbnail_Manager {
    private $settings;
    
    public function __construct() {
        $this->settings = new RTC_Thumbnail_Settings();
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_filter('intermediate_image_sizes_advanced', [$this, 'filter_image_sizes'], 10, 1);
    }
    
    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_rtc-thumbnail-manager' !== $hook) {
            return;
        }
        wp_enqueue_script('rtc-admin', plugins_url('assets/js/admin.js', __FILE__), ['jquery'], '2.0', true);
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Thumbnail Manager',
            'Thumbnail Manager',
            'manage_options',
            'rtc-thumbnail-manager',
            [$this, 'render_main_page'],
            'dashicons-images-alt2',
            100
        );
    }
    
    public function render_main_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.'));
        }
        
        $sizes_stats = $this->analyze_existing_thumbnails();
        
        include plugin_dir_path(__FILE__) . 'includes/views/main-page.php';
    }
    
    private function analyze_existing_thumbnails() {
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'];
        $sizes_stats = [];
        
        if (!is_dir($base_dir)) return [];
        
        $dir = new RecursiveDirectoryIterator($base_dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($dir);
        
        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;
            
            $filename = $file->getFilename();
            if (preg_match('/-(\d+x\d+)\.(jpg|jpeg|png|gif|webp)$/i', $filename, $matches)) {
                $size = $matches[1];
                if (!isset($sizes_stats[$size])) {
                    $sizes_stats[$size] = [
                        'count' => 0,
                        'size' => 0
                    ];
                }
                $sizes_stats[$size]['count']++;
                $sizes_stats[$size]['size'] += filesize($file->getPathname());
            }
        }
        
        return $sizes_stats;
    }
    
    public function filter_image_sizes($sizes) {
        $enabled_sizes = get_option('rtc_enabled_sizes', array_keys($sizes));
        return array_intersect_key($sizes, array_flip($enabled_sizes));
    }
}

// Initialize the plugin
$rtc_thumbnail_manager = new RTC_Thumbnail_Manager();