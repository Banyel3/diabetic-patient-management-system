<?php
/**
 * DiabetaCare - Create Medication Page
 */

$pageTitle = 'Add Medication';

// Get patient_id if pre-selected
$preSelectedPatientId = get('patient_id', '');

$errors = [];
$formData = [
    'patient_id' => $preSelectedPatientId,
    'medication_name' => '',
    'dosage' => '',
    'frequency' => 'Once daily',
    'category' => '',
    'start_date' => date('Y-m-d'),
    'end_date' => '',
    'instructions' => '',
    'prescribing_doctor' => '',
    'is_active' => true,
    'notes' => '',
];

// Fetch patients for dropdown
$patientsResponse = api()->getPatients(['page_size' => 100]);
$patients = $patientsResponse['success'] ? ($patientsResponse['items'] ?? []) : [];

// Handle form submission
if (isPost()) {
    if (!validateCsrfToken(post('csrf_token'))) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $formData = array_merge($formData, [
            'patient_id' => (int) post('patient_id'),
            'medication_name' => trim(post('medication_name', '')),
            'dosage' => trim(post('dosage', '')),
            'frequency' => post('frequency', 'Once daily'),
            'category' => trim(post('category', '')),
            'start_date' => post('start_date', ''),
            'end_date' => post('end_date', ''),
            'instructions' => trim(post('instructions', '')),
            'prescribing_doctor' => trim(post('prescribing_doctor', '')),
            'is_active' => isset($_POST['is_active']),
            'notes' => trim(post('notes', '')),
        ]);
        
        // Validation
        if (empty($formData['patient_id'])) {
            $errors[] = 'Please select a patient.';
        }
        if (empty($formData['medication_name'])) {
            $errors[] = 'Medication name is required.';
        }
        if (empty($formData['dosage'])) {
            $errors[] = 'Dosage is required.';
        }
        if (empty($formData['start_date'])) {
            $errors[] = 'Start date is required.';
        }
        
        // Submit if no errors
        if (empty($errors)) {
            $response = api()->createMedication($formData);
            
            if ($response['success']) {
                setFlash('success', 'Medication added successfully.');
                redirect('/medications');
            } else {
                $errors[] = $response['error']['message'] ?? 'Failed to add medication.';
            }
        }
    }
}

include BASE_PATH . '/includes/layout/header.php';
?>

