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
    'appointment_type' => 'Check-up',
    'provider_name' => '',
    'notes' => '',
    'status' => 'Scheduled',
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
            'appointment_date' => post('appointment_date', ''),
            'appointment_time' => post('appointment_time', ''),
            'appointment_type' => post('appointment_type', 'Check-up'),
            'provider_name' => trim(post('provider_name', '')),
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
            $response = api()->createAppointment($formData);
            
            if ($response['success']) {
                setFlash('success', 'Appointment scheduled successfully.');
                redirect('/appointments');
            } else {
                $errors[] = $response['error']['message'] ?? 'Failed to schedule appointment.';
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
                        <select name="appointment_type" class="form-select">
                            <option value="Check-up" <?php echo $formData['appointment_type'] === 'Check-up' ? 'selected' : ''; ?>>Check-up</option>
                            <option value="Follow-up" <?php echo $formData['appointment_type'] === 'Follow-up' ? 'selected' : ''; ?>>Follow-up</option>
                            <option value="Consultation" <?php echo $formData['appointment_type'] === 'Consultation' ? 'selected' : ''; ?>>Consultation</option>
                            <option value="Lab Work" <?php echo $formData['appointment_type'] === 'Lab Work' ? 'selected' : ''; ?>>Lab Work</option>
                            <option value="Emergency" <?php echo $formData['appointment_type'] === 'Emergency' ? 'selected' : ''; ?>>Emergency</option>
                            <option value="Telehealth" <?php echo $formData['appointment_type'] === 'Telehealth' ? 'selected' : ''; ?>>Telehealth</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Provider/Doctor</label>
                        <input type="text" name="provider_name" class="form-input" 
                               value="<?php echo e($formData['provider_name']); ?>"
                               placeholder="e.g., Dr. Smith">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Scheduled" <?php echo $formData['status'] === 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                            <option value="Confirmed" <?php echo $formData['status'] === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
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
