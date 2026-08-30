<?php

namespace Drivejob\Services\Matching;

use PDO;
use Throwable;
use Drivejob\Helpers\OperatorSpecialities;

/**
 * Ταίριασμα ΩΣ ΣΥΓΚΡΙΣΗ ΑΠΑΙΤΗΣΕΩΝ — όχι ως ομοιότητα. (01/09/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΙ ΑΝΤΙΚΑΘΙΣΤΑ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Ο παλιός υπολογισμός σύγκρινε προτιμήσεις («ίδιος τύπος απασχόλησης;
 * +20») και σημαίες, και ΔΕΝ ΔΙΑΒΑΖΕ ΚΑΘΟΛΟΥ τις κατηγορίες διπλώματος:
 * αγγελία που ζητούσε ΓΕ έβγαζε το ίδιο ποσοστό για οδηγό με Β και για
 * οδηγό με ΓΕ. Το ποσοστό ήταν αριθμός χωρίς απάντηση στο «γιατί;» —
 * και χωρίς απάντηση στο πιο χρήσιμο ερώτημα: «τι μου λείπει;».
 *
 * Τώρα που η φόρμα αγγελίας γράφει τα ΙΔΙΑ λεξικά με το προφίλ
 * (κατηγορίες-κωδικοί, σημαίες ΠΕΙ/ADR/ταχογράφου/χειριστή, ενιαία
 * εμπειρία), το ταίριασμα γίνεται αυτό που έπρεπε: για κάθε απαίτηση
 * της αγγελίας, ένα ναι ή όχι με όνομα.
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΟ «ΤΙ ΛΕΙΠΕΙ» ΕΙΝΑΙ ΤΟ ΠΡΟΪΟΝ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Το ποσοστό είναι για την ταξινόμηση. Η ΛΙΣΤΑ είναι για τον άνθρωπο:
 *   - ο οδηγός βλέπει «Καλύπτεις 5/6 — λείπει ADR» και ξέρει ακριβώς
 *     ποιο σεμινάριο τον χωρίζει από τη θέση (και ο όμιλος ξέρει τι
 *     να του προσφέρει)·
 *   - η εταιρεία βλέπει ποιον καλύπτει τι, όχι ένα αδιαφανές 73%.
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΟΙ ΑΛΥΣΙΔΕΣ ΤΟΥ ΔΙΠΛΩΜΑΤΟΣ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Η αγγελία απαριθμεί τις κατηγορίες που ΑΡΚΟΥΝ («C ή CE»)· ο οδηγός
 * καλύπτει την απαίτηση αν έχει οποιαδήποτε από αυτές — ή ανώτερή τους:
 * το ΓΕ προϋποθέτει Γ, το Δ1Ε προϋποθέτει Δ1. Η κάλυψη δηλώνεται ρητά
 * ανά κατηγορία, όχι με «μέγεθος» — οι αλυσίδες C και D δεν συγκρίνονται
 * μεταξύ τους.
 */
final class RequirementsMatcher
{
    /** Ποιες ΔΙΚΕΣ του κατηγορίες καλύπτουν την καθεμιά ζητούμενη. */
    private const COVERS = [
        'B' => ['B', 'BE', 'C1', 'C1E', 'C', 'CE', 'D1', 'D1E', 'D', 'DE'],
        'BE' => ['BE', 'C1E', 'CE', 'D1E', 'DE'],
        'C1' => ['C1', 'C1E', 'C', 'CE'],
        'C1E' => ['C1E', 'CE'],
        'C' => ['C', 'CE'],
        'CE' => ['CE'],
        'D1' => ['D1', 'D1E', 'D', 'DE'],
        'D1E' => ['D1E', 'DE'],
        'D' => ['D', 'DE'],
        'DE' => ['DE'],
    ];

    /** Βάρη ανά απαίτηση — τα «χαρτιά» βαραίνουν όσο και η γεωγραφία μαζί. */
    private const W_LICENSE = 25;
    private const W_PEI = 10;
    private const W_ADR = 10;
    private const W_TACHO = 8;
    private const W_OPERATOR = 12;
    private const W_EXPERIENCE = 10;
    private const W_DISTANCE = 25;

