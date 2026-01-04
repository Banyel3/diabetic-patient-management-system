<?php
/**
 * DiabetaCare - Settings Page
 */

$pageTitle = 'Settings';

// Get current user
$user = getCurrentUser();

$errors = [];
$successMessage = getFlash('success');
$errorMessage = getFlash('error');

// Handle profile update
if (isPost() && post('action') === 'update_profile') {
    if (!validateCsrfToken(post('csrf_token'))) {
        $errors[] = 'Invalid form submission.';
    } else {
        $profileData = [
            'name' => trim(post('name', '')),
            'email' => trim(post('email', '')),
        ];
        
        if (empty($profileData['name'])) {
            $errors[] = 'Name is required.';
        }
        if (empty($profileData['email']) || !filter_var($profileData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email is required.';
        }
        
        if (empty($errors)) {
            $response = api()->updateProfile($profileData);
            if ($response['success']) {
                // Update session
                $_SESSION['user']['name'] = $profileData['name'];
                $_SESSION['user']['email'] = $profileData['email'];
                setFlash('success', 'Profile updated successfully.');
                redirect('/settings');
            } else {
                $errors[] = $response['error']['message'] ?? 'Failed to update profile.';
            }
        }
    }
}

// Handle password change
if (isPost() && post('action') === 'change_password') {
    if (!validateCsrfToken(post('csrf_token'))) {
        $errors[] = 'Invalid form submission.';
    } else {
        $currentPassword = post('current_password', '');
        $newPassword = post('new_password', '');
        $confirmPassword = post('confirm_password', '');
        
        if (empty($currentPassword)) {
            $errors[] = 'Current password is required.';
        }
        if (empty($newPassword) || strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match.';
        }
        
        if (empty($errors)) {
            $response = api()->changePassword([
                'current_password' => $currentPassword,
                'new_password' => $newPassword,
            ]);
            if ($response['success']) {
                setFlash('success', 'Password changed successfully.');
                redirect('/settings');
            } else {
                $errors[] = $response['error']['message'] ?? 'Failed to change password.';
            }
        }
    }
}

include BASE_PATH . '/includes/layout/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
    <!-- Header -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Settings</h1>
            <p class="page-subtitle">Manage your account and preferences</p>
        </div>
    </div>
    
    <?php if ($successMessage): ?>
    <div class="alert alert-success">
        <i data-lucide="check-circle"></i>
        <span><?php echo e($successMessage); ?></span>
    </div>
    <?php endif; ?>
    
    <?php if ($errorMessage || !empty($errors)): ?>
    <div class="alert alert-danger">
        <i data-lucide="alert-circle"></i>
        <div>
            <?php if ($errorMessage): ?>
            <p><?php echo e($errorMessage); ?></p>
            <?php endif; ?>
            <?php foreach ($errors as $error): ?>
            <p><?php echo e($error); ?></p>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Profile Settings -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i data-lucide="user"></i>
                Profile Information
            </h3>
        </div>
        <div class="card-body">
            <form method="POST" action="<?php echo baseUrl('/settings'); ?>">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label required">Name</label>
                        <input type="text" name="name" class="form-input" 
                               value="<?php echo e($user['name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Email</label>
                        <input type="email" name="email" class="form-input" 
                               value="<?php echo e($user['email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-input" 
                               value="<?php echo e(ucfirst($user['role'] ?? 'User')); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Member Since</label>
                        <input type="text" class="form-input" 
                               value="<?php echo formatDate($user['created_at'] ?? '', 'M j, Y'); ?>" disabled>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i>
                        Update Profile
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Change Password -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i data-lucide="lock"></i>
                Change Password
            </h3>
        </div>
        <div class="card-body">
            <form method="POST" action="<?php echo baseUrl('/settings'); ?>">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label class="form-label required">Current Password</label>
                        <div class="password-input">
                            <input type="password" name="current_password" class="form-input" 
                                   id="current_password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                                <i data-lucide="eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">New Password</label>
                        <div class="password-input">
                            <input type="password" name="new_password" class="form-input" 
                                   id="new_password" minlength="8" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                <i data-lucide="eye"></i>
                            </button>
                        </div>
                        <p class="form-hint">Minimum 8 characters</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Confirm New Password</label>
                        <div class="password-input">
                            <input type="password" name="confirm_password" class="form-input" 
                                   id="confirm_password" minlength="8" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                <i data-lucide="eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="key"></i>
                        Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Application Info -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i data-lucide="info"></i>
                About DiabetaCare
            </h3>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item">
                    <label>Version</label>
                    <p>1.0.0</p>
                </div>
                <div class="info-item">
                    <label>Environment</label>
                    <p>Development</p>
                </div>
                <div class="info-item">
                    <label>PHP Version</label>
                    <p><?php echo e(phpversion()); ?></p>
                </div>
                <div class="info-item">
                    <label>Server</label>
                    <p><?php echo e($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Danger Zone -->
    <div class="card danger-zone">
        <div class="card-header">
            <h3 class="card-title text-danger">
                <i data-lucide="alert-triangle"></i>
                Danger Zone
            </h3>
        </div>
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-medium" style="color: var(--text-primary);">Delete Account</p>
                    <p class="text-sm" style="color: var(--text-muted);">Permanently delete your account and all data.</p>
                </div>
                <button type="button" class="btn btn-danger" onclick="alert('This feature is not yet implemented.')">
                    Delete Account
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(id) {
    const input = document.getElementById(id);
    const icon = input.nextElementSibling.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.setAttribute('data-lucide', 'eye-off');
    } else {
        input.type = 'password';
        icon.setAttribute('data-lucide', 'eye');
    }
    lucide.createIcons();
}
</script>

<?php include BASE_PATH . '/includes/layout/footer.php'; ?>
