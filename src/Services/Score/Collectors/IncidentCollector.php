<?php

namespace Drivejob\Services\Score\Collectors;

use PDO;
use Throwable;
use Drivejob\Services\Score\Collector;
use Drivejob\Services\Score\Contribution;

/**
 * Καταγεγραμμένα συμβάντα — ΜΟΝΟ ΕΠΑΛΗΘΕΥΜΕΝΑ. (01/09/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΔΥΟ ΑΛΛΑΓΕΣ ΑΠΟ ΤΟΝ ΠΑΛΙΟ ΥΠΟΛΟΓΙΣΜΟ
 * ══════════════════════════════════════════════════════════════════════
 *
 * 1. ΔΕΝ ΞΕΚΙΝΑΜΕ ΑΠΟ ΤΟ 100. Ο παλιός κώδικας έκανε
 *    `$incidentScore = 100;` και αφαιρούσε ποινές. Επειδή κανείς δεν
 *    καταχωρεί συμβάντα, ΚΑΘΕ οδηγός έπαιρνε 100 στο 30% της
 *    βαθμολογίας — δηλαδή 30 δωρεάν μονάδες για την «ασφάλειά» του,
 *    χωρίς κανένα στοιχείο ασφάλειας.
 *
 *    Η απουσία συμβάντων ΔΕΝ είναι απόδειξη ασφαλούς οδήγησης. Είναι
 *    απουσία πληροφορίας. Εδώ τα συμβάντα μόνο ΑΦΑΙΡΟΥΝ· η θετική
 *    πλευρά έρχεται από αξιολογήσεις και μετρήσεις.
 *
 * 2. ΜΟΝΟ `verified = 1`. Ο πίνακας έχει τη στήλη από την αρχή και
 *    κανείς δεν τη διάβαζε. Χωρίς αυτόν τον έλεγχο, ένα ανώνυμο ή
 *    κακόβουλο συμβάν ρίχνει τη βαθμολογία ανθρώπου. Ένα σήμα που
 *    βλάπτει πρέπει να έχει όνομα από πίσω.
 *
 * ΠΑΛΑΙΩΣΗ: το συμβάν του 2019 δεν λέει για τον οδηγό του 2026 ό,τι
 * έλεγε τότε. Η ποινή σβήνει γραμμικά μέσα σε 3 χρόνια.
 */
final class IncidentCollector implements Collector
{
    /** Ποινή ανά μονάδα σοβαρότητας (1-5). */
    private const PENALTY = [
        'accident' => 5.0,
        'traffic_violation' => 3.0,
        'near_miss' => 1.5,
        'complaint' => 2.0,
        'other' => 1.0,
    ];

    private const LABELS = [
        'accident' => 'Ατύχημα',
        'traffic_violation' => 'Παράβαση',
        'near_miss' => 'Παρ’ ολίγον συμβάν',
        'complaint' => 'Παράπονο',
        'other' => 'Συμβάν',
    ];

    private const FADE_YEARS = 3;
    private const MAX_TOTAL_PENALTY = 40.0;

    public function source(): string
    {
        return 'incident';
    }

    public function collect(array $profile, PDO $pdo): array
    {
        $driverId = (int) ($profile['id'] ?? 0);
        if ($driverId <= 0) {
            return [];
        }

        try {
            $stmt = $pdo->prepare(
                'SELECT incident_type, incident_date, severity, description
                 FROM driver_incidents
                 WHERE driver_id = ? AND verified = 1 AND incident_date >= ?
                 ORDER BY incident_date DESC'
            );
            $stmt->execute([$driverId, date('Y-m-d', strtotime('-' . self::FADE_YEARS . ' years'))]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }

        $out = [];
        $total = 0.0;

        foreach ($rows as $r) {
            $type = (string) ($r['incident_type'] ?? 'other');
            $severity = max(1, min(5, (int) ($r['severity'] ?? 1)));
            $penalty = (self::PENALTY[$type] ?? 1.0) * $severity;

            // Παλαίωση: πλήρης ποινή σήμερα, μηδέν στα 3 χρόνια.
            $age = (time() - strtotime((string) $r['incident_date'])) / (365.25 * 86400);
            $penalty *= max(0.0, 1 - ($age / self::FADE_YEARS));

            if ($total + $penalty > self::MAX_TOTAL_PENALTY) {
                $penalty = self::MAX_TOTAL_PENALTY - $total;
            }
            if ($penalty <= 0.01) {
                continue;
            }
            $total += $penalty;

            $out[] = new Contribution(
                source: $this->source(),
                label: self::LABELS[$type] ?? 'Συμβάν',
                points: -$penalty,
                maxPoints: 0,
                detail: date('m/Y', strtotime((string) $r['incident_date']))
                    . ' · σοβαρότητα ' . $severity . '/5',
                occurredAt: $r['incident_date'] ?? null,
            );
        }

        return $out;
    }
}