<div style="max-width: 700px; margin: 0 auto;">
    <!-- Header -->
    <div class="page-header">
        <div class="flex items-center gap-4">
            <a href="<?php echo baseUrl('/medications'); ?>" class="btn btn-icon btn-outline">
                <i data-lucide="arrow-left"></i>
            </a>
            <div>
                <h1 class="page-title mb-0">Add Medication</h1>
                <p class="page-subtitle">Prescribe a new medication</p>
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
    
    <form method="POST" action="<?php echo baseUrl('/medications/create' . ($preSelectedPatientId ? '?patient_id=' . $preSelectedPatientId : '')); ?>">
        <?php echo csrfField(); ?>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i data-lucide="pill"></i>
                    Medication Details
                </h3>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label class="form-label required">Patient</label>
                        <select name="patient_id" class="form-select" required>
                            <option value="">Select Patient</option>
                            <?php foreach ($patients as $patient): ?>
                            <option value="<?php echo $patient['id']; ?>" 
                                    <?php echo $formData['patient_id'] == $patient['id'] ? 'selected' : ''; ?>>
                                <?php echo e($patient['first_name'] . ' ' . $patient['last_name']); ?> 
                                (<?php echo e($patient['patient_code']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Medication Name</label>
                        <input type="text" name="medication_name" class="form-input" 
                               value="<?php echo e($formData['medication_name']); ?>" 
                               placeholder="e.g., Metformin" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="">Select Category</option>
                            <option value="Oral Hypoglycemic" <?php echo $formData['category'] === 'Oral Hypoglycemic' ? 'selected' : ''; ?>>Oral Hypoglycemic</option>
                            <option value="Insulin" <?php echo $formData['category'] === 'Insulin' ? 'selected' : ''; ?>>Insulin</option>
                            <option value="GLP-1 Agonist" <?php echo $formData['category'] === 'GLP-1 Agonist' ? 'selected' : ''; ?>>GLP-1 Agonist</option>
                            <option value="SGLT2 Inhibitor" <?php echo $formData['category'] === 'SGLT2 Inhibitor' ? 'selected' : ''; ?>>SGLT2 Inhibitor</option>
                            <option value="DPP-4 Inhibitor" <?php echo $formData['category'] === 'DPP-4 Inhibitor' ? 'selected' : ''; ?>>DPP-4 Inhibitor</option>
                            <option value="Supplement" <?php echo $formData['category'] === 'Supplement' ? 'selected' : ''; ?>>Supplement</option>
                            <option value="Other" <?php echo $formData['category'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Dosage</label>
                        <input type="text" name="dosage" class="form-input" 
                               value="<?php echo e($formData['dosage']); ?>" 
                               placeholder="e.g., 500mg" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Frequency</label>
                        <select name="frequency" class="form-select">
                            <option value="Once daily" <?php echo $formData['frequency'] === 'Once daily' ? 'selected' : ''; ?>>Once daily</option>
                            <option value="Twice daily" <?php echo $formData['frequency'] === 'Twice daily' ? 'selected' : ''; ?>>Twice daily</option>
                            <option value="Three times daily" <?php echo $formData['frequency'] === 'Three times daily' ? 'selected' : ''; ?>>Three times daily</option>
                            <option value="Four times daily" <?php echo $formData['frequency'] === 'Four times daily' ? 'selected' : ''; ?>>Four times daily</option>
                            <option value="Every 12 hours" <?php echo $formData['frequency'] === 'Every 12 hours' ? 'selected' : ''; ?>>Every 12 hours</option>
                            <option value="Every 8 hours" <?php echo $formData['frequency'] === 'Every 8 hours' ? 'selected' : ''; ?>>Every 8 hours</option>
                            <option value="Every 6 hours" <?php echo $formData['frequency'] === 'Every 6 hours' ? 'selected' : ''; ?>>Every 6 hours</option>
                            <option value="As needed" <?php echo $formData['frequency'] === 'As needed' ? 'selected' : ''; ?>>As needed (PRN)</option>
                            <option value="Weekly" <?php echo $formData['frequency'] === 'Weekly' ? 'selected' : ''; ?>>Weekly</option>
                            <option value="With meals" <?php echo $formData['frequency'] === 'With meals' ? 'selected' : ''; ?>>With meals</option>
                            <option value="Before meals" <?php echo $formData['frequency'] === 'Before meals' ? 'selected' : ''; ?>>Before meals</option>
                            <option value="After meals" <?php echo $formData['frequency'] === 'After meals' ? 'selected' : ''; ?>>After meals</option>
                            <option value="Bedtime" <?php echo $formData['frequency'] === 'Bedtime' ? 'selected' : ''; ?>>At bedtime</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Start Date</label>
                        <input type="date" name="start_date" class="form-input" 
                               value="<?php echo e($formData['start_date']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-input" 
                               value="<?php echo e($formData['end_date']); ?>">
                        <p class="form-hint">Leave blank for ongoing prescriptions</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Prescribing Doctor</label>
                        <input type="text" name="prescribing_doctor" class="form-input" 
                               value="<?php echo e($formData['prescribing_doctor']); ?>"
                               placeholder="e.g., Dr. Smith">
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Instructions</label>
                        <textarea name="instructions" class="form-input" rows="2"
                                  placeholder="e.g., Take with food, avoid grapefruit..."><?php echo e($formData['instructions']); ?></textarea>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-input" rows="2"
                                  placeholder="Additional notes..."><?php echo e($formData['notes']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="is_active" <?php echo $formData['is_active'] ? 'checked' : ''; ?>>
                            <span>Active prescription</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="form-actions">
            <a href="<?php echo baseUrl('/medications'); ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i>
                Save Medication
            </button>
        </div>
    </form>
</div>

<?php include BASE_PATH . '/includes/layout/footer.php'; ?>
