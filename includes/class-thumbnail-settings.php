<?php
class RTC_Thumbnail_Settings {
    private $available_sizes = [];
    private $active_subsizes = [];
    
    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
        $this->get_active_subsizes();
    }
    
    public function register_settings() {
        register_setting('rtc_settings', 'rtc_enabled_sizes');
        
        // Get all registered image sizes
        global $_wp_additional_image_sizes;
        
        // Get default WordPress sizes
        $default_sizes = ['thumbnail', 'medium', 'medium_large', 'large'];
        foreach ($default_sizes as $size) {
            $width = get_option("{$size}_size_w");
            $height = get_option("{$size}_size_h");
            $crop = get_option("{$size}_crop", false);
            
            if ($width || $height) {
                $this->available_sizes[$size] = [
                    'width' => absint($width),
                    'height' => absint($height),
                    'crop' => $crop
                ];
            }
        }
        
        // Get additional registered sizes
        if (is_array($_wp_additional_image_sizes)) {
            foreach ($_wp_additional_image_sizes as $size => $config) {
                if (!empty($config['width']) || !empty($config['height'])) {
                    $this->available_sizes[$size] = [
                        'width' => absint($config['width']),
                        'height' => absint($config['height']),
                        'crop' => !empty($config['crop'])
                    ];
                }
            }
        }
    }
    
    private function get_active_subsizes() {
        // Get a recent image to check its subsizes
        $args = array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => 1,
            'post_status' => 'inherit'
        );
        
        $query = new WP_Query($args);
        if ($query->have_posts()) {
            $post = $query->posts[0];
            $metadata = wp_get_attachment_metadata($post->ID);
            if (!empty($metadata['sizes'])) {
                $this->active_subsizes = array_keys($metadata['sizes']);
            }
        }
    }
    
    public function get_available_sizes() {
        return $this->available_sizes;
    }
    
    public function is_size_active($size) {
        return in_array($size, $this->active_subsizes);
    }
}