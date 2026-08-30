<?php

namespace Drivejob\Services\Score\Collectors;

use PDO;
use Drivejob\Services\Score\Collector;
use Drivejob\Services\Score\Contribution;

/**
 * Προϋπηρεσία — ΔΗΛΩΜΕΝΗ, άρα ΜΗΔΕΝ μονάδες. (01/09/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ ΔΕΝ ΜΕΤΡΑΕΙ, ΕΝΩ ΕΙΝΑΙ ΤΟ ΠΡΩΤΟ ΠΟΥ ΚΟΙΤΑΖΕΙ Ο ΕΡΓΟΔΟΤΗΣ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Ο παλιός υπολογισμός έδινε έως 25 μονάδες για τα έτη εμπειρίας. Τα έτη
 * όμως τα γράφει ο ίδιος ο οδηγός, σε ελεύθερη φόρμα, χωρίς κανέναν
 * έλεγχο. Είναι το ίδιο ακριβώς με το να βαθμολογείς κάποιον με βάση
 * αυτό που δήλωσε ότι αξίζει.
 *
 * Η προϋπηρεσία **εμφανίζεται πλήρως** — στο βιογραφικό, στο προφίλ,
 * στην επισκόπηση. Απλώς δεν παριστάνει ότι είναι επαληθευμένη.
 *
 * ΚΑΙ ΕΧΕΙ ΔΡΟΜΟ: μόλις ένας εργοδότης επιβεβαιώσει την περίοδο
 * απασχόλησης (πεδία `employment_from`/`employment_to` στο
 * `driver_reviews`), η ίδια προϋπηρεσία γίνεται ATTESTED και αρχίζει να
 * μετράει. Έτσι ο κανόνας δεν είναι τιμωρία — είναι κίνητρο να ζητήσει
 * ο οδηγός τη βεβαίωση.
 */
final class ExperienceCollector implements Collector
{
    public function source(): string
    {
        return 'experience';
    }

    public function collect(array $profile, PDO $pdo): array
    {
        $rows = $profile['vehicle_experience'] ?? [];
        if (!$rows) {
            return [];
        }

        $months = 0;
        foreach ($rows as $e) {
            $months += (int) round(((float) ($e['years'] ?? 0)) * 12);
        }
        if ($months <= 0) {
            return [];
        }

        $years = intdiv($months, 12);
        $rest = $months % 12;
        $label = $years > 0
            ? $years . ' έτ' . ($years === 1 ? 'ος' : 'η') . ($rest ? ' και ' . $rest . ' μήνες' : '')
            : $rest . ' μήνες';

        return [new Contribution(
            source: $this->source(),
            label: 'Δηλωμένη προϋπηρεσία: ' . $label,
            points: 0,
            maxPoints: 0,
            detail: 'Δεν μετράει στη βαθμολογία όσο δεν την έχει βεβαιώσει εργοδότης.',
        )];
    }
}
