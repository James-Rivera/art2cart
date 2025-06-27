<?php
/**
 * Enhanced Dynamic Base Href Generator for Art2Cart
 * Works for both localhost (WAMP) and live server (Cloudflare)
 * Handles HTTP/HTTPS, subdirectories, and various hosting scenarios
 * 
 * @version 2.0
 * @author Art2Cart Development Team
 */

class EnhancedBaseHref {
    private static $baseUrl = null;
    private static $config = null;
    private static $debug = false;
    
    /**
     * Initialize the base href system
     * @param array $config Custom configuration options
     */
    public static function init($config = []) {
        self::$config = array_merge([
            'localhost_path' => '/Art2Cart/',
            'live_path' => '/',
            'force_https' => false,
            'debug' => false,
            'cloudflare_detection' => true,
            'auto_detect_script_dir' => true
        ], $config);
        
        self::$debug = self::$config['debug'];
    }
    
    /**
     * Get the dynamic base URL for the current environment
     * @return string The complete base URL
     */
    public static function getBaseUrl() {
        if (self::$baseUrl !== null) {
            return self::$baseUrl;
        }
        
        if (self::$config === null) {
            self::init();
        }
        
        // Detect protocol
        $protocol = self::detectProtocol();
        
        // Get the host
        $host = self::detectHost();
        
        // Get the project path
        $projectPath = self::detectProjectPath();
        
        // Construct the base URL
        self::$baseUrl = $protocol . '://' . $host . $projectPath;
        
        if (self::$debug) {
            error_log("EnhancedBaseHref: Generated base URL: " . self::$baseUrl);
        }
        
        return self::$baseUrl;
    }
    
