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

$labResults = safeGet($response, 'success') ? safeGet($response, 'items', []) : [];
$pagination = safeGet($response, 'success') ? safeGet($response, 'pagination', []) : [];
$totalItems = safeInt($pagination, 'total_items', 0);
$totalPages = safeInt($pagination, 'total_pages', 1);

// Fetch patients for filter dropdown (using lightweight endpoint)
$patientsResponse = api()->getPatientList();
$patients = safeGet($patientsResponse, 'data', []);

// Fetch page statistics
$statsResponse = api()->getLabResultsStats();
$pageStats = safeGet($statsResponse, 'success') ? $statsResponse : null;

// Build stats cards array
$stats = [];
if ($pageStats) {
    $byTestType = safeGet($pageStats, 'by_test_type', []);
    $stats = [
        [
            'label' => 'Total Results',
            'value' => safeInt($pageStats, 'total_results', 0),
            'change' => safeInt($pageStats, 'patients_tested', 0) . ' patients tested',
            'icon' => 'beaker',
            'iconClass' => 'amber',
        ],
        [
            'label' => 'Last 30 Days',
            'value' => safeInt($pageStats, 'last_30_days', 0),
            'change' => 'Recent tests',
            'icon' => 'calendar',
            'iconClass' => 'blue',
        ],
        [
            'label' => 'Abnormal Results',
            'value' => safeInt($pageStats, 'abnormal_results', 0),
            'change' => safeGet($pageStats, 'abnormal_percentage', 0) . '% of total',
            'icon' => 'alert-triangle',
            'iconClass' => 'red',
        ],
        [
            'label' => 'HbA1c Tests',
            'value' => safeInt($byTestType, 'hba1c', 0),
            'change' => safeInt($byTestType, 'glucose', 0) . ' glucose tests',
            'icon' => 'activity',
            'iconClass' => 'purple',
        ],
    ];
}

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
    
    <!-- Stats Summary Cards -->
    <?php if (!empty($stats)): ?>
    <div class="grid grid-4 mb-6">
        <?php foreach ($stats as $stat): ?>
        <div class="card stat-card">
            <div class="flex items-center justify-between mb-3">
                <div class="stat-icon <?php echo e($stat['iconClass']); ?>">
                    <i data-lucide="<?php echo e($stat['icon']); ?>"></i>
                </div>
            </div>
            <p class="stat-value"><?php echo e($stat['value']); ?></p>
            <p class="stat-label"><?php echo e($stat['label']); ?></p>
            <p class="stat-change"><?php echo e($stat['change']); ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
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
                <option value="Post-meal Glucose" <?php echo $testType === 'Post-meal Glucose' ? 'selected' : ''; ?>>Post-meal Glucose</option>
                <option value="Creatinine" <?php echo $testType === 'Creatinine' ? 'selected' : ''; ?>>Creatinine</option>
                <option value="eGFR" <?php echo $testType === 'eGFR' ? 'selected' : ''; ?>>eGFR</option>
                <option value="Total Cholesterol" <?php echo $testType === 'Total Cholesterol' ? 'selected' : ''; ?>>Total Cholesterol</option>
                <option value="LDL Cholesterol" <?php echo $testType === 'LDL Cholesterol' ? 'selected' : ''; ?>>LDL Cholesterol</option>
                <option value="HDL Cholesterol" <?php echo $testType === 'HDL Cholesterol' ? 'selected' : ''; ?>>HDL Cholesterol</option>
                <option value="Triglycerides" <?php echo $testType === 'Triglycerides' ? 'selected' : ''; ?>>Triglycerides</option>
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
                    <th>Test Name</th>
                    <th>Result</th>
                    <th>Last Updated</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($labResults as $lab): ?>
                <?php
                    $labId = safeInt($lab, 'id');
                    $labPatientId = safeInt($lab, 'patient_id');
                    $patientName = safeStr($lab, 'patient_name', 'Unknown');
                    $testDate = safeStr($lab, 'test_date', '');
                    $testName = safeStr($lab, 'test_name', 'Unknown');
                    $resultValue = safeStr($lab, 'result_value', '');
                    $unit = safeStr($lab, 'unit', '');
                    $labStatus = safeStr($lab, 'status', 'Normal');
                    $updatedAt = safeStr($lab, 'updated_at', '');
                ?>
                <tr>
                    <td>
                        <a href="<?php echo baseUrl('/patients/' . $labPatientId); ?>" 
                           class="text-link font-medium">
                            <?php echo e($patientName); ?>
                        </a>
                    </td>
                    <td class="text-sm" style="color: var(--text-secondary);">
                        <?php echo formatDate($testDate, 'M j, Y'); ?>
                    </td>
                    <td class="text-sm" style="color: var(--text-secondary);">
                        <?php echo e($testName); ?>
                    </td>
                    <td>
                        <?php if ($resultValue !== ''): ?>
                        <span class="font-semibold">
                            <?php echo e($resultValue); ?><?php echo $unit ? ' ' . e($unit) : ''; ?>
                        </span>
                        <?php else: ?>
                        <span style="color: var(--text-muted);">N/A</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-sm" style="color: var(--text-muted);" title="Updated by trigger">
                        <?php echo $updatedAt ? formatDate($updatedAt, 'M j, Y H:i') : 'N/A'; ?>
                    </td>
                    <td>
                        <span class="badge <?php echo e(getStatusBadgeClass($labStatus)); ?>">
                            <?php echo e($labStatus); ?>
                        </span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <a href="<?php echo baseUrl('/lab-results/' . $labId); ?>" 
                               class="table-action-btn" title="View">
                                <i data-lucide="eye"></i>
                            </a>
                            <a href="<?php echo baseUrl('/lab-results/' . $labId . '/edit'); ?>" 
                               class="table-action-btn" title="Edit">
                                <i data-lucide="edit-2"></i>
                            </a>
                            <button type="button" class="table-action-btn danger" title="Delete"
                                    onclick="confirmDelete(<?php echo $labId; ?>)">
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
