<?php

namespace Drivejob\Models;

use PDO;
use PDOException;
use Drivejob\Core\Logger;

/**
 * Κατάλογοι τιμών που συντηρεί ο διαχειριστής (lookup_values) — 30/08/2026.
 *
 * Αντικαθιστά τις σκληροκωδικοποιημένες λίστες: ο Κώστας μπορεί να
 * προσθέτει, να διορθώνει και να αποσύρει τιμές από το admin χωρίς deploy.
 *
 * ΔΥΟ ΚΑΝΟΝΕΣ ΑΣΦΑΛΕΙΑΣ:
 *  1. Καμία διαγραφή όσο η τιμή χρησιμοποιείται — μόνο απενεργοποίηση.
 *     Αλλιώς ένας οδηγός με «ΕΔΧ» θα έμενε με ορφανό κωδικό στη βάση.
 *  2. Οι τιμές is_system δεν απενεργοποιούνται: πάνω τους υπολογίζει το
 *     ταίριασμα. Η ετικέτα τους όμως διορθώνεται ελεύθερα.
 *
 * Fallback: αν ο πίνακας δεν υπάρχει ακόμη (π.χ. πριν το migration), οι
 * μέθοδοι επιστρέφουν [] και ο καλών helper πέφτει στις ενσωματωμένες
 * τιμές του — η εφαρμογή δεν σπάει ποτέ από ένα migration που άργησε.
 */
class LookupModel
{
    private $pdo;

    /** Κατάλογοι που μπορεί να διαχειριστεί ο admin. */
    public const DOMAINS = [
        'special_license' => 'Ειδικές άδειες & πιστοποιητικά οδηγού',
        'cert_category'   => 'Θεματολογίες σεμιναρίων & πιστοποιητικών',
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Οι τιμές ενός καταλόγου.
     *
     * @param bool $activeOnly true = μόνο ενεργές (για φόρμες οδηγών),
     *                         false = όλες (για το admin)
     * @return array<int, array>
     */
    public function all(string $domain, bool $activeOnly = true): array
    {
        try {
            $sql = 'SELECT * FROM lookup_values WHERE domain = :domain'
                . ($activeOnly ? ' AND is_active = 1' : '')
                . ' ORDER BY sort_order ASC, label ASC';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['domain' => $domain]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Ο πίνακας μπορεί να μην υπάρχει ακόμη — ο helper έχει fallback.
            Logger::error('LookupModel::all — ' . $e->getMessage(), ['domain' => $domain]);
            return [];
        }
    }

    /** code => label, έτοιμο για <select>. */
    public function options(string $domain, bool $activeOnly = true): array
    {
        $out = [];
        foreach ($this->all($domain, $activeOnly) as $row) {
            $out[$row['code']] = $row['label'];
        }
        return $out;
    }

    public function find(int $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM lookup_values WHERE id = ?');
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            Logger::error('LookupModel::find — ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Νέα τιμή. Ο κωδικός κανονικοποιείται (πεζά λατινικά/αριθμοί/κάτω παύλα)
     * γιατί αποθηκεύεται στις εγγραφές των οδηγών και δεν αλλάζει ποτέ.
     *
     * @return array{ok:bool, error?:string}
     */
    public function create(string $domain, string $code, string $label, ?string $shortLabel, int $sortOrder): array
    {
        $code = $this->normalizeCode($code);
        $label = trim($label);

        if ($code === '' || $label === '') {
            return ['ok' => false, 'error' => 'Ο κωδικός και η ονομασία είναι υποχρεωτικά.'];
        }
        if (!isset(self::DOMAINS[$domain])) {
            return ['ok' => false, 'error' => 'Άγνωστος κατάλογος.'];
        }

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO lookup_values (domain, code, label, short_label, sort_order, is_active, is_system)
                 VALUES (?, ?, ?, ?, ?, 1, 0)'
            );
            $stmt->execute([$domain, $code, $label, trim((string) $shortLabel) ?: null, $sortOrder]);
            return ['ok' => true];
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return ['ok' => false, 'error' => 'Υπάρχει ήδη τιμή με αυτόν τον κωδικό στον κατάλογο.'];
            }
            Logger::error('LookupModel::create — ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Δεν ήταν δυνατή η αποθήκευση.'];
        }
    }

