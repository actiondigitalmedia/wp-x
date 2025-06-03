<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Image optimizer class for X posting
 */
class Image_Optimizer {
    
    // X image specifications (based on 2024 requirements)
    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    const RECOMMENDED_SIZES = array(
        'landscape' => array('width' => 1200, 'height' => 675), // 16:9 aspect ratio
        'square' => array('width' => 1200, 'height' => 1200),   // 1:1 aspect ratio
        'portrait' => array('width' => 1080, 'height' => 1350)  // 4:5 aspect ratio
    );
    
    /**
     * Optimize image for X posting
     */
    public function optimize_for_x($attachment_id) {
        if (!$attachment_id) {
            return null;
        }
        
        $original_path = get_attached_file($attachment_id);
        if (!$original_path || !file_exists($original_path)) {
            return null;
        }
        
        // Check if we need to optimize
        if ($this->is_image_optimized($original_path)) {
            return $original_path;
        }
        
        // Create optimized version
        $optimized_path = $this->create_optimized_image($original_path, $attachment_id);
        
        return $optimized_path ?: $original_path;
    }
    
    /**
     * Check if image meets X requirements
     */
    private function is_image_optimized($image_path) {
        // Check file size
        if (filesize($image_path) > self::MAX_FILE_SIZE) {
            return false;
        }
        
        // Check image dimensions
        $image_info = getimagesize($image_path);
        if (!$image_info) {
            return false;
        }
        
        $width = $image_info[0];
        $height = $image_info[1];
        
        // Check if dimensions are within acceptable range
        $aspect_ratio = $width / $height;
        
        // X supports aspect ratios from 1:3 to 3:1
        if ($aspect_ratio < 0.33 || $aspect_ratio > 3.0) {
            return false;
        }
        
        // Check if it's one of the recommended sizes
        foreach (self::RECOMMENDED_SIZES as $size) {
            if ($width == $size['width'] && $height == $size['height']) {
                return true;
            }
        }
        
        // If it's within reasonable size limits and proper aspect ratio, it's acceptable
        return ($width <= 1600 && $height <= 1600);
    }
    
    /**
     * Create optimized image
     */
    private function create_optimized_image($original_path, $attachment_id) {
        $image_info = getimagesize($original_path);
        if (!$image_info) {
            return null;
        }
        
        $original_width = $image_info[0];
        $original_height = $image_info[1];
        $mime_type = $image_info['mime'];
        
        // Determine target dimensions
        $target_size = $this->get_target_size($original_width, $original_height);
        
        // Create WordPress image editor
        $image_editor = wp_get_image_editor($original_path);
        if (is_wp_error($image_editor)) {
            return null;
        }
        
        // Resize image
        $resize_result = $image_editor->resize(
            $target_size['width'], 
            $target_size['height'], 
            true // crop to exact dimensions
        );
        
        if (is_wp_error($resize_result)) {
            return null;
        }
        
        // Set quality for compression
        $quality = $this->get_optimal_quality($mime_type);
        $image_editor->set_quality($quality);
        
        // Generate filename for optimized version
        $upload_dir = wp_upload_dir();
        $filename = pathinfo($original_path, PATHINFO_FILENAME);
        $extension = pathinfo($original_path, PATHINFO_EXTENSION);
        $optimized_filename = $filename . '-x-optimized.' . $extension;
        $optimized_path = $upload_dir['path'] . '/' . $optimized_filename;
        
        // Save optimized image
        $save_result = $image_editor->save($optimized_path);
        if (is_wp_error($save_result)) {
            return null;
        }
        
        // Check if the optimized version meets size requirements
        if (file_exists($optimized_path) && filesize($optimized_path) <= self::MAX_FILE_SIZE) {
            return $optimized_path;
        }
        
        // If still too large, try with lower quality
        if (file_exists($optimized_path)) {
            unlink($optimized_path);
        }
        
        $image_editor->set_quality(70);
        $save_result = $image_editor->save($optimized_path);
        
        if (!is_wp_error($save_result) && file_exists($optimized_path) && filesize($optimized_path) <= self::MAX_FILE_SIZE) {
            return $optimized_path;
        }
        
        // Clean up if optimization failed
        if (file_exists($optimized_path)) {
            unlink($optimized_path);
        }
        
        return null;
    }
    
