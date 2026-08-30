<?php

namespace Drivejob\Helpers;

/**
 * Θεματολογίες και τύποι μεταφοράς σεμιναρίων/πιστοποιητικών (30/08/2026).
 *
 * Ήταν private constants μέσα στον DriversController — αόρατες στον
 * διαχειριστή και αδύνατο να επεκταθούν χωρίς deploy. Τώρα ζουν στον
 * κατάλογο (lookup_values, domain «cert_category») με τα DEFAULTS ως
 * seed και δίχτυ ασφαλείας.
 *
 * Ο τύπος μεταφοράς (both/freight/passenger) ΔΕΝ γίνεται κατάλογος:
 * είναι τρεις σταθερές τιμές πάνω στις οποίες υπολογίζει το ταίριασμα,
 * όχι λίστα που αλλάζει με τη νομοθεσία.
 */
final class CertificationCategories
{
    public const DOMAIN = 'cert_category';

    public const DEFAULTS = [
        'road_safety'      => 'Οδική ασφάλεια',
        'tachograph'       => 'Ταχογράφος',
        'loading_securing' => 'Φόρτωση - Πρόσδεση',
        'technical'        => 'Τεχνική επιμόρφωση',
        'commercial'       => 'Εμπορική επιμόρφωση',
        'procedures'       => 'Διαδικασίες',
        'inspections'      => 'Έλεγχοι',
        'first_aid'        => 'Πρώτες βοήθειες',
        'adr'              => 'ADR / Επικίνδυνα φορτία',
        'other'            => 'Άλλο',
    ];

    /** Σταθερό — δεν είναι κατάλογος (βλ. σχόλιο κλάσης). */
    public const TRANSPORT = [
        'both'      => 'Όλες οι μεταφορές',
        'freight'   => 'Εμπορευματικές',
        'passenger' => 'Επιβατικές',
    ];

    public static function options(): array
    {
        return Lookup::options(self::DOMAIN, self::DEFAULTS);
    }

    public static function isValid(string $code): bool
    {
        return Lookup::isValid(self::DOMAIN, $code, self::DEFAULTS);
    }

    public static function label(string $code): string
    {
        return Lookup::label(self::DOMAIN, $code, self::DEFAULTS);
    }

    public static function transportLabel(string $code): string
    {
        return self::TRANSPORT[$code] ?? self::TRANSPORT['both'];
    }
}
