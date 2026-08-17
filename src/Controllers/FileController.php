<?php

namespace Drivejob\Controllers;

use Drivejob\Core\Session;
use Drivejob\Core\Logger;

/**
 * Σερβίρισμα αρχείων uploads με έλεγχο πρόσβασης.
 *
 * Τα αρχεία ζουν στο storage/uploads/ (ΕΚΤΟΣ web root).
 * Τα URLs παραμένουν /uploads/{folder}/{filename} — τα πιάνει route,
 * όχι ο web server, ώστε κανένα αρχείο να μη σερβίρεται χωρίς έλεγχο.
 *
 * Κανόνες πρόσβασης (v1 — αυστηροποίηση ανά ιδιοκτήτη στο Πακέτο 3):
 *  - profile_images, company_logos: δημόσια (εμφανίζονται σε δημόσια προφίλ)
 *  - όλα τα υπόλοιπα (βιογραφικά, διπλώματα, ταχογράφοι, ADR, πιστοποιητικά):
 *    ΜΟΝΟ συνδεδεμένοι χρήστες
 */
class FileController extends BaseController
{
    /** Φάκελοι με δημόσια πρόσβαση */
    private const PUBLIC_FOLDERS = ['profile_images', 'company_logos'];

    /** Φάκελοι που απαιτούν σύνδεση */
    private const PRIVATE_FOLDERS = [
        'resumes',
        'licenses', 'license_images',
        'tachographs', 'tachograph_images',
        'adr_certificates', 'adr_images',
        'operator_licenses',
        'certificates',
        'misc',
    ];

    public function serve($folder, $filename)
    {
        // Αποκωδικοποίηση URL (%20 κ.λπ.) + εξουδετέρωση path traversal
        $folder = basename(rawurldecode((string) $folder));
        $filename = basename(rawurldecode((string) $filename));

        $allowed = array_merge(self::PUBLIC_FOLDERS, self::PRIVATE_FOLDERS);
        if (!in_array($folder, $allowed, true)) {
            $this->notFound();
        }

        // Έλεγχος πρόσβασης για ιδιωτικούς φακέλους
        if (!in_array($folder, self::PUBLIC_FOLDERS, true)) {
            if (!Session::has('user_id')) {
                Logger::warning('Άρνηση πρόσβασης σε ιδιωτικό αρχείο (μη συνδεδεμένος)', [
                    'folder' => $folder,
                    'file' => $filename,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                ]);
                http_response_code(403);
                header('Content-Type: text/plain; charset=utf-8');
                echo 'Απαιτείται σύνδεση για την προβολή αυτού του αρχείου.';
                exit;
            }
        }

        // Επίλυση πραγματικής διαδρομής + έλεγχος ότι μένουμε μέσα στο storage/uploads
        $base = realpath(ROOT_DIR . '/storage/uploads');
        $real = realpath(ROOT_DIR . '/storage/uploads/' . $folder . '/' . $filename);

        if ($base === false || $real === false || strpos($real, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($real)) {
            $this->notFound();
        }

        $mime = function_exists('mime_content_type') ? (mime_content_type($real) ?: 'application/octet-stream') : 'application/octet-stream';

        // Τα HTML/SVG δεν σερβίρονται inline (XSS μέσω uploads)
        $forceDownload = in_array($mime, ['text/html', 'image/svg+xml', 'application/xhtml+xml'], true);

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($real));
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: ' . ($forceDownload ? 'attachment' : 'inline') . '; filename="' . rawurlencode($filename) . '"');
        header('Cache-Control: private, max-age=3600');

        readfile($real);
        exit;
    }

    private function notFound(): void
    {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Το αρχείο δεν βρέθηκε.';
        exit;
    }
}
