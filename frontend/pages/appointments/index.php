<?php
/**
 * DiabetaCare - Appointments List Page
 */

$pageTitle = 'Appointments';

// Get filter parameters
$page = max(1, (int) get('page', 1));
$search = get('search', '');
$status = get('status', '');
$date = get('date', '');
$pageSize = 10;

// Handle status update action
if (isPost() && post('action') === 'update_status') {
    if (validateCsrfToken(post('csrf_token'))) {
        $appointmentId = (int) post('appointment_id');
        $newStatus = post('new_status');
        $result = api()->updateAppointment($appointmentId, ['status' => $newStatus]);
        if ($result['success']) {
            setFlash('success', 'Appointment status updated.');
        } else {
            setFlash('error', $result['error']['message'] ?? 'Failed to update status.');
        }
    }
    redirect('/appointments?' . buildQueryString(['page' => $page, 'search' => $search, 'status' => $status, 'date' => $date]));
}

// Handle delete action
if (isPost() && post('action') === 'delete') {
    if (validateCsrfToken(post('csrf_token'))) {
        $appointmentId = (int) post('appointment_id');
        $result = api()->deleteAppointment($appointmentId);
        if ($result['success']) {
            setFlash('success', 'Appointment deleted successfully.');
        } else {
            setFlash('error', $result['error']['message'] ?? 'Failed to delete appointment.');
        }
    }
    redirect('/appointments?' . buildQueryString(['page' => $page, 'search' => $search, 'status' => $status, 'date' => $date]));
}

// Fetch appointments
$response = api()->getAppointments([
    'page' => $page,
    'page_size' => $pageSize,
    'search' => $search ?: null,
    'status' => $status ?: null,
    'date' => $date ?: null,
]);

