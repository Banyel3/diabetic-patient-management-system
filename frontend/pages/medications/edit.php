<?php
/**
 * DiabetaCare - Edit Medication Page
 */

$medicationId = (int) get('id');
if (!$medicationId) {
    setFlash('error', 'Medication ID is required.');
    redirect('/medications');
}

// Fetch existing medication
$response = api()->getMedication($medicationId);
if (!safeGet($response, 'success', false)) {
    $errorMsg = safeGet($response, 'error.message', safeStr($response, 'message', 'Medication not found.'));
    setFlash('error', $errorMsg);
    redirect('/medications');
}

$medication = $response;
$medName = safeStr($medication, 'name', '');
$pageTitle = 'Edit Medication';

$errors = [];
$formData = [
    'patient_id' => safeInt($medication, 'patient_id'),
    'name' => $medName,
    'dosage' => safeStr($medication, 'dosage', ''),
    'frequency' => safeStr($medication, 'frequency', 'Once daily'),
    'route' => safeStr($medication, 'route', 'Oral'),
    'start_date' => safeStr($medication, 'start_date', ''),
    'end_date' => safeStr($medication, 'end_date', ''),
    'prescribing_doctor' => safeStr($medication, 'prescribing_doctor', ''),
    'status' => safeStr($medication, 'status', 'active'),
    'notes' => safeStr($medication, 'notes', ''),
];

// Fetch patients for dropdown
$patientsResponse = api()->getPatients(['page_size' => 100]);
$patients = safeGet($patientsResponse, 'success', false) ? safeGet($patientsResponse, 'items', []) : [];

// Handle form submission
if (isPost()) {
    if (!validateCsrfToken(post('csrf_token'))) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $formData = array_merge($formData, [
            'patient_id' => (int) post('patient_id'),
            'name' => trim(post('name', '')),
            'dosage' => trim(post('dosage', '')),
            'frequency' => post('frequency', 'Once daily'),
            'route' => post('route', 'Oral'),
            'start_date' => post('start_date', ''),
            'end_date' => post('end_date', ''),
            'prescribing_doctor' => trim(post('prescribing_doctor', '')),
            'status' => post('status', 'active'),
            'notes' => trim(post('notes', '')),
        ]);
        
        // Validation
        if (empty($formData['patient_id'])) {
            $errors[] = 'Please select a patient.';
        }
        if (empty($formData['name'])) {
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
            $response = api()->updateMedication($medicationId, $formData);
            
            if (safeGet($response, 'success', false)) {
                setFlash('success', 'Medication updated successfully.');
                redirect('/medications');
            } else {
                $errorMsg = safeGet($response, 'error.message', safeStr($response, 'message', 'Failed to update medication.'));
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
            <a href="<?php echo baseUrl('/medications'); ?>" class="btn btn-icon btn-outline">
                <i data-lucide="arrow-left"></i>
            </a>
            <div>
                <h1 class="page-title mb-0">Edit Medication</h1>
                <p class="page-subtitle"><?php echo e($medName); ?></p>
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
    
    <form method="POST" action="<?php echo baseUrl('/medications/edit?id=' . $medicationId); ?>">
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
                            <?php foreach ($patients as $patient): 
                                $ptId = safeInt($patient, 'id');
                                $ptFirstName = safeStr($patient, 'first_name', '');
                                $ptLastName = safeStr($patient, 'last_name', '');
                                $ptCode = safeStr($patient, 'patient_code', '');
                            ?>
                            <option value="<?php echo $ptId; ?>" 
                                    <?php echo $formData['patient_id'] == $ptId ? 'selected' : ''; ?>>
                                <?php echo e($ptFirstName . ' ' . $ptLastName); ?> 
                                (<?php echo e($ptCode); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Medication Name</label>
                        <input type="text" name="name" class="form-input" 
                               value="<?php echo e($formData['name']); ?>" 
                               placeholder="e.g., Metformin" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Route</label>
                        <select name="route" class="form-select">
                            <option value="Oral" <?php echo $formData['route'] === 'Oral' ? 'selected' : ''; ?>>Oral</option>
                            <option value="Subcutaneous" <?php echo $formData['route'] === 'Subcutaneous' ? 'selected' : ''; ?>>Subcutaneous</option>
                            <option value="Intramuscular" <?php echo $formData['route'] === 'Intramuscular' ? 'selected' : ''; ?>>Intramuscular</option>
                            <option value="Intravenous" <?php echo $formData['route'] === 'Intravenous' ? 'selected' : ''; ?>>Intravenous</option>
                            <option value="Topical" <?php echo $formData['route'] === 'Topical' ? 'selected' : ''; ?>>Topical</option>
                            <option value="Inhalation" <?php echo $formData['route'] === 'Inhalation' ? 'selected' : ''; ?>>Inhalation</option>
                            <option value="Sublingual" <?php echo $formData['route'] === 'Sublingual' ? 'selected' : ''; ?>>Sublingual</option>
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
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?php echo $formData['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="discontinued" <?php echo $formData['status'] === 'discontinued' ? 'selected' : ''; ?>>Discontinued</option>
                            <option value="completed" <?php echo $formData['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-input" rows="2"
                                  placeholder="Additional notes..."><?php echo e($formData['notes']); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="form-actions">
            <a href="<?php echo baseUrl('/medications'); ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i>
                Update Medication
            </button>
        </div>
    </form>
</div>

<?php include BASE_PATH . '/includes/layout/footer.php'; ?>