    /**
     * Get target size based on original dimensions
     */
    private function get_target_size($width, $height) {
        $aspect_ratio = $width / $height;
        
        // Determine best fit based on aspect ratio
        if ($aspect_ratio > 1.5) {
            // Landscape
            return self::RECOMMENDED_SIZES['landscape'];
        } elseif ($aspect_ratio < 0.9) {
            // Portrait
            return self::RECOMMENDED_SIZES['portrait'];
        } else {
            // Square-ish
            return self::RECOMMENDED_SIZES['square'];
        }
    }
    
    /**
     * Get optimal quality setting based on MIME type
     */
    private function get_optimal_quality($mime_type) {
        switch ($mime_type) {
            case 'image/jpeg':
                return 85;
            case 'image/png':
                return 90;
            case 'image/webp':
                return 85;
            default:
                return 85;
        }
    }
    
    /**
     * Get recommended image from WordPress sizes
     */
    public function get_best_wordpress_size($attachment_id) {
        $size_preference = get_option('auto_post_x_image_size_preference', 'large');
        
        // Try to get the preferred size first
        $image_url = wp_get_attachment_image_url($attachment_id, $size_preference);
        if ($image_url) {
            $image_path = $this->url_to_path($image_url);
            if ($image_path && $this->is_suitable_for_x($image_path)) {
                return $image_path;
            }
        }
        
        // If preferred size doesn't work, try other sizes
        $sizes_to_try = array('large', 'medium_large', 'medium', 'full');
        
        foreach ($sizes_to_try as $size) {
            if ($size === $size_preference) continue; // Already tried
            
            $image_url = wp_get_attachment_image_url($attachment_id, $size);
            if ($image_url) {
                $image_path = $this->url_to_path($image_url);
                if ($image_path && $this->is_suitable_for_x($image_path)) {
                    return $image_path;
                }
            }
        }
        
        // If no WordPress size works, return the full size for optimization
        return get_attached_file($attachment_id);
    }
    
    /**
     * Convert image URL to file path
     */
    private function url_to_path($url) {
        $upload_dir = wp_upload_dir();
        $upload_url = $upload_dir['baseurl'];
        $upload_path = $upload_dir['basedir'];
        
        if (strpos($url, $upload_url) === 0) {
            return str_replace($upload_url, $upload_path, $url);
        }
        
        return null;
    }
    
    /**
     * Check if image is suitable for X without optimization
     */
    private function is_suitable_for_x($image_path) {
        if (!file_exists($image_path)) {
            return false;
        }
        
        // Check file size
        if (filesize($image_path) > self::MAX_FILE_SIZE) {
            return false;
        }
        
        // Check dimensions
        $image_info = getimagesize($image_path);
        if (!$image_info) {
            return false;
        }
        
        $width = $image_info[0];
        $height = $image_info[1];
        $aspect_ratio = $width / $height;
        
        // Check aspect ratio constraints
        return ($aspect_ratio >= 0.33 && $aspect_ratio <= 3.0);
    }
    
    /**
     * Clean up old optimized images
     */
    public function cleanup_old_optimized_images() {
        $upload_dir = wp_upload_dir();
        $upload_path = $upload_dir['basedir'];
        
        // Find files that end with '-x-optimized.*'
        $pattern = $upload_path . '/*-x-optimized.*';
        $files = glob($pattern);
        
        $cutoff_time = time() - (7 * 24 * 60 * 60); // 7 days ago
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff_time) {
                unlink($file);
            }
        }
    }
    
    /**
     * Get image dimensions info
     */
    public function get_image_info($image_path) {
        if (!file_exists($image_path)) {
            return null;
        }
        
        $image_info = getimagesize($image_path);
        if (!$image_info) {
            return null;
        }
        
        $file_size = filesize($image_path);
        $width = $image_info[0];
        $height = $image_info[1];
        $aspect_ratio = $width / $height;
        
        return array(
            'width' => $width,
            'height' => $height,
            'aspect_ratio' => $aspect_ratio,
            'file_size' => $file_size,
            'file_size_mb' => round($file_size / (1024 * 1024), 2),
            'mime_type' => $image_info['mime'],
            'is_x_compatible' => $this->is_suitable_for_x($image_path)
        );
    }
} 