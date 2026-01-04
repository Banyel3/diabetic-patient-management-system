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
if (!$response['success']) {
    setFlash('error', $response['error']['message'] ?? 'Lab result not found.');
    redirect('/lab-results');
}

$labResult = $response;
$pageTitle = ($labResult['test_type'] ?? 'Lab Result') . ' Details';

// Get value status
function getLabResultStatus($testType, $value): array {
    $value = (float) $value;
    
    // Define ranges for common tests
    $ranges = [
        'HbA1c' => ['low' => 0, 'normal_low' => 4.0, 'normal_high' => 5.6, 'high' => 6.5, 'unit' => '%'],
        'Fasting Glucose' => ['low' => 0, 'normal_low' => 70, 'normal_high' => 100, 'high' => 126, 'unit' => 'mg/dL'],
        'Random Glucose' => ['low' => 0, 'normal_low' => 70, 'normal_high' => 140, 'high' => 200, 'unit' => 'mg/dL'],
        'Blood Pressure Systolic' => ['low' => 0, 'normal_low' => 90, 'normal_high' => 120, 'high' => 140, 'unit' => 'mmHg'],
        'Cholesterol' => ['low' => 0, 'normal_low' => 0, 'normal_high' => 200, 'high' => 240, 'unit' => 'mg/dL'],
    ];
    
    if (!isset($ranges[$testType])) {
        return ['status' => 'unknown', 'class' => 'badge-secondary', 'message' => 'N/A'];
    }
    
    $range = $ranges[$testType];
    
    if ($value < $range['normal_low']) {
        return ['status' => 'low', 'class' => 'badge-warning', 'message' => 'Below Normal'];
    } elseif ($value <= $range['normal_high']) {
        return ['status' => 'normal', 'class' => 'badge-success', 'message' => 'Normal'];
    } elseif ($value <= $range['high']) {
        return ['status' => 'elevated', 'class' => 'badge-warning', 'message' => 'Elevated'];
    } else {
        return ['status' => 'high', 'class' => 'badge-danger', 'message' => 'High'];
    }
}

$valueStatus = getLabResultStatus($labResult['test_type'] ?? '', $labResult['value'] ?? 0);

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
                <h1 class="page-title mb-0"><?php echo e($labResult['test_type'] ?? 'Lab Result'); ?></h1>
                <p class="text-muted">
                    <?php echo formatDate($labResult['test_date'] ?? '', 'l, F j, Y'); ?>
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?php echo baseUrl('/lab-results/' . $labResultId . '/edit'); ?>" class="btn btn-primary">
                <i data-lucide="edit-2"></i>
                Edit Result
            </a>
        </div>
    </div>
    
    <!-- Result Value Card -->
    <div class="card mb-4">
        <div class="card-body text-center py-5">
            <div class="text-muted text-sm mb-2"><?php echo e($labResult['test_type'] ?? 'Test'); ?></div>
            <div class="text-4xl font-bold mb-2">
                <?php echo e($labResult['value'] ?? 'N/A'); ?>
                <span class="text-lg font-normal text-muted"><?php echo e($labResult['unit'] ?? ''); ?></span>
            </div>
            <span class="badge <?php echo e($valueStatus['class']); ?>">
                <?php echo e($valueStatus['message']); ?>
            </span>
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
                        <a href="<?php echo baseUrl('/patients/' . ($labResult['patient_id'] ?? '')); ?>" class="text-primary">
                            <?php echo e(($labResult['patient_first_name'] ?? '') . ' ' . ($labResult['patient_last_name'] ?? '')); ?>
                        </a>
                    </p>
                </div>
                <div class="info-item">
                    <label>Test Date</label>
                    <p><?php echo formatDate($labResult['test_date'] ?? '', 'M j, Y'); ?></p>
                </div>
                <div class="info-item">
                    <label>Test Type</label>
                    <p><?php echo e($labResult['test_type'] ?? 'N/A'); ?></p>
                </div>
                <div class="info-item">
                    <label>Value</label>
                    <p><?php echo e($labResult['value'] ?? 'N/A'); ?> <?php echo e($labResult['unit'] ?? ''); ?></p>
                </div>
                <?php if (!empty($labResult['reference_range'])): ?>
                <div class="info-item">
                    <label>Reference Range</label>
                    <p><?php echo e($labResult['reference_range']); ?></p>
                </div>
                <?php endif; ?>
                <?php if (!empty($labResult['lab_name'])): ?>
                <div class="info-item">
                    <label>Laboratory</label>
                    <p><?php echo e($labResult['lab_name']); ?></p>
                </div>
                <?php endif; ?>
                <?php if (!empty($labResult['ordered_by'])): ?>
                <div class="info-item">
                    <label>Ordered By</label>
                    <p><?php echo e($labResult['ordered_by']); ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($labResult['notes'])): ?>
            <div class="info-item mt-4">
                <label>Notes</label>
                <p><?php echo nl2br(e($labResult['notes'])); ?></p>
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
