# WordPress Thumbnail Manager

## Description

This WordPress plugin helps manage image thumbnails by:

- Providing fine-grained control over which thumbnail sizes are generated on upload
- Offering a clean interface to review and delete existing thumbnail files
- Showing detailed statistics about thumbnail usage and disk space
- Organizing thumbnails into logical groups for easier management

## Features

- Visual dashboard showing thumbnail statistics by size groups
- Safe deletion of selected thumbnail sizes
- Detailed size settings management interface
- Groups thumbnails by common use cases (avatars, mobile, desktop, etc.)
- Compatible with WooCommerce, Divi, and Elementor thumbnail sizes
- Secure, permission-controlled interface with CSRF protection

## Installation

1. Upload the plugin folder to `/wp-content/plugins/thumbnail-manager/`
2. Activate the plugin through the WordPress admin panel
3. Go to `Thumbnail Manager` in the WordPress admin menu
4. Choose which thumbnail sizes to keep or remove

## Usage

### Cleanup Interface
- View all thumbnail sizes grouped by their usage type
- See file counts and disk space usage for each size
- Selectively delete specific thumbnail dimensions
- Expandable/collapsible groups for better organization

### Settings Interface
- Enable/disable specific thumbnail sizes
- View current dimensions for each thumbnail type
- See which sizes are actively being generated
- Manage WordPress core and theme-specific sizes

## Warning

- Always backup your uploads directory before performing bulk deletions
- The plugin only removes thumbnail-sized images, not original uploads
- Some themes may require specific thumbnail sizes to function properly

## License

This plugin is licensed under the GPLv2 or later.
