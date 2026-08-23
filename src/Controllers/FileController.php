<?php

namespace Drivejob\Controllers;

use Drivejob\Core\Session;
use Drivejob\Core\Logger;
use Drivejob\Core\Container;

/**
 * Σερβίρισμα αρχείων uploads με έλεγχο πρόσβασης ΚΑΙ ιδιοκτησίας.
 *
 * Τα αρχεία ζουν στο storage/uploads/ (ΕΚΤΟΣ web root).
 * URLs: /uploads/{folder}/{filename} — μέσω route, όχι web server.
 *
 * Κανόνες πρόσβασης (v2 — Πακέτο 3, Φάση 6):
 *  - profile_images, company_logos: δημόσια
 *  - admin: όλα
 *  - οδηγός: ΜΟΝΟ τα αρχεία που αναφέρονται στο δικό του προφίλ/πιστοποιήσεις
 *  - εταιρεία: αρχεία οδηγού ΜΟΝΟ αν ο οδηγός έχει αίτηση σε δική της αγγελία
 */
class FileController extends BaseController
{
    /** Φάκελοι με δημόσια πρόσβαση */
    private const PUBLIC_FOLDERS = ['profile_images', 'company_logos'];

    /** Φάκελοι που απαιτούν σύνδεση + ιδιοκτησία */
    private const PRIVATE_FOLDERS = [
        'resumes',
        'licenses', 'license_images',
        'tachographs', 'tachograph_images',
        'adr_certificates', 'adr_images',
        'operator_licenses',
        'certificates',
        'misc',
    ];

