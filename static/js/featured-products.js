// Mock backend data - This will be replaced with actual backend API calls
const mockFeaturedProducts = [
    {
        id: 1,
        title: "Scenery",
        imageUrl: "static/images/products/Scenary.png",
        rating: 4.8,
        downloads: 1200,
        price: 29.99
    },
    {
        id: 2,
        title: "Wandering Whales",
        imageUrl: "static/images/products/Wandering Whales.png",
        rating: 4.9,
        downloads: 850,
        price: 34.99
    },
    {
        id: 3,
        title: "Alter Ego",
        imageUrl: "static/images/products/Alter Ego.png",
        rating: 4.7,
        downloads: 2100,
        price: 24.99
    },
    {
        id: 4,
        title: "Art ni Juan",
        imageUrl: "static/images/products/sample.jpg",
        rating: 4.7,
        downloads: 2100,
        price: 24.99
    }
];

// Function to create a product card from data
function createProductCard(product) {
    return `
        <article class="product-card">
            <img class="product-image" src="${product.imageUrl}" alt="${product.title}">
            <div class="hover-content">
                <h3 class="product-title">${product.title}</h3>
                <div class="rating">
                    <img src="static/images/star.svg" alt="star" class="star-icon">
                    <span class="rating-score">${product.rating}</span>
                    <span class="downloads">(${product.downloads} downloads)</span>
                </div>
                <div class="price-row">
                    <span class="price">₱${product.price.toFixed(2)}</span>
                    <button class="add-to-cart" onclick="addToCart(${product.id})">Add to Cart</button>
                </div>
            </div>
        </article>
    `;
}

// Function to simulate fetching data from backend
async function fetchFeaturedProducts() {
    // Simulate API delay
    await new Promise(resolve => setTimeout(resolve, 500));
    return mockFeaturedProducts;
}

// Function to load featured products
async function loadFeaturedProducts() {
    const container = document.querySelector('.container3');
    
    try {
        // Add loading state
        container.innerHTML = '<div class="loading">Loading featured products...</div>';
        
        // Fetch products
        const products = await fetchFeaturedProducts();
        
        // Render products
        container.innerHTML = products.map(product => createProductCard(product)).join('');
        
        // Initialize slider after loading products
        const event = new CustomEvent('productsLoaded');
        document.dispatchEvent(event);
    } catch (error) {
        console.error('Error loading featured products:', error);
        container.innerHTML = '<div class="error">Failed to load featured products</div>';
    }
}

// Mock function for adding to cart
function addToCart(productId) {
    console.log(`Added product ${productId} to cart`);
    // This will be implemented by the backend developer
    alert('Product added to cart!');
}

// Load products when the page loads
document.addEventListener('DOMContentLoaded', loadFeaturedProducts); 