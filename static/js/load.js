// Get base href from global variable or fallback to current protocol and host
const getBaseHref = () => {
    if (window.baseHref) {
        return window.baseHref;
    }
    
    // Fallback: try to detect if we're on localhost or live
    const host = window.location.host;
    const isLocalhost = host.includes('localhost') || host.includes('127.0.0.1') || host.includes('.local');
    const protocol = window.location.protocol;
    
    if (isLocalhost) {
        return `${protocol}//${host}/Art2Cart/`;
    } else {
        return `${protocol}//${host}/`;
    }
};

// Initialize theme immediately - ENSURE CONSISTENT DEFAULT (only if not already initialized)
(function() {
    // Check if theme has already been initialized
    if (document.documentElement.hasAttribute('data-theme-initialized')) {
        console.log('load.js: Theme already initialized, skipping');
        return;
    }
    
    console.log('load.js: Starting theme initialization');
    const savedTheme = localStorage.getItem('art2cart-theme');
    console.log('load.js: Found saved theme:', savedTheme);
    
    // ALWAYS set theme explicitly - default to light
    const themeToApply = savedTheme || 'light';
    console.log('load.js: Applying theme:', themeToApply);
    document.documentElement.setAttribute('data-theme', themeToApply);
    document.documentElement.setAttribute('data-theme-initialized', 'true');
    
    // If no theme was saved, save light as default
    if (!savedTheme) {
        localStorage.setItem('art2cart-theme', 'light');
        console.log('load.js: Saved default light theme to localStorage');
    }
})();

// Global Dark Mode Functionality - Only define if not already defined (for dynamic loading compatibility)
if (typeof window.toggleDarkMode !== 'function') {
    function toggleDarkMode() {
        console.log('toggleDarkMode: Function called from load.js');
        const html = document.documentElement;
        const currentTheme = html.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        console.log('toggleDarkMode: Switching from', currentTheme, 'to', newTheme);
        
        html.setAttribute('data-theme', newTheme);
        localStorage.setItem('art2cart-theme', newTheme);
        
        // Dispatch custom event for other components to listen to
        const event = new CustomEvent('themeChanged', { detail: { theme: newTheme } });
        document.dispatchEvent(event);
        
        console.log('toggleDarkMode: Theme changed successfully to', newTheme);
    }
    
    // Make function globally available
    window.toggleDarkMode = toggleDarkMode;
    console.log('load.js: toggleDarkMode function defined and made globally available');
}

// Load header function
function loadHeader() {
    console.log('load.js: Starting header load process');
    const baseHref = getBaseHref();
    console.log('load.js: Base href:', baseHref);
    
    const headerElem = document.getElementById("header");
    if (!headerElem) {
        console.error('load.js: Header element not found in DOM');
        return;
    }
    
    console.log('load.js: Header element found, fetching header content');
    fetch(`${baseHref}static/templates/header_new.php`)
      .then(res => {
        console.log('load.js: Header fetch response status:', res.status);
        if (!res.ok) {
          throw new Error(`HTTP error! status: ${res.status}`);
        }
        return res.text();
      })
      .then(data => {
        console.log('load.js: Header content received, length:', data.length);
        headerElem.innerHTML = data;
        console.log('load.js: Header content inserted into DOM');
        
        console.log('load.js: Header loaded, initializing components');
        
        // Initialize components with proper delays
        setTimeout(() => {
            initializeDropdown();
            initializeDarkMode();
            
            // Dispatch event when header is loaded
            const event = new CustomEvent('headerLoaded');
            document.dispatchEvent(event);
        }, 150);
      })
      .catch(err => {
        console.error('Failed to load header:', err);
      });
}

// Load header when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadHeader);
} else {
    // DOM is already loaded
    loadHeader();
}

// Function to initialize dropdown
function initializeDropdown() {
    const accountWrapper = document.getElementById('accountWrapper');
    if (accountWrapper) {        const handleDropdownToggle = (e) => {
            const isLogoutLink = e.target.closest(`a[href="${baseHref}auth/logout.php"]`);
            const isMenuLink = e.target.closest('.account-dropdown a');
            
            // Allow normal link behavior for logout and menu items
            if (!isLogoutLink && !isMenuLink) {
                e.preventDefault();
            }
            
            if (!isMenuLink) {
                e.stopPropagation();
            }
            
            const dropdown = accountWrapper.querySelector('.account-dropdown');
            if (dropdown) {
                const isShown = dropdown.classList.contains('show');
                // Close any other open dropdowns first
                document.querySelectorAll('.account-dropdown.show').forEach(d => {
                    if (d !== dropdown) d.classList.remove('show');
                });
                // Toggle current dropdown
                dropdown.classList.toggle('show');
                // Set aria-expanded attribute
                accountWrapper.setAttribute('aria-expanded', !isShown);
            }
        };

        // Add click event for dropdown toggle
        accountWrapper.addEventListener('click', handleDropdownToggle);

        // Close dropdown when pressing Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const dropdown = accountWrapper.querySelector('.account-dropdown');
                if (dropdown && dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                    accountWrapper.setAttribute('aria-expanded', 'false');
                }
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!accountWrapper.contains(e.target)) {
                const dropdown = accountWrapper.querySelector('.account-dropdown');
                if (dropdown && dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                    accountWrapper.setAttribute('aria-expanded', 'false');
                }
            }
        });
    }
}

