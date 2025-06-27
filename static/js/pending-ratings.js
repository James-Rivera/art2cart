/**
 * Pending Ratings JavaScript Functions
 * Global functions for handling pending ratings notifications and modals
 * This file should be included on all pages that might show pending ratings notifications
 */

// Check if notification was dismissed for specific orders (localStorage persists across browser sessions)
function wasNotificationDismissed() {
    if (typeof window.pendingRatingsData === "undefined" || !window.pendingRatingsData.length) {
        return false;
    }
    
    // Get all order IDs from pending ratings data
    const orderIds = window.pendingRatingsData.map(order => order.order_id).sort().join('_');
    const dismissalKey = `pendingRatingsDismissed_${orderIds}`;
    
    const dismissed = localStorage.getItem(dismissalKey);
    if (!dismissed) return false;
    
    const dismissedTime = parseInt(dismissed);
    const now = Date.now();
    
    // For demo: 2 minutes, for production: 7 days
    const DISMISS_DURATION = 2 * 60 * 1000; // 2 minutes for presentation
    // const DISMISS_DURATION = 7 * 24 * 60 * 60 * 1000; // 7 days for production
    
    return (now - dismissedTime) < DISMISS_DURATION;
}

// Check if there are new orders since last dismissal
function hasNewOrdersSinceLastDismissal() {
    if (typeof window.pendingRatingsData === "undefined" || !window.pendingRatingsData.length) {
        return false;
    }
    
    const currentOrderIds = window.pendingRatingsData.map(order => order.order_id).sort();
    
    // Check if any current orders don't have dismissal records
    for (const orderId of currentOrderIds) {
        const orderDismissalKey = `pendingRatingsDismissed_${orderId}`;
        const dismissed = localStorage.getItem(orderDismissalKey);
        
        if (!dismissed) {
            return true; // Found an order that hasn't been dismissed
        }
        
        const dismissedTime = parseInt(dismissed);
        const now = Date.now();
        const DISMISS_DURATION = 2 * 60 * 1000; // 2 minutes for demo
        
        if ((now - dismissedTime) >= DISMISS_DURATION) {
            return true; // Found an order whose dismissal has expired
        }
    }
    
    return false;
}

// Global functions that are always available
function hidePendingRatingsNotification() {
    const notification = document.getElementById("pendingRatingsNotification");
    if (notification) {
        notification.style.display = "none";
    }
    
    // Store order-specific dismissal timestamp in localStorage
    if (typeof window.pendingRatingsData !== "undefined" && window.pendingRatingsData.length) {
        const orderIds = window.pendingRatingsData.map(order => order.order_id).sort().join('_');
        const dismissalKey = `pendingRatingsDismissed_${orderIds}`;
        localStorage.setItem(dismissalKey, Date.now().toString());
        
        // Also store individual order dismissals for future reference
        window.pendingRatingsData.forEach(order => {
            const individualKey = `pendingRatingsDismissed_${order.order_id}`;
            localStorage.setItem(individualKey, Date.now().toString());
        });
    }
}

function showPendingRatingsModal() {
    // First, check if there's an existing modal from order-confirmation.php
    const existingModal = document.getElementById("ratingModal");
    
    if (existingModal) {
        // Use the existing order-confirmation modal
        // We need to populate it with pending ratings data
        populateExistingRatingModal();
        existingModal.style.display = "flex";
    } else {
        // Create our own modal if the order-confirmation modal doesn't exist
        const modal = document.getElementById("pendingRatingsModal");
        if (modal) {
            modal.style.display = "flex";
        } else {
            createPendingRatingsModal();
            const newModal = document.getElementById("pendingRatingsModal");
            if (newModal) {
                newModal.style.display = "flex";
            }
        }
    }
    hidePendingRatingsNotification();
}

