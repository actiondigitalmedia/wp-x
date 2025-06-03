<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin settings page
 */

// Add admin menu
add_action('admin_menu', 'auto_post_x_admin_menu');
function auto_post_x_admin_menu() {
    add_options_page(
        __('Auto Post to X Settings', 'auto-post-to-x'),
        __('Auto Post to X', 'auto-post-to-x'),
        'manage_options',
        'auto-post-to-x',
        'auto_post_x_settings_page'
    );
}

// Settings page content
function auto_post_x_settings_page() {
    if (isset($_POST['submit'])) {
        auto_post_x_save_settings();
    }
    
    if (isset($_POST['test_connection'])) {
        auto_post_x_test_connection();
    }
    
    if (isset($_POST['authorize'])) {
        auto_post_x_start_authorization();
    }
    
    // Handle OAuth callback
    if (isset($_GET['code']) && isset($_GET['state'])) {
        auto_post_x_handle_oauth_callback();
    }
    
    ?>
    <div class="wrap">
        <h1><?php _e('Auto Post to X Settings', 'auto-post-to-x'); ?></h1>
        
        <?php auto_post_x_show_notices(); ?>
        
        <div class="nav-tab-wrapper">
            <a href="#general" class="nav-tab nav-tab-active" data-tab="general"><?php _e('General', 'auto-post-to-x'); ?></a>
            <a href="#api" class="nav-tab" data-tab="api"><?php _e('API Settings', 'auto-post-to-x'); ?></a>
            <a href="#image" class="nav-tab" data-tab="image"><?php _e('Image Settings', 'auto-post-to-x'); ?></a>
            <a href="#logs" class="nav-tab" data-tab="logs"><?php _e('Logs', 'auto-post-to-x'); ?></a>
        </div>
        
        <form method="post" action="">
            <?php wp_nonce_field('auto_post_x_settings', 'auto_post_x_nonce'); ?>
            
            <!-- General Tab -->
            <div id="tab-general" class="tab-content">
                <h2><?php _e('General Settings', 'auto-post-to-x'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable Auto Posting', 'auto-post-to-x'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="auto_post_x_enabled" value="1" <?php checked(get_option('auto_post_x_enabled'), 1); ?>>
                                <?php _e('Automatically post new content to X', 'auto-post-to-x'); ?>
                            </label>
                            <p class="description"><?php _e('Enable or disable automatic posting to X for all new posts.', 'auto-post-to-x'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Post Types', 'auto-post-to-x'); ?></th>
                        <td>
                            <?php
                            $selected_post_types = get_option('auto_post_x_post_types', array('post'));
                            $post_types = get_post_types(array('public' => true), 'objects');
                            foreach ($post_types as $post_type) {
                                $checked = in_array($post_type->name, $selected_post_types) ? 'checked' : '';
                                echo '<label><input type="checkbox" name="auto_post_x_post_types[]" value="' . esc_attr($post_type->name) . '" ' . $checked . '> ' . esc_html($post_type->label) . '</label><br>';
                            }
                            ?>
                            <p class="description"><?php _e('Select which post types should be automatically posted to X.', 'auto-post-to-x'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Message Template', 'auto-post-to-x'); ?></th>
                        <td>
                            <textarea name="auto_post_x_message_template" rows="3" cols="50" class="large-text"><?php echo esc_textarea(get_option('auto_post_x_message_template', '{POST_TITLE} - {PERMALINK}')); ?></textarea>
                            <p class="description">
                                <?php _e('Template for X posts. Available placeholders:', 'auto-post-to-x'); ?><br>
                                <code>{POST_TITLE}</code> - <?php _e('Post title', 'auto-post-to-x'); ?><br>
                                <code>{PERMALINK}</code> - <?php _e('Post URL', 'auto-post-to-x'); ?><br>
                                <code>{EXCERPT}</code> - <?php _e('Post excerpt', 'auto-post-to-x'); ?><br>
                                <code>{AUTHOR}</code> - <?php _e('Post author', 'auto-post-to-x'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Character Limit', 'auto-post-to-x'); ?></th>
                        <td>
                            <input type="number" name="auto_post_x_char_limit" value="<?php echo esc_attr(get_option('auto_post_x_char_limit', 280)); ?>" min="1" max="280">
                            <p class="description"><?php _e('Maximum number of characters for X posts (default: 280).', 'auto-post-to-x'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- API Tab -->
            <div id="tab-api" class="tab-content" style="display:none;">
                <h2><?php _e('X API Settings', 'auto-post-to-x'); ?></h2>
                <div class="notice notice-info">
                    <p>
                        <?php _e('To use this plugin, you need to create an X (Twitter) app and get API credentials.', 'auto-post-to-x'); ?>
                        <a href="https://developer.twitter.com/en/portal/dashboard" target="_blank"><?php _e('Create an app here', 'auto-post-to-x'); ?></a>
                    </p>
                </div>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Setup Instructions', 'auto-post-to-x'); ?></th>
                        <td>
                            <div class="notice notice-warning inline">
                                <p><strong><?php _e('Required Callback URL for your X App:', 'auto-post-to-x'); ?></strong></p>
                                <code><?php echo esc_url(admin_url('options-general.php?page=auto-post-to-x')); ?></code>
                                <p>
                                    <?php _e('Copy this URL exactly and add it to your X App\'s Callback URLs in the Authentication Settings.', 'auto-post-to-x'); ?>
                                    <br><em><?php _e('Important: The URL must match exactly (including https/http).', 'auto-post-to-x'); ?></em>
                                </p>
                            </div>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Client ID', 'auto-post-to-x'); ?></th>
                        <td>
                            <input type="text" name="auto_post_x_client_id" value="<?php echo esc_attr(get_option('auto_post_x_client_id')); ?>" class="regular-text">
                            <p class="description"><?php _e('Your X app Client ID.', 'auto-post-to-x'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Client Secret', 'auto-post-to-x'); ?></th>
                        <td>
                            <input type="password" name="auto_post_x_client_secret" value="<?php echo esc_attr(get_option('auto_post_x_client_secret')); ?>" class="regular-text">
                            <p class="description"><?php _e('Your X app Client Secret.', 'auto-post-to-x'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Authorization Status', 'auto-post-to-x'); ?></th>
                        <td>
                            <?php
                            $access_token = get_option('auto_post_x_access_token');
                            if ($access_token) {
                                echo '<span style="color: green;">' . __('Authorized', 'auto-post-to-x') . '</span>';
                                echo '<br><br>';
                                echo '<input type="submit" name="test_connection" class="button" value="' . __('Test Connection', 'auto-post-to-x') . '">';
                            } else {
                                echo '<span style="color: red;">' . __('Not Authorized', 'auto-post-to-x') . '</span>';
                                echo '<br><br>';
                                echo '<input type="submit" name="authorize" class="button-primary" value="' . __('Authorize with X', 'auto-post-to-x') . '">';
                            }
                            ?>
                            <p class="description"><?php _e('You must authorize this plugin to post to your X account.', 'auto-post-to-x'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Image Tab -->
            <div id="tab-image" class="tab-content" style="display:none;">
                <h2><?php _e('Image Settings', 'auto-post-to-x'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Include Images', 'auto-post-to-x'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="auto_post_x_include_image" value="1" <?php checked(get_option('auto_post_x_include_image'), 1); ?>>
                                <?php _e('Attach images to X posts', 'auto-post-to-x'); ?>
                            </label>
                            <p class="description"><?php _e('When enabled, featured images or first content images will be attached to X posts.', 'auto-post-to-x'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Image Source', 'auto-post-to-x'); ?></th>
                        <td>
                            <label>
                                <input type="radio" name="auto_post_x_only_featured_image" value="1" <?php checked(get_option('auto_post_x_only_featured_image'), 1); ?>>
                                <?php _e('Featured image only', 'auto-post-to-x'); ?>
                            </label><br>
                            <label>
                                <input type="radio" name="auto_post_x_only_featured_image" value="0" <?php checked(get_option('auto_post_x_only_featured_image'), 0); ?>>
                                <?php _e('Featured image or first image from content', 'auto-post-to-x'); ?>
                            </label>
                            <p class="description"><?php _e('Choose whether to use only featured images or fall back to the first image in post content.', 'auto-post-to-x'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Image Size Preference', 'auto-post-to-x'); ?></th>
                        <td>
                            <select name="auto_post_x_image_size_preference">
                                <?php
                                $current_size = get_option('auto_post_x_image_size_preference', 'large');
                                $sizes = array(
                                    'thumbnail' => __('Thumbnail', 'auto-post-to-x'),
                                    'medium' => __('Medium', 'auto-post-to-x'),
                                    'large' => __('Large', 'auto-post-to-x'),
                                    'full' => __('Full Size', 'auto-post-to-x')
                                );
                                foreach ($sizes as $value => $label) {
                                    echo '<option value="' . esc_attr($value) . '" ' . selected($current_size, $value, false) . '>' . esc_html($label) . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description"><?php _e('Preferred WordPress image size to use. Images will be optimized to meet X specifications.', 'auto-post-to-x'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <h3><?php _e('X Image Requirements', 'auto-post-to-x'); ?></h3>
                <table class="form-table">
                    <tr>
                        <td colspan="2">
                            <div class="notice notice-info inline">
                                <p><strong><?php _e('X Image Specifications:', 'auto-post-to-x'); ?></strong></p>
                                <ul>
                                    <li><?php _e('Maximum file size: 5MB', 'auto-post-to-x'); ?></li>
                                    <li><?php _e('Supported formats: JPG, PNG, GIF, WebP', 'auto-post-to-x'); ?></li>
                                    <li><?php _e('Recommended sizes:', 'auto-post-to-x'); ?>
                                        <ul>
                                            <li><?php _e('Landscape: 1200 x 675 pixels (16:9)', 'auto-post-to-x'); ?></li>
                                            <li><?php _e('Square: 1200 x 1200 pixels (1:1)', 'auto-post-to-x'); ?></li>
                                            <li><?php _e('Portrait: 1080 x 1350 pixels (4:5)', 'auto-post-to-x'); ?></li>
                                        </ul>
                                    </li>
                                    <li><?php _e('Aspect ratio range: 1:3 to 3:1', 'auto-post-to-x'); ?></li>
                                </ul>
                                <p><?php _e('Images will be automatically optimized to meet these requirements.', 'auto-post-to-x'); ?></p>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Logs Tab -->
            <div id="tab-logs" class="tab-content" style="display:none;">
                <h2><?php _e('Activity Logs', 'auto-post-to-x'); ?></h2>
                <?php auto_post_x_display_logs(); ?>
            </div>
            
            <div class="tab-save-area">
                <?php submit_button(__('Save Settings', 'auto-post-to-x'), 'primary', 'submit', false); ?>
            </div>
        </form>
    </div>
    
    <style>
    .nav-tab-wrapper {
        margin-bottom: 20px;
    }
    .tab-content {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-top: none;
        padding: 20px;
    }
    .tab-save-area {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-top: none;
        padding: 20px;
        border-bottom-left-radius: 3px;
        border-bottom-right-radius: 3px;
    }
    .notice.inline {
        display: inline-block;
        margin: 0;
        padding: 12px;
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        $('.nav-tab').click(function(e) {
            e.preventDefault();
            var tab = $(this).data('tab');
            
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            $('.tab-content').hide();
            $('#tab-' + tab).show();
        });
    });
    </script>
    <?php
}

// Save settings
function auto_post_x_save_settings() {
    if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['auto_post_x_nonce'], 'auto_post_x_settings')) {
        return;
    }
    
    update_option('auto_post_x_enabled', isset($_POST['auto_post_x_enabled']) ? 1 : 0);
    update_option('auto_post_x_client_id', sanitize_text_field($_POST['auto_post_x_client_id']));
    update_option('auto_post_x_client_secret', sanitize_text_field($_POST['auto_post_x_client_secret']));
    update_option('auto_post_x_post_types', isset($_POST['auto_post_x_post_types']) ? array_map('sanitize_text_field', $_POST['auto_post_x_post_types']) : array());
    update_option('auto_post_x_include_image', isset($_POST['auto_post_x_include_image']) ? 1 : 0);
    update_option('auto_post_x_message_template', sanitize_textarea_field($_POST['auto_post_x_message_template']));
    update_option('auto_post_x_char_limit', intval($_POST['auto_post_x_char_limit']));
    update_option('auto_post_x_image_size_preference', sanitize_text_field($_POST['auto_post_x_image_size_preference']));
    update_option('auto_post_x_only_featured_image', intval($_POST['auto_post_x_only_featured_image']));
    
    add_settings_error('auto_post_x_settings', 'settings_saved', __('Settings saved successfully!', 'auto-post-to-x'), 'success');
}

// Test API connection
function auto_post_x_test_connection() {
    if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['auto_post_x_nonce'], 'auto_post_x_settings')) {
        return;
    }
    
    $x_api = new X_API();
    $result = $x_api->test_connection();
    
    if ($result['success']) {
        add_settings_error('auto_post_x_settings', 'test_success', $result['message'], 'success');
    } else {
        add_settings_error('auto_post_x_settings', 'test_error', $result['message'], 'error');
    }
}

// Start OAuth authorization
function auto_post_x_start_authorization() {
    if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['auto_post_x_nonce'], 'auto_post_x_settings')) {
        return;
    }
    
    $x_api = new X_API();
    $redirect_uri = admin_url('options-general.php?page=auto-post-to-x');
    $auth_url = $x_api->get_authorization_url($redirect_uri);
    
    wp_redirect($auth_url);
    exit;
}

// Handle OAuth callback
function auto_post_x_handle_oauth_callback() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $code = sanitize_text_field($_GET['code']);
    $state = sanitize_text_field($_GET['state']);
    $redirect_uri = admin_url('options-general.php?page=auto-post-to-x');
    
    $x_api = new X_API();
    $result = $x_api->exchange_code_for_token($code, $redirect_uri, $state);
    
    if ($result['success']) {
        add_settings_error('auto_post_x_settings', 'auth_success', __('Authorization successful! You can now post to X.', 'auto-post-to-x'), 'success');
    } else {
        add_settings_error('auto_post_x_settings', 'auth_error', sprintf(__('Authorization failed: %s', 'auto-post-to-x'), $result['message']), 'error');
    }
    
    // Redirect to clean URL
    wp_redirect(admin_url('options-general.php?page=auto-post-to-x'));
    exit;
}

// Display admin notices
function auto_post_x_show_notices() {
    settings_errors('auto_post_x_settings');
}

// Display logs
function auto_post_x_display_logs() {
    $logs = get_option('auto_post_x_logs', array());
    
    if (empty($logs)) {
        echo '<p>' . __('No activity logs yet.', 'auto-post-to-x') . '</p>';
        return;
    }
    
    echo '<div class="wp-list-table widefat fixed striped">';
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th>' . __('Date/Time', 'auto-post-to-x') . '</th>';
    echo '<th>' . __('Post ID', 'auto-post-to-x') . '</th>';
    echo '<th>' . __('Message', 'auto-post-to-x') . '</th>';
    echo '<th>' . __('Status', 'auto-post-to-x') . '</th>';
    echo '<th>' . __('Response', 'auto-post-to-x') . '</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach (array_slice($logs, 0, 50) as $log) {
        echo '<tr>';
        echo '<td>' . esc_html($log['timestamp']) . '</td>';
        echo '<td><a href="' . get_edit_post_link($log['post_id']) . '">' . esc_html($log['post_id']) . '</a></td>';
        echo '<td>' . esc_html(substr($log['message'], 0, 50)) . '...</td>';
        echo '<td>';
        if ($log['success']) {
            echo '<span style="color: green;">✓ ' . __('Success', 'auto-post-to-x') . '</span>';
            if ($log['tweet_id']) {
                echo '<br><a href="https://x.com/i/web/status/' . esc_attr($log['tweet_id']) . '" target="_blank">' . __('View Tweet', 'auto-post-to-x') . '</a>';
            }
        } else {
            echo '<span style="color: red;">✗ ' . __('Failed', 'auto-post-to-x') . '</span>';
        }
        echo '</td>';
        echo '<td>' . esc_html(substr($log['response'], 0, 100)) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    
    if (count($logs) > 50) {
        echo '<p><em>' . sprintf(__('Showing latest 50 entries out of %d total.', 'auto-post-to-x'), count($logs)) . '</em></p>';
    }
} 