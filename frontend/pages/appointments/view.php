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
if (!$response['success']) {
    setFlash('error', $response['error']['message'] ?? 'Appointment not found.');
    redirect('/appointments');
}

$appointment = $response;
$pageTitle = 'Appointment Details';

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
                    <?php echo formatDate($appointment['appointment_date'] ?? '', 'l, F j, Y'); ?>
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?php echo baseUrl('/appointments/' . $appointmentId . '/edit'); ?>" class="btn btn-primary">
                <i data-lucide="edit-2"></i>
                Edit Appointment
            </a>
        </div>
    </div>
    
    <!-- Appointment Details Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Appointment Information</h3>
            <span class="badge <?php echo e(getAppointmentStatusBadgeClass($appointment['status'] ?? '')); ?>">
                <?php echo e($appointment['status'] ?? 'Unknown'); ?>
            </span>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item">
                    <label>Patient</label>
                    <p>
                        <a href="<?php echo baseUrl('/patients/' . ($appointment['patient_id'] ?? '')); ?>" class="text-primary">
                            <?php echo e(($appointment['patient_first_name'] ?? '') . ' ' . ($appointment['patient_last_name'] ?? '')); ?>
                        </a>
                    </p>
                </div>
                <div class="info-item">
                    <label>Date</label>
                    <p><?php echo formatDate($appointment['appointment_date'] ?? '', 'M j, Y'); ?></p>
                </div>
                <div class="info-item">
                    <label>Time</label>
                    <p><?php echo formatTime($appointment['appointment_time'] ?? ''); ?></p>
                </div>
                <div class="info-item">
                    <label>Type</label>
                    <p><?php echo e($appointment['appointment_type'] ?? 'N/A'); ?></p>
                </div>
                <div class="info-item">
                    <label>Provider</label>
                    <p><?php echo e($appointment['provider_name'] ?? 'N/A'); ?></p>
                </div>
                <div class="info-item">
                    <label>Location</label>
                    <p><?php echo e($appointment['location'] ?? 'N/A'); ?></p>
                </div>
            </div>
            
            <?php if (!empty($appointment['notes'])): ?>
            <div class="info-item mt-4">
                <label>Notes</label>
                <p><?php echo nl2br(e($appointment['notes'])); ?></p>
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
