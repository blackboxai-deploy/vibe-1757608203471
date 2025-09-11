<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in for cart operations
if (!$auth->isLoggedIn() && $_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login to manage your cart']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($method) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? '';
            
            switch ($action) {
                case 'add':
                    $productId = (int)$input['product_id'];
                    $quantity = (int)($input['quantity'] ?? 1);
                    $variantId = isset($input['variant_id']) ? (int)$input['variant_id'] : null;
                    
                    if ($productId <= 0 || $quantity <= 0) {
                        throw new Exception('Invalid product or quantity');
                    }
                    
                    // Check if product exists and is active
                    $productQuery = "SELECT id, name, price, stock_quantity FROM products WHERE id = :id AND status = 'active'";
                    $productStmt = $db->prepare($productQuery);
                    $productStmt->bindParam(':id', $productId);
                    $productStmt->execute();
                    
                    if (!$product = $productStmt->fetch(PDO::FETCH_ASSOC)) {
                        throw new Exception('Product not found or inactive');
                    }
                    
                    // Check stock availability
                    if ($product['stock_quantity'] < $quantity) {
                        throw new Exception('Insufficient stock available');
                    }
                    
                    // Check if item already exists in cart
                    $cartQuery = "SELECT id, quantity FROM cart WHERE user_id = :user_id AND product_id = :product_id";
                    if ($variantId) {
                        $cartQuery .= " AND variant_id = :variant_id";
                    } else {
                        $cartQuery .= " AND variant_id IS NULL";
                    }
                    
                    $cartStmt = $db->prepare($cartQuery);
                    $cartStmt->bindParam(':user_id', $_SESSION['user_id']);
                    $cartStmt->bindParam(':product_id', $productId);
                    if ($variantId) {
                        $cartStmt->bindParam(':variant_id', $variantId);
                    }
                    $cartStmt->execute();
                    
                    if ($existingItem = $cartStmt->fetch(PDO::FETCH_ASSOC)) {
                        // Update quantity
                        $newQuantity = $existingItem['quantity'] + $quantity;
                        if ($newQuantity > $product['stock_quantity']) {
                            throw new Exception('Cannot add more items than available in stock');
                        }
                        
                        $updateQuery = "UPDATE cart SET quantity = :quantity, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
                        $updateStmt = $db->prepare($updateQuery);
                        $updateStmt->bindParam(':quantity', $newQuantity);
                        $updateStmt->bindParam(':id', $existingItem['id']);
                        $updateStmt->execute();
                    } else {
                        // Add new item
                        $insertQuery = "INSERT INTO cart (user_id, product_id, variant_id, quantity) VALUES (:user_id, :product_id, :variant_id, :quantity)";
                        $insertStmt = $db->prepare($insertQuery);
                        $insertStmt->bindParam(':user_id', $_SESSION['user_id']);
                        $insertStmt->bindParam(':product_id', $productId);
                        $insertStmt->bindParam(':variant_id', $variantId);
                        $insertStmt->bindParam(':quantity', $quantity);
                        $insertStmt->execute();
                    }
                    
                    echo json_encode(['success' => true, 'message' => 'Item added to cart']);
                    break;
                    
                case 'update':
                    $cartId = (int)$input['cart_id'];
                    $quantity = (int)$input['quantity'];
                    
                    if ($quantity <= 0) {
                        // Remove item if quantity is 0 or negative
                        $deleteQuery = "DELETE FROM cart WHERE id = :id AND user_id = :user_id";
                        $deleteStmt = $db->prepare($deleteQuery);
                        $deleteStmt->bindParam(':id', $cartId);
                        $deleteStmt->bindParam(':user_id', $_SESSION['user_id']);
                        $deleteStmt->execute();
                        
                        echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
                    } else {
                        // Update quantity
                        $updateQuery = "UPDATE cart SET quantity = :quantity, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND user_id = :user_id";
                        $updateStmt = $db->prepare($updateQuery);
                        $updateStmt->bindParam(':quantity', $quantity);
                        $updateStmt->bindParam(':id', $cartId);
                        $updateStmt->bindParam(':user_id', $_SESSION['user_id']);
                        $updateStmt->execute();
                        
                        echo json_encode(['success' => true, 'message' => 'Cart updated']);
                    }
                    break;
                    
                case 'remove':
                    $cartId = (int)$input['cart_id'];
                    
                    $deleteQuery = "DELETE FROM cart WHERE id = :id AND user_id = :user_id";
                    $deleteStmt = $db->prepare($deleteQuery);
                    $deleteStmt->bindParam(':id', $cartId);
                    $deleteStmt->bindParam(':user_id', $_SESSION['user_id']);
                    $deleteStmt->execute();
                    
                    echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
                    break;
                    
                default:
                    throw new Exception('Invalid action');
            }
            break;
            
        case 'GET':
            switch ($action) {
                case 'count':
                    if (!$auth->isLoggedIn()) {
                        echo json_encode(['count' => 0]);
                        break;
                    }
                    
                    $countQuery = "SELECT SUM(quantity) as total FROM cart WHERE user_id = :user_id";
                    $countStmt = $db->prepare($countQuery);
                    $countStmt->bindParam(':user_id', $_SESSION['user_id']);
                    $countStmt->execute();
                    
                    $result = $countStmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode(['count' => (int)($result['total'] ?? 0)]);
                    break;
                    
                case 'items':
                    if (!$auth->isLoggedIn()) {
                        echo json_encode(['items' => [], 'total' => 0]);
                        break;
                    }
                    
                    $itemsQuery = "SELECT c.*, p.name, p.price, p.slug, pi.image_path, v.store_name,
                                          pv.variant_type, pv.variant_value, pv.price_modifier
                                   FROM cart c
                                   JOIN products p ON c.product_id = p.id
                                   LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                                   LEFT JOIN vendors v ON p.vendor_id = v.id
                                   LEFT JOIN product_variants pv ON c.variant_id = pv.id
                                   WHERE c.user_id = :user_id
                                   ORDER BY c.updated_at DESC";
                    
                    $itemsStmt = $db->prepare($itemsQuery);
                    $itemsStmt->bindParam(':user_id', $_SESSION['user_id']);
                    $itemsStmt->execute();
                    
                    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
                    $total = 0;
                    
                    foreach ($items as &$item) {
                        $itemPrice = $item['price'] + ($item['price_modifier'] ?? 0);
                        $item['unit_price'] = $itemPrice;
                        $item['total_price'] = $itemPrice * $item['quantity'];
                        $total += $item['total_price'];
                        
                        // Format image path
                        $item['image_url'] = $item['image_path'] ? 'uploads/' . $item['image_path'] : 'https://placehold.co/100x100?text=' . urlencode($item['name']);
                    }
                    
                    echo json_encode([
                        'items' => $items,
                        'total' => $total,
                        'formatted_total' => formatPrice($total)
                    ]);
                    break;
                    
                default:
                    throw new Exception('Invalid action');
            }
            break;
            
        default:
            throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>