<?php
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Get featured products
try {
    $featuredQuery = "SELECT p.*, pi.image_path, v.store_name, c.name as category_name 
                      FROM products p 
                      LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
                      LEFT JOIN vendors v ON p.vendor_id = v.id 
                      LEFT JOIN categories c ON p.category_id = c.id 
                      WHERE p.status = 'active' AND p.featured = 1 
                      ORDER BY p.created_at DESC 
                      LIMIT 8";
    $featuredStmt = $db->prepare($featuredQuery);
    $featuredStmt->execute();
    $featuredProducts = $featuredStmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $featuredProducts = array();
}

// Get categories
try {
    $categoriesQuery = "SELECT * FROM categories WHERE parent_id IS NULL AND status = 'active' ORDER BY sort_order, name LIMIT 6";
    $categoriesStmt = $db->prepare($categoriesQuery);
    $categoriesStmt->execute();
    $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $categories = array();
}

// Get latest products
try {
    $latestQuery = "SELECT p.*, pi.image_path, v.store_name, c.name as category_name 
                    FROM products p 
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
                    LEFT JOIN vendors v ON p.vendor_id = v.id 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    WHERE p.status = 'active' 
                    ORDER BY p.created_at DESC 
                    LIMIT 12";
    $latestStmt = $db->prepare($latestQuery);
    $latestStmt->execute();
    $latestProducts = $latestStmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $latestProducts = array();
}

