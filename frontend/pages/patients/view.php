<?php
/**
 * DiabetaCare - Patient Detail View Page
 */

$patientId = (int) get('id');
if (!$patientId) {
    setFlash('error', 'Patient ID is required.');
    redirect('/patients');
}

// Fetch patient data
$response = api()->getPatient($patientId);
if (!$response['success']) {
    setFlash('error', $response['error']['message'] ?? 'Patient not found.');
    redirect('/patients');
}

$patient = $response;
$pageTitle = $patient['first_name'] . ' ' . $patient['last_name'];

// Get active tab
$activeTab = get('tab', 'overview');
$validTabs = ['overview', 'lab-results', 'medications', 'appointments'];
if (!in_array($activeTab, $validTabs)) {
    $activeTab = 'overview';
}

// Fetch related data based on tab
$labResults = [];
$medications = [];
$appointments = [];

if ($activeTab === 'lab-results') {
    $labResponse = api()->getLabResults(['patient_id' => $patientId, 'page_size' => 20]);
    $labResults = $labResponse['success'] ? ($labResponse['items'] ?? []) : [];
}
if ($activeTab === 'medications') {
    $medResponse = api()->getMedications(['patient_id' => $patientId, 'page_size' => 20]);
    $medications = $medResponse['success'] ? ($medResponse['items'] ?? []) : [];
}
if ($activeTab === 'appointments') {
    $apptResponse = api()->getAppointments(['patient_id' => $patientId, 'page_size' => 20]);
    $appointments = $apptResponse['success'] ? ($apptResponse['items'] ?? []) : [];
}

// Calculate age
$age = isset($patient['date_of_birth']) ? (new DateTime())->diff(new DateTime($patient['date_of_birth']))->y : 'N/A';

$successMessage = getFlash('success');
$errorMessage = getFlash('error');

include BASE_PATH . '/includes/layout/header.php';
?>

