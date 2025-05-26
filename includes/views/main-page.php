<?php
// Ensure we're in WordPress context
if (!defined('ABSPATH')) exit;

// Group similar sizes
$size_groups = [];
$total_space = 0;
$total_files = 0;

foreach ($sizes_stats as $size => $stats) {
    if (preg_match('/^(\d+)x(\d+)/', $size, $matches)) {
        $width = (int)$matches[1];
        $height = (int)$matches[2];
        
        // Determine aspect ratio
        $ratio = $width / max($height, 1);
        
        // Group by common use cases and aspect ratios
        if ($width <= 150 && $height <= 150) {
            $group = 'Avatar & Icons (≤ 150px)';
        } else if ($width === $height) {
            $group = 'Square Images';
        } else if ($ratio >= 2.3) {
            $group = 'Wide Banner Images';
        } else if ($ratio <= 0.6) {
            $group = 'Vertical/Portrait Images';
        } else if ($width <= 300) {
            $group = 'Mobile Thumbnails (≤ 300px)';
        } else if ($width <= 768) {
            if (abs($ratio - 1.77) < 0.2) {
                $group = 'Tablet Video (16:9)';
            } else {
                $group = 'Tablet Content (≤ 768px)';
            }
        } else if ($width <= 1024) {
            if (abs($ratio - 1.77) < 0.2) {
                $group = 'Desktop Video (16:9)';
            } else {
                $group = 'Desktop Content (≤ 1024px)';
            }
        } else if ($width <= 1536) {
            $group = 'Retina & HD (≤ 1536px)';
        } else if ($width <= 2048) {
            $group = '2K & Featured (≤ 2048px)';
        } else {
            $group = '4K & Full Size (> 2048px)';
        }

        // Special handling for WooCommerce sizes
        if (strpos($size, 'shop_') === 0 || strpos($size, 'woocommerce_') === 0) {
            $group = 'WooCommerce Images';
        }

        // Special handling for common theme sizes
        if (strpos($size, 'et-pb-') === 0) {
            $group = 'Divi Theme Images';
        } else if (strpos($size, 'elementor-') === 0) {
            $group = 'Elementor Images';
        }
        
        if (!isset($size_groups[$group])) {
            $size_groups[$group] = [];
        }
        $size_groups[$group][$size] = $stats;
        $total_space += $stats['size'];
        $total_files += $stats['count'];
    }
}

// Sort groups by priority
$group_order = [
    'Avatar & Icons (≤ 150px)',
    'Mobile Thumbnails (≤ 300px)',
    'Square Images',
    'Tablet Content (≤ 768px)',
    'Tablet Video (16:9)',
    'Desktop Content (≤ 1024px)',
    'Desktop Video (16:9)',
    'Wide Banner Images',
    'Vertical/Portrait Images',
    'Retina & HD (≤ 1536px)',
    '2K & Featured (≤ 2048px)',
    '4K & Full Size (> 2048px)',
    'WooCommerce Images',
    'Divi Theme Images',
    'Elementor Images'
];

// Reorder groups
$ordered_groups = [];
foreach ($group_order as $group) {
    if (isset($size_groups[$group])) {
        $ordered_groups[$group] = $size_groups[$group];
    }
}
$size_groups = $ordered_groups;
?>

