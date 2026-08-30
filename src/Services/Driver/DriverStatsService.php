<?php

namespace Drivejob\Services\Driver;

use PDO;

/**
 * Στατιστικά προφίλ οδηγού (30/08/2026).
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΙ ΔΙΟΡΘΩΝΕΙ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Η κεφαλίδα του προφίλ έδειχνε τρία νούμερα:
 *
 *     Προβολές Προφίλ 0 · Αιτήσεις για Θέσεις 0 · Ταιριάσματα Εργασίας 0
 *
 * Και τα τρία ήταν ΠΑΝΤΑ μηδέν: το view διάβαζε `$driverStats[...]`, μια
 * μεταβλητή που δεν οριζόταν πουθενά στον κώδικα (grep σε ολόκληρο το
 * src/ εκτός Views: κανένα αποτέλεσμα). Ένας δείκτης που δείχνει πάντα
 * μηδέν είναι χειρότερος από κανέναν — ο οδηγός συμπεραίνει είτε ότι
 * κανείς δεν τον κοιτάζει είτε ότι η πλατφόρμα δεν δουλεύει.
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΙ ΔΕΙΧΝΟΥΜΕ ΤΩΡΑ ΚΑΙ ΓΙΑΤΙ
 * ══════════════════════════════════════════════════════════════════════
 *
 *  1. ΠΛΗΡΟΤΗΤΑ ΠΡΟΦΙΛ — αντικαθιστά τις «Προβολές Προφίλ».
 *     Οι προβολές θέλουν tracking που ΔΕΝ υπάρχει (κανένας πίνακας
 *     προβολών/επισκέψεων στη βάση). Αντί για ψεύτικο μηδέν, δείχνουμε
 *     κάτι που ξέρουμε με βεβαιότητα και είναι πιο χρήσιμο: πόσο
 *     συμπληρωμένο είναι το προφίλ και τι λείπει. Είναι ταυτόχρονα και
 *     ο δείκτης ετοιμότητας του ΑΥΤΟΜΑΤΟΥ ΒΙΟΓΡΑΦΙΚΟΥ.
 *
 *  2. ΑΙΤΗΣΕΙΣ — COUNT από job_applications. Πραγματικό νούμερο.
 *
 *  3. ΠΡΟΣΦΟΡΕΣ — COUNT από job_offers (προσφορές που ΕΛΑΒΕ ο οδηγός).
 *     Αντικαθιστά τα «Ταιριάσματα Εργασίας», που υπολογίζονται ζωντανά
 *     στην καρτέλα Ταιριάσματα και δεν αποθηκεύονται — ένας αριθμός
 *     ταιριασμάτων στην κεφαλίδα θα ήταν φωτογραφία μιας στιγμής.
 *
 * Όταν προστεθεί tracking προβολών, μπαίνει εδώ και μόνο εδώ.
 */
class DriverStatsService
{
    private PDO $pdo;
    private ProfileCompletenessService $completeness;

    public function __construct(PDO $pdo, ?ProfileCompletenessService $completeness = null)
    {
        $this->pdo = $pdo;
        $this->completeness = $completeness ?? new ProfileCompletenessService();
    }

    /**
     * @param int   $driverId
     * @param array $profile Το πλήρες προφίλ (για την πληρότητα)
     * @return array{completeness:array, applications:int, offers:int, pending_offers:int}
     */
    public function forDriver(int $driverId, array $profile): array
    {
        return [
            'completeness' => $this->completeness->calculate($profile),
            'applications' => $this->countRows('job_applications', $driverId),
            'offers' => $this->countRows('job_offers', $driverId),
            'pending_offers' => $this->countRows('job_offers', $driverId, "status = 'pending'"),
        ];
    }

    /**
     * Τα ονόματα πινάκων είναι σταθερές ΤΟΥ ΚΩΔΙΚΑ, ποτέ είσοδος χρήστη —
     * το driver_id πάει πάντα με placeholder.
     *
     * Ο πίνακας μπορεί να λείπει σε παλιά βάση: επιστρέφουμε 0 αντί να
     * ρίξουμε ολόκληρη τη σελίδα προφίλ για ένα νούμερο στην κεφαλίδα.
     */
    private function countRows(string $table, int $driverId, string $extraWhere = ''): int
    {
        $sql = "SELECT COUNT(*) FROM {$table} WHERE driver_id = :id";
        if ($extraWhere !== '') {
            $sql .= ' AND ' . $extraWhere;
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $driverId]);
            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