<div style="max-width: 1400px;">
    <?php if ($successMessage): ?>
    <div class="alert alert-success">
        <i data-lucide="check-circle"></i>
        <span><?php echo e($successMessage); ?></span>
    </div>
    <?php endif; ?>
    
    <?php if ($errorMessage): ?>
    <div class="alert alert-danger">
        <i data-lucide="alert-circle"></i>
        <span><?php echo e($errorMessage); ?></span>
    </div>
    <?php endif; ?>
    
    <!-- Header -->
    <div class="page-header">
        <div class="flex items-center gap-4">
            <a href="<?php echo baseUrl('/patients'); ?>" class="btn btn-icon btn-outline">
                <i data-lucide="arrow-left"></i>
            </a>
            <div class="flex items-center gap-4">
                <div class="avatar avatar-xl">
                    <?php echo e(getInitials($patient['first_name'], $patient['last_name'])); ?>
                </div>
                <div>
                    <h1 class="page-title mb-0"><?php echo e($patient['first_name'] . ' ' . $patient['last_name']); ?></h1>
                    <div class="flex items-center gap-3 mt-1">
                        <span class="text-sm" style="color: var(--text-muted);"><?php echo e($patient['patient_code']); ?></span>
                        <span class="badge <?php echo e(getDiabetesTypeBadgeClass($patient['diabetes_type'])); ?>">
                            <?php echo e($patient['diabetes_type']); ?>
                        </span>
                        <span class="badge <?php echo e(getStatusBadgeClass($patient['status'])); ?>">
                            <?php echo e($patient['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?php echo baseUrl('/patients/edit?id=' . $patient['id']); ?>" class="btn btn-outline">
                <i data-lucide="edit-2"></i>
                Edit
            </a>
            <a href="<?php echo baseUrl('/lab-results/create?patient_id=' . $patient['id']); ?>" class="btn btn-primary">
                <i data-lucide="plus"></i>
                Add Lab Result
            </a>
        </div>
    </div>
    
    <!-- Tab Navigation -->
    <div class="tabs">
        <a href="<?php echo baseUrl('/patients/view?id=' . $patientId . '&tab=overview'); ?>" 
           class="tab <?php echo $activeTab === 'overview' ? 'active' : ''; ?>">
            <i data-lucide="user"></i> Overview
        </a>
        <a href="<?php echo baseUrl('/patients/view?id=' . $patientId . '&tab=lab-results'); ?>" 
           class="tab <?php echo $activeTab === 'lab-results' ? 'active' : ''; ?>">
            <i data-lucide="flask-conical"></i> Lab Results
        </a>
        <a href="<?php echo baseUrl('/patients/view?id=' . $patientId . '&tab=medications'); ?>" 
           class="tab <?php echo $activeTab === 'medications' ? 'active' : ''; ?>">
            <i data-lucide="pill"></i> Medications
        </a>
        <a href="<?php echo baseUrl('/patients/view?id=' . $patientId . '&tab=appointments'); ?>" 
           class="tab <?php echo $activeTab === 'appointments' ? 'active' : ''; ?>">
            <i data-lucide="calendar"></i> Appointments
        </a>
    </div>
    
    <!-- Tab Content -->
    <?php if ($activeTab === 'overview'): ?>
    <div class="grid grid-3">
        <!-- Personal Information Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Personal Information</h3>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <label>Full Name</label>
                        <p><?php echo e($patient['first_name'] . ' ' . $patient['last_name']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Date of Birth</label>
                        <p><?php echo formatDate($patient['date_of_birth'] ?? '', 'M j, Y'); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Age</label>
                        <p><?php echo e($age); ?> years</p>
                    </div>
                    <div class="info-item">
                        <label>Gender</label>
                        <p><?php echo e($patient['gender'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Phone</label>
                        <p><?php echo e($patient['phone'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Email</label>
                        <p><?php echo e($patient['email'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="info-item full-width">
                        <label>Address</label>
                        <p><?php echo e($patient['address'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Medical Information Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Medical Information</h3>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <label>Diabetes Type</label>
                        <span class="badge <?php echo e(getDiabetesTypeBadgeClass($patient['diabetes_type'])); ?>">
                            <?php echo e($patient['diabetes_type']); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <label>Diagnosis Date</label>
                        <p><?php echo formatDate($patient['diagnosis_date'] ?? '', 'M j, Y'); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Last HbA1c</label>
                        <p class="<?php echo e(getHbA1cColorClass($patient['last_hba1c'])); ?> font-semibold">
                            <?php echo $patient['last_hba1c'] !== null ? e($patient['last_hba1c']) . '%' : 'N/A'; ?>
                        </p>
                    </div>
                    <div class="info-item">
                        <label>Target HbA1c</label>
                        <p><?php echo isset($patient['target_hba1c']) ? e($patient['target_hba1c']) . '%' : '< 7%'; ?></p>
                    </div>
                    <div class="info-item">
                        <label>Blood Pressure</label>
                        <p><?php echo e($patient['blood_pressure'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="info-item">
                        <label>BMI</label>
                        <p><?php echo isset($patient['bmi']) ? e($patient['bmi']) . ' kg/m²' : 'N/A'; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Emergency Contact Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Emergency Contact</h3>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <label>Contact Name</label>
                        <p><?php echo e($patient['emergency_contact_name'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Relationship</label>
                        <p><?php echo e($patient['emergency_contact_relation'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Phone</label>
                        <p><?php echo e($patient['emergency_contact_phone'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Medical History Section -->
    <div class="grid grid-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Medical History</h3>
            </div>
            <div class="card-body">
                <p style="white-space: pre-line;"><?php echo e($patient['medical_history'] ?? 'No medical history recorded.'); ?></p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Notes</h3>
            </div>
            <div class="card-body">
                <p style="white-space: pre-line;"><?php echo e($patient['notes'] ?? 'No notes recorded.'); ?></p>
            </div>
        </div>
    </div>
    
    <?php elseif ($activeTab === 'lab-results'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Lab Results History</h3>
            <a href="<?php echo baseUrl('/lab-results/create?patient_id=' . $patientId); ?>" class="btn btn-primary btn-sm">
                <i data-lucide="plus"></i> Add Lab Result
            </a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($labResults)): ?>
            <div class="empty-state">
                <i data-lucide="flask-conical"></i>
                <p>No lab results recorded yet</p>
                <a href="<?php echo baseUrl('/lab-results/create?patient_id=' . $patientId); ?>">Add first lab result</a>
            </div>
            <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Test Type</th>
                        <th>Value</th>
                        <th>HbA1c</th>
                        <th>Fasting Glucose</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($labResults as $lab): ?>
                    <tr>
                        <td><?php echo formatDate($lab['test_date']); ?></td>
                        <td><?php echo e($lab['test_type'] ?? 'General'); ?></td>
                        <td><?php echo e($lab['value'] ?? 'N/A'); ?></td>
                        <td>
                            <span class="<?php echo e(getHbA1cColorClass($lab['hba1c'])); ?> font-semibold">
                                <?php echo $lab['hba1c'] !== null ? e($lab['hba1c']) . '%' : 'N/A'; ?>
                            </span>
                        </td>
                        <td><?php echo $lab['fasting_glucose'] !== null ? e($lab['fasting_glucose']) . ' mg/dL' : 'N/A'; ?></td>
                        <td>
                            <div class="table-actions">
                                <a href="<?php echo baseUrl('/lab-results/view?id=' . $lab['id']); ?>" class="table-action-btn" title="View">
                                    <i data-lucide="eye"></i>
                                </a>
                                <a href="<?php echo baseUrl('/lab-results/edit?id=' . $lab['id']); ?>" class="table-action-btn" title="Edit">
                                    <i data-lucide="edit-2"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
    
    <?php elseif ($activeTab === 'medications'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Current Medications</h3>
            <a href="<?php echo baseUrl('/medications/create?patient_id=' . $patientId); ?>" class="btn btn-primary btn-sm">
                <i data-lucide="plus"></i> Add Medication
            </a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($medications)): ?>
            <div class="empty-state">
                <i data-lucide="pill"></i>
                <p>No medications recorded yet</p>
                <a href="<?php echo baseUrl('/medications/create?patient_id=' . $patientId); ?>">Add first medication</a>
            </div>
            <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Medication</th>
                        <th>Dosage</th>
                        <th>Frequency</th>
                        <th>Start Date</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($medications as $med): ?>
                    <tr>
                        <td>
                            <p class="font-medium"><?php echo e($med['medication_name']); ?></p>
                            <p class="text-xs" style="color: var(--text-muted);"><?php echo e($med['category'] ?? ''); ?></p>
                        </td>
                        <td><?php echo e($med['dosage']); ?></td>
                        <td><?php echo e($med['frequency']); ?></td>
                        <td><?php echo formatDate($med['start_date']); ?></td>
                        <td>
                            <span class="badge <?php echo $med['is_active'] ? 'badge-success' : 'badge-secondary'; ?>">
                                <?php echo $med['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="<?php echo baseUrl('/medications/view?id=' . $med['id']); ?>" class="table-action-btn" title="View">
                                    <i data-lucide="eye"></i>
                                </a>
                                <a href="<?php echo baseUrl('/medications/edit?id=' . $med['id']); ?>" class="table-action-btn" title="Edit">
                                    <i data-lucide="edit-2"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
    
    <?php elseif ($activeTab === 'appointments'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Appointments</h3>
            <a href="<?php echo baseUrl('/appointments/create?patient_id=' . $patientId); ?>" class="btn btn-primary btn-sm">
                <i data-lucide="plus"></i> Schedule Appointment
            </a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($appointments)): ?>
            <div class="empty-state">
                <i data-lucide="calendar"></i>
                <p>No appointments scheduled</p>
                <a href="<?php echo baseUrl('/appointments/create?patient_id=' . $patientId); ?>">Schedule first appointment</a>
            </div>
            <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Type</th>
                        <th>Provider</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appt): ?>
                    <tr>
                        <td>
                            <p class="font-medium"><?php echo formatDate($appt['appointment_date'], 'M j, Y'); ?></p>
                            <p class="text-sm" style="color: var(--text-muted);"><?php echo formatDate($appt['appointment_time'], 'g:i A'); ?></p>
                        </td>
                        <td><?php echo e($appt['appointment_type'] ?? 'Check-up'); ?></td>
                        <td><?php echo e($appt['provider_name'] ?? 'N/A'); ?></td>
                        <td>
                            <span class="badge <?php echo e(getAppointmentStatusBadgeClass($appt['status'])); ?>">
                                <?php echo e($appt['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="<?php echo baseUrl('/appointments/view?id=' . $appt['id']); ?>" class="table-action-btn" title="View">
                                    <i data-lucide="eye"></i>
                                </a>
                                <a href="<?php echo baseUrl('/appointments/edit?id=' . $appt['id']); ?>" class="table-action-btn" title="Edit">
                                    <i data-lucide="edit-2"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include BASE_PATH . '/includes/layout/footer.php'; ?>