<div class="wrap">
    <h1>Thumbnail Manager</h1>
    
    <h2 class="nav-tab-wrapper">
        <a href="?page=rtc-thumbnail-manager&tab=cleanup" class="nav-tab <?php echo (!isset($_GET['tab']) || $_GET['tab'] == 'cleanup') ? 'nav-tab-active' : ''; ?>">
            Cleanup Thumbnails
        </a>
        <a href="?page=rtc-thumbnail-manager&tab=settings" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'settings') ? 'nav-tab-active' : ''; ?>">
            Upload Settings
        </a>
    </h2>
    
    <?php if (!isset($_GET['tab']) || $_GET['tab'] == 'cleanup'): ?>
        <form method="post" action="">
            <?php wp_nonce_field('rtc_cleanup_action'); ?>
            
            <div class="cleanup-summary">
                <h3>Summary</h3>
                <p>Found <?php echo number_format($total_files); ?> thumbnail files using <?php echo size_format($total_space); ?> of space</p>
            </div>

            <style>
            .group-content { display: none; }
            .group-header { cursor: pointer; }
            .group-header:before {
                content: '▸';
                display: inline-block;
                margin-right: 5px;
                transition: transform 0.2s;
            }
            .group-header.open:before {
                transform: rotate(90deg);
            }
            </style>

            <?php foreach ($size_groups as $group => $sizes): ?>
                <div class="group-wrapper">
                    <div class="group-header" style="background: #f1f1f1; padding: 10px; margin-bottom: 0;">
                        <strong><?php echo esc_html($group); ?></strong>
                        <?php 
                        $group_files = array_sum(array_column($sizes, 'count'));
                        $group_size = array_sum(array_column($sizes, 'size'));
                        echo ' (' . number_format($group_files) . ' files, ' . size_format($group_size) . ')';
                        ?>
                    </div>
                    <div class="group-content">
                        <table class="wp-list-table widefat fixed striped" style="margin-bottom: 20px;">
                            <thead>
                                <tr>
                                    <th scope="col" class="manage-column column-cb check-column">
                                        <input type="checkbox" class="group-select-all">
                                    </th>
                                    <th>Size</th>
                                    <th>Files</th>
                                    <th>Total Size</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sizes as $size => $stats): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="sizes_to_delete[]" value="<?php echo esc_attr($size); ?>">
                                    </td>
                                    <td><?php echo esc_html($size); ?></td>
                                    <td><?php echo number_format($stats['count']); ?></td>
                                    <td><?php echo size_format($stats['size']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php submit_button('Delete Selected Sizes', 'primary', 'rtc_delete_selected'); ?>
        </form>
    <?php else: ?>
        <form method="post" action="options.php">
            <?php 
            settings_fields('rtc_settings');
            $available_sizes = $this->settings->get_available_sizes();
            $enabled_sizes = get_option('rtc_enabled_sizes', array_keys($available_sizes));
            ?>
            
            <table class="form-table">
                <?php foreach ($available_sizes as $size => $config): ?>
                <tr>
                    <th scope="row" style="width: 300px;">
                        <?php 
                        // Format the size name for better readability
                        $display_name = preg_replace('/[-_]/', ' ', $size); // Replace hyphens and underscores with spaces
                        
                        // Handle common theme prefixes
                        $display_name = preg_replace('/^(et-pb|elementor|woo|shop|custom)[\s-]/', '', $display_name);
                        
                        // Special handling for WP default sizes
                        if (in_array($size, ['thumbnail', 'medium', 'medium_large', 'large'])) {
                            $display_name = 'WordPress ' . ucfirst($display_name);
                        }
                        
                        echo esc_html(ucwords($display_name)); 
                        ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="rtc_enabled_sizes[]" 
                                   value="<?php echo esc_attr($size); ?>"
                                   <?php checked(in_array($size, $enabled_sizes)); ?>>
                            <span style="display: inline-block; min-width: 150px;">
                                <?php 
                                $width = !empty($config['width']) ? $config['width'] : 'auto';
                                $height = !empty($config['height']) ? $config['height'] : 'auto';
                                echo sprintf(
                                    'Generate %sx%s', 
                                    esc_html($width), 
                                    esc_html($height)
                                );
                                ?>
                            </span>
                            <?php if (!empty($config['crop'])): ?>
                                <span class="description">(cropped)</span>
                            <?php endif; ?>
                            
                            <span class="status-indicator">
                                <?php if ($this->settings->is_size_active($size)): ?>
                                    <span class="active-size dashicons dashicons-yes" style="color: #46b450;" title="This size is currently being generated on upload"></span>
                                <?php else: ?>
                                    <span class="inactive-size dashicons dashicons-no" style="color: #dc3232;" title="This size is not currently being generated on upload"></span>
                                <?php endif; ?>
                            </span>
                        </label>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            
            <?php submit_button('Save Settings'); ?>
        </form>
    <?php endif; ?>
</div>