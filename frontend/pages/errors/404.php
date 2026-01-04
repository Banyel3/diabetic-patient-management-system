<?php
/**
 * DiabetaCare - 404 Not Found Page
 */
$pageTitle = 'Page Not Found';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | DiabetaCare</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo baseUrl('/assets/css/style.css'); ?>">
    <style>
        .error-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--background);
            padding: 2rem;
        }
        
        .error-content {
            text-align: center;
            max-width: 500px;
        }
        
        .error-code {
            font-size: 8rem;
            font-weight: 700;
            color: var(--accent);
            line-height: 1;
            margin: 0;
        }
        
        .error-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 1rem 0 0.5rem;
        }
        
        .error-message {
            color: var(--text-muted);
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .error-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="error-page">
        <div class="error-content">
            <p class="error-code">404</p>
            <h1 class="error-title">Page Not Found</h1>
            <p class="error-message">
                The page you're looking for doesn't exist or has been moved. 
                Please check the URL or navigate back to the dashboard.
            </p>
            <div class="error-actions">
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i data-lucide="arrow-left"></i>
                    Go Back
                </a>
                <a href="<?php echo baseUrl('/'); ?>" class="btn btn-primary">
                    <i data-lucide="home"></i>
                    Dashboard
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
