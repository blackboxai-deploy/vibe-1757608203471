const express = require('express');
const path = require('path');
const fs = require('fs');

const app = express();
const PORT = 8000;

// Middleware
app.use(express.static('.'));
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Mock database data
const mockData = {
    users: [
        { id: 1, username: 'admin', email: 'admin@marketplace.com', role: 'admin', first_name: 'Admin', last_name: 'User' },
        { id: 2, username: 'techstore', email: 'tech@example.com', role: 'vendor', first_name: 'John', last_name: 'Tech' },
        { id: 3, username: 'customer1', email: 'customer1@example.com', role: 'customer', first_name: 'Alice', last_name: 'Johnson' }
    ],
    vendors: [
        { id: 1, user_id: 2, store_name: 'TechWorld Electronics', status: 'approved' }
    ],
    categories: [
        { id: 1, name: 'Electronics', slug: 'electronics', status: 'active' },
        { id: 2, name: 'Fashion', slug: 'fashion', status: 'active' },
        { id: 3, name: 'Home & Garden', slug: 'home-garden', status: 'active' }
    ],
    products: [
        {
            id: 1,
            vendor_id: 1,
            category_id: 1,
            name: 'Wireless Bluetooth Headphones',
            slug: 'wireless-bluetooth-headphones',
            description: 'Premium quality wireless Bluetooth headphones with noise cancellation.',
            price: 89.99,
            stock_quantity: 50,
            featured: 1,
            status: 'active',
            rating: 4.5,
            total_reviews: 24,
            store_name: 'TechWorld Electronics',
            category_name: 'Electronics'
        },
        {
            id: 2,
            vendor_id: 1,
            category_id: 1,
            name: 'Smart Fitness Tracker Watch',
            slug: 'smart-fitness-tracker-watch',
            description: 'Advanced fitness tracker with heart rate monitoring and GPS.',
            price: 149.99,
            stock_quantity: 35,
            featured: 1,
            status: 'active',
            rating: 4.3,
            total_reviews: 18,
            store_name: 'TechWorld Electronics',
            category_name: 'Electronics'
        },
        {
            id: 3,
            vendor_id: 1,
            category_id: 1,
            name: 'Portable Power Bank 20000mAh',
            slug: 'portable-power-bank-20000mah',
            description: 'High-capacity portable charger with fast charging technology.',
            price: 39.99,
            stock_quantity: 75,
            featured: 0,
            status: 'active',
            rating: 4.7,
            total_reviews: 42,
            store_name: 'TechWorld Electronics',
            category_name: 'Electronics'
        }
    ]
};

// Session simulation
let sessions = {};

// Helper functions
function generateSessionId() {
    return Math.random().toString(36).substr(2, 9);
}

function getCurrentUser(sessionId) {
    return sessions[sessionId] || null;
}

// Routes

// Home page
app.get('/', (req, res) => {
    const html = generateHomePage();
    res.send(html);
});

// Products page
app.get('/products', (req, res) => {
    const html = generateProductsPage(req.query);
    res.send(html);
});

// Login page
app.get('/login', (req, res) => {
    const html = generateLoginPage();
    res.send(html);
});

// Login API
app.post('/api/login', (req, res) => {
    const { email, password } = req.body;
    const user = mockData.users.find(u => u.email === email);
    
    if (user && password === 'password') { // Mock password check
        const sessionId = generateSessionId();
        sessions[sessionId] = user;
        
        res.json({ 
            success: true, 
            sessionId: sessionId,
            user: user,
            redirect: user.role === 'admin' ? '/admin/dashboard' : 
                     user.role === 'vendor' ? '/vendor/dashboard' : '/customer/dashboard'
        });
    } else {
        res.json({ success: false, message: 'Invalid credentials' });
    }
});

// Cart API
app.get('/api/cart', (req, res) => {
    const { action } = req.query;
    
    if (action === 'count') {
        res.json({ count: 3 }); // Mock cart count
    } else if (action === 'items') {
        res.json({ 
            items: [],
            total: 0,
            formatted_total: '$0.00'
        });
    }
});

