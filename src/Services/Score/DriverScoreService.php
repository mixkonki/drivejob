<?php

namespace Drivejob\Services\Score;

use PDO;
use Throwable;
use Drivejob\Core\Logger;

/**
 * Ο συνθέτης της βαθμολογίας. (01/09/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΙ ΚΑΝΕΙ ΚΑΙ ΤΙ ΔΕΝ ΞΕΡΕΙ
 * ══════════════════════════════════════════════════════════════════════
 *
 * ΔΕΝ ξέρει τίποτα για άδειες, ΠΕΙ, ADR ή ταχογράφους. Ρωτά κάθε
 * collector του μητρώου «τι έχεις;», παίρνει Contribution[], και τα
 * συνθέτει. Η προσθήκη του ταχογράφου αύριο είναι μία κλάση και μία
 * γραμμή στο ScoreSource — τίποτα εδώ.
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΟΙ ΤΡΕΙΣ ΑΠΟΦΑΣΕΙΣ ΠΟΥ ΟΡΙΖΟΥΝ ΤΟΝ ΑΡΙΘΜΟ
 * ══════════════════════════════════════════════════════════════════════
 *
 * 1. ΦΘΙΝΟΥΣΕΣ ΑΠΟΔΟΣΕΙΣ ΑΝΤΙ ΓΙΑ ΤΑΒΑΝΙ.
 *
 *        credentials = 100 × (1 − e^(−μονάδες / 55))
 *
 *    Ο παλιός κώδικας άθροιζε και έκοβε με `min(…, 100)`. Με CE + ADR +
 *    χειριστή + εμπειρία το άθροισμα έβγαινε 131, δηλαδή **κάθε καλά
 *    συμπληρωμένος οδηγός φορτηγού έπιανε 100 και έπαυε να ξεχωρίζει από
 *    τον επόμενο**. Η εκθετική καμπύλη δεν φτάνει ποτέ το 100: το 80ό
 *    προσόν μετράει λιγότερο από το 2ο, αλλά ποτέ μηδέν.
 *
 *        30 μονάδες → 42     80 μονάδες → 77
 *        60 μονάδες → 66    135 μονάδες → 91
 *
 * 2. Ο ΣΥΝΟΛΙΚΟΣ ΑΡΙΘΜΟΣ ΕΙΝΑΙ `null` ΧΩΡΙΣ ΜΑΡΤΥΡΙΑ ΤΡΙΤΟΥ.
 *    Επιβάλλεται ΕΔΩ, στο μοντέλο, όχι στην όψη — ώστε καμία μελλοντική
 *    σελίδα να μην μπορέσει κατά λάθος να δείξει αριθμό που δεν στηρίζεται
 *    σε τίποτα.
 *
 * 3. ΦΗΜΗ 60% / ΠΡΟΣΟΝΤΑ 40%.
 *    Τα χαρτιά σε φέρνουν στη συνέντευξη· η συμπεριφορά σε κρατά στη
 *    δουλειά. Και τα χαρτιά είναι δυαδικά (τα έχεις ή δεν τα έχεις) ενώ
 *    η συμπεριφορά έχει διαβαθμίσεις — άρα εκεί υπάρχει πληροφορία που
 *    ξεχωρίζει τους ανθρώπους.
 */
final class DriverScoreService
{
    /** Ρυθμός της καμπύλης προσόντων. Μεγαλύτερο = πιο αργή άνοδος. */
    private const CURVE_K = 55.0;

    private const W_CREDENTIALS = 0.40;
    private const W_REPUTATION = 0.60;

    public function __construct(private PDO $pdo)
    {
    }

    // ══════════════════════════════════════════════════════════════════
    //  ΥΠΟΛΟΓΙΣΜΟΣ
    // ══════════════════════════════════════════════════════════════════

