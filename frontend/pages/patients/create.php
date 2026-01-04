<?php
/**
 * DiabetaCare - Create Patient Page
 */

$pageTitle = 'Add New Patient';

$errors = [];
$formData = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'date_of_birth' => '',
    'gender' => '',
    'address' => '',
    'diabetes_type' => 'Type 2',
    'diagnosis_date' => '',
    'medical_history' => '',
    'emergency_contact_name' => '',
    'emergency_contact_phone' => '',
    'emergency_contact_relation' => '',
    'notes' => '',
    'status' => 'Active',
];

// Handle form submission
if (isPost()) {
    if (!validateCsrfToken(post('csrf_token'))) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $formData = array_merge($formData, [
            'first_name' => trim(post('first_name', '')),
            'last_name' => trim(post('last_name', '')),
            'email' => trim(post('email', '')),
            'phone' => trim(post('phone', '')),
            'date_of_birth' => post('date_of_birth', ''),
            'gender' => post('gender', ''),
            'address' => trim(post('address', '')),
            'diabetes_type' => post('diabetes_type', 'Type 2'),
            'diagnosis_date' => post('diagnosis_date', ''),
            'medical_history' => trim(post('medical_history', '')),
            'emergency_contact_name' => trim(post('emergency_contact_name', '')),
            'emergency_contact_phone' => trim(post('emergency_contact_phone', '')),
            'emergency_contact_relation' => trim(post('emergency_contact_relation', '')),
            'notes' => trim(post('notes', '')),
            'status' => post('status', 'Active'),
        ]);
        
        // Validation
        if (empty($formData['first_name'])) {
            $errors[] = 'First name is required.';
        }
        if (empty($formData['last_name'])) {
            $errors[] = 'Last name is required.';
        }
        if (empty($formData['date_of_birth'])) {
            $errors[] = 'Date of birth is required.';
        }
        if (empty($formData['gender'])) {
            $errors[] = 'Gender is required.';
        }
        if (empty($formData['diabetes_type'])) {
            $errors[] = 'Diabetes type is required.';
        }
        if (!empty($formData['email']) && !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format.';
        }
        
        // Submit if no errors
        if (empty($errors)) {
            $response = api()->createPatient($formData);
            
            if ($response['success']) {
                setFlash('success', 'Patient created successfully.');
                redirect('/patients/view?id=' . $response['id']);
            } else {
                $errors[] = $response['error']['message'] ?? 'Failed to create patient.';
            }
        }
    }
}

include BASE_PATH . '/includes/layout/header.php';
?>

<div style="max-width: 900px; margin: 0 auto;">
    <!-- Header -->
    <div class="page-header">
        <div class="flex items-center gap-4">
            <a href="<?php echo baseUrl('/patients'); ?>" class="btn btn-icon btn-outline">
                <i data-lucide="arrow-left"></i>
            </a>
            <div>
                <h1 class="page-title mb-0">Add New Patient</h1>
                <p class="page-subtitle">Enter patient information below</p>
            </div>
        </div>
    </div>
    
    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <i data-lucide="alert-circle"></i>
        <div>
            <?php foreach ($errors as $error): ?>
            <p><?php echo e($error); ?></p>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <form method="POST" action="<?php echo baseUrl('/patients/create'); ?>">
        <?php echo csrfField(); ?>
        
        <!-- Personal Information -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i data-lucide="user"></i>
                    Personal Information
                </h3>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label required">First Name</label>
                        <input type="text" name="first_name" class="form-input" 
                               value="<?php echo e($formData['first_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Last Name</label>
                        <input type="text" name="last_name" class="form-input" 
                               value="<?php echo e($formData['last_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-input" 
                               value="<?php echo e($formData['date_of_birth']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Gender</label>
                        <select name="gender" class="form-select" required>
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo $formData['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $formData['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo $formData['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" 
                               value="<?php echo e($formData['email']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-input" 
                               value="<?php echo e($formData['phone']); ?>">
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-input" rows="2"><?php echo e($formData['address']); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Medical Information -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i data-lucide="stethoscope"></i>
                    Medical Information
                </h3>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label required">Diabetes Type</label>
                        <select name="diabetes_type" class="form-select" required>
                            <option value="Type 1" <?php echo $formData['diabetes_type'] === 'Type 1' ? 'selected' : ''; ?>>Type 1</option>
                            <option value="Type 2" <?php echo $formData['diabetes_type'] === 'Type 2' ? 'selected' : ''; ?>>Type 2</option>
                            <option value="Gestational" <?php echo $formData['diabetes_type'] === 'Gestational' ? 'selected' : ''; ?>>Gestational</option>
                            <option value="Pre-diabetic" <?php echo $formData['diabetes_type'] === 'Pre-diabetic' ? 'selected' : ''; ?>>Pre-diabetic</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Diagnosis Date</label>
                        <input type="date" name="diagnosis_date" class="form-input" 
                               value="<?php echo e($formData['diagnosis_date']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active" <?php echo $formData['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo $formData['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="Critical" <?php echo $formData['status'] === 'Critical' ? 'selected' : ''; ?>>Critical</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Medical History</label>
                        <textarea name="medical_history" class="form-input" rows="3"
                                  placeholder="Enter any relevant medical history, allergies, or conditions..."><?php echo e($formData['medical_history']); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Emergency Contact -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i data-lucide="phone-call"></i>
                    Emergency Contact
                </h3>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Contact Name</label>
                        <input type="text" name="emergency_contact_name" class="form-input" 
                               value="<?php echo e($formData['emergency_contact_name']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Relationship</label>
                        <input type="text" name="emergency_contact_relation" class="form-input" 
                               value="<?php echo e($formData['emergency_contact_relation']); ?>"
                               placeholder="e.g., Spouse, Parent, Child">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Phone</label>
                        <input type="tel" name="emergency_contact_phone" class="form-input" 
                               value="<?php echo e($formData['emergency_contact_phone']); ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Notes -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i data-lucide="file-text"></i>
                    Additional Notes
                </h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <textarea name="notes" class="form-input" rows="4"
                              placeholder="Any additional notes about this patient..."><?php echo e($formData['notes']); ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="form-actions">
            <a href="<?php echo baseUrl('/patients'); ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i>
                Save Patient
            </button>
        </div>
    </form>
</div>

<?php include BASE_PATH . '/includes/layout/footer.php'; ?>
