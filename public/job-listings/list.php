<?php

/**
 * Job Listings List Page
 */

require_once __DIR__ . '/../../src/bootstrap.php';

use Drivejob\Core\Database;
use Drivejob\Core\Session;

Session::start();

$pdo = Database::getInstance()->getConnection();

// Get active job listings (only job offers from companies)
$stmt = $pdo->query("
    SELECT j.*, c.company_name, c.city as company_city
    FROM job_listings j
    LEFT JOIN companies c ON j.company_id = c.id
    WHERE j.listing_type = 'job_offer' 
    AND j.status = 'active'
    AND j.company_id IS NOT NULL
    ORDER BY j.created_at DESC
");

$jobListings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set the variable name that the view expects
$listings = $jobListings;

// Include view
if (file_exists(__DIR__ . '/../../src/Views/job-listings/index.php')) {
    require_once __DIR__ . '/../../src/Views/job-listings/index.php';
} else {
    // Simple view
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
            <h1>Διαθέσιμες Αγγελίες Εργασίας</h1>

            <div class="row mt-4">
                <?php foreach ($jobListings as $job): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($job['title']); ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted">
                                    <?php echo htmlspecialchars($job['company_name']); ?> -
                                    <?php echo htmlspecialchars($job['location']); ?>
                                </h6>
                                <p class="card-text">
                                    <?php echo htmlspecialchars(substr($job['description'], 0, 150)); ?>...
                                </p>
                                <a href="/drivejob/public/job-listings/view.php?id=<?php echo $job['id']; ?>"
                                    class="btn btn-primary">Δείτε Περισσότερα</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($jobListings)): ?>
                <div class="alert alert-info">
                    Δεν υπάρχουν διαθέσιμες αγγελίες αυτή τη στιγμή.
                </div>
            <?php endif; ?>
        </div>

        <?php include __DIR__ . '/../../src/Views/partials/footer.php'; ?>
    </body>

    </html>
<?php
}
