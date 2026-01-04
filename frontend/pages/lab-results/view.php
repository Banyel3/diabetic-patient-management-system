<?php
/**
 * DiabetaCare - Lab Result Detail View Page
 */

$labResultId = (int) get('id');
if (!$labResultId) {
    setFlash('error', 'Lab Result ID is required.');
    redirect('/lab-results');
}

// Fetch lab result data
$response = api()->getLabResult($labResultId);
if (!safeGet($response, 'success', false)) {
    $errorMsg = safeGet($response, 'error.message', safeStr($response, 'message', 'Lab result not found.'));
    setFlash('error', $errorMsg);
    redirect('/lab-results');
}

$labResult = $response;

// Extract lab result fields safely - using backend field names
$testName = safeStr($labResult, 'test_name', 'Lab Result');
$testDate = safeStr($labResult, 'test_date', '');
$resultValue = safeStr($labResult, 'result_value', 'N/A');
$unit = safeStr($labResult, 'unit', '');
$referenceRange = safeStr($labResult, 'reference_range', '');
$labPatientId = safeInt($labResult, 'patient_id');
$labPatientName = safeStr($labResult, 'patient_name', 'Unknown');
$labNotes = safeStr($labResult, 'notes', '');
$labStatus = safeStr($labResult, 'status', 'Normal');

$pageTitle = $testName . ' Details';

// Get status badge class based on lab status
function getLabStatusBadgeClass($status): string {
    switch ($status) {
        case 'Normal':
            return 'badge-success';
        case 'Abnormal':
            return 'badge-warning';
        case 'Critical':
            return 'badge-danger';
        case 'Pending':
            return 'badge-secondary';
        default:
            return 'badge-secondary';
    }
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
            <a href="<?php echo baseUrl('/lab-results'); ?>" class="btn btn-icon btn-outline">
                <i data-lucide="arrow-left"></i>
            </a>
            <div>
                <h1 class="page-title mb-0"><?php echo e($testName); ?></h1>
                <p class="text-muted">
                    <?php echo formatDate($testDate, 'l, F j, Y'); ?>
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?php echo baseUrl('/lab-results/edit?id=' . $labResultId); ?>" class="btn btn-primary">
                <i data-lucide="edit-2"></i>
                Edit Result
            </a>
        </div>
    </div>
    
    <!-- Result Value Card -->
    <div class="card mb-4">
        <div class="card-body text-center py-5">
            <div class="text-muted text-sm mb-2"><?php echo e($testName); ?></div>
            <div class="text-4xl font-bold mb-2">
                <?php echo e($resultValue); ?>
                <span class="text-lg font-normal text-muted"><?php echo e($unit); ?></span>
            </div>
            <span class="badge <?php echo e(getLabStatusBadgeClass($labStatus)); ?>">
                <?php echo e($labStatus); ?>
            </span>
            <?php if (!empty($referenceRange)): ?>
            <div class="text-sm text-muted mt-2">Reference: <?php echo e($referenceRange); ?></div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Lab Result Details Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Result Details</h3>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item">
                    <label>Patient</label>
                    <p>
                        <a href="<?php echo baseUrl('/patients/view?id=' . $labPatientId); ?>" class="text-primary">
                            <?php echo e($labPatientName); ?>
                        </a>
                    </p>
                </div>
                <div class="info-item">
                    <label>Test Date</label>
                    <p><?php echo formatDate($testDate, 'M j, Y'); ?></p>
                </div>
                <div class="info-item">
                    <label>Test Name</label>
                    <p><?php echo e($testName); ?></p>
                </div>
                <div class="info-item">
                    <label>Result</label>
                    <p><?php echo e($resultValue); ?> <?php echo e($unit); ?></p>
                </div>
                <?php if (!empty($referenceRange)): ?>
                <div class="info-item">
                    <label>Reference Range</label>
                    <p><?php echo e($referenceRange); ?></p>
                </div>
                <?php endif; ?>
                <div class="info-item">
                    <label>Status</label>
                    <p><span class="badge <?php echo e(getLabStatusBadgeClass($labStatus)); ?>"><?php echo e($labStatus); ?></span></p>
                </div>
            </div>
            
            <?php if (!empty($labNotes)): ?>
            <div class="info-item mt-4">
                <label>Notes</label>
                <p><?php echo nl2br(e($labNotes)); ?></p>
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

.text-4xl {
    font-size: 2.5rem;
}

.font-bold {
    font-weight: 700;
}

.py-5 {
    padding-top: 2rem;
    padding-bottom: 2rem;
}

@media (max-width: 640px) {
    .info-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include BASE_PATH . '/includes/layout/footer.php'; ?>
