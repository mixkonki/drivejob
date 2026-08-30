<?php

namespace Drivejob\Services\Score\Collectors;

use PDO;
use Throwable;
use Drivejob\Services\Score\Collector;
use Drivejob\Services\Score\Contribution;

/**
 * Αξιολογήσεις εργοδοτών — Η ΜΟΝΗ ΜΑΡΤΥΡΙΑ ΤΡΙΤΟΥ ΠΟΥ ΕΧΟΥΜΕ. (01/09/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ ΓΡΑΦΤΗΚΕ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Ο πίνακας `driver_reviews` υπήρχε από την αρχή, με πλήρη δομή
 * (συνολική βαθμολογία + πέντε επιμέρους). Υπήρχε και η
 * `RatingService::addDriverReview()` που τον γεμίζει. Το
 * `calculateTotalRating()` όμως **δεν τον καλούσε πουθενά**: το μοναδικό
 * σήμα που ερχόταν από τρίτο πρόσωπο υπολογιζόταν, αποθηκευόταν, και δεν
 * έφτανε ποτέ στον αριθμό που έβλεπε ο εργοδότης.
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ ΣΥΡΡΙΚΝΩΣΗ ΠΡΟΣ ΤΟ ΟΥΔΕΤΕΡΟ ΚΑΙ ΟΧΙ ΣΚΕΤΟΣ ΜΕΣΟΣ ΟΡΟΣ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Με σκέτο μέσο όρο, μία αξιολόγηση 5/5 δίνει 100 και μία 1/5 δίνει 0.
 * Δηλαδή ο πρώτος εργοδότης — φίλος ή εχθρός — ορίζει μόνος του τη φήμη
 * ενός ανθρώπου. Ένας θυμωμένος πρώην εργοδότης θα μπορούσε να
 * καταστρέψει καριέρα με ένα κλικ, και ένας κουμπάρος να τη χαρίσει.
 *
 *     βαθμός = (Σ αξιολογήσεων + k × ουδέτερο) / (πλήθος + k)
 *
 * με ουδέτερο = 3 και k = 2. Πρακτικά:
 *
 *     1 × 5 αστέρια   → 3,67  → 67 στα 100
 *     5 × 5 αστέρια   → 4,43  → 86
 *     1 × 1 αστέρι    → 2,33  → 33   (όχι 0)
 *     5 × 1 αστέρι    → 1,57  → 14
 *
 * Δηλαδή: **η φήμη χτίζεται και καταστρέφεται με επανάληψη, όχι με ένα
 * περιστατικό.** Όσο μαζεύονται μαρτυρίες, τόσο πλησιάζει την αλήθεια.
 *
 * ΤΟ «ΘΑ ΤΟΝ ΞΑΝΑΠΡΟΣΛΑΜΒΑΝΕΣ;» είναι το ισχυρότερο ερώτημα σε κάθε
 * έρευνα συστάσεων — απαντιέται πιο ειλικρινά από τα αστέρια, γιατί
 * είναι δεσμευτικό. Μπαίνει ως ξεχωριστό σήμα όταν υπάρχει.
 */
final class EmployerReviewCollector implements Collector
{
    private const NEUTRAL = 3.0;      // ουδέτερη βαθμολογία στην κλίμακα 1-5
    private const PRIOR_WEIGHT = 2.0; // k — πόσο «τραβά» το ουδέτερο

    public function source(): string
    {
        return 'employer_review';
    }