    /**
     * Generate the complete base href tag
     * @return string The HTML base tag
     */
    public static function getBaseHrefTag() {
        return '<base href="' . htmlspecialchars(self::getBaseUrl(), ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Echo the base href tag directly
     */
    public static function echoBaseHrefTag() {
        echo self::getBaseHrefTag();
    }
    
    /**
     * Detect the current protocol with enhanced Cloudflare support
     * @return string 'http' or 'https'
     */
    private static function detectProtocol() {
        // Force HTTPS if configured
        if (self::$config['force_https']) {
            return 'https';
        }
        
        // Check for HTTPS in various ways
        $httpsChecks = [
            // Standard HTTPS check
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            
            // Cloudflare and proxy headers
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'),
            (!empty($_SERVER['HTTP_CF_VISITOR']) && strpos($_SERVER['HTTP_CF_VISITOR'], '"scheme":"https"') !== false),
            (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on'),
            (!empty($_SERVER['HTTP_X_FORWARDED_SCHEME']) && $_SERVER['HTTP_X_FORWARDED_SCHEME'] === 'https'),
            
            // Port-based detection
            (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443),
            
            // Load balancer headers
            (!empty($_SERVER['HTTP_X_PROTO']) && $_SERVER['HTTP_X_PROTO'] === 'SSL'),
        ];
        
        foreach ($httpsChecks as $isHttps) {
            if ($isHttps) {
                return 'https';
            }
        }
        
        return 'http';
    }
    
    /**
     * Detect the current host with enhanced proxy support
     * @return string The hostname with port if necessary
     */
    private static function detectHost() {
        // Try different host headers in order of preference
        $hostHeaders = [
            'HTTP_X_FORWARDED_HOST',    // Cloudflare and proxies
            'HTTP_HOST',                // Standard HTTP host
            'SERVER_NAME'               // Fallback
        ];
        
        foreach ($hostHeaders as $header) {
            if (!empty($_SERVER[$header])) {
                $host = $_SERVER[$header];
                
                // Handle multiple forwarded hosts (take the first)
                if (strpos($host, ',') !== false) {
                    $host = trim(explode(',', $host)[0]);
                }
                
                // Remove port from host if it's standard
                $host = self::normalizeHost($host);
                
                if (self::$debug) {
                    error_log("EnhancedBaseHref: Using host from {$header}: {$host}");
                }
                
                return $host;
            }
        }
        
        return 'localhost';
    }
    
    /**
     * Normalize host by removing standard ports
     * @param string $host
     * @return string
     */
    private static function normalizeHost($host) {
        $protocol = self::detectProtocol();
        
        // Remove standard ports
        if ($protocol === 'https' && str_ends_with($host, ':443')) {
            return substr($host, 0, -4);
        }
        
        if ($protocol === 'http' && str_ends_with($host, ':80')) {
            return substr($host, 0, -3);
        }
        
        return $host;
    }
    
    /**
     * Detect the project path based on environment and configuration
     * @return string The project path
     */
    private static function detectProjectPath() {
        $host = self::detectHost();
          // Check if localhost
        if (self::isLocalhostHost($host)) {
            return self::$config['localhost_path'];
        }
        
        // Auto-detect script directory if enabled
        if (self::$config['auto_detect_script_dir']) {
            $detectedPath = self::detectScriptDirectory();
            if ($detectedPath !== null) {
                return $detectedPath;
            }
        }
        
        // Use configured live path
        return self::$config['live_path'];
    }
      /**
     * Check if the current host is localhost
     * @param string $host
     * @return bool
     */
    private static function isLocalhostHost($host) {
        $localhostPatterns = [
            'localhost',
            '127.0.0.1',
            '::1',
            '0.0.0.0',
            '.local',
            '.test',
            '.dev',
            '.localhost'
        ];
        
        $host = strtolower($host);
        
        foreach ($localhostPatterns as $pattern) {
            if (strpos($host, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Auto-detect the script directory from server variables
     * @return string|null The detected path or null if cannot detect
     */
    private static function detectScriptDirectory() {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        
        if (empty($scriptName)) {
            return null;
        }
        
        // Extract the directory path from script name
        $scriptDir = dirname($scriptName);
        
        // Normalize path
        $scriptDir = str_replace('\\', '/', $scriptDir);
        $scriptDir = rtrim($scriptDir, '/') . '/';
        
        // If script is in root, return root
        if ($scriptDir === '/') {
            return '/';
        }
        
        if (self::$debug) {
            error_log("EnhancedBaseHref: Auto-detected script directory: {$scriptDir}");
        }
        
        return $scriptDir;
    }
    
    /**
     * Convert a relative path to absolute path based on base URL
     * @param string $relativePath
     * @return string
     */
    public static function toAbsolutePath($relativePath) {
        $baseUrl = rtrim(self::getBaseUrl(), '/');
        $relativePath = ltrim($relativePath, '/');
        
        return $baseUrl . '/' . $relativePath;
    }
    
    /**
     * Get the base path without protocol and host
     * @return string
     */
    public static function getBasePath() {
        if (self::$config === null) {
            self::init();
        }
        
        $host = self::detectHost();
        return self::isLocalhost($host) ? 
            self::$config['localhost_path'] : 
            self::$config['live_path'];
    }
    
    /**
     * Check if we're running on HTTPS
     * @return bool
     */
    public static function isHttps() {
        return self::detectProtocol() === 'https';
    }
      /**
     * Check if we're running on localhost
     * @return bool
     */
    public static function isLocalhost() {
        return self::isLocalhostHost(self::detectHost());
    }
    
    /**
     * Get comprehensive environment info for debugging
     * @return array
     */
    public static function getEnvironmentInfo() {
        return [
            'base_url' => self::getBaseUrl(),
            'protocol' => self::detectProtocol(),
            'host' => self::detectHost(),
            'project_path' => self::detectProjectPath(),
            'is_localhost' => self::isLocalhost(),
            'is_https' => self::isHttps(),
            'config' => self::$config,
            'server_vars' => [
                'SERVER_NAME' => $_SERVER['SERVER_NAME'] ?? 'N/A',
                'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'N/A',
                'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'N/A',
                'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? 'N/A',
                'SERVER_PORT' => $_SERVER['SERVER_PORT'] ?? 'N/A',
                'HTTPS' => $_SERVER['HTTPS'] ?? 'N/A',
                'HTTP_X_FORWARDED_PROTO' => $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'N/A',
                'HTTP_CF_VISITOR' => $_SERVER['HTTP_CF_VISITOR'] ?? 'N/A',
                'HTTP_X_FORWARDED_HOST' => $_SERVER['HTTP_X_FORWARDED_HOST'] ?? 'N/A',
            ]
        ];
    }
    
    /**
     * Reset cached values (useful for testing)
     */
    public static function reset() {
        self::$baseUrl = null;
        self::$config = null;
    }
    
    /**
     * Simple function for quick implementation
     * @param array $config Optional configuration
     * @return string Base href tag
     */
    public static function quickSetup($config = []) {
        self::init($config);
        return self::getBaseHrefTag();
    }
}

// Compatibility functions for easy migration
if (!function_exists('getEnhancedBaseHref')) {
    function getEnhancedBaseHref() {
        return EnhancedBaseHref::getBaseUrl();
    }
}

if (!function_exists('echoEnhancedBaseHref')) {
    function echoEnhancedBaseHref() {
        EnhancedBaseHref::echoBaseHrefTag();
    }
}
