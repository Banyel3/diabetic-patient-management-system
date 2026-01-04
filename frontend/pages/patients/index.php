<?php
/**
 * DiabetaCare - Patients List Page
 */

$pageTitle = 'Patients';

// Get filter parameters
$page = max(1, (int) get('page', 1));
$search = get('search', '');
$diabetesType = get('type', '');
$pageSize = 10;

// Handle delete action
if (isPost() && post('action') === 'delete') {
    if (validateCsrfToken(post('csrf_token'))) {
        $patientId = (int) post('patient_id');
        $result = api()->deletePatient($patientId);
        if ($result['success']) {
            setFlash('success', 'Patient deleted successfully.');
        } else {
            setFlash('error', $result['error']['message'] ?? 'Failed to delete patient.');
        }
    }
    redirect('/patients?' . buildQueryString(['page' => $page, 'search' => $search, 'type' => $diabetesType]));
}

// Fetch patients
$response = api()->getPatients([
    'page' => $page,
    'page_size' => $pageSize,
    'search' => $search ?: null,
    'diabetes_type' => $diabetesType ?: null,
]);

$patients = safeGet($response, 'success') ? safeGet($response, 'items', []) : [];
$pagination = safeGet($response, 'success') ? safeGet($response, 'pagination', []) : [];
$totalItems = safeInt($pagination, 'total_items', 0);
$totalPages = safeInt($pagination, 'total_pages', 1);

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
            <h1 class="page-title">Patients</h1>
            <p class="page-subtitle"><?php echo e($totalItems); ?> patients found</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?php echo baseUrl('/patients?' . buildQueryString(['search' => $search, 'type' => $diabetesType])); ?>" 
               class="btn btn-outline btn-icon" title="Refresh">
                <i data-lucide="refresh-cw"></i>
            </a>
            <a href="<?php echo baseUrl('/patients/create'); ?>" class="btn btn-primary">
                <i data-lucide="plus"></i>
                Add Patient
            </a>
        </div>
    </div>
    
    <!-- Search and Filter -->
    <form method="GET" action="<?php echo baseUrl('/patients'); ?>" class="search-filters">
        <div class="search-box">
            <i data-lucide="search"></i>
            <input type="text" name="search" class="form-input" 
                   placeholder="Search by name, ID, or phone..."
                   value="<?php echo e($search); ?>">
        </div>
        <div class="filter-select">
            <select name="type" class="form-select" onchange="this.form.submit()">
                <option value="">All Types</option>
                <option value="Type 1" <?php echo $diabetesType === 'Type 1' ? 'selected' : ''; ?>>Type 1</option>
                <option value="Type 2" <?php echo $diabetesType === 'Type 2' ? 'selected' : ''; ?>>Type 2</option>
                <option value="Gestational" <?php echo $diabetesType === 'Gestational' ? 'selected' : ''; ?>>Gestational</option>
                <option value="Pre-diabetic" <?php echo $diabetesType === 'Pre-diabetic' ? 'selected' : ''; ?>>Pre-diabetic</option>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">Search</button>
    </form>
    
    <!-- Patients Table -->
    <div class="table-container">
        <?php if (empty($patients)): ?>
        <div class="empty-state">
            <i data-lucide="users"></i>
            <p>No patients found</p>
            <a href="<?php echo baseUrl('/patients/create'); ?>">Add your first patient</a>
        </div>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Type</th>
                    <th>Age/Gender</th>
                    <th>Phone</th>
                    <th>Last HbA1c</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($patients as $patient): ?>
                <?php
                    $patientId = safeInt($patient, 'id');
                    $firstName = safeStr($patient, 'first_name', '');
                    $lastName = safeStr($patient, 'last_name', '');
                    $patientCode = safeStr($patient, 'patient_code', '');
                    $diabetesType = safeStr($patient, 'diabetes_type', 'N/A');
                    // Calculate age from date_of_birth
                    $dob = safeStr($patient, 'date_of_birth', '');
                    $age = $dob ? (new DateTime())->diff(new DateTime($dob))->y : 'N/A';
                    $gender = safeStr($patient, 'gender', 'N/A');
                    // Capitalize first letter for display
                    $genderDisplay = $gender !== 'N/A' ? ucfirst($gender) : 'N/A';
                    $phone = safeStr($patient, 'phone', 'N/A');
                    $lastHba1c = safeFloat($patient, 'last_hba1c');
                    $status = safeStr($patient, 'status', 'Active');
                ?>
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="avatar avatar-md">
                                <?php echo e(getInitials($firstName, $lastName)); ?>
                            </div>
                            <div>
                                <p class="font-medium" style="color: var(--text-primary);">
                                    <?php echo e($firstName . ' ' . $lastName); ?>
                                </p>
                                <p class="text-xs" style="color: var(--text-muted);">
                                    <?php echo e($patientCode); ?>
                                </p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge <?php echo e(getDiabetesTypeBadgeClass($diabetesType)); ?>">
                            <?php echo e($diabetesType); ?>
                        </span>
                    </td>
                    <td class="text-sm" style="color: var(--text-secondary);">
                        <?php echo e($age); ?> yrs / <?php echo e($genderDisplay); ?>
                    </td>
                    <td class="text-sm" style="color: var(--text-secondary);">
                        <?php echo e($phone); ?>
                    </td>
                    <td>
                        <span class="<?php echo e(getHbA1cColorClass($lastHba1c)); ?> font-semibold">
                            <?php echo $lastHba1c !== null ? e($lastHba1c) . '%' : 'N/A'; ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?php echo e(getStatusBadgeClass($status)); ?>">
                            <?php echo e($status); ?>
                        </span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <a href="<?php echo baseUrl('/patients/' . $patientId); ?>" 
                               class="table-action-btn" title="View">
                                <i data-lucide="eye"></i>
                            </a>
                            <a href="<?php echo baseUrl('/patients/' . $patientId . '/edit'); ?>" 
                               class="table-action-btn" title="Edit">
                                <i data-lucide="edit-2"></i>
                            </a>
                            <button type="button" class="table-action-btn danger" title="Delete"
                                    onclick="confirmDelete(<?php echo $patientId; ?>, '<?php echo e($firstName . ' ' . $lastName); ?>')">
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
            <a href="<?php echo baseUrl('/patients?' . buildQueryString(['page' => $page - 1, 'search' => $search, 'type' => $diabetesType])); ?>" 
               class="pagination-btn">
                <i data-lucide="chevron-left"></i>
            </a>
            <?php endif; ?>
            
            <?php foreach (getPaginationData($page, $totalPages) as $p): ?>
                <?php if ($p === '...'): ?>
                <span class="pagination-btn" style="cursor: default;">...</span>
                <?php else: ?>
                <a href="<?php echo baseUrl('/patients?' . buildQueryString(['page' => $p, 'search' => $search, 'type' => $diabetesType])); ?>" 
                   class="pagination-btn <?php echo $p === $page ? 'active' : ''; ?>">
                    <?php echo $p; ?>
                </a>
                <?php endif; ?>
            <?php endforeach; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="<?php echo baseUrl('/patients?' . buildQueryString(['page' => $page + 1, 'search' => $search, 'type' => $diabetesType])); ?>" 
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
            <p>Are you sure you want to delete <strong id="deletePatientName"></strong>?</p>
            <p class="text-sm mt-2" style="color: var(--text-muted);">This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <form method="POST" action="<?php echo baseUrl('/patients'); ?>" id="deleteForm">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="patient_id" id="deletePatientId">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">Delete</button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('deletePatientId').value = id;
    document.getElementById('deletePatientName').textContent = name;
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Close modal on outside click
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});
</script>

<?php include BASE_PATH . '/includes/layout/footer.php'; ?>
