<?php
/**
 * DiabetaCare - Forgot Password Page
 */

if (isAuthenticated()) {
    redirect('/');
}

$error = null;
$success = null;

if (isPost()) {
    if (!validateCsrfToken(post('csrf_token'))) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = trim(post('email', ''));
        
        if (empty($email)) {
            $error = 'Please enter your email address.';
        } else {
            $response = api()->forgotPassword($email);
            
            if ($response['success']) {
                $success = 'If an account exists with that email, you will receive a password reset link shortly.';
            } else {
                // Don't reveal if email exists
                $success = 'If an account exists with that email, you will receive a password reset link shortly.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - DiabetaCare</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="<?php echo baseUrl('/assets/css/style.css'); ?>">
</head>
<body>
    <div class="auth-container">
        <div class="auth-form-container" style="width: 100%;">
            <div class="auth-form-wrapper">
                <div class="auth-logo" style="display: flex;">
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
                        <h2 class="auth-form-title">Forgot Password?</h2>
                        <p class="auth-form-subtitle">Enter your email to receive a reset link</p>
                    </div>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i data-lucide="alert-circle"></i>
                        <span><?php echo e($error); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i data-lucide="check-circle"></i>
                        <span><?php echo e($success); ?></span>
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
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            Send Reset Link
                        </button>
                    </form>
                    
                    <p class="text-center mt-4" style="color: var(--text-muted); font-size: 0.875rem;">
                        Remember your password? 
                        <a href="<?php echo baseUrl('/login'); ?>">Sign in</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
