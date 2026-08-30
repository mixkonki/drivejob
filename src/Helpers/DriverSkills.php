<?php

namespace Drivejob\Helpers;

/**
 * Επαγγελματικές δεξιότητες οδηγού — ονόματα και ομαδοποίηση.
 * (30/08/2026)
 *
 * ΓΙΑΤΙ ΥΠΑΡΧΕΙ: τα ελληνικά ονόματα των δεξιοτήτων ήταν γραμμένα ΜΟΝΟ
 * μέσα στη φόρμα επεξεργασίας (skills.php), σε <label>. Το προφίλ, το
 * βιογραφικό και το ταίριασμα δεν είχαν τρόπο να τα διαβάσουν — και η
 * πρώτη σελίδα που θα ήθελε να τα δείξει θα τα ξανάγραφε, με μικρές
 * διαφορές. Μία πηγή αλήθειας.
 *
 * ΟΙ ΚΩΔΙΚΟΙ ΕΙΝΑΙ ΣΤΗΛΕΣ ΠΙΝΑΚΑ (driver_skills), όχι κατάλογος
 * lookup_values: κάθε δεξιότητα είναι δικό της TINYINT πεδίο. Αν
 * χρειαστεί να τις διαχειρίζεται ο admin, θέλει πρώτα κανονικοποίηση
 * του πίνακα σε γραμμές — δεν το κάνουμε τώρα, αλλά αυτός ο helper
 * είναι το σημείο απ' όπου θα ξεκινήσει.
 *
 * Οι ομάδες είναι ΟΙ ΙΔΙΕΣ με τα <h4> της φόρμας: ο οδηγός τις δήλωσε
 * με αυτή τη σειρά, τις ξαναβλέπει με αυτή τη σειρά.
 */
class DriverSkills
{
    public const LABELS = [
        // Οδηγικές ικανότητες
        'defensive_driving' => 'Αμυντική οδήγηση',
        'eco_driving' => 'Οικολογική οδήγηση',
        'night_driving' => 'Νυχτερινή οδήγηση',
        'mountain_driving' => 'Οδήγηση σε ορεινές περιοχές',
        'extreme_conditions' => 'Οδήγηση σε ακραίες συνθήκες',
        'precision_handling' => 'Ακρίβεια χειρισμών',

        // Ασφάλεια & συμμόρφωση
        'loading_securing' => 'Φόρτωση & ασφάλιση φορτίου',
        'dangerous_goods' => 'Διαχείριση επικίνδυνων εμπορευμάτων',
        'emergency_response' => 'Αντιμετώπιση έκτακτων καταστάσεων',
        'first_aid' => 'Πρώτες βοήθειες',
        'fire_safety' => 'Πυρασφάλεια',
        'vehicle_inspection' => 'Έλεγχος οχημάτων',
        'tacograph_compliance' => 'Συμμόρφωση με ταχογράφο',

        // Επαγγελματισμός
        'customer_service' => 'Εξυπηρέτηση πελατών',
        'time_management' => 'Διαχείριση χρόνου',
        'route_planning' => 'Σχεδιασμός διαδρομής',
        'conflict_resolution' => 'Επίλυση συγκρούσεων',
        'report_writing' => 'Σύνταξη αναφορών',
        'multilingual' => 'Πολύγλωσσος',
        'inspection_behavior' => 'Συμπεριφορά σε έλεγχο',
        'border_crossing' => 'Διέλευση συνόρων',

        // Τεχνικές γνώσεις
        'vehicle_maintenance' => 'Συντήρηση οχήματος',
        'troubleshooting' => 'Αντιμετώπιση βλαβών',
        'technical_terms' => 'Γνώση τεχνικών όρων',
        'equipment_handling' => 'Χειρισμός εξοπλισμού',
        'checklists_usage' => 'Χρήση λιστών ελέγχου',
        'digital_tachograph' => 'Ψηφιακός ταχογράφος',
        'gps_systems' => 'Συστήματα GPS',
        'logistics_software' => 'Λογισμικό logistics',
    ];

    /** Ομάδες όπως εμφανίζονται στη φόρμα επεξεργασίας. */
    public const GROUPS = [
        'Οδηγικές ικανότητες' => [
            'defensive_driving', 'eco_driving', 'night_driving',
            'mountain_driving', 'extreme_conditions', 'precision_handling',
        ],
        'Ασφάλεια & συμμόρφωση' => [
            'loading_securing', 'dangerous_goods', 'emergency_response',
            'first_aid', 'fire_safety', 'vehicle_inspection', 'tacograph_compliance',
        ],
        'Επαγγελματισμός' => [
            'customer_service', 'time_management', 'route_planning',
            'conflict_resolution', 'report_writing', 'multilingual',
            'inspection_behavior', 'border_crossing',
        ],
        'Τεχνικές γνώσεις' => [
            'vehicle_maintenance', 'troubleshooting', 'technical_terms',
            'equipment_handling', 'checklists_usage', 'digital_tachograph',
            'gps_systems', 'logistics_software',
        ],
    ];

    public static function label(string $code): string
    {
        return self::LABELS[$code] ?? $code;
    }
}
