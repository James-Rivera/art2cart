// Main JavaScript functionality for Art2Cart

// Function to initialize header behavior
function initializeHeader() {
    const header = document.querySelector('.header-bar');
    if (!header) return;

    if (window.isIndexPage) {
        // Set initial state to transparent
        header.classList.remove('header-colored');
        header.classList.add('header-transparent');

        // Function to update header style
        function updateHeaderStyle() {
            if (window.scrollY > 100) {
                if (!header.classList.contains('header-colored')) {
                    header.classList.remove('header-transparent');
                    header.classList.add('header-colored');
                }
            } else {
                if (!header.classList.contains('header-transparent')) {
                    header.classList.remove('header-colored');
                    header.classList.add('header-transparent');
                }
            }
        }

        // Update on scroll with throttling
        let ticking = false;
        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    updateHeaderStyle();
                    ticking = false;
                });
                ticking = true;
            }
        });

        // Set initial state based on current scroll position
        updateHeaderStyle();
    } else {
        // For non-index pages, ensure header is colored
        header.classList.remove('header-transparent');
        header.classList.add('header-colored');
    }
}

// Initialize header when it's loaded
document.addEventListener('headerLoaded', initializeHeader);

document.addEventListener('DOMContentLoaded', function() {
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Add active state to navigation items based on scroll position
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav a');

    function updateActiveNavItem() {
        const scrollPosition = window.scrollY + 100;

        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            const sectionId = section.getAttribute('id');

            if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                navLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === `#${sectionId}`) {
                        link.classList.add('active');
                    }
                });
            }
        });
    }

    window.addEventListener('scroll', updateActiveNavItem);
    updateActiveNavItem();    // Header transparency is handled by initializeHeader function
});