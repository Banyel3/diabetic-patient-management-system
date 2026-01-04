<?php
/**
 * DiabetaCare - Medication Detail View Page
 */

$medicationId = (int) get('id');
if (!$medicationId) {
    setFlash('error', 'Medication ID is required.');
    redirect('/medications');
}

// Fetch medication data
$response = api()->getMedication($medicationId);
if (!safeGet($response, 'success', false)) {
    $errorMsg = safeGet($response, 'error.message', safeStr($response, 'message', 'Medication not found.'));
    setFlash('error', $errorMsg);
    redirect('/medications');
}

$medication = $response;

// Extract medication fields safely - using backend field names
$medName = safeStr($medication, 'name', 'Medication Details');
$medDosage = safeStr($medication, 'dosage', '');
$medFrequency = safeStr($medication, 'frequency', '');
$medStatus = safeStr($medication, 'status', 'Unknown');
$medPatientId = safeInt($medication, 'patient_id');
$medPatientName = safeStr($medication, 'patient_name', 'Unknown');
$medStartDate = safeStr($medication, 'start_date', '');
$medEndDate = safeStr($medication, 'end_date', '');
$medNotes = safeStr($medication, 'notes', '');

$pageTitle = $medName;

// Get status badge class
function getMedicationStatusBadgeClass($status): string {
    return match(strtolower($status ?? '')) {
        'active' => 'badge-success',
        'completed' => 'badge-info',
        'discontinued' => 'badge-danger',
        'on-hold' => 'badge-warning',
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
            <a href="<?php echo baseUrl('/medications'); ?>" class="btn btn-icon btn-outline">
                <i data-lucide="arrow-left"></i>
            </a>
            <div>
                <h1 class="page-title mb-0"><?php echo e($pageTitle); ?></h1>
                <p class="text-muted">
                    <?php echo e($medDosage); ?> - <?php echo e($medFrequency); ?>
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?php echo baseUrl('/medications/edit?id=' . $medicationId); ?>" class="btn btn-primary">
                <i data-lucide="edit-2"></i>
                Edit Medication
            </a>
        </div>
    </div>
    
    <!-- Medication Details Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Medication Information</h3>
            <span class="badge <?php echo e(getMedicationStatusBadgeClass($medStatus)); ?>">
                <?php echo e($medStatus); ?>
            </span>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item">
                    <label>Patient</label>
                    <p>
                        <a href="<?php echo baseUrl('/patients/view?id=' . $medPatientId); ?>" class="text-primary">
                            <?php echo e($medPatientName); ?>
                        </a>
                    </p>
                </div>
                <div class="info-item">
                    <label>Medication Name</label>
                    <p><?php echo e($medName); ?></p>
                </div>
                <div class="info-item">
                    <label>Dosage</label>
                    <p><?php echo e($medDosage ? $medDosage : 'N/A'); ?></p>
                </div>
                <div class="info-item">
                    <label>Frequency</label>
                    <p><?php echo e($medFrequency ? $medFrequency : 'N/A'); ?></p>
                </div>
                <div class="info-item">
                    <label>Start Date</label>
                    <p><?php echo formatDate($medStartDate, 'M j, Y'); ?></p>
                </div>
                <div class="info-item">
                    <label>End Date</label>
                    <p><?php echo $medEndDate ? formatDate($medEndDate, 'M j, Y') : 'Ongoing'; ?></p>
                </div>
            </div>
            
            <?php if (!empty($medNotes)): ?>
            <div class="info-item mt-4">
                <label>Notes</label>
                <p><?php echo nl2br(e($medNotes)); ?></p>
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
