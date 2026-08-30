<?php

namespace Drivejob\Services\Score;

/**
 * Μία συνεισφορά στη βαθμολογία — ένα γεγονός με πηγή, μονάδες και αιτία.
 * (01/09/2026)
 *
 * ΓΙΑΤΙ ΑΝΤΙΚΕΙΜΕΝΟ ΚΑΙ ΟΧΙ ΣΚΕΤΟΣ ΑΡΙΘΜΟΣ: το προηγούμενο σύστημα
 * επέστρεφε τέσσερις αριθμούς και τίποτα άλλο. Όταν ο οδηγός ρωτούσε
 * «γιατί 52;» δεν υπήρχε απάντηση πουθενά — ούτε στον κώδικα, ούτε στη
 * βάση, ούτε στην οθόνη. Κάθε μονάδα εδώ κουβαλά το «γιατί» μαζί της,
 * και το `driver_score_breakdown` το αποθηκεύει.
 *
 * Ένας αριθμός που δεν μπορείς να εξηγήσεις δεν είναι αξιολόγηση, είναι
 * μαντεψιά με δεκαδικά.
 */
final class Contribution
{
    public function __construct(
        /** Κλειδί πηγής από το ScoreSource::ALL */
        public readonly string $source,
        /** Τι ακριβώς είναι («Κατηγορία CE», «Αξιολόγηση: Μεταφορική ΑΕ») */
        public readonly string $label,
        /** Μονάδες — αρνητικές για ποινή */
        public readonly float $points,
        /** Πόσες μονάδες θα έδινε στο μέγιστο (για ποσοστά προόδου) */
        public readonly float $maxPoints = 0.0,
        /** Δεύτερη γραμμή: λεπτομέρεια που εξηγεί τις μονάδες */
        public readonly string $detail = '',
        /** Πότε συνέβη — για μελλοντική απόσβεση παλιών σημάτων */
        public readonly ?string $occurredAt = null,
        /** Πότε λήγει το τεκμήριο (άδεια, πιστοποιητικό) */
        public readonly ?string $expiresAt = null,
    ) {
    }

    public function evidence(): string
    {
        return ScoreSource::ALL[$this->source]['evidence'] ?? ScoreSource::DECLARED;
    }

    public function group(): string
    {
        return ScoreSource::ALL[$this->source]['group'] ?? ScoreSource::GROUP_GUIDANCE;
    }

    /** Μετράει στον αριθμό ή είναι μόνο για εμφάνιση; */
    public function counts(): bool
    {
        return ScoreSource::counts($this->evidence());
    }

    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'evidence' => $this->evidence(),
            'group' => $this->group(),
            'label' => $this->label,
            'detail' => $this->detail,
            'points' => round($this->points, 2),
            'max_points' => round($this->maxPoints, 2),
            'counts' => $this->counts(),
            'occurred_at' => $this->occurredAt,
            'expires_at' => $this->expiresAt,
        ];
    }
}
