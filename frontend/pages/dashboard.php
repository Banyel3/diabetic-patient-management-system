<?php
/**
 * DiabetaCare - Dashboard Page
 * 
 * Main dashboard with statistics, charts, and patient overview.
 */

$pageTitle = 'Dashboard';

// Fetch dashboard data
$summary = api()->getDashboardSummary();
$upcomingAppointments = api()->getUpcomingAppointments(5);
$recentPatients = api()->getRecentPatients(5);
$criticalAlerts = api()->getCriticalAlerts();
$chartData = api()->getChartData();

// Handle errors gracefully - use safe access patterns
$summaryData = safeGet($summary, 'success') ? $summary : null;
$appointmentsData = safeGet($upcomingAppointments, 'success') ? safeGet($upcomingAppointments, 'appointments', []) : [];
$patientsData = safeGet($recentPatients, 'success') ? safeGet($recentPatients, 'patients', []) : [];
$alertsData = safeGet($criticalAlerts, 'success') ? $criticalAlerts : null;
$charts = safeGet($chartData, 'success') ? $chartData : null;

// Build stats with safe array access
$stats = [];
if ($summaryData) {
    $patients = safeGet($summaryData, 'patients', []);
    $appointments = safeGet($summaryData, 'appointments', []);
    $appointmentsToday = safeGet($appointments, 'today', []);
    $medications = safeGet($summaryData, 'medications', []);
    $labResults = safeGet($summaryData, 'lab_results', []);
    $hba1cControl = safeGet($summaryData, 'hba1c_control', []);
    
    $stats = [
        [
            'label' => 'Total Patients',
            'value' => safeInt($patients, 'total', 0),
            'change' => safeInt($patients, 'active', 0) . ' active',
            'icon' => 'users',
            'iconClass' => 'accent',
        ],
        [
            'label' => 'Appointments Today',
            'value' => safeInt($appointmentsToday, 'total', 0),
            'change' => safeInt($appointmentsToday, 'scheduled', 0) . ' scheduled',
            'icon' => 'calendar',
            'iconClass' => 'blue',
        ],
        [
            'label' => 'Active Prescriptions',
            'value' => safeInt($medications, 'active_prescriptions', 0),
            'change' => safeInt($medications, 'patients_on_medications', 0) . ' patients',
            'icon' => 'pill',
            'iconClass' => 'purple',
        ],
        [
            'label' => 'Lab Results (30d)',
            'value' => safeInt($labResults, 'last_30_days', 0),
            'change' => safeInt($hba1cControl, 'patients_tracked', 0) . ' HbA1c tracked',
            'icon' => 'beaker',
            'iconClass' => 'amber',
        ],
    ];
}

// Check if quick start banner should be shown
$showQuickStart = !isset($_COOKIE['diabetacare_quickstart_dismissed']);

include BASE_PATH . '/includes/layout/header.php';
?>

<div style="max-width: 1400px;">
    <?php if ($showQuickStart): ?>
    <!-- Quick Start Banner -->
    <div class="quick-start-banner" style="position: relative;">
        <button class="close-btn" onclick="dismissQuickStart()">
            <i data-lucide="x"></i>
        </button>
        <div class="icon">
            <i data-lucide="sparkles"></i>
        </div>
        <div class="content">
            <h3>Welcome to DiabetaCare! 🎉</h3>
            <p>New here? Check out our 2-minute quick start guide to get your clinic up and running.</p>
        </div>
        <a href="<?php echo baseUrl('/quick-start'); ?>" class="btn btn-primary">
            View Quick Start Guide
            <i data-lucide="arrow-right"></i>
        </a>
    </div>
    <?php endif; ?>
    
    <!-- Header -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle">Welcome back, Doctor</p>
        </div>
        <a href="<?php echo baseUrl('/patients/create'); ?>" class="btn btn-primary">
            <i data-lucide="plus"></i>
            Add Patient
        </a>
    </div>
    
    <!-- Stats Grid -->
    <div class="grid grid-4 mb-6">
        <?php foreach ($stats as $stat): ?>
        <div class="card stat-card">
            <div class="flex items-center justify-between mb-3">
                <div class="stat-icon <?php echo e($stat['iconClass']); ?>">
                    <i data-lucide="<?php echo e($stat['icon']); ?>"></i>
                </div>
                <i data-lucide="trending-up" style="width: 1rem; height: 1rem; color: var(--success);"></i>
            </div>
            <p class="stat-value"><?php echo e($stat['value']); ?></p>
            <p class="stat-label"><?php echo e($stat['label']); ?></p>
            <p class="stat-change"><?php echo e($stat['change']); ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Main Content Grid -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
        <!-- Recent Patients -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Recent Patients</h2>
                <a href="<?php echo baseUrl('/patients'); ?>" class="flex items-center gap-1" style="font-size: 0.875rem;">
                    View all <i data-lucide="arrow-right" style="width: 1rem; height: 1rem;"></i>
                </a>
            </div>
            
            <?php if (empty($patientsData)): ?>
            <div class="empty-state">
                <i data-lucide="users"></i>
                <p>No recent patients</p>
                <a href="<?php echo baseUrl('/patients/create'); ?>" class="btn btn-primary btn-sm">Add your first patient</a>
            </div>
            <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>HbA1c</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($patientsData as $patient): ?>
                    <?php
                        $patientId = safeInt($patient, 'id');
                        $firstName = safeStr($patient, 'first_name', '');
                        $lastName = safeStr($patient, 'last_name', '');
                        $patientName = safeStr($patient, 'name', trim("$firstName $lastName"));
                        $patientCode = safeStr($patient, 'patient_code', '');
                        $diabetesType = safeStr($patient, 'diabetes_type', 'N/A');
                        $status = safeStr($patient, 'status', 'Active');
                        $lastHba1c = safeFloat($patient, 'last_hba1c');
                    ?>
                    <tr style="cursor: pointer;" onclick="window.location='<?php echo baseUrl('/patients/' . $patientId); ?>'">
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="avatar avatar-sm">
                                    <?php echo e(getInitials($firstName ?: substr($patientName, 0, 1), $lastName ?: '')); ?>
                                </div>
                                <div>
                                    <p class="font-medium" style="color: var(--text-primary); font-size: 0.875rem;">
                                        <?php echo e($patientName ?: 'Unknown'); ?>
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
                        <td>
                            <span class="badge <?php echo e(getStatusBadgeClass($status)); ?>">
                                <?php echo e($status); ?>
                            </span>
                        </td>
                        <td>
                            <span class="<?php echo e(getHbA1cColorClass($lastHba1c)); ?> font-semibold">
                                <?php echo $lastHba1c !== null ? e($lastHba1c) . '%' : 'N/A'; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        
        <!-- Upcoming Appointments -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Today's Appointments</h2>
                <a href="<?php echo baseUrl('/appointments'); ?>" class="flex items-center gap-1" style="font-size: 0.875rem;">
                    View all <i data-lucide="arrow-right" style="width: 1rem; height: 1rem;"></i>
                </a>
            </div>
            
            <?php if (empty($appointmentsData)): ?>
            <div class="empty-state">
                <i data-lucide="calendar"></i>
                <p>No appointments today</p>
                <a href="<?php echo baseUrl('/appointments/create'); ?>" class="btn btn-primary btn-sm">Schedule appointment</a>
            </div>
            <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <?php foreach ($appointmentsData as $apt): ?>
                <?php
                    $patientName = safeStr($apt, 'patient_name', 'Unknown');
                    $nameParts = explode(' ', $patientName);
                    $aptTime = safeStr($apt, 'time', '');
                    $aptStatus = safeStr($apt, 'status', 'Scheduled');
                ?>
                <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: var(--surface-secondary); border-radius: var(--radius-md);">
                    <div class="avatar avatar-sm">
                        <?php echo e(getInitials($nameParts[0] ?? '', $nameParts[1] ?? '')); ?>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <p class="font-medium text-sm" style="color: var(--text-primary);">
                            <?php echo e($patientName); ?>
                        </p>
                        <div class="flex items-center gap-2 text-xs" style="color: var(--text-muted);">
                            <i data-lucide="clock" style="width: 0.75rem; height: 0.75rem;"></i>
                            <span><?php echo formatTime($aptTime); ?></span>
                        </div>
                    </div>
                    <span class="badge <?php echo e(getStatusBadgeClass($aptStatus)); ?>">
                        <?php echo e($aptStatus); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php 
    $totalAlerts = safeInt($alertsData, 'total_alerts', 0);
    $alerts = safeGet($alertsData, 'alerts', []);
    $highHba1cAlerts = safeGet($alerts, 'high_hba1c', []);
    $noRecentVisitAlerts = safeGet($alerts, 'no_recent_visit', []);
    $criticalLabsAlerts = safeGet($alerts, 'critical_labs', []);
    ?>
    <?php if ($alertsData && $totalAlerts > 0): ?>
    <!-- Critical Alerts Section -->
    <div class="card mt-6">
        <div class="card-header">
            <h2 class="card-title flex items-center gap-2">
                <i data-lucide="alert-triangle" style="width: 1.25rem; height: 1.25rem; color: var(--warning);"></i>
                Critical Alerts
                <span class="badge badge-warning"><?php echo e($totalAlerts); ?></span>
            </h2>
        </div>
        
        <div class="grid grid-3">
            <?php if (!empty($highHba1cAlerts)): ?>
            <div>
                <h4 class="text-sm font-semibold mb-2" style="color: var(--danger);">High HbA1c (&gt;8%)</h4>
                <?php foreach (array_slice($highHba1cAlerts, 0, 3) as $alert): ?>
                <div class="flex items-center gap-2 mb-2 text-sm">
                    <span class="font-medium"><?php echo e(safeStr($alert, 'patient_name', 'Unknown')); ?></span>
                    <span class="text-danger font-semibold"><?php echo e(safeStr($alert, 'hba1c', '')); ?>%</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($noRecentVisitAlerts)): ?>
            <div>
                <h4 class="text-sm font-semibold mb-2" style="color: var(--warning);">No Recent Visit</h4>
                <?php foreach (array_slice($noRecentVisitAlerts, 0, 3) as $alert): ?>
                <div class="flex items-center gap-2 mb-2 text-sm">
                    <span class="font-medium"><?php echo e(safeStr($alert, 'patient_name', 'Unknown')); ?></span>
                    <span style="color: var(--text-muted);"><?php echo e(safeStr($alert, 'days_since_visit', '')); ?> days</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($criticalLabsAlerts)): ?>
            <div>
                <h4 class="text-sm font-semibold mb-2" style="color: var(--danger);">Critical Lab Results</h4>
                <?php foreach (array_slice($criticalLabsAlerts, 0, 3) as $alert): ?>
                <div class="flex items-center gap-2 mb-2 text-sm">
                    <span class="font-medium"><?php echo e(safeStr($alert, 'patient_name', 'Unknown')); ?></span>
                    <span style="color: var(--text-muted);"><?php echo e(safeStr($alert, 'test_name', '')); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Charts Section -->
    <?php if ($charts): ?>
    <div class="grid grid-2 mt-6" style="gap: 1.5rem;">
        <!-- HbA1c Distribution Chart -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title flex items-center gap-2">
                    <i data-lucide="pie-chart" style="width: 1.25rem; height: 1.25rem; color: var(--accent);"></i>
                    HbA1c Distribution
                </h2>
            </div>
            <div style="height: 280px; display: flex; align-items: center; justify-content: center;">
                <canvas id="hba1cChart"></canvas>
            </div>
        </div>
        
        <!-- Diabetes Types Chart -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title flex items-center gap-2">
                    <i data-lucide="users" style="width: 1.25rem; height: 1.25rem; color: var(--accent);"></i>
                    Patients by Diabetes Type
                </h2>
            </div>
            <div style="height: 280px; display: flex; align-items: center; justify-content: center;">
                <canvas id="diabetesTypesChart"></canvas>
            </div>
        </div>
        
        <!-- Appointments Trend Chart -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title flex items-center gap-2">
                    <i data-lucide="trending-up" style="width: 1.25rem; height: 1.25rem; color: var(--accent);"></i>
                    Monthly Appointments Trend
                </h2>
            </div>
            <div style="height: 280px;">
                <canvas id="appointmentTrendChart"></canvas>
            </div>
        </div>
        
        <!-- Appointment Status Chart -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title flex items-center gap-2">
                    <i data-lucide="calendar-check" style="width: 1.25rem; height: 1.25rem; color: var(--accent);"></i>
                    This Week's Appointments by Status
                </h2>
            </div>
            <div style="height: 280px;">
                <canvas id="appointmentStatusChart"></canvas>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
function dismissQuickStart() {
    document.cookie = 'diabetacare_quickstart_dismissed=true; path=/; max-age=31536000';
    document.querySelector('.quick-start-banner').style.display = 'none';
}

// Chart.js Configuration
<?php if ($charts): ?>
document.addEventListener('DOMContentLoaded', function() {
    // Chart.js defaults for dark theme
    Chart.defaults.color = '#94a3b8';
    Chart.defaults.borderColor = 'rgba(148, 163, 184, 0.1)';
    
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    usePointStyle: true,
                    pointStyle: 'circle',
                    font: { size: 11 }
                }
            }
        }
    };
    
    // HbA1c Distribution Doughnut Chart
    const hba1cData = <?php echo json_encode($charts['hba1c_distribution'] ?? []); ?>;
    if (hba1cData.data && hba1cData.data.some(v => v > 0)) {
        new Chart(document.getElementById('hba1cChart'), {
            type: 'doughnut',
            data: {
                labels: hba1cData.labels || [],
                datasets: [{
                    data: hba1cData.data || [],
                    backgroundColor: hba1cData.backgroundColor || ['#22c55e', '#f59e0b', '#f97316', '#ef4444'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                ...chartOptions,
                cutout: '60%',
                plugins: {
                    ...chartOptions.plugins,
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.raw / total) * 100).toFixed(1);
                                return `${context.label}: ${context.raw} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    } else {
        document.getElementById('hba1cChart').parentElement.innerHTML = '<p style="color: var(--text-muted); text-align: center;">No HbA1c data available</p>';
    }
    
    // Diabetes Types Pie Chart
    const diabetesData = <?php echo json_encode($charts['diabetes_types'] ?? []); ?>;
    if (diabetesData.data && diabetesData.data.some(v => v > 0)) {
        new Chart(document.getElementById('diabetesTypesChart'), {
            type: 'pie',
            data: {
                labels: diabetesData.labels || [],
                datasets: [{
                    data: diabetesData.data || [],
                    backgroundColor: diabetesData.backgroundColor || ['#3b82f6', '#8b5cf6', '#ec4899', '#06b6d4'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                ...chartOptions,
                plugins: {
                    ...chartOptions.plugins,
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.raw / total) * 100).toFixed(1);
                                return `${context.label}: ${context.raw} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    } else {
        document.getElementById('diabetesTypesChart').parentElement.innerHTML = '<p style="color: var(--text-muted); text-align: center;">No patient data available</p>';
    }
    
    // Appointments Trend Line Chart
    const trendData = <?php echo json_encode($charts['appointment_trend'] ?? []); ?>;
    if (trendData.labels && trendData.labels.length > 0) {
        new Chart(document.getElementById('appointmentTrendChart'), {
            type: 'line',
            data: {
                labels: trendData.labels || [],
                datasets: [{
                    label: 'Appointments',
                    data: trendData.data || [],
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                ...chartOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 },
                        grid: { color: 'rgba(148, 163, 184, 0.1)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    } else {
        document.getElementById('appointmentTrendChart').innerHTML = '<p style="color: var(--text-muted); text-align: center; padding-top: 100px;">No appointment trend data</p>';
    }
    
    // Appointment Status Bar Chart
    const statusData = <?php echo json_encode($charts['appointment_status'] ?? []); ?>;
    if (statusData.labels && statusData.labels.length > 0) {
        const statusColors = {
            'Scheduled': '#3b82f6',
            'Completed': '#22c55e',
            'Cancelled': '#f97316',
            'No-show': '#ef4444'
        };
        const bgColors = statusData.labels.map(label => statusColors[label] || '#6366f1');
        
        new Chart(document.getElementById('appointmentStatusChart'), {
            type: 'bar',
            data: {
                labels: statusData.labels || [],
                datasets: [{
                    label: 'Appointments',
                    data: statusData.data || [],
                    backgroundColor: bgColors,
                    borderRadius: 6,
                    maxBarThickness: 50
                }]
            },
            options: {
                ...chartOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 },
                        grid: { color: 'rgba(148, 163, 184, 0.1)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    } else {
        document.getElementById('appointmentStatusChart').innerHTML = '<p style="color: var(--text-muted); text-align: center; padding-top: 100px;">No appointment status data</p>';
    }
});
<?php endif; ?>
</script>

<?php include BASE_PATH . '/includes/layout/footer.php'; ?>
