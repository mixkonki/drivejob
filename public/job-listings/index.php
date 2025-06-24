<?php
/**
 * Job Listings Directory Handler
 * Shows available job listings or redirects appropriately
 */

// Check if there are actual job listing files in this directory
$jobFiles = glob(__DIR__ . '/*.php');
$jobFiles = array_filter($jobFiles, function($file) {
    $basename = basename($file);
    return $basename !== 'index.php' && $basename !== 'create.php' && $basename !== 'store.php';
});

if (!empty($jobFiles)) {
    // If there are job listing files, show the first one or a listing page
    require_once __DIR__ . '/../../src/bootstrap.php';
    
    // Include the job listings view or controller
    if (file_exists(__DIR__ . '/../../src/Views/job-listings/index.php')) {
        require_once __DIR__ . '/../../src/Views/job-listings/index.php';
    } else {
        // Fallback: Show a simple listing
        ?>
        <!DOCTYPE html>
        <html lang="el">
        <head>
            <meta charset="UTF-8">
            <title>Αγγελίες Εργασίας - DriveJob</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body>
            <?php include __DIR__ . '/../../src/Views/partials/header.php'; ?>
            
            <div class="container mt-4">
                <h1>Αγγελίες Εργασίας</h1>
                <p>Βρείτε την ιδανική θέση εργασίας για εσάς!</p>
                
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <h4>Διαθέσιμες Αγγελίες</h4>
                            <p>Για να δείτε τις διαθέσιμες αγγελίες, παρακαλούμε συνδεθείτε:</p>
                            <a href="/drivejob/public/login.php" class="btn btn-primary">Σύνδεση</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include __DIR__ . '/../../src/Views/partials/footer.php'; ?>
        </body>
        </html>
        <?php
    }
} else {
    // No job files, redirect to appropriate location based on session
    session_start();
    
    if (isset($_SESSION['user_role'])) {
        if ($_SESSION['user_role'] === 'company') {
            // Companies should go to their job management area
            header('Location: /drivejob/public/companies/company-profile.php#jobs');
        } elseif ($_SESSION['user_role'] === 'driver') {
            // Drivers should go to job search
            header('Location: /drivejob/public/drivers/driver-profile.php#jobs');
        } else {
            header('Location: /drivejob/public/');
        }
    } else {
        // Not logged in - redirect to login with return URL
        header('Location: /drivejob/public/login.php?redirect=' . urlencode('/drivejob/public/job-listings/'));
    }
    exit;
}