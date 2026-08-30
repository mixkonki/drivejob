<?php

namespace Drivejob\Services\Score\Collectors;

use PDO;
use Throwable;
use Drivejob\Services\Score\Collector;
use Drivejob\Services\Score\Contribution;

/**
 * Ασφαλιστικό ιστορικό (ένσημα e-ΕΦΚΑ). Μέγιστο 30. (01/09/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΕΝΕΡΓΟΠΟΙΗΘΗΚΕ ΠΑΝΩ ΣΕ ΠΡΑΓΜΑΤΙΚΑ ΔΕΔΟΜΕΝΑ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Ο Κώστας ανέβασε τα τρία δικά του exports (01/09): ΙΚΑ μισθωτού με
 * επωνυμίες εργοδοτών, ΤΕΒΕ/ΤΣΑ 1995-2016 και ΟΑΕΕ 2017-2026. Δηλαδή
 * η ίδια η ερώτησή του «τι κάνουμε με τους αυτοαπασχολούμενους»
 * απαντήθηκε από τα αρχεία του: ο Αναλυτικός Λογαριασμός Ασφάλισης
 * ΠΕΡΙΕΧΕΙ την αυτοαπασχόληση — ταμεία μη μισθωτών, μήνας-μήνας.
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΙ ΒΑΘΜΟΛΟΓΕΙ ΚΑΙ ΤΙ ΟΧΙ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Βαθμολογεί ΔΙΑΡΚΕΙΑ ασφαλισμένης εργασίας — όχι «ως οδηγός». Το
 * export δεν γράφει επάγγελμα· το «ως οδηγός» για τον μισθωτό το
 * βεβαιώνει ο κατονομαζόμενος εργοδότης (σύστημα συστάσεων) και για
 * τον αυτοαπασχολούμενο ο ΚΑΔ της ΑΑΔΕ (μελλοντική προσθήκη). Γι' αυτό
 * το ταβάνι είναι 30 και όχι ψηλότερα: είναι θεμέλιο, όχι όλη η απόδειξη.
 *
 * ΚΛΙΜΑΚΑ: 15 χρόνια (180 μήνες) = πλήρες. Ρίζα αντί για γραμμική —
 * τα πρώτα χρόνια λένε τα περισσότερα:
 *     2 χρόνια → 11 μον. · 5 → 17 · 10 → 24 · 15+ → 30
 *
 * ΑΝΕΠΙΒΕΒΑΙΩΤΟ = ×0.6: το xlsx επεξεργάζεται με Excel. Όταν ελεγχθεί
 * Βεβαίωση με κωδικό docs.gov.gr (verified=1), μετρά πλήρως.
 */
final class InsuranceRecordCollector implements Collector
{
    private const CAP = 30.0;
    private const FULL_MONTHS = 180.0;
    private const UNVERIFIED_FACTOR = 0.6;

    public function source(): string
    {
        return 'insurance_record';
    }

    public function collect(array $profile, PDO $pdo): array
    {
        $driverId = (int) ($profile['id'] ?? 0);
        if ($driverId <= 0) {
            return [];
        }

        try {
            $stmt = $pdo->prepare(
                'SELECT fund_kind, verified, SUM(months) AS months,
                        MIN(date_from) AS date_from, MAX(date_to) AS date_to,
                        COUNT(DISTINCT NULLIF(employer_name, "")) AS employers
                 FROM driver_insurance_periods
                 WHERE driver_id = ?
                 GROUP BY fund_kind, verified'
            );
            $stmt->execute([$driverId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }

        if (!$rows) {
            return [];
        }

        // Πρώτα το σύνολο για την καμπύλη, μετά μοιρασιά αναλογικά — ώστε
        // το άθροισμα των γραμμών να ισούται με την τιμή της καμπύλης.
        $weighted = 0.0;
        foreach ($rows as $r) {
            $weighted += (float) $r['months'] * ((int) $r['verified'] === 1 ? 1.0 : self::UNVERIFIED_FACTOR);
        }
        $totalPoints = self::CAP * min(1.0, sqrt(min($weighted, self::FULL_MONTHS) / self::FULL_MONTHS));

        $out = [];
        foreach ($rows as $r) {
            $months = (float) $r['months'];
            $isVerified = (int) $r['verified'] === 1;
            $share = $weighted > 0
                ? ($months * ($isVerified ? 1.0 : self::UNVERIFIED_FACTOR)) / $weighted
                : 0;

            $years = $months / 12;
            $label = ($r['fund_kind'] === 'self_employed' ? 'Αυτοαπασχόληση' : 'Μισθωτή εργασία')
                . ' · ' . number_format($years, 1, ',', '.') . ' έτη ασφάλισης';

            $detail = date('m/Y', strtotime((string) $r['date_from']))
                . ' – ' . date('m/Y', strtotime((string) $r['date_to']));
            if ((int) $r['employers'] > 0) {
                $detail .= ' · ' . (int) $r['employers'] . ' εργοδότ' . ((int) $r['employers'] === 1 ? 'ης' : 'ες');
            }
            if (!$isVerified) {
                $detail .= ' · μειωμένο έως την επαλήθευση gov.gr';
            }

            $out[] = new Contribution(
                source: $this->source(),
                label: $label,
                points: round($totalPoints * $share, 2),
                maxPoints: self::CAP,
                detail: $detail,
                occurredAt: $r['date_to'] ?? null,
            );
        }

        return $out;
    }
}
