<?php

namespace Drivejob\Services\Driver;

/**
 * Πληρότητα προφίλ οδηγού (30/08/2026).
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Τα «Στατιστικά Προφίλ» έδειχναν 0 Προβολές / 0 Αιτήσεις / 0 Ταιριάσματα
 * επειδή η μεταβλητή $driverStats ΔΕΝ οριζόταν πουθενά — τρία μηδενικά,
 * πάντα. Ένας δείκτης που δείχνει πάντα μηδέν είναι χειρότερος από
 * κανέναν: ο οδηγός συμπεραίνει ότι η πλατφόρμα δεν δουλεύει.
 *
 * Αντί για μετρικά που δεν μετράμε ακόμη (οι προβολές προφίλ θέλουν
 * tracking που δεν υπάρχει), δείχνουμε κάτι που ΞΕΡΟΥΜΕ και είναι
 * χρήσιμο: πόσο συμπληρωμένο είναι το προφίλ και τι λείπει.
 *
 * Ο ίδιος υπολογισμός εξυπηρετεί τρία πράγματα:
 *   1. Κίνητρο στον οδηγό («σου λείπουν 3 πράγματα»)
 *   2. Ποιότητα δεδομένων για το ταίριασμα
 *   3. Ετοιμότητα του ΑΥΤΟΜΑΤΟΥ ΒΙΟΓΡΑΦΙΚΟΥ — τα ίδια πεδία που
 *      τροφοδοτούν το CV είναι αυτά που μετράμε εδώ.
 *
 * ΒΑΡΥΤΗΤΕΣ: ό,τι ζητά η αγορά μετράει περισσότερο. Ένας οδηγός χωρίς
 * κατηγορίες διπλώματος είναι αόρατος· χωρίς φωτογραφία απλώς λιγότερο
 * ελκυστικός.
 */
class ProfileCompletenessService
{
    /**
     * @param array $profile Το πλήρες προφίλ (DriverProfileService::getDriverProfile)
     * @return array{percent:int, done:int, total:int, missing:array<int,array>}
     */
    public function calculate(array $profile): array
    {
        $d = $profile;

        $checks = [
            // ── Βάση: χωρίς αυτά δεν υπάρχει προφίλ ──────────────────
            [
                'key' => 'name',
                'label' => 'Ονοματεπώνυμο',
                'weight' => 5,
                'done' => !empty($d['first_name']) && !empty($d['last_name']),
                'link' => 'drivers/edit-profile',
            ],
            [
                'key' => 'phone',
                'label' => 'Τηλέφωνο επικοινωνίας',
                'weight' => 8,
                'done' => !empty($d['phone']),
                'link' => 'drivers/edit-profile',
            ],
            [
                'key' => 'location',
                'label' => 'Πόλη / περιοχή',
                'weight' => 8,
                'done' => !empty($d['city']),
                'link' => 'drivers/edit-profile',
                'why' => 'Χωρίς περιοχή δεν σας βρίσκουν οι εταιρείες κοντά σας.',
            ],
            [
                'key' => 'photo',
                'label' => 'Φωτογραφία προφίλ',
                'weight' => 5,
                'done' => !empty($d['profile_image']),
                'link' => 'drivers/edit-profile',
            ],

            // ── Επαγγελματικά: εδώ κρίνεται το ταίριασμα ─────────────
            [
                'key' => 'license',
                'label' => 'Κατηγορίες άδειας οδήγησης',
                'weight' => 20,
                'done' => !empty($d['licenses']),
                'link' => 'drivers/edit-profile',
                'why' => 'Είναι το πρώτο που φιλτράρουν οι εταιρείες.',
            ],
            [
                'key' => 'license_expiry',
                'label' => 'Ημερομηνίες λήξης άδειας',
                'weight' => 8,
                'done' => $this->anyWithExpiry($d['licenses'] ?? []),
                'link' => 'drivers/edit-profile',
                'why' => 'Χωρίς λήξεις δεν μπορούμε να σας ειδοποιήσουμε για ανανέωση.',
            ],
            [
                'key' => 'experience',
                'label' => 'Προϋπηρεσία σε οχήματα',
                'weight' => 15,
                'done' => !empty($d['vehicle_experience']),
                'link' => 'drivers/vehicle-experience',
                'why' => 'Καθορίζει τη βαθμολογία και το βιογραφικό σας.',
            ],
            [
                'key' => 'certifications',
                'label' => 'Σεμινάρια & πιστοποιητικά',
                'weight' => 10,
                'done' => !empty($d['certifications']),
                'link' => 'drivers/edit-profile',
            ],
            [
                'key' => 'languages',
                'label' => 'Γλώσσες',
                'weight' => 6,
                'done' => !empty($d['languages_list']),
                'link' => 'drivers/edit-profile',
                'why' => 'Απαραίτητο για διεθνείς μεταφορές.',
            ],
            [
                'key' => 'skills',
                'label' => 'Επαγγελματικές δεξιότητες',
                'weight' => 5,
                'done' => !empty(array_filter((array) ($d['skills'] ?? []))),
                'link' => 'drivers/edit-profile',
            ],

            // ── Παρουσίαση ──────────────────────────────────────────
            [
                'key' => 'availability',
                'label' => 'Δήλωση διαθεσιμότητας',
                'weight' => 5,
                'done' => !empty($d['available_for_work']),
                'link' => 'drivers/edit-profile',
                'why' => 'Οι μη διαθέσιμοι δεν εμφανίζονται στις αναζητήσεις.',
            ],
            [
                'key' => 'legal',
                'label' => 'Δήλωση ποινικού μητρώου',
                'weight' => 5,
                'done' => ($d['legal_status'] ?? '') === 'yes',
                'link' => 'drivers/edit-profile',
            ],
        ];

        $total = 0;
        $earned = 0;
        $missing = [];

        foreach ($checks as $check) {
            $total += $check['weight'];
            if ($check['done']) {
                $earned += $check['weight'];
            } else {
                $missing[] = [
                    'label' => $check['label'],
                    'link' => $check['link'],
                    'why' => $check['why'] ?? null,
                    'weight' => $check['weight'],
                ];
            }
        }

        // Τα πιο βαριά ελλείποντα πρώτα: εκεί κερδίζει περισσότερο ο οδηγός.
        usort($missing, static fn($a, $b) => $b['weight'] <=> $a['weight']);

        return [
            'percent' => $total > 0 ? (int) round(($earned / $total) * 100) : 0,
            'done' => count($checks) - count($missing),
            'total' => count($checks),
            'missing' => $missing,
        ];
    }

    /** Υπάρχει έστω μία κατηγορία με ημερομηνία λήξης; */
    private function anyWithExpiry(array $licenses): bool
    {
        foreach ($licenses as $license) {
            if (!empty($license['expiry_date'])) {
                return true;
            }
        }
        return false;
    }
}
