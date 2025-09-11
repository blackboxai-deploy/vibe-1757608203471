<?php
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Get search and filter parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
$vendor = isset($_GET['vendor']) ? sanitizeInput($_GET['vendor']) : '';
$minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$sortBy = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = PRODUCTS_PER_PAGE;
$offset = ($page - 1) * $perPage;

// Build where conditions
$whereConditions = ["p.status = 'active'"];
$params = array();

if (!empty($search)) {
    $whereConditions[] = "(p.name LIKE :search OR p.description LIKE :search OR p.short_description LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($category)) {
    $whereConditions[] = "c.slug = :category";
    $params[':category'] = $category;
}

if (!empty($vendor)) {
    $whereConditions[] = "v.id = :vendor";
    $params[':vendor'] = $vendor;
}

if ($minPrice > 0) {
    $whereConditions[] = "p.price >= :min_price";
    $params[':min_price'] = $minPrice;
}

if ($maxPrice > 0) {
    $whereConditions[] = "p.price <= :max_price";
    $params[':max_price'] = $maxPrice;
}

// Build sort order
$sortOptions = array(
    'newest' => 'p.created_at DESC',
    'oldest' => 'p.created_at ASC',
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'name_asc' => 'p.name ASC',
    'name_desc' => 'p.name DESC',
    'rating' => 'p.rating DESC'
);

$orderBy = isset($sortOptions[$sortBy]) ? $sortOptions[$sortBy] : $sortOptions['newest'];

// Build final query
$whereClause = implode(' AND ', $whereConditions);

// Get products
try {
    $productsQuery = "SELECT p.*, pi.image_path, v.store_name, c.name as category_name 
                      FROM products p 
                      LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
                      LEFT JOIN vendors v ON p.vendor_id = v.id 
                      LEFT JOIN categories c ON p.category_id = c.id 
                      WHERE $whereClause 
                      ORDER BY $orderBy 
                      LIMIT $perPage OFFSET $offset";
    
    $productsStmt = $db->prepare($productsQuery);
    $productsStmt->execute($params);
    $products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total 
                   FROM products p 
                   LEFT JOIN vendors v ON p.vendor_id = v.id 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   WHERE $whereClause";
    
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $totalProducts = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalProducts / $perPage);
    
} catch(PDOException $e) {
    $products = array();
    $totalProducts = 0;
    $totalPages = 0;
}

// Get categories for filter
try {
    $categoriesQuery = "SELECT * FROM categories WHERE status = 'active' ORDER BY name";
    $categoriesStmt = $db->prepare($categoriesQuery);
    $categoriesStmt->execute();
    $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $categories = array();
}

// Get vendors for filter
try {
    $vendorsQuery = "SELECT v.id, v.store_name FROM vendors v WHERE v.status = 'approved' ORDER BY v.store_name";
    $vendorsStmt = $db->prepare($vendorsQuery);
    $vendorsStmt->execute();
    $vendors = $vendorsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $vendors = array();
}

$pageTitle = "Products - " . SITE_NAME;
if (!empty($search)) {
    $pageTitle = "Search Results for '" . htmlspecialchars($search) . "' - " . SITE_NAME;
}
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
            background: var(--light-color);
        }

        .navbar {
            background: white !important;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        .filters-section {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin: 20px 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }

        .filter-form .form-control,
        .filter-form .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .filter-form .form-control:focus,
        .filter-form .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.15);
        }

        .products-header {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .product-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
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
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .product-category {
            color: var(--secondary-color);
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .product-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .product-title a {
            color: inherit;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .product-title a:hover {
            color: var(--primary-color);
        }

        .product-vendor {
            color: var(--secondary-color);
            font-size: 0.9rem;
            margin-bottom: 12px;
        }

        .product-rating {
            margin-bottom: 15px;
        }

        .star-rating .star {
            font-size: 14px;
            color: var(--accent-color);
        }

        .star.empty {
            color: #d1d5db;
        }

        .product-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 15px;
            margin-top: auto;
        }

        .product-actions {
            display: flex;
            gap: 10px;
            margin-top: auto;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
            transform: translateY(-1px);
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
            font-weight: 500;
        }

        .pagination {
            justify-content: center;
            margin-top: 40px;
        }

        .pagination .page-link {
            border: 2px solid #e2e8f0;
            color: var(--secondary-color);
            font-weight: 500;
            margin: 0 5px;
            border-radius: 8px;
        }

        .pagination .page-link:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .no-products {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 16px;
            margin: 40px 0;
        }

        .no-products img {
            max-width: 300px;
            margin-bottom: 30px;
        }

        .no-products h3 {
            color: var(--dark-color);
            margin-bottom: 15px;
        }

        .no-products p {
            color: var(--secondary-color);
            font-size: 1.1rem;
        }

        .active-filters {
            margin-top: 20px;
        }

        .filter-tag {
            display: inline-block;
            background: var(--primary-color);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            margin-right: 10px;
            margin-bottom: 10px;
        }

        .filter-tag .remove {
            margin-left: 8px;
            cursor: pointer;
            opacity: 0.8;
        }

        .filter-tag .remove:hover {
            opacity: 1;
        }

        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 15px;
            }
            
            .filters-section {
                margin: 10px 0;
                padding: 20px;
            }
            
            .product-info {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
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
                        <a class="nav-link active" href="products.php">Products</a>
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

    <!-- Main Content -->
    <div class="container">
        <!-- Filters Section -->
        <div class="filters-section">
            <h5 class="mb-3">Filter Products</h5>
            <form method="GET" action="products.php" class="filter-form">
                <div class="row g-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['slug']; ?>" <?php echo $category === $cat['slug'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="vendor">
                            <option value="">All Vendors</option>
                            <?php foreach ($vendors as $v): ?>
                                <option value="<?php echo $v['id']; ?>" <?php echo $vendor == $v['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($v['store_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <input type="number" class="form-control" name="min_price" placeholder="Min $" value="<?php echo $minPrice > 0 ? $minPrice : ''; ?>" min="0" step="0.01">
                    </div>
                    <div class="col-md-1">
                        <input type="number" class="form-control" name="max_price" placeholder="Max $" value="<?php echo $maxPrice > 0 ? $maxPrice : ''; ?>" min="0" step="0.01">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="sort">
                            <option value="newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo $sortBy === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="price_low" <?php echo $sortBy === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sortBy === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="name_asc" <?php echo $sortBy === 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                            <option value="name_desc" <?php echo $sortBy === 'name_desc' ? 'selected' : ''; ?>>Name: Z to A</option>
                            <option value="rating" <?php echo $sortBy === 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </div>
            </form>
            
            <!-- Active Filters -->
            <?php if (!empty($search) || !empty($category) || !empty($vendor) || $minPrice > 0 || $maxPrice > 0): ?>
            <div class="active-filters">
                <strong>Active Filters:</strong>
                <?php if (!empty($search)): ?>
                    <span class="filter-tag">
                        Search: "<?php echo htmlspecialchars($search); ?>"
                        <span class="remove" onclick="removeFilter('search')">×</span>
                    </span>
                <?php endif; ?>
                
                <?php if (!empty($category)): ?>
                    <?php
                    $catName = '';
                    foreach ($categories as $cat) {
                        if ($cat['slug'] === $category) {
                            $catName = $cat['name'];
                            break;
                        }
                    }
                    ?>
                    <span class="filter-tag">
                        Category: <?php echo htmlspecialchars($catName); ?>
                        <span class="remove" onclick="removeFilter('category')">×</span>
                    </span>
                <?php endif; ?>
                
                <?php if (!empty($vendor)): ?>
                    <?php
                    $vendorName = '';
                    foreach ($vendors as $v) {
                        if ($v['id'] == $vendor) {
                            $vendorName = $v['store_name'];
                            break;
                        }
                    }
                    ?>
                    <span class="filter-tag">
                        Vendor: <?php echo htmlspecialchars($vendorName); ?>
                        <span class="remove" onclick="removeFilter('vendor')">×</span>
                    </span>
                <?php endif; ?>
                
                <?php if ($minPrice > 0): ?>
                    <span class="filter-tag">
                        Min Price: <?php echo formatPrice($minPrice); ?>
                        <span class="remove" onclick="removeFilter('min_price')">×</span>
                    </span>
                <?php endif; ?>
                
                <?php if ($maxPrice > 0): ?>
                    <span class="filter-tag">
                        Max Price: <?php echo formatPrice($maxPrice); ?>
                        <span class="remove" onclick="removeFilter('max_price')">×</span>
                    </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Products Header -->
        <div class="products-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2 class="mb-2">
                        <?php if (!empty($search)): ?>
                            Search Results for "<?php echo htmlspecialchars($search); ?>"
                        <?php else: ?>
                            All Products
                        <?php endif; ?>
                    </h2>
                    <p class="text-muted mb-0">
                        Showing <?php echo count($products); ?> of <?php echo $totalProducts; ?> products
                        <?php if ($page > 1): ?>
                            (Page <?php echo $page; ?> of <?php echo $totalPages; ?>)
                        <?php endif; ?>
                    </p>
                </div>
                <div>
                    <?php if (!empty($search) || !empty($category) || !empty($vendor) || $minPrice > 0 || $maxPrice > 0): ?>
                        <a href="products.php" class="btn btn-outline-primary">Clear All Filters</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <?php if (empty($products)): ?>
            <div class="no-products">
                <img src="https://placehold.co/400x300?text=No+products+found+illustration" alt="No products found">
                <h3>No Products Found</h3>
                <p>We couldn't find any products matching your criteria. Try adjusting your filters or search terms.</p>
                <a href="products.php" class="btn btn-primary">Browse All Products</a>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <img src="<?php echo $product['image_path'] ? 'uploads/' . $product['image_path'] : 'https://placehold.co/280x250?text=' . urlencode($product['name']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                        
                        <div class="product-info">
                            <div class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></div>
                            
                            <h3 class="product-title">
                                <a href="product.php?slug=<?php echo $product['slug']; ?>">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </a>
                            </h3>
                            
                            <div class="product-vendor">by <?php echo htmlspecialchars($product['store_name']); ?></div>
                            
                            <?php if ($product['rating'] > 0): ?>
                                <div class="product-rating">
                                    <?php echo getStarRating($product['rating']); ?>
                                    <small class="text-muted">(<?php echo $product['total_reviews']; ?>)</small>
                                </div>
                            <?php endif; ?>
                            
                            <div class="product-price"><?php echo formatPrice($product['price']); ?></div>
                            
                            <div class="product-actions">
                                <button class="btn btn-primary btn-sm flex-grow-1" onclick="addToCart(<?php echo $product['id']; ?>)">
                                    Add to Cart
                                </button>
                                <button class="btn btn-outline-primary btn-sm" onclick="addToWishlist(<?php echo $product['id']; ?>)" title="Add to Wishlist">
                                    ♡
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <?php
                $paginationParams = array_filter(array(
                    'search' => $search,
                    'category' => $category,
                    'vendor' => $vendor,
                    'min_price' => $minPrice > 0 ? $minPrice : null,
                    'max_price' => $maxPrice > 0 ? $maxPrice : null,
                    'sort' => $sortBy
                ));
                echo getPagination($page, $totalPages, 'products.php', $paginationParams);
                ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Add to cart function
        function addToCart(productId) {
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
        
        // Add to wishlist function
        function addToWishlist(productId) {
            <?php if (!$auth->isLoggedIn()): ?>
            alert('Please login to add items to wishlist');
            window.location.href = 'login.php';
            return;
            <?php endif; ?>
            
            // TODO: Implement wishlist API
            alert('Wishlist feature coming soon!');
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
        
        // Remove filter function
        function removeFilter(filterName) {
            const url = new URL(window.location);
            url.searchParams.delete(filterName);
            window.location.href = url.toString();
        }
        
        // Load cart count on page load
        <?php if ($auth->isLoggedIn()): ?>
        document.addEventListener('DOMContentLoaded', updateCartCount);
        <?php endif; ?>
    </script>
</body>
</html>