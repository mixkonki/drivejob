<?php

namespace Drivejob\Services\Score;

/**
 * Το αποτέλεσμα — ΤΡΕΙΣ αριθμοί που δεν συγχέονται. (01/09/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ ΟΧΙ ΕΝΑΣ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Ένας συγκεντρωτικός αριθμός 0-100 ανακατεύει πράγματα που ο εργοδότης
 * σταθμίζει διαφορετικά και ο οδηγός ελέγχει διαφορετικά:
 *
 *   ΠΛΗΡΟΤΗΤΑ    πόσο συμπληρωμένο είναι το προφίλ.
 *                Το ελέγχει 100% ο οδηγός — και σωστά.
 *   ΠΡΟΣΟΝΤΑ     τι χαρτιά κρατά, με ισχύ.
 *                Το ελέγχει με το να πάει στο ΚΤΕΟ και στη σχολή.
 *   ΦΗΜΗ         τι λένε οι άλλοι και τι δείχνουν οι μετρήσεις.
 *                ΔΕΝ το ελέγχει. Γι' αυτό είναι και το μόνο που πείθει.
 *
 * **Ο συνολικός αριθμός είναι `null` όσο δεν υπάρχει φήμη.** Δεν είναι
 * παράλειψη — είναι ο κανόνας. Το «52» που έβγαζε το παλιό σύστημα από
 * προεπιλεγμένες τιμές ήταν χειρότερο από κανέναν αριθμό: έδειχνε
 * βεβαιότητα εκεί που δεν υπήρχε ούτε ένα δεδομένο.
 */
final class DriverScore
{
    /**
     * @param Contribution[] $contributions όλα, και όσα δεν μετράνε
     * @param array<string, array> $sources κατάσταση κάθε πηγής του μητρώου
     */
    public function __construct(
        public readonly int $driverId,
        public readonly float $credentials,
        public readonly ?float $reputation,
        public readonly ?float $total,
        public readonly float $confidence,
        public readonly array $contributions = [],
        public readonly array $sources = [],
    ) {
    }

    /** Υπάρχει έστω μία μαρτυρία τρίτου ή μέτρηση; */
    public function hasThirdParty(): bool
    {
        return $this->reputation !== null;
    }

    /** @return Contribution[] */
    public function inGroup(string $group): array
    {
        return array_values(array_filter(
            $this->contributions,
            static fn(Contribution $c) => $c->group() === $group
        ));
    }

    /**
     * Οι πηγές που λείπουν και ΜΠΟΡΕΙ να αποκτήσει ο οδηγός.
     *
     * @return array<int, array{key:string,label:string,hint:string,evidence:string}>
     */
    public function missing(): array
    {
        $out = [];
        foreach ($this->sources as $key => $s) {
            if ($s['active'] && !$s['has_data'] && $s['hint'] !== '' && $s['weight'] > 0) {
                $out[] = [
                    'key' => $key,
                    'label' => $s['label'],
                    'hint' => $s['hint'],
                    'evidence' => $s['evidence'],
                ];
            }
        }
        return $out;
    }

    /** Ετικέτα επιπέδου — λέξεις, όχι δεκαδικά. */
    public function label(): string
    {
        if ($this->total === null) {
            return 'Χωρίς αξιολόγηση ακόμη';
        }
        return match (true) {
            $this->total >= 80 => 'Εξαιρετικό',
            $this->total >= 65 => 'Πολύ καλό',
            $this->total >= 50 => 'Καλό',
            $this->total >= 35 => 'Μέτριο',
            default => 'Χρειάζεται βελτίωση',
        };
    }

    public function toArray(): array
    {
        return [
            'driver_id' => $this->driverId,
            'credentials' => round($this->credentials, 1),
            'reputation' => $this->reputation === null ? null : round($this->reputation, 1),
            'total' => $this->total === null ? null : round($this->total, 1),
            'confidence' => round($this->confidence, 1),
            'has_third_party' => $this->hasThirdParty(),
            'label' => $this->label(),
            'contributions' => array_map(static fn(Contribution $c) => $c->toArray(), $this->contributions),
            'sources' => $this->sources,
        ];
    }
}
