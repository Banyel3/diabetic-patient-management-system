<?php
/**
 * DiabetaCare - Registration Page
 */

if (isAuthenticated()) {
    redirect('/');
}

$error = null;
$step = (int) get('step', 1);

// Handle form submission
if (isPost()) {
    if (!validateCsrfToken(post('csrf_token'))) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = post('action');
        
        if ($action === 'next' && $step < 3) {
            // Store form data in session and go to next step
            $_SESSION['register_form'] = array_merge(
                $_SESSION['register_form'] ?? [],
                $_POST
            );
            redirect('/register?step=' . ($step + 1));
        } elseif ($action === 'back' && $step > 1) {
            redirect('/register?step=' . ($step - 1));
        } elseif ($action === 'submit') {
            // Merge all form data
            $formData = array_merge($_SESSION['register_form'] ?? [], $_POST);
            
            // Validate passwords match
            if ($formData['password'] !== $formData['confirm_password']) {
                $error = 'Passwords do not match';
            } elseif (empty($formData['terms_accepted'])) {
                $error = 'You must accept the terms and conditions';
            } else {
                // Submit registration
                $response = api()->register([
                    'clinic_name' => $formData['clinic_name'] ?? '',
                    'clinic_email' => $formData['clinic_email'] ?? '',
                    'clinic_phone' => $formData['clinic_phone'] ?? '',
                    'registration_number' => $formData['registration_number'] ?? '',
                    'license_number' => $formData['license_number'] ?? '',
                    'street_address' => $formData['address'] ?? '',
                    'city' => $formData['city'] ?? '',
                    'state_province' => $formData['state'] ?? '',
                    'zip_postal_code' => $formData['zip_code'] ?? '',
                    'first_name' => $formData['first_name'] ?? '',
                    'last_name' => $formData['last_name'] ?? '',
                    'email' => $formData['admin_email'] ?? '',
                    'phone' => $formData['admin_phone'] ?? '',
                    'password' => $formData['password'] ?? '',
                    'terms_accepted' => true,
                ]);
                
                if ($response['success']) {
                    // Clear registration session
                    unset($_SESSION['register_form']);
                    
                    // Set auth and redirect
                    $expiresAt = is_numeric($response['expires_at']) 
                        ? (int) $response['expires_at']
                        : strtotime($response['expires_at']);
                    setAuth($response['user'], $response['token'], $expiresAt);
                    redirect('/');
                } else {
                    $error = $response['error']['message'] ?? 'Registration failed. Please try again.';
                }
            }
        }
    }
}

// Get stored form data
$formData = $_SESSION['register_form'] ?? [];