    /**
     * @param array $profile το πλήρες προφίλ — ΤΟ ΙΔΙΟ που τρώει ο
     *                       DriverCvService, ώστε βαθμολογία και
     *                       βιογραφικό να μη διαφωνούν ποτέ.
     */
    public function build(array $profile): DriverScore
    {
        $driverId = (int) ($profile['id'] ?? 0);

        $contributions = [];
        $sources = [];

        foreach (ScoreSource::ALL as $key => $spec) {
            $rows = [];

            if ($spec['active']) {
                try {
                    /** @var Collector $collector */
                    $collector = new $spec['collector']();
                    $rows = $collector->collect($profile, $this->pdo);
                } catch (Throwable $e) {
                    // Μία χαλασμένη πηγή δεν ρίχνει τη βαθμολογία ούτε τη
                    // σελίδα: λείπει το κομμάτι της, τα υπόλοιπα μετράνε.
                    Logger::error('Score collector «' . $key . '» απέτυχε: ' . $e->getMessage());
                    $rows = [];
                }
            }

            $points = 0.0;
            foreach ($rows as $c) {
                if ($c->counts()) {
                    $points += $c->points;
                }
            }

            $sources[$key] = [
                'label' => $spec['label'],
                'evidence' => $spec['evidence'],
                'group' => $spec['group'],
                'weight' => $spec['weight'],
                'active' => $spec['active'],
                'hint' => $spec['hint'],
                'has_data' => $rows !== [],
                'points' => round($points, 1),
                'counts' => ScoreSource::counts($spec['evidence']),
            ];

            $contributions = array_merge($contributions, $rows);
        }

        $credentials = $this->credentialsScore($contributions);
        $reputation = $this->reputationScore($contributions);

        $total = $reputation === null
            ? null
            : ($credentials * self::W_CREDENTIALS) + ($reputation * self::W_REPUTATION);

        return new DriverScore(
            driverId: $driverId,
            credentials: $credentials,
            reputation: $reputation,
            total: $total,
            confidence: $this->confidence($sources),
            contributions: $contributions,
            sources: $sources,
        );
    }

    /**
     * Χαρτιά → 0-100 με φθίνουσες αποδόσεις.
     *
     * @param Contribution[] $contributions
     */
    private function credentialsScore(array $contributions): float
    {
        $points = 0.0;
        foreach ($contributions as $c) {
            if ($c->group() === ScoreSource::GROUP_CREDENTIALS && $c->counts()) {
                $points += $c->points;
            }
        }
        if ($points <= 0) {
            return 0.0;
        }

        return 100.0 * (1 - exp(-$points / self::CURVE_K));
    }

    /**
     * Φήμη → 0-100 ή `null` αν δεν υπάρχει καμία μαρτυρία τρίτου.
     *
     * ΓΙΑΤΙ ΤΑ ΣΥΜΒΑΝΤΑ ΜΟΝΑ ΤΟΥΣ ΔΕΝ ΦΤΙΑΧΝΟΥΝ ΦΗΜΗ: αν ο μόνος
     * καταγεγραμμένος πόντος ήταν μια ποινή, ο οδηγός θα έβγαινε 0 —
     * σαν να ξέρουμε ότι είναι κακός, ενώ ξέρουμε μόνο ένα περιστατικό.
     * Τα συμβάντα χρειάζονται βάση για να αφαιρέσουν από κάπου· χωρίς
     * αξιολόγηση ή μέτρηση, η βάση είναι το ουδέτερο 50.
     *
     * @param Contribution[] $contributions
     */
    private function reputationScore(array $contributions): ?float
    {
        $positive = [];   // [points, maxPoints] από αξιολογήσεις/μετρήσεις
        $penalty = 0.0;
        $hasSignal = false;

        foreach ($contributions as $c) {
            if ($c->group() !== ScoreSource::GROUP_REPUTATION || !$c->counts()) {
                continue;
            }
            if ($c->source === 'incident') {
                $penalty += abs($c->points);
                continue;
            }
            $hasSignal = true;
            $positive[] = $c;
        }

        if (!$hasSignal && $penalty <= 0) {
            return null;   // καμία μαρτυρία — ούτε καλή ούτε κακή
        }

        if (!$hasSignal) {
            // Μόνο συμβάντα: ξεκινάμε από το ουδέτερο και αφαιρούμε.
            return max(0.0, 50.0 - $penalty);
        }

        // Οι θετικές πηγές είναι ήδη σε κλίμακα 0-100 η καθεμία
        // (αξιολογήσεις, ταχογράφος, τηλεματική). Σταθμίζονται με το
        // βάρος τους στο μητρώο, ώστε ο ταχογράφος να μετράει
        // περισσότερο από την τηλεματική όταν υπάρχουν και τα δύο.
        $sum = 0.0;
        $weights = 0.0;
        foreach ($positive as $c) {
            $w = (float) abs(ScoreSource::ALL[$c->source]['weight'] ?? 1);
            if ($c->maxPoints > 0 && $c->maxPoints < 100) {
                // Μπόνους τύπου «θα τον ξαναπροσλάμβαναν»: προσαύξηση,
                // όχι ξεχωριστός βαθμός.
                continue;
            }
            $sum += $c->points * $w;
            $weights += $w;
        }
        $base = $weights > 0 ? $sum / $weights : 50.0;

        // Τα μικρά μπόνους/ποινές μπαίνουν πάνω στη βάση.
        foreach ($positive as $c) {
            if ($c->maxPoints > 0 && $c->maxPoints < 100) {
                $base += $c->points;
            }
        }

        return max(0.0, min(100.0, $base - $penalty));
    }

