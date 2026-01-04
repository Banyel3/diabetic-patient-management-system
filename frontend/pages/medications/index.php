<?php
/**
 * DiabetaCare - Medications List Page
 */

$pageTitle = 'Medications';

// Get filter parameters
$page = max(1, (int) get('page', 1));
$search = get('search', '');
$patientId = get('patient_id', '');
$activeOnly = get('active', '');
$pageSize = 10;

// Handle delete action
if (isPost() && post('action') === 'delete') {
    if (validateCsrfToken(post('csrf_token'))) {
        $medicationId = (int) post('medication_id');
        $result = api()->deleteMedication($medicationId);
        if ($result['success']) {
            setFlash('success', 'Medication deleted successfully.');
        } else {
            setFlash('error', $result['error']['message'] ?? 'Failed to delete medication.');
        }
    }
    redirect('/medications?' . buildQueryString(['page' => $page, 'search' => $search, 'patient_id' => $patientId, 'active' => $activeOnly]));
}

// Handle toggle active status
if (isPost() && post('action') === 'toggle_active') {
    if (validateCsrfToken(post('csrf_token'))) {
        $medicationId = (int) post('medication_id');
        $newStatus = post('new_status'); // 'Active' or 'Discontinued'
        $result = api()->updateMedication($medicationId, ['status' => $newStatus]);
        if ($result['success']) {
            setFlash('success', 'Medication status updated.');
        } else {
            setFlash('error', $result['error']['message'] ?? 'Failed to update status.');
        }
    }
    redirect('/medications?' . buildQueryString(['page' => $page, 'search' => $search, 'patient_id' => $patientId, 'active' => $activeOnly]));
}

// Fetch medications
$response = api()->getMedications([
    'page' => $page,
    'page_size' => $pageSize,
    'search' => $search ?: null,
    'patient_id' => $patientId ?: null,
    'status' => $activeOnly !== '' ? ($activeOnly === '1' ? 'active' : 'discontinued') : null,
]);

$medications = safeGet($response, 'success') ? safeGet($response, 'items', []) : [];
$pagination = safeGet($response, 'success') ? safeGet($response, 'pagination', []) : [];
$totalItems = safeInt($pagination, 'total_items', 0);
$totalPages = safeInt($pagination, 'total_pages', 1);

