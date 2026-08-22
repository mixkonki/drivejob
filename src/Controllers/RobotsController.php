<?php

namespace Drivejob\Controllers;

/**
 * robots.txt — GET /robots.txt
 *
 * Όσο το ALLOW_INDEXING είναι false (προεπιλογή), απαγορεύεται πλήρως η
 * σάρωση. Μόλις ανοίξει, επιτρέπεται το δημόσιο μέρος και αποκλείονται οι
 * ιδιωτικές διαδρομές.
 *
 * Το robots.txt από μόνο του ΔΕΝ εμποδίζει την ευρετηρίαση — γι' αυτό
 * συνοδεύεται από κεφαλίδα X-Robots-Tag και meta robots στα partials.
 */
class RobotsController
{
    public function index(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-store');

        if (defined('ALLOW_INDEXING') && ALLOW_INDEXING) {
            echo "User-agent: *\n";
            echo "Disallow: /admin/\n";
            echo "Disallow: /auth/\n";
            echo "Disallow: /login\n";
            echo "Disallow: /logout\n";
            echo "Disallow: /gdpr/\n";
            echo "Disallow: /cron/\n";
            echo "Disallow: /messages\n";
            echo "Disallow: /drivers/profile\n";
            echo "Disallow: /companies/profile\n";
            echo "Disallow: /job-applications/\n";
            echo "Disallow: /job-offers/\n";
            echo "\n";
            echo 'Sitemap: ' . BASE_URL . "sitemap.xml\n";
        } else {
            echo "# Το DriveJob δεν έχει ανακοινωθεί ακόμη.\n";
            echo "User-agent: *\n";
            echo "Disallow: /\n";
        }
        exit;
    }
}
