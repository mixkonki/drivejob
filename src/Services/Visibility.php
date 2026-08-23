<?php

namespace Drivejob\Services;

use PDO;

/**
 * Ποιος επιτρέπεται να δει τι.
 *
 * ΓΙΑΤΙ ΥΠΑΡΧΕΙ: μέχρι τώρα δεν υπήρχε κανένας κανόνας. Το προφίλ κάθε οδηγού
 * ήταν προσβάσιμο σε οποιονδήποτε στο διαδίκτυο, χωρίς σύνδεση, με το email
 * και το τηλέφωνό του ως clickable mailto: και tel:. Το ίδιο ίσχυε για τις
 * εταιρείες, μαζί με ακριβή διεύθυνση και ενσωματωμένο χάρτη. Πρακτικά κανείς
 * δεν χρειαζόταν να κάνει αίτηση για να επικοινωνήσει, και ένα bot μπορούσε να
 * συλλέξει όλα τα τηλέφωνα του καταλόγου.
 *
 * Ο ΚΑΝΟΝΑΣ, σε μία πρόταση: τα στοιχεία επικοινωνίας αποκαλύπτονται μόνο όταν
 * και οι δύο πλευρές έχουν δείξει ενδιαφέρον — ο οδηγός κάνοντας αίτηση, η
 * εταιρεία βάζοντας την αίτηση σε προεπιλογή ή πρόσληψη.
 *
 * Κάθε έλεγχος ζει ΕΔΩ. Τα views δεν αποφασίζουν μόνα τους.
 */
final class Visibility
{
    /**
     * Καταστάσεις αίτησης που σημαίνουν «η εταιρεία ενδιαφέρεται πραγματικά».
     *
     * Μόνο σε αυτές ξεκλειδώνουν τα στοιχεία της εταιρείας για τον οδηγό.
     * Το «pending» δεν αρκεί: αλλιώς κάποιος θα έκανε είκοσι αιτήσεις για να
     * συλλέξει είκοσι τηλέφωνα.
     */
    private const ENGAGED_STATUSES = ['shortlisted', 'hired'];

    private PDO $pdo;

    /** @var array<string, bool> μνήμη εντός αιτήματος — τα ίδια ερωτήματα επαναλαμβάνονται σε κάθε view */
    private array $cache = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ─────────────────────────────────────────────── Ο θεατής

    /**
     * @param string|null $viewerRole 'driver' | 'company' | 'admin' | null (επισκέπτης)
     */
    private function isAdmin(?string $viewerRole): bool
    {
        return $viewerRole === 'admin' || $viewerRole === 'super_admin';
    }

    // ─────────────────────────────────────────────── Προφίλ οδηγού

    /**
     * Μπορεί ο θεατής να δει το προφίλ αυτού του οδηγού;
     *
     * Τα προφίλ οδηγών ΔΕΝ είναι δημόσια. Πρόσβαση έχουν μόνο:
     *   - ο ίδιος ο οδηγός
     *   - εταιρεία που έχει λάβει αίτηση από αυτόν
     *   - διαχειριστής
     */
    public function canViewDriverProfile(?string $viewerRole, $viewerId, int $driverId): bool
    {
        if ($this->isAdmin($viewerRole)) {
            return true;
        }

        if ($viewerRole === 'driver' && (int) $viewerId === $driverId) {
            return true;
        }

        if ($viewerRole === 'company') {
            return $this->companyHasApplicationFrom((int) $viewerId, $driverId);
        }

        return false;
    }

    /**
     * Μπορεί να δει email, τηλέφωνο και διεύθυνση του οδηγού;
     *
     * Ταυτίζεται με την πρόσβαση στο προφίλ: αν μια εταιρεία έλαβε αίτηση, ο
     * οδηγός έδωσε ο ίδιος τη συναίνεσή του να επικοινωνήσουν μαζί του.
     */
    public function canViewDriverContact(?string $viewerRole, $viewerId, int $driverId): bool
    {
        return $this->canViewDriverProfile($viewerRole, $viewerId, $driverId);
    }

    // ─────────────────────────────────────────────── Προφίλ εταιρείας

    /**
     * Μπορεί ο θεατής να δει το προφίλ της εταιρείας;
     *
     * Το προφίλ (περιγραφή, στόλος, αξιολογήσεις, αγγελίες) ανοίγει σε κάθε
     * συνδεδεμένο χρήστη — είναι εμπορική πληροφορία, όχι προσωπικό δεδομένο.
     * Ο επισκέπτης χωρίς λογαριασμό βλέπει μόνο όνομα και πόλη μέσα από την
     * αγγελία.
     */
    public function canViewCompanyProfile(?string $viewerRole, $viewerId, int $companyId): bool
    {
        if ($this->isAdmin($viewerRole)) {
            return true;
        }

        if ($viewerRole === 'company' && (int) $viewerId === $companyId) {
            return true;
        }

        return $viewerRole !== null && $viewerId !== null;
    }

