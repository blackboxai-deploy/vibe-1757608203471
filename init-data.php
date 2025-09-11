<?php
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';

echo "<h2>Initializing Sample Data...</h2>";

try {
    // Create sample vendor users first
    $vendorUsers = array(
        array(
            'username' => 'techstore',
            'email' => 'tech@example.com', 
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'first_name' => 'John',
            'last_name' => 'Tech',
            'phone' => '555-0101',
            'role' => 'vendor'
        ),
        array(
            'username' => 'fashionhub',
            'email' => 'fashion@example.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'first_name' => 'Sarah',
            'last_name' => 'Style',
            'phone' => '555-0102',
            'role' => 'vendor'
        ),
        array(
            'username' => 'homegoods',
            'email' => 'home@example.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'first_name' => 'Mike',
            'last_name' => 'Home',
            'phone' => '555-0103',
            'role' => 'vendor'
        )
    );

    $vendorIds = array();
    foreach ($vendorUsers as $user) {
        $userQuery = "INSERT INTO users (username, email, password, first_name, last_name, phone, role, status, email_verified) 
                      VALUES (:username, :email, :password, :first_name, :last_name, :phone, :role, 'active', 1)";
        $userStmt = $db->prepare($userQuery);
        $userStmt->execute($user);
        $vendorIds[] = $db->lastInsertId();
    }

    echo "✓ Created vendor users<br>";

    // Create vendor profiles
    $vendors = array(
        array(
            'user_id' => $vendorIds[0],
            'store_name' => 'TechWorld Electronics',
            'store_description' => 'Your one-stop shop for the latest electronics, gadgets, and tech accessories. We offer competitive prices and excellent customer service.',
            'status' => 'approved',
            'commission_rate' => 8.00
        ),
        array(
            'user_id' => $vendorIds[1], 
            'store_name' => 'Fashion Hub',
            'store_description' => 'Trendy fashion for men and women. Discover the latest styles, seasonal collections, and timeless classics.',
            'status' => 'approved',
            'commission_rate' => 12.00
        ),
        array(
            'user_id' => $vendorIds[2],
            'store_name' => 'Home & Garden Paradise',
            'store_description' => 'Beautiful home decor, furniture, and garden supplies to make your space perfect. Quality products for every room and outdoor area.',
            'status' => 'approved', 
            'commission_rate' => 10.00
        )
    );

    $dbVendorIds = array();
    foreach ($vendors as $vendor) {
        $vendorQuery = "INSERT INTO vendors (user_id, store_name, store_description, status, commission_rate) 
                        VALUES (:user_id, :store_name, :store_description, :status, :commission_rate)";
        $vendorStmt = $db->prepare($vendorQuery);
        $vendorStmt->execute($vendor);
        $dbVendorIds[] = $db->lastInsertId();
    }

    echo "✓ Created vendor profiles<br>";

    // Create sample customer users
    $customerUsers = array(
        array(
            'username' => 'customer1',
            'email' => 'customer1@example.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'first_name' => 'Alice',
            'last_name' => 'Johnson',
            'phone' => '555-0201',
            'role' => 'customer'
        ),
        array(
            'username' => 'customer2', 
            'email' => 'customer2@example.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'first_name' => 'Bob',
            'last_name' => 'Smith',
            'phone' => '555-0202',
            'role' => 'customer'
        )
    );

    foreach ($customerUsers as $user) {
        $userQuery = "INSERT INTO users (username, email, password, first_name, last_name, phone, role, status, email_verified) 
                      VALUES (:username, :email, :password, :first_name, :last_name, :phone, :role, 'active', 1)";
        $userStmt = $db->prepare($userQuery);
        $userStmt->execute($user);
    }

    echo "✓ Created customer users<br>";

    // Create sample products
    $products = array(
        // Electronics
        array(
            'vendor_id' => $dbVendorIds[0],
            'category_id' => 1, // Electronics
            'name' => 'Wireless Bluetooth Headphones',
            'slug' => 'wireless-bluetooth-headphones',
            'description' => 'Premium quality wireless Bluetooth headphones with noise cancellation, 30-hour battery life, and superior sound quality. Perfect for music lovers and professionals.',
            'short_description' => 'Premium wireless headphones with 30-hour battery and noise cancellation.',
            'price' => 89.99,
            'compare_price' => 129.99,
            'sku' => 'WBH-001',
            'stock_quantity' => 50,
            'featured' => 1,
            'status' => 'active'
        ),
        array(
            'vendor_id' => $dbVendorIds[0],
            'category_id' => 1,
            'name' => 'Smart Fitness Tracker Watch',
            'slug' => 'smart-fitness-tracker-watch',
            'description' => 'Advanced fitness tracker with heart rate monitoring, GPS tracking, sleep analysis, and smartphone notifications. Water-resistant design perfect for active lifestyles.',
            'short_description' => 'Smart fitness tracker with heart rate monitor and GPS.',
            'price' => 149.99,
            'compare_price' => 199.99,
            'sku' => 'SFT-002',
            'stock_quantity' => 35,
            'featured' => 1,
            'status' => 'active'
        ),
        array(
            'vendor_id' => $dbVendorIds[0],
            'category_id' => 1,
            'name' => 'Portable Power Bank 20000mAh',
            'slug' => 'portable-power-bank-20000mah',
            'description' => 'High-capacity portable charger with fast charging technology, multiple USB ports, and LED display. Compatible with smartphones, tablets, and other devices.',
            'short_description' => 'High-capacity 20000mAh power bank with fast charging.',
            'price' => 39.99,
            'compare_price' => 59.99,
            'sku' => 'PPB-003',
            'stock_quantity' => 75,
            'featured' => 0,
            'status' => 'active'
        ),
        // Fashion
        array(
            'vendor_id' => $dbVendorIds[1],
            'category_id' => 2, // Fashion
            'name' => 'Premium Cotton T-Shirt - Classic Fit',
            'slug' => 'premium-cotton-tshirt-classic-fit',
            'description' => 'Soft, comfortable premium cotton t-shirt with classic fit. Made from 100% organic cotton with reinforced seams and pre-shrunk fabric. Available in multiple colors.',
            'short_description' => '100% organic cotton t-shirt with classic comfortable fit.',
            'price' => 24.99,
            'compare_price' => 34.99,
            'sku' => 'PCT-004',
            'stock_quantity' => 100,
            'featured' => 1,
            'status' => 'active'
        ),
        array(
            'vendor_id' => $dbVendorIds[1],
            'category_id' => 2,
            'name' => 'Designer Denim Jacket - Vintage Style',
            'slug' => 'designer-denim-jacket-vintage-style',
            'description' => 'Stylish vintage-inspired denim jacket with premium quality fabric, classic button closure, and multiple pockets. Perfect for casual and semi-formal occasions.',
            'short_description' => 'Vintage-style designer denim jacket with premium fabric.',
            'price' => 79.99,
            'compare_price' => 119.99,
            'sku' => 'DDJ-005',
            'stock_quantity' => 25,
            'featured' => 1,
            'status' => 'active'
        ),
        array(
            'vendor_id' => $dbVendorIds[1],
            'category_id' => 2,
            'name' => 'Leather Crossbody Bag - Handcrafted',
            'slug' => 'leather-crossbody-bag-handcrafted',
            'description' => 'Handcrafted genuine leather crossbody bag with adjustable strap, multiple compartments, and elegant design. Perfect for everyday use or special occasions.',
            'short_description' => 'Handcrafted genuine leather crossbody bag with elegant design.',
            'price' => 129.99,
            'compare_price' => 179.99,
            'sku' => 'LCB-006',
            'stock_quantity' => 18,
            'featured' => 0,
            'status' => 'active'
        ),
        // Home & Garden
        array(
            'vendor_id' => $dbVendorIds[2],
            'category_id' => 3, // Home & Garden
            'name' => 'Modern Coffee Table - Solid Wood',
            'slug' => 'modern-coffee-table-solid-wood',
            'description' => 'Beautiful modern coffee table made from solid oak wood with natural finish. Features clean lines, sturdy construction, and spacious tabletop perfect for any living room.',
            'short_description' => 'Solid oak wood modern coffee table with natural finish.',
            'price' => 299.99,
            'compare_price' => 399.99,
            'sku' => 'MCT-007',
            'stock_quantity' => 12,
            'featured' => 1,
            'status' => 'active'
        ),
        array(
            'vendor_id' => $dbVendorIds[2],
            'category_id' => 3,
            'name' => 'Decorative Throw Pillow Set',
            'slug' => 'decorative-throw-pillow-set',
            'description' => 'Set of 4 decorative throw pillows with premium fabric covers and comfortable filling. Various patterns and colors available to complement any home decor style.',
            'short_description' => 'Set of 4 decorative throw pillows with premium fabric covers.',
            'price' => 49.99,
            'compare_price' => 69.99,
            'sku' => 'DTP-008',
            'stock_quantity' => 40,
            'featured' => 0,
            'status' => 'active'
        ),
        array(
            'vendor_id' => $dbVendorIds[2],
            'category_id' => 3,
            'name' => 'Indoor Plant Collection - Set of 3',
            'slug' => 'indoor-plant-collection-set-of-3',
            'description' => 'Curated collection of 3 low-maintenance indoor plants perfect for beginners. Includes decorative pots, care instructions, and plant food. Great for improving air quality.',
            'short_description' => 'Set of 3 low-maintenance indoor plants with decorative pots.',
            'price' => 69.99,
            'compare_price' => 89.99,
            'sku' => 'IPC-009',
            'stock_quantity' => 22,
            'featured' => 1,
            'status' => 'active'
        )
    );

    foreach ($products as $product) {
        $productQuery = "INSERT INTO products (vendor_id, category_id, name, slug, description, short_description, price, compare_price, sku, stock_quantity, featured, status) 
                         VALUES (:vendor_id, :category_id, :name, :slug, :description, :short_description, :price, :compare_price, :sku, :stock_quantity, :featured, :status)";
        $productStmt = $db->prepare($productQuery);
        $productStmt->execute($product);
        
        $productId = $db->lastInsertId();
        
        // Add a sample product image (placeholder)
        $imageQuery = "INSERT INTO product_images (product_id, image_path, alt_text, is_primary) 
                       VALUES (:product_id, :image_path, :alt_text, 1)";
        $imageStmt = $db->prepare($imageQuery);
        $imageStmt->execute(array(
            ':product_id' => $productId,
            ':image_path' => '', // Will use placeholder images
            ':alt_text' => $product['name']
        ));
        
        // Add some sample reviews for featured products
        if ($product['featured']) {
            $reviews = array(
                array(
                    'product_id' => $productId,
                    'user_id' => $vendorIds[0] + 3, // Customer users start after vendors
                    'rating' => 5,
                    'title' => 'Excellent Product!',
                    'review_text' => 'Really happy with this purchase. Great quality and fast shipping!',
                    'status' => 'approved'
                ),
                array(
                    'product_id' => $productId,
                    'user_id' => $vendorIds[0] + 4,
                    'rating' => 4,
                    'title' => 'Good value for money',
                    'review_text' => 'Nice product overall, would recommend to others.',
                    'status' => 'approved'
                )
            );
            
            foreach ($reviews as $review) {
                $reviewQuery = "INSERT INTO product_reviews (product_id, user_id, rating, title, review_text, status) 
                                VALUES (:product_id, :user_id, :rating, :title, :review_text, :status)";
                $reviewStmt = $db->prepare($reviewQuery);
                $reviewStmt->execute($review);
            }
            
            // Update product rating
            $avgQuery = "UPDATE products SET rating = (SELECT AVG(rating) FROM product_reviews WHERE product_id = :product_id AND status = 'approved'), 
                         total_reviews = (SELECT COUNT(*) FROM product_reviews WHERE product_id = :product_id AND status = 'approved') 
                         WHERE id = :product_id";
            $avgStmt = $db->prepare($avgQuery);
            $avgStmt->execute(array(':product_id' => $productId));
        }
    }

    echo "✓ Created sample products with reviews<br>";

    echo "<br><strong>Sample data initialization completed!</strong><br><br>";
    echo "<strong>Test Users Created:</strong><br>";
    echo "Admin: admin@marketplace.com / password<br>";
    echo "Vendor 1: tech@example.com / password (TechWorld Electronics)<br>";
    echo "Vendor 2: fashion@example.com / password (Fashion Hub)<br>";
    echo "Vendor 3: home@example.com / password (Home & Garden Paradise)<br>";
    echo "Customer 1: customer1@example.com / password<br>";
    echo "Customer 2: customer2@example.com / password<br>";
    echo "<br><a href='index.php' class='btn btn-primary'>Go to Homepage</a>";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>