<?php

namespace Drivejob\Services\Driver;

use Drivejob\Helpers\OperatorSpecialities;
use Drivejob\Helpers\SpecialLicenseTypes;
use Drivejob\Helpers\CertificationCategories;
use Drivejob\Helpers\VehicleExperienceTypes;
use Drivejob\Helpers\DriverSkills;

/**
 * ΤΟ ΒΙΟΓΡΑΦΙΚΟ ΤΟΥ ΟΔΗΓΟΥ — μία δομή, πολλές όψεις (30/08/2026).
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ ΥΠΑΡΧΕΙ ΑΥΤΗ Η ΚΛΑΣΗ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Η καρτέλα Επισκόπηση και το PDF βιογραφικό δείχνουν ΤΑ ΙΔΙΑ πράγματα.
 * Αν γραφτούν χωριστά, αποκλίνουν μέσα σε δύο μήνες: προστίθεται πεδίο
 * στο ένα και ξεχνιέται στο άλλο, και ο οδηγός βλέπει στην οθόνη κάτι
 * διαφορετικό από αυτό που στέλνει στον εργοδότη.
 *
 * Είναι ακριβώς το λάθος που είχαμε με τις ταξινομίες σε PHP σταθερές,
 * πριν τον πίνακα lookup_values: η ίδια πληροφορία σε δύο μέρη.
 *
 * ΕΔΩ γίνεται ΟΛΗ η μετάφραση κωδικών σε ανθρώπινο κείμενο (freight →
 * «Εμπορευματικές», own_business → «Ελεύθερος επαγγελματίας», 1.3 →
 * «Σύνθετα εκσκαπτικά-φορτωτικά»). Οι όψεις δέχονται έτοιμα κείμενα και
 * απλώς τα τυπώνουν — μια view δεν κάνει ποτέ λογική.
 *
 * ══════════════════════════════════════════════════════════════════════
 *  Η ΔΟΜΗ (η σειρά ΕΙΝΑΙ η σειρά του βιογραφικού)
 * ══════════════════════════════════════════════════════════════════════
 *
 *   identity      → ταυτότητα, επικοινωνία, διαθεσιμότητα, ακτίνα
 *   alerts        → τι λήγει σύντομα (μόνο υπό συνθήκη)
 *   qualifications→ τυπικά προσόντα (τις ομάδες τις χτίζει η όψη)
 *   experience    → προϋπηρεσία, ταξινομημένη, με σύνολα
 *   certifications→ σεμινάρια & πιστοποιητικά
 *   languages     → γλώσσες με επίπεδο
 *   skills        → δεξιότητες ανά ομάδα
 *
 * ΟΨΕΙΣ: η ιδιωτική (ο ίδιος ο οδηγός) βλέπει και τα «τι λείπει»· η
 * δημόσια/PDF όχι. Γι' αυτό το `forDriver()` δέχεται $includePrivate.
 */
class DriverCvService
{
    /** Πόσο μπροστά κοιτάμε για λήξεις στην επισκόπηση. */
    public const ALERT_WINDOW_DAYS = 90;

    private ProfileCompletenessService $completeness;

    public function __construct(?ProfileCompletenessService $completeness = null)
    {
        $this->completeness = $completeness ?? new ProfileCompletenessService();
    }

    /**
     * @param array $profile Το πλήρες προφίλ (DriverProfileService::getDriverProfile)
     * @param bool  $includePrivate Ιδιωτική όψη: προσθέτει «τι λείπει»
     */
    public function build(array $profile, bool $includePrivate = true): array
    {
        $cv = [
            'identity' => $this->identity($profile),
            'alerts' => $this->alerts($profile),
            'experience' => $this->experience($profile['vehicle_experience'] ?? []),
            'certifications' => $this->certifications($profile['certifications'] ?? []),
            'languages' => $this->languages($profile['languages_list'] ?? []),
            'skills' => $this->skills($profile['skills'] ?? []),
        ];

        if ($includePrivate) {
            $cv['completeness'] = $this->completeness->calculate($profile);
        }

        return $cv;
    }

    // ══════════════════════════════════════════════════════════════════
    //  ΤΑΥΤΟΤΗΤΑ
    // ══════════════════════════════════════════════════════════════════