    /**
     * Πόσο στηρίζεται ο αριθμός — 0-100.
     *
     * Δεν είναι βαθμός του οδηγού· είναι βαθμός των **δεδομένων μας**.
     * Ο εργοδότης πρέπει να ξέρει αν κοιτάζει συμπέρασμα από έξι πηγές ή
     * από μία.
     */
    private function confidence(array $sources): float
    {
        $have = 0.0;
        $possible = 0.0;

        foreach ($sources as $s) {
            if (!$s['active'] || !$s['counts'] || $s['weight'] <= 0) {
                continue;
            }
            $possible += $s['weight'];
            if ($s['has_data']) {
                $have += $s['weight'];
            }
        }

        return $possible > 0 ? ($have / $possible) * 100 : 0.0;
    }

    // ══════════════════════════════════════════════════════════════════
    //  ΑΠΟΘΗΚΕΥΣΗ
    // ══════════════════════════════════════════════════════════════════

    /**
     * Γράφει το αποτέλεσμα ΚΑΙ την ανάλυσή του.
     *
     * ΓΙΑΤΙ ΑΠΟΘΗΚΕΥΟΥΜΕ ΤΗΝ ΑΝΑΛΥΣΗ: όταν ο οδηγός ρωτήσει «γιατί 61;»
     * — και θα ρωτήσει — η απάντηση πρέπει να υπάρχει, όχι να
     * ξαναϋπολογιστεί με τον κώδικα της επόμενης έκδοσης. Το
     * `driver_score_breakdown` είναι το αποδεικτικό της στιγμής.
     */
    public function persist(DriverScore $score): bool
    {
        if ($score->driverId <= 0) {
            return false;
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare(
                'INSERT INTO driver_ratings
                    (driver_id, credentials_score, reputation_score, confidence,
                     has_third_party, total_score, last_updated)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE
                    credentials_score = VALUES(credentials_score),
                    reputation_score  = VALUES(reputation_score),
                    confidence        = VALUES(confidence),
                    has_third_party   = VALUES(has_third_party),
                    total_score       = VALUES(total_score),
                    last_updated      = NOW()'
            );
            $stmt->execute([
                $score->driverId,
                round($score->credentials, 2),
                $score->reputation === null ? null : round($score->reputation, 2),
                round($score->confidence, 2),
                $score->hasThirdParty() ? 1 : 0,
                $score->total === null ? null : round($score->total, 2),
            ]);

            // Η ανάλυση ξαναγράφεται ολόκληρη: είναι φωτογραφία, όχι ιστορικό.
            $this->pdo->prepare('DELETE FROM driver_score_breakdown WHERE driver_id = ?')
                ->execute([$score->driverId]);

            if ($score->contributions) {
                $ins = $this->pdo->prepare(
                    'INSERT INTO driver_score_breakdown
                        (driver_id, source, evidence, score_group, label, detail,
                         points, max_points, counts_toward_score, occurred_at, expires_at, computed_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
                );
                foreach ($score->contributions as $c) {
                    $ins->execute([
                        $score->driverId,
                        $c->source,
                        $c->evidence(),
                        $c->group(),
                        mb_substr($c->label, 0, 190),
                        mb_substr($c->detail, 0, 250),
                        round($c->points, 2),
                        round($c->maxPoints, 2),
                        $c->counts() ? 1 : 0,
                        $c->occurredAt ?: null,
                        $c->expiresAt ?: null,
                    ]);
                }
            }

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            Logger::error('Αποτυχία αποθήκευσης βαθμολογίας: ' . $e->getMessage());
            return false;
        }
    }

    /** Υπολογισμός και αποθήκευση σε μία κίνηση. */
    public function refresh(array $profile): DriverScore
    {
        $score = $this->build($profile);
        $this->persist($score);
        return $score;
    }
}