$steps = [
    ['number' => 1, 'title' => 'Clinic Info', 'description' => 'Basic details'],
    ['number' => 2, 'title' => 'Address', 'description' => 'Location details'],
    ['number' => 3, 'title' => 'Admin Account', 'description' => 'Login credentials'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - DiabetaCare</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="<?php echo baseUrl('/assets/css/style.css'); ?>">
</head>
<body>
    <div class="auth-container">
        <!-- Left Side - Branding -->
        <div class="auth-branding" style="width: 40%;">
            <div class="auth-branding-content">
                <div class="flex items-center gap-3">
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
                    Register Your<br>
                    <span class="accent">Diabetes Clinic</span><br>
                    Today
                </h2>
                <p class="auth-branding-text mb-6">
                    Join hundreds of healthcare providers who trust DiabetaCare for managing their diabetic patients efficiently and securely.
                </p>
                
                <!-- Features List -->
                <div style="margin-top: 2rem;">
                    <?php 
                    $features = [
                        'Complete patient management',
                        'HbA1c tracking & analytics',
                        'Medication management',
                        'Secure & HIPAA compliant',
                    ];
                    foreach ($features as $feature): ?>
                    <div class="flex items-center gap-3 mb-3">
                        <div style="width: 1.5rem; height: 1.5rem; background: rgba(45, 212, 191, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i data-lucide="check" style="width: 1rem; height: 1rem; color: var(--accent);"></i>
                        </div>
                        <span style="color: rgba(255,255,255,0.8);"><?php echo e($feature); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="auth-branding-content">
                <p style="color: rgba(255,255,255,0.6); font-size: 0.875rem;">
                    Already have an account? <a href="<?php echo baseUrl('/login'); ?>" style="color: var(--accent);">Sign in</a>
                </p>
            </div>
        </div>
        
        <!-- Right Side - Registration Form -->
        <div class="auth-form-container" style="padding: 1.5rem;">
            <div class="auth-form-wrapper" style="max-width: 600px;">
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
                    <!-- Step Indicator -->
                    <div class="flex items-center justify-center gap-4 mb-6">
                        <?php foreach ($steps as $index => $s): ?>
                        <div class="flex items-center gap-2">
                            <div style="width: 2rem; height: 2rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.875rem; font-weight: 600;
                                <?php if ($step > $s['number']): ?>
                                background: var(--success); color: white;
                                <?php elseif ($step === $s['number']): ?>
                                background: var(--accent); color: white;
                                <?php else: ?>
                                background: var(--surface-secondary); color: var(--text-muted);
                                <?php endif; ?>">
                                <?php if ($step > $s['number']): ?>
                                <i data-lucide="check" style="width: 1rem; height: 1rem;"></i>
                                <?php else: ?>
                                <?php echo $s['number']; ?>
                                <?php endif; ?>
                            </div>
                            <div class="hidden" style="display: <?php echo $index < count($steps) - 1 ? 'block' : 'none'; ?>;">
                                <span style="color: var(--text-muted); font-size: 0.75rem;"><?php echo e($s['title']); ?></span>
                            </div>
                        </div>
                        <?php if ($index < count($steps) - 1): ?>
                        <div style="width: 3rem; height: 2px; background: <?php echo $step > $s['number'] ? 'var(--accent)' : 'var(--border-light)'; ?>;"></div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="auth-form-header">
                        <h2 class="auth-form-title"><?php echo e($steps[$step - 1]['title']); ?></h2>
                        <p class="auth-form-subtitle"><?php echo e($steps[$step - 1]['description']); ?></p>
                    </div>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i data-lucide="alert-circle"></i>
                        <span><?php echo e($error); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <?php echo csrfField(); ?>
                        
                        <?php if ($step === 1): ?>
                        <!-- Step 1: Clinic Info -->
                        <div class="form-group">
                            <label class="form-label">Clinic Name *</label>
                            <div class="form-input-icon">
                                <i data-lucide="building-2"></i>
                                <input type="text" name="clinic_name" class="form-input" required
                                       placeholder="Enter clinic name"
                                       value="<?php echo e($formData['clinic_name'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Registration Number</label>
                                <div class="form-input-icon">
                                    <i data-lucide="file-text"></i>
                                    <input type="text" name="registration_number" class="form-input"
                                           placeholder="Business registration #"
                                           value="<?php echo e($formData['registration_number'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">License Number</label>
                                <div class="form-input-icon">
                                    <i data-lucide="file-text"></i>
                                    <input type="text" name="license_number" class="form-input"
                                           placeholder="Medical license #"
                                           value="<?php echo e($formData['license_number'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Clinic Phone *</label>
                                <div class="form-input-icon">
                                    <i data-lucide="phone"></i>
                                    <input type="tel" name="clinic_phone" class="form-input" required
                                           placeholder="Phone number"
                                           value="<?php echo e($formData['clinic_phone'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Clinic Email *</label>
                                <div class="form-input-icon">
                                    <i data-lucide="mail"></i>
                                    <input type="email" name="clinic_email" class="form-input" required
                                           placeholder="clinic@example.com"
                                           value="<?php echo e($formData['clinic_email'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <?php elseif ($step === 2): ?>
                        <!-- Step 2: Address -->
                        <div class="form-group">
                            <label class="form-label">Street Address *</label>
                            <div class="form-input-icon">
                                <i data-lucide="map-pin"></i>
                                <input type="text" name="address" class="form-input" required
                                       placeholder="123 Medical Center Dr"
                                       value="<?php echo e($formData['address'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">City *</label>
                                <input type="text" name="city" class="form-input" required
                                       placeholder="City"
                                       value="<?php echo e($formData['city'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">State/Province *</label>
                                <input type="text" name="state" class="form-input" required
                                       placeholder="State"
                                       value="<?php echo e($formData['state'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">ZIP/Postal Code *</label>
                            <input type="text" name="zip_code" class="form-input" required
                                   placeholder="12345"
                                   value="<?php echo e($formData['zip_code'] ?? ''); ?>">
                        </div>
                        
                        <?php elseif ($step === 3): ?>
                        <!-- Step 3: Admin Account -->
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">First Name *</label>
                                <div class="form-input-icon">
                                    <i data-lucide="user"></i>
                                    <input type="text" name="first_name" class="form-input" required
                                           placeholder="First name"
                                           value="<?php echo e($formData['first_name'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Last Name *</label>
                                <div class="form-input-icon">
                                    <i data-lucide="user"></i>
                                    <input type="text" name="last_name" class="form-input" required
                                           placeholder="Last name"
                                           value="<?php echo e($formData['last_name'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Email *</label>
                                <div class="form-input-icon">
                                    <i data-lucide="mail"></i>
                                    <input type="email" name="admin_email" class="form-input" required
                                           placeholder="admin@example.com"
                                           value="<?php echo e($formData['admin_email'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone</label>
                                <div class="form-input-icon">
                                    <i data-lucide="phone"></i>
                                    <input type="tel" name="admin_phone" class="form-input"
                                           placeholder="Phone number"
                                           value="<?php echo e($formData['admin_phone'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Password *</label>
                                <div class="form-input-icon">
                                    <i data-lucide="lock"></i>
                                    <input type="password" name="password" class="form-input" required
                                           placeholder="Min 8 characters"
                                           minlength="8">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Confirm Password *</label>
                                <div class="form-input-icon">
                                    <i data-lucide="lock"></i>
                                    <input type="password" name="confirm_password" class="form-input" required
                                           placeholder="Confirm password"
                                           minlength="8">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-checkbox">
                                <input type="checkbox" name="terms_accepted" value="1" required>
                                <span class="text-sm" style="color: var(--text-secondary);">
                                    I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                                </span>
                            </label>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Navigation Buttons -->
                        <div class="flex justify-between mt-6">
                            <?php if ($step > 1): ?>
                            <button type="submit" name="action" value="back" class="btn btn-secondary">
                                <i data-lucide="arrow-left"></i>
                                Back
                            </button>
                            <?php else: ?>
                            <div></div>
                            <?php endif; ?>
                            
                            <?php if ($step < 3): ?>
                            <button type="submit" name="action" value="next" class="btn btn-primary">
                                Continue
                                <i data-lucide="arrow-right"></i>
                            </button>
                            <?php else: ?>
                            <button type="submit" name="action" value="submit" class="btn btn-primary">
                                Create Account
                                <i data-lucide="check"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
