<?php
/**
 * Base Href Configuration for Art2Cart
 * Modify these settings based on your deployment requirements
 */

class Art2CartConfig {
    /**
     * Get configuration for the current environment
     * Modify these values based on your specific setup
     */    public static function getBaseHrefConfig() {
        // Get the current host
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
          // Special handling for art2cart.shop domain through Cloudflare tunnel
        if ($host === 'art2cart.shop') {
            return [
                'localhost_path' => '/Art2Cart/',
                'live_path' => '/',              
                'debug' => true,                 // Enable debugging for tunnel setup
                'force_https' => false,         // Don't force HTTPS - let tunnel decide
                'auto_detect_script_dir' => false, // Don't auto-detect, use live_path
                'cloudflare_detection' => true,
            ];
        }
        
        // Auto-detect environment type for other domains
        $isLocalhost = self::isLocalhost();
        
        if ($isLocalhost) {
            // DEVELOPMENT CONFIGURATION (WAMP/XAMPP)
            return [
                'localhost_path' => '/Art2Cart/',
                'live_path' => '/',              
                'debug' => true,                 
                'force_https' => false,          
                'auto_detect_script_dir' => true,
                'cloudflare_detection' => true,
            ];
        } else {
            // PRODUCTION CONFIGURATION
            return [
                'localhost_path' => '/Art2Cart/',
                'live_path' => '/',              
                'debug' => false,                
                'force_https' => true,          // Force HTTPS in production
                'auto_detect_script_dir' => true,
                'cloudflare_detection' => true,
            ];
        }
    }
    
    /**
     * Quick setup function - call this in your files
     */
    public static function setupBaseHref() {
        require_once __DIR__ . '/EnhancedBaseHref.php';
        EnhancedBaseHref::init(self::getBaseHrefConfig());
        return EnhancedBaseHref::getBaseHrefTag();
    }
    
    /**
     * Echo base href tag directly
     */
    public static function echoBaseHref() {
        echo self::setupBaseHref();
    }
    
    /**
     * Get base URL
     */
    public static function getBaseUrl() {
        require_once __DIR__ . '/EnhancedBaseHref.php';
        EnhancedBaseHref::init(self::getBaseHrefConfig());
        return EnhancedBaseHref::getBaseUrl();
    }
    
    /**
     * Check if running on localhost
     */
    private static function isLocalhost() {
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $localhostPatterns = ['localhost', '127.0.0.1', '::1', '.local', '.test', '.dev'];
        
        foreach ($localhostPatterns as $pattern) {
            if (strpos(strtolower($host), $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * DEPLOYMENT CONFIGURATIONS
     * Uncomment and modify the appropriate section based on your deployment
     */
    
    /*
    // FOR SUBDIRECTORY DEPLOYMENT (e.g., yourdomain.com/shop/)
    public static function getBaseHrefConfig() {
        return [
            'localhost_path' => '/Art2Cart/',
            'live_path' => '/shop/',         // Your subdirectory
            'debug' => false,
            'force_https' => true,
        ];
    }
    */
    
    /*
    // FOR ROOT DOMAIN DEPLOYMENT (e.g., yourdomain.com/)
    public static function getBaseHrefConfig() {
        return [
            'localhost_path' => '/Art2Cart/',
            'live_path' => '/',              // Root of domain
            'debug' => false,
            'force_https' => true,
        ];
    }
    */
    
    /*
    // FOR CLOUDFLARE WITH CUSTOM DOMAIN
    public static function getBaseHrefConfig() {
        return [
            'localhost_path' => '/Art2Cart/',
            'live_path' => '/',
            'debug' => false,
            'force_https' => true,           // Cloudflare handles SSL
            'cloudflare_detection' => true,  // Enhanced Cloudflare support
        ];
    }
    */
}

// Convenience functions for easy migration
if (!function_exists('art2cart_base_href')) {
    function art2cart_base_href() {
        return Art2CartConfig::setupBaseHref();
    }
}

if (!function_exists('art2cart_echo_base_href')) {
    function art2cart_echo_base_href() {
        Art2CartConfig::echoBaseHref();
    }
}

if (!function_exists('art2cart_base_url')) {
    function art2cart_base_url() {
        return Art2CartConfig::getBaseUrl();
    }
}