function createPendingRatingsModal() {
    if (typeof window.pendingRatingsData === "undefined") {
        console.error("No pending ratings data available");
        return;
    }
    
    let modalHTML = `
    <div id="pendingRatingsModal" class="rating-modal" style="display: none;">
        <div class="modal-overlay" onclick="closePendingRatingsModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Rate Your Purchases</h3>
                <button class="close-btn" onclick="closePendingRatingsModal()">&times;</button>
            </div>            <div class="modal-body">
                <p>Help other customers by rating the products you purchased:</p>
                <div id="pendingRatingProducts" class="rating-products">`;
      window.pendingRatingsData.forEach(order => {
        order.products.forEach(product => {
            // Safe fallbacks for product data
            const productTitle = product.product_name || product.title || 'Unknown Product';
            const productPrice = product.price ? parseFloat(product.price).toFixed(2) : '0.00';
            const productImage = product.image_path || 'static/images/products/sample.jpg';
            const productId = product.product_id || product.id;
            
            // Clean up image path
            let imageSrc = productImage;
            if (imageSrc && !imageSrc.startsWith('http')) {
                imageSrc = imageSrc.startsWith('/') ? imageSrc.substring(1) : imageSrc;
                if (imageSrc.startsWith('Art2Cart/')) {
                    imageSrc = imageSrc.substring(9);
                }
                // Add base URL if not absolute
                if (typeof baseUrl !== 'undefined') {
                    imageSrc = baseUrl + imageSrc;
                }
            }            modalHTML += `
                <div class="rating-product-item">
                    <div class="product-info">
                        <img src="${imageSrc}" alt="${productTitle}" class="product-thumbnail" 
                             onerror="this.src='${typeof baseUrl !== 'undefined' ? baseUrl : ''}static/images/products/sample.jpg';">
                        <div class="product-details">
                            <h4>${productTitle}</h4>
                            <p class="product-price">₱${productPrice}</p>
                        </div>
                    </div>
                    <div class="rating-input-section">
                        <div class="star-rating" data-product-id="${productId}">`;
                        
            for (let i = 1; i <= 5; i++) {
                modalHTML += `<span class="rating-star" data-rating="${i}" onclick="setPendingRating(${productId}, ${i})">★</span>`;
            }
            
            modalHTML += `
                        </div>
                        <textarea class="rating-comment" placeholder="Add a comment (optional)" data-product-id="${productId}"></textarea>
                        <input type="hidden" class="product-rating" data-product-id="${productId}" value="">
                    </div>
                </div>`;
        });
    });
    
    modalHTML += `
                </div>
                <div class="modal-actions">
                    <button id="submitPendingRatingsBtn" class="submit-ratings-btn" onclick="submitPendingRatings()">Submit Ratings</button>
                    <button class="rate-later-btn" onclick="closePendingRatingsModal()">Close</button>
                </div>
            </div>
        </div>
    </div>`;
    
    document.body.insertAdjacentHTML("beforeend", modalHTML);
}

function closePendingRatingsModal() {
    // Close the pending ratings modal (from PHP)
    const pendingModal = document.getElementById("pendingRatingsModal");
    if (pendingModal) {
        pendingModal.style.display = "none";
    }
    
    // Close the order-confirmation modal (if we're using it)
    const ratingModal = document.getElementById("ratingModal");
    if (ratingModal) {
        ratingModal.style.display = "none";
    }
}

function setPendingRating(productId, rating) {
    const starContainer = document.querySelector(`[data-product-id="${productId}"].star-rating`);
    if (!starContainer) return;
    
    const stars = starContainer.querySelectorAll(".rating-star");
    const hiddenInput = document.querySelector(`input[data-product-id="${productId}"]`);
    
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.add("active");
        } else {
            star.classList.remove("active");
        }
    });
    
    if (hiddenInput) {
        hiddenInput.value = rating;
    }
}

// Global setProductRating function (compatible with order-confirmation modal)
function setProductRating(productId, rating) {
    const starContainer = document.querySelector(`[data-product-id="${productId}"].star-rating`);
    if (!starContainer) return;
    
    const stars = starContainer.querySelectorAll('.rating-star');
    const hiddenInput = document.querySelector(`input[data-product-id="${productId}"]`);
    
    // Update visual stars
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
    });
    
    // Update hidden input
    if (hiddenInput) {
        hiddenInput.value = rating;
    }
}

async function submitPendingRatings() {
    const submitBtn = document.getElementById("submitPendingRatingsBtn") || document.getElementById("submitRatingsBtn");
    if (!submitBtn) return;
    
    submitBtn.disabled = true;
    submitBtn.textContent = "Submitting...";
    
    // Check if pendingRatingsData exists
    if (typeof window.pendingRatingsData === "undefined") {
        alert("No ratings data found");
        submitBtn.disabled = false;
        submitBtn.textContent = "Submit Ratings";
        return;
    }
    
    const ratings = [];
    const allProducts = [];
    
    window.pendingRatingsData.forEach(order => {
        order.products.forEach(product => {
            allProducts.push(product);
        });
    });
    
    allProducts.forEach(product => {
        const ratingInput = document.querySelector(`input[data-product-id="${product.id}"]`);
        const commentInput = document.querySelector(`textarea[data-product-id="${product.id}"]`);
        
        if (ratingInput && ratingInput.value) {
            ratings.push({
                product_id: product.id,
                rating: parseFloat(ratingInput.value),
                comment: commentInput ? commentInput.value.trim() : ""
            });
        }
    });
    
    if (ratings.length === 0) {
        alert("Please rate at least one product");
        submitBtn.disabled = false;
        submitBtn.textContent = "Submit Ratings";
        return;
    }
    
    try {
        // Get base URL from meta tag or use current origin
        const baseUrl = document.querySelector('meta[name="base-url"]')?.content || 
                       document.querySelector('base')?.href || 
                       window.location.origin + '/';
        
        for (const ratingData of ratings) {
            const response = await fetch(baseUrl + "api/ratings.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(ratingData)
            });
              const result = await response.json();
            if (!result.success) {
                console.error("Failed to submit rating for product", ratingData.product_id);
            }
        }        // Clear order-specific dismissal after successful rating submission
        if (typeof window.pendingRatingsData !== "undefined" && window.pendingRatingsData.length) {
            const orderIds = window.pendingRatingsData.map(order => order.order_id).sort().join('_');
            const dismissalKey = `pendingRatingsDismissed_${orderIds}`;
            localStorage.removeItem(dismissalKey);
            
            // Clear individual order dismissals, reminders, and skip preferences
            window.pendingRatingsData.forEach(order => {
                const individualKey = `pendingRatingsDismissed_${order.order_id}`;
                const reminderKey = `rateLaterReminder_${order.order_id}`;
                const skipKey = `skipRatings_${order.order_id}`;
                
                localStorage.removeItem(individualKey);
                localStorage.removeItem(reminderKey);
                localStorage.removeItem(skipKey);
            });
        }
        
        alert("Thank you for your ratings!");
        closePendingRatingsModal();
        
        // Clear all pending ratings cookies
        if (window.pendingRatingsData) {
            window.pendingRatingsData.forEach(order => {
                document.cookie = "pending_ratings_" + order.order_id + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            });
        }
        
        location.reload();
        
    } catch (error) {
        console.error("Error submitting ratings:", error);
        alert("An error occurred while submitting your ratings. Please try again.");
    }
    
    submitBtn.disabled = false;
    submitBtn.textContent = "Submit Ratings";
}

// Populate rating modal with products (similar to order-confirmation modal)
function populateRatingModal() {
    const ratingProducts = document.getElementById("pendingRatingProducts");
    if (!ratingProducts || !window.pendingRatingsData) return;
    
    // Clear previous content
    ratingProducts.innerHTML = '';
    
    // Create rating forms for each product
    window.pendingRatingsData.forEach(order => {
        order.products.forEach(product => {
            const productDiv = document.createElement('div');
            productDiv.className = 'rating-product-item';
            productDiv.innerHTML = `
                <div class="product-info">
                    <img src="${getBaseUrl()}${product.image_path}" alt="${product.title}" class="product-thumbnail">
                    <div class="product-details">
                        <h4>${product.title}</h4>
                    </div>
                </div>
                <div class="rating-input-section">
                    <div class="star-rating" data-product-id="${product.id}">
                        ${[1,2,3,4,5].map(star => `
                            <span class="rating-star" data-rating="${star}" onclick="setPendingRating(${product.id}, ${star})">★</span>
                        `).join('')}
                    </div>
                    <textarea class="rating-comment" placeholder="Add a comment (optional)" data-product-id="${product.id}"></textarea>
                    <input type="hidden" class="product-rating" data-product-id="${product.id}" value="">
                </div>
            `;
            ratingProducts.appendChild(productDiv);
        });
    });
}

// Populate the existing order-confirmation modal with pending ratings data
function populateExistingRatingModal() {
    if (typeof window.pendingRatingsData === "undefined" || !window.pendingRatingsData.length) {
        return;
    }
      const ratingProducts = document.getElementById('ratingProducts');
    if (!ratingProducts) return;
    
    // Clear previous content
    ratingProducts.innerHTML = '';
      // Create rating forms for each pending product (using order-confirmation format)
    window.pendingRatingsData.forEach(order => {
        order.products.forEach(product => {
            // Safe fallbacks for product data
            const productTitle = product.product_name || product.title || 'Unknown Product';
            const productPrice = product.price ? parseFloat(product.price).toFixed(2) : '0.00';
            const productImage = product.image_path || 'static/images/products/sample.jpg';
            const productId = product.product_id || product.id;
            
            // Clean up image path
            let imageSrc = productImage;
            if (imageSrc && !imageSrc.startsWith('http')) {
                imageSrc = imageSrc.startsWith('/') ? imageSrc.substring(1) : imageSrc;
                if (imageSrc.startsWith('Art2Cart/')) {
                    imageSrc = imageSrc.substring(9);
                }
                imageSrc = getBaseUrl() + imageSrc;
            }            const productDiv = document.createElement('div');
            productDiv.className = 'rating-product-item';
            productDiv.innerHTML = `
                <div class="product-info">
                    <img src="${imageSrc}" alt="${productTitle}" class="product-thumbnail" 
                         onerror="this.src='${getBaseUrl()}static/images/products/sample.jpg';">
                    <div class="product-details">
                        <h4>${productTitle}</h4>
                        <p class="product-price">₱${productPrice}</p>
                    </div>
                </div>
                <div class="rating-input-section">
                    <div class="star-rating" data-product-id="${productId}">
                        ${[1,2,3,4,5].map(star => `
                            <span class="rating-star" data-rating="${star}" onclick="setProductRating(${productId}, ${star})">★</span>
                        `).join('')}
                    </div>
                    <textarea class="rating-comment" placeholder="Add a comment (optional)" data-product-id="${productId}"></textarea>
                    <input type="hidden" class="product-rating" data-product-id="${productId}" value="">
                </div>
            `;
            ratingProducts.appendChild(productDiv);
        });
    });
    
    // Update the modal actions to use our pending ratings functions
    const modalActions = document.querySelector('#ratingModal .modal-actions');
    if (modalActions) {
        modalActions.innerHTML = `
            <button id="submitRatingsBtn" class="submit-ratings-btn" onclick="submitPendingRatings()">Submit Ratings</button>
            <button class="rate-later-btn" onclick="closePendingRatingsModal()">Close</button>
        `;
    }
}

// Helper function to get base URL
function getBaseUrl() {
    // Try multiple methods to get base URL
    const metaBase = document.querySelector('meta[name="base-url"]')?.content;
    const baseElement = document.querySelector('base')?.href;
    const windowBase = window.baseHref;
    
    return metaBase || baseElement || windowBase || window.location.origin + '/Art2Cart/';
}

// Add event listeners when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {    // Close modal when clicking outside
    const modal = document.getElementById('pendingRatingsModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closePendingRatingsModal();
            }
        });
    }
      // Auto-hide notification if it was previously dismissed and no new orders
    if (wasNotificationDismissed() && !hasNewOrdersSinceLastDismissal()) {
        const notification = document.getElementById("pendingRatingsNotification");
        if (notification) {
            notification.style.display = "none";
        }
    }
});