app.post('/api/cart', (req, res) => {
    const { action } = req.body;
    
    if (action === 'add') {
        res.json({ success: true, message: 'Product added to cart' });
    } else {
        res.json({ success: false, message: 'Invalid action' });
    }
});

// HTML Generators
function generateHomePage() {
    const featuredProducts = mockData.products.filter(p => p.featured);
    const categories = mockData.categories;
    
    return `
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - MultiVendor Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .hero-section { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: white; padding: 100px 0; }
        .product-card { border-radius: 16px; transition: transform 0.3s ease; }
        .product-card:hover { transform: translateY(-5px); }
        .product-image { width: 100%; height: 250px; object-fit: cover; }
        .product-price { font-size: 1.25rem; font-weight: 600; color: #2563eb; }
        .btn-primary { background-color: #2563eb; border-color: #2563eb; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="/">MultiVendor Marketplace</a>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="/">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="/products">Products</a></li>
                <li class="nav-item"><a class="nav-link" href="/login">Login</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Discover Amazing Products from Top Vendors</h1>
                    <p class="lead mb-4">Shop from thousands of trusted vendors and find everything you need in one place.</p>
                    <a href="/products" class="btn btn-primary btn-lg me-3">Start Shopping</a>
                    <a href="/vendor-register" class="btn btn-outline-light btn-lg">Become a Vendor</a>
                </div>
                <div class="col-lg-6">
                    <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/149b40db-077e-4c35-9ad5-3ab5823107e4.png" alt="Hero" class="img-fluid">
                </div>
            </div>
        </div>
    </section>

    <!-- Categories -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Shop by Category</h2>
            <div class="row g-4">
                ${categories.map(category => `
                    <div class="col-lg-4 col-md-6">
                        <a href="/products?category=${category.slug}" class="text-decoration-none">
                            <div class="card h-100 text-center p-4">
                                <div class="mb-3">
                                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                        <h3 class="mb-0">${category.name.charAt(0)}</h3>
                                    </div>
                                </div>
                                <h5>${category.name}</h5>
                                <p class="text-muted">Explore our ${category.name.toLowerCase()} collection</p>
                            </div>
                        </a>
                    </div>
                `).join('')}
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Featured Products</h2>
            <div class="row g-4">
                ${featuredProducts.map(product => `
                    <div class="col-lg-3 col-md-6">
                        <div class="card product-card h-100">
                            <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/cc7d536d-967a-41aa-a948-c890ad2bc097.png}" 
                                 alt="${product.name}" class="product-image card-img-top">
                            <div class="card-body d-flex flex-column">
                                <h6 class="card-title">${product.name}</h6>
                                <p class="text-muted small">by ${product.store_name}</p>
                                <div class="text-warning mb-2">
                                    ${'★'.repeat(Math.floor(product.rating))} (${product.total_reviews})
                                </div>
                                <div class="mt-auto d-flex justify-content-between align-items-center">
                                    <span class="product-price">$${product.price}</span>
                                    <button class="btn btn-primary btn-sm" onclick="addToCart(${product.id})">Add to Cart</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-5 bg-dark text-white">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3"><h3 class="text-warning">10K+</h3><p>Products</p></div>
                <div class="col-md-3"><h3 class="text-warning">500+</h3><p>Vendors</p></div>
                <div class="col-md-3"><h3 class="text-warning">50K+</h3><p>Happy Customers</p></div>
                <div class="col-md-3"><h3 class="text-warning">99%</h3><p>Satisfaction</p></div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <h5>MultiVendor Marketplace</h5>
                    <p>Your trusted marketplace connecting buyers with quality vendors worldwide.</p>
                </div>
                <div class="col-lg-8">
                    <div class="row">
                        <div class="col-md-3">
                            <h6>Quick Links</h6>
                            <ul class="list-unstyled">
                                <li><a href="/products" class="text-light text-decoration-none">Products</a></li>
                                <li><a href="/categories" class="text-light text-decoration-none">Categories</a></li>
                                <li><a href="/vendors" class="text-light text-decoration-none">Vendors</a></li>
                            </ul>
                        </div>
                        <div class="col-md-3">
                            <h6>Customer Care</h6>
                            <ul class="list-unstyled">
                                <li><a href="/contact" class="text-light text-decoration-none">Contact Us</a></li>
                                <li><a href="/faq" class="text-light text-decoration-none">FAQ</a></li>
                                <li><a href="/returns" class="text-light text-decoration-none">Returns</a></li>
                            </ul>
                        </div>
                        <div class="col-md-3">
                            <h6>For Vendors</h6>
                            <ul class="list-unstyled">
                                <li><a href="/vendor-register" class="text-light text-decoration-none">Become a Vendor</a></li>
                                <li><a href="/vendor-login" class="text-light text-decoration-none">Vendor Login</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p>&copy; ${new Date().getFullYear()} MultiVendor Marketplace. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addToCart(productId) {
            fetch('/api/cart', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'add', product_id: productId, quantity: 1 })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Product added to cart!');
                } else {
                    alert('Please login to add items to cart');
                    window.location.href = '/login';
                }
            })
            .catch(() => alert('Error adding product to cart'));
        }
    </script>
