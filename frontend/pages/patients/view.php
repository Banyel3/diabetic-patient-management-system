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
if (!safeGet($response, 'success', false)) {
    setFlash('error', safeGet($response, 'error.message', 'Patient not found.'));
    redirect('/patients');
}

$patient = $response;
$firstName = safeStr($patient, 'first_name', '');
$lastName = safeStr($patient, 'last_name', '');
$pageTitle = trim("$firstName $lastName") ?: 'Patient Details';

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
    $labResults = safeGet($labResponse, 'success') ? safeGet($labResponse, 'items', []) : [];
}
if ($activeTab === 'medications') {
    $medResponse = api()->getMedications(['patient_id' => $patientId, 'page_size' => 20]);
    $medications = safeGet($medResponse, 'success') ? safeGet($medResponse, 'items', []) : [];
}
if ($activeTab === 'appointments') {
    $apptResponse = api()->getAppointments(['patient_id' => $patientId, 'page_size' => 20]);
    $appointments = safeGet($apptResponse, 'success') ? safeGet($apptResponse, 'items', []) : [];
}

// Calculate age safely
$dob = safeStr($patient, 'date_of_birth', '');
$age = $dob ? (new DateTime())->diff(new DateTime($dob))->y : 'N/A';

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
                    <?php echo e(getInitials($firstName, $lastName)); ?>
                </div>
                <div>
                    <?php 
                        $patientCode = safeStr($patient, 'patient_code', '');
                        $diabetesType = safeStr($patient, 'diabetes_type', 'Unknown');
                        $status = safeStr($patient, 'status', 'Active');
                    ?>
                    <h1 class="page-title mb-0"><?php echo e($firstName . ' ' . $lastName); ?></h1>
                    <div class="flex items-center gap-3 mt-1">
                        <span class="text-sm" style="color: var(--text-muted);"><?php echo e($patientCode); ?></span>
                        <span class="badge <?php echo e(getDiabetesTypeBadgeClass($diabetesType)); ?>">
                            <?php echo e($diabetesType); ?>
                        </span>
                        <span class="badge <?php echo e(getStatusBadgeClass($status)); ?>">
                            <?php echo e($status); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?php echo baseUrl('/patients/' . safeInt($patient, 'id') . '/edit'); ?>" class="btn btn-outline">
                <i data-lucide="edit-2"></i>
                Edit
            </a>
            <a href="<?php echo baseUrl('/lab-results/create?patient_id=' . safeInt($patient, 'id')); ?>" class="btn btn-primary">
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
    <?php
        // Extract all patient fields safely
        $gender = safeStr($patient, 'gender', 'N/A');
        $phone = safeStr($patient, 'phone', 'N/A');
        $email = safeStr($patient, 'email', 'N/A');
        $address = safeStr($patient, 'address', 'N/A');
        $diagnosisDate = safeStr($patient, 'diagnosis_date', '');
        $lastHba1c = safeFloat($patient, 'last_hba1c');
        $targetHba1c = safeFloat($patient, 'target_hba1c');
        $bloodPressure = safeStr($patient, 'blood_pressure', 'N/A');
        $bmi = safeFloat($patient, 'bmi');
        $emergencyName = safeStr($patient, 'emergency_contact_name', 'N/A');
        $emergencyRelation = safeStr($patient, 'emergency_contact_relation', 'N/A');
        $emergencyPhone = safeStr($patient, 'emergency_contact_phone', 'N/A');
        $medicalHistory = safeStr($patient, 'medical_history', 'No medical history recorded.');
        $notes = safeStr($patient, 'notes', 'No notes recorded.');
    ?>
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
                        <p><?php echo e($firstName . ' ' . $lastName); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Date of Birth</label>
                        <p><?php echo formatDate($dob, 'M j, Y'); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Age</label>
                        <p><?php echo e($age); ?> years</p>
                    </div>
                    <div class="info-item">
                        <label>Gender</label>
                        <p><?php echo e($gender); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Phone</label>
                        <p><?php echo e($phone); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Email</label>
                        <p><?php echo e($email); ?></p>
                    </div>
                    <div class="info-item full-width">
                        <label>Address</label>
                        <p><?php echo e($address); ?></p>
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
                        <span class="badge <?php echo e(getDiabetesTypeBadgeClass($diabetesType)); ?>">
                            <?php echo e($diabetesType); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <label>Diagnosis Date</label>
                        <p><?php echo formatDate($diagnosisDate, 'M j, Y'); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Last HbA1c</label>
                        <p class="<?php echo e(getHbA1cColorClass($lastHba1c)); ?> font-semibold">
                            <?php echo $lastHba1c !== null ? e($lastHba1c) . '%' : 'N/A'; ?>
                        </p>
                    </div>
                    <div class="info-item">
                        <label>Target HbA1c</label>
                        <p><?php echo $targetHba1c !== null ? e($targetHba1c) . '%' : '< 7%'; ?></p>
                    </div>
                    <div class="info-item">
                        <label>Blood Pressure</label>
                        <p><?php echo e($bloodPressure); ?></p>
                    </div>
                    <div class="info-item">
                        <label>BMI</label>
                        <p><?php echo $bmi !== null ? e($bmi) . ' kg/m²' : 'N/A'; ?></p>
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
                        <p><?php echo e($emergencyName); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Relationship</label>
                        <p><?php echo e($emergencyRelation); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Phone</label>
                        <p><?php echo e($emergencyPhone); ?></p>
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
                <p style="white-space: pre-line;"><?php echo e($medicalHistory); ?></p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Notes</h3>
            </div>
            <div class="card-body">
                <p style="white-space: pre-line;"><?php echo e($notes); ?></p>
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
                    <?php foreach ($labResults as $lab): 
                        $labId = safeInt($lab, 'id');
                        $labDate = safeStr($lab, 'test_date', '');
                        $labType = safeStr($lab, 'test_type', 'General');
                        $labValue = safeStr($lab, 'value', 'N/A');
                        $labHba1c = safeFloat($lab, 'hba1c');
                        $labFasting = safeFloat($lab, 'fasting_glucose');
                    ?>
                    <tr>
                        <td><?php echo formatDate($labDate); ?></td>
                        <td><?php echo e($labType); ?></td>
                        <td><?php echo e($labValue); ?></td>
                        <td>
                            <span class="<?php echo e(getHbA1cColorClass($labHba1c)); ?> font-semibold">
                                <?php echo $labHba1c !== null ? e($labHba1c) . '%' : 'N/A'; ?>
                            </span>
                        </td>
                        <td><?php echo $labFasting !== null ? e($labFasting) . ' mg/dL' : 'N/A'; ?></td>
                        <td>
                            <div class="table-actions">
                                <a href="<?php echo baseUrl('/lab-results/view?id=' . $labId); ?>" class="table-action-btn" title="View">
                                    <i data-lucide="eye"></i>
                                </a>
                                <a href="<?php echo baseUrl('/lab-results/edit?id=' . $labId); ?>" class="table-action-btn" title="Edit">
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
                    <?php foreach ($medications as $med): 
                        $medId = safeInt($med, 'id');
                        $medName = safeStr($med, 'name', 'N/A');
                        $medDosage = safeStr($med, 'dosage', 'N/A');
                        $medFrequency = safeStr($med, 'frequency', 'N/A');
                        $medStartDate = safeStr($med, 'start_date', '');
                        $medStatusVal = safeStr($med, 'status', 'Active');
                        $medIsActive = strtolower($medStatusVal) === 'active';
                    ?>
                    <tr>
                        <td>
                            <p class="font-medium"><?php echo e($medName); ?></p>
                        </td>
                        <td><?php echo e($medDosage); ?></td>
                        <td><?php echo e($medFrequency); ?></td>
                        <td><?php echo formatDate($medStartDate); ?></td>
                        <td>
                            <span class="badge <?php echo $medIsActive ? 'badge-success' : 'badge-secondary'; ?>">
                                <?php echo $medIsActive ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="<?php echo baseUrl('/medications/view?id=' . $medId); ?>" class="table-action-btn" title="View">
                                    <i data-lucide="eye"></i>
                                </a>
                                <a href="<?php echo baseUrl('/medications/edit?id=' . $medId); ?>" class="table-action-btn" title="Edit">
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
                        <th>Duration</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appt): 
                        $apptId = safeInt($appt, 'id');
                        $apptDate = safeStr($appt, 'date', '');
                        $apptTime = safeStr($appt, 'time', '');
                        $apptType = safeStr($appt, 'type', 'Check-up');
                        $apptDuration = safeInt($appt, 'duration_minutes', 30);
                        $apptStatus = safeStr($appt, 'status', 'scheduled');
                    ?>
                    <tr>
                        <td>
                            <p class="font-medium"><?php echo formatDate($apptDate, 'M j, Y'); ?></p>
                            <p class="text-sm" style="color: var(--text-muted);"><?php echo formatTime($apptTime); ?></p>
                        </td>
                        <td><?php echo e($apptType); ?></td>
                        <td><?php echo e($apptDuration); ?> min</td>
                        <td>
                            <span class="badge <?php echo e(getAppointmentStatusBadgeClass($apptStatus)); ?>">
                                <?php echo e($apptStatus); ?>
                            </span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="<?php echo baseUrl('/appointments/view?id=' . $apptId); ?>" class="table-action-btn" title="View">
                                    <i data-lucide="eye"></i>
                                </a>
                                <a href="<?php echo baseUrl('/appointments/edit?id=' . $apptId); ?>" class="table-action-btn" title="Edit">
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
