document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.artist-card');
    const dots = document.querySelectorAll('.nav-dot');
    let currentIndex = 0;
    let autoRotateInterval;
    let isHovering = false;

    function showSlide(index) {
        // Hide all cards and remove active class from dots
        cards.forEach(card => card.classList.remove('active'));
        dots.forEach(dot => dot.classList.remove('active'));
        
        // Show current card and dot
        if (cards[index] && dots[index]) {
            cards[index].classList.add('active');
            dots[index].classList.add('active');
            currentIndex = index;
        }
    }

    // Add click handlers to dots
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            clearInterval(autoRotateInterval);
            showSlide(index);
            if (!isHovering) {
                startAutoRotate();
            }
        });
    });

    function startAutoRotate() {
        // Clear any existing interval
        if (autoRotateInterval) {
            clearInterval(autoRotateInterval);
        }

        // Set new interval
        autoRotateInterval = setInterval(() => {
            if (!isHovering) {
                const nextIndex = (currentIndex + 1) % cards.length;
                showSlide(nextIndex);
            }
        }, 4000); // Changed to 4 seconds for better UX
    }

    // FIXED: Image replacement logic instead of popup
    cards.forEach((card, index) => {
        const artistInfo = card.querySelector('.artist-info');
        const img = card.querySelector('img');
        const originalSrc = card.dataset.original;
        const hoverSrc = card.dataset.hover;
        
        if (artistInfo && img && originalSrc && hoverSrc) {
            // Hover over artist info to change main image
            artistInfo.addEventListener('mouseenter', () => {
                isHovering = true;
                clearInterval(autoRotateInterval);
                
                // Change to hover image with smooth transition
                if (card.classList.contains('active')) {
                    img.style.opacity = '0.7';
                    setTimeout(() => {
                        img.src = hoverSrc;
                        img.style.opacity = '1';
                        img.style.transform = 'scale(1.03)';
                    }, 200);
                }
            });

            artistInfo.addEventListener('mouseleave', () => {
                isHovering = false;
                
                // Revert to original image
                img.style.opacity = '0.7';
                setTimeout(() => {
                    img.src = originalSrc;
                    img.style.opacity = '1';
                    img.style.transform = 'scale(1)';
                }, 200);
                
                // Resume auto-rotation after a short delay
                setTimeout(() => {
                    if (!isHovering) {
                        startAutoRotate();
                    }
                }, 500);
            });

            // Enhanced hover effect for artist details
            artistInfo.addEventListener('mouseenter', () => {
                const details = artistInfo.querySelector('.artist-details');
                if (details) {
                    details.style.background = 'rgba(255, 255, 255, 0.9)';
                    details.style.transform = 'translateY(-6px) scale(1.05)';
                    details.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.15)';
                }
            });

            artistInfo.addEventListener('mouseleave', () => {
                const details = artistInfo.querySelector('.artist-details');
                if (details) {
                    details.style.background = 'rgba(255, 255, 255, 0.42)';
                    details.style.transform = 'translateY(0) scale(1)';
                    details.style.boxShadow = 'none';
                }
            });
        }

        // Add click handler for cards
        card.addEventListener('click', (e) => {
            console.log(`Clicked on artist card ${index + 1}`);
        });
    });

    // Add keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            clearInterval(autoRotateInterval);
            const prevIndex = (currentIndex - 1 + cards.length) % cards.length;
            showSlide(prevIndex);
            if (!isHovering) {
                startAutoRotate();
            }
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            clearInterval(autoRotateInterval);
            const nextIndex = (currentIndex + 1) % cards.length;
            showSlide(nextIndex);
            if (!isHovering) {
                startAutoRotate();
            }
        }
    });

    // Pause auto-rotation when page is not visible
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            clearInterval(autoRotateInterval);
        } else if (!isHovering) {
            startAutoRotate();
        }
    });

    // Initialize first slide and start auto-rotation
    showSlide(0);
    startAutoRotate();

    // Add smooth scroll behavior for explore button (optional)
    const exploreBtn = document.querySelector('.explore-more-btn');
    if (exploreBtn) {
        exploreBtn.addEventListener('click', () => {
            console.log('Explore More clicked');
            // Add your navigation logic here
        });
    }
});