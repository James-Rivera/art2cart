// Load header
fetch('/Art2Cart/static/templates/header_new.php')
  .then(res => res.text())
  .then(data => {
    const headerElem = document.getElementById("header");
    headerElem.innerHTML = data;
    
    // Initialize dropdown functionality
    initializeDropdown();
    
    // Dispatch event when header is loaded
    const event = new CustomEvent('headerLoaded');
    document.dispatchEvent(event);
  })
  .catch(err => {
    console.error('Failed to load header:', err);
  });

// Function to initialize dropdown
function initializeDropdown() {
    const accountWrapper = document.getElementById('accountWrapper');
    if (accountWrapper) {
        const handleDropdownToggle = (e) => {
            const isLogoutLink = e.target.closest('a[href="/Art2Cart/auth/logout.php"]');
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

// Load footer
fetch('/Art2Cart/static/templates/footer_new.html')
  .then(res => res.text())
  .then(data => {
    document.getElementById("footer").innerHTML = data;
  })
  .catch(err => {
    console.error('Failed to load footer:', err);
  });
