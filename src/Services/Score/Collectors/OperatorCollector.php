<?php

namespace Drivejob\Services\Score\Collectors;

use PDO;
use Drivejob\Helpers\OperatorSpecialities;
use Drivejob\Services\Score\Collector;
use Drivejob\Services\Score\Contribution;

/**
 * Άδεια χειριστή μηχανημάτων έργου. Μέγιστο 30. (01/09/2026)
 *
 * Η ΘΕΩΡΗΣΗ ΕΙΝΑΙ ΚΟΙΝΗ για όλο το βιβλιάριο — μία ημερομηνία, όχι μία
 * ανά ειδικότητα (ίδια παραδοχή με τον DriverCvService, ώστε η
 * βαθμολογία και το βιογραφικό να μη διαφωνούν ποτέ).
 *
 * ΓΙΑΤΙ ΟΙ ΕΙΔΙΚΟΤΗΤΕΣ ΔΕΝ ΑΘΡΟΙΖΟΝΤΑΙ ΓΡΑΜΜΙΚΑ: η δεύτερη ειδικότητα
 * ανοίγει σαφώς νέα αγορά, η πέμπτη πολύ λιγότερο. Φθίνουσα κλίμακα
 * (8, 6, 5, 4, 3) αντί για σταθερό +5 που θα έδινε 25 μονάδες σε όποιον
 * καταχώρησε πέντε γραμμές.
 */
final class OperatorCollector implements Collector
{
    private const BOOK_POINTS = 8;
    /** Μονάδες ανά ειδικότητα, με σειρά καταχώρησης — φθίνουσες. */
    private const SPEC_POINTS = [8, 6, 5, 4, 3];
    private const COVERS_ALL_BONUS = 1;
    private const EXPIRED_FACTOR = 0.30;

    public function source(): string
    {
        return 'operator';
    }

    public function collect(array $profile, PDO $pdo): array
    {
        $ops = $profile['operator_licenses'] ?? [];
        if (!$ops) {
            return [];
        }

        $bookExpiry = $ops[0]['expiry_date'] ?? null;
        $expired = $bookExpiry && strtotime($bookExpiry) < time();
        $factor = $expired ? self::EXPIRED_FACTOR : 1.0;

        $out = [];
        $out[] = new Contribution(
            source: $this->source(),
            label: 'Βιβλιάριο χειριστή',
            points: self::BOOK_POINTS * $factor,
            maxPoints: self::BOOK_POINTS,
            detail: $expired
                ? 'Η θεώρηση έχει λήξει'
                : ($bookExpiry ? 'Θεώρηση έως ' . date('m/Y', strtotime($bookExpiry)) : ''),
            expiresAt: $bookExpiry,
        );

        // Με τη σειρά του βιβλιαρίου, ώστε η φθίνουσα κλίμακα να μην
        // εξαρτάται από το πότε τα καταχώρησε ο οδηγός.
        usort($ops, static fn($a, $b) => ((int) ($a['speciality'] ?? 0)) <=> ((int) ($b['speciality'] ?? 0)));

        foreach (array_values($ops) as $i => $op) {
            $points = self::SPEC_POINTS[$i] ?? 2;
            if (!empty($op['covers_all'])) {
                $points += self::COVERS_ALL_BONUS;
            }
            $spec = (string) ($op['speciality'] ?? '');
            $group = strtoupper((string) ($op['group_type'] ?? 'A'));

            $out[] = new Contribution(
                source: $this->source(),
                label: $spec . 'η ειδικότητα · ' . ($group === 'M' ? 'μικτή ομάδα' : 'Ομάδα ' . $group . '΄'),
                points: $points * $factor,
                maxPoints: self::SPEC_POINTS[0] + self::COVERS_ALL_BONUS,
                detail: OperatorSpecialities::SPECIALITIES[$spec] ?? '',
                expiresAt: $bookExpiry,
            );
        }

        return $out;
    }
}
