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
    
    // Validation
    $errors = array();
    
    if (empty($username)) $errors[] = "Username is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($password)) $errors[] = "Password is required";
    if (empty($confirmPassword)) $errors[] = "Please confirm your password";
    if (empty($firstName)) $errors[] = "First name is required";
    if (empty($lastName)) $errors[] = "Last name is required";
    
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
    
    // Check if email or username already exists
    if (empty($errors)) {
        if ($auth->emailExists($email)) {
            $errors[] = "Email address is already registered";
        }
        
        if ($auth->usernameExists($username)) {
            $errors[] = "Username is already taken";
        }
    }
    
    // Register user if no errors
    if (empty($errors)) {
        $userData = array(
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'role' => 'customer'
        );
        
        $userId = $auth->register($userData);
        
        if ($userId) {
            // Auto-login after registration
            if ($auth->login($email, $password)) {
                setMessage("Welcome to " . SITE_NAME . "! Your account has been created successfully.", "success");
                redirect('customer/dashboard.php');
            } else {
                setMessage("Account created successfully! Please login.", "success");
                redirect('login.php');
            }
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}

$pageTitle = "Create Account - " . SITE_NAME;
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
            background: linear-gradient(135deg, var(--primary-color) 0%, #3b82f6 100%);
            min-height: 100vh;
            padding: 40px 0;
        }

        .register-container {
            max-width: 520px;
            width: 100%;
            margin: 0 auto;
        }

        .register-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .register-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 10px;
        }

        .register-header p {
            color: var(--secondary-color);
            margin: 0;
        }

        .form-floating {
            margin-bottom: 20px;
        }

        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.15);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 15px;
            font-weight: 600;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
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

        .password-requirements {
            background: #f8fafc;
            border-radius: 12px;
            padding: 15px;
            margin-top: 10px;
            font-size: 0.875rem;
        }

        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
        }

        .password-requirements li {
            color: var(--secondary-color);
            margin-bottom: 5px;
        }

        .password-requirements li.valid {
            color: var(--success-color);
        }

        .form-check {
            margin: 20px 0;
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .footer-links {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .footer-links a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #1d4ed8;
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

        .strength-meter {
            height: 4px;
            border-radius: 2px;
            background: #e2e8f0;
            margin-top: 8px;
            overflow: hidden;
        }

        .strength-meter-fill {
            height: 100%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .strength-weak { background: var(--danger-color); width: 25%; }
        .strength-fair { background: var(--accent-color); width: 50%; }
        .strength-good { background: var(--accent-color); width: 75%; }
        .strength-strong { background: var(--success-color); width: 100%; }

        @media (max-width: 576px) {
            .register-card {
                margin: 20px;
                padding: 30px 25px;
            }
            
            body {
                padding: 20px 0;
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
        <div class="register-container">
            <div class="register-card">
                <div class="register-header">
                    <h1>Create Account</h1>
                    <p>Join our marketplace and start shopping today</p>
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

                <form method="POST" action="" id="registerForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       placeholder="First Name" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>
                                <label for="first_name">First Name</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       placeholder="Last Name" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>
                                <label for="last_name">Last Name</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-floating">
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="Username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                        <label for="username">Username</label>
                        <div class="form-text">Must be at least 3 characters long</div>
                    </div>

                    <div class="form-floating">
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="name@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        <label for="email">Email Address</label>
                    </div>

                    <div class="form-floating">
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               placeholder="Phone Number" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        <label for="phone">Phone Number (Optional)</label>
                    </div>

                    <div class="form-floating">
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Password" required>
                        <label for="password">Password</label>
                        <div class="strength-meter">
                            <div class="strength-meter-fill" id="strengthMeter"></div>
                        </div>
                        <div class="form-text" id="strengthText">Password strength: Not entered</div>
                    </div>

                    <div class="password-requirements">
                        <strong>Password must contain:</strong>
                        <ul>
                            <li id="length-req">At least 6 characters</li>
                            <li id="uppercase-req">One uppercase letter (recommended)</li>
                            <li id="lowercase-req">One lowercase letter (recommended)</li>
                            <li id="number-req">One number (recommended)</li>
                        </ul>
                    </div>

                    <div class="form-floating">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               placeholder="Confirm Password" required>
                        <label for="confirm_password">Confirm Password</label>
                        <div class="form-text" id="matchText"></div>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and 
                            <a href="privacy.php" target="_blank">Privacy Policy</a>
                        </label>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter">
                        <label class="form-check-label" for="newsletter">
                            Subscribe to our newsletter for deals and updates
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Create Account</button>
                </form>

                <div class="footer-links">
                    <p>
                        Already have an account? 
                        <a href="login.php">Sign in here</a>
                    </p>
                    <p class="mt-3">
                        <small class="text-muted">
                            Want to become a vendor? 
                            <a href="vendor-register.php" class="text-decoration-none">Apply here</a>
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
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const strengthMeter = document.getElementById('strengthMeter');
            const strengthText = document.getElementById('strengthText');
            const matchText = document.getElementById('matchText');
            const form = document.getElementById('registerForm');
            
            // Password strength checker
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                const strength = checkPasswordStrength(password);
                
                strengthMeter.className = 'strength-meter-fill strength-' + strength.level;
                strengthText.textContent = 'Password strength: ' + strength.text;
                strengthText.style.color = strength.color;
                
                updateRequirements(password);
            });
            
            // Password match checker
            confirmPasswordInput.addEventListener('input', function() {
                const password = passwordInput.value;
                const confirmPassword = this.value;
                
                if (confirmPassword.length > 0) {
                    if (password === confirmPassword) {
                        matchText.textContent = 'Passwords match ✓';
                        matchText.style.color = 'var(--success-color)';
                    } else {
                        matchText.textContent = 'Passwords do not match ✗';
                        matchText.style.color = 'var(--danger-color)';
                    }
                } else {
                    matchText.textContent = '';
                }
            });
            
            // Form validation
            form.addEventListener('submit', function(e) {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                const terms = document.getElementById('terms').checked;
                
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
                    alert('Please accept the Terms of Service and Privacy Policy');
                    return;
                }
            });
            
            function checkPasswordStrength(password) {
                let score = 0;
                const checks = {
                    length: password.length >= 6,
                    uppercase: /[A-Z]/.test(password),
                    lowercase: /[a-z]/.test(password),
                    numbers: /\d/.test(password),
                    special: /[^A-Za-z0-9]/.test(password)
                };
                
                if (checks.length) score++;
                if (checks.uppercase) score++;
                if (checks.lowercase) score++;
                if (checks.numbers) score++;
                if (checks.special) score++;
                
                if (password.length === 0) {
                    return { level: '', text: 'Not entered', color: '#64748b' };
                } else if (score < 2) {
                    return { level: 'weak', text: 'Weak', color: 'var(--danger-color)' };
                } else if (score < 4) {
                    return { level: 'fair', text: 'Fair', color: 'var(--accent-color)' };
                } else if (score < 5) {
                    return { level: 'good', text: 'Good', color: 'var(--accent-color)' };
                } else {
                    return { level: 'strong', text: 'Strong', color: 'var(--success-color)' };
                }
            }
            
            function updateRequirements(password) {
                const requirements = {
                    'length-req': password.length >= 6,
                    'uppercase-req': /[A-Z]/.test(password),
                    'lowercase-req': /[a-z]/.test(password),
                    'number-req': /\d/.test(password)
                };
                
                for (const [id, valid] of Object.entries(requirements)) {
                    const element = document.getElementById(id);
                    if (valid) {
                        element.classList.add('valid');
                        element.style.color = 'var(--success-color)';
                    } else {
                        element.classList.remove('valid');
                        element.style.color = 'var(--secondary-color)';
                    }
                }
            }
        });
    </script>
</body>
</html>