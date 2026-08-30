<?php

namespace Drivejob\Services\Score\Collectors;

use PDO;
use Drivejob\Helpers\SpecialLicenseTypes;
use Drivejob\Services\Score\Collector;
use Drivejob\Services\Score\Contribution;

/**
 * ADR, κάρτα ταχογράφου, ειδικές άδειες. Μέγιστο 35. (01/09/2026)
 *
 * ΓΙΑΤΙ Η ΚΑΡΤΑ ΤΑΧΟΓΡΑΦΟΥ ΑΞΙΖΕΙ ΤΟΣΟ: χωρίς αυτήν ο οδηγός δεν
 * μπορεί να οδηγήσει νόμιμα όχημα άνω των 3,5 τόνων σε επαγγελματική
 * μεταφορά. Δεν είναι «καλό να έχει» — είναι προϋπόθεση, και ο εργοδότης
 * που βλέπει «δεν διαθέτει» δεν προχωρά στη συνέντευξη.
 *
 * Είναι επίσης η πόρτα για το επόμενο βήμα: όποιος έχει κάρτα, έχει και
 * αρχείο .DDD να ανεβάσει (βλ. TachographCollector).
 */
final class CertificateCollector implements Collector
{
    private const ADR_BASE = 12;
    /** Επιπλέον μονάδες ανά κατηγορία ADR — οι ανώτερες ανοίγουν αγορές. */
    private const ADR_BONUS = [
        'Π1' => 0, 'Π2' => 1, 'Π3' => 1, 'Π4' => 2,
        'Π5' => 2, 'Π6' => 3, 'Π7' => 3, 'Π8' => 4,
    ];
    private const TACHO_POINTS = 9;
    private const SPECIAL_POINTS = 4;
    private const SPECIAL_CAP = 12;
    private const EXPIRED_FACTOR = 0.30;

    public function source(): string
    {
        return 'certificate';
    }

    public function collect(array $profile, PDO $pdo): array
    {
        $out = [];

        // ── ADR ─────────────────────────────────────────────────────────
        $adr = $profile['adr_certificates'][0] ?? null;
        if ($adr) {
            $type = (string) ($adr['adr_type'] ?? '');
            $exp = $adr['expiry_date'] ?? null;
            $expired = $exp && strtotime($exp) < time();
            $points = self::ADR_BASE + (self::ADR_BONUS[$type] ?? 0);

            $out[] = new Contribution(
                source: $this->source(),
                label: 'Πιστοποιητικό ADR' . ($type !== '' ? ' (' . $type . ')' : ''),
                points: $expired ? $points * self::EXPIRED_FACTOR : $points,
                maxPoints: self::ADR_BASE + 4,
                detail: $expired
                    ? 'Έχει λήξει — απαιτεί επανακατάρτιση'
                    : 'Μεταφορά επικίνδυνων εμπορευμάτων',
                expiresAt: $exp,
            );
        }

        // ── Κάρτα ψηφιακού ταχογράφου ───────────────────────────────────
        $tacho = $profile['tachograph_cards'][0] ?? null;
        if ($tacho) {
            $exp = $tacho['expiry_date'] ?? null;
            $expired = $exp && strtotime($exp) < time();

            $out[] = new Contribution(
                source: $this->source(),
                label: 'Κάρτα ψηφιακού ταχογράφου',
                points: $expired ? self::TACHO_POINTS * self::EXPIRED_FACTOR : self::TACHO_POINTS,
                maxPoints: self::TACHO_POINTS,
                detail: $expired
                    ? 'Έχει λήξει — χωρίς αυτήν δεν επιτρέπεται επαγγελματική μεταφορά'
                    : 'Προϋπόθεση για επαγγελματική μεταφορά',
                expiresAt: $exp,
            );
        }

        // ── Ειδικές άδειες (ΕΔΧ, ζώντα ζώα, ΠΕΕ κ.λπ.) ──────────────────
        $given = 0;
        foreach (($profile['special_licenses'] ?? []) as $sl) {
            if ($given >= self::SPECIAL_CAP) {
                break;
            }
            $code = (string) ($sl['license_type'] ?? '');
            $exp = $sl['expiry_date'] ?? null;
            // Κενή ημερομηνία = ρητή επιλογή «αορίστου», όχι παράλειψη.
            $expired = $exp && strtotime($exp) < time();
            $points = min(self::SPECIAL_POINTS, self::SPECIAL_CAP - $given);
            $given += $points;

            $out[] = new Contribution(
                source: $this->source(),
                label: SpecialLicenseTypes::label($code) ?: 'Ειδική άδεια',
                points: $expired ? $points * self::EXPIRED_FACTOR : $points,
                maxPoints: self::SPECIAL_POINTS,
                detail: $expired ? 'Έχει λήξει' : ($exp ? '' : 'Αορίστου διάρκειας'),
                expiresAt: $exp,
            );
        }

        return $out;
    }
}
