<?php

namespace Drivejob\Controllers;

use Drivejob\Core\AuthMiddleware;
use Drivejob\Core\CSRF;
use Drivejob\Core\Database;
use Drivejob\Core\Logger;
use Drivejob\Core\Session;
use Drivejob\Helpers\JsonHelper;
use Drivejob\Services\Driver\DriverProfileService;
use Drivejob\Services\Score\InsuranceXlsx;
use PDO;
use Throwable;

/**
 * Ασφαλιστικό ιστορικό οδηγού — ανέβασμα, σύνοψη, διαγραφή. (01/09/2026)
 *
 * Η ροή: ο οδηγός κατεβάζει από το gov.gr τον «Αναλυτικό Λογαριασμό
 * Ασφάλισης» (.xlsx), τον ανεβάζει εδώ, βλέπει ΤΙ διαβάστηκε πριν
 * αποθηκευτεί οτιδήποτε μόνιμα, και οι περίοδοι μπαίνουν στη βαθμολογία
 * ως VERIFIED πηγή — μειωμένες μέχρι τον έλεγχο γνησιότητας.
 *
 * ΤΟ ΑΡΧΕΙΟ ΔΕΝ ΑΠΟΘΗΚΕΥΕΤΑΙ. Διαβάζεται από το tmp του PHP,
 * εξάγονται οι περίοδοι, και τελείωσε. Περιέχει το πλήρες μισθολογικό
 * ιστορικό ενός ανθρώπου — δεν θέλουμε ούτε την ευθύνη ούτε τον
 * πειρασμό. Στη βάση μένουν: φορέας, είδος, εργοδότης, περίοδος, μήνες.
 */
class InsuranceController extends BaseController
{
    private const MAX_FILE_BYTES = 2_000_000;   // τα πραγματικά είναι ~20KB

