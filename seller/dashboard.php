<?php
require_once '../config/db.php';
require_once '../includes/User.php';

session_start();

// Check if user is logged in and has seller role
if (!isset($_SESSION['user_id'])) {
    header('Location: /Art2Cart/auth/auth.html');
    exit;
}

$user = new User($_SESSION['user_id']);
if (!$user->hasRole('seller')) {
    header('Location: /Art2Cart/auth/become_seller.html');
    exit;
}

// Get seller's products
$products = $user->getProducts();
$stats = $user->getProductStats();

// Get all categories for the product form
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT id, name FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - Art2Cart</title>    <link rel="stylesheet" href="/Art2Cart/static/css/var.css">
    <link rel="stylesheet" href="/Art2Cart/static/css/fonts.css">
    <link rel="stylesheet" href="/Art2Cart/static/css/seller/dashboard.css">
    
</head>
<body>
    <?php include '../static/templates/header_new.php'; ?>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Seller Dashboard</h1>
            <button class="upload-btn" onclick="openUploadModal()">Upload New Product</button>
        </div>

        <!-- Statistics Section -->
        <div class="stats-grid">
            <div class="stat-card">                <div class="stat-value"><?php echo number_format($stats['total_products'] ?? 0); ?></div>
                <div class="stat-label">Active Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['total_downloads'] ?? 0); ?></div>
                <div class="stat-label">Total Downloads</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['average_rating'] ?? 0, 1); ?></div>
                <div class="stat-label">Average Rating</div>
            </div>
        </div>

        <!-- Filters -->        <div class="filters">
            <button class="filter-btn active" onclick="filterProducts('all')">All</button>
            <button class="filter-btn" onclick="filterProducts('active')">Active</button>
            <button class="filter-btn" onclick="filterProducts('pending')">Pending</button>
            <button class="filter-btn" onclick="filterProducts('rejected')">Rejected</button>
            <button class="filter-btn" onclick="filterProducts('inactive')">Inactive</button>
        </div>

        <!-- Products Grid -->
        <div class="products-grid">
            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <h3>No Products Yet</h3>
                    <p>Start selling by uploading your first digital product</p>
                    <button class="upload-btn" onclick="openUploadModal()">Upload Product</button>
                </div>
            <?php else: ?>                <?php foreach ($products as $product): ?>
                    <article class="product-card" data-status="<?php echo $product['status']; ?>" data-product-id="<?php echo $product['id']; ?>">                        <div class="product-image-container">
                            <img class="product-image" 
                                src="<?php echo htmlspecialchars('/Art2Cart/' . $product['image_path']); ?>" 
                                alt="<?php echo htmlspecialchars($product['title']); ?>">
                            <span class="product-status status-<?php echo $product['status']; ?>">
                                <?php echo ucfirst($product['status']); ?>
                            </span>
                        </div>
                        <div class="product-details">
                            <h3 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h3>
                            <div class="product-stats">
                                <span>Downloads: <?php echo $product['downloads']; ?></span>
                                <span>Rating: <?php echo number_format($product['average_rating'], 1); ?> (<?php echo $product['rating_count']; ?>)</span>
                            </div>
                            <p class="product-price">₱<?php echo number_format($product['price'], 2); ?></p>
                              <div class="action-buttons">
                                <?php if ($product['status'] === 'rejected'): ?>
                                    <button class="edit-btn" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                        Edit & Resubmit
                                    </button>
                                    <button class="delete-btn" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                        Delete
                                    </button>
                                <?php else: ?>
                                    <button class="edit-btn" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                        Edit
                                    </button>
                                    <button class="delete-btn" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                        Delete
                                    </button>
                                    <?php if ($product['status'] !== 'pending'): ?>
                                        <button class="toggle-btn" onclick="toggleProductStatus(<?php echo $product['id']; ?>, '<?php echo $product['status'] === 'active' ? 'inactive' : 'active'; ?>')">
                                            <?php echo $product['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <?php if ($product['status'] === 'rejected'): ?>
                                <div class="rejection-message">
                                    <?php if (!empty($product['review_notes'])): ?>
                                        <p><?php echo htmlspecialchars($product['review_notes']); ?></p>
                                    <?php else: ?>
                                        <p>Product was rejected by admin. No specific reason provided.</p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Upload/Edit Modal -->
        <div id="productModal" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="closeModal()">&times;</span>
                <h2 id="modalTitle">Upload New Product</h2>
                <div id="productAlert" class="alert"></div>
                <form id="productForm" onsubmit="handleProductSubmit(event)">
                    <input type="hidden" id="productId" value="">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="price">Price (₱)</label>
                        <input type="number" id="price" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" required>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="productImage">Product Image</label>
                        <input type="file" id="productImage" accept="image/*" required>
                    </div>
                    <div class="form-group">
                        <label for="productFile">Digital Product File</label>
                        <input type="file" id="productFile" required>
                    </div>
                    <button type="submit" class="upload-btn">Upload Product</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        let editingProductId = null;        function openUploadModal() {
            document.getElementById('modalTitle').textContent = 'Upload New Product';
            document.getElementById('productId').value = '';
            document.getElementById('productForm').reset();
            
            // Show file inputs for new product
            document.getElementById('productImage').parentElement.style.display = 'block';
            document.getElementById('productFile').parentElement.style.display = 'block';
            document.getElementById('productImage').required = true;
            document.getElementById('productFile').required = true;
            
            // Reset submit button text
            const submitBtn = document.querySelector('#productForm button[type="submit"]');
            submitBtn.textContent = 'Upload Product';
            
            document.getElementById('productModal').style.display = 'block';
            editingProductId = null;
        }

        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
            document.getElementById('productAlert').style.display = 'none';
            editingProductId = null;
        }

        function showAlert(message, type) {
            const alert = document.getElementById('productAlert');
            alert.textContent = message;
            alert.className = `alert alert-${type}`;
            alert.style.display = 'block';
            
            if (type === 'success') {
                setTimeout(() => {
                    closeModal();
                    // Reload page to show updated products
                    window.location.reload();
                }, 1500);
            }
        }        function editProduct(product) {
            document.getElementById('modalTitle').textContent = 'Edit Product';
            document.getElementById('productId').value = product.id;
            document.getElementById('title').value = product.title;
            document.getElementById('description').value = product.description;
            document.getElementById('price').value = product.price;
            document.getElementById('category').value = product.category_id;
            
            // Hide file inputs when editing existing product
            document.getElementById('productImage').parentElement.style.display = 'none';
            document.getElementById('productFile').parentElement.style.display = 'none';
            document.getElementById('productImage').required = false;
            document.getElementById('productFile').required = false;
            
            // Change submit button text
            const submitBtn = document.querySelector('#productForm button[type="submit"]');
            submitBtn.textContent = 'Update Product';
            
            document.getElementById('productModal').style.display = 'block';
            editingProductId = product.id;
        }

        async function handleProductSubmit(event) {
            event.preventDefault();
            
            try {
                const formData = new FormData();
                
                // Basic form validation
                const title = document.getElementById('title').value.trim();
                const description = document.getElementById('description').value.trim();
                const price = document.getElementById('price').value;
                const categoryId = document.getElementById('category').value;
                
                if (!title || !description || !price || !categoryId) {
                    throw new Error('Please fill in all required fields');
                }

                formData.append('title', title);
                formData.append('description', description);
                formData.append('price', price);
                formData.append('category_id', categoryId);
                
                const imageFile = document.getElementById('productImage').files[0];
                const productFile = document.getElementById('productFile').files[0];
                  // Handle files differently for new vs editing
                if (!editingProductId) {  // New product
                    if (!imageFile) throw new Error('Please select a product image');
                    if (!productFile) throw new Error('Please select a product file');
                    
                    if (imageFile.size > 10 * 1024 * 1024) throw new Error('Image file size must be less than 10MB');
                    if (productFile.size > 100 * 1024 * 1024) throw new Error('Product file size must be less than 100MB');
                    
                    formData.append('image', imageFile);
                    formData.append('file', productFile);
                } else {  // Editing existing product
                    formData.append('id', editingProductId);
                    
                    // Only append files if they were changed
                    if (imageFile) {
                        if (imageFile.size > 10 * 1024 * 1024) throw new Error('Image file size must be less than 10MB');
                        formData.append('image', imageFile);
                    }
                    if (productFile) {
                        if (productFile.size > 100 * 1024 * 1024) throw new Error('Product file size must be less than 100MB');
                        formData.append('file', productFile);
                    }
                }const response = await fetch('/Art2Cart/seller/upload_product.php', {
                    method: 'POST',
                    body: formData
                });

                let data;
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    data = await response.json();
                } else {
                    throw new Error('Invalid response format from server');
                }

                if (response.ok && data.success) {
                    showAlert(editingProductId ? 'Product updated successfully!' : 'Product uploaded successfully!', 'success');
                } else {
                    throw new Error(data.error || 'Server error occurred');
                }
            } catch (error) {
                console.error('Upload error:', error);
                showAlert(error.message || 'An error occurred. Please try again.', 'error');
            }
        }

        function filterProducts(status) {
            // Update filter buttons
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');

            // Filter products
            document.querySelectorAll('.product-card').forEach(card => {
                if (status === 'all' || card.dataset.status === status) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        async function toggleProductStatus(productId, newStatus) {
            if (!confirm(`Are you sure you want to ${newStatus === 'active' ? 'activate' : 'deactivate'} this product?`)) {
                return;
            }            try {
                const response = await fetch('/Art2Cart/seller/update_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ productId, status: newStatus })
                });

                let data;
                try {
                    data = await response.json();
                } catch (parseError) {
                    console.error('JSON parsing error:', parseError);
                    throw new Error('Invalid response from server');
                }

                if (response.ok) {
                    // Update the UI immediately instead of reloading
                    const productCard = document.querySelector(`[data-product-id="${productId}"]`);
                    if (productCard) {
                        productCard.dataset.status = newStatus;
                        const statusSpan = productCard.querySelector('.product-status');
                        if (statusSpan) {
                            statusSpan.className = `product-status status-${newStatus}`;
                            statusSpan.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                        }
                        const toggleButton = productCard.querySelector(`button[onclick*="toggleProductStatus"]`);
                        if (toggleButton) {
                            toggleButton.textContent = newStatus === 'active' ? 'Deactivate' : 'Activate';
                            toggleButton.setAttribute('onclick', `toggleProductStatus(${productId}, '${newStatus === 'active' ? 'inactive' : 'active'}')`);
                        }
                    }
                    showAlert(`Product ${newStatus === 'active' ? 'activated' : 'deactivated'} successfully!`, 'success');
                } else {
                    throw new Error(data.error || 'Failed to update product status');
                }
            } catch (error) {
                console.error('Status update error:', error);
                showAlert(error.message || 'An error occurred while updating the product status', 'error');
            }
        }

        async function deleteProduct(productId) {
            if (!confirm('Are you sure you want to delete this product? This cannot be undone.')) {
                return;
            }

            try {
                const response = await fetch('/Art2Cart/seller/delete_product.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ productId })
                });

                const data = await response.json();

                if (response.ok) {
                    // Remove the product card from the UI
                    const productCard = document.querySelector(`.product-card[data-status][data-product-id="${productId}"]`);
                    if (productCard) {
                        productCard.remove();
                        
                        // Show success message
                        showAlert('Product deleted successfully!', 'success');
                        
                        // Reload the page after a short delay to update stats
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                } else {
                    throw new Error(data.error || 'Failed to delete product');
                }
            } catch (error) {
                console.error('Delete error:', error);
                showAlert(error.message || 'An error occurred while deleting the product.', 'error');
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
