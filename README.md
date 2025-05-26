# Remove Thumbnails Cleaner

**Version:** 1.1  
**Author:** Steven Hill
**License:** GPLv2 or later  

## Description

This plugin helps reduce file bloat in WordPress by:

- Disabling WordPress from generating multiple image sizes (thumbnails) on upload.
- Setting all default image dimensions to 0.
- Providing an admin interface to safely delete old thumbnail files from `wp-content/uploads`.

Useful for large sites with thousands of posts/images that are hitting hosting limits due to file count.

## Features

- Prevents WordPress from generating `-150x150`, `-300x200`, etc.
- Cleans up existing thumbnails without touching original images.
- Adds a simple admin page under the name “Thumbnail Cleaner”.
- Safe, permission-controlled, with CSRF protection.

## Installation

1. Upload the plugin folder to `/wp-content/plugins/remove-thumbnails-cleaner/`
2. Activate the plugin via the WordPress admin panel.
3. Go to `Thumbnail Cleaner` in the WordPress admin menu.
4. Click the “Delete Thumbnails Now” button to remove old thumbnails.

## Warning

- This tool only removes files matching WordPress thumbnail patterns (e.g. `-150x150.jpg`).
- It does **not** delete original uploads.
- Always back up your uploads directory before running large batch deletions.

## License

This plugin is licensed under the GPLv2 or later.
