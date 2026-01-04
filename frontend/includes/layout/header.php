<?php
/**
 * DiabetaCare - Main Layout Header
 * 
 * Common header for all pages (includes HTML head, CSS, and opening body/container tags)
 */

$pageTitle = $pageTitle ?? 'DiabetaCare';
$currentPath = $path ?? '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?> - DiabetaCare</title>
    
    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="<?php echo baseUrl('/assets/css/style.css'); ?>">
</head>
<body>
    <div class="app-container">
        <?php if (isAuthenticated()): ?>
        <!-- Sidebar -->
        <aside class="sidebar">
            <!-- Logo -->
            <div class="sidebar-logo">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2L2 7L12 12L22 7L12 2Z"></path>
                    <path d="M2 17L12 22L22 17"></path>
                    <path d="M2 12L12 17L22 12"></path>
                </svg>
            </div>
            
            <!-- Main Navigation -->
            <nav class="sidebar-nav">
                <a href="<?php echo baseUrl('/'); ?>" class="sidebar-link <?php echo ($currentPath === '/' || $currentPath === '/dashboard') ? 'active' : ''; ?>" title="Dashboard">
                    <i data-lucide="layout-dashboard"></i>
                    <span class="tooltip">Dashboard</span>
                </a>
                <a href="<?php echo baseUrl('/patients'); ?>" class="sidebar-link <?php echo strpos($currentPath, '/patients') === 0 ? 'active' : ''; ?>" title="Patients">
                    <i data-lucide="users"></i>
                    <span class="tooltip">Patients</span>
                </a>
                <a href="<?php echo baseUrl('/appointments'); ?>" class="sidebar-link <?php echo strpos($currentPath, '/appointments') === 0 ? 'active' : ''; ?>" title="Appointments">
                    <i data-lucide="calendar"></i>
                    <span class="tooltip">Appointments</span>
                </a>
                <a href="<?php echo baseUrl('/medications'); ?>" class="sidebar-link <?php echo strpos($currentPath, '/medications') === 0 ? 'active' : ''; ?>" title="Medications">
                    <i data-lucide="pill"></i>
                    <span class="tooltip">Medications</span>
                </a>
                <a href="<?php echo baseUrl('/lab-results'); ?>" class="sidebar-link <?php echo strpos($currentPath, '/lab-results') === 0 ? 'active' : ''; ?>" title="Lab Results">
                    <i data-lucide="beaker"></i>
                    <span class="tooltip">Lab Results</span>
                </a>
            </nav>
            
            <!-- Bottom Navigation -->
            <div class="sidebar-bottom">
                <a href="<?php echo baseUrl('/quick-start'); ?>" class="sidebar-link <?php echo $currentPath === '/quick-start' ? 'active' : ''; ?>" title="Quick Start">
                    <i data-lucide="sparkles"></i>
                    <span class="tooltip">Quick Start</span>
                </a>
                <a href="<?php echo baseUrl('/settings'); ?>" class="sidebar-link <?php echo $currentPath === '/settings' ? 'active' : ''; ?>" title="Settings">
                    <i data-lucide="settings"></i>
                    <span class="tooltip">Settings</span>
                </a>
                <a href="<?php echo baseUrl('/logout'); ?>" class="sidebar-link logout" title="Log out">
                    <i data-lucide="log-out"></i>
                    <span class="tooltip">Log out</span>
                </a>
            </div>
        </aside>
        <?php endif; ?>
        
        <!-- Main Content -->
        <main class="main-content">
            <?php if (isAuthenticated()): ?>
            <div class="content-wrapper">
            <?php endif; ?>