    public function collect(array $profile, PDO $pdo): array
    {
        $driverId = (int) ($profile['id'] ?? 0);
        if ($driverId <= 0) {
            return [];
        }

        try {
            $stmt = $pdo->prepare(
                'SELECT rating, would_rehire, created_at,
                        professionalism_rating, driving_skills_rating,
                        reliability_rating, communication_rating, technical_skills_rating
                 FROM driver_reviews WHERE driver_id = ?'
            );
            $stmt->execute([$driverId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            // Ο πίνακας ή η στήλη μπορεί να λείπει σε παλιά βάση — η
            // βαθμολογία δεν επιτρέπεται να ρίξει τη σελίδα του προφίλ.
            return [];
        }

        if (!$rows) {
            return [];
        }

        // ── Ο σταθμισμένος μέσος όρος ───────────────────────────────────
        $sum = 0.0;
        $n = 0;
        foreach ($rows as $r) {
            $v = (float) ($r['rating'] ?? 0);
            if ($v > 0) {
                $sum += $v;
                $n++;
            }
        }
        if ($n === 0) {
            return [];
        }

        $shrunk = ($sum + self::PRIOR_WEIGHT * self::NEUTRAL) / ($n + self::PRIOR_WEIGHT);
        // 1-5 → 0-100. Το 1 (χειρότερο) είναι 0, όχι 20.
        $points = max(0.0, min(100.0, ($shrunk - 1) / 4 * 100));

        $out = [];
        $out[] = new Contribution(
            source: $this->source(),
            label: $n === 1 ? 'Αξιολόγηση εργοδότη' : $n . ' αξιολογήσεις εργοδοτών',
            points: $points,
            maxPoints: 100,
            detail: sprintf(
                'Μέσος όρος %.1f/5 από %d %s. Όσο περισσότερες, τόσο βαρύτερη η βαθμολογία.',
                $sum / $n,
                $n,
                $n === 1 ? 'αξιολόγηση' : 'αξιολογήσεις'
            ),
            occurredAt: $rows[0]['created_at'] ?? null,
        );

        // ── «Θα τον ξαναπροσλάμβανες;» ──────────────────────────────────
        $rehireYes = 0;
        $rehireTotal = 0;
        foreach ($rows as $r) {
            if ($r['would_rehire'] !== null && $r['would_rehire'] !== '') {
                $rehireTotal++;
                $rehireYes += (int) $r['would_rehire'] === 1 ? 1 : 0;
            }
        }
        if ($rehireTotal > 0) {
            $ratio = $rehireYes / $rehireTotal;
            $out[] = new Contribution(
                source: $this->source(),
                label: 'Θα τον ξαναπροσλάμβαναν',
                // Μπόνους/ποινή γύρω από το ουδέτερο: −10 έως +10 μονάδες.
                points: ($ratio - 0.5) * 20,
                maxPoints: 10,
                detail: $rehireYes . ' από ' . $rehireTotal . ' εργοδότες',
            );
        }

        return $out;
    }

    /**
     * Οι επιμέρους βαθμολογίες — για την οθόνη, όχι για τον αριθμό.
     *
     * Δεν μπαίνουν στη βαθμολογία γιατί θα ήταν διπλή μέτρηση του ίδιου
     * πράγματος: ο εργοδότης που βάζει 5 στο σύνολο βάζει 5 και παντού.
     * Είναι όμως χρήσιμες στον οδηγό — του λένε ΠΟΥ υστερεί.
     *
     * @return array<string, float> ετικέτα → μέσος όρος 1-5
     */
    public static function facets(PDO $pdo, int $driverId): array
    {
        $map = [
            'reliability_rating' => 'Αξιοπιστία',
            'driving_skills_rating' => 'Οδηγικές ικανότητες',
            'professionalism_rating' => 'Επαγγελματισμός',
            'communication_rating' => 'Επικοινωνία',
            'technical_skills_rating' => 'Τεχνικές γνώσεις',
        ];

        try {
            $cols = implode(', ', array_map(static fn($c) => "AVG($c) AS $c", array_keys($map)));
            $stmt = $pdo->prepare("SELECT $cols FROM driver_reviews WHERE driver_id = ?");
            $stmt->execute([$driverId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }

        $out = [];
        foreach ($map as $col => $label) {
            if (isset($row[$col]) && $row[$col] !== null && (float) $row[$col] > 0) {
                $out[$label] = round((float) $row[$col], 1);
            }
        }
        return $out;
    }
}
