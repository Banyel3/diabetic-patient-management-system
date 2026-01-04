<?php
/**
 * DiabetaCare - Lab Results List Page
 */

$pageTitle = 'Lab Results';

// Get filter parameters
$page = max(1, (int) get('page', 1));
$search = get('search', '');
$patientId = get('patient_id', '');
$testType = get('test_type', '');
$pageSize = 10;

// Handle delete action
if (isPost() && post('action') === 'delete') {
    if (validateCsrfToken(post('csrf_token'))) {
        $labResultId = (int) post('lab_result_id');
        $result = api()->deleteLabResult($labResultId);
        if ($result['success']) {
            setFlash('success', 'Lab result deleted successfully.');
        } else {
            setFlash('error', $result['error']['message'] ?? 'Failed to delete lab result.');
        }
    }
    redirect('/lab-results?' . buildQueryString(['page' => $page, 'search' => $search, 'patient_id' => $patientId, 'test_type' => $testType]));
}

// Fetch lab results
$response = api()->getLabResults([
    'page' => $page,
    'page_size' => $pageSize,
    'search' => $search ?: null,
    'patient_id' => $patientId ?: null,
    'test_type' => $testType ?: null,
]);

$labResults = $response['success'] ? ($response['items'] ?? []) : [];
$pagination = $response['success'] ? ($response['pagination'] ?? []) : [];
$totalItems = $pagination['total_items'] ?? 0;
$totalPages = $pagination['total_pages'] ?? 1;

