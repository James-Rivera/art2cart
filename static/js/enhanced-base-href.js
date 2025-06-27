/**
 * Enhanced Dynamic Base Href Generator for HTML Files
 * Works for both localhost (WAMP) and live server (Cloudflare)
 * Version 2.0 - Enhanced with better detection and configuration
 */

(function(window, document) {
    'use strict';
    
    // Configuration object
    const config = {
        localhostPath: '/Art2Cart/',
        livePath: '/',
        forceHttps: false,
        debug: false,
        autoDetectScriptDir: true,
        cloudflareDetection: true
    };
    
    /**
     * Initialize the enhanced base href system
     * @param {Object} userConfig - Custom configuration options
     */
    function init(userConfig = {}) {
        Object.assign(config, userConfig);
        
        if (config.debug) {
            console.log('EnhancedBaseHref: Initialized with config:', config);
        }
    }
    
    /**
     * Detect if we're running on HTTPS
     * @returns {boolean}
     */
    function isHttps() {
        // Force HTTPS if configured
        if (config.forceHttps) {
            return true;
        }
        
        // Check current protocol
        return window.location.protocol === 'https:';
    }
    
    /**
     * Check if current host is localhost
     * @returns {boolean}
     */
    function isLocalhost() {
        const host = window.location.hostname.toLowerCase();
        const localhostPatterns = [
            'localhost',
            '127.0.0.1',
            '::1',
            '0.0.0.0'
        ];
        
        return localhostPatterns.some(pattern => host.includes(pattern)) ||
               host.endsWith('.local') ||
               host.endsWith('.test') ||
               host.endsWith('.dev') ||
               host.endsWith('.localhost');
    }
    
    /**
     * Auto-detect the script directory from current location
     * @returns {string|null}
     */
    function detectScriptDirectory() {
        if (!config.autoDetectScriptDir) {
            return null;
        }
        
        const pathname = window.location.pathname;
        
        // If we're at the root, return root
        if (pathname === '/' || pathname === '') {
            return '/';
        }
        
        // Extract directory from current path
        let dir = pathname;
        
        // If path ends with a file (has extension), get directory
        if (pathname.includes('.') && !pathname.endsWith('/')) {
            dir = pathname.substring(0, pathname.lastIndexOf('/') + 1);
        } else if (!pathname.endsWith('/')) {
            dir += '/';
        }
        
        // Look for Art2Cart in the path
        if (dir.includes('/Art2Cart/')) {
            const index = dir.indexOf('/Art2Cart/');
            return dir.substring(0, index + 10); // +10 for '/Art2Cart/'
        }
        
        if (config.debug) {
            console.log('EnhancedBaseHref: Auto-detected directory:', dir);
        }
        
        return dir;
    }
    
    /**
     * Get the project path based on environment
     * @returns {string}
     */
    function getProjectPath() {
        if (isLocalhost()) {
            return config.localhostPath;
        }
        
        // Try auto-detection first
        const autoDetected = detectScriptDirectory();
        if (autoDetected !== null) {
            return autoDetected;
        }
        
        // Use configured live path
        return config.livePath;
    }
    
    /**
     * Get the dynamic base URL for the current environment
     * @returns {string} The complete base URL
     */
    function getDynamicBaseUrl() {
        const protocol = isHttps() ? 'https:' : 'http:';
        const host = window.location.host; // includes port if non-standard
        const projectPath = getProjectPath();
        
        const baseUrl = protocol + '//' + host + projectPath;
        
        if (config.debug) {
            console.log('EnhancedBaseHref: Generated base URL:', baseUrl);
        }
        
        return baseUrl;
    }
    
    /**
     * Set the base href dynamically in the document
     */
    function setDynamicBaseHref() {
        const baseUrl = getDynamicBaseUrl();
        
        // Check if base tag already exists
        let baseTag = document.querySelector('base[href]');
        
        if (!baseTag) {
            // Create new base tag
            baseTag = document.createElement('base');
            
            // Insert base tag as first element in head
            const head = document.head || document.getElementsByTagName('head')[0];
            
            if (head.firstElementChild) {
                head.insertBefore(baseTag, head.firstElementChild);
            } else {
                head.appendChild(baseTag);
            }
            
            if (config.debug) {
                console.log('EnhancedBaseHref: Created new base tag');
            }
        } else {
            if (config.debug) {
                console.log('EnhancedBaseHref: Found existing base tag, updating href');
            }
        }
        
        // Set the href attribute
        baseTag.href = baseUrl;
        
        if (config.debug) {
            console.log('EnhancedBaseHref: Set base href to:', baseUrl);
        }
        
        return baseUrl;
    }
    
    /**
     * Convert relative path to absolute path
     * @param {string} relativePath - The relative path
     * @returns {string} The absolute path
     */
    function toAbsolutePath(relativePath) {
        const baseUrl = getDynamicBaseUrl().replace(/\/$/, ''); // Remove trailing slash
        const cleanPath = relativePath.replace(/^\/+/, ''); // Remove leading slashes
        
        return baseUrl + '/' + cleanPath;
    }
    
    /**
     * Get the base path without protocol and host
     * @returns {string}
     */
    function getBasePath() {
        return getProjectPath();
    }
    
    /**
     * Get comprehensive environment info for debugging
     * @returns {Object}
     */
    function getEnvironmentInfo() {
        return {
            baseUrl: getDynamicBaseUrl(),
            protocol: window.location.protocol,
            host: window.location.host,
            hostname: window.location.hostname,
            port: window.location.port,
            pathname: window.location.pathname,
            projectPath: getProjectPath(),
            isLocalhost: isLocalhost(),
            isHttps: isHttps(),
            config: { ...config },
            userAgent: navigator.userAgent,
            timestamp: new Date().toISOString()
        };
    }
    
    /**
     * Update configuration at runtime
     * @param {Object} newConfig - New configuration options
     */
    function updateConfig(newConfig) {
        Object.assign(config, newConfig);
        
        if (config.debug) {
            console.log('EnhancedBaseHref: Configuration updated:', config);
        }
    }
    
    /**
     * Quick setup function for immediate use
     * @param {Object} userConfig - Optional configuration
     * @returns {string} The base URL that was set
     */
    function quickSetup(userConfig = {}) {
        init(userConfig);
        return setDynamicBaseHref();
    }
    
    /**
     * Test function to verify the base href is working correctly
     * @returns {Object} Test results
     */
    function runTests() {
        const baseUrl = getDynamicBaseUrl();
        const testPaths = [
            'static/css/style.css',
            'static/js/main.js',
            'static/images/logo.png',
            'includes/header.php'
        ];
        
        const results = {
            baseUrl: baseUrl,
            isLocalhost: isLocalhost(),
            isHttps: isHttps(),
            testPaths: testPaths.map(path => ({
                relative: path,
                absolute: toAbsolutePath(path)
            }))
        };
        
        if (config.debug) {
            console.table(results.testPaths);
        }
        
        return results;
    }
    
    // Auto-initialize when DOM is ready
    function autoInit() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setDynamicBaseHref);
        } else {
            setDynamicBaseHref();
        }
    }
    
    // Initialize automatically unless disabled
    if (typeof window.Art2CartAutoInit === 'undefined' || window.Art2CartAutoInit !== false) {
        autoInit();
    }
    
    // Expose enhanced API globally
    window.Art2Cart = window.Art2Cart || {};
    window.Art2Cart.EnhancedBaseHref = {
        // Main functions
        init: init,
        getDynamicBaseUrl: getDynamicBaseUrl,
        setDynamicBaseHref: setDynamicBaseHref,
        toAbsolutePath: toAbsolutePath,
        
        // Utility functions
        getBasePath: getBasePath,
        isLocalhost: isLocalhost,
        isHttps: isHttps,
        
        // Configuration and debugging
        updateConfig: updateConfig,
        getEnvironmentInfo: getEnvironmentInfo,
        runTests: runTests,
        
        // Quick setup
        quickSetup: quickSetup
    };
    
    // Maintain backward compatibility
    window.Art2Cart.BaseHref = window.Art2Cart.EnhancedBaseHref;
    
})(window, document);
