<?php

namespace Drivejob\Services\Score\Collectors;

use PDO;
use Drivejob\Services\Score\Collector;
use Drivejob\Services\Score\Contribution;

/**
 * Άδεια οδήγησης και ΠΕΙ. Μέγιστο 45 μονάδες. (01/09/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΟ ΣΦΑΛΜΑ ΠΟΥ ΔΙΟΡΘΩΝΕΙ: ΤΟ ΑΘΡΟΙΣΜΑ ΚΑΤΗΓΟΡΙΩΝ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Ο παλιός υπολογισμός πρόσθετε ΚΑΘΕ κατηγορία:
 *
 *     'B' => 5, 'C' => 10, 'CE' => 15, 'D' => 10, 'DE' => 15 …
 *     foreach ($licenses as $l) { $licenseScore += $categoryScores[...]; }
 *
 * Αλλά η CE ΠΡΟΫΠΟΘΕΤΕΙ τη C, και η C προϋποθέτει τη B. Ο οδηγός με CE
 * έχει υποχρεωτικά και τις τρεις, οπότε έπαιρνε 5+10+15 = 30 για ΕΝΑ
 * προσόν. Μαζί με ADR και χειριστή το άθροισμα ξεπερνούσε το 131 και
 * κοβόταν με `min(…, 100)` — δηλαδή κάθε καλά συμπληρωμένος οδηγός
 * φορτηγού έπιανε ταβάνι και έπαυε να ξεχωρίζει από τον επόμενο.
 *
 * Εδώ μετράει **η κορυφή κάθε αλυσίδας**, όχι το άθροισμα:
 *   εμπορευματικές  C1 → C → C1E → CE
 *   επιβατικές      D1 → D → D1E → DE
 * Οι δύο αλυσίδες προστίθενται μεταξύ τους (είναι όντως δύο διαφορετικά
 * επαγγέλματα), αλλά μέσα στην αλυσίδα μετράει μόνο η ανώτερη.
 *
 * ΛΗΞΗ: άδεια που έχει λήξει δεν μηδενίζεται — ο οδηγός δεν ξέχασε πώς
 * οδηγεί, έχει χαρτί που θέλει ανανέωση. Κρατά το 30% και το λέει.
 */
final class LicenseCollector implements Collector
{
    /** Κορυφή αλυσίδας → μονάδες. Η σειρά είναι από την ανώτερη προς τα κάτω. */
    private const FREIGHT = ['CE' => 16, 'C1E' => 12, 'C' => 10, 'C1' => 6];
    private const PASSENGER = ['DE' => 15, 'D1E' => 13, 'D' => 11, 'D1' => 7];
    private const BASIC = ['BE' => 4, 'B' => 3];

    private const PEI_POINTS = 7;
    private const EXPIRED_FACTOR = 0.30;

    public function source(): string
    {
        return 'license';
    }

    public function collect(array $profile, PDO $pdo): array
    {
        $rows = $profile['licenses'] ?? [];
        $cats = array_map(
            static fn($c) => strtoupper(trim((string) $c)),
            array_column($rows, 'license_type')
        );

        if (!$cats && empty($profile['license_number'])) {
            return [];
        }

        // ── Πότε λήγει; Ό,τι λήγει ΠΡΩΤΟ: έντυπο ή κατηγορίες ───────────
        $docExpiry = $profile['license_document_expiry'] ?? null;
        $catExpiry = null;
        foreach ($rows as $l) {
            if (!empty($l['expiry_date'])
                && ($catExpiry === null || strtotime($l['expiry_date']) < strtotime($catExpiry))) {
                $catExpiry = $l['expiry_date'];
            }
        }
        $effective = $catExpiry;
        if ($docExpiry && (!$effective || strtotime($docExpiry) < strtotime($effective))) {
            $effective = $docExpiry;
        }
        $expired = $effective && strtotime($effective) < time();
        $factor = $expired ? self::EXPIRED_FACTOR : 1.0;

        $out = [];

        // ── Οι δύο αλυσίδες ─────────────────────────────────────────────
        $top = static function (array $chain) use ($cats): ?array {
            foreach ($chain as $code => $points) {
                if (in_array($code, $cats, true)) {
                    return [$code, $points];
                }
            }
            return null;
        };

        $freight = $top(self::FREIGHT);
        $passenger = $top(self::PASSENGER);
        $basic = $top(self::BASIC);

        if ($freight) {
            $out[] = new Contribution(
                source: $this->source(),
                label: 'Κατηγορία ' . $freight[0],
                points: $freight[1] * $factor,
                maxPoints: 16,
                detail: $expired
                    ? 'Εμπορευματικές μεταφορές — η άδεια έχει λήξει'
                    : 'Εμπορευματικές μεταφορές',
                expiresAt: $effective,
            );
        }
        if ($passenger) {
            $out[] = new Contribution(
                source: $this->source(),
                label: 'Κατηγορία ' . $passenger[0],
                points: $passenger[1] * $factor,
                maxPoints: 15,
                detail: $expired
                    ? 'Επιβατικές μεταφορές — η άδεια έχει λήξει'
                    : 'Επιβατικές μεταφορές',
                expiresAt: $effective,
            );
        }
        // Η βασική μετράει ΜΟΝΟ αν δεν υπάρχει επαγγελματική: αλλιώς θα
        // ήταν διπλή μέτρηση της ίδιας αλυσίδας.
        if (!$freight && !$passenger && $basic) {
            $out[] = new Contribution(
                source: $this->source(),
                label: 'Κατηγορία ' . $basic[0],
                points: $basic[1] * $factor,
                maxPoints: 4,
                detail: $expired ? 'Η άδεια έχει λήξει' : '',
                expiresAt: $effective,
            );
        }

        // ── ΠΕΙ: μία φορά το καθένα ─────────────────────────────────────
        // Οι στήλες pei_expiry_c/d επαναλαμβάνονται σε ΚΑΘΕ γραμμή
        // κατηγορίας· χωρίς αυτό το μάζεμα το ΠΕΙ μετριόταν τέσσερις
        // φορές (το ίδιο σφάλμα που είχε και η όψη, 30/08).
        $peiC = null;
        $peiD = null;
        foreach ($rows as $l) {
            $peiC = $peiC ?: ($l['pei_expiry_c'] ?: null);
            $peiD = $peiD ?: ($l['pei_expiry_d'] ?: null);
        }

        foreach ([['ΠΕΙ Εμπορευμάτων', $peiC], ['ΠΕΙ Επιβατών', $peiD]] as [$label, $exp]) {
            if (!$exp) {
                continue;
            }
            $peiExpired = strtotime($exp) < time();
            $out[] = new Contribution(
                source: $this->source(),
                label: $label,
                points: $peiExpired ? self::PEI_POINTS * self::EXPIRED_FACTOR : self::PEI_POINTS,
                maxPoints: self::PEI_POINTS,
                detail: $peiExpired
                    ? 'Έχει λήξει — χωρίς αυτό δεν επιτρέπεται επαγγελματική οδήγηση'
                    : 'Σε ισχύ έως ' . date('m/Y', strtotime($exp)),
                expiresAt: $exp,
            );
        }

        return $out;
    }
}