    /** Ενημέρωση ετικέτας/σειράς. Ο κωδικός ΔΕΝ αλλάζει ποτέ. */
    public function update(int $id, string $label, ?string $shortLabel, int $sortOrder): array
    {
        $label = trim($label);
        if ($label === '') {
            return ['ok' => false, 'error' => 'Η ονομασία δεν μπορεί να είναι κενή.'];
        }

        try {
            $stmt = $this->pdo->prepare(
                'UPDATE lookup_values SET label = ?, short_label = ?, sort_order = ? WHERE id = ?'
            );
            $stmt->execute([$label, trim((string) $shortLabel) ?: null, $sortOrder, $id]);
            return ['ok' => true];
        } catch (PDOException $e) {
            Logger::error('LookupModel::update — ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Δεν ήταν δυνατή η αποθήκευση.'];
        }
    }

    /** Απενεργοποίηση/επαναφορά — ΠΟΤΕ διαγραφή τιμής που χρησιμοποιείται. */
    public function setActive(int $id, bool $active): array
    {
        $row = $this->find($id);
        if (!$row) {
            return ['ok' => false, 'error' => 'Η τιμή δεν βρέθηκε.'];
        }
        if (!$active && (int) $row['is_system'] === 1) {
            return ['ok' => false, 'error' => 'Η τιμή είναι βασική του συστήματος και δεν απενεργοποιείται.'];
        }

        try {
            $stmt = $this->pdo->prepare('UPDATE lookup_values SET is_active = ? WHERE id = ?');
            $stmt->execute([$active ? 1 : 0, $id]);
            return ['ok' => true];
        } catch (PDOException $e) {
            Logger::error('LookupModel::setActive — ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Δεν ήταν δυνατή η αλλαγή.'];
        }
    }

    /**
     * Οριστική διαγραφή — επιτρέπεται ΜΟΝΟ αν κανένας οδηγός δεν τη
     * χρησιμοποιεί. Αλλιώς προτείνεται απενεργοποίηση.
     */
    public function delete(int $id): array
    {
        $row = $this->find($id);
        if (!$row) {
            return ['ok' => false, 'error' => 'Η τιμή δεν βρέθηκε.'];
        }
        if ((int) $row['is_system'] === 1) {
            return ['ok' => false, 'error' => 'Η τιμή είναι βασική του συστήματος και δεν διαγράφεται.'];
        }

        $usage = $this->usageCount($row['domain'], $row['code']);
        if ($usage > 0) {
            return [
                'ok' => false,
                'error' => 'Δεν διαγράφεται: τη χρησιμοποιούν ' . $usage . ' καταχωρήσεις οδηγών. '
                    . 'Απενεργοποιήστε την ώστε να μην προσφέρεται σε νέες καταχωρήσεις.',
            ];
        }

        try {
            $stmt = $this->pdo->prepare('DELETE FROM lookup_values WHERE id = ?');
            $stmt->execute([$id]);
            return ['ok' => true];
        } catch (PDOException $e) {
            Logger::error('LookupModel::delete — ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Δεν ήταν δυνατή η διαγραφή.'];
        }
    }

    /** Πόσες εγγραφές οδηγών δείχνουν σε αυτόν τον κωδικό. */
    public function usageCount(string $domain, string $code): int
    {
        $map = [
            'special_license' => ['table' => 'driver_special_licenses', 'column' => 'license_type'],
            'cert_category'   => ['table' => 'driver_certifications', 'column' => 'category'],
        ];

        if (!isset($map[$domain])) {
            return 0;
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM ' . $map[$domain]['table'] . ' WHERE ' . $map[$domain]['column'] . ' = ?'
            );
            $stmt->execute([$code]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            Logger::error('LookupModel::usageCount — ' . $e->getMessage());
            return 0;
        }
    }

    private function normalizeCode(string $code): string
    {
        $code = strtolower(trim($code));
        $code = preg_replace('/[^a-z0-9_]+/', '_', $code);
        return trim((string) $code, '_');
    }
}
