<?php
/**
 * DiabetaCare - Edit Lab Result Page
 * 
 * Backend expects: test_name, test_date, result_value, unit, reference_range, status, notes
 */

$labResultId = (int) get('id');
if (!$labResultId) {
    setFlash('error', 'Lab result ID is required.');
    redirect('/lab-results');
}

// Fetch existing lab result
$response = api()->getLabResult($labResultId);
if (!safeGet($response, 'success', false)) {
    $errorMsg = safeGet($response, 'error.message', safeStr($response, 'message', 'Lab result not found.'));
    setFlash('error', $errorMsg);
    redirect('/lab-results');
}

$labResult = $response;
$pageTitle = 'Edit Lab Result';

$errors = [];
$formData = [
    'patient_id' => safeInt($labResult, 'patient_id'),
    'test_name' => safeStr($labResult, 'test_name', 'HbA1c'),
    'test_date' => safeStr($labResult, 'test_date', ''),
    'result_value' => safeStr($labResult, 'result_value', ''),
    'unit' => safeStr($labResult, 'unit', ''),
    'reference_range' => safeStr($labResult, 'reference_range', ''),
    'status' => safeStr($labResult, 'status', 'Normal'),
    'notes' => safeStr($labResult, 'notes', ''),
];

// Standard test references for auto-population
$testReferences = [
    'HbA1c' => ['unit' => '%', 'reference_range' => '< 7.0'],
    'Fasting Glucose' => ['unit' => 'mg/dL', 'reference_range' => '70-100'],
    'Random Glucose' => ['unit' => 'mg/dL', 'reference_range' => '< 140'],
    'Post-meal Glucose' => ['unit' => 'mg/dL', 'reference_range' => '< 180'],
    'Creatinine' => ['unit' => 'mg/dL', 'reference_range' => '0.7-1.3'],
    'eGFR' => ['unit' => 'mL/min/1.73m²', 'reference_range' => '> 90'],
    'Total Cholesterol' => ['unit' => 'mg/dL', 'reference_range' => '< 200'],
    'LDL Cholesterol' => ['unit' => 'mg/dL', 'reference_range' => '< 100'],
    'HDL Cholesterol' => ['unit' => 'mg/dL', 'reference_range' => '> 40'],
    'Triglycerides' => ['unit' => 'mg/dL', 'reference_range' => '< 150'],
];

// Fetch patients for dropdown (using lightweight endpoint)
$patientsResponse = api()->getPatientList();
$patients = safeGet($patientsResponse, 'data', []);

