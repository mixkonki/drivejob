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

    // ─────────────────────────────────────────────── Τοποθεσία

    /**
     * Πόσο ακριβής επιτρέπεται να είναι η τοποθεσία για αυτόν τον θεατή;
     *
     * ΑΠΟΦΑΣΗ (Κώστας, 23/08): μέχρι και την υποβολή αίτησης, ο οδηγός βλέπει
     * ΝΟΜΟ ή ΠΟΛΗ. Ακριβής διεύθυνση μόνο όταν η εταιρεία τον έχει βάλει σε
     * προεπιλογή ή τον προσέλαβε.
     *
     * ΓΙΑΤΙ ΕΧΕΙ ΣΗΜΑΣΙΑ: η διεύθυνση δεν είναι απλώς «πού πάω για δουλειά».
     * Στον κλάδο των μεταφορών, «Θέρμη, 6ο χλμ. Θεσσαλονίκης–Μουδανιών»
     * ταυτοποιεί την εταιρεία σε δέκα δευτερόλεπτα με μια αναζήτηση χάρτη.
     * Κρύβοντας την επωνυμία αλλά δείχνοντας τη διεύθυνση, δεν κρύβεις
     * τίποτα — απλώς προσθέτεις ένα βήμα.
     *
     * @param array $source εγγραφή εταιρείας ή αγγελίας
     */
    public function locationFor(?string $viewerRole, $viewerId, ?int $companyId, array $source): string
    {
        $precise = $companyId !== null
            && $this->canViewCompanyContact($viewerRole, $viewerId, $companyId);

        if ($precise) {
            $full = trim((string) ($source['address'] ?? ''));
            if ($full !== '') {
                $city = trim((string) ($source['city'] ?? ''));
                // Πολλές διευθύνσεις ξεκινούν ήδη με την πόλη — μη την πούμε δύο φορές.
                return ($city !== '' && !str_contains($full, $city))
                    ? $full . ', ' . $city
                    : $full;
            }
        }

        return self::publicLocation($source);
    }

    /**
     * Η τοποθεσία σε επίπεδο πόλης/νομού, χωρίς οδό και αριθμό.
     *
     * Δέχεται ό,τι σχήμα έχει η εγγραφή: οι εταιρείες κρατούν `city`, οι
     * αγγελίες ένα ελεύθερο `location` της μορφής «Θέρμη, Ελλάδα». Δεν
     * εμπιστευόμαστε κανένα από τα δύο τυφλά.
     */
    public static function publicLocation(array $source): string
    {
        $city = trim((string) ($source['city'] ?? ''));
        if ($city !== '') {
            return $city;
        }

        $location = trim((string) ($source['location'] ?? ''));
        if ($location === '') {
            return 'Δεν καθορίστηκε';
        }

        /*
         * Το `location` είναι ελεύθερο κείμενο από φόρμα με αυτόματη
         * συμπλήρωση Google Places. Έρχεται σε δύο σχήματα:
         *
         *     «Θέρμη, Ελλάδα»                              ← πόλη πρώτα
         *     «Λεωφ. Γεωργικής Σχολής 45, Θέρμη, Greece»   ← οδός πρώτα
         *
         * Το να κρατάμε πάντα το πρώτο τμήμα δουλεύει στο πρώτο σχήμα και
         * ΑΠΟΤΥΓΧΑΝΕΙ στο δεύτερο — θα δημοσιεύαμε ακριβώς τη διεύθυνση που
         * υποτίθεται ότι κρύβουμε. Γι' αυτό αναγνωρίζουμε τα τμήματα που
         * μοιάζουν με οδό και τα πετάμε.
         */
        $parts = array_map('trim', explode(',', $location));
        $parts = array_values(array_filter($parts, static function ($p) {
            $lower = mb_strtolower($p);
            return $p !== ''
                && $lower !== 'ελλάδα' && $lower !== 'greece' && $lower !== 'ελλας'
                && !preg_match('/^\d{3}\s?\d{2}$/', $p); // ταχυδρομικός κώδικας
        }));

        if ($parts === []) {
            return 'Δεν καθορίστηκε';
        }

        // Ένα τμήμα είναι διεύθυνση, όχι πόλη, όταν φέρει αριθμό οδού ή
        // τυπική συντομογραφία δρόμου.
        $looksLikeStreet = static function (string $p): bool {
            return (bool) preg_match('/\d/', $p)
                || (bool) preg_match('/\b(οδ|οδός|λεωφ|λεωφόρος|χλμ|αρ|πλ|πλατεία|str|ave|road)\b\.?/ui', $p);
        };

        foreach ($parts as $part) {
            if (!$looksLikeStreet($part)) {
                return $part;
            }
        }

        // Όλα τα τμήματα έμοιαζαν με διεύθυνση: το τελευταίο είναι η πιο
        // γενική ένδειξη που έχουμε — προτιμότερο από το να δείξουμε οδό.
        return end($parts) ?: 'Δεν καθορίστηκε';
    }

    // ─────────────────────────────────────────────── Καθαρισμός αγγελιών

    /**
     * Πεδία αγγελίας που δεν επιτρέπεται να φύγουν από τον server σε κανέναν
     * που δεν έχει ξεκλειδώσει την επικοινωνία.
     */
    private const LISTING_SECRETS = [
        'contact_email', 'contact_phone', 'latitude', 'longitude', 'address',
    ];

    /**
     * Καθαρίζει μία αγγελία πριν φτάσει σε view ή σε JSON.
     *
     * ══════════════════════════════════════════════════════════════════
     *  Η ΔΙΑΡΡΟΗ ΠΟΥ ΕΚΑΝΕ ΑΥΤΗ ΤΗ ΜΕΘΟΔΟ ΑΠΑΡΑΙΤΗΤΗ (μετρήθηκε 23/08)
     * ══════════════════════════════════════════════════════════════════
     *
     * Το ερώτημα αναζήτησης είναι `SELECT j.*, c.company_name` — και το
     * `j.*` περιλαμβάνει τα `contact_email` και `contact_phone` της
     * αγγελίας. Ο controller επέστρεφε το αποτέλεσμα αυτούσιο σε JSON για
     * κάθε αίτημα AJAX. Μία εντολή, χωρίς λογαριασμό, χωρίς cookie:
     *
     *     curl -H "X-Requested-With: XMLHttpRequest" https://drivejob.gr/job-listings
     *     → "contact_email":"…@…", "contact_phone":"2310555101", …
     *
     * Ολόκληρος ο κατάλογος τηλεφώνων, δημόσια.
     *
     * ΤΟ ΜΑΘΗΜΑ: μια κάρτα που κρύβει το τηλέφωνο δεν προστατεύει τίποτα
     * όσο το ίδιο τηλέφωνο ταξιδεύει σε JSON δίπλα της. Ο καθαρισμός
     * πρέπει να γίνεται στα ΔΕΔΟΜΕΝΑ, όχι στην εμφάνιση — γι' αυτό ζει
     * εδώ και όχι στο view.
     */
    public function sanitiseListing(?string $viewerRole, $viewerId, array $listing): array
    {
        $companyId = isset($listing['company_id']) ? (int) $listing['company_id'] : null;

        $mayContact = $companyId !== null
            && $this->canViewCompanyContact($viewerRole, $viewerId, $companyId);

        // Η τοποθεσία υπολογίζεται ΠΡΙΝ σβηστούν τα πεδία που τη συνθέτουν.
        $listing['location'] = $this->locationFor($viewerRole, $viewerId, $companyId, $listing);

        if (!$mayContact) {
            foreach (self::LISTING_SECRETS as $field) {
                if (array_key_exists($field, $listing)) {
                    unset($listing[$field]);
                }
            }

            // Ένδειξη ότι ΥΠΑΡΧΕΙ τρόπος επικοινωνίας, χωρίς να δοθεί:
            // αλλιώς η κάρτα μοιάζει ελλιπής και ο οδηγός νομίζει ότι
            // η αγγελία είναι εγκαταλελειμμένη.
            $listing['contact_locked'] = true;
        }

        if (!$this->canRevealCompanyIdentity($viewerRole, $viewerId)) {
            $listing['company_name'] = $this->companyNameFor($viewerRole, $viewerId, $listing);
            $listing['company_identity_hidden'] = true;
        }

        return $listing;
    }

    /**
     * Το ίδιο για ολόκληρη λίστα. Χρησιμοποίησέ το ΠΑΝΤΑ πριν από
     * `JsonHelper::response()` και πριν από κάθε βρόχο εμφάνισης.
     */
    public function sanitiseListings(?string $viewerRole, $viewerId, array $listings): array
    {
        foreach ($listings as $i => $listing) {
            if (is_array($listing)) {
                $listings[$i] = $this->sanitiseListing($viewerRole, $viewerId, $listing);
            }
        }

        return $listings;
    }

    // ─────────────────────────────────────────────── Υποψήφιοι ταιριάσματος

    /**
     * Καθαρίζει έναν υποψήφιο οδηγό πριν σταλεί σε εταιρεία.
     *
     * ══════════════════════════════════════════════════════════════════
     *  ΤΙ ΕΔΙΝΕ ΤΟ /api/matching/job/candidates
     * ══════════════════════════════════════════════════════════════════
     *
     * Το AI matching επέστρεφε για κάθε προτεινόμενο οδηγό:
     *
     *     'name'  => 'Όνομα Επώνυμο',
     *     'email' => 'odigos@example.gr',
     *
     * — για είκοσι οδηγούς που ΔΕΝ είχαν κάνει καμία αίτηση στην εταιρεία.
     * Δηλαδή μια έτοιμη λίστα επικοινωνιών, παραδομένη αυτόματα.
     *
     * Αυτό αναιρεί ολόκληρο τον πίνακα ορατότητας: εκεί η εταιρεία βλέπει
     * πλήρες προφίλ οδηγού ΜΟΝΟ αφού εκείνος υποβάλει αίτηση. Ο οδηγός
     * δίνει τη συναίνεσή του κάνοντας αίτηση — δεν του τη ζητά κανείς όταν
     * ένας αλγόριθμος τον προτείνει.
     *
     * ΤΙ ΜΕΝΕΙ: όλη η χρήσιμη πληροφορία για την απόφαση — σκορ
     * ταιριάσματος, ανάλυση ανά κριτήριο, πόλη, χρόνια εμπειρίας,
     * αξιολόγηση. Η εταιρεία μπορεί να κρίνει ποιον θέλει.
     *
     * ΤΙ ΦΕΥΓΕΙ: ονοματεπώνυμο και email. Στη θέση τους μπαίνει ένα
     * ψευδώνυμο («Οδηγός #84») που επιτρέπει να ξεχωρίζει τους υποψηφίους
     * χωρίς να τους ταυτοποιεί.
     */
    public function sanitiseCandidate(int $companyId, array $candidate): array
    {
        $driverId = (int) ($candidate['driver_id'] ?? 0);

        if ($driverId > 0 && $this->companyHasApplicationFrom($companyId, $driverId)) {
            return $candidate; // έχει κάνει αίτηση — τα δίνει ο ίδιος
        }

        $candidate['name'] = 'Οδηγός #' . ($driverId ?: '—');
        $candidate['identity_hidden'] = true;
        $candidate['identity_hint'] = 'Το ονοματεπώνυμο και τα στοιχεία επικοινωνίας '
            . 'εμφανίζονται μόλις ο οδηγός υποβάλει αίτηση στην αγγελία σου.';

        unset($candidate['email'], $candidate['phone'], $candidate['first_name'], $candidate['last_name']);

        return $candidate;
    }

    /**
     * Το ίδιο για ολόκληρη λίστα υποψηφίων.
     */
    public function sanitiseCandidates(int $companyId, array $candidates): array
    {
        foreach ($candidates as $i => $candidate) {
            if (is_array($candidate)) {
                $candidates[$i] = $this->sanitiseCandidate($companyId, $candidate);
            }
        }

        return $candidates;
    }

    // ─────────────────────────────────────────────── Ταυτότητα εταιρείας

    /**
     * Το όνομα που επιτρέπεται να δει ο θεατής για την εταιρεία της αγγελίας.
     *
     * ΑΠΟΦΑΣΗ (Κώστας, 23/08): ο ΑΝΩΝΥΜΟΣ επισκέπτης βλέπει πλήρη αγγελία
     * αλλά ΟΧΙ την επωνυμία. Ο συνδεδεμένος οδηγός τη βλέπει.
     *
     * Το ζητούμενο δεν είναι μυστικότητα — είναι ότι η συνάντηση οδηγού και
     * εταιρείας γίνεται μέσα στην πλατφόρμα, όπως στο Booking. Αρκετά για να
     * τραβήξει τον οδηγό, όχι αρκετά για να τηλεφωνήσει απευθείας.
     */
    public function companyNameFor(?string $viewerRole, $viewerId, array $company): string
    {
        $name = trim((string) ($company['company_name'] ?? $company['name'] ?? ''));

        if ($viewerRole !== null && $viewerId !== null) {
            return $name !== '' ? $name : 'Εταιρεία';
        }

        return 'Εταιρεία μεταφορών';
    }

    /**
     * Επιτρέπεται να δείξουμε την επωνυμία και τον σύνδεσμο στο προφίλ;
     */
    public function canRevealCompanyIdentity(?string $viewerRole, $viewerId): bool
    {
        return $viewerRole !== null && $viewerId !== null;
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