$pageTitle = "Home - " . SITE_NAME;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --accent-color: #f59e0b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: #334155;
        }

        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1d4ed8 100%);
            color: white;
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://placehold.co/1920x800?text=Modern+marketplace+hero+background+with+shopping+elements') center/cover;
            opacity: 0.1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 12px 30px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 12px 30px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .category-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            height: 100%;
        }

        .category-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }

        .category-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), #3b82f6);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 2rem;
            font-weight: bold;
        }

        .product-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .product-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .product-info {
            padding: 20px;
        }

        .product-price {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .product-rating {
            color: var(--accent-color);
            margin: 10px 0;
        }

        .star-rating .star {
            font-size: 14px;
        }

        .star.full {
            color: var(--accent-color);
        }

        .star.empty {
            color: #d1d5db;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 60px;
            color: var(--dark-color);
        }

        .section-subtitle {
            text-align: center;
            font-size: 1.125rem;
            color: var(--secondary-color);
            margin-bottom: 50px;
        }

        .stats-section {
            background: var(--dark-color);
            color: white;
            padding: 80px 0;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: var(--accent-color);
            display: block;
        }

        .stat-label {
            font-size: 1.125rem;
            opacity: 0.8;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color) !important;
        }

        .navbar-nav .nav-link {
            font-weight: 500;
            padding: 12px 16px !important;
            transition: color 0.3s ease;
        }

        .navbar-nav .nav-link:hover {
            color: var(--primary-color) !important;
        }

        .footer {
            background: var(--dark-color);
            color: white;
            padding: 60px 0 20px;
        }

        .footer h5 {
            color: white;
            margin-bottom: 20px;
        }

        .footer a {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer a:hover {
            color: white;
        }

        .search-section {
            background: white;
            padding: 40px 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .search-form {
            max-width: 600px;
            margin: 0 auto;
        }

        .search-input {
            border-radius: 50px;
            padding: 15px 25px;
            border: 2px solid #e2e8f0;
            font-size: 1rem;
        }

        .search-btn {
            border-radius: 50px;
            padding: 15px 30px;
        }

        @media (max-width: 768px) {
            .hero-section {
                padding: 60px 0;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php"><?php echo SITE_NAME; ?></a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categories.php">Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="vendors.php">Vendors</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if ($auth->isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="cart.php">Cart (<span id="cart-count">0</span>)</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <?php echo $_SESSION['first_name']; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <?php if ($auth->hasRole('customer')): ?>
                                    <li><a class="dropdown-item" href="customer/dashboard.php">Dashboard</a></li>
                                    <li><a class="dropdown-item" href="customer/orders.php">My Orders</a></li>
                                    <li><a class="dropdown-item" href="customer/profile.php">Profile</a></li>
                                <?php elseif ($auth->hasRole('vendor')): ?>
                                    <li><a class="dropdown-item" href="vendor/dashboard.php">Vendor Dashboard</a></li>
                                    <li><a class="dropdown-item" href="vendor/products.php">My Products</a></li>
                                    <li><a class="dropdown-item" href="vendor/orders.php">Orders</a></li>
                                <?php elseif ($auth->hasRole('admin')): ?>
                                    <li><a class="dropdown-item" href="admin/dashboard.php">Admin Panel</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content">
                        <h1 class="display-4 fw-bold mb-4">
                            Discover Amazing Products from Top Vendors
                        </h1>
                        <p class="lead mb-4">
                            Shop from thousands of trusted vendors and find everything you need in one place. 
                            Quality products, competitive prices, and exceptional service.
                        </p>
                        <div class="hero-buttons">
                            <a href="products.php" class="btn btn-primary btn-lg me-3">Start Shopping</a>
                            <a href="vendor-register.php" class="btn btn-outline-light btn-lg">Become a Vendor</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="https://placehold.co/600x400?text=Hero+marketplace+illustration+with+shopping+bags+and+products" 
                         alt="Hero marketplace illustration" class="img-fluid">
                </div>
            </div>
        </div>
    </section>

    <!-- Search Section -->
    <section class="search-section">
        <div class="container">
            <form class="search-form" method="GET" action="products.php">
                <div class="input-group">
                    <input type="text" name="search" class="form-control search-input" 
                           placeholder="Search for products, brands, categories..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button class="btn btn-primary search-btn" type="submit">Search</button>
                </div>
            </form>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="section-title">Shop by Category</h2>
            <p class="section-subtitle">Explore our diverse range of product categories</p>
            
            <div class="row g-4">
                <?php foreach ($categories as $category): ?>
                <div class="col-lg-4 col-md-6">
                    <a href="products.php?category=<?php echo $category['slug']; ?>" class="text-decoration-none">
                        <div class="category-card">
                            <div class="category-icon">
                                <?php echo strtoupper(substr($category['name'], 0, 1)); ?>
                            </div>
                            <h5 class="fw-bold"><?php echo htmlspecialchars($category['name']); ?></h5>
                            <p class="text-muted"><?php echo htmlspecialchars($category['description']); ?></p>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <?php if (!empty($featuredProducts)): ?>
    <section class="py-5">
        <div class="container">
            <h2 class="section-title">Featured Products</h2>
            <p class="section-subtitle">Hand-picked products from our top vendors</p>
            
            <div class="row g-4">
                <?php foreach ($featuredProducts as $product): ?>
                <div class="col-lg-3 col-md-6">
                    <div class="product-card">
                        <img src="<?php echo $product['image_path'] ? 'uploads/' . $product['image_path'] : 'https://placehold.co/300x250?text=' . urlencode($product['name']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                        
                        <div class="product-info">
                            <h6 class="fw-bold mb-2">
                                <a href="product.php?slug=<?php echo $product['slug']; ?>" class="text-decoration-none text-dark">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </a>
                            </h6>
                            <p class="text-muted small mb-2">by <?php echo htmlspecialchars($product['store_name']); ?></p>
                            
                            <?php if ($product['rating'] > 0): ?>
                            <div class="product-rating">
                                <?php echo getStarRating($product['rating']); ?>
                                <small>(<?php echo $product['total_reviews']; ?>)</small>
                            </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="product-price"><?php echo formatPrice($product['price']); ?></span>
                                <button class="btn btn-sm btn-primary" onclick="addToCart(<?php echo $product['id']; ?>)">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <span class="stat-number">10K+</span>
                        <div class="stat-label">Products</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <span class="stat-number">500+</span>
                        <div class="stat-label">Vendors</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <span class="stat-number">50K+</span>
                        <div class="stat-label">Happy Customers</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <span class="stat-number">99%</span>
                        <div class="stat-label">Satisfaction</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Latest Products -->
    <?php if (!empty($latestProducts)): ?>
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="section-title">Latest Products</h2>
            <p class="section-subtitle">Discover the newest additions to our marketplace</p>
            
            <div class="row g-4">
                <?php foreach (array_slice($latestProducts, 0, 8) as $product): ?>
                <div class="col-lg-3 col-md-6">
                    <div class="product-card">
                        <img src="<?php echo $product['image_path'] ? 'uploads/' . $product['image_path'] : 'https://placehold.co/300x250?text=' . urlencode($product['name']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                        
                        <div class="product-info">
                            <h6 class="fw-bold mb-2">
                                <a href="product.php?slug=<?php echo $product['slug']; ?>" class="text-decoration-none text-dark">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </a>
                            </h6>
                            <p class="text-muted small mb-2">by <?php echo htmlspecialchars($product['store_name']); ?></p>
                            
                            <?php if ($product['rating'] > 0): ?>
                            <div class="product-rating">
                                <?php echo getStarRating($product['rating']); ?>
                                <small>(<?php echo $product['total_reviews']; ?>)</small>
                            </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="product-price"><?php echo formatPrice($product['price']); ?></span>
                                <button class="btn btn-sm btn-primary" onclick="addToCart(<?php echo $product['id']; ?>)">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="products.php" class="btn btn-primary btn-lg">View All Products</a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5><?php echo SITE_NAME; ?></h5>
                    <p>Your trusted multivendor marketplace connecting buyers with quality vendors worldwide.</p>
                    <div class="social-links">
                        <a href="#" class="me-3">Facebook</a>
                        <a href="#" class="me-3">Twitter</a>
                        <a href="#" class="me-3">Instagram</a>
                        <a href="#" class="me-3">LinkedIn</a>
                    </div>
                </div>
                
                <div class="col-lg-2">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="products.php">Products</a></li>
                        <li><a href="categories.php">Categories</a></li>
                        <li><a href="vendors.php">Vendors</a></li>
                        <li><a href="about.php">About Us</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2">
                    <h5>Customer Care</h5>
                    <ul class="list-unstyled">
                        <li><a href="contact.php">Contact Us</a></li>
                        <li><a href="faq.php">FAQ</a></li>
                        <li><a href="returns.php">Returns</a></li>
                        <li><a href="shipping.php">Shipping Info</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2">
                    <h5>For Vendors</h5>
                    <ul class="list-unstyled">
                        <li><a href="vendor-register.php">Become a Vendor</a></li>
                        <li><a href="vendor/login.php">Vendor Login</a></li>
                        <li><a href="vendor-guide.php">Seller Guide</a></li>
                        <li><a href="fees.php">Fees & Pricing</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2">
                    <h5>Legal</h5>
                    <ul class="list-unstyled">
                        <li><a href="privacy.php">Privacy Policy</a></li>
                        <li><a href="terms.php">Terms of Service</a></li>
                        <li><a href="cookies.php">Cookie Policy</a></li>
                    </ul>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>Made with ❤️ for vendors and customers worldwide</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Add to cart function
        function addToCart(productId) {
            // Check if user is logged in
            <?php if (!$auth->isLoggedIn()): ?>
            alert('Please login to add items to cart');
            window.location.href = 'login.php';
            return;
            <?php endif; ?>
            
            fetch('api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'add',
                    product_id: productId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Product added to cart!');
                    updateCartCount();
                } else {
                    alert(data.message || 'Error adding product to cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding product to cart');
            });
        }
        
        // Update cart count
        function updateCartCount() {
            fetch('api/cart.php?action=count')
            .then(response => response.json())
            .then(data => {
                document.getElementById('cart-count').textContent = data.count || 0;
            })
            .catch(error => console.error('Error updating cart count:', error));
        }
        
        // Load cart count on page load
        <?php if ($auth->isLoggedIn()): ?>
        document.addEventListener('DOMContentLoaded', updateCartCount);
        <?php endif; ?>
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>