    /**
     * Μπορεί να δει email, τηλέφωνο, ακριβή διεύθυνση και χάρτη της εταιρείας;
     *
     * Μόνο αν η εταιρεία έχει δείξει ενδιαφέρον για μια αίτησή του — δηλαδή
     * την έβαλε σε προεπιλογή ή τον προσέλαβε.
     */
    public function canViewCompanyContact(?string $viewerRole, $viewerId, int $companyId): bool
    {
        if ($this->isAdmin($viewerRole)) {
            return true;
        }

        if ($viewerRole === 'company' && (int) $viewerId === $companyId) {
            return true;
        }

        if ($viewerRole === 'driver') {
            return $this->driverIsEngagedWith((int) $viewerId, $companyId);
        }

        return false;
    }

    // ─────────────────────────────────────────────── Ερωτήματα βάσης

    /**
     * Έχει η εταιρεία λάβει αίτηση από αυτόν τον οδηγό;
     *
     * Οι αποσυρμένες αιτήσεις δεν μετρούν: αν ο οδηγός απέσυρε το ενδιαφέρον
     * του, ανακάλεσε και τη συναίνεση να τον βλέπουν.
     */
    public function companyHasApplicationFrom(int $companyId, int $driverId): bool
    {
        $key = "app:$companyId:$driverId";
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $stmt = $this->pdo->prepare(
            'SELECT 1
             FROM job_applications ja
             JOIN job_listings jl ON ja.job_listing_id = jl.id
             WHERE jl.company_id = :company
               AND ja.driver_id = :driver
               AND ja.status <> :withdrawn
             LIMIT 1'
        );
        $stmt->execute([
            ':company' => $companyId,
            ':driver' => $driverId,
            ':withdrawn' => 'withdrawn',
        ]);

        return $this->cache[$key] = (bool) $stmt->fetchColumn();
    }

    /**
     * Έχει η εταιρεία δείξει ενδιαφέρον για κάποια αίτηση αυτού του οδηγού;
     */
    public function driverIsEngagedWith(int $driverId, int $companyId): bool
    {
        $key = "eng:$driverId:$companyId";
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $placeholders = implode(',', array_fill(0, count(self::ENGAGED_STATUSES), '?'));

        $stmt = $this->pdo->prepare(
            "SELECT 1
             FROM job_applications ja
             JOIN job_listings jl ON ja.job_listing_id = jl.id
             WHERE ja.driver_id = ?
               AND jl.company_id = ?
               AND ja.status IN ($placeholders)
             LIMIT 1"
        );
        $stmt->execute(array_merge([$driverId, $companyId], self::ENGAGED_STATUSES));

        return $this->cache[$key] = (bool) $stmt->fetchColumn();
    }

    /**
     * Έχει ο οδηγός κάνει αίτηση σε αυτή τη συγκεκριμένη αγγελία;
     *
     * Χρησιμοποιείται για να δείξει η σελίδα αγγελίας «Έχεις ήδη κάνει αίτηση»
     * αντί για το κουμπί υποβολής.
     */
    public function driverHasAppliedTo(int $driverId, int $listingId): bool
    {
        $key = "applied:$driverId:$listingId";
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM job_applications
             WHERE driver_id = :driver AND job_listing_id = :listing LIMIT 1'
        );
        $stmt->execute([':driver' => $driverId, ':listing' => $listingId]);

        return $this->cache[$key] = (bool) $stmt->fetchColumn();
    }

    // ─────────────────────────────────────────────── Βοηθητικά για τα views

    /**
     * Το μήνυμα που εξηγεί γιατί κρύβονται τα στοιχεία και τι πρέπει να γίνει.
     *
     * Ένα κλειδωμένο πεδίο χωρίς εξήγηση μοιάζει με σφάλμα. Ο χρήστης πρέπει
     * να καταλαβαίνει τι θα ξεκλειδώσει την πληροφορία.
     */
    public function companyContactHint(?string $viewerRole, $viewerId, int $companyId): string
    {
        if ($viewerRole === null || $viewerId === null) {
            return 'Συνδέσου για να δεις περισσότερα για την εταιρεία.';
        }

        if ($viewerRole !== 'driver') {
            return 'Τα στοιχεία επικοινωνίας δεν είναι διαθέσιμα.';
        }

        return 'Τα στοιχεία επικοινωνίας εμφανίζονται μόλις η εταιρεία '
             . 'προχωρήσει την αίτησή σου σε προεπιλογή ή πρόσληψη.';
    }

    /**
     * Αποκρύπτει μερικώς ένα email: kostas@example.gr → k••••s@example.gr
     *
     * Χρήσιμο όπου θέλουμε να φανεί ότι υπάρχει στοιχείο επικοινωνίας χωρίς να
     * αποκαλυφθεί.
     */
    public static function maskEmail(?string $email): string
    {
        if (empty($email) || !str_contains($email, '@')) {
            return '•••';
        }

        [$user, $domain] = explode('@', $email, 2);
        $len = mb_strlen($user);

        if ($len <= 2) {
            return str_repeat('•', max($len, 1)) . '@' . $domain;
        }

        return mb_substr($user, 0, 1) . str_repeat('•', min($len - 2, 5)) . mb_substr($user, -1) . '@' . $domain;
    }

    /**
     * Αποκρύπτει μερικώς τηλέφωνο: 6972964602 → 697•••••02
     */
    public static function maskPhone(?string $phone): string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        if ($digits === '' || mb_strlen($digits) < 6) {
            return '•••';
        }

        return mb_substr($digits, 0, 3) . str_repeat('•', mb_strlen($digits) - 5) . mb_substr($digits, -2);
    }
}
