<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin class for Auto Post to X
 */
class Auto_Post_X {
    
    private $x_api;
    private $image_optimizer;
    
    public function __construct() {
        $this->x_api = new X_API();
        $this->image_optimizer = new Image_Optimizer();
        
        // Hook into post publishing
        add_action('transition_post_status', array($this, 'handle_post_transition'), 10, 3);
        
        // Add meta box for post editing
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta_box'));
        
        // Schedule retry for failed posts
        add_action('auto_post_x_retry_failed_posts', array($this, 'retry_failed_posts'));
        
        // Add admin notices
        add_action('admin_notices', array($this, 'display_admin_notices'));
    }
    
    /**
     * Handle post status transitions
     */
    public function handle_post_transition($new_status, $old_status, $post) {
        // Only proceed if plugin is enabled
        if (!get_option('auto_post_x_enabled')) {
            return;
        }
        
        // Check if this is a valid post type
        $allowed_post_types = get_option('auto_post_x_post_types', array('post'));
        if (!in_array($post->post_type, $allowed_post_types)) {
            return;
        }
        
        // Only proceed when post is being published
        if ($new_status !== 'publish') {
            return;
        }
        
        // Skip if this is an auto-save or revision
        if (wp_is_post_autosave($post->ID) || wp_is_post_revision($post->ID)) {
            return;
        }
        
        // Check if we should post this automatically
        $auto_post = get_post_meta($post->ID, '_auto_post_x_enabled', true);
        
        // If meta is not set, use default behavior (only for new posts)
        if ($auto_post === '') {
            $auto_post = ($old_status !== 'publish') ? '1' : '0';
        }
        
        if ($auto_post === '1') {
            $this->post_to_x($post->ID);
        }
    }
    
    /**
     * Post content to X
     */
    public function post_to_x($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }
        
        // Generate message
        $message = $this->generate_message($post);
        
        // Get image if enabled
        $image_path = null;
        if (get_option('auto_post_x_include_image')) {
            $image_path = $this->get_optimized_image($post_id);
        }
        
        // Attempt to post
        $result = $this->x_api->create_post($message, $image_path);
        
        // Log the result
        $this->log_post_attempt($post_id, $result, $message, $image_path);
        
        // Update post meta with result
        update_post_meta($post_id, '_auto_post_x_status', $result['success'] ? 'success' : 'failed');
        update_post_meta($post_id, '_auto_post_x_response', $result);
        update_post_meta($post_id, '_auto_post_x_timestamp', current_time('timestamp'));
        
        if ($result['success']) {
            update_post_meta($post_id, '_auto_post_x_tweet_id', $result['tweet_id']);
            
            // Show success notice
            set_transient('auto_post_x_success_notice', sprintf(
                __('Post successfully shared to X! <a href="%s" target="_blank">View Tweet</a>', 'auto-post-to-x'),
                'https://x.com/i/web/status/' . $result['tweet_id']
            ), 30);
        } else {
            // Schedule retry for failed posts
            if (!wp_next_scheduled('auto_post_x_retry_failed_posts', array($post_id))) {
                wp_schedule_single_event(time() + 300, 'auto_post_x_retry_failed_posts', array($post_id)); // retry in 5 minutes
            }
            
            // Show error notice
            set_transient('auto_post_x_error_notice', sprintf(
                __('Failed to post to X: %s', 'auto-post-to-x'),
                $result['message']
            ), 30);
        }
        
