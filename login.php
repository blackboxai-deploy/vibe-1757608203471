<?php
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } elseif (!validateEmail($email)) {
        $error = "Please enter a valid email address";
    } else {
        if ($auth->login($email, $password)) {
            // Set remember me cookie if checked
            if ($remember) {
                setcookie('remember_email', $email, time() + (30 * 24 * 60 * 60), '/'); // 30 days
            }
            
            // Redirect based on role
            $redirectUrl = isset($_GET['redirect']) ? $_GET['redirect'] : '';
            
            if (!empty($redirectUrl)) {
                redirect($redirectUrl);
            } elseif ($auth->hasRole('admin')) {
                redirect('admin/dashboard.php');
            } elseif ($auth->hasRole('vendor')) {
                redirect('vendor/dashboard.php');
            } else {
                redirect('customer/dashboard.php');
            }
        } else {
            $error = "Invalid email or password";
        }
    }
}

$pageTitle = "Login - " . SITE_NAME;
$rememberedEmail = isset($_COOKIE['remember_email']) ? $_COOKIE['remember_email'] : '';
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
            display: flex;
            align-items: center;
        }

        .login-container {
            max-width: 420px;
            width: 100%;
            margin: 0 auto;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 10px;
        }

        .login-header p {
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

        .form-check {
            margin: 20px 0;
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
            color: var(--secondary-color);
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e2e8f0;
            z-index: 1;
        }

        .divider span {
            background: white;
            padding: 0 20px;
            position: relative;
            z-index: 2;
        }

        .social-login {
            display: grid;
            gap: 15px;
        }

        .btn-social {
            padding: 12px;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 2px solid #e2e8f0;
            background: white;
        }

        .btn-social:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
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
            position: absolute;
            top: 20px;
            left: 20px;
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

        @media (max-width: 576px) {
            .login-card {
                margin: 20px;
                padding: 30px 25px;
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
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <h1>Welcome Back</h1>
                    <p>Sign in to your account to continue</p>
                </div>

                <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-floating">
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="name@example.com" value="<?php echo htmlspecialchars($rememberedEmail); ?>" required>
                        <label for="email">Email Address</label>
                    </div>

                    <div class="form-floating">
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Password" required>
                        <label for="password">Password</label>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember" 
                               <?php echo !empty($rememberedEmail) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="remember">
                            Remember me
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Sign In</button>
                </form>

                <div class="divider">
                    <span>or</span>
                </div>

                <div class="social-login">
                    <button class="btn btn-social" onclick="alert('Social login coming soon!')">
                        <strong>Continue with Google</strong>
                    </button>
                    <button class="btn btn-social" onclick="alert('Social login coming soon!')">
                        <strong>Continue with Facebook</strong>
                    </button>
                </div>

                <div class="footer-links">
                    <p class="mb-2">
                        <a href="forgot-password.php">Forgot your password?</a>
                    </p>
                    <p>
                        Don't have an account? 
                        <a href="register.php">Sign up here</a>
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
        // Auto-focus on password field if email is remembered
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            
            if (emailInput.value) {
                passwordInput.focus();
            } else {
                emailInput.focus();
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
                return;
            }

            if (!isValidEmail(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return;
            }
        });

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
    </script>
</body>
</html>