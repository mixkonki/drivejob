<?php
/**
 * Job Listings Index
 * Redirects to the main job listings page
 */

// Start session
session_start();

// Redirect based on user type
if (isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === 'company') {
        header('Location: /drivejob/public/companies/job-listings');
    } elseif ($_SESSION['user_role'] === 'driver') {
        header('Location: /drivejob/public/drivers/job-listings');
    } else {
        header('Location: /drivejob/public/');
    }
} else {
    // Not logged in - show public job listings
    header('Location: /drivejob/public/');
}
exit;