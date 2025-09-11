<?php
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $firstName = sanitizeInput($_POST['first_name']);
    $lastName = sanitizeInput($_POST['last_name']);
    $phone = sanitizeInput($_POST['phone']);
    $storeName = sanitizeInput($_POST['store_name']);
    $storeDescription = sanitizeInput($_POST['store_description']);
    $businessLicense = sanitizeInput($_POST['business_license']);
    $taxId = sanitizeInput($_POST['tax_id']);
    
    // Validation
    $errors = array();
    
    if (empty($username)) $errors[] = "Username is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($password)) $errors[] = "Password is required";
    if (empty($confirmPassword)) $errors[] = "Please confirm your password";
    if (empty($firstName)) $errors[] = "First name is required";
    if (empty($lastName)) $errors[] = "Last name is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($storeName)) $errors[] = "Store name is required";
    if (empty($storeDescription)) $errors[] = "Store description is required";
    
    if (!validateEmail($email)) {
        $errors[] = "Please enter a valid email address";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    }
    
    if (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters long";
    }
    
    if (strlen($storeName) < 3) {
        $errors[] = "Store name must be at least 3 characters long";
    }
    
    // Check if email or username already exists
    if (empty($errors)) {
        if ($auth->emailExists($email)) {
            $errors[] = "Email address is already registered";
        }
        
        if ($auth->usernameExists($username)) {
            $errors[] = "Username is already taken";
        }
        
        // Check if store name already exists
        try {
            $storeQuery = "SELECT id FROM vendors WHERE store_name = :store_name";
            $storeStmt = $db->prepare($storeQuery);
            $storeStmt->bindParam(':store_name', $storeName);
            $storeStmt->execute();
            
            if ($storeStmt->rowCount() > 0) {
                $errors[] = "Store name is already taken";
            }
        } catch(PDOException $e) {
            $errors[] = "Database error occurred";
        }
    }
    
    // Register vendor if no errors
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Create user account
            $userData = array(
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'role' => 'vendor'
            );
            
            $userId = $auth->register($userData);
            
            if ($userId) {
                // Create vendor record
                $vendorQuery = "INSERT INTO vendors (user_id, store_name, store_description, business_license, tax_id, status, commission_rate) 
                                VALUES (:user_id, :store_name, :store_description, :business_license, :tax_id, 'pending', :commission_rate)";
                
                $vendorStmt = $db->prepare($vendorQuery);
                $vendorStmt->bindParam(':user_id', $userId);
                $vendorStmt->bindParam(':store_name', $storeName);
                $vendorStmt->bindParam(':store_description', $storeDescription);
                $vendorStmt->bindParam(':business_license', $businessLicense);
                $vendorStmt->bindParam(':tax_id', $taxId);
                $vendorStmt->bindParam(':commission_rate', DEFAULT_COMMISSION_RATE);
                
                if ($vendorStmt->execute()) {
                    $db->commit();
                    
                    // Send notification email to admin
                    sendEmail(SITE_EMAIL, "New Vendor Application", 
                        "A new vendor has applied: $firstName $lastName ($email) - Store: $storeName");
                    
                    setMessage("Thank you for applying to become a vendor! Your application is under review. We'll contact you within 24-48 hours.", "success");
                    redirect('vendor-application-success.php');
                } else {
                    $db->rollBack();
                    $errors[] = "Failed to create vendor profile. Please try again.";
                }
            } else {
                $db->rollBack();
                $errors[] = "Registration failed. Please try again.";
            }
        } catch(PDOException $e) {
            $db->rollBack();
            $errors[] = "An error occurred during registration. Please try again.";
            error_log("Vendor registration error: " . $e->getMessage());
        }
    }
}