    private PDO $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance()->getConnection();
    }

    /** GET /drivers/insurance — σύνοψη + φόρμα ανεβάσματος. */
    public function index(): void
    {
        AuthMiddleware::hasRole('driver');
        $driverId = (int) Session::get('user_id');

        $stmt = $this->db->prepare(
            'SELECT fund_kind, employer_name, verified,
                    MIN(date_from) AS date_from, MAX(date_to) AS date_to,
                    SUM(months) AS months, COUNT(*) AS periods
             FROM driver_insurance_periods
             WHERE driver_id = ?
             GROUP BY fund_kind, employer_name, verified
             ORDER BY MIN(date_from)'
        );
        $stmt->execute([$driverId]);
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totals = ['employee' => 0.0, 'self_employed' => 0.0];
        foreach ($groups as $g) {
            $totals[$g['fund_kind']] += (float) $g['months'];
        }

        $pageTitle = 'Ασφαλιστικό Ιστορικό';
        include ROOT_DIR . '/src/Views/partials/header.php';
        include ROOT_DIR . '/src/Views/drivers/insurance.php';
        include ROOT_DIR . '/src/Views/partials/footer.php';
    }

    /** POST /drivers/insurance — ανέβασμα και ανάγνωση xlsx. */
    public function upload(): void
    {
        AuthMiddleware::hasRole('driver');
        $driverId = (int) Session::get('user_id');

        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            JsonHelper::error('Η φόρμα έληξε. Ανανεώστε τη σελίδα και δοκιμάστε ξανά.');
            return;
        }

        $file = $_FILES['insurance_file'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            JsonHelper::error('Δεν στάλθηκε αρχείο. Επίλεξε το .xlsx από το gov.gr.');
            return;
        }
        if ((int) $file['size'] > self::MAX_FILE_BYTES) {
            JsonHelper::error('Το αρχείο είναι υπερβολικά μεγάλο για Αναλυτικό Λογαριασμό.');
            return;
        }
        // Ο έλεγχος είναι στο ΠΕΡΙΕΧΟΜΕΝΟ (ο parser απαιτεί τις στήλες
        // του e-ΕΦΚΑ) — η κατάληξη απλώς κόβει τα προφανή λάθη.
        if (!preg_match('/\.xlsx$/iu', (string) $file['name'])) {
            JsonHelper::error('Το αρχείο πρέπει να είναι .xlsx (όπως το δίνει το gov.gr).');
            return;
        }

        try {
            $parsed = (new InsuranceXlsx())->parse($file['tmp_name']);
        } catch (Throwable $e) {
            JsonHelper::error($e->getMessage());
            return;
        }

        if (!$parsed['periods']) {
            JsonHelper::error('Δεν αναγνωρίστηκε καμία περίοδος ασφάλισης στο αρχείο.');
            return;
        }

        /*
         * INSERT IGNORE πάνω στο μοναδικό κλειδί (driver, fund, employer,
         * from, to): το ίδιο αρχείο ανεβασμένο δεύτερη φορά δεν
         * διπλασιάζει τίποτα — και δύο αρχεία με επικάλυψη περιόδων
         * κρατούν μία εγγραφή ανά περίοδο.
         */
        $ins = $this->db->prepare(
            'INSERT IGNORE INTO driver_insurance_periods
                (driver_id, fund, fund_kind, employer_name, date_from, date_to, months, verified, uploaded_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())'
        );

        $added = 0;
        $employeeMonths = 0.0;
        $selfMonths = 0.0;
        foreach ($parsed['periods'] as $p) {
            $ins->execute([
                $driverId, $p['fund'], $p['fund_kind'],
                $p['employer_name'] !== '' ? mb_substr($p['employer_name'], 0, 190) : '',
                $p['date_from'], $p['date_to'], $p['months'],
            ]);
            $added += $ins->rowCount();
            if ($p['fund_kind'] === 'employee') {
                $employeeMonths += $p['months'];
            } else {
                $selfMonths += $p['months'];
            }
        }

        // Νέο τεκμήριο → νέα βαθμολογία, τώρα.
        try {
            (new DriverProfileService($this->db))->updateDriverRating($driverId);
        } catch (Throwable $e) {
            Logger::error('Επανυπολογισμός μετά από ένσημα απέτυχε: ' . $e->getMessage());
        }

        JsonHelper::success(
            sprintf(
                'Διαβάστηκαν %d περίοδοι (%s νέες): %s μήνες μισθωτής και %s μήνες αυτοαπασχόλησης. Το αρχείο ΔΕΝ αποθηκεύτηκε — μόνο η σύνοψη.',
                count($parsed['periods']),
                $added,
                number_format($employeeMonths, 1, ',', '.'),
                number_format($selfMonths, 1, ',', '.')
            ),
            ['added' => $added]
        );
    }

    /** POST /drivers/insurance/delete — διαγραφή ΟΛΩΝ των περιόδων του οδηγού. */
    public function destroy(): void
    {
        AuthMiddleware::hasRole('driver');
        $driverId = (int) Session::get('user_id');

        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            JsonHelper::error('Η φόρμα έληξε. Ανανεώστε τη σελίδα και δοκιμάστε ξανά.');
            return;
        }

        /*
         * Όλα ή τίποτα, με ΕΝΑ κουμπί: είναι η δικλείδα «διαγραφή
         * δεδομένων οποτεδήποτε» του σχεδιασμού ιδιωτικότητας —
         * τα δεδομένα είναι δικά του, φεύγουν όποτε το ζητήσει.
         */
        $this->db->prepare('DELETE FROM driver_insurance_periods WHERE driver_id = ?')
            ->execute([$driverId]);

        try {
            (new DriverProfileService($this->db))->updateDriverRating($driverId);
        } catch (Throwable $e) {
            Logger::error('Επανυπολογισμός μετά από διαγραφή ενσήμων απέτυχε: ' . $e->getMessage());
        }

        JsonHelper::success('Το ασφαλιστικό ιστορικό διαγράφηκε από το προφίλ σου.');
    }
}
