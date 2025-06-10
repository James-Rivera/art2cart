// Navigation functionality
document.addEventListener("DOMContentLoaded", () => {
    const navLinks = document.querySelectorAll(".nav-link")
    const sections = document.querySelectorAll(".content-section")
  
    // Handle navigation clicks
    navLinks.forEach((link) => {
      link.addEventListener("click", function (e) {
        e.preventDefault()
  
        // Remove active class from all links and sections
        navLinks.forEach((l) => l.classList.remove("active"))
        sections.forEach((s) => s.classList.remove("active"))
  
        // Add active class to clicked link
        this.classList.add("active")
  
        // Show corresponding section
        const targetSection = this.getAttribute("data-section")
        document.getElementById(targetSection).classList.add("active")
      })
    })
  
    // Search functionality
    const sellerSearch = document.getElementById("seller-search")
    const productSearch = document.getElementById("product-search")
  
    if (sellerSearch) {
      sellerSearch.addEventListener("input", function () {
        filterTable("seller-auth", this.value)
      })
    }
  
    if (productSearch) {
      productSearch.addEventListener("input", function () {
        filterTable("product-auth", this.value)
      })
    }
  
    // Search functionality for approved products
    const approvedProductSearch = document.getElementById("approved-product-search")
    if (approvedProductSearch) {
        approvedProductSearch.addEventListener("input", function () {
            filterTable("approved-products", this.value)
        })
    }
  
    // Filter functionality
    const sellerFilter = document.getElementById("seller-filter")
    const productFilter = document.getElementById("product-filter")
  
    if (sellerFilter) {
      sellerFilter.addEventListener("change", function () {
        filterByStatus("seller-auth", this.value)
      })
    }
  
    if (productFilter) {
      productFilter.addEventListener("change", function () {
        filterByCategory("product-auth", this.value)
      })
    }
  
    // Filter functionality for approved products
    const approvedProductFilter = document.getElementById("approved-product-filter")
    if (approvedProductFilter) {
        approvedProductFilter.addEventListener("change", function () {
            filterByCategory("approved-products", this.value)
        })
    }
  
    // Modal functionality
    const modal = document.getElementById("detailModal")
    const closeBtn = document.querySelector(".close")
  
    if (closeBtn) {
      closeBtn.addEventListener("click", () => {
        modal.style.display = "none"
      })
    }
  
    window.addEventListener("click", (e) => {
      if (e.target === modal) {
        modal.style.display = "none"
      }
    })
  })
  
  // Filter table by search term
  function filterTable(sectionId, searchTerm) {
    const section = document.getElementById(sectionId)
    const rows = section.querySelectorAll("tbody tr")
  
    rows.forEach((row) => {
      const text = row.textContent.toLowerCase()
      if (text.includes(searchTerm.toLowerCase())) {
        row.style.display = ""
      } else {
        row.style.display = "none"
      }
    })
  }
  
  // Filter by status
  function filterByStatus(sectionId, status) {
    const section = document.getElementById(sectionId)
    const rows = section.querySelectorAll("tbody tr")
  
    rows.forEach((row) => {
      const statusElement = row.querySelector(".status")
      if (status === "all" || statusElement.textContent.toLowerCase() === status) {
        row.style.display = ""
      } else {
        row.style.display = "none"
      }
    })
  }
  
  // Filter by category
  function filterByCategory(sectionId, category) {
    const section = document.getElementById(sectionId)
    const rows = section.querySelectorAll("tbody tr")
  
    rows.forEach((row) => {
      const cells = row.querySelectorAll("td")
      const categoryCell = cells[2] // Category is in the 3rd column
  
      if (category === "all" || categoryCell.textContent.toLowerCase().includes(category.replace("-", " "))) {
        row.style.display = ""
      } else {
        row.style.display = "none"
      }
    })
  }
  
  // Seller actions
  function approveSeller(sellerId) {
    if (confirm("Are you sure you want to approve this seller?")) {
      // Update UI
      updateSellerStatus(sellerId, "approved")
      showNotification("Seller approved successfully!", "success")
  
      // Here you would make an API call to update the backend
      console.log(`Approving seller: ${sellerId}`)
    }
  }
  
  function rejectSeller(sellerId) {
    const reason = prompt("Please provide a reason for rejection:")
    if (reason) {
      updateSellerStatus(sellerId, "rejected")
      showNotification("Seller rejected successfully!", "error")
  
      // Here you would make an API call to update the backend
      console.log(`Rejecting seller: ${sellerId}, Reason: ${reason}`)
    }
  }
  
  function suspendSeller(sellerId) {
    if (confirm("Are you sure you want to suspend this seller?")) {
      updateSellerStatus(sellerId, "suspended")
      showNotification("Seller suspended successfully!", "warning")
  
      console.log(`Suspending seller: ${sellerId}`)
    }
  }
  
  function viewSellerDetails(sellerId) {
    const modal = document.getElementById("detailModal")
    const modalBody = document.getElementById("modalBody")
  
    // Mock seller details - in real app, fetch from API
    modalBody.innerHTML = `
          <h3>Seller Details</h3>
          <div style="margin-top: 20px;">
              <p><strong>Name:</strong> Sarah Johnson</p>
              <p><strong>Email:</strong> sarah.j@email.com</p>
              <p><strong>Experience:</strong> 5 years Digital Art</p>
              <p><strong>Portfolio:</strong> <a href="#" target="_blank">View Portfolio</a></p>
              <p><strong>Application Date:</strong> Dec 15, 2024</p>
              <p><strong>Bio:</strong> Passionate digital artist specializing in fantasy landscapes and character design.</p>
          </div>
      `
  
    modal.style.display = "block"
  }
  
  // Product actions
  async function approveProduct(productId) {
    if (confirm("Are you sure you want to approve this product?")) {
        try {
            const response = await fetch('review_product.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    productId: productId,
                    action: 'approve'
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                updateProductStatus(productId, "approved");
                showNotification("Product approved successfully!", "success");
                // Remove the row from the table since it's no longer pending
                const row = document.querySelector(`tr[data-product-id="${productId}"]`);
                if (row) row.remove();
            } else {
                showNotification(data.error || "Failed to approve product", "error");
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification("Failed to approve product", "error");
        }
    }
  }
  
  async function rejectProduct(productId) {
    const reason = prompt("Please provide a reason for rejection:")
    if (reason) {
        try {
            const response = await fetch('review_product.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    productId: productId,
                    action: 'reject',
                    notes: reason
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                updateProductStatus(productId, "rejected");
                showNotification("Product rejected successfully!", "error");
                // Remove the row from the table since it's no longer pending
                const row = document.querySelector(`tr[data-product-id="${productId}"]`);
                if (row) row.remove();
            } else {
                showNotification(data.error || "Failed to reject product", "error");
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification("Failed to reject product", "error");
        }
    }
  }
  
  function viewProductDetails(productId) {
    const modal = document.getElementById("detailModal");
    const modalBody = document.getElementById("modalBody");
    
    // Show loading state
    modalBody.innerHTML = '<div class="loading">Loading...</div>';
    modal.style.display = "block";
    
    // Fetch product details
    fetch(`get_product_details.php?id=${productId}`)
        .then(response => response.json())
        .then(product => {
        modalBody.innerHTML = `
                <h3>Product Details</h3>                <div class="product-detail">
                    <img src="${product.image_path.startsWith('/Art2Cart/') ? '' : '/Art2Cart/'}${product.image_path}" alt="${product.title}" class="detail-img">
                    <div class="detail-info">
                        <p><strong>Title:</strong> ${product.title}</p>
                        <p><strong>Category:</strong> ${product.category_name}</p>
                        <p><strong>Price:</strong> ₱${parseFloat(product.price).toFixed(2)}</p>
                        <p><strong>Seller:</strong> ${product.seller_name}</p>
                        <p><strong>Submitted:</strong> ${new Date(product.created_at).toLocaleDateString()}</p>
                        <p><strong>Description:</strong></p>
                        <p class="description">${product.description}</p>
                    </div>
                </div>
                <div class="detail-actions">
                    ${product.review_status === 'pending' ? `
                        <button onclick="approveProduct(${product.id})" class="btn-approve">
                            <i class="fas fa-check"></i> Approve
                        </button>
                    ` : ''}
                    <button onclick="rejectProduct(${product.id})" class="btn-reject">
                        <i class="fas fa-times"></i> Reject
                    </button>
                </div>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            modalBody.innerHTML = '<div class="error">Failed to load product details</div>';
        });
}
  
  // Seller application actions
  async function approveSellerApplication(applicationId) {
    if (confirm("Are you sure you want to approve this seller application?")) {
        try {
            const response = await fetch('review_seller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    applicationId: applicationId,
                    action: 'approve'
                })
            });

            const data = await response.json();
            
            if (response.ok) {
                showNotification(data.message, "success");
                // Remove the application row from the table
                document.querySelector(`tr[data-application-id="${applicationId}"]`).remove();
            } else {
                showNotification(data.error, "error");
            }
        } catch (error) {
            showNotification("An error occurred. Please try again.", "error");
            console.error(error);
        }
    }
}

async function rejectSellerApplication(applicationId) {
    const reason = prompt("Please provide a reason for rejection:");
    if (reason) {
        try {
            const response = await fetch('review_seller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    applicationId: applicationId,
                    action: 'reject',
                    reason: reason
                })
            });

            const data = await response.json();
            
            if (response.ok) {
                showNotification(data.message, "success");
                // Remove the application row from the table
                document.querySelector(`tr[data-application-id="${applicationId}"]`).remove();
            } else {
                showNotification(data.error, "error");
            }
        } catch (error) {
            showNotification("An error occurred. Please try again.", "error");
            console.error(error);
        }
    }
}

function viewSellerDetails(applicationId) {
    const modal = document.getElementById("detailModal");
    const modalBody = document.getElementById("modalBody");
    const row = document.querySelector(`tr[data-application-id="${applicationId}"]`);
    
    const name = row.cells[0].textContent;
    const email = row.cells[1].textContent;
    const experience = row.cells[2].textContent;
    const portfolio = row.cells[3].querySelector('a')?.href || 'Not provided';
    const governmentId = row.cells[4].querySelector('a')?.href || 'Not provided';
    const applicationDate = row.cells[5].textContent;
    
    modalBody.innerHTML = `
        <h3>Seller Application Details</h3>
        <div style="margin-top: 20px;">
            <p><strong>Name:</strong> ${name}</p>
            <p><strong>Email:</strong> ${email}</p>
            <p><strong>Experience:</strong> ${experience}</p>
            <p><strong>Portfolio:</strong> ${portfolio === 'Not provided' ? portfolio : `<a href="${portfolio}" target="_blank">View Portfolio <i class="fas fa-external-link-alt"></i></a>`}</p>
            <p><strong>Government ID:</strong> ${governmentId === 'Not provided' ? governmentId : `<a href="${governmentId}" target="_blank"><i class="fas fa-id-card"></i> View ID</a>`}</p>
            <p><strong>Application Date:</strong> ${applicationDate}</p>
        </div>
    `;

    modal.style.display = "block";
}

// Helper functions
  function updateSellerStatus(sellerId, status) {
    // Find the seller row and update status
    const rows = document.querySelectorAll("tbody tr")
    rows.forEach((row) => {
      if (row.dataset.sellerId === sellerId) {
        const statusElement = row.querySelector(".status")
        statusElement.textContent = status
        statusElement.className = `status ${status}`
      }
    })
  }
  
  function updateProductStatus(productId, status) {
    // Find the product row and update status
    const rows = document.querySelectorAll("tbody tr")
    rows.forEach((row) => {
      if (row.dataset.productId === productId) {
        const statusElement = row.querySelector(".status")
        statusElement.textContent = status
        statusElement.className = `status ${status}`
      }
    })
  }
  
  function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement("div")
    notification.style.cssText = `
          position: fixed;
          top: 20px;
          right: 20px;
          padding: 12px 20px;
          border-radius: 6px;
          color: white;
          font-weight: 500;
          z-index: 1001;
          animation: slideIn 0.3s ease;
      `
  
    // Set background color based on type
    switch (type) {
      case "success":
        notification.style.backgroundColor = "#059669"
        break
      case "error":
        notification.style.backgroundColor = "#dc2626"
        break
      case "warning":
        notification.style.backgroundColor = "#d97706"
        break
      default:
        notification.style.backgroundColor = "#3b82f6"
    }
  
    notification.textContent = message
    document.body.appendChild(notification)
  
    // Remove notification after 3 seconds
    setTimeout(() => {
      notification.remove()
    }, 3000)
  }
  
  // Add CSS animation for notifications
  const style = document.createElement("style")
  style.textContent = `
      @keyframes slideIn {
          from {
              transform: translateX(100%);
              opacity: 0;
          }
          to {
              transform: translateX(0);
              opacity: 1;
          }
      }
  `
  document.head.appendChild(style)