$pageTitle = "Become a Vendor - " . SITE_NAME;
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
            background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%);
            min-height: 100vh;
            padding: 40px 0;
        }

        .vendor-container {
            max-width: 800px;
            width: 100%;
            margin: 0 auto;
        }

        .vendor-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .vendor-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .vendor-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 15px;
        }

        .vendor-header p {
            color: var(--secondary-color);
            font-size: 1.125rem;
            margin: 0;
        }

        .benefits-section {
            background: var(--light-color);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 40px;
        }

        .benefits-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 20px;
            text-align: center;
        }

        .benefits-list {
            list-style: none;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .benefit-item {
            display: flex;
            align-items: center;
            color: var(--secondary-color);
            font-weight: 500;
        }

        .benefit-item::before {
            content: '✓';
            background: var(--success-color);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 12px;
            font-size: 14px;
        }

        .form-section {
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            transition: border-color 0.3s ease;
        }

        .form-section:focus-within {
            border-color: var(--primary-color);
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .section-title::before {
            content: '';
            width: 4px;
            height: 24px;
            background: var(--success-color);
            border-radius: 2px;
            margin-right: 12px;
        }

        .form-floating {
            margin-bottom: 20px;
        }

        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.15);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .btn-primary {
            background-color: var(--success-color);
            border-color: var(--success-color);
            padding: 15px 40px;
            font-weight: 600;
            border-radius: 12px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #059669;
            border-color: #059669;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px;
            margin-bottom: 25px;
        }

        .alert-danger {
            background-color: #fef2f2;
            color: #dc2626;
        }

        .form-check {
            margin: 20px 0;
        }

        .form-check-input:checked {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .footer-links {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .footer-links a {
            color: var(--success-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #059669;
        }

        .back-home {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
        }

        .back-home a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            opacity: 0.9;
            transition: opacity 0.3s ease;
        }

        .back-home a:hover {
            opacity: 1;
        }

        .info-box {
            background: #fffbeb;
            border: 1px solid #fbbf24;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .info-box h6 {
            color: #92400e;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .info-box p {
            color: #92400e;
            margin: 0;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .vendor-card {
                margin: 20px;
                padding: 30px 25px;
            }
            
            .vendor-header h1 {
                font-size: 2rem;
            }
            
            body {
                padding: 20px 0;
            }
            
            .benefits-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Back to Home -->
    <div class="back-home">
        <a href="index.php">← Back to Home</a>
    </div>

    <div class="container">
        <div class="vendor-container">
            <div class="vendor-card">
                <div class="vendor-header">
                    <h1>Become a Vendor</h1>
                    <p>Join our marketplace and start selling to thousands of customers</p>
                </div>

                <!-- Benefits Section -->
                <div class="benefits-section">
                    <h3 class="benefits-title">Why Choose Our Platform?</h3>
                    <ul class="benefits-list">
                        <li class="benefit-item">Reach thousands of customers</li>
                        <li class="benefit-item">Easy-to-use vendor dashboard</li>
                        <li class="benefit-item">Secure payment processing</li>
                        <li class="benefit-item">Marketing and promotional tools</li>
                        <li class="benefit-item">24/7 customer support</li>
                        <li class="benefit-item">Detailed analytics and reports</li>
                        <li class="benefit-item">Mobile-friendly interface</li>
                        <li class="benefit-item">Low commission rates</li>
                    </ul>
                </div>

                <!-- Info Box -->
                <div class="info-box">
                    <h6>📋 Application Review Process</h6>
                    <p>All vendor applications are reviewed within 24-48 hours. You'll receive an email notification once your application has been processed. Please ensure all information is accurate and complete.</p>
                </div>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" action="" id="vendorForm">
                    <!-- Personal Information -->
                    <div class="form-section">
                        <h4 class="section-title">Personal Information</h4>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           placeholder="First Name" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>
                                    <label for="first_name">First Name *</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           placeholder="Last Name" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>
                                    <label for="last_name">Last Name *</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="username" name="username" 
                                           placeholder="Username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                                    <label for="username">Username *</label>
                                    <div class="form-text">Must be at least 3 characters long</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           placeholder="Phone Number" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
                                    <label for="phone">Phone Number *</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-floating">
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="name@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                            <label for="email">Email Address *</label>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Password" required>
                                    <label for="password">Password *</label>
                                    <div class="form-text">Must be at least 6 characters long</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           placeholder="Confirm Password" required>
                                    <label for="confirm_password">Confirm Password *</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Store Information -->
                    <div class="form-section">
                        <h4 class="section-title">Store Information</h4>
                        
                        <div class="form-floating">
                            <input type="text" class="form-control" id="store_name" name="store_name" 
                                   placeholder="Store Name" value="<?php echo isset($_POST['store_name']) ? htmlspecialchars($_POST['store_name']) : ''; ?>" required>
                            <label for="store_name">Store Name *</label>
                            <div class="form-text">This will be displayed to customers</div>
                        </div>

                        <div class="form-floating">
                            <textarea class="form-control" id="store_description" name="store_description" 
                                      placeholder="Store Description" required><?php echo isset($_POST['store_description']) ? htmlspecialchars($_POST['store_description']) : ''; ?></textarea>
                            <label for="store_description">Store Description *</label>
                            <div class="form-text">Describe your store and the products you plan to sell</div>
                        </div>
                    </div>

                    <!-- Business Information -->
                    <div class="form-section">
                        <h4 class="section-title">Business Information</h4>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="business_license" name="business_license" 
                                           placeholder="Business License" value="<?php echo isset($_POST['business_license']) ? htmlspecialchars($_POST['business_license']) : ''; ?>">
                                    <label for="business_license">Business License Number</label>
                                    <div class="form-text">Optional but recommended</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="tax_id" name="tax_id" 
                                           placeholder="Tax ID" value="<?php echo isset($_POST['tax_id']) ? htmlspecialchars($_POST['tax_id']) : ''; ?>">
                                    <label for="tax_id">Tax ID / EIN</label>
                                    <div class="form-text">Optional but recommended</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="vendor-terms.php" target="_blank">Vendor Terms of Service</a>, 
                            <a href="terms.php" target="_blank">Terms of Service</a>, and 
                            <a href="privacy.php" target="_blank">Privacy Policy</a>
                        </label>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="commission" name="commission" required>
                        <label class="form-check-label" for="commission">
                            I understand and agree to the <?php echo DEFAULT_COMMISSION_RATE; ?>% commission fee on all sales
                        </label>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter">
                        <label class="form-check-label" for="newsletter">
                            Subscribe to vendor newsletters and updates
                        </label>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">Submit Vendor Application</button>
                    </div>
                </form>

                <div class="footer-links">
                    <p>
                        Already a vendor? 
                        <a href="login.php">Sign in to your account</a>
                    </p>
                    <p class="mt-2">
                        <small class="text-muted">
                            Looking to shop instead? 
                            <a href="register.php" class="text-decoration-none">Create a customer account</a>
                        </small>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('vendorForm');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            // Real-time password validation
            confirmPasswordInput.addEventListener('input', function() {
                const password = passwordInput.value;
                const confirmPassword = this.value;
                
                if (confirmPassword && password !== confirmPassword) {
                    this.setCustomValidity('Passwords do not match');
                } else {
                    this.setCustomValidity('');
                }
            });
            
            // Form validation
            form.addEventListener('submit', function(e) {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                const terms = document.getElementById('terms').checked;
                const commission = document.getElementById('commission').checked;
                
                if (password.length < 6) {
                    e.preventDefault();
                    alert('Password must be at least 6 characters long');
                    return;
                }
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match');
                    return;
                }
                
                if (!terms) {
                    e.preventDefault();
                    alert('Please accept the Terms of Service');
                    return;
                }
                
                if (!commission) {
                    e.preventDefault();
                    alert('Please acknowledge the commission agreement');
                    return;
                }
                
                // Show loading state
                const submitBtn = form.querySelector('button[type="submit"]');
                submitBtn.textContent = 'Submitting Application...';
                submitBtn.disabled = true;
            });
            
            // Auto-generate store slug preview
            const storeNameInput = document.getElementById('store_name');
            storeNameInput.addEventListener('input', function() {
                // You could add store URL preview here
            });
        });
    </script>
</body>
</html>