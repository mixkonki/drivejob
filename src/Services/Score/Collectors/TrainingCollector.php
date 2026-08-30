<?php

namespace Drivejob\Services\Score\Collectors;

use PDO;
use Drivejob\Services\Score\Collector;
use Drivejob\Services\Score\Contribution;

/**
 * Σεμινάρια και κατάρτιση. Μέγιστο 25. (01/09/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ ΤΟ «ΜΕ ΦΟΡΕΑ» ΑΞΙΖΕΙ ΔΙΠΛΑΣΙΑ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Ο παλιός υπολογισμός ήταν `min(count($certifications) * 5, 20)` —
 * μετρούσε ΓΡΑΜΜΕΣ. Ένας οδηγός που γράφει τέσσερις τίτλους χωρίς
 * τίποτα άλλο έπαιρνε ό,τι κι ένας με τέσσερα πιστοποιητικά από
 * αναγνωρισμένο φορέα.
 *
 * Το επίπεδο τεκμηρίου «CERTIFIED» σημαίνει «το βεβαιώνει φορέας». Χωρίς
 * όνομα φορέα δεν το βεβαιώνει κανείς — άρα μισές μονάδες, και το λέει
 * στον οδηγό ώστε να συμπληρώσει το πεδίο.
 *
 * ΠΡΟΣΦΑΤΟΤΗΤΑ: ένα σεμινάριο ασφάλειας του 2009 δεν λέει το ίδιο με ένα
 * του 2025. Ό,τι είναι παλαιότερο από 5 χρόνια μετράει μισό.
 */
final class TrainingCollector implements Collector
{
    private const WITH_PROVIDER = 5;
    private const WITHOUT_PROVIDER = 2.5;
    private const CAP = 25;
    private const STALE_YEARS = 5;
    private const STALE_FACTOR = 0.5;
    private const EXPIRED_FACTOR = 0.4;

    public function source(): string
    {
        return 'training';
    }

    public function collect(array $profile, PDO $pdo): array
    {
        $rows = $profile['certifications'] ?? [];
        if (!$rows) {
            return [];
        }

        // Τα πιο πρόσφατα πρώτα: αν χτυπήσει το ταβάνι, να έχουν μπει τα
        // σεμινάρια που μετράνε περισσότερο.
        usort($rows, static fn($a, $b) => strtotime($b['date'] ?? '1970-01-01') <=> strtotime($a['date'] ?? '1970-01-01'));

        $given = 0.0;
        $out = [];
        $staleBefore = strtotime('-' . self::STALE_YEARS . ' years');

        foreach ($rows as $c) {
            if ($given >= self::CAP) {
                break;
            }

            $hasProvider = !empty(trim((string) ($c['provider'] ?? '')));
            $points = $hasProvider ? self::WITH_PROVIDER : self::WITHOUT_PROVIDER;

            $date = $c['date'] ?? null;
            $stale = $date && strtotime($date) < $staleBefore;
            if ($stale) {
                $points *= self::STALE_FACTOR;
            }

            $exp = $c['expiry'] ?? null;
            $expired = $exp && strtotime($exp) < time();
            if ($expired) {
                $points *= self::EXPIRED_FACTOR;
            }

            $points = min($points, self::CAP - $given);
            $given += $points;

            $why = [];
            if (!$hasProvider) {
                $why[] = 'χωρίς φορέα — πρόσθεσέ τον για διπλάσιες μονάδες';
            }
            if ($stale) {
                $why[] = 'άνω των ' . self::STALE_YEARS . ' ετών';
            }
            if ($expired) {
                $why[] = 'έχει λήξει';
            }

            $out[] = new Contribution(
                source: $this->source(),
                label: (string) ($c['title'] ?? 'Σεμινάριο'),
                points: $points,
                maxPoints: self::WITH_PROVIDER,
                detail: $why ? implode(' · ', $why) : ($hasProvider ? (string) $c['provider'] : ''),
                occurredAt: $date,
                expiresAt: $exp,
            );
        }

        return $out;
    }
}