    /** Στήλες του πίνακα drivers που κρατούν paths αρχείων */
    private const DRIVER_FILE_COLUMNS = [
        'profile_image', 'resume_file',
        'license_front_image', 'license_back_image',
        'tachograph_front_image', 'tachograph_back_image',
        'adr_front_image', 'adr_back_image',
        'operator_front_image', 'operator_back_image',
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
                $this->deny($folder, $filename, 'μη συνδεδεμένος');
            }
            if (!$this->authorize($filename)) {
                $this->deny($folder, $filename, 'χωρίς δικαίωμα πρόσβασης');
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

    /**
     * Έλεγχος ιδιοκτησίας: ποιος επιτρέπεται να δει το συγκεκριμένο αρχείο.
     */
    private function authorize(string $filename): bool
    {
        $role = Session::get('user_role');
        $userId = (int) Session::get('user_id');

        if ($role === 'admin') {
            return true;
        }

        try {
            $pdo = Container::getInstance()->get('pdo');

            /*
             * Συνημμένα προσφοράς εργασίας.
             *
             * Αυτά τα ανεβάζει η ΕΤΑΙΡΕΙΑ, όχι ο οδηγός — σύμβαση, περιγραφή
             * θέσης, εταιρικό έντυπο. Ο έλεγχος παρακάτω ψάχνει σε ποιον
             * ΟΔΗΓΟ ανήκει το αρχείο, δεν βρίσκει κανέναν, και κλείνει την
             * πόρτα (fail-closed). Σωστή προεπιλογή, λάθος απάντηση εδώ:
             * χωρίς αυτόν τον έλεγχο η εταιρεία δεν βλέπει ούτε το έγγραφο
             * που έστειλε η ίδια.
             *
             * Δικαίωμα έχουν ακριβώς δύο: αυτός που το έστειλε και αυτός που
             * το έλαβε.
             */
            $offerAccess = $this->offerAttachmentAccess($pdo, $filename, $role, $userId);
            if ($offerAccess !== null) {
                return $offerAccess;
            }

            $ownerDriverId = $this->findOwnerDriverId($pdo, $filename);

            if ($ownerDriverId === null) {
                // Αρχείο που δεν αναφέρεται πουθενά στη βάση: fail-closed (μόνο admin)
                return false;
            }

            if ($role === 'driver') {
                return $ownerDriverId === $userId;
            }

            if ($role === 'company') {
                // Η εταιρεία βλέπει αρχεία οδηγού μόνο αν υπάρχει αίτησή του σε δική της αγγελία.
                // Καλύπτονται και τα δύο σχήματα: jl.company_id = companies.id (session) ή companies.user_id.
                $st = $pdo->prepare("
                    SELECT 1
                    FROM job_applications ja
                    JOIN job_listings jl ON jl.id = ja.job_listing_id
                    LEFT JOIN companies c ON c.id = jl.company_id
                    WHERE ja.driver_id = :did
                      AND (jl.company_id = :cid1 OR c.user_id = :cid2)
                    LIMIT 1
                ");
                $st->execute(['did' => $ownerDriverId, 'cid1' => $userId, 'cid2' => $userId]);
                return (bool) $st->fetchColumn();
            }
        } catch (\Throwable $e) {
            Logger::error('FileController authorize error: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Πρόσβαση σε συνημμένο προσφοράς εργασίας.
     *
     * @return bool|null true/false αν το αρχείο ανήκει σε προσφορά,
     *                   null αν δεν ανήκει σε καμία (συνεχίζει ο κανονικός έλεγχος).
     */
    private function offerAttachmentAccess(\PDO $pdo, string $filename, ?string $role, int $userId): ?bool
    {
        $columns = ['document_path', 'contract_template_path', 'job_description_path', 'company_brochure_path'];

        $conditions = [];
        $params = ['p' => '%' . $filename];
        foreach ($columns as $col) {
            $conditions[] = "$col LIKE :p";
        }

        try {
            $st = $pdo->prepare(
                'SELECT company_id, driver_id FROM job_offers WHERE ' . implode(' OR ', $conditions) . ' LIMIT 1'
            );
            $st->execute($params);
            $row = $st->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // Ο πίνακας δεν υπάρχει ακόμη σε αυτό το περιβάλλον.
            return null;
        }

        if (!$row) {
            return null;
        }

        if ($role === 'company') {
            return (int) $row['company_id'] === $userId;
        }

        if ($role === 'driver') {
            return (int) $row['driver_id'] === $userId;
        }

        return false;
    }

    /**
     * Βρίσκει σε ποιον οδηγό ανήκει το αρχείο (μέσω των paths στη βάση).
     */
    private function findOwnerDriverId(\PDO $pdo, string $filename): ?int
    {
        $like = '%' . $filename;

        // 1. Στήλες του πίνακα drivers
        $conditions = [];
        $params = [];
        foreach (self::DRIVER_FILE_COLUMNS as $i => $col) {
            $conditions[] = "$col LIKE :p$i";
            $params["p$i"] = $like;
        }
        try {
            $st = $pdo->prepare("SELECT id FROM drivers WHERE " . implode(' OR ', $conditions) . " LIMIT 1");
            $st->execute($params);
            $id = $st->fetchColumn();
            if ($id !== false) {
                return (int) $id;
            }
        } catch (\Throwable $e) {
            // Κάποια στήλη ίσως δεν υπάρχει σε αυτό το σχήμα — δοκίμασε μία-μία
            foreach (self::DRIVER_FILE_COLUMNS as $col) {
                try {
                    $st = $pdo->prepare("SELECT id FROM drivers WHERE $col LIKE :p LIMIT 1");
                    $st->execute(['p' => $like]);
                    $id = $st->fetchColumn();
                    if ($id !== false) {
                        return (int) $id;
                    }
                } catch (\Throwable $inner) {
                    continue;
                }
            }
        }

        // 2. Πιστοποιήσεις (driver_certifications.certificate_file)
        try {
            $st = $pdo->prepare("SELECT driver_id FROM driver_certifications WHERE certificate_file LIKE :p LIMIT 1");
            $st->execute(['p' => $like]);
            $id = $st->fetchColumn();
            if ($id !== false) {
                return (int) $id;
            }
        } catch (\Throwable $e) {
            // πίνακας/στήλη δεν υπάρχει — συνεχίζουμε
        }

        return null;
    }

    private function deny(string $folder, string $filename, string $reason): void
    {
        Logger::warning('Άρνηση πρόσβασης σε αρχείο', [
            'folder' => $folder,
            'file' => $filename,
            'reason' => $reason,
            'user_id' => Session::get('user_id'),
            'role' => Session::get('user_role'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Δεν έχετε δικαίωμα πρόσβασης σε αυτό το αρχείο.';
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
