<?php
/*
Plugin Name: Auto Post to X
Plugin URI: https://example.com/auto-post-to-x
Description: Auto-publish WordPress posts to X (Twitter) with optimized images.
Version: 1.0.0
Requires PHP: 7.4
Author: Your Name
Author URI: https://example.com
License: GPLv2 or later
Text Domain: auto-post-to-x
Domain Path: /languages/
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AUTO_POST_X_VERSION', '1.0.0');
define('AUTO_POST_X_PLUGIN_FILE', __FILE__);
define('AUTO_POST_X_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AUTO_POST_X_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load text domain for translations
function auto_post_x_load_textdomain() {
    load_plugin_textdomain('auto-post-to-x', false, basename(dirname(__FILE__)) . '/languages/');
}
add_action('init', 'auto_post_x_load_textdomain');

// Include required files
require_once AUTO_POST_X_PLUGIN_DIR . 'includes/class-auto-post-x.php';
require_once AUTO_POST_X_PLUGIN_DIR . 'includes/class-x-api.php';
require_once AUTO_POST_X_PLUGIN_DIR . 'includes/class-image-optimizer.php';
require_once AUTO_POST_X_PLUGIN_DIR . 'admin/admin-settings.php';

// Initialize the plugin
function auto_post_x_init() {
    new Auto_Post_X();
}
add_action('plugins_loaded', 'auto_post_x_init');

// Activation hook
register_activation_hook(__FILE__, 'auto_post_x_activate');
function auto_post_x_activate() {
    // Create options with default values
    add_option('auto_post_x_enabled', 1);
    add_option('auto_post_x_client_id', '');
    add_option('auto_post_x_client_secret', '');
    add_option('auto_post_x_access_token', '');
    add_option('auto_post_x_refresh_token', '');
    add_option('auto_post_x_post_types', array('post'));
    add_option('auto_post_x_include_image', 1);
    add_option('auto_post_x_message_template', '{POST_TITLE} - {PERMALINK}');
    add_option('auto_post_x_char_limit', 280);
    add_option('auto_post_x_image_size_preference', 'large'); // 'large', 'medium', 'thumbnail'
    add_option('auto_post_x_only_featured_image', 1);
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'auto_post_x_deactivate');
function auto_post_x_deactivate() {
    // Clean up scheduled events if any
    wp_clear_scheduled_hook('auto_post_x_retry_failed_posts');
}

// Uninstall hook
register_uninstall_hook(__FILE__, 'auto_post_x_uninstall');
function auto_post_x_uninstall() {
    // Delete options
    delete_option('auto_post_x_enabled');
    delete_option('auto_post_x_client_id');
    delete_option('auto_post_x_client_secret');
    delete_option('auto_post_x_access_token');
    delete_option('auto_post_x_refresh_token');
    delete_option('auto_post_x_post_types');
    delete_option('auto_post_x_include_image');
    delete_option('auto_post_x_message_template');
    delete_option('auto_post_x_char_limit');
    delete_option('auto_post_x_image_size_preference');
    delete_option('auto_post_x_only_featured_image');
    delete_option('auto_post_x_logs');
}

// Add settings link to plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'auto_post_x_settings_link');
function auto_post_x_settings_link($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=auto-post-to-x') . '">' . __('Settings', 'auto-post-to-x') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
} 