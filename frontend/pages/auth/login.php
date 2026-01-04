<?php
/**
 * DiabetaCare - Login Page
 */

// If already logged in, redirect to dashboard
if (isAuthenticated()) {
    redirect('/');
}

$error = null;

// Handle form submission
if (isPost()) {
    if (!validateCsrfToken(post('csrf_token'))) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = trim(post('email', ''));
        $password = post('password', '');
        
        if (empty($email) || empty($password)) {
            $error = 'Please enter your email and password.';
        } else {
            $response = api()->login($email, $password);
            
            if (safeGet($response, 'success', false)) {
                // Calculate expiration timestamp
                $expiresAt = safeGet($response, 'expires_at');
                $expiresAt = is_numeric($expiresAt) 
                    ? (int) $expiresAt
                    : strtotime($expiresAt);
                
                // Set session
                setAuth(safeGet($response, 'user', []), safeStr($response, 'token', ''), $expiresAt);
                
                // Redirect to intended page or dashboard
                $redirectTo = $_SESSION['redirect_after_login'] ?? '/';
                unset($_SESSION['redirect_after_login']);
                redirect($redirectTo);
            } else {
                $error = safeGet($response, 'error.message', safeStr($response, 'message', 'Invalid email or password. Please try again.'));
            }
        }
    }
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DiabetaCare</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="<?php echo baseUrl('/assets/css/style.css'); ?>">
</head>
<body>
    <div class="auth-container">
        <!-- Left Side - Branding -->
        <div class="auth-branding">
            <div class="auth-branding-content">
                <div class="flex items-center gap-3 mb-6">
                    <div class="sidebar-logo">
                        <i data-lucide="activity" style="width: 1.75rem; height: 1.75rem; color: white;"></i>
                    </div>
                    <div>
                        <h1 style="font-size: 1.5rem; font-weight: 700; color: white;">DiabetaCare</h1>
                        <p style="color: rgba(255,255,255,0.6); font-size: 0.875rem;">Clinic Management System</p>
                    </div>
                </div>
            </div>
            
            <div class="auth-branding-content">
                <h2 class="auth-branding-title">
                    Streamline Your<br>
                    <span class="accent">Diabetes Care</span><br>
                    Management
                </h2>
                <p class="auth-branding-text">
                    Efficiently manage patient records, appointments, medications, and lab results all in one secure platform.
                </p>
            </div>
            
            <div class="auth-branding-content">
                <p style="color: rgba(255,255,255,0.6); font-size: 0.875rem;">
                    Trusted by <span style="color: white; font-weight: 600;">500+</span> healthcare providers
                </p>
            </div>
        </div>
        
        <!-- Right Side - Login Form -->
        <div class="auth-form-container">
            <div class="auth-form-wrapper">
                <!-- Mobile Logo -->
                <div class="auth-logo">
                    <div class="sidebar-logo">
                        <i data-lucide="activity" style="width: 1.75rem; height: 1.75rem; color: white;"></i>
                    </div>
                    <div>
                        <h1 style="font-size: 1.5rem; font-weight: 700;">DiabetaCare</h1>
                        <p style="color: var(--text-muted); font-size: 0.875rem;">Clinic Management System</p>
                    </div>
                </div>
                
                <div class="auth-form-card">
                    <div class="auth-form-header">
                        <h2 class="auth-form-title">Welcome Back</h2>
                        <p class="auth-form-subtitle">Sign in to access your dashboard</p>
                    </div>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i data-lucide="alert-circle"></i>
                        <span><?php echo e($error); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <?php echo csrfField(); ?>
                        
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <div class="form-input-icon">
                                <i data-lucide="mail"></i>
                                <input type="email" name="email" class="form-input" 
                                       placeholder="Enter your email" required
                                       value="<?php echo e(post('email', '')); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <div class="form-input-icon form-input-icon-right">
                                <i data-lucide="lock"></i>
                                <input type="password" name="password" id="password" class="form-input" 
                                       placeholder="Enter your password" required>
                                <i data-lucide="eye" id="togglePassword" onclick="togglePasswordVisibility()"></i>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between mb-6">
                            <label class="form-checkbox">
                                <input type="checkbox" name="remember">
                                <span class="text-sm" style="color: var(--text-secondary);">Remember me</span>
                            </label>
                            <a href="<?php echo baseUrl('/forgot-password'); ?>" class="text-sm">Forgot password?</a>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            Sign In
                        </button>
                    </form>
                    
                    <p class="text-center mt-4" style="color: var(--text-muted); font-size: 0.875rem;">
                        Don't have an account? 
                        <a href="<?php echo baseUrl('/register'); ?>">Register your clinic</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        lucide.createIcons();
        
        function togglePasswordVisibility() {
            const password = document.getElementById('password');
            const toggle = document.getElementById('togglePassword');
            
            if (password.type === 'password') {
                password.type = 'text';
                toggle.setAttribute('data-lucide', 'eye-off');
            } else {
                password.type = 'password';
                toggle.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }
    </script>
</body>
</html>