// Function to initialize dark mode functionality
function initializeDarkMode() {
    console.log('load.js: Initializing dark mode functionality');
    
    // Method 1: Use event delegation on document for immediate response
    document.addEventListener('click', function(e) {
        if (e.target.closest('.dark-mode-toggle')) {
            console.log('load.js: Dark mode toggle clicked via event delegation!');
            e.preventDefault();
            e.stopPropagation();
            
            // Call the global toggleDarkMode function
            if (typeof window.toggleDarkMode === 'function') {
                console.log('load.js: Calling window.toggleDarkMode');
                window.toggleDarkMode();
            } else if (typeof toggleDarkMode === 'function') {
                console.log('load.js: Calling global toggleDarkMode');
                toggleDarkMode();
            } else {
                console.error('load.js: toggleDarkMode function not found!');
            }
            return false;
        }
    });
    
    // Method 2: Also try direct binding after a delay (backup)
    setTimeout(() => {
        const darkModeToggle = document.querySelector('.dark-mode-toggle');
        if (darkModeToggle) {
            console.log('load.js: Found dark mode toggle button, applying direct binding as backup');
            
            // Clear any existing onclick handler
            darkModeToggle.onclick = null;
            
            // Set up the click handler as backup
            darkModeToggle.onclick = function(e) {
                console.log('load.js: Dark mode toggle clicked via direct onclick!');
                e.preventDefault();
                e.stopPropagation();
                
                // Call the global toggleDarkMode function
                if (typeof window.toggleDarkMode === 'function') {
                    console.log('load.js: Calling window.toggleDarkMode (backup)');
                    window.toggleDarkMode();
                } else if (typeof toggleDarkMode === 'function') {
                    console.log('load.js: Calling global toggleDarkMode (backup)');
                    toggleDarkMode();
                } else {
                    console.error('load.js: toggleDarkMode function not found! (backup)');
                }
                return false;
            };
            
            console.log('load.js: Dark mode toggle direct binding completed');
        } else {
            console.error('load.js: Dark mode toggle button not found in DOM');
            
            // Debug: log all buttons to see what's available
            const allButtons = document.querySelectorAll('button');
            console.log('load.js: Found', allButtons.length, 'buttons total:');
            allButtons.forEach((btn, index) => {
                console.log(`  Button ${index}:`, {
                    className: btn.className,
                    id: btn.id,
                    ariaLabel: btn.getAttribute('aria-label'),
                    onclick: btn.onclick ? 'has onclick' : 'no onclick'
                });
            });
        }
    }, 300);
    
    console.log('load.js: Dark mode initialization completed');
}

// Global Dark Mode Functionality
function toggleDarkMode() {
    console.log('toggleDarkMode: Function called from load.js');
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-theme') || 'light';
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    console.log('toggleDarkMode: Switching from', currentTheme, 'to', newTheme);
    
    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('art2cart-theme', newTheme);
    
    // Dispatch custom event for other components to listen to
    const event = new CustomEvent('themeChanged', { detail: { theme: newTheme } });
    document.dispatchEvent(event);
    
    console.log('toggleDarkMode: Theme changed successfully to', newTheme);
}

// Make function globally available
window.toggleDarkMode = toggleDarkMode;

// Load footer function
function loadFooter() {
    console.log('load.js: Starting footer load process');
    const baseHref = getBaseHref();
    console.log('load.js: Base href for footer:', baseHref);
    
    const footerElem = document.getElementById("footer");
    if (!footerElem) {
        console.error('load.js: Footer element not found in DOM');
        return;
    }
    
    console.log('load.js: Footer element found, fetching footer content');
    fetch(`${baseHref}static/templates/footer_new.html`)
      .then(res => {
        console.log('load.js: Footer fetch response status:', res.status);
        if (!res.ok) {
          throw new Error(`HTTP error! status: ${res.status}`);
        }
        return res.text();
      })
      .then(data => {
        console.log('load.js: Footer content received, length:', data.length);
        footerElem.innerHTML = data;
        console.log('load.js: Footer content inserted into DOM');
        
        // Load footer CSS files dynamically with correct base URL
        const footerCssFiles = [
          'static/css/template/footer.css',
          'static/css/var.css',
          'static/css/fonts.css'
        ];
        
        footerCssFiles.forEach(cssFile => {
          // Check if CSS file is already loaded to avoid duplicates
          const existingLink = document.querySelector(`link[href="${baseHref}${cssFile}"]`);
          if (!existingLink) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = `${baseHref}${cssFile}`;
            document.head.appendChild(link);
          }
        });
        
        // Fix image sources in footer to use correct base URL
        const footerImages = document.querySelectorAll('#footer img');
        footerImages.forEach(img => {
          const src = img.getAttribute('src');
          if (src && src.startsWith('static/')) {
            // Fix the logo filename case and add base URL
            if (src.includes('logo.png')) {
              img.src = `${baseHref}static/images/Logo.png`;
            } else {
              img.src = `${baseHref}${src}`;
            }
          }
        });
      })
      .catch(err => {
        console.error('Failed to load footer:', err);
      });
}

// Load footer when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadFooter);
} else {
    // DOM is already loaded
    loadFooter();
}