// Handle form submission
if (isPost()) {
    if (!validateCsrfToken(post('csrf_token'))) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $formData = array_merge($formData, [
            'patient_id' => (int) post('patient_id'),
            'test_name' => trim(post('test_name', '')),
            'test_date' => post('test_date', ''),
            'result_value' => trim(post('result_value', '')),
            'unit' => trim(post('unit', '')),
            'reference_range' => trim(post('reference_range', '')),
            'notes' => trim(post('notes', '')),
        ]);
        
        // Validation
        if (empty($formData['patient_id'])) {
            $errors[] = 'Please select a patient.';
        }
        if (empty($formData['test_name'])) {
            $errors[] = 'Test name is required.';
        }
        if (empty($formData['test_date'])) {
            $errors[] = 'Test date is required.';
        }
        if (empty($formData['result_value'])) {
            $errors[] = 'Result value is required.';
        }
        
        // Submit if no errors
        if (empty($errors)) {
            $response = api()->updateLabResult($labResultId, $formData);
            
            if (safeGet($response, 'success', false)) {
                setFlash('success', 'Lab result updated successfully.');
                redirect('/lab-results');
            } else {
                $errorMsg = safeGet($response, 'error.message', safeStr($response, 'message', 'Failed to update lab result.'));
                $errors[] = $errorMsg;
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
            <a href="<?php echo baseUrl('/lab-results'); ?>" class="btn btn-icon btn-outline">
                <i data-lucide="arrow-left"></i>
            </a>
            <div>
                <h1 class="page-title mb-0">Edit Lab Result</h1>
                <p class="page-subtitle"><?php echo formatDate(safeStr($labResult, 'test_date', ''), 'M j, Y'); ?> - <?php echo e(safeStr($labResult, 'test_name', 'Test')); ?></p>
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
    
    <form method="POST" action="<?php echo baseUrl('/lab-results/edit?id=' . $labResultId); ?>">
        <?php echo csrfField(); ?>
        
        <!-- Test Information -->
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
                            <?php foreach ($patients as $patient): 
                                $ptId = safeInt($patient, 'id');
                                $ptFirstName = safeStr($patient, 'first_name', '');
                                $ptLastName = safeStr($patient, 'last_name', '');
                                $ptCode = safeStr($patient, 'patient_code', '');
                                $ptStatus = safeStr($patient, 'status', 'Active');
                                $statusIndicator = $ptStatus !== 'Active' ? ' [' . $ptStatus . ']' : '';
                            ?>
                            <option value="<?php echo $ptId; ?>" 
                                    <?php echo $formData['patient_id'] == $ptId ? 'selected' : ''; ?>>
                                <?php echo e($ptFirstName . ' ' . $ptLastName . $statusIndicator); ?> 
                                (<?php echo e($ptCode); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Test Name</label>
                        <select name="test_name" id="test_name" class="form-select" required>
                            <option value="">Select Test</option>
                            <?php 
                            $testOptions = ['HbA1c', 'Fasting Glucose', 'Random Glucose', 'Post-meal Glucose', 'Creatinine', 'eGFR', 'Total Cholesterol', 'LDL Cholesterol', 'HDL Cholesterol', 'Triglycerides'];
                            foreach ($testOptions as $test): ?>
                            <option value="<?php echo e($test); ?>" <?php echo $formData['test_name'] === $test ? 'selected' : ''; ?>><?php echo e($test); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Test Date</label>
                        <input type="date" name="test_date" class="form-input" 
                               value="<?php echo e($formData['test_date']); ?>" required>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Test Result -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i data-lucide="clipboard-list"></i>
                    Test Result
                </h3>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label required">Result Value</label>
                        <input type="text" name="result_value" class="form-input" 
                               value="<?php echo e($formData['result_value']); ?>" placeholder="e.g., 6.5" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unit</label>
                        <input type="text" name="unit" id="unit" class="form-input" 
                               value="<?php echo e($formData['unit']); ?>" placeholder="e.g., %">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reference Range</label>
                        <input type="text" name="reference_range" id="reference_range" class="form-input" 
                               value="<?php echo e($formData['reference_range']); ?>" placeholder="e.g., < 7.0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Current Status</label>
                        <div class="form-static">
                            <span class="badge <?php echo e(getStatusBadgeClass($formData['status'])); ?>">
                                <?php echo e($formData['status']); ?>
                            </span>
                            <small class="form-hint" style="display: block; margin-top: 0.5rem;">Auto-calculated based on result and reference range</small>
                        </div>
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
                    <textarea name="notes" class="form-input" rows="3" placeholder="Optional notes..."><?php echo e($formData['notes']); ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="form-actions">
            <a href="<?php echo baseUrl('/lab-results'); ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i>
                Update Lab Result
            </button>
        </div>
    </form>
    
    <script>
    // Auto-populate unit and reference range when test is selected
    const testReferences = <?php echo json_encode($testReferences); ?>;
    document.getElementById('test_name').addEventListener('change', function() {
        const test = this.value;
        if (testReferences[test]) {
            document.getElementById('unit').value = testReferences[test].unit;
            document.getElementById('reference_range').value = testReferences[test].reference_range;
        }
    });
    </script>
</div>

<?php include BASE_PATH . '/includes/layout/footer.php'; ?>