    private function identity(array $p): array
    {
        $city = trim((string) ($p['city'] ?? ''));
        $country = trim((string) ($p['country'] ?? ''));

        return [
            'full_name' => trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '')),
            'photo' => $p['profile_image'] ?? null,
            'city' => $city,
            'location' => $city !== '' ? trim($city . ($country !== '' ? ', ' . $country : '')) : '',
            'email' => $p['email'] ?? null,
            'phone' => $p['phone'] ?? null,
            'landline' => $p['landline'] ?? null,
            'linkedin' => $p['social_linkedin'] ?? null,
            'available' => !empty($p['available_for_work']),
            'reach' => $this->reach($p),
            'rating' => [
                'value' => (float) ($p['rating'] ?? 0),
                'count' => (int) ($p['rating_count'] ?? 0),
            ],
        ];
    }

    /**
     * ΠΟΥ δέχεται να εργαστεί ο οδηγός.
     *
     * Το πεδίο `preferred_radius` υπάρχει στη βάση από την αρχή και το
     * διαβάζει ΤΟ ΤΑΙΡΙΑΣΜΑ (MatchingModel) — αλλά δεν το συμπλήρωνε
     * ποτέ κανείς, γιατί δεν υπήρχε πουθενά στη φόρμα. Έμενε 0, το
     * ταίριασμα έπεφτε σε προεπιλογή 50 χλμ, και ο οδηγός Θεσσαλονίκης
     * έβλεπε αγγελίες Αθηνών. Τώρα δηλώνεται και φαίνεται.
     */
    private function reach(array $p): array
    {
        $radius = (int) ($p['preferred_radius'] ?? 0);
        $relocate = !empty($p['willing_to_relocate']);
        $travel = !empty($p['willing_to_travel']);

        if ($relocate) {
            $label = 'Όλη την Ελλάδα — δέχομαι μετεγκατάσταση';
        } elseif ($radius > 0) {
            $label = 'Έως ' . $radius . ' χλμ από ' . ($p['city'] ?: 'την έδρα μου');
        } else {
            $label = '';   // αδήλωτο: το λέει η όψη, δεν το μαντεύουμε εδώ
        }

        return [
            'radius' => $radius,
            'relocate' => $relocate,
            'travel' => $travel,
            'label' => $label,
            'declared' => $radius > 0 || $relocate,
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    //  ΕΙΔΟΠΟΙΗΣΕΙΣ ΛΗΞΕΩΝ
    // ══════════════════════════════════════════════════════════════════

    /**
     * Ό,τι λήγει μέσα στο παράθυρο, ταξινομημένο κατά ΕΠΕΙΓΟΝ.
     *
     * Ό,τι έχει ΗΔΗ λήξει μπαίνει πρώτο: δεν είναι υπενθύμιση, είναι
     * πρόβλημα που ήδη κοστίζει δουλειές.
     */
    private function alerts(array $p): array
    {
        $items = [];
        $now = time();
        $limit = $now + self::ALERT_WINDOW_DAYS * 86400;

        $add = function (?string $date, string $label, string $action = '', string $url = '') use (&$items, $now, $limit) {
            if (empty($date)) {
                return;
            }
            $ts = strtotime($date);
            if ($ts === false || $ts > $limit) {
                return;
            }
            $items[] = [
                'label' => $label,
                'date' => date('d/m/Y', $ts),
                'ts' => $ts,
                'expired' => $ts < $now,
                'days' => (int) floor(($ts - $now) / 86400),
                'action' => $action,
                'url' => $url,
            ];
        };

        $city = $p['city'] ?? null;

        // Δίπλωμα: έντυπο και κατηγορίες λήγουν χωριστά
        $add(
            $p['license_document_expiry'] ?? null,
            'Άδεια οδήγησης (έντυπο)',
            'Ανανέωση από gov.gr',
            'https://www.gov.gr/ipiresies/polites-kai-kathemerinoteta/diploma-odegeses/ananeose-adeias-odegeses'
        );

        foreach (($p['licenses'] ?? []) as $lic) {
            $type = $lic['license_type'] ?? '';
            $add(
                $lic['expiry_date'] ?? null,
                'Κατηγορία ' . $type,
                'Ανανέωση από gov.gr',
                'https://www.gov.gr/ipiresies/polites-kai-kathemerinoteta/diploma-odegeses/ananeose-adeias-odegeses'
            );

            /*
             * Το ΠΕΙ ΔΕΝ μπαίνει εδώ: οι στήλες pei_expiry_c/d
             * επαναλαμβάνονται σε ΚΑΘΕ γραμμή κατηγορίας του διπλώματος.
             * Ένας οδηγός με C, CE, D, DE έπαιρνε τέσσερις φορές την ίδια
             * ειδοποίηση. Μαζεύονται παρακάτω, μία φορά η καθεμία.
             */
            if (!empty($lic['pei_expiry_c'])) {
                $peiC = $peiC ?? $lic['pei_expiry_c'];
            }
            if (!empty($lic['pei_expiry_d'])) {
                $peiD = $peiD ?? $lic['pei_expiry_d'];
            }
        }

        $add($peiC ?? null, 'ΠΕΙ Εμπορευμάτων', 'Σχολές ΠΕΙ', $this->schoolSearch('ΠΕΙ', $city));
        $add($peiD ?? null, 'ΠΕΙ Επιβατών', 'Σχολές ΠΕΙ', $this->schoolSearch('ΠΕΙ', $city));

        $adr = $p['adr_certificates'][0] ?? null;
        if ($adr) {
            $add($adr['expiry_date'] ?? null, 'Πιστοποιητικό ADR', 'Σχολές ADR', $this->schoolSearch('ADR', $city));
        }

        $tacho = $p['tachograph_cards'][0] ?? null;
        if ($tacho) {
            $add(
                $tacho['expiry_date'] ?? null,
                'Κάρτα ψηφιακού ταχογράφου',
                'Ανανέωση από gov.gr',
                'https://www.gov.gr/ipiresies/polites-kai-kathemerinoteta/metakineseis/karta-psephiakou-takhographou'
            );
        }

        $op = $p['operator_licenses'][0] ?? null;
        if ($op) {
            $add($op['expiry_date'] ?? null, 'Θεώρηση άδειας χειριστή', '', '');
        }

        foreach (($p['special_licenses'] ?? []) as $sl) {
            // Κενή ημερομηνία = «χωρίς λήξη», ρητή επιλογή — δεν ειδοποιούμε.
            $add($sl['expiry_date'] ?? null, SpecialLicenseTypes::label((string) $sl['license_type']), '', '');
        }

        foreach (($p['certifications'] ?? []) as $c) {
            $add($c['expiry'] ?? null, $c['title'] ?? 'Πιστοποιητικό', '', '');
        }

        usort($items, static fn($a, $b) => $a['ts'] <=> $b['ts']);

        return $items;
    }

    private function schoolSearch(string $what, ?string $city): string
    {
        return 'https://www.google.com/search?q=' . urlencode($what . ' σχολή ' . ($city ?: 'Ελλάδα'));
    }

    // ══════════════════════════════════════════════════════════════════
    //  ΠΡΟΫΠΗΡΕΣΙΑ
    // ══════════════════════════════════════════════════════════════════

    /**
     * Η προϋπηρεσία ήταν ΤΟ ΜΕΓΑΛΟ ΚΕΝΟ της επισκόπησης: εμφανιζόταν
     * μόνο ως «7 έτη εμπειρίας» δίπλα στο όνομα. Ποια οχήματα; Πόσο;
     * Ένα βιογραφικό οδηγού χωρίς αυτό δεν είναι βιογραφικό.
     *
     * Ταξινόμηση: οι ΤΡΕΧΟΥΣΕΣ θέσεις πρώτα (χωρίς end_date), μετά κατά
     * φθίνουσα διάρκεια — έτσι διαβάζεται πρώτο ό,τι μετράει περισσότερο.
     */
    private function experience(array $rows): array
    {
        $items = [];
        $totalMonths = 0;

        foreach ($rows as $r) {
            $years = (int) ($r['years'] ?? 0);
            $months = (int) ($r['months'] ?? 0);
            $months += $years * 12;
            $totalMonths += $months;

            $category = (string) ($r['vehicle_category'] ?? '');
            $type = (string) ($r['vehicle_type'] ?? '');
            $current = empty($r['end_date']);

            $items[] = [
                'category' => $category,
                'category_label' => VehicleExperienceTypes::categoryLabel($category),
                'type_label' => $type !== '' ? VehicleExperienceTypes::typeLabel($category, $type) : '',
                'transport_label' => VehicleExperienceTypes::transportLabel($r['transport_type'] ?? null),
                'employment_label' => VehicleExperienceTypes::EMPLOYMENT_LABELS[$r['employment_type'] ?? ''] ?? '',
                'duration_label' => $this->durationLabel($months),
                'months' => $months,
                'period_label' => $this->periodLabel($r['start_date'] ?? null, $r['end_date'] ?? null),
                'current' => $current,
                'description' => trim((string) ($r['description'] ?? '')),
            ];
        }

        usort($items, static function ($a, $b) {
            if ($a['current'] !== $b['current']) {
                return $b['current'] <=> $a['current'];
            }
            return $b['months'] <=> $a['months'];
        });

        return [
            'items' => $items,
            'total_months' => $totalMonths,
            'total_label' => $this->durationLabel($totalMonths),
            'count' => count($items),
        ];
    }

    /** «7 έτη 5 μήνες», «11 μήνες», «—». */
    private function durationLabel(int $months): string
    {
        if ($months <= 0) {
            return '—';
        }
        $y = intdiv($months, 12);
        $m = $months % 12;

        $parts = [];
        if ($y > 0) {
            $parts[] = $y . ($y === 1 ? ' έτος' : ' έτη');
        }
        if ($m > 0) {
            $parts[] = $m . ($m === 1 ? ' μήνας' : ' μήνες');
        }

        return implode(' ', $parts);
    }

    /** «03/2019 — σήμερα» ή «03/2019 — 08/2024». */
    private function periodLabel(?string $start, ?string $end): string
    {
        if (empty($start)) {
            return '';
        }
        $s = strtotime($start);
        if ($s === false) {
            return '';
        }
        $out = date('m/Y', $s);
        if (empty($end)) {
            return $out . ' — σήμερα';
        }
        $e = strtotime($end);
        return $out . ' — ' . ($e ? date('m/Y', $e) : '');
    }

    // ══════════════════════════════════════════════════════════════════
    //  ΣΕΜΙΝΑΡΙΑ & ΠΙΣΤΟΠΟΙΗΤΙΚΑ
    // ══════════════════════════════════════════════════════════════════

    private function certifications(array $rows): array
    {
        $items = [];
        $now = time();

        foreach ($rows as $c) {
            $expiry = $c['expiry'] ?? null;
            $ts = $expiry ? strtotime($expiry) : null;
            $date = $c['date'] ?? null;

            $items[] = [
                'title' => (string) ($c['title'] ?? ''),
                'provider' => (string) ($c['provider'] ?? ''),
                'category_label' => !empty($c['category']) ? CertificationCategories::label((string) $c['category']) : '',
                'transport_label' => CertificationCategories::transportLabel($c['transport_type'] ?? 'both'),
                'date_label' => $date && strtotime($date) ? date('m/Y', strtotime($date)) : '',
                'date_sort' => $date && strtotime($date) ? strtotime($date) : 0,
                'expiry_label' => $ts ? date('d/m/Y', $ts) : '',
                'expired' => $ts !== null && $ts < $now,
                'duration' => (int) ($c['duration'] ?? 0),
                'file' => $c['certificate_file'] ?? null,
            ];
        }

        // Τα πιο πρόσφατα πρώτα: αυτά δείχνουν τι κάνει ΤΩΡΑ ο οδηγός.
        usort($items, static fn($a, $b) => $b['date_sort'] <=> $a['date_sort']);

        return ['items' => $items, 'count' => count($items)];
    }

    // ══════════════════════════════════════════════════════════════════
    //  ΓΛΩΣΣΕΣ
    // ══════════════════════════════════════════════════════════════════

    /*
     * ΤΑ ΤΕΣΣΕΡΑ ΕΠΙΠΕΔΑ ΤΗΣ ΦΟΡΜΑΣ — και μόνο αυτά.
     *
     * Πρώτη γραφή είχε 'advanced'/'intermediate' από μνήμη· η φόρμα
     * (skills.php) γράφει basic/good/fluent/native. Αποτέλεσμα στο PDF:
     * «Βουλγαρικά (good)» — ο κωδικός τυπωμένος ωμός. Τα επίπεδα
     * αντιγράφονται ΑΠΟ τη φόρμα, δεν εικάζονται.
     */
    private const LANGUAGE_LEVELS = [
        'native' => 'Μητρική',
        'fluent' => 'Άριστα',
        'good' => 'Καλά',
        'basic' => 'Βασικά',
    ];

    /** Σειρά ισχύος: η μητρική πρώτη, τα βασικά τελευταία. */
    private const LEVEL_RANK = ['native' => 4, 'fluent' => 3, 'good' => 2, 'basic' => 1];

    private function languages(array $rows): array
    {
        $items = [];
        foreach ($rows as $l) {
            $level = (string) ($l['level'] ?? '');
            $items[] = [
                'name' => (string) ($l['language_name'] ?? ''),
                'level' => $level,
                'level_label' => self::LANGUAGE_LEVELS[$level] ?? $level,
                'rank' => self::LEVEL_RANK[$level] ?? 0,
            ];
        }

        usort($items, static fn($a, $b) => $b['rank'] <=> $a['rank']);

        return $items;
    }

    // ══════════════════════════════════════════════════════════════════
    //  ΔΕΞΙΟΤΗΤΕΣ
    // ══════════════════════════════════════════════════════════════════

    /**
     * Μόνο όσες έχει δηλώσει, ομαδοποιημένες όπως στη φόρμα. Ένας
     * κατάλογος 30 δεξιοτήτων με τσεκ και χ δεν διαβάζεται· μια λίστα
     * με 8 πράγματα που ΞΕΡΕΙ ο οδηγός, ναι.
     */
    private function skills(array $flags): array
    {
        $groups = [];

        foreach (DriverSkills::GROUPS as $groupLabel => $codes) {
            $found = [];
            foreach ($codes as $code) {
                if (!empty($flags[$code])) {
                    $found[] = DriverSkills::LABELS[$code] ?? $code;
                }
            }
            if ($found) {
                $groups[] = ['label' => $groupLabel, 'items' => $found];
            }
        }

        $total = 0;
        foreach ($groups as $g) {
            $total += count($g['items']);
        }

        return ['groups' => $groups, 'count' => $total];
    }
}