// Fetch patients for filter dropdown
$patientsResponse = api()->getPatients(['page_size' => 100]);
$patients = $patientsResponse['success'] ? ($patientsResponse['items'] ?? []) : [];

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
        <div>
            <h1 class="page-title">Lab Results</h1>
            <p class="page-subtitle"><?php echo e($totalItems); ?> results found</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?php echo baseUrl('/lab-results?' . buildQueryString(['search' => $search, 'patient_id' => $patientId, 'test_type' => $testType])); ?>" 
               class="btn btn-outline btn-icon" title="Refresh">
                <i data-lucide="refresh-cw"></i>
            </a>
            <a href="<?php echo baseUrl('/lab-results/create'); ?>" class="btn btn-primary">
                <i data-lucide="plus"></i>
                Add Lab Result
            </a>
        </div>
    </div>
    
    <!-- Search and Filter -->
    <form method="GET" action="<?php echo baseUrl('/lab-results'); ?>" class="search-filters">
        <div class="search-box">
            <i data-lucide="search"></i>
            <input type="text" name="search" class="form-input" 
                   placeholder="Search by patient name..."
                   value="<?php echo e($search); ?>">
        </div>
        <div class="filter-select">
            <select name="patient_id" class="form-select" onchange="this.form.submit()">
                <option value="">All Patients</option>
                <?php foreach ($patients as $patient): ?>
                <option value="<?php echo $patient['id']; ?>" <?php echo $patientId == $patient['id'] ? 'selected' : ''; ?>>
                    <?php echo e($patient['first_name'] . ' ' . $patient['last_name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-select">
            <select name="test_type" class="form-select" onchange="this.form.submit()">
                <option value="">All Test Types</option>
                <option value="HbA1c" <?php echo $testType === 'HbA1c' ? 'selected' : ''; ?>>HbA1c</option>
                <option value="Fasting Glucose" <?php echo $testType === 'Fasting Glucose' ? 'selected' : ''; ?>>Fasting Glucose</option>
                <option value="Random Glucose" <?php echo $testType === 'Random Glucose' ? 'selected' : ''; ?>>Random Glucose</option>
                <option value="Lipid Panel" <?php echo $testType === 'Lipid Panel' ? 'selected' : ''; ?>>Lipid Panel</option>
                <option value="Kidney Function" <?php echo $testType === 'Kidney Function' ? 'selected' : ''; ?>>Kidney Function</option>
                <option value="Comprehensive Panel" <?php echo $testType === 'Comprehensive Panel' ? 'selected' : ''; ?>>Comprehensive Panel</option>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">Search</button>
    </form>
    
    <!-- Lab Results Table -->
    <div class="table-container">
        <?php if (empty($labResults)): ?>
        <div class="empty-state">
            <i data-lucide="flask-conical"></i>
            <p>No lab results found</p>
            <a href="<?php echo baseUrl('/lab-results/create'); ?>">Add first lab result</a>
        </div>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Test Date</th>
                    <th>Test Type</th>
                    <th>HbA1c</th>
                    <th>Fasting Glucose</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($labResults as $lab): ?>
                <tr>
                    <td>
                        <a href="<?php echo baseUrl('/patients/view?id=' . $lab['patient_id']); ?>" 
                           class="text-link font-medium">
                            <?php echo e(($lab['patient_first_name'] ?? '') . ' ' . ($lab['patient_last_name'] ?? '')); ?>
                        </a>
                    </td>
                    <td class="text-sm" style="color: var(--text-secondary);">
                        <?php echo formatDate($lab['test_date'], 'M j, Y'); ?>
                    </td>
                    <td class="text-sm" style="color: var(--text-secondary);">
                        <?php echo e($lab['test_type'] ?? 'General'); ?>
                    </td>
                    <td>
                        <?php if ($lab['hba1c'] !== null): ?>
                        <span class="<?php echo e(getHbA1cColorClass($lab['hba1c'])); ?> font-semibold">
                            <?php echo e($lab['hba1c']); ?>%
                        </span>
                        <?php else: ?>
                        <span style="color: var(--text-muted);">N/A</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-sm" style="color: var(--text-secondary);">
                        <?php echo $lab['fasting_glucose'] !== null ? e($lab['fasting_glucose']) . ' mg/dL' : 'N/A'; ?>
                    </td>
                    <td>
                        <span class="badge <?php echo e(getStatusBadgeClass($lab['status'] ?? 'Normal')); ?>">
                            <?php echo e($lab['status'] ?? 'Normal'); ?>
                        </span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <a href="<?php echo baseUrl('/lab-results/view?id=' . $lab['id']); ?>" 
                               class="table-action-btn" title="View">
                                <i data-lucide="eye"></i>
                            </a>
                            <a href="<?php echo baseUrl('/lab-results/edit?id=' . $lab['id']); ?>" 
                               class="table-action-btn" title="Edit">
                                <i data-lucide="edit-2"></i>
                            </a>
                            <button type="button" class="table-action-btn danger" title="Delete"
                                    onclick="confirmDelete(<?php echo $lab['id']; ?>)">
                                <i data-lucide="trash-2"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="<?php echo baseUrl('/lab-results?' . buildQueryString(['page' => $page - 1, 'search' => $search, 'patient_id' => $patientId, 'test_type' => $testType])); ?>" 
               class="pagination-btn">
                <i data-lucide="chevron-left"></i>
            </a>
            <?php endif; ?>
            
            <?php foreach (getPaginationData($page, $totalPages) as $p): ?>
                <?php if ($p === '...'): ?>
                <span class="pagination-btn" style="cursor: default;">...</span>
                <?php else: ?>
                <a href="<?php echo baseUrl('/lab-results?' . buildQueryString(['page' => $p, 'search' => $search, 'patient_id' => $patientId, 'test_type' => $testType])); ?>" 
                   class="pagination-btn <?php echo $p === $page ? 'active' : ''; ?>">
                    <?php echo $p; ?>
                </a>
                <?php endif; ?>
            <?php endforeach; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="<?php echo baseUrl('/lab-results?' . buildQueryString(['page' => $page + 1, 'search' => $search, 'patient_id' => $patientId, 'test_type' => $testType])); ?>" 
               class="pagination-btn">
                <i data-lucide="chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal-overlay" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Confirm Delete</h3>
            <button type="button" class="modal-close" onclick="closeDeleteModal()">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this lab result?</p>
            <p class="text-sm mt-2" style="color: var(--text-muted);">This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <form method="POST" action="<?php echo baseUrl('/lab-results'); ?>" id="deleteForm">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="lab_result_id" id="deleteLabResultId">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">Delete</button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    document.getElementById('deleteLabResultId').value = id;
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});
</script>

<?php include BASE_PATH . '/includes/layout/footer.php'; ?>
