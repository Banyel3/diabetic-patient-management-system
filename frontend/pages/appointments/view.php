<?php
/**
 * DiabetaCare - Appointment Detail View Page
 */

$appointmentId = (int) get('id');
if (!$appointmentId) {
    setFlash('error', 'Appointment ID is required.');
    redirect('/appointments');
}

// Fetch appointment data
$response = api()->getAppointment($appointmentId);
if (!safeGet($response, 'success', false)) {
    $errorMsg = safeGet($response, 'error.message', safeStr($response, 'message', 'Appointment not found.'));
    setFlash('error', $errorMsg);
    redirect('/appointments');
}

$appointment = $response;
$pageTitle = 'Appointment Details';

// Extract appointment fields safely - using backend field names
$apptDate = safeStr($appointment, 'date', '');
$apptTime = safeStr($appointment, 'time', '');
$apptType = safeStr($appointment, 'type', 'N/A');
$apptStatus = safeStr($appointment, 'status', 'Unknown');
$apptPatientId = safeInt($appointment, 'patient_id');
$apptPatientName = safeStr($appointment, 'patient_name', 'Unknown');
$apptDurationMinutes = safeInt($appointment, 'duration_minutes', 30);
$apptNotes = safeStr($appointment, 'notes', '');

// Get status badge class
function getAppointmentStatusBadgeClass($status): string {
    return match(strtolower($status ?? '')) {
        'scheduled' => 'badge-info',
        'completed' => 'badge-success',
        'cancelled' => 'badge-danger',
        'no-show' => 'badge-warning',
        default => 'badge-secondary'
    };
}

$successMessage = getFlash('success');
$errorMessage = getFlash('error');

include BASE_PATH . '/includes/layout/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
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
            <a href="<?php echo baseUrl('/appointments'); ?>" class="btn btn-icon btn-outline">
                <i data-lucide="arrow-left"></i>
            </a>
            <div>
                <h1 class="page-title mb-0"><?php echo e($pageTitle); ?></h1>
                <p class="text-muted">
                    <?php echo formatDate($apptDate, 'l, F j, Y'); ?>
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?php echo baseUrl('/appointments/edit?id=' . $appointmentId); ?>" class="btn btn-primary">
                <i data-lucide="edit-2"></i>
                Edit Appointment
            </a>
        </div>
    </div>
    
    <!-- Appointment Details Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Appointment Information</h3>
            <span class="badge <?php echo e(getAppointmentStatusBadgeClass($apptStatus)); ?>">
                <?php echo e($apptStatus); ?>
            </span>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item">
                    <label>Patient</label>
                    <p>
                        <a href="<?php echo baseUrl('/patients/view?id=' . $apptPatientId); ?>" class="text-primary">
                            <?php echo e($apptPatientName); ?>
                        </a>
                    </p>
                </div>
                <div class="info-item">
                    <label>Date</label>
                    <p><?php echo formatDate($apptDate, 'M j, Y'); ?></p>
                </div>
                <div class="info-item">
                    <label>Time</label>
                    <p><?php echo formatTime($apptTime); ?></p>
                </div>
                <div class="info-item">
                    <label>Type</label>
                    <p><?php echo e($apptType); ?></p>
                </div>
                <div class="info-item">
                    <label>Duration</label>
                    <p><?php echo e($apptDurationMinutes); ?> minutes</p>
                </div>
            </div>
            
            <?php if (!empty($apptNotes)): ?>
            <div class="info-item mt-4">
                <label>Notes</label>
                <p><?php echo nl2br(e($apptNotes)); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

.info-item label {
    display: block;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.25rem;
}

.info-item p {
    margin: 0;
    color: var(--text-primary);
}

@media (max-width: 640px) {
    .info-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include BASE_PATH . '/includes/layout/footer.php'; ?>