        return $result;
    }
    
    /**
     * Generate message for X post
     */
    private function generate_message($post) {
        $template = get_option('auto_post_x_message_template', '{POST_TITLE} - {PERMALINK}');
        $char_limit = get_option('auto_post_x_char_limit', 280);
        
        // Replace placeholders
        $message = str_replace(
            array('{POST_TITLE}', '{PERMALINK}', '{EXCERPT}', '{AUTHOR}'),
            array(
                $post->post_title,
                get_permalink($post->ID),
                wp_trim_words($post->post_excerpt ?: $post->post_content, 20),
                get_the_author_meta('display_name', $post->post_author)
            ),
            $template
        );
        
        // Trim to character limit
        if (strlen($message) > $char_limit) {
            $message = substr($message, 0, $char_limit - 3) . '...';
        }
        
        return $message;
    }
    
    /**
     * Get optimized image for post
     */
    private function get_optimized_image($post_id) {
        $only_featured = get_option('auto_post_x_only_featured_image', 1);
        
        if ($only_featured) {
            $attachment_id = get_post_thumbnail_id($post_id);
        } else {
            // Try featured image first, then first image in content
            $attachment_id = get_post_thumbnail_id($post_id);
            
            if (!$attachment_id) {
                $attachment_id = $this->get_first_image_from_content($post_id);
            }
        }
        
        if (!$attachment_id) {
            return null;
        }
        
        return $this->image_optimizer->optimize_for_x($attachment_id);
    }
    
    /**
     * Get first image from post content
     */
    private function get_first_image_from_content($post_id) {
        $post = get_post($post_id);
        $content = $post->post_content;
        
        // Look for image attachments in content
        preg_match_all('/\[gallery[^\]]*ids=["\']([^"\']*)["\'][^\]]*\]/', $content, $gallery_matches);
        if (!empty($gallery_matches[1][0])) {
            $ids = explode(',', $gallery_matches[1][0]);
            return intval(trim($ids[0]));
        }
        
        // Look for image tags
        preg_match('/<img[^>]+src=["\']([^"\']*)["\'][^>]*>/i', $content, $img_matches);
        if (!empty($img_matches[1])) {
            return attachment_url_to_postid($img_matches[1]);
        }
        
        return null;
    }
    
    /**
     * Add meta box to post editor
     */
    public function add_meta_box() {
        $post_types = get_option('auto_post_x_post_types', array('post'));
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'auto_post_x_meta_box',
                __('Auto Post to X', 'auto-post-to-x'),
                array($this, 'render_meta_box'),
                $post_type,
                'side',
                'default'
            );
        }
    }
    
    /**
     * Render meta box content
     */
    public function render_meta_box($post) {
        wp_nonce_field('auto_post_x_meta_box', 'auto_post_x_meta_nonce');
        
        $enabled = get_post_meta($post->ID, '_auto_post_x_enabled', true);
        $status = get_post_meta($post->ID, '_auto_post_x_status', true);
        $tweet_id = get_post_meta($post->ID, '_auto_post_x_tweet_id', true);
        
        // Default to enabled for new posts
        if ($enabled === '' && $post->post_status !== 'publish') {
            $enabled = '1';
        }
        
        echo '<p>';
        echo '<label><input type="checkbox" name="auto_post_x_enabled" value="1" ' . checked($enabled, '1', false) . '> ';
        echo __('Post to X when published', 'auto-post-to-x');
        echo '</label>';
        echo '</p>';
        
        if ($status) {
            echo '<p><strong>' . __('Status:', 'auto-post-to-x') . '</strong> ';
            if ($status === 'success') {
                echo '<span style="color: green;">' . __('Posted successfully', 'auto-post-to-x') . '</span>';
                if ($tweet_id) {
                    echo '<br><a href="https://x.com/i/web/status/' . esc_attr($tweet_id) . '" target="_blank">' . __('View Tweet', 'auto-post-to-x') . '</a>';
                }
            } else {
                echo '<span style="color: red;">' . __('Failed to post', 'auto-post-to-x') . '</span>';
            }
            echo '</p>';
        }
    }
    
    /**
     * Save meta box data
     */
    public function save_meta_box($post_id) {
        if (!isset($_POST['auto_post_x_meta_nonce']) || !wp_verify_nonce($_POST['auto_post_x_meta_nonce'], 'auto_post_x_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $enabled = isset($_POST['auto_post_x_enabled']) ? '1' : '0';
        update_post_meta($post_id, '_auto_post_x_enabled', $enabled);
    }
    
    /**
     * Retry failed posts
     */
    public function retry_failed_posts($post_id) {
        $status = get_post_meta($post_id, '_auto_post_x_status', true);
        
        if ($status === 'failed') {
            $this->post_to_x($post_id);
        }
    }
    
    /**
     * Log post attempt
     */
    private function log_post_attempt($post_id, $result, $message, $image_path) {
        $logs = get_option('auto_post_x_logs', array());
        
        $log_entry = array(
            'post_id' => $post_id,
            'timestamp' => current_time('Y-m-d H:i:s'),
            'message' => $message,
            'image_path' => $image_path,
            'success' => $result['success'],
            'response' => $result['message'],
            'tweet_id' => isset($result['tweet_id']) ? $result['tweet_id'] : null
        );
        
        // Keep only last 100 log entries
        array_unshift($logs, $log_entry);
        if (count($logs) > 100) {
            $logs = array_slice($logs, 0, 100);
        }
        
        update_option('auto_post_x_logs', $logs);
    }
    
    /**
     * Display admin notices
     */
    public function display_admin_notices() {
        if ($success_notice = get_transient('auto_post_x_success_notice')) {
            echo '<div class="notice notice-success is-dismissible"><p>' . $success_notice . '</p></div>';
            delete_transient('auto_post_x_success_notice');
        }
        
        if ($error_notice = get_transient('auto_post_x_error_notice')) {
            echo '<div class="notice notice-error is-dismissible"><p>' . $error_notice . '</p></div>';
            delete_transient('auto_post_x_error_notice');
        }
    }
} 