</body>
</html>`;
}

function generateProductsPage(query = {}) {
    let products = [...mockData.products];
    const categories = mockData.categories;
    
    // Filter by search
    if (query.search) {
        products = products.filter(p => 
            p.name.toLowerCase().includes(query.search.toLowerCase()) ||
            p.description.toLowerCase().includes(query.search.toLowerCase())
        );
    }
    
    // Filter by category
    if (query.category) {
        const category = categories.find(c => c.slug === query.category);
        if (category) {
            products = products.filter(p => p.category_id === category.id);
        }
    }
    
    // Sort products
    switch (query.sort) {
        case 'price_low':
            products.sort((a, b) => a.price - b.price);
            break;
        case 'price_high':
            products.sort((a, b) => b.price - a.price);
            break;
        case 'rating':
            products.sort((a, b) => b.rating - a.rating);
            break;
        default:
            // newest first (default)
            break;
    }

    return `
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - MultiVendor Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .product-card { border-radius: 16px; transition: transform 0.3s ease; }
        .product-card:hover { transform: translateY(-5px); }
        .product-image { width: 100%; height: 250px; object-fit: cover; }
        .product-price { font-size: 1.25rem; font-weight: 600; color: #2563eb; }
        .btn-primary { background-color: #2563eb; border-color: #2563eb; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="/">MultiVendor Marketplace</a>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="/">Home</a></li>
                <li class="nav-item"><a class="nav-link active" href="/products">Products</a></li>
                <li class="nav-item"><a class="nav-link" href="/login">Login</a></li>
            </ul>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Filters -->
        <div class="bg-white rounded p-4 mb-4 shadow-sm">
            <h5 class="mb-3">Filter Products</h5>
            <form method="GET" action="/products">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="search" placeholder="Search products..." value="${query.search || ''}">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="category">
                            <option value="">All Categories</option>
                            ${categories.map(cat => `
                                <option value="${cat.slug}" ${query.category === cat.slug ? 'selected' : ''}>${cat.name}</option>
                            `).join('')}
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="sort">
                            <option value="newest" ${query.sort === 'newest' ? 'selected' : ''}>Newest First</option>
                            <option value="price_low" ${query.sort === 'price_low' ? 'selected' : ''}>Price: Low to High</option>
                            <option value="price_high" ${query.sort === 'price_high' ? 'selected' : ''}>Price: High to Low</option>
                            <option value="rating" ${query.sort === 'rating' ? 'selected' : ''}>Highest Rated</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Products Header -->
        <div class="bg-white rounded p-4 mb-4 shadow-sm">
            <h2>
                ${query.search ? `Search Results for "${query.search}"` : 'All Products'}
            </h2>
            <p class="text-muted mb-0">Showing ${products.length} products</p>
        </div>

        <!-- Products Grid -->
        ${products.length === 0 ? `
            <div class="text-center py-5 bg-white rounded">
                <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/8cb3605a-505a-4a8e-8798-2d28cb12de4a.png" alt="No products found" class="mb-4">
                <h3>No Products Found</h3>
                <p class="text-muted">Try adjusting your search or filter criteria</p>
                <a href="/products" class="btn btn-primary">Browse All Products</a>
            </div>
        ` : `
            <div class="row g-4 mb-5">
                ${products.map(product => `
                    <div class="col-lg-3 col-md-6">
                        <div class="card product-card h-100">
                            <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/bbc9d142-17ba-4d44-9fa1-990544ed7d7e.png}" 
                                 alt="${product.name}" class="product-image card-img-top">
                            <div class="card-body d-flex flex-column">
                                <div class="text-muted small text-uppercase mb-2">${product.category_name}</div>
                                <h6 class="card-title">${product.name}</h6>
                                <p class="text-muted small">by ${product.store_name}</p>
                                <div class="text-warning mb-2">
                                    ${'★'.repeat(Math.floor(product.rating))} (${product.total_reviews})
                                </div>
                                <div class="mt-auto">
                                    <div class="product-price mb-3">$${product.price}</div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-primary btn-sm flex-grow-1" onclick="addToCart(${product.id})">Add to Cart</button>
                                        <button class="btn btn-outline-primary btn-sm" onclick="addToWishlist(${product.id})">♡</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `}
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addToCart(productId) {
            fetch('/api/cart', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'add', product_id: productId, quantity: 1 })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Product added to cart!');
                } else {
                    alert('Please login to add items to cart');
                }
            })
            .catch(() => alert('Error adding product to cart'));
        }

        function addToWishlist(productId) {
            alert('Wishlist feature coming soon!');
        }
    </script>
</body>
</html>`;
}

function generateLoginPage() {
    return `
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MultiVendor Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            max-width: 420px;
            width: 100%;
            margin: 0 auto;
        }
        .btn-primary { background-color: #2563eb; border-color: #2563eb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card">
            <div class="text-center mb-4">
                <h1 class="h3 mb-3 fw-bold">Welcome Back</h1>
                <p class="text-muted">Sign in to your account to continue</p>
            </div>

            <div id="error-message" class="alert alert-danger d-none"></div>
            <div id="success-message" class="alert alert-success d-none"></div>

            <form id="loginForm">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                <button type="submit" class="btn btn-primary w-100 mb-3">Sign In</button>
            </form>

            <div class="text-center">
                <p><a href="/forgot-password">Forgot your password?</a></p>
                <p>Don't have an account? <a href="/register">Sign up here</a></p>
            </div>

            <div class="mt-4 p-3 bg-light rounded">
                <h6>Demo Credentials:</h6>
                <p class="mb-1"><strong>Admin:</strong> admin@marketplace.com / password</p>
                <p class="mb-1"><strong>Vendor:</strong> tech@example.com / password</p>
                <p class="mb-0"><strong>Customer:</strong> customer1@example.com / password</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const errorDiv = document.getElementById('error-message');
            const successDiv = document.getElementById('success-message');
            
            errorDiv.classList.add('d-none');
            successDiv.classList.add('d-none');
            
            try {
                const response = await fetch('/api/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    successDiv.textContent = 'Login successful! Redirecting...';
                    successDiv.classList.remove('d-none');
                    
                    setTimeout(() => {
                        if (data.user.role === 'admin') {
                            alert('Admin dashboard would redirect here');
                        } else if (data.user.role === 'vendor') {
                            alert('Vendor dashboard would redirect here');
                        } else {
                            alert('Customer dashboard would redirect here');
                        }
                        window.location.href = '/';
                    }, 1500);
                } else {
                    errorDiv.textContent = data.message || 'Login failed';
                    errorDiv.classList.remove('d-none');
                }
            } catch (error) {
                errorDiv.textContent = 'Network error occurred';
                errorDiv.classList.remove('d-none');
            }
        });
    </script>
</body>
</html>`;
}

// Check if Express is installed
try {
    require('express');
    console.log('Starting PHP Multivendor Marketplace Demo Server...');
    
    app.listen(PORT, () => {
        console.log(`🚀 Server running at http://localhost:${PORT}`);
        console.log('📁 PHP Marketplace files are available as static files');
        console.log('🔄 Demo routes available: /, /products, /login');
        console.log('🎯 This is a demonstration server showing the marketplace structure');
    });
} catch (e) {
    console.log('Express not found. Installing...');
}