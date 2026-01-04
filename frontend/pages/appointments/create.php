<?php
/**
 * DiabetaCare - Create Appointment Page
 */

$pageTitle = 'New Appointment';

// Get patient_id if pre-selected
$preSelectedPatientId = get('patient_id', '');

$errors = [];
$formData = [
    'patient_id' => $preSelectedPatientId,
    'appointment_date' => date('Y-m-d'),
    'appointment_time' => '09:00',
    'type' => 'Check-up',
    'duration_minutes' => 30,
    'notes' => '',
    'status' => 'Scheduled',
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
            'appointment_date' => post('appointment_date', ''),
            'appointment_time' => post('appointment_time', ''),
            'type' => post('type', 'Check-up'),
            'duration_minutes' => (int) post('duration_minutes', 30),
            'notes' => trim(post('notes', '')),
            'status' => post('status', 'Scheduled'),
        ]);
        
        // Validation
        if (empty($formData['patient_id'])) {
            $errors[] = 'Please select a patient.';
        }
        if (empty($formData['appointment_date'])) {
            $errors[] = 'Appointment date is required.';
        }
        if (empty($formData['appointment_time'])) {
            $errors[] = 'Appointment time is required.';
        }
        
        // Submit if no errors
        if (empty($errors)) {
            // Combine date and time into scheduled_at format for backend
            $apiData = [
                'patient_id' => $formData['patient_id'],
                'scheduled_at' => $formData['appointment_date'] . ' ' . $formData['appointment_time'] . ':00',
                'type' => $formData['type'],
                'duration_minutes' => $formData['duration_minutes'],
                'status' => $formData['status'],
                'notes' => $formData['notes'],
            ];
            $response = api()->createAppointment($apiData);
            
            if (safeGet($response, 'success', false)) {
                setFlash('success', 'Appointment scheduled successfully.');
                redirect('/appointments');
            } else {
                $errorMsg = safeGet($response, 'error.message', safeStr($response, 'message', 'Failed to schedule appointment.'));
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
            <a href="<?php echo baseUrl('/appointments'); ?>" class="btn btn-icon btn-outline">
                <i data-lucide="arrow-left"></i>
            </a>
            <div>
                <h1 class="page-title mb-0">Schedule Appointment</h1>
                <p class="page-subtitle">Book a new appointment</p>
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
    
    <form method="POST" action="<?php echo baseUrl('/appointments/create' . ($preSelectedPatientId ? '?patient_id=' . $preSelectedPatientId : '')); ?>">
        <?php echo csrfField(); ?>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i data-lucide="calendar-plus"></i>
                    Appointment Details
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
                        <label class="form-label required">Date</label>
                        <input type="date" name="appointment_date" class="form-input" 
                               value="<?php echo e($formData['appointment_date']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Time</label>
                        <input type="time" name="appointment_time" class="form-input" 
                               value="<?php echo e($formData['appointment_time']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Appointment Type</label>
                        <select name="type" class="form-select">
                            <option value="Check-up" <?php echo $formData['type'] === 'Check-up' ? 'selected' : ''; ?>>Check-up</option>
                            <option value="Follow-up" <?php echo $formData['type'] === 'Follow-up' ? 'selected' : ''; ?>>Follow-up</option>
                            <option value="Lab Review" <?php echo $formData['type'] === 'Lab Review' ? 'selected' : ''; ?>>Lab Review</option>
                            <option value="Consultation" <?php echo $formData['type'] === 'Consultation' ? 'selected' : ''; ?>>Consultation</option>
                            <option value="New Patient" <?php echo $formData['type'] === 'New Patient' ? 'selected' : ''; ?>>New Patient</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Duration</label>
                        <select name="duration_minutes" class="form-select">
                            <option value="15" <?php echo $formData['duration_minutes'] === 15 ? 'selected' : ''; ?>>15 minutes</option>
                            <option value="30" <?php echo $formData['duration_minutes'] === 30 ? 'selected' : ''; ?>>30 minutes</option>
                            <option value="45" <?php echo $formData['duration_minutes'] === 45 ? 'selected' : ''; ?>>45 minutes</option>
                            <option value="60" <?php echo $formData['duration_minutes'] === 60 ? 'selected' : ''; ?>>60 minutes</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Scheduled" <?php echo $formData['status'] === 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-input" rows="3"
                                  placeholder="Reason for visit, special instructions..."><?php echo e($formData['notes']); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="form-actions">
            <a href="<?php echo baseUrl('/appointments'); ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="calendar-check"></i>
                Schedule Appointment
            </button>
        </div>
    </form>
</div>

<?php include BASE_PATH . '/includes/layout/footer.php'; ?>
