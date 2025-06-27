/**
 * Dynamic Base Href Generator for Pure HTML Files
 * This JavaScript solution works in environments where PHP is not available
 */

(function() {
    'use strict';
    
    /**
     * Get the dynamic base URL for the current environment
     * @returns {string} The complete base URL
     */
    function getDynamicBaseUrl() {
        // Get protocol
        const protocol = window.location.protocol; // includes the ':'
        
        // Get host (includes port if non-standard)
        const host = window.location.host;
        
        // Determine project path
        let projectPath = '/Art2Cart/';
        
        // Check if we're on localhost
        if (host.includes('localhost') || host.includes('127.0.0.1')) {
            projectPath = '/Art2Cart/';
        } else {
            // On live server - adjust this based on your deployment
            // If your live site is at the domain root, use:
            // projectPath = '/';
            projectPath = '/Art2Cart/'; // Keep this if deploying to a subdirectory
        }
        
        return protocol + '//' + host + projectPath;
    }
    
    /**
     * Set the base href dynamically
     */
    function setDynamicBaseHref() {
        const baseUrl = getDynamicBaseUrl();
        
        // Check if base tag already exists
        let baseTag = document.querySelector('base');
        
        if (!baseTag) {
            // Create new base tag
            baseTag = document.createElement('base');
            
            // Insert base tag as first element in head
            const head = document.head || document.getElementsByTagName('head')[0];
            const firstChild = head.firstChild;
            
            if (firstChild) {
                head.insertBefore(baseTag, firstChild);
            } else {
                head.appendChild(baseTag);
            }
        }
        
        // Set the href attribute
        baseTag.href = baseUrl;
        
        // Log for debugging (remove in production)
        console.log('Dynamic Base Href set to:', baseUrl);
    }
    
    /**
     * Convert relative path to absolute path
     * @param {string} relativePath - The relative path
     * @returns {string} The absolute path
     */
    function toAbsolutePath(relativePath) {
        const baseUrl = getDynamicBaseUrl();
        return baseUrl + relativePath.replace(/^\/+/, '');
    }
    
    /**
     * Get environment info for debugging
     * @returns {object} Environment information
     */
    function getEnvironmentInfo() {
        return {
            protocol: window.location.protocol,
            host: window.location.host,
            hostname: window.location.hostname,
            port: window.location.port,
            pathname: window.location.pathname,
            baseUrl: getDynamicBaseUrl(),
            userAgent: navigator.userAgent
        };
    }
    
    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setDynamicBaseHref);
    } else {
        setDynamicBaseHref();
    }
    
    // Expose functions globally for external use
    window.Art2Cart = window.Art2Cart || {};
    window.Art2Cart.BaseHref = {
        getDynamicBaseUrl: getDynamicBaseUrl,
        setDynamicBaseHref: setDynamicBaseHref,
        toAbsolutePath: toAbsolutePath,
        getEnvironmentInfo: getEnvironmentInfo
    };
    
})();
