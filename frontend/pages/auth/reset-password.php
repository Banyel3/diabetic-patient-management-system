<?php
/**
 * DiabetaCare - Reset Password Page
 */

if (isAuthenticated()) {
    redirect('/');
}

$token = get('token', '');
$error = null;
$success = null;

if (empty($token)) {
    redirect('/forgot-password');
}

if (isPost()) {
    if (!validateCsrfToken(post('csrf_token'))) {
        $error = 'Invalid request. Please try again.';
    } else {
        $password = post('password', '');
        $confirmPassword = post('confirm_password', '');
        
        if (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            $response = api()->resetPassword($token, $password);
            
            if ($response['success']) {
                setFlash('success', 'Password reset successfully. Please sign in with your new password.');
                redirect('/login');
            } else {
                $error = $response['error']['message'] ?? 'Failed to reset password. The link may have expired.';
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
    <title>Reset Password - DiabetaCare</title>
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
                        <h2 class="auth-form-title">Reset Password</h2>
                        <p class="auth-form-subtitle">Enter your new password</p>
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
                            <label class="form-label">New Password</label>
                            <div class="form-input-icon">
                                <i data-lucide="lock"></i>
                                <input type="password" name="password" class="form-input" 
                                       placeholder="Min 8 characters" required minlength="8">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Confirm Password</label>
                            <div class="form-input-icon">
                                <i data-lucide="lock"></i>
                                <input type="password" name="confirm_password" class="form-input" 
                                       placeholder="Confirm new password" required minlength="8">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            Reset Password
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