// Fetch patients for filter dropdown
$patientsResponse = api()->getPatients(['page_size' => 100]);
$patients = safeGet($patientsResponse, 'success') ? safeGet($patientsResponse, 'items', []) : [];

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
            <h1 class="page-title">Medications</h1>
            <p class="page-subtitle"><?php echo e($totalItems); ?> prescriptions found</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?php echo baseUrl('/medications?' . buildQueryString(['search' => $search, 'patient_id' => $patientId, 'active' => $activeOnly])); ?>" 
               class="btn btn-outline btn-icon" title="Refresh">
                <i data-lucide="refresh-cw"></i>
            </a>
            <a href="<?php echo baseUrl('/medications/create'); ?>" class="btn btn-primary">
                <i data-lucide="plus"></i>
                Add Medication
            </a>
        </div>
    </div>
    
    <!-- Search and Filter -->
    <form method="GET" action="<?php echo baseUrl('/medications'); ?>" class="search-filters">
        <div class="search-box">
            <i data-lucide="search"></i>
            <input type="text" name="search" class="form-input" 
                   placeholder="Search by medication name..."
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
            <select name="active" class="form-select" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="1" <?php echo $activeOnly === '1' ? 'selected' : ''; ?>>Active Only</option>
                <option value="0" <?php echo $activeOnly === '0' ? 'selected' : ''; ?>>Inactive Only</option>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">Search</button>
    </form>
    
    <!-- Medications Table -->
    <div class="table-container">
        <?php if (empty($medications)): ?>
        <div class="empty-state">
            <i data-lucide="pill"></i>
            <p>No medications found</p>
            <a href="<?php echo baseUrl('/medications/create'); ?>">Add first medication</a>
        </div>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Medication</th>
                    <th>Patient</th>
                    <th>Dosage</th>
                    <th>Frequency</th>
                    <th>Start Date</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($medications as $medication): ?>
                <?php
                    $medId = safeInt($medication, 'id');
                    $medName = safeStr($medication, 'name', 'Unknown');
                    $medPatientId = safeInt($medication, 'patient_id');
                    $patientName = safeStr($medication, 'patient_name', 'Unknown');
                    $dosage = safeStr($medication, 'dosage', 'N/A');
                    $frequency = safeStr($medication, 'frequency', 'N/A');
                    $startDate = safeStr($medication, 'start_date', '');
                    $medStatus = safeStr($medication, 'status', 'Active');
                    $isActive = strtolower($medStatus) === 'active';
                ?>
                <tr>
                    <td>
                        <p class="font-medium" style="color: var(--text-primary);">
                            <?php echo e($medName); ?>
                        </p>
                    </td>
                    <td>
                        <a href="<?php echo baseUrl('/patients/' . $medPatientId); ?>" 
                           class="text-link">
                            <?php echo e($patientName); ?>
                        </a>
                    </td>
                    <td class="text-sm" style="color: var(--text-secondary);">
                        <?php echo e($dosage); ?>
                    </td>
                    <td class="text-sm" style="color: var(--text-secondary);">
                        <?php echo e($frequency); ?>
                    </td>
                    <td class="text-sm" style="color: var(--text-secondary);">
                        <?php echo formatDate($startDate); ?>
                    </td>
                    <td>
                        <span class="badge <?php echo $isActive ? 'badge-success' : 'badge-secondary'; ?>">
                            <?php echo $isActive ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <form method="POST" action="<?php echo baseUrl('/medications'); ?>" style="display: inline;">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="toggle_active">
                                <input type="hidden" name="medication_id" value="<?php echo $medId; ?>">
                                <input type="hidden" name="new_status" value="<?php echo $isActive ? 'discontinued' : 'active'; ?>">
                                <button type="submit" class="table-action-btn <?php echo $isActive ? 'warning' : 'success'; ?>" 
                                        title="<?php echo $isActive ? 'Discontinue' : 'Activate'; ?>">
                                    <i data-lucide="<?php echo $isActive ? 'pause' : 'play'; ?>"></i>
                                </button>
                            </form>
                            <a href="<?php echo baseUrl('/medications/' . $medId . '/edit'); ?>" 
                               class="table-action-btn" title="Edit">
                                <i data-lucide="edit-2"></i>
                            </a>
                            <button type="button" class="table-action-btn danger" title="Delete"
                                    onclick="confirmDelete(<?php echo $medId; ?>, '<?php echo e($medName); ?>')">
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
            <a href="<?php echo baseUrl('/medications?' . buildQueryString(['page' => $page - 1, 'search' => $search, 'patient_id' => $patientId, 'active' => $activeOnly])); ?>" 
               class="pagination-btn">
                <i data-lucide="chevron-left"></i>
            </a>
            <?php endif; ?>
            
            <?php foreach (getPaginationData($page, $totalPages) as $p): ?>
                <?php if ($p === '...'): ?>
                <span class="pagination-btn" style="cursor: default;">...</span>
                <?php else: ?>
                <a href="<?php echo baseUrl('/medications?' . buildQueryString(['page' => $p, 'search' => $search, 'patient_id' => $patientId, 'active' => $activeOnly])); ?>" 
                   class="pagination-btn <?php echo $p === $page ? 'active' : ''; ?>">
                    <?php echo $p; ?>
                </a>
                <?php endif; ?>
            <?php endforeach; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="<?php echo baseUrl('/medications?' . buildQueryString(['page' => $page + 1, 'search' => $search, 'patient_id' => $patientId, 'active' => $activeOnly])); ?>" 
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
            <p>Are you sure you want to delete <strong id="deleteMedName"></strong>?</p>
            <p class="text-sm mt-2" style="color: var(--text-muted);">This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <form method="POST" action="<?php echo baseUrl('/medications'); ?>" id="deleteForm">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="medication_id" id="deleteMedicationId">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">Delete</button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('deleteMedicationId').value = id;
    document.getElementById('deleteMedName').textContent = name;
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