    /** @var array<int, array> προφίλ προσόντων ανά οδηγό — μία ανάγνωση ο καθένας */
    private array $driverCache = [];

    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Συγκρίνει μία αγγελία με έναν οδηγό.
     *
     * @param array $listing γραμμή του job_listings
     * @param array $driver  γραμμή του drivers (θέλει τουλάχιστον id,
     *                       latitude, longitude, preferred_radius)
     * @return array{percent:int, met:string[], missing:string[], distance_km:?float}
     */
    public function match(array $listing, array $driver): array
    {
        $quals = $this->driverQualifications((int) ($driver['id'] ?? 0));

        $score = 0.0;
        $max = 0.0;
        $met = [];
        $missing = [];

        // ── 1. Κατηγορίες διπλώματος ────────────────────────────────────
        $required = array_filter(array_map('trim', explode(',', (string) ($listing['required_license'] ?? ''))));
        if ($required) {
            $max += self::W_LICENSE;
            $covered = null;
            foreach ($required as $code) {
                foreach (self::COVERS[$code] ?? [$code] as $covering) {
                    if (in_array($covering, $quals['licenses'], true)) {
                        $covered = $code;
                        break 2;
                    }
                }
            }
            if ($covered !== null) {
                $score += self::W_LICENSE;
                $met[] = 'Δίπλωμα ' . $this->greek($covered);
            } else {
                $missing[] = 'Δίπλωμα ' . implode(' ή ', array_map([$this, 'greek'], $required));
            }
        }

        // ── 2. ΠΕΙ — του σωστού είδους για τη μεταφορά ──────────────────
        if (!empty($listing['requires_pei'])) {
            $max += self::W_PEI;
            $type = (string) ($listing['transport_type'] ?? '');
            $hasPei = match ($type) {
                'freight' => $quals['pei_freight'],
                'passenger' => $quals['pei_passenger'],
                default => $quals['pei_freight'] || $quals['pei_passenger'],
            };
            if ($hasPei) {
                $score += self::W_PEI;
                $met[] = 'ΠΕΙ';
            } else {
                $missing[] = $type === 'passenger' ? 'ΠΕΙ Επιβατών' : 'ΠΕΙ Εμπορευμάτων';
            }
        }

        // ── 3. ADR ──────────────────────────────────────────────────────
        if (!empty($listing['adr_certificate'])) {
            $max += self::W_ADR;
            if ($quals['adr']) {
                $score += self::W_ADR;
                $met[] = 'ADR';
            } else {
                $missing[] = 'Πιστοποιητικό ADR';
            }
        }

        // ── 4. Κάρτα ταχογράφου ─────────────────────────────────────────
        if (!empty($listing['requires_tachograph'])) {
            $max += self::W_TACHO;
            if ($quals['tachograph']) {
                $score += self::W_TACHO;
                $met[] = 'Κάρτα ταχογράφου';
            } else {
                $missing[] = 'Κάρτα ψηφιακού ταχογράφου';
            }
        }

        // ── 5. Άδεια χειριστή + ειδικότητες ─────────────────────────────
        if (!empty($listing['operator_license'])) {
            $max += self::W_OPERATOR;

            // Η αγγελία μιλά σε μηχανήματα· το βιβλιάριο σε ειδικότητες.
            $wantedSpecs = [];
            foreach (array_filter(array_map('trim', explode(',', (string) ($listing['machinery_types'] ?? '')))) as $machine) {
                $spec = OperatorSpecialities::specialityForMachine($machine);
                if ($spec !== null && !in_array($spec, $wantedSpecs, true)) {
                    $wantedSpecs[] = $spec;
                }
            }

            if (!$quals['operator_specs']) {
                $missing[] = 'Άδεια χειριστή μηχανημάτων έργου';
            } elseif ($wantedSpecs && !array_intersect($wantedSpecs, $quals['operator_specs'])) {
                // Έχει βιβλιάριο, όχι όμως τη ζητούμενη ειδικότητα: μισές
                // μονάδες — είναι κοντύτερα από τον μη χειριστή.
                $score += self::W_OPERATOR / 2;
                $specNames = array_map(
                    static fn($s) => mb_substr(OperatorSpecialities::SPECIALITIES[$s] ?? ('ειδικότητα ' . $s), 0, 30),
                    $wantedSpecs
                );
                $missing[] = 'Ειδικότητα χειριστή: ' . implode(' ή ', $specNames);
            } else {
                $score += self::W_OPERATOR;
                $met[] = 'Άδεια χειριστή';
            }
        }

        // ── 6. Εμπειρία — αναλογικά, όχι όλα ή τίποτα ───────────────────
        $wantYears = (int) ($listing['experience_years'] ?? 0);
        if ($wantYears > 0) {
            $max += self::W_EXPERIENCE;
            $hasYears = $quals['experience_years'];
            if ($hasYears >= $wantYears) {
                $score += self::W_EXPERIENCE;
                $met[] = 'Εμπειρία ' . $wantYears . '+ έτη';
            } else {
                // Τα 2 από 3 έτη δεν είναι μηδέν — είναι τα δύο τρίτα.
                $score += self::W_EXPERIENCE * ($hasYears / $wantYears);
                $missing[] = 'Εμπειρία: έχει ' . $hasYears . ' από ' . $wantYears . ' έτη';
            }
        }

        // ── 7. Απόσταση — πάντα στον παρονομαστή (κανόνας 31/08) ────────
        $max += self::W_DISTANCE;
        $distance = null;

        $hasDriverCoords = !empty($driver['latitude']) && !empty($driver['longitude']);
        $hasListingCoords = !empty($listing['latitude']) && !empty($listing['longitude']);

        if ($hasDriverCoords && $hasListingCoords) {
            $distance = $this->haversine(
                (float) $driver['latitude'],
                (float) $driver['longitude'],
                (float) $listing['latitude'],
                (float) $listing['longitude']
            );

            $radius = (int) ($driver['preferred_radius'] ?? 0) ?: 50;

            if ($radius >= 9999 || !empty($driver['willing_to_relocate'])) {
                $score += self::W_DISTANCE;
            } elseif ($distance <= $radius) {
                $score += self::W_DISTANCE * (1 - ($distance / $radius) * 0.5);
            } elseif ($distance <= $radius * 2) {
                // Φθίνουσα ως το διπλάσιο της ακτίνας, όχι απότομο μηδέν.
                $score += (self::W_DISTANCE / 2) * (1 - (($distance - $radius) / $radius));
                $missing[] = 'Απόσταση ' . round($distance) . ' χλμ (ακτίνα ' . $radius . ')';
            } else {
                $missing[] = 'Απόσταση ' . round($distance) . ' χλμ';
            }
        }
        // Χωρίς συντεταγμένες: 0 από τα 25 — η άγνωστη απόσταση δεν
        // βαθμολογείται σαν κοντινή.

        return [
            'percent' => $max > 0 ? (int) round(($score / $max) * 100) : 0,
            'met' => $met,
            'missing' => $missing,
            'distance_km' => $distance !== null ? round($distance, 1) : null,
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    //  ΤΑ ΠΡΟΣΟΝΤΑ ΤΟΥ ΟΔΗΓΟΥ — ΜΙΑ ΑΝΑΓΝΩΣΗ, ΜΕ ΕΛΕΓΧΟ ΙΣΧΥΟΣ
    // ══════════════════════════════════════════════════════════════════

    /**
     * ΓΙΑΤΙ ΔΕΝ ΑΡΚΟΥΝ ΟΙ ΣΗΜΑΙΕΣ ΤΟΥ ΠΙΝΑΚΑ drivers: το παλιό ταίριασμα
     * διάβαζε drivers.adr_certificate — ένα tinyint που γράφτηκε κάποτε
     * στην εγγραφή και δεν ξέρει από λήξεις. Εδώ διαβάζονται οι πηγές
     * (driver_licenses, adr_certificates, tachograph_cards,
     * operator_licenses) και ό,τι ΕΧΕΙ ΛΗΞΕΙ ΔΕΝ ΜΕΤΡΑΕΙ: για την
     * αγγελία, ADR που έληξε είναι ADR που δεν υπάρχει.
     */
    private function driverQualifications(int $driverId): array
    {
        if (isset($this->driverCache[$driverId])) {
            return $this->driverCache[$driverId];
        }

        $q = [
            'licenses' => [],
            'pei_freight' => false,
            'pei_passenger' => false,
            'adr' => false,
            'tachograph' => false,
            'operator_specs' => [],
            'experience_years' => 0,
        ];

        try {
            $stmt = $this->pdo->prepare(
                'SELECT license_type, expiry_date, pei_expiry_c, pei_expiry_d
                 FROM driver_licenses WHERE driver_id = ?'
            );
            $stmt->execute([$driverId]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $l) {
                $type = strtoupper(trim((string) $l['license_type']));
                if ($type !== '' && (empty($l['expiry_date']) || strtotime($l['expiry_date']) >= time())) {
                    $q['licenses'][] = $type;
                }
                if (!empty($l['pei_expiry_c']) && strtotime($l['pei_expiry_c']) >= time()) {
                    $q['pei_freight'] = true;
                }
                if (!empty($l['pei_expiry_d']) && strtotime($l['pei_expiry_d']) >= time()) {
                    $q['pei_passenger'] = true;
                }
            }

            $adr = $this->pdo->prepare(
                'SELECT COUNT(*) FROM driver_adr_certificates
                 WHERE driver_id = ? AND (expiry_date IS NULL OR expiry_date >= CURDATE())'
            );
            $adr->execute([$driverId]);
            $q['adr'] = (int) $adr->fetchColumn() > 0;

            $tacho = $this->pdo->prepare(
                'SELECT COUNT(*) FROM driver_tachograph_cards
                 WHERE driver_id = ? AND (expiry_date IS NULL OR expiry_date >= CURDATE())'
            );
            $tacho->execute([$driverId]);
            $q['tachograph'] = (int) $tacho->fetchColumn() > 0;

            $ops = $this->pdo->prepare(
                'SELECT speciality FROM driver_operator_licenses
                 WHERE driver_id = ? AND (expiry_date IS NULL OR expiry_date >= CURDATE())'
            );
            $ops->execute([$driverId]);
            $q['operator_specs'] = array_values(array_unique(array_map(
                'strval',
                $ops->fetchAll(PDO::FETCH_COLUMN) ?: []
            )));

            // Εμπειρία: το άθροισμα της προϋπηρεσίας αν υπάρχει, αλλιώς η
            // δηλωμένη στήλη — ό,τι πιο συγκεκριμένο διαθέτουμε.
            $exp = $this->pdo->prepare(
                'SELECT COALESCE(SUM(years), 0) FROM driver_vehicle_experience WHERE driver_id = ?'
            );
            $exp->execute([$driverId]);
            $years = (float) $exp->fetchColumn();
            if ($years <= 0) {
                $col = $this->pdo->prepare('SELECT COALESCE(experience_years, 0) FROM drivers WHERE id = ?');
                $col->execute([$driverId]);
                $years = (float) $col->fetchColumn();
            }
            $q['experience_years'] = (int) floor($years);
        } catch (Throwable $e) {
            // Ελλιπές σχήμα δεν ρίχνει το ταίριασμα — απλώς δεν πιστώνει.
        }

        return $this->driverCache[$driverId] = $q;
    }

    /** Κωδικός → ελληνική ονομασία κατηγορίας για τα μηνύματα. */
    private function greek(string $code): string
    {
        return strtr($code, ['A' => 'Α', 'B' => 'Β', 'C' => 'Γ', 'D' => 'Δ', 'E' => 'Ε', 'M' => 'Μ']);
    }

    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
