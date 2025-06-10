document.addEventListener('DOMContentLoaded', function() {
    function initializeSlider() {
        const container = document.querySelector('.container3');
        const slider = document.querySelector('#carouselSlider');
        const progress = document.querySelector('.slider-progress');
        
        if (!container || !slider || !progress) return;
        
        // Update slider progress bar
        function updateProgress() {
            const percent = (slider.value / slider.max) * 100;
            progress.style.transform = `scaleX(${percent / 100})`;
        }
        
        // Handle slider input
        slider.addEventListener('input', function() {
            const scrollWidth = container.scrollWidth - container.clientWidth;
            const scrollPosition = (scrollWidth * slider.value) / 100;
            container.scrollLeft = scrollPosition;
            updateProgress();
        });
        
        // Update slider position when scrolling
        container.addEventListener('scroll', function() {
            const scrollWidth = container.scrollWidth - container.clientWidth;
            const scrolled = (container.scrollLeft / scrollWidth) * 100;
            slider.value = scrolled;
            updateProgress();
        });
        
        // Initialize progress bar
        updateProgress();
        
        // Make slider visible
        const sliderContainer = document.querySelector('.carousel-slider');
        if (sliderContainer) {
            sliderContainer.style.opacity = '1';
        }
    }

    // Initialize on page load and when products are loaded
    initializeSlider();
    document.addEventListener('productsLoaded', initializeSlider);
}); 