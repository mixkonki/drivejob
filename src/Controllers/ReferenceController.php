<?php

namespace Drivejob\Controllers;

use Drivejob\Core\AuthMiddleware;
use Drivejob\Core\CSRF;
use Drivejob\Core\Database;
use Drivejob\Helpers\JsonHelper;
use Drivejob\Core\Logger;
use Drivejob\Core\Session;
use Drivejob\Services\Driver\DriverProfileService;
use Drivejob\Services\EmailService;
use PDO;

/**
 * Συστάσεις εργοδοτών — πρόσκληση με σύνδεσμο, χωρίς λογαριασμό.
 * (01/09/2026 — βήμα 6 του πλάνου βαθμολογίας)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ Ο ΣΥΝΔΕΣΜΟΣ ΕΙΝΑΙ ΤΟ ΠΡΟΪΟΝ, ΟΧΙ ΤΟ EMAIL
 * ══════════════════════════════════════════════════════════════════════
 *
 * Ο πίνακας driver_reviews υπήρχε από την αρχή του project με ΜΗΔΕΝ
 * γραμμές. Ο λόγος ήταν αρχιτεκτονικός: το company_id ήταν υποχρεωτικό,
 * άρα αξιολογούσε μόνο εγγεγραμμένη εταιρεία. Ο παλιός εργοδότης του
 * οδηγού όμως — το βενζινάδικο, η μεταφορική με τα τρία φορτηγά — ΔΕΝ
 * έχει λογαριασμό στο DriveJob και δεν πρόκειται να φτιάξει για να
 * κάνει μια χάρη.
 *
 * Γι' αυτό: ο οδηγός παίρνει έναν σύνδεσμο και τον στέλνει ΟΠΩΣ ΘΕΛΕΙ —
 * Viber, WhatsApp, SMS, από κοντά. Το email είναι προαιρετική ευκολία,
 * όχι προϋπόθεση. Στην αγορά των μεταφορών το «σου έστειλα κάτι στο
 * Viber» δουλεύει· το «θα λάβετε ηλεκτρονικό μήνυμα» όχι.
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΙ ΕΜΠΟΔΙΖΕΙ ΤΗΝ ΚΑΤΑΧΡΗΣΗ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Ο οδηγός θα μπορούσε να στείλει τον σύνδεσμο στον κουμπάρο του. Τα
 * αναχώματα, με σειρά ισχύος:
 *
 *   1. Η ΣΥΡΡΙΚΝΩΣΗ ΤΟΥ ΜΕΣΟΥ ΟΡΟΥ (EmployerReviewCollector): μία
 *      αξιολόγηση 5/5 δίνει 67, όχι 100. Το «φτιαχτό» 5άρι από έναν
 *      φίλο μετακινεί τη βελόνα λίγο — πέντε αληθινές μαρτυρίες τη
 *      μετακινούν πολύ. Το ψέμα δεν κλιμακώνεται εύκολα.
 *   2. Στοιχεία αξιολογητή υποχρεωτικά (όνομα, εταιρεία): η μαρτυρία
 *      είναι επώνυμη και εμφανίζεται με το όνομά της.
 *   3. Όριο εκκρεμών προσκλήσεων (3) και συνόλου (15): όχι εργοστάσιο.
 *   4. Ο οδηγός ΔΕΝ βλέπει τη βαθμολογία ανά αξιολόγηση — μόνο το
 *      σύνολο. Δεν μπορεί να κυνηγήσει τον «κακό» αξιολογητή.
 *
 * Ό,τι δεν πιάνουν αυτά, το πιάνει αργότερα η επαλήθευση στοιχείων
 * (verified_at μένει NULL για ό,τι δεν έχει ελεγχθεί).
 */
class ReferenceController extends BaseController
{
    private const MAX_PENDING = 3;
    private const MAX_TOTAL = 15;

    private PDO $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance()->getConnection();
    }

    // ══════════════════════════════════════════════════════════════════
    //  ΠΛΕΥΡΑ ΟΔΗΓΟΥ
    // ══════════════════════════════════════════════════════════════════

    /** GET /drivers/references — λίστα προσκλήσεων + φόρμα νέας. */
    public function index(): void
    {
        AuthMiddleware::hasRole('driver');
        $driverId = (int) Session::get('user_id');

        $stmt = $this->db->prepare(
            'SELECT id, reviewer_name, reviewer_company, reviewer_email,
                    employment_from, employment_to, invite_token, invited_at,
                    rating, would_rehire, created_at, updated_at
             FROM driver_reviews
             WHERE driver_id = ? AND invite_token IS NOT NULL
             ORDER BY invited_at DESC'
        );
        $stmt->execute([$driverId]);
        $invites = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'Συστάσεις Εργοδοτών';
        include ROOT_DIR . '/src/Views/partials/header.php';
        include ROOT_DIR . '/src/Views/drivers/references.php';
        include ROOT_DIR . '/src/Views/partials/footer.php';
    }

    /** POST /drivers/references — νέα πρόσκληση. Επιστρέφει JSON με τον σύνδεσμο. */
    public function invite(): void
    {
        AuthMiddleware::hasRole('driver');
        $driverId = (int) Session::get('user_id');

        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            JsonHelper::error('Η φόρμα έληξε. Ανανεώστε τη σελίδα και δοκιμάστε ξανά.');
            return;
        }

        $name = trim((string) ($_POST['reviewer_name'] ?? ''));
        $company = trim((string) ($_POST['reviewer_company'] ?? ''));
        /*
         * Η ΣΧΕΣΗ (01/09): ο αυτοαπασχολούμενος δεν έχει εργοδότη —
         * έχει ΠΕΛΑΤΕΣ. Η μεταφορική που του έδινε δρομολόγια πέντε
         * χρόνια ξέρει για τη δουλειά του ό,τι θα ήξερε εργοδότης.
         * Λευκή λίστα: ό,τι άλλο έρθει γίνεται employer.
         */
        $relation = (string) ($_POST['reviewer_relation'] ?? 'employer');
        if (!in_array($relation, ['employer', 'client', 'supervisor'], true)) {
            $relation = 'employer';
        }
        $email = trim((string) ($_POST['reviewer_email'] ?? ''));
        $from = trim((string) ($_POST['employment_from'] ?? ''));
        $to = trim((string) ($_POST['employment_to'] ?? ''));

        if ($name === '' || $company === '') {
            JsonHelper::error('Συμπλήρωσε όνομα και εταιρεία του εργοδότη — η σύσταση είναι επώνυμη.');
            return;
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            JsonHelper::error('Το email δεν είναι έγκυρο. Αν δεν το ξέρεις, άφησέ το κενό και στείλε τον σύνδεσμο ο ίδιος.');
            return;
        }
        // Μελλοντική περίοδος απασχόλησης = σίγουρο λάθος πληκτρολόγησης.
        $fromDate = $this->monthToDate($from);
        $toDate = $this->monthToDate($to);
        if (($from !== '' && $fromDate === null) || ($to !== '' && $toDate === null)) {
            JsonHelper::error('Η περίοδος απασχόλησης δεν είναι έγκυρη.');
            return;
        }

        // ── Όρια ────────────────────────────────────────────────────────
        $counts = $this->db->prepare(
            'SELECT COUNT(*) AS total,
                    SUM(CASE WHEN rating IS NULL THEN 1 ELSE 0 END) AS pending
             FROM driver_reviews
             WHERE driver_id = ? AND invite_token IS NOT NULL'
        );
        $counts->execute([$driverId]);
        $row = $counts->fetch(PDO::FETCH_ASSOC);

        if ((int) $row['pending'] >= self::MAX_PENDING) {
            JsonHelper::error(
                'Έχεις ήδη ' . self::MAX_PENDING . ' προσκλήσεις που περιμένουν απάντηση. '
                . 'Περίμενε να απαντηθούν ή ακύρωσε κάποια.'
            );
            return;
        }
        if ((int) $row['total'] >= self::MAX_TOTAL) {
            JsonHelper::error('Έχεις φτάσει το όριο των ' . self::MAX_TOTAL . ' προσκλήσεων.');
            return;
        }

        $token = bin2hex(random_bytes(24));

        /*
         * Η εκκρεμής πρόσκληση είναι γραμμή με rating NULL. Ο
         * EmployerReviewCollector μετράει μόνο rating > 0, άρα οι
         * εκκρεμείς δεν αγγίζουν ποτέ τη βαθμολογία.
         */
        $ins = $this->db->prepare(
            'INSERT INTO driver_reviews
                (driver_id, rating, reviewer_name, reviewer_company, reviewer_email,
                 reviewer_relation, employment_from, employment_to, invite_token, invited_at, created_at)
             VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );
        $ins->execute([
            $driverId, $name, $company,
            $email ?: null, $relation, $fromDate, $toDate, $token,
        ]);
        // Αμέσως μετά το INSERT — πριν τρέξει οποιοδήποτε άλλο ερώτημα.
        $inviteId = (int) $this->db->lastInsertId();

        $link = BASE_URL . 'reference/' . $token;
        $emailSent = false;

        if ($email !== '') {
            $emailSent = $this->sendInviteEmail($email, $name, $driverId, $link);
        }

        JsonHelper::success(
            $emailSent
                ? 'Η πρόσκληση στάλθηκε στο ' . $email . '. Μπορείς να στείλεις και τον σύνδεσμο ο ίδιος.'
                : 'Η πρόσκληση δημιουργήθηκε — αντίγραψε τον σύνδεσμο και στείλ\' τον στον εργοδότη.',
            [
                'id' => $inviteId,
                'link' => $link,
                'email_sent' => $emailSent,
            ]
        );
    }

    /** POST /drivers/references/delete/{id} — ακύρωση ΜΟΝΟ εκκρεμούς. */
    public function cancel(int $id): void
    {
        AuthMiddleware::hasRole('driver');
        $driverId = (int) Session::get('user_id');

        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            JsonHelper::error('Η φόρμα έληξε. Ανανεώστε τη σελίδα και δοκιμάστε ξανά.');
            return;
        }

        /*
         * rating IS NULL: απαντημένη σύσταση ΔΕΝ διαγράφεται από τον
         * οδηγό. Αλλιώς θα σβήνε όποια δεν του άρεσε και θα κρατούσε
         * τις καλές — και η «φήμη» θα ήταν απλώς επιμελημένη βιτρίνα.
         */
        $del = $this->db->prepare(
            'DELETE FROM driver_reviews
             WHERE id = ? AND driver_id = ? AND rating IS NULL AND invite_token IS NOT NULL'
        );
        $del->execute([$id, $driverId]);

        if ($del->rowCount() > 0) {
            JsonHelper::success('Η πρόσκληση ακυρώθηκε.');
        } else {
            JsonHelper::error('Η πρόσκληση δεν βρέθηκε — ή έχει ήδη απαντηθεί και δεν ακυρώνεται.');
        }
    }

    // ══════════════════════════════════════════════════════════════════
    //  ΠΛΕΥΡΑ ΕΡΓΟΔΟΤΗ — ΔΗΜΟΣΙΑ, ΧΩΡΙΣ ΛΟΓΑΡΙΑΣΜΟ
    // ══════════════════════════════════════════════════════════════════

    /** GET /reference/{token} — η φόρμα αξιολόγησης. */
    public function show(string $token): void
    {
        $invite = $this->findInvite($token);

        if (!$invite) {
            http_response_code(404);
            $refError = 'Ο σύνδεσμος δεν ισχύει. Ίσως η πρόσκληση ακυρώθηκε — ζητήστε νέο σύνδεσμο από τον οδηγό.';
            $pageTitle = 'Σύσταση Οδηγού';
            include ROOT_DIR . '/src/Views/partials/header.php';
            include ROOT_DIR . '/src/Views/reference-form.php';
            include ROOT_DIR . '/src/Views/partials/footer.php';
            return;
        }

        $alreadyDone = $invite['rating'] !== null;
        $pageTitle = 'Σύσταση για ' . $invite['first_name'] . ' ' . $invite['last_name'];

        include ROOT_DIR . '/src/Views/partials/header.php';
        include ROOT_DIR . '/src/Views/reference-form.php';
        include ROOT_DIR . '/src/Views/partials/footer.php';
    }

    /** POST /reference/{token} — υποβολή. */
    public function submit(string $token): void
    {
        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            JsonHelper::error('Η φόρμα έληξε. Ανανεώστε τη σελίδα και δοκιμάστε ξανά.');
            return;
        }

        $invite = $this->findInvite($token);
        if (!$invite) {
            JsonHelper::error('Ο σύνδεσμος δεν ισχύει.');
            return;
        }
        if ($invite['rating'] !== null) {
            // Ένα token, μία υποβολή — αλλιώς ο ίδιος σύνδεσμος θα
            // ξαναγραφόταν όσες φορές ήθελε όποιος τον είχε.
            JsonHelper::error('Η σύσταση έχει ήδη υποβληθεί. Ευχαριστούμε!');
            return;
        }

        $rating = (int) ($_POST['rating'] ?? 0);
        if ($rating < 1 || $rating > 5) {
            JsonHelper::error('Επιλέξτε συνολική βαθμολογία από 1 έως 5.');
            return;
        }

        $rehireRaw = $_POST['would_rehire'] ?? '';
        if (!in_array($rehireRaw, ['1', '0'], true)) {
            JsonHelper::error('Απαντήστε αν θα τον ξαναπροσλαμβάνατε — είναι η πιο χρήσιμη ερώτηση.');
            return;
        }

        // Επιμέρους: προαιρετικές, 1-5 ή τίποτα.
        $facets = [];
        foreach (['professionalism_rating', 'driving_skills_rating', 'reliability_rating',
                  'communication_rating', 'technical_skills_rating'] as $f) {
            $v = (int) ($_POST[$f] ?? 0);
            $facets[$f] = ($v >= 1 && $v <= 5) ? $v : null;
        }

        $comment = mb_substr(trim((string) ($_POST['comment'] ?? '')), 0, 2000);

        /*
         * verified_at = τώρα: η υποβολή μέσω του token ΕΙΝΑΙ η
         * επαλήθευση που διαθέτουμε σήμερα — ο σύνδεσμος έφτασε στο
         * πρόσωπο που όρισε ο οδηγός. Ισχυρότερη επαλήθευση (τηλέφωνο
         * εταιρείας, ΓΕΜΗ) μπορεί να προστεθεί χωρίς αλλαγή σχήματος.
         */
        $upd = $this->db->prepare(
            'UPDATE driver_reviews
             SET rating = ?, would_rehire = ?, comment = ?,
                 professionalism_rating = ?, driving_skills_rating = ?,
                 reliability_rating = ?, communication_rating = ?,
                 technical_skills_rating = ?,
                 employment_from = COALESCE(?, employment_from),
                 employment_to = COALESCE(?, employment_to),
                 verified_at = NOW(), updated_at = NOW()
             WHERE id = ? AND rating IS NULL'
        );
        $upd->execute([
            $rating, (int) $rehireRaw, $comment ?: null,
            $facets['professionalism_rating'], $facets['driving_skills_rating'],
            $facets['reliability_rating'], $facets['communication_rating'],
            $facets['technical_skills_rating'],
            $this->monthToDate((string) ($_POST['employment_from'] ?? '')),
            $this->monthToDate((string) ($_POST['employment_to'] ?? '')),
            (int) $invite['id'],
        ]);

        if ($upd->rowCount() === 0) {
            JsonHelper::error('Η σύσταση έχει ήδη υποβληθεί.');
            return;
        }

        // Η βαθμολογία ξαναϋπολογίζεται ΤΩΡΑ — αυτή η υποβολή είναι
        // πιθανότατα η πρώτη μαρτυρία τρίτου, δηλαδή η στιγμή που ο
        // οδηγός αποκτά συνολικό αριθμό.
        try {
            (new DriverProfileService($this->db))->updateDriverRating((int) $invite['driver_id']);
        } catch (\Throwable $e) {
            Logger::error('Επανυπολογισμός βαθμολογίας μετά από σύσταση απέτυχε: ' . $e->getMessage());
        }

        JsonHelper::success('Η σύσταση καταχωρήθηκε. Ευχαριστούμε για τον χρόνο σας!');
    }

    // ══════════════════════════════════════════════════════════════════
    //  ΒΟΗΘΗΤΙΚΑ
    // ══════════════════════════════════════════════════════════════════

    /**
     * «2019-03» (input type=month) → «2019-03-01» για στήλη DATE.
     * Χωρίς αυτό η MariaDB απορρίπτει την τιμή με 22007 — πιάστηκε
     * στο e2e τεστ (01/09). Άκυρο ή μελλοντικό → null.
     */
    private function monthToDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}$/', $value)) {
            $value .= '-01';
        }
        $ts = strtotime($value);
        return ($ts !== false && $ts <= time()) ? date('Y-m-d', $ts) : null;
    }

    private function findInvite(string $token): ?array
    {
        // Το token είναι δικό μας hex — ό,τι άλλο κόβεται πριν τη βάση.
        if (!preg_match('/^[a-f0-9]{48}$/', $token)) {
            return null;
        }

        $stmt = $this->db->prepare(
            'SELECT r.*, d.first_name, d.last_name
             FROM driver_reviews r
             JOIN drivers d ON d.id = r.driver_id
             WHERE r.invite_token = ?'
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** Το email είναι ευκολία: η αποτυχία του ΔΕΝ αποτυγχάνει την πρόσκληση. */
    private function sendInviteEmail(string $to, string $name, int $driverId, string $link): bool
    {
        if (!defined('SMTP_HOST') || SMTP_HOST === '') {
            return false;
        }

        try {
            $stmt = $this->db->prepare('SELECT first_name, last_name FROM drivers WHERE id = ?');
            $stmt->execute([$driverId]);
            $d = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['first_name' => 'Οδηγός', 'last_name' => ''];
            $driverName = trim($d['first_name'] . ' ' . $d['last_name']);

            $svc = new EmailService(
                SMTP_HOST,
                SMTP_PORT,
                SMTP_USERNAME,
                SMTP_PASSWORD,
                SMTP_FROM_EMAIL,
                SMTP_FROM_NAME,
                defined('EMAIL_DEBUG') ? EMAIL_DEBUG : false
            );

            $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            $safeDriver = htmlspecialchars($driverName, ENT_QUOTES, 'UTF-8');
            $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');

            $body = '<p>Αγαπητέ/ή ' . $safeName . ',</p>'
                . '<p>Ο/η <strong>' . $safeDriver . '</strong> εργάστηκε στην επιχείρησή σας και σας ζητά μια σύντομη '
                . 'επαγγελματική σύσταση μέσω της πλατφόρμας DriveJob.</p>'
                . '<p>Η φόρμα έχει 3 υποχρεωτικά πεδία και δεν απαιτεί λογαριασμό — 2 λεπτά:</p>'
                . '<p><a href="' . $safeLink . '">' . $safeLink . '</a></p>'
                . '<p>Ευχαριστούμε,<br>DriveJob</p>';

            return (bool) $svc->send($to, 'Αίτημα σύστασης για τον οδηγό ' . $driverName, $body);
        } catch (\Throwable $e) {
            Logger::error('Αποστολή email πρόσκλησης απέτυχε: ' . $e->getMessage());
            return false;
        }
    }
}
