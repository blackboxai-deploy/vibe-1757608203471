<?php
require_once 'config/database.php';
require_once 'config/config.php';

// Utility Functions for the Marketplace

// Sanitize input data
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Generate random string
function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

// Generate unique order number
function generateOrderNumber() {
    return 'ORD-' . date('Y') . '-' . strtoupper(generateRandomString(8));
}

// Format price
function formatPrice($price) {
    return '$' . number_format($price, 2);
}

// Calculate commission
function calculateCommission($amount, $rate) {
    return ($amount * $rate) / 100;
}

// Generate SEO-friendly slug
function generateSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    
    if (empty($text)) {
        return 'n-a';
    }
    
    return $text;
}

// Upload file function
function uploadFile($file, $uploadDir = UPLOAD_PATH, $allowedTypes = ALLOWED_EXTENSIONS) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return array('success' => false, 'error' => 'No file uploaded or upload error');
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return array('success' => false, 'error' => 'File size too large');
    }
    
    // Check file type
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension']);
    
    if (!in_array($extension, $allowedTypes)) {
        return array('success' => false, 'error' => 'File type not allowed');
    }
    
    // Create upload directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $filename = time() . '_' . generateRandomString(8) . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return array('success' => true, 'filename' => $filename, 'filepath' => $filepath);
    }
    
    return array('success' => false, 'error' => 'Failed to move uploaded file');
}

// Get product rating stars HTML
function getStarRating($rating, $maxStars = 5) {
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
    $emptyStars = $maxStars - $fullStars - $halfStar;
    
    $html = '<div class="star-rating">';
    
    // Full stars
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<span class="star full">★</span>';
    }
    
    // Half star
    if ($halfStar) {
        $html .= '<span class="star half">☆</span>';
    }
    
    // Empty stars
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<span class="star empty">☆</span>';
    }
    
    $html .= '</div>';
    
    return $html;
}

// Pagination function
function getPagination($currentPage, $totalPages, $url, $params = array()) {
    if ($totalPages <= 1) {
        return '';
    }
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($currentPage > 1) {
        $prevParams = array_merge($params, array('page' => $currentPage - 1));
        $prevUrl = $url . '?' . http_build_query($prevParams);
        $html .= '<li class="page-item"><a class="page-link" href="' . $prevUrl . '">Previous</a></li>';
    }
    
    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    if ($start > 1) {
        $firstParams = array_merge($params, array('page' => 1));
        $firstUrl = $url . '?' . http_build_query($firstParams);
        $html .= '<li class="page-item"><a class="page-link" href="' . $firstUrl . '">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $pageParams = array_merge($params, array('page' => $i));
        $pageUrl = $url . '?' . http_build_query($pageParams);
        $active = ($i == $currentPage) ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $pageUrl . '">' . $i . '</a></li>';
    }
    
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $lastParams = array_merge($params, array('page' => $totalPages));
        $lastUrl = $url . '?' . http_build_query($lastParams);
        $html .= '<li class="page-item"><a class="page-link" href="' . $lastUrl . '">' . $totalPages . '</a></li>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $nextParams = array_merge($params, array('page' => $currentPage + 1));
        $nextUrl = $url . '?' . http_build_query($nextParams);
        $html .= '<li class="page-item"><a class="page-link" href="' . $nextUrl . '">Next</a></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

// Send email notification (placeholder function)
function sendEmail($to, $subject, $message) {
    // In a real application, implement proper email sending
    // For now, just log the email
    error_log("Email to: $to, Subject: $subject, Message: $message");
    return true;
}

// Create notification
function createNotification($db, $userId, $type, $title, $message, $actionUrl = null) {
    try {
        $query = "INSERT INTO notifications (user_id, type, title, message, action_url) 
                  VALUES (:user_id, :type, :title, :message, :action_url)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':action_url', $actionUrl);
        
        return $stmt->execute();
    } catch(PDOException $e) {
        error_log("Notification creation error: " . $e->getMessage());
        return false;
    }
}

// Get unread notifications count
function getUnreadNotificationsCount($db, $userId) {
    try {
        $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = 0";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    } catch(PDOException $e) {
        return 0;
    }
}

// Validate form data
function validateRequired($fields, $data) {
    $errors = array();
    
    foreach ($fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    
    return $errors;
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Time ago function
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31104000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31104000) . ' years ago';
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// Success and error message functions
function setMessage($message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'] ?? 'success';
        
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        
        return array('message' => $message, 'type' => $type);
    }
    
    return false;
}
?>