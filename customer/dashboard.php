<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require customer login
$auth->requireLogin();
if (!$auth->hasRole('customer')) {
    redirect('../index.php?error=access_denied');
}

$user = $auth->getCurrentUser();

// Get recent orders
try {
    $ordersQuery = "SELECT o.*, COUNT(oi.id) as item_count 
                    FROM orders o 
                    LEFT JOIN order_items oi ON o.id = oi.order_id 
                    WHERE o.user_id = :user_id 
                    GROUP BY o.id 
                    ORDER BY o.created_at DESC 
                    LIMIT 5";
    $ordersStmt = $db->prepare($ordersQuery);
    $ordersStmt->bindParam(':user_id', $_SESSION['user_id']);
    $ordersStmt->execute();
    $recentOrders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $recentOrders = array();
}

// Get order statistics
try {
    $statsQuery = "SELECT 
                       COUNT(*) as total_orders,
                       COALESCE(SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END), 0) as delivered_orders,
                       COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_orders,
                       COALESCE(SUM(total_amount), 0) as total_spent
                   FROM orders 
                   WHERE user_id = :user_id";
    $statsStmt = $db->prepare($statsQuery);
    $statsStmt->bindParam(':user_id', $_SESSION['user_id']);
    $statsStmt->execute();
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $stats = array('total_orders' => 0, 'delivered_orders' => 0, 'pending_orders' => 0, 'total_spent' => 0);
}

// Get wishlist count
try {
    $wishlistQuery = "SELECT COUNT(*) as count FROM wishlist WHERE user_id = :user_id";
    $wishlistStmt = $db->prepare($wishlistQuery);
    $wishlistStmt->bindParam(':user_id', $_SESSION['user_id']);
    $wishlistStmt->execute();
    $wishlistCount = $wishlistStmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch(PDOException $e) {
    $wishlistCount = 0;
}

$pageTitle = "Customer Dashboard - " . SITE_NAME;
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
            line-height: 1.6;
        }

        .navbar {
            background: white !important;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        .main-content {
            margin-top: 20px;
            margin-bottom: 40px;
        }

        .welcome-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, #3b82f6 100%);
            color: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
        }

        .welcome-card h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .welcome-card p {
            font-size: 1.125rem;
            opacity: 0.9;
            margin: 0;
        }

        .stats-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            height: 100%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            display: block;
        }

        .stats-label {
            color: var(--secondary-color);
            font-weight: 500;
        }

        .stats-primary { color: var(--primary-color); }
        .stats-success { color: var(--success-color); }
        .stats-warning { color: var(--accent-color); }
        .stats-info { color: #06b6d4; }

        .section-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .order-item {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .order-item:hover {
            border-color: var(--primary-color);
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .order-number {
            font-weight: 600;
            color: var(--dark-color);
        }

        .order-date {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }

        .order-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-pending { background: #fef3c7; color: #92400e; }
        .status-processing { background: #dbeafe; color: #1e40af; }
        .status-shipped { background: #d1fae5; color: #065f46; }
        .status-delivered { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fecaca; color: #991b1b; }

        .order-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-amount {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--primary-color);
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
            transform: translateY(-2px);
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
            font-weight: 500;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .action-btn {
            display: block;
            padding: 20px;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            text-decoration: none;
            color: var(--dark-color);
            text-align: center;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .action-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.15);
        }

        .sidebar {
            background: white;
            border-radius: 16px;
            padding: 25px;
            height: fit-content;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }

        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav li {
            margin-bottom: 10px;
        }

        .sidebar-nav a {
            display: block;
            padding: 12px 15px;
            color: var(--secondary-color);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: var(--primary-color);
            color: white;
        }

        @media (max-width: 768px) {
            .welcome-card {
                padding: 30px 20px;
                text-align: center;
            }
            
            .welcome-card h1 {
                font-size: 2rem;
            }
            
            .order-header,
            .order-details {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="../index.php"><?php echo SITE_NAME; ?></a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../cart.php">Cart</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <?php echo htmlspecialchars($user['first_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
                            <li><a class="dropdown-item" href="orders.php">My Orders</a></li>
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container main-content">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="sidebar">
                    <h5 class="mb-3">Customer Menu</h5>
                    <ul class="sidebar-nav">
                        <li><a href="dashboard.php" class="active">Dashboard</a></li>
                        <li><a href="orders.php">My Orders</a></li>
                        <li><a href="wishlist.php">Wishlist (<?php echo $wishlistCount; ?>)</a></li>
                        <li><a href="addresses.php">Addresses</a></li>
                        <li><a href="profile.php">Profile Settings</a></li>
                        <li><a href="reviews.php">My Reviews</a></li>
                        <li><a href="../logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>

            <!-- Main Dashboard -->
            <div class="col-lg-9">
                <!-- Welcome Card -->
                <div class="welcome-card">
                    <h1>Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>! 👋</h1>
                    <p>Here's an overview of your account activity and recent orders.</p>
                </div>

                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3 col-sm-6">
                        <div class="stats-card">
                            <span class="stats-number stats-primary"><?php echo $stats['total_orders']; ?></span>
                            <div class="stats-label">Total Orders</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stats-card">
                            <span class="stats-number stats-success"><?php echo $stats['delivered_orders']; ?></span>
                            <div class="stats-label">Delivered</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stats-card">
                            <span class="stats-number stats-warning"><?php echo $stats['pending_orders']; ?></span>
                            <div class="stats-label">Pending</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stats-card">
                            <span class="stats-number stats-info"><?php echo formatPrice($stats['total_spent']); ?></span>
                            <div class="stats-label">Total Spent</div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="section-card">
                    <div class="section-title">
                        Recent Orders
                        <a href="orders.php" class="btn btn-outline-primary btn-sm">View All</a>
                    </div>
                    
                    <?php if (empty($recentOrders)): ?>
                        <div class="text-center py-5">
                            <img src="https://placehold.co/200x150?text=No+orders+yet" alt="No orders" class="mb-3">
                            <h5>No Orders Yet</h5>
                            <p class="text-muted">You haven't placed any orders yet. Start shopping to see your orders here!</p>
                            <a href="../products.php" class="btn btn-primary">Start Shopping</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $order): ?>
                        <div class="order-item">
                            <div class="order-header">
                                <div>
                                    <div class="order-number">Order #<?php echo htmlspecialchars($order['order_number']); ?></div>
                                    <div class="order-date"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></div>
                                </div>
                                <span class="order-status status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                </span>
                            </div>
                            <div class="order-details">
                                <div>
                                    <span class="text-muted"><?php echo $order['item_count']; ?> item(s)</span>
                                </div>
                                <div>
                                    <span class="order-amount"><?php echo formatPrice($order['total_amount']); ?></span>
                                    <a href="orders.php?order=<?php echo $order['id']; ?>" class="btn btn-outline-primary btn-sm ms-2">View Details</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <a href="../products.php" class="action-btn">
                        🛍️ Continue Shopping
                    </a>
                    <a href="orders.php" class="action-btn">
                        📦 Track Orders
                    </a>
                    <a href="wishlist.php" class="action-btn">
                        ❤️ View Wishlist
                    </a>
                    <a href="profile.php" class="action-btn">
                        ⚙️ Account Settings
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>