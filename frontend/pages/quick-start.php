<?php
/**
 * DiabetaCare - Quick Start Guide Page
 */

$pageTitle = 'Quick Start Guide';

include BASE_PATH . '/includes/layout/header.php';
?>

<div style="max-width: 900px; margin: 0 auto;">
    <!-- Header -->
    <div class="page-header">
        <div class="flex items-center gap-4">
            <a href="<?php echo baseUrl('/'); ?>" class="btn btn-icon btn-outline">
                <i data-lucide="arrow-left"></i>
            </a>
            <div>
                <h1 class="page-title mb-0">Quick Start Guide</h1>
                <p class="page-subtitle">Get started with DiabetaCare in minutes</p>
            </div>
        </div>
    </div>
    
    <!-- Welcome Card -->
    <div class="card" style="background: linear-gradient(135deg, var(--accent) 0%, #14b8a6 100%); color: white; border: none;">
        <div class="card-body" style="padding: 2rem;">
            <div class="flex items-center gap-4 mb-4">
                <div style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="heart-pulse" style="width: 32px; height: 32px;"></i>
                </div>
                <div>
                    <h2 style="font-size: 1.5rem; font-weight: 700; margin: 0;">Welcome to DiabetaCare</h2>
                    <p style="opacity: 0.9; margin: 0;">Your comprehensive diabetes patient management solution</p>
                </div>
            </div>
            <p style="opacity: 0.95;">
                DiabetaCare helps healthcare providers efficiently manage diabetic patients with features like 
                patient tracking, medication management, lab result monitoring, and appointment scheduling.
            </p>
        </div>
    </div>
    
    <!-- Steps -->
    <div class="quick-start-steps">
        <!-- Step 1 -->
        <div class="card">
            <div class="card-body">
                <div class="flex gap-4">
                    <div class="step-number">1</div>
                    <div style="flex: 1;">
                        <h3 class="step-title">Add Your First Patient</h3>
                        <p class="step-description">
                            Start by adding a patient to the system. You'll need their basic information, 
                            diabetes type, and medical history.
                        </p>
                        <div class="step-actions">
                            <a href="<?php echo baseUrl('/patients/create'); ?>" class="btn btn-primary btn-sm">
                                <i data-lucide="user-plus"></i>
                                Add Patient
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Step 2 -->
        <div class="card">
            <div class="card-body">
                <div class="flex gap-4">
                    <div class="step-number">2</div>
                    <div style="flex: 1;">
                        <h3 class="step-title">Record Lab Results</h3>
                        <p class="step-description">
                            Enter HbA1c, glucose levels, and other important lab values to track patient health over time.
                        </p>
                        <div class="step-actions">
                            <a href="<?php echo baseUrl('/lab-results/create'); ?>" class="btn btn-primary btn-sm">
                                <i data-lucide="flask-conical"></i>
                                Add Lab Result
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Step 3 -->
        <div class="card">
            <div class="card-body">
                <div class="flex gap-4">
                    <div class="step-number">3</div>
                    <div style="flex: 1;">
                        <h3 class="step-title">Manage Medications</h3>
                        <p class="step-description">
                            Keep track of patient prescriptions, dosages, and medication schedules to ensure proper treatment.
                        </p>
                        <div class="step-actions">
                            <a href="<?php echo baseUrl('/medications/create'); ?>" class="btn btn-primary btn-sm">
                                <i data-lucide="pill"></i>
                                Add Medication
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Step 4 -->
        <div class="card">
            <div class="card-body">
                <div class="flex gap-4">
                    <div class="step-number">4</div>
                    <div style="flex: 1;">
                        <h3 class="step-title">Schedule Appointments</h3>
                        <p class="step-description">
                            Book follow-up visits, consultations, and lab work appointments to maintain regular patient care.
                        </p>
                        <div class="step-actions">
                            <a href="<?php echo baseUrl('/appointments/create'); ?>" class="btn btn-primary btn-sm">
                                <i data-lucide="calendar-plus"></i>
                                Schedule Appointment
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Step 5 -->
        <div class="card">
            <div class="card-body">
                <div class="flex gap-4">
                    <div class="step-number">5</div>
                    <div style="flex: 1;">
                        <h3 class="step-title">Monitor Dashboard</h3>
                        <p class="step-description">
                            Use the dashboard to get an overview of patient statistics, upcoming appointments, and critical alerts.
                        </p>
                        <div class="step-actions">
                            <a href="<?php echo baseUrl('/'); ?>" class="btn btn-primary btn-sm">
                                <i data-lucide="layout-dashboard"></i>
                                View Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tips Section -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i data-lucide="lightbulb"></i>
                Tips for Effective Use
            </h3>
        </div>
        <div class="card-body">
            <div class="tips-grid">
                <div class="tip-item">
                    <i data-lucide="target" class="tip-icon"></i>
                    <div>
                        <h4>Set HbA1c Targets</h4>
                        <p>Customize target HbA1c levels for each patient based on their individual health profile.</p>
                    </div>
                </div>
                <div class="tip-item">
                    <i data-lucide="bell" class="tip-icon"></i>
                    <div>
                        <h4>Monitor Alerts</h4>
                        <p>Pay attention to critical alerts on the dashboard for patients with abnormal values.</p>
                    </div>
                </div>
                <div class="tip-item">
                    <i data-lucide="clock" class="tip-icon"></i>
                    <div>
                        <h4>Regular Updates</h4>
                        <p>Update lab results and medications regularly for accurate patient tracking.</p>
                    </div>
                </div>
                <div class="tip-item">
                    <i data-lucide="shield" class="tip-icon"></i>
                    <div>
                        <h4>Data Security</h4>
                        <p>Always log out when leaving your workstation to protect patient information.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Help Section -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i data-lucide="help-circle"></i>
                Need Help?
            </h3>
        </div>
        <div class="card-body">
            <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                If you have questions or need assistance, check out these resources:
            </p>
            <div class="flex gap-3">
                <a href="<?php echo baseUrl('/settings'); ?>" class="btn btn-outline">
                    <i data-lucide="settings"></i>
                    Settings
                </a>
                <a href="mailto:support@diabetacare.example.com" class="btn btn-outline">
                    <i data-lucide="mail"></i>
                    Contact Support
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.step-number {
    width: 48px;
    height: 48px;
    min-width: 48px;
    background: var(--accent);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    font-weight: 700;
}

.step-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.5rem 0;
}

.step-description {
    color: var(--text-secondary);
    margin: 0 0 1rem 0;
    line-height: 1.6;
}

.step-actions {
    display: flex;
    gap: 0.5rem;
}

.quick-start-steps .card {
    margin-bottom: 1rem;
}

.tips-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.tip-item {
    display: flex;
    gap: 1rem;
}

.tip-icon {
    width: 24px;
    height: 24px;
    color: var(--accent);
    min-width: 24px;
}

.tip-item h4 {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.25rem 0;
}

.tip-item p {
    font-size: 0.8125rem;
    color: var(--text-muted);
    margin: 0;
    line-height: 1.5;
}
</style>

<?php include BASE_PATH . '/includes/layout/footer.php'; ?>
