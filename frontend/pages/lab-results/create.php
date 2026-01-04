<?php
/**
 * DiabetaCare - Create Lab Result Page
 */

$pageTitle = 'Add Lab Result';

// Get patient_id if pre-selected
$preSelectedPatientId = get('patient_id', '');

$errors = [];
$formData = [
    'patient_id' => $preSelectedPatientId,
    'test_date' => date('Y-m-d'),
    'test_type' => 'HbA1c',
    'hba1c' => '',
    'fasting_glucose' => '',
    'random_glucose' => '',
    'cholesterol_total' => '',
    'cholesterol_ldl' => '',
    'cholesterol_hdl' => '',
    'triglycerides' => '',
    'creatinine' => '',
    'egfr' => '',
    'blood_pressure_systolic' => '',
    'blood_pressure_diastolic' => '',
    'weight' => '',
    'notes' => '',
    'status' => 'Normal',
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
            'test_date' => post('test_date', ''),
            'test_type' => post('test_type', 'HbA1c'),
            'hba1c' => post('hba1c', '') !== '' ? (float) post('hba1c') : null,
            'fasting_glucose' => post('fasting_glucose', '') !== '' ? (int) post('fasting_glucose') : null,
            'random_glucose' => post('random_glucose', '') !== '' ? (int) post('random_glucose') : null,
            'cholesterol_total' => post('cholesterol_total', '') !== '' ? (int) post('cholesterol_total') : null,
            'cholesterol_ldl' => post('cholesterol_ldl', '') !== '' ? (int) post('cholesterol_ldl') : null,
            'cholesterol_hdl' => post('cholesterol_hdl', '') !== '' ? (int) post('cholesterol_hdl') : null,
            'triglycerides' => post('triglycerides', '') !== '' ? (int) post('triglycerides') : null,
            'creatinine' => post('creatinine', '') !== '' ? (float) post('creatinine') : null,
            'egfr' => post('egfr', '') !== '' ? (int) post('egfr') : null,
            'blood_pressure_systolic' => post('blood_pressure_systolic', '') !== '' ? (int) post('blood_pressure_systolic') : null,
            'blood_pressure_diastolic' => post('blood_pressure_diastolic', '') !== '' ? (int) post('blood_pressure_diastolic') : null,
            'weight' => post('weight', '') !== '' ? (float) post('weight') : null,
            'notes' => trim(post('notes', '')),
            'status' => post('status', 'Normal'),
        ]);
        
        // Validation
        if (empty($formData['patient_id'])) {
            $errors[] = 'Please select a patient.';
        }
        if (empty($formData['test_date'])) {
            $errors[] = 'Test date is required.';
        }
        
        // Submit if no errors
        if (empty($errors)) {
            // Filter out null values
            $dataToSubmit = array_filter($formData, function($v) { return $v !== null && $v !== ''; });
            $dataToSubmit['patient_id'] = $formData['patient_id'];
            $dataToSubmit['test_date'] = $formData['test_date'];
            $dataToSubmit['test_type'] = $formData['test_type'];
            $dataToSubmit['status'] = $formData['status'];
            
            $response = api()->createLabResult($dataToSubmit);
            
            if ($response['success']) {
                setFlash('success', 'Lab result added successfully.');
                redirect('/lab-results');
            } else {
                $errors[] = $response['error']['message'] ?? 'Failed to add lab result.';
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
            <a href="<?php echo baseUrl('/lab-results'); ?>" class="btn btn-icon btn-outline">
                <i data-lucide="arrow-left"></i>
            </a>
            <div>
                <h1 class="page-title mb-0">Add Lab Result</h1>
                <p class="page-subtitle">Record new test results</p>
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
    
    <form method="POST" action="<?php echo baseUrl('/lab-results/create' . ($preSelectedPatientId ? '?patient_id=' . $preSelectedPatientId : '')); ?>">
        <?php echo csrfField(); ?>
        
        <!-- Basic Information -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i data-lucide="flask-conical"></i>
                    Test Information
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
                        <label class="form-label required">Test Date</label>
                        <input type="date" name="test_date" class="form-input" 
                               value="<?php echo e($formData['test_date']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Test Type</label>
                        <select name="test_type" class="form-select">
                            <option value="HbA1c" <?php echo $formData['test_type'] === 'HbA1c' ? 'selected' : ''; ?>>HbA1c</option>
                            <option value="Fasting Glucose" <?php echo $formData['test_type'] === 'Fasting Glucose' ? 'selected' : ''; ?>>Fasting Glucose</option>
                            <option value="Random Glucose" <?php echo $formData['test_type'] === 'Random Glucose' ? 'selected' : ''; ?>>Random Glucose</option>
                            <option value="Lipid Panel" <?php echo $formData['test_type'] === 'Lipid Panel' ? 'selected' : ''; ?>>Lipid Panel</option>
                            <option value="Kidney Function" <?php echo $formData['test_type'] === 'Kidney Function' ? 'selected' : ''; ?>>Kidney Function</option>
                            <option value="Comprehensive Panel" <?php echo $formData['test_type'] === 'Comprehensive Panel' ? 'selected' : ''; ?>>Comprehensive Panel</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Normal" <?php echo $formData['status'] === 'Normal' ? 'selected' : ''; ?>>Normal</option>
                            <option value="Abnormal" <?php echo $formData['status'] === 'Abnormal' ? 'selected' : ''; ?>>Abnormal</option>
                            <option value="Critical" <?php echo $formData['status'] === 'Critical' ? 'selected' : ''; ?>>Critical</option>
                            <option value="Pending" <?php echo $formData['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Glucose Results -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i data-lucide="droplet"></i>
                    Glucose Results
                </h3>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">HbA1c (%)</label>
                        <input type="number" name="hba1c" class="form-input" step="0.1" min="0" max="20"
                               value="<?php echo e($formData['hba1c']); ?>" placeholder="e.g., 6.5">
                        <p class="form-hint">Target: &lt; 7.0%</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fasting Glucose (mg/dL)</label>
                        <input type="number" name="fasting_glucose" class="form-input" min="0" max="1000"
                               value="<?php echo e($formData['fasting_glucose']); ?>" placeholder="e.g., 100">
                        <p class="form-hint">Target: 70-100 mg/dL</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Random Glucose (mg/dL)</label>
                        <input type="number" name="random_glucose" class="form-input" min="0" max="1000"
                               value="<?php echo e($formData['random_glucose']); ?>" placeholder="e.g., 140">
                        <p class="form-hint">Target: &lt; 200 mg/dL</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Lipid Panel -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i data-lucide="heart"></i>
                    Lipid Panel
                </h3>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Total Cholesterol (mg/dL)</label>
                        <input type="number" name="cholesterol_total" class="form-input" min="0" max="500"
                               value="<?php echo e($formData['cholesterol_total']); ?>" placeholder="e.g., 200">
                    </div>
                    <div class="form-group">
                        <label class="form-label">LDL Cholesterol (mg/dL)</label>
                        <input type="number" name="cholesterol_ldl" class="form-input" min="0" max="500"
                               value="<?php echo e($formData['cholesterol_ldl']); ?>" placeholder="e.g., 100">
                    </div>
                    <div class="form-group">
                        <label class="form-label">HDL Cholesterol (mg/dL)</label>
                        <input type="number" name="cholesterol_hdl" class="form-input" min="0" max="200"
                               value="<?php echo e($formData['cholesterol_hdl']); ?>" placeholder="e.g., 50">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Triglycerides (mg/dL)</label>
                        <input type="number" name="triglycerides" class="form-input" min="0" max="1000"
                               value="<?php echo e($formData['triglycerides']); ?>" placeholder="e.g., 150">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Kidney Function & Vitals -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i data-lucide="activity"></i>
                    Kidney Function & Vitals
                </h3>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Creatinine (mg/dL)</label>
                        <input type="number" name="creatinine" class="form-input" step="0.1" min="0" max="20"
                               value="<?php echo e($formData['creatinine']); ?>" placeholder="e.g., 1.0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">eGFR (mL/min/1.73m²)</label>
                        <input type="number" name="egfr" class="form-input" min="0" max="200"
                               value="<?php echo e($formData['egfr']); ?>" placeholder="e.g., 90">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Blood Pressure Systolic (mmHg)</label>
                        <input type="number" name="blood_pressure_systolic" class="form-input" min="0" max="300"
                               value="<?php echo e($formData['blood_pressure_systolic']); ?>" placeholder="e.g., 120">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Blood Pressure Diastolic (mmHg)</label>
                        <input type="number" name="blood_pressure_diastolic" class="form-input" min="0" max="200"
                               value="<?php echo e($formData['blood_pressure_diastolic']); ?>" placeholder="e.g., 80">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Weight (kg)</label>
                        <input type="number" name="weight" class="form-input" step="0.1" min="0" max="500"
                               value="<?php echo e($formData['weight']); ?>" placeholder="e.g., 70.5">
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
                    <textarea name="notes" class="form-input" rows="3"
                              placeholder="Clinical notes, observations, or follow-up recommendations..."><?php echo e($formData['notes']); ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="form-actions">
            <a href="<?php echo baseUrl('/lab-results'); ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i>
                Save Lab Result
            </button>
        </div>
    </form>
</div>

<?php include BASE_PATH . '/includes/layout/footer.php'; ?>