$appointments = safeGet($response, 'success') ? safeGet($response, 'items', []) : [];
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
            <h1 class="page-title">Appointments</h1>
            <p class="page-subtitle"><?php echo e($totalItems); ?> appointments found</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?php echo baseUrl('/appointments?' . buildQueryString(['search' => $search, 'status' => $status, 'date' => $date])); ?>" 
               class="btn btn-outline btn-icon" title="Refresh">
                <i data-lucide="refresh-cw"></i>
            </a>
            <a href="<?php echo baseUrl('/appointments/create'); ?>" class="btn btn-primary">
                <i data-lucide="plus"></i>
                New Appointment
            </a>
        </div>
    </div>
    
    <!-- Search and Filter -->
    <form method="GET" action="<?php echo baseUrl('/appointments'); ?>" class="search-filters">
        <div class="search-box">
            <i data-lucide="search"></i>
            <input type="text" name="search" class="form-input" 
                   placeholder="Search by patient name..."
                   value="<?php echo e($search); ?>">
        </div>
        <div class="filter-select">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="Scheduled" <?php echo $status === 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                <option value="Confirmed" <?php echo $status === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                <option value="Completed" <?php echo $status === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="Cancelled" <?php echo $status === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                <option value="No-show" <?php echo $status === 'No-show' ? 'selected' : ''; ?>>No-show</option>
            </select>
        </div>
        <div class="filter-select">
            <input type="date" name="date" class="form-input" value="<?php echo e($date); ?>" onchange="this.form.submit()">
        </div>
        <button type="submit" class="btn btn-secondary">Search</button>
    </form>
    
    <!-- Appointments Table -->
    <div class="table-container">
        <?php if (empty($appointments)): ?>
        <div class="empty-state">
            <i data-lucide="calendar"></i>
            <p>No appointments found</p>
            <a href="<?php echo baseUrl('/appointments/create'); ?>">Schedule your first appointment</a>
        </div>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Date & Time</th>
                    <th>Type</th>
                    <th>Duration</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appointment): ?>
                <?php
                    $aptId = safeInt($appointment, 'id');
                    $patientName = safeStr($appointment, 'patient_name', 'Unknown');
                    $patientCode = safeStr($appointment, 'patient_code', '');
                    $appointmentDate = safeStr($appointment, 'date', '');
                    $appointmentTime = safeStr($appointment, 'time', '');
                    $appointmentType = safeStr($appointment, 'type', 'Check-up');
                    $durationMinutes = safeInt($appointment, 'duration_minutes', 30);
                    $aptStatus = safeStr($appointment, 'status', 'Scheduled');
                ?>
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="avatar avatar-md">
                                <?php echo e(getInitialsFromFullName($patientName)); ?>
                            </div>
                            <div>
                                <p class="font-medium" style="color: var(--text-primary);">
                                    <?php echo e($patientName); ?>
                                </p>
                                <p class="text-xs" style="color: var(--text-muted);">
                                    <?php echo e($patientCode); ?>
                                </p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <p class="font-medium" style="color: var(--text-primary);">
                            <?php echo formatDate($appointmentDate, 'M j, Y'); ?>
                        </p>
                        <p class="text-sm" style="color: var(--text-muted);">
                            <?php echo formatTime($appointmentTime); ?>
                        </p>
                    </td>
                    <td class="text-sm" style="color: var(--text-secondary);">
                        <?php echo e($appointmentType); ?>
                    </td>
                    <td class="text-sm" style="color: var(--text-secondary);">
                        <?php echo e($durationMinutes); ?> min
                    </td>
                    <td>
                        <span class="badge <?php echo e(getAppointmentStatusBadgeClass($aptStatus)); ?>">
                            <?php echo e($aptStatus); ?>
                        </span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <?php if ($aptStatus === 'Scheduled'): ?>
                            <form method="POST" action="<?php echo baseUrl('/appointments'); ?>" style="display: inline;">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="appointment_id" value="<?php echo $aptId; ?>">
                                <input type="hidden" name="new_status" value="Completed">
                                <button type="submit" class="table-action-btn success" title="Mark Complete">
                                    <i data-lucide="check"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                            <a href="<?php echo baseUrl('/appointments/' . $aptId); ?>" 
                               class="table-action-btn" title="View">
                                <i data-lucide="eye"></i>
                            </a>
                            <a href="<?php echo baseUrl('/appointments/' . $aptId . '/edit'); ?>" 
                               class="table-action-btn" title="Edit">
                                <i data-lucide="edit-2"></i>
                            </a>
                            <button type="button" class="table-action-btn danger" title="Delete"
                                    onclick="confirmDelete(<?php echo $aptId; ?>)">
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
            <a href="<?php echo baseUrl('/appointments?' . buildQueryString(['page' => $page - 1, 'search' => $search, 'status' => $status, 'date' => $date])); ?>" 
               class="pagination-btn">
                <i data-lucide="chevron-left"></i>
            </a>
            <?php endif; ?>
            
            <?php foreach (getPaginationData($page, $totalPages) as $p): ?>
                <?php if ($p === '...'): ?>
                <span class="pagination-btn" style="cursor: default;">...</span>
                <?php else: ?>
                <a href="<?php echo baseUrl('/appointments?' . buildQueryString(['page' => $p, 'search' => $search, 'status' => $status, 'date' => $date])); ?>" 
                   class="pagination-btn <?php echo $p === $page ? 'active' : ''; ?>">
                    <?php echo $p; ?>
                </a>
                <?php endif; ?>
            <?php endforeach; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="<?php echo baseUrl('/appointments?' . buildQueryString(['page' => $page + 1, 'search' => $search, 'status' => $status, 'date' => $date])); ?>" 
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
            <p>Are you sure you want to delete this appointment?</p>
            <p class="text-sm mt-2" style="color: var(--text-muted);">This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <form method="POST" action="<?php echo baseUrl('/appointments'); ?>" id="deleteForm">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="appointment_id" id="deleteAppointmentId">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">Delete</button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    document.getElementById('deleteAppointmentId').value = id;
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
