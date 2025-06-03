<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * X API handler class
 */
class X_API {
    
    private $client_id;
    private $client_secret;
    private $access_token;
    private $refresh_token;
    
    public function __construct() {
        $this->client_id = get_option('auto_post_x_client_id');
        $this->client_secret = get_option('auto_post_x_client_secret');
        $this->access_token = get_option('auto_post_x_access_token');
        $this->refresh_token = get_option('auto_post_x_refresh_token');
    }
    
    /**
     * Create a post on X with optional image
     */
    public function create_post($message, $image_path = null) {
        if (!$this->is_configured()) {
            return array(
                'success' => false,
                'message' => __('X API credentials not configured', 'auto-post-to-x')
            );
        }
        
        // Refresh token if needed
        $this->refresh_access_token_if_needed();
        
        $media_id = null;
        
        // Upload image if provided
        if ($image_path && file_exists($image_path)) {
            $upload_result = $this->upload_media($image_path);
            if ($upload_result['success']) {
                $media_id = $upload_result['media_id'];
            } else {
                // If image upload fails, proceed without image
                error_log('Auto Post to X: Image upload failed - ' . $upload_result['message']);
            }
        }
        
        // Create the tweet
        return $this->create_tweet($message, $media_id);
    }
    
    /**
     * Upload media to X
     */
    private function upload_media($image_path) {
        $url = 'https://upload.twitter.com/1.1/media/upload.json';
        
        // Get file info
        $file_size = filesize($image_path);
        $file_content = file_get_contents($image_path);
        $file_name = basename($image_path);
        
        // Get MIME type
        $mime_type = $this->get_mime_type($image_path);
        if (!$mime_type) {
            return array(
                'success' => false,
                'message' => __('Unsupported file type', 'auto-post-to-x')
            );
        }
        
        // Prepare multipart form data
        $boundary = wp_generate_password(24, false);
        $body = '';
        
        // Add media field
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Disposition: form-data; name=\"media\"; filename=\"{$file_name}\"\r\n";
        $body .= "Content-Type: {$mime_type}\r\n\r\n";
        $body .= $file_content . "\r\n";
        
        // Add media_category field
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Disposition: form-data; name=\"media_category\"\r\n\r\n";
        $body .= "tweet_image\r\n";
        
        $body .= "--{$boundary}--\r\n";
        
        // Prepare headers
        $headers = array(
            'Authorization' => 'Bearer ' . $this->access_token,
            'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
            'Content-Length' => strlen($body)
        );
        
        // Make request
        $response = wp_remote_post($url, array(
            'headers' => $headers,
            'body' => $body,
            'timeout' => 30,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);
        
        if ($response_code === 200 && isset($data['media_id'])) {
            return array(
                'success' => true,
                'media_id' => $data['media_id_string'] // Use string version for compatibility
            );
        } else {
            $error_message = isset($data['errors'][0]['message']) 
                ? $data['errors'][0]['message'] 
                : __('Unknown error uploading media', 'auto-post-to-x');
            
            return array(
                'success' => false,
                'message' => $error_message
            );
        }
    }
    
    /**
     * Create tweet with optional media
     */
    private function create_tweet($text, $media_id = null) {
        $url = 'https://api.twitter.com/2/tweets';
        
        // Prepare tweet data
        $tweet_data = array(
            'text' => $text
        );
        
        if ($media_id) {
            $tweet_data['media'] = array(
                'media_ids' => array($media_id)
            );
        }
        
        // Prepare headers
        $headers = array(
            'Authorization' => 'Bearer ' . $this->access_token,
            'Content-Type' => 'application/json'
        );
        
        // Make request
        $response = wp_remote_post($url, array(
            'headers' => $headers,
            'body' => json_encode($tweet_data),
            'timeout' => 30,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);
        
        if ($response_code === 201 && isset($data['data']['id'])) {
            return array(
                'success' => true,
                'tweet_id' => $data['data']['id'],
                'message' => __('Tweet posted successfully', 'auto-post-to-x')
            );
        } else {
            $error_message = isset($data['detail']) 
                ? $data['detail'] 
                : (isset($data['errors'][0]['detail']) 
                    ? $data['errors'][0]['detail'] 
                    : __('Unknown error creating tweet', 'auto-post-to-x'));
            
            return array(
                'success' => false,
                'message' => $error_message
            );
        }
    }
    
    /**
     * Refresh access token if needed
     */
    private function refresh_access_token_if_needed() {
        $last_refresh = get_option('auto_post_x_last_token_refresh', 0);
        $refresh_interval = 60 * 60; // 1 hour
        
        if (time() - $last_refresh > $refresh_interval) {
            $this->refresh_access_token();
        }
    }
    
    /**
     * Refresh access token
     */
    private function refresh_access_token() {
        if (!$this->refresh_token) {
            return false;
        }
        
        $url = 'https://api.twitter.com/2/oauth2/token';
        
        $auth_header = base64_encode($this->client_id . ':' . $this->client_secret);
        
        $headers = array(
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Basic ' . $auth_header
        );
        
        $body = array(
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refresh_token,
            'client_id' => $this->client_id
        );
        
        $response = wp_remote_post($url, array(
            'headers' => $headers,
            'body' => $body,
            'timeout' => 15,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);
        
        if ($response_code === 200 && isset($data['access_token'])) {
            $this->access_token = $data['access_token'];
            update_option('auto_post_x_access_token', $this->access_token);
            
            if (isset($data['refresh_token'])) {
                $this->refresh_token = $data['refresh_token'];
                update_option('auto_post_x_refresh_token', $this->refresh_token);
            }
            
            update_option('auto_post_x_last_token_refresh', time());
            return true;
        }
        
        return false;
    }
    
    /**
     * Get authorization URL for OAuth 2.0 flow
     */
    public function get_authorization_url($redirect_uri) {
        // Generate a secure random state
        $state = wp_generate_password(32, false);
        update_option('auto_post_x_oauth_state', $state);
        
        // Generate PKCE challenge (simplified version)
        $code_challenge = base64_encode(hash('sha256', wp_generate_password(43, false), true));
        $code_challenge = rtrim(strtr($code_challenge, '+/', '-_'), '=');
        update_option('auto_post_x_code_challenge', $code_challenge);
        
        $params = array(
            'response_type' => 'code',
            'client_id' => $this->client_id,
            'redirect_uri' => $redirect_uri,
            'scope' => 'tweet.read tweet.write users.read offline.access',
            'state' => $state,
            'code_challenge' => $code_challenge,
            'code_challenge_method' => 'S256'
        );
        
        // Use X's correct authorization endpoint
        return 'https://x.com/i/oauth2/authorize?' . http_build_query($params);
    }
    
    /**
     * Exchange authorization code for access token
     */
    public function exchange_code_for_token($code, $redirect_uri, $state) {
        // Verify state
        $stored_state = get_option('auto_post_x_oauth_state');
        if ($state !== $stored_state) {
            return array('success' => false, 'message' => 'Invalid state parameter');
        }
        
        // Get the stored code challenge for PKCE
        $code_challenge = get_option('auto_post_x_code_challenge');
        if (!$code_challenge) {
            return array('success' => false, 'message' => 'PKCE challenge not found');
        }
        
        $url = 'https://api.x.com/2/oauth2/token';
        
        $auth_header = base64_encode($this->client_id . ':' . $this->client_secret);
        
        $headers = array(
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Basic ' . $auth_header
        );
        
        $body = array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirect_uri,
            'client_id' => $this->client_id,
            'code_verifier' => $code_challenge
        );
        
        $response = wp_remote_post($url, array(
            'headers' => $headers,
            'body' => $body,
            'timeout' => 15,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);
        
        if ($response_code === 200 && isset($data['access_token'])) {
            update_option('auto_post_x_access_token', $data['access_token']);
            if (isset($data['refresh_token'])) {
                update_option('auto_post_x_refresh_token', $data['refresh_token']);
            }
            update_option('auto_post_x_last_token_refresh', time());
            
            // Clean up PKCE data
            delete_option('auto_post_x_oauth_state');
            delete_option('auto_post_x_code_challenge');
            
            return array('success' => true, 'message' => 'Authorization successful');
        } else {
            $error_message = isset($data['error_description']) 
                ? $data['error_description'] 
                : (isset($data['error']) ? $data['error'] : 'Authorization failed');
            
            return array('success' => false, 'message' => $error_message);
        }
    }
    
    /**
     * Check if API is properly configured
     */
    public function is_configured() {
        return !empty($this->client_id) && !empty($this->client_secret) && !empty($this->access_token);
    }
    
    /**
     * Get MIME type for file
     */
    private function get_mime_type($file_path) {
        $allowed_types = array(
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp'
        );
        
        $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        return isset($allowed_types[$ext]) ? $allowed_types[$ext] : null;
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        if (!$this->is_configured()) {
            return array(
                'success' => false,
                'message' => __('API credentials not configured', 'auto-post-to-x')
            );
        }
        
        $url = 'https://api.twitter.com/2/users/me';
        
        $headers = array(
            'Authorization' => 'Bearer ' . $this->access_token
        );
        
        $response = wp_remote_get($url, array(
            'headers' => $headers,
            'timeout' => 15,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code === 200) {
            return array(
                'success' => true,
                'message' => __('API connection successful', 'auto-post-to-x')
            );
        } else {
            return array(
                'success' => false,
                'message' => sprintf(__('API connection failed with code %d', 'auto-post-to-x'), $response_code)
            );
        }
    }
} 