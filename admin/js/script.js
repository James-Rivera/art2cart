// Clean Admin Dashboard JavaScript

// Tab switching functionality
function initDashboard() {
    // Get all navigation buttons and tab panels
    const navButtons = document.querySelectorAll('.nav-btn');
    const tabPanels = document.querySelectorAll('.tab-panel');
      // Function to switch tabs
    function switchTab(targetTab) {
        // Remove active class from all buttons
        navButtons.forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Hide all tab panels
        tabPanels.forEach(panel => {
            panel.classList.remove('active');
        });
        
        // Activate the clicked button
        const activeButton = document.querySelector(`[data-tab="${targetTab}"]`);
        if (activeButton) {
            activeButton.classList.add('active');
        }
        
        // Show the target tab panel
        const activePanel = document.getElementById(targetTab);
        if (activePanel) {
            activePanel.classList.add('active');
        }
          // Initialize sub-navigation if switching to user-management tab
        if (targetTab === 'user-management') {
            setTimeout(() => {
                initSubNavigation();
            }, 100);
        }
        
        // Initialize product review navigation if switching to products tab
        if (targetTab === 'products') {
            setTimeout(() => {
                initProductReviewNavigation();
            }, 100);
        }
    }
      // Add click event listeners to navigation buttons
    navButtons.forEach(button => {
        const tabName = button.getAttribute('data-tab');
        
        button.addEventListener('click', function(e) {
            e.preventDefault();
            switchTab(tabName);
        });
    });
      // Initialize the first tab (overview)
    switchTab('overview');
    
    // Also initialize product review navigation if products tab is active
    setTimeout(() => {
        const activeTab = document.querySelector('.tab-panel.active');
        if (activeTab && activeTab.id === 'products') {
            initProductReviewNavigation();
        }
    }, 200);
}


// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDashboard);
} else {
    initDashboard();
}

// Mobile sidebar toggle (for future responsive enhancement)
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('open');
}

// Make functions globally available
window.switchTab = function(tabName) {
    const activeButton = document.querySelector(`[data-tab="${tabName}"]`);
    if (activeButton) {
        activeButton.click();
    }
};

window.toggleSidebar = toggleSidebar;

window.reviewSellerApplication = function(applicationId, action) {
    let reason = null;
    if (action === 'reject') {
        // Check if reason was set from modal
        if (window.tempRejectReason) {
            reason = window.tempRejectReason;
            delete window.tempRejectReason;
        } else {
            // Fallback to getting reason from form field
            reason = document.getElementById(`seller-reject-reason-${applicationId}`)?.value;
            if (!reason || reason.trim() === '') {
                reason = prompt('Please provide a reason for rejection:');
                if (!reason) return;
            }
        }
    }
    
    const requestData = {
        applicationId: applicationId,
        action: action
    };
      if (reason) {
        requestData.reason = reason;
    }
    
    fetch('admin/api/review_seller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData)    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        
        if (data.success) {
            alert(data.message);
            // Remove the row from the table
            const row = document.querySelector(`tr[data-application-id="${applicationId}"]`);
            const card = document.querySelector(`div[data-id="${applicationId}"]`);
            if (row) row.remove();
            if (card) card.remove();
            
            // Refresh the page if no more rows
            const remainingRows = document.querySelectorAll('tr[data-application-id]');
            const remainingCards = document.querySelectorAll('div[data-id]');
            if (remainingRows.length === 0 && remainingCards.length === 0) {
                location.reload();
            }
        } else {
            alert('Error: ' + (data.error || 'Unknown error occurred'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error occurred. Please try again.');
    });
};

window.viewSellerDetails = function(applicationId) {
    // Show the modal
    const modal = document.getElementById('sellerDetailsModal');
    const content = document.getElementById('sellerDetailsContent');
    const modalActions = document.getElementById('modalActions');
    
    // Show loading state
    content.innerHTML = `
        <div class="loading">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading seller details...</p>
        </div>
    `;
    
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('show'), 10);
    
    // Fetch seller details
    fetch(`admin/api/get_seller_details.php?id=${applicationId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })        .then(data => {
            if (data.success) {
                displaySellerDetails(data.application);
                setupModalActions(applicationId);
            } else {
                content.innerHTML = `
                    <div class="error-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p><strong>Error loading seller details:</strong><br>${data.error}</p>
                        <small>Application ID: ${applicationId}</small>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = `
                <div class="error-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p><strong>Network error occurred:</strong><br>${error.message}</p>
                    <p>Please check the browser console for more details.</p>
                    <small>Application ID: ${applicationId}</small>
                </div>
            `;
        });
};

function displaySellerDetails(application) {
    const content = document.getElementById('sellerDetailsContent');
    
    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };
    
    const timeAgo = (dateString) => {
        const now = new Date();
        const date = new Date(dateString);
        const diffInSeconds = Math.floor((now - date) / 1000);
        
        if (diffInSeconds < 60) return 'just now';
        if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + ' minutes ago';
        if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + ' hours ago';
        if (diffInSeconds < 2592000) return Math.floor(diffInSeconds / 86400) + ' days ago';
        return formatDate(dateString);
    };
    
    content.innerHTML = `
        <div class="seller-detail-section">
            <h4><i class="fas fa-user"></i> User Information</h4>
            <div class="seller-detail-item">
                <span class="detail-label">Username:</span>
                <span class="detail-value">${application.username}</span>
            </div>
            <div class="seller-detail-item">
                <span class="detail-label">Email:</span>
                <span class="detail-value">${application.email}</span>
            </div>
            <div class="seller-detail-item">
                <span class="detail-label">Email Verified:</span>
                <span class="detail-value">
                    ${application.email_verified ? '✅ Verified' : '❌ Not Verified'}
                </span>
            </div>
            <div class="seller-detail-item">
                <span class="detail-label">Member Since:</span>
                <span class="detail-value">${formatDate(application.user_created_at)}</span>
            </div>
            <div class="seller-detail-item">
                <span class="detail-label">Last Login:</span>
                <span class="detail-value">
                    ${application.last_login ? timeAgo(application.last_login) : 'Never'}
                </span>
            </div>
        </div>
        
        <div class="seller-detail-section">
            <h4><i class="fas fa-file-alt"></i> Application Details</h4>
            <div class="seller-detail-item">
                <span class="detail-label">Application ID:</span>
                <span class="detail-value">#${application.id}</span>
            </div>            <div class="seller-detail-item">
                <span class="detail-label">Applied Date:</span>
                <span class="detail-value">${formatDate(application.application_date)}</span>
            </div>
            <div class="seller-detail-item">
                <span class="detail-label">Status:</span>
                <span class="detail-value">
                    <span class="application-status status-${application.status.toLowerCase()}">
                        ${application.status}
                    </span>
                </span>
            </div>
        </div>
        
        <div class="seller-detail-section">
            <h4><i class="fas fa-globe"></i> Portfolio</h4>
            <div class="seller-detail-item">
                <span class="detail-label">Portfolio URL:</span>
                <span class="detail-value">
                    ${application.portfolio_url ? 
                        `<a href="${application.portfolio_url}" target="_blank" class="portfolio-link">
                            <i class="fas fa-external-link-alt"></i> View Portfolio
                        </a>` : 
                        'No portfolio provided'
                    }
                </span>
            </div>
        </div>        <div class="seller-detail-section">
            <h4><i class="fas fa-id-card"></i> Government ID</h4>            ${application.government_id_path && application.file_exists ? 
                `<div class="id-document-container">
                    <img src="${application.government_id_url}" 
                         alt="Government ID" 
                         class="id-document-modal"
                         onclick="window.open(this.src, '_blank')"
                         style="max-width: 100%; cursor: pointer;"
                         title="Click to view full size">
                    <p style="margin-top: 8px; font-size: 12px; color: #666;">
                        <i class="fas fa-info-circle"></i> Click image to view full size
                    </p>
                </div>` :
                application.government_id_path && !application.file_exists ?
                `<div class="no-document-modal error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Government ID file not found</p>
                    <div style="font-size: 11px; margin-top: 10px; padding: 8px; background: #f5f5f5; border-radius: 4px;">
                        <strong>Debug Info:</strong><br>
                        Original: ${application.government_id_path}<br>
                        URL: ${application.government_id_url}<br>
                        ${application.debug_info ? `Full Path: ${application.debug_info.full_path}` : ''}
                    </div>
                </div>` :
                `<div class="no-document-modal">
                    <i class="fas fa-file-image"></i>
                    <p>No government ID uploaded</p>
                </div>`
            }
        </div>
        
        <div class="seller-detail-section">
            <h4><i class="fas fa-chart-bar"></i> Activity Statistics</h4>
            <div class="seller-detail-item">
                <span class="detail-label">Products Listed:</span>
                <span class="detail-value">${application.product_count}</span>
            </div>
            <div class="seller-detail-item">
                <span class="detail-label">Orders Made:</span>
                <span class="detail-value">${application.order_count}</span>
            </div>
        </div>
    `;
}

function setupModalActions(applicationId) {
    const modalActions = document.getElementById('modalActions');
    const approveBtn = document.getElementById('modalApproveBtn');
    const rejectBtn = document.getElementById('modalRejectBtn');
    
    // Show action buttons
    modalActions.style.display = 'flex';
    
    // Setup approve button
    approveBtn.onclick = function() {
        if (confirm('Are you sure you want to approve this seller application?')) {
            reviewSellerApplication(applicationId, 'approve');
            closeSellerModal();
        }
    };
    
    // Setup reject button  
    rejectBtn.onclick = function() {
        const reason = prompt('Please provide a reason for rejection:');
        if (reason !== null) {
            window.tempRejectReason = reason;
            reviewSellerApplication(applicationId, 'reject');
            closeSellerModal();
        }
    };
}

window.closeSellerModal = function() {
    const modal = document.getElementById('sellerDetailsModal');
    const modalActions = document.getElementById('modalActions');
    
    modal.classList.remove('show');
    modalActions.style.display = 'none';
    
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
};

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('sellerDetailsModal');
    if (e.target === modal) {
        closeSellerModal();
    }
});

// Product Review Functions
window.reviewProduct = function(productId, action) {
    const confirmMessage = action === 'approve' 
        ? 'Are you sure you want to approve this product?' 
        : 'Are you sure you want to reject this product?';
    
    if (!confirm(confirmMessage)) {
        return;
    }
    
    let notes = null;
    if (action === 'reject') {
        notes = document.getElementById(`reject-notes-${productId}`)?.value;
        if (!notes || notes.trim() === '') {
            alert('Please provide a reason for rejection.');
            return;
        }
    }
    
    const requestData = {
        productId: productId,
        action: action
    };
    
    if (notes) {
        requestData.notes = notes;
    }    fetch('admin/api/review_product.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            // Remove the product card or row from the view
            const productCard = document.querySelector(`[data-id="${productId}"]`);
            if (productCard) {
                productCard.remove();
            }
            // Refresh if no more products
            const remainingProducts = document.querySelectorAll('[data-id]');
            if (remainingProducts.length === 0) {
                location.reload();
            }
        } else {
            alert('Error: ' + (data.error || 'Unknown error occurred'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error occurred. Please try again.');
    });
};

window.showRejectForm = function(productId) {
    const form = document.getElementById(`reject-form-${productId}`);
    if (form) {
        form.style.display = 'block';
    }
};

window.hideRejectForm = function(productId) {
    const form = document.getElementById(`reject-form-${productId}`);
    if (form) {
        form.style.display = 'none';
    }
};

window.showSellerRejectForm = function(applicationId) {
    const form = document.getElementById(`seller-reject-form-${applicationId}`);
    if (form) {
        form.style.display = 'block';
    }
};

window.hideSellerRejectForm = function(applicationId) {
    const form = document.getElementById(`seller-reject-form-${applicationId}`);
    if (form) {
        form.style.display = 'none';
    }
};

// Function to show removal form for approved products
window.showRemoveForm = function(productId) {
    const form = document.getElementById(`remove-form-${productId}`);
    if (form) {
        form.style.display = 'block';
    }
};

// Function to hide removal form
window.hideRemoveForm = function(productId) {
    const form = document.getElementById(`remove-form-${productId}`);
    if (form) {
        form.style.display = 'none';
        // Clear the textarea
        const textarea = document.getElementById(`remove-notes-${productId}`);
        if (textarea) {
            textarea.value = '';
        }
    }
};

// Function to remove an approved product (reject it)
window.removeApprovedProduct = function(productId) {
    const notesTextarea = document.getElementById(`remove-notes-${productId}`);
    const reason = notesTextarea ? notesTextarea.value.trim() : '';
    
    if (!reason) {
        showNotification('Please provide a reason for removing this product.', 'error');
        return;
    }
    
    if (!confirm('Are you sure you want to remove this approved product? This action will make it unavailable to customers.')) {
        return;
    }
    
    // Add loading state to product card
    const productCard = document.querySelector(`[data-id="${productId}"]`);
    if (productCard && productCard.classList.contains('approved-product')) {
        productCard.classList.add('loading');
    }
    
    const requestData = {
        productId: productId,
        action: 'reject',
        notes: reason
    };
    
    fetch('admin/api/review_product.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            showNotification('Product successfully removed from the platform.', 'success');
            
            // Remove the product card from the DOM
            if (productCard && productCard.classList.contains('approved-product')) {
                productCard.remove();
            }
            
            // Check if no more approved products remain
            const approvedProductsGrid = document.querySelector('.products-review-grid');
            const remainingApprovedProducts = document.querySelectorAll('.approved-product');
            if (remainingApprovedProducts.length === 0 && approvedProductsGrid) {
                approvedProductsGrid.innerHTML = `
                    <div class="no-data">
                        <i class="fas fa-check-circle"></i>
                        <p>No approved products found</p>
                    </div>
                `;
            }
        } else {
            // Remove loading state
            if (productCard) {
                productCard.classList.remove('loading');
            }
            showNotification('Error removing product: ' + (data.message || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Remove loading state
        if (productCard) {
            productCard.classList.remove('loading');
        }
        showNotification('Error removing product. Please try again.', 'error');
    });
};

// Order Management Functions
let currentOrderId = null;

window.viewOrderDetails = function(orderId) {
    currentOrderId = orderId;
    const modal = document.getElementById('orderDetailsModal');
    const content = document.getElementById('orderDetailsContent');
    
    // Show modal with loading state
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('show'), 10);
    
    // Show loading spinner
    content.innerHTML = `
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading order details...</p>
        </div>
    `;    // Fetch order details
    fetch(`admin/api/order_management.php?id=${orderId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayOrderDetails(data.order);
            } else {
                throw new Error(data.error || 'Failed to load order details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = `
                <div class="error-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Error Loading Order</h3>
                    <p>${error.message}</p>
                    <button class="btn btn-primary" onclick="viewOrderDetails(${orderId})">
                        <i class="fas fa-redo"></i> Try Again
                    </button>
                </div>
            `;
        });
};

function displayOrderDetails(order) {
    const content = document.getElementById('orderDetailsContent');
    
    let itemsHtml = '';
    if (order.items && order.items.length > 0) {
        itemsHtml = order.items.map(item => `
            <div class="order-item">
                <div class="item-info">
                    <h4>${item.title}</h4>
                    <p><strong>Category:</strong> ${item.category_name || 'N/A'}</p>
                    <p><strong>Seller:</strong> ${item.seller_name}</p>
                    <p><strong>Price:</strong> ₱${parseFloat(item.price || 0).toFixed(2)}</p>
                    <p><strong>Quantity:</strong> ${item.quantity || 1}</p>
                </div>
            </div>
        `).join('');
    } else {
        itemsHtml = '<p>No items found for this order.</p>';
    }
    
    content.innerHTML = `
        <div class="order-details-container">
            <div class="order-info-grid">
                <div class="order-section">
                    <h3><i class="fas fa-shopping-cart"></i> Order Information</h3>
                    <div class="detail-row">
                        <span class="detail-label">Order ID:</span>
                        <span class="detail-value">#${order.id}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">
                            <span class="status-badge status-${order.status}">${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Total Amount:</span>
                        <span class="detail-value">₱${parseFloat(order.total_amount || 0).toFixed(2)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Order Date:</span>
                        <span class="detail-value">${order.formatted_date}</span>
                    </div>
                    ${order.formatted_updated ? `
                    <div class="detail-row">
                        <span class="detail-label">Last Updated:</span>
                        <span class="detail-value">${order.formatted_updated}</span>
                    </div>
                    ` : ''}
                </div>
                
                <div class="order-section">
                    <h3><i class="fas fa-user"></i> Customer Information</h3>
                    <div class="detail-row">
                        <span class="detail-label">Name:</span>
                        <span class="detail-value">${order.customer_name || 'N/A'}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Username:</span>
                        <span class="detail-value">${order.username}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value">${order.billing_email || order.user_email}</span>
                    </div>
                    ${order.phone ? `
                    <div class="detail-row">
                        <span class="detail-label">Phone:</span>
                        <span class="detail-value">${order.phone}</span>
                    </div>
                    ` : ''}
                </div>
                
                ${order.address ? `
                <div class="order-section">
                    <h3><i class="fas fa-map-marker-alt"></i> Billing Address</h3>
                    <div class="address-details">
                        <p>${order.address}</p>
                        <p>${order.city}, ${order.state_province} ${order.postal_code}</p>
                        <p>${order.country}</p>
                    </div>
                </div>
                ` : ''}
                
                ${order.payment_method ? `
                <div class="order-section">
                    <h3><i class="fas fa-credit-card"></i> Payment Information</h3>
                    <div class="detail-row">
                        <span class="detail-label">Payment Method:</span>
                        <span class="detail-value">${order.payment_method.charAt(0).toUpperCase() + order.payment_method.slice(1)}</span>
                    </div>
                </div>
                ` : ''}
            </div>
            
            <div class="order-section">
                <h3><i class="fas fa-list"></i> Order Items</h3>
                <div class="order-items-list">
                    ${itemsHtml}
                </div>
            </div>
        </div>
    `;
}

window.updateOrderStatus = function(orderId) {
    currentOrderId = orderId;
    const modal = document.getElementById('orderStatusModal');
    const errorDiv = document.getElementById('orderStatusError');
    
    // Clear previous error
    errorDiv.style.display = 'none';
    errorDiv.textContent = '';
    
    // Show modal
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('show'), 10);
};

window.saveOrderStatus = function() {
    const status = document.getElementById('orderStatus').value;
    const notes = document.getElementById('orderNotes').value;
    const errorDiv = document.getElementById('orderStatusError');
    const saveBtn = document.getElementById('saveOrderStatusBtn');
    
    if (!currentOrderId) {
        errorDiv.textContent = 'No order selected';
        errorDiv.style.display = 'block';
        return;
    }
    
    // Show loading state
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    
    const requestData = {
        orderId: currentOrderId,
        status: status,
        notes: notes
    };    fetch('admin/api/order_management.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeOrderStatusModal();
            // Show success message and reload page
            alert('Order status updated successfully!');
            window.location.reload();
        } else {
            throw new Error(data.error || 'Failed to update order status');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        errorDiv.textContent = error.message;
        errorDiv.style.display = 'block';
    })
    .finally(() => {
        // Reset button state
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-save"></i> Update Status';
    });
};

window.closeOrderModal = function() {
    const modal = document.getElementById('orderDetailsModal');
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
    currentOrderId = null;
};

window.closeOrderStatusModal = function() {
    const modal = document.getElementById('orderStatusModal');
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
        // Clear form
        document.getElementById('orderStatus').value = 'pending';
        document.getElementById('orderNotes').value = '';
        document.getElementById('orderStatusError').style.display = 'none';
    }, 300);
    currentOrderId = null;
};

window.viewProductDetails = function(productId) {
    // Show the modal
    const modal = document.getElementById('productDetailsModal');
    const content = document.getElementById('productDetailsContent');
    
    // Show loading state
    content.innerHTML = `
        <div class="loading">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading product details...</p>
        </div>
    `;
      modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('show'), 10);    // Fetch product details
    fetch(`admin/api/get_product_details.php?id=${productId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayProductDetails(data.product);
            } else {
                content.innerHTML = `<div class="error">Error: ${data.message}</div>`;
            }
        })
        .catch(error => {
            console.error('Error loading product:', error);
            content.innerHTML = '<div class="error">Failed to load product details. Please try again.</div>';
        });
};

function displayProductDetails(product) {
    const content = document.getElementById('productDetailsContent');
    
    const formatDate = (dateString) => {
        // Check if the date is already formatted from PHP
        if (dateString && typeof dateString === 'string' && dateString.includes('at')) {
            return dateString; // Already formatted by PHP
        }
        
        // Try to parse and format the date
        const date = new Date(dateString);
        if (isNaN(date.getTime())) {
            return 'Invalid Date';
        }
        
        return date.toLocaleString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };// Main product file for review (from uploads/files/)
    const productFileHtml = product.file_path && product.file_exists
        ? `<div class="product-image-section">
             <img src="${product.file_url}" alt="${product.title} - Product File" onclick="window.open(this.src, '_blank')" style="max-width: 100%; max-height: 400px; cursor: pointer; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);">
             <p style="margin-top: 8px; font-size: 12px; color: #666;">
                 <i class="fas fa-info-circle"></i> Click image to view full size - This is the actual product file for review
             </p>
           </div>`
        : product.file_path && !product.file_exists
        ? `<div class="no-image-placeholder error">
             <i class="fas fa-exclamation-triangle"></i>
             <p>Product file not found</p>
             <div style="font-size: 11px; margin-top: 10px; padding: 8px; background: #f5f5f5; border-radius: 4px;">
                 <strong>Debug Info:</strong><br>
                 Original: ${product.file_path}<br>
                 URL: ${product.file_url}<br>
             </div>
           </div>`
        : `<div class="no-image-placeholder">
             <i class="fas fa-file"></i>
             <p>No product file uploaded</p>
           </div>`;
    
    // Preview image (from static/images/products/) - for reference only
    const previewImageHtml = product.image_path && product.image_exists
        ? `<div class="product-image-section">
             <img src="${product.image_url}" alt="${product.title} - Preview" onclick="window.open(this.src, '_blank')" style="max-width: 100%; max-height: 200px; cursor: pointer; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);">
             <p style="margin-top: 8px; font-size: 12px; color: #666;">
                 <i class="fas fa-info-circle"></i> Preview image for display purposes
             </p>
           </div>`
        : `<div class="no-image-placeholder">
             <i class="fas fa-image"></i>
             <p>No preview image</p>
           </div>`;
    
    content.innerHTML = `
        <div class="product-details-container">
            <div class="product-detail-section">
                <h4><i class="fas fa-box"></i> Product Information</h4>
                <div class="product-detail-item">
                    <span class="detail-label">Title:</span>
                    <span class="detail-value">${product.title}</span>
                </div>
                <div class="product-detail-item">
                    <span class="detail-label">Price:</span>
                    <span class="detail-value">₱${parseFloat(product.price).toFixed(2)}</span>
                </div>
                <div class="product-detail-item">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span class="status-badge status-${product.review_status.toLowerCase()}">${product.review_status}</span>
                    </span>
                </div>
                <div class="product-detail-item">
                    <span class="detail-label">Category ID:</span>
                    <span class="detail-value">${product.category_id}</span>
                </div>                <div class="product-detail-item">
                    <span class="detail-label">Submitted:</span>
                    <span class="detail-value">${product.created_at_formatted || formatDate(product.created_at)}</span>
                </div>
                ${product.tags ? `
                <div class="product-detail-item">
                    <span class="detail-label">Tags:</span>
                    <span class="detail-value">${product.tags}</span>
                </div>
                ` : ''}
            </div>
            
            <div class="product-detail-section">
                <h4><i class="fas fa-align-left"></i> Description</h4>
                <div class="detail-value long-text">
                    ${product.description || 'No description provided'}
                </div>
            </div>
            
            <div class="product-detail-section">
                <h4><i class="fas fa-user"></i> Seller Information</h4>
                <div class="product-detail-item">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value">${product.seller_name}</span>
                </div>
                <div class="product-detail-item">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">${product.seller_email}</span>
                </div>
            </div>
              <div class="product-detail-section">
                <h4><i class="fas fa-file"></i> Product File for Review</h4>
                ${productFileHtml}
            </div>
            
            <div class="product-detail-section">
                <h4><i class="fas fa-image"></i> Preview Image</h4>
                ${previewImageHtml}
            </div>
            
            ${product.file_path ? `
            <div class="product-detail-section">
                <h4><i class="fas fa-download"></i> File Information</h4>
                <div class="product-detail-item">
                    <span class="detail-label">File Path:</span>
                    <span class="detail-value">${product.file_path}</span>
                </div>                <div class="product-detail-item">
                    <span class="detail-label">Download File:</span>
                    <span class="detail-value">
                        <a href="${product.file_url}" target="_blank" class="portfolio-link">
                            <i class="fas fa-download"></i> Download Product File
                        </a>
                    </span>
                </div>
            </div>
            ` : ''}
        </div>
    `;
}

function closeProductModal() {
    const modal = document.getElementById('productDetailsModal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }
}

// Pagination functions for product review
window.changeItemsPerPage = function(perPage) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('per_page', perPage);
    urlParams.set('page', '1'); // Reset to first page when changing items per page
    window.location.href = 'admin/admin_dashboard.php?' + urlParams.toString() + '#products';
};

window.changePage = function(page) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('page', page);
    window.location.href = window.location.pathname + '?' + urlParams.toString();
};

function updatePaginationInfo(currentPage, totalPages) {
    const paginationInfo = document.getElementById('paginationInfo');
    if (paginationInfo) {
        paginationInfo.textContent = `Page ${currentPage} of ${totalPages}`;
    }
}

// Auto-switch to products tab on page load if there are pagination parameters
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get('page');
    const perPage = urlParams.get('per_page');
    
    // If pagination parameters exist, switch to products tab
    if (page || perPage) {
        switchTab('products');
    }
    
    // Handle hash navigation for pagination
    if (window.location.hash === '#products') {
        switchTab('products');
    }
});

// Notification system
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-triangle' : 'fa-info-circle'}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">×</button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Make functions globally available
window.closeProductModal = closeProductModal;
window.viewProductDetails = viewProductDetails;

// Function to review product from overview tab
window.reviewProductFromOverview = function(productId) {
    // Switch to products tab and scroll to pending reviews section
    switchTabAndScroll('products', 'pending-reviews');
    
    // Small delay to ensure tab switch and scroll completes, then open product modal
    setTimeout(() => {
        if (typeof viewProductDetails === 'function') {
            viewProductDetails(productId);
        } else {
            console.error('viewProductDetails function not available');
        }
    }, 500); // Increased delay to account for scroll animation
};

// Sub-navigation functionality for User Management tab
function initSubNavigation() {
    const subNavButtons = document.querySelectorAll('.sub-tab-btn');
    const subTabContents = document.querySelectorAll('.sub-tab-content');
    
    // Function to switch sub-tabs
    function switchSubTab(targetSubTab) {
        // Remove active class from all sub-nav buttons
        subNavButtons.forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Hide all sub-tab contents
        subTabContents.forEach(content => {
            content.classList.remove('active');
        });
        
        // Activate the clicked button
        const activeButton = document.querySelector(`[data-sub-tab="${targetSubTab}"]`);
        if (activeButton) {
            activeButton.classList.add('active');
        }
        
        // Show the target sub-tab content
        const activeContent = document.getElementById(targetSubTab);
        if (activeContent) {
            activeContent.classList.add('active');
        }
        
        // Load users data when switching to manage-users tab
        if (targetSubTab === 'manage-users') {
            loadUsersData();
        }
    }
    
    // Add click event listeners to sub-navigation buttons
    subNavButtons.forEach(button => {
        const subTabName = button.getAttribute('data-sub-tab');
        
        button.addEventListener('click', function(e) {
            e.preventDefault();
            switchSubTab(subTabName);
        });
    });
    
    // Initialize default sub-tab (pending-applications)
    switchSubTab('pending-applications');
}

// Function to load users data for the manage-users tab
function loadUsersData() {
    const usersTableBody = document.getElementById('usersTableBody');
    
    if (!usersTableBody) return;
    
    // Show loading state
    usersTableBody.innerHTML = `
        <tr><td colspan="6">
            <div class="loading">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Loading users...</p>
            </div>
        </td></tr>
    `;
    
    // Fetch users data
    fetch('admin/api/get_users.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayUsersData(data.users);
            } else {
                usersTableBody.innerHTML = `
                    <tr><td colspan="6">
                        <div class="error-state">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>Error loading users: ${data.error}</p>
                        </div>
                    </td></tr>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading users:', error);
            usersTableBody.innerHTML = `
                <tr><td colspan="6">
                    <div class="error-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Failed to load users data</p>
                        <small>${error.message}</small>
                    </div>
                </td></tr>
            `;
        });
}

// Function to display users data in the table
function displayUsersData(users) {
    const usersTableBody = document.getElementById('usersTableBody');
    
    if (!users || users.length === 0) {
        usersTableBody.innerHTML = `
            <tr><td colspan="6">
                <div class="no-data">
                    <i class="fas fa-users"></i>
                    <p>No users found</p>
                </div>
            </td></tr>
        `;
        return;
    }
    
    const usersHtml = users.map(user => {
        const statusClass = user.status === 'active' ? 'status-success' : 'status-warning';
        const statusText = user.status === 'active' ? 'Active' : 'Inactive';
        
        return `
            <tr data-user-id="${user.id}">
                <td>${user.id}</td>
                <td>${escapeHtml(user.username)}</td>
                <td>${escapeHtml(user.email)}</td>
                <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                <td>${formatDate(user.created_at)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-primary" onclick="viewUserDetails(${user.id})">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <button class="btn btn-sm ${user.status === 'active' ? 'btn-warning' : 'btn-success'}" 
                                onclick="toggleUserStatus(${user.id}, '${user.status}')">
                            <i class="fas fa-${user.status === 'active' ? 'pause' : 'play'}"></i> 
                            ${user.status === 'active' ? 'Deactivate' : 'Activate'}
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
    
    usersTableBody.innerHTML = usersHtml;
}

// Helper function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Helper function to format date
function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Function to view user details
window.viewUserDetails = function(userId) {
    alert(`View details for user ID: ${userId}\n(This feature can be implemented as needed)`);
};

// Function to toggle user status
window.toggleUserStatus = function(userId, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    const action = newStatus === 'active' ? 'activate' : 'deactivate';
    
    if (!confirm(`Are you sure you want to ${action} this user?`)) {
        return;
    }
    
    // Here you would make an API call to update the user status
    fetch('admin/api/update_user_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            userId: userId,
            status: newStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload the users data to reflect the change
            loadUsersData();
            alert(`User ${action}d successfully`);
        } else {
            alert(`Error: ${data.error}`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error occurred. Please try again.');
    });
};

// Initialize sub-navigation when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initSubNavigation();
});

// Product Review Navigation Functions
window.scrollToSection = function(sectionId) {
    // Update active state of navigation buttons
    const navButtons = document.querySelectorAll('.nav-btn-section');
    navButtons.forEach(btn => {
        btn.classList.remove('active');
        if (btn.getAttribute('data-section') === sectionId) {
            btn.classList.add('active');
        }
    });
    
    // Smooth scroll to the target section
    const targetElement = document.getElementById(sectionId);
    if (targetElement) {
        // Calculate offset to account for fixed header or navigation
        const offset = 100; // Adjust this value as needed
        const elementPosition = targetElement.getBoundingClientRect().top;
        const offsetPosition = elementPosition + window.pageYOffset - offset;
        
        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
        });
          // Add a subtle highlight effect to the target section
        targetElement.classList.add('section-highlight');
        
        setTimeout(() => {
            targetElement.classList.remove('section-highlight');
        }, 2000);
    }
};

// Initialize product review navigation on page load
function initProductReviewNavigation() {
    // Set up intersection observer to update active navigation based on scroll position
    if (typeof IntersectionObserver !== 'undefined') {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const sectionId = entry.target.id;
                    if (sectionId === 'pending-reviews' || sectionId === 'approved-management') {
                        // Update active navigation button
                        const navButtons = document.querySelectorAll('.nav-btn-section');
                        navButtons.forEach(btn => {
                            btn.classList.remove('active');
                            if (btn.getAttribute('data-section') === sectionId) {
                                btn.classList.add('active');
                            }
                        });
                    }
                }
            });
        }, {
            threshold: 0.3, // Trigger when 30% of the section is visible
            rootMargin: '-100px 0px -50% 0px' // Account for header and better detection
        });
        
        // Observe both sections
        const pendingSection = document.getElementById('pending-reviews');
        const approvedSection = document.getElementById('approved-management');
        
        if (pendingSection) observer.observe(pendingSection);
        if (approvedSection) observer.observe(approvedSection);
    }
    
    // Handle direct navigation via URL hash
    const hash = window.location.hash;
    if (hash === '#pending-reviews' || hash === '#approved-management') {
        setTimeout(() => {
            scrollToSection(hash.substring(1));
        }, 500); // Delay to ensure page is fully loaded
    }
}

// Initialize product review navigation
document.addEventListener('DOMContentLoaded', function() {
    initProductReviewNavigation();
});

// Enhanced tab switching with section scrolling
window.switchTabAndScroll = function(tabName, sectionId) {
    // First switch to the tab
    switchTab(tabName);
    
    // Then scroll to the specific section after a short delay
    setTimeout(() => {
        if (sectionId) {
            scrollToSection(sectionId);
        }
    }, 300);
};
