<?php

namespace Drivejob\Helpers;

/**
 * Η ταξινομία οχημάτων της προϋπηρεσίας — ΜΙΑ πηγή αλήθειας.
 *
 * Μέχρι 25/08/2026 η ταξινομία ζούσε ΟΛΟΚΛΗΡΗ στο vehicle-experience.js
 * και ΜΙΣΗ στο SkillModel::getVehicleTypeName (3 από τις 10 κατηγορίες,
 * με σχόλιο «Προσθέστε και τις υπόλοιπες»). Αποτέλεσμα: ένα ταξί
 * αποθηκευόταν σωστά αλλά εμφανιζόταν ως «standard_taxi» αντί για
 * «Κλασικό Ταξί», γιατί η PHP δεν ήξερε το όνομα.
 *
 * Από εδώ διαβάζουν: το view (σερβίρει τα select server-side), ο
 * controller (επικύρωση εισόδου με allowlist), το SkillModel (ονόματα
 * για εμφάνιση). Το JavaScript της σελίδας ΔΕΝ έχει δικό του αντίγραφο.
 */
final class VehicleExperienceTypes
{
    /** transport_type → category → (type code → όνομα) */
    public const TAXONOMY = [
        'freight' => [
            'lcv' => [
                'panel_van' => 'Κλειστό Van',
                'pickup_truck' => 'Van με καρότσα (Pick-up)',
                'small_refrigerated' => 'Μικρό φορτηγό ψυγείο/κατάψυξης',
            ],
            'rigid_truck' => [
                'distribution_truck' => 'Φορτηγό Διανομών',
                'refrigerated_truck' => 'Φορτηγό Ψυγείο/Κατάψυξης',
                'platform_truck' => 'Φορτηγό Πλατφόρμα',
                'dump_truck' => 'Ανατρεπόμενο Φορτηγό',
                'tanker_truck' => 'Βυτιοφόρο (άκαμπτο)',
                'car_carrier' => 'Όχημα Μεταφοράς Οχημάτων',
                'silo_truck' => 'Φορτηγό με Σιλό',
                'crane_truck' => 'Φορτηγό με Γερανό',
                'livestock_truck' => 'Όχημα Μεταφοράς Ζώων',
            ],
            'articulated' => [
                'curtainsider' => 'Επικαθήμενο με Μουσαμά',
                'reefer' => 'Επικαθήμενο Ψυγείο/Κατάψυξη',
                'box_trailer' => 'Επικαθήμενο Κλειστού Τύπου',
                'flatbed' => 'Επικαθήμενο Πλατφόρμα',
                'tipper' => 'Επικαθήμενο Ανατρεπόμενο',
                'tanker' => 'Επικαθήμενο Βυτίο',
                'silo' => 'Επικαθήμενο Σιλό',
                'container' => 'Επικαθήμενο Μεταφοράς Εμπορευματοκιβωτίων',
                'car_transporter' => 'Επικαθήμενο Μεταφοράς Οχημάτων',
                'livestock' => 'Επικαθήμενο Μεταφοράς Ζώων',
                'low_loader' => 'Επικαθήμενο Χαμηλής Κλίνης',
                'drawbar' => 'Φορτηγό με Ρυμουλκούμενο (συρμός)',
            ],
            'utility' => [
                'garbage_truck' => 'Απορριμματοφόρο',
                'street_sweeper' => 'Σάρωθρο Δρόμων',
                'snow_plow' => 'Εκχιονιστικό',
                'water_truck' => 'Υδροφόρα',
                'maintenance_vehicle' => 'Όχημα Συντήρησης',
            ],
            'construction' => [
                'concrete_mixer' => 'Μπετονιέρα',
                'crane_truck' => 'Γερανοφόρο',
                'excavator_transport' => 'Μεταφορά Εκσκαφέων',
                'bulldozer_transport' => 'Μεταφορά Μπουλντόζας',
            ],
            'specialized' => [
                'mobile_workshop' => 'Κινητό Συνεργείο',
                'mobile_library' => 'Κινητή Βιβλιοθήκη',
                'mobile_medical' => 'Κινητή Ιατρική Μονάδα',
                'food_truck' => 'Κινητή Καντίνα',
                'other' => 'Άλλο Εξειδικευμένο Όχημα',
            ],
        ],
        'passenger' => [
            'taxi' => [
                'standard_taxi' => 'Κλασικό Ταξί',
                'luxury_taxi' => 'Ταξί Πολυτελείας',
                'accessible_taxi' => 'Ταξί για ΑμεΑ',
            ],
            'minibus' => [
                'standard_minibus' => 'Τυπικό Μικρό Λεωφορείο',
                'school_minibus' => 'Σχολικό Μικρό Λεωφορείο',
                'accessible_minibus' => 'Μικρό Λεωφορείο για ΑμεΑ',
                'luxury_minibus' => 'Μικρό Λεωφορείο Πολυτελείας',
            ],
            'bus' => [
                'city_bus' => 'Αστικό Λεωφορείο',
                'intercity_bus' => 'Υπεραστικό Λεωφορείο',
                'coach' => 'Τουριστικό Πούλμαν',
                'double_decker' => 'Διώροφο Λεωφορείο',
                'articulated_bus' => 'Αρθρωτό Λεωφορείο',
                'school_bus' => 'Σχολικό Λεωφορείο',
            ],
            'emergency' => [
                'ambulance' => 'Ασθενοφόρο',
                'fire_truck' => 'Πυροσβεστικό',
                'police_vehicle' => 'Αστυνομικό Όχημα',
                'rescue_vehicle' => 'Όχημα Διάσωσης',
            ],
        ],
    ];

    public const CATEGORY_LABELS = [
        'lcv' => 'Ελαφρά Επαγγελματικά Οχήματα',
        'rigid_truck' => 'Μεσαία & Βαρέα Φορτηγά',
        'articulated' => 'Αρθρωτά/Συρόμενα Οχήματα',
        'utility' => 'Οχήματα Δημοτικά/Κοινής Ωφέλειας',
        'construction' => 'Οχήματα Έργων/Κατασκευών',
        'specialized' => 'Άλλα Εξειδικευμένα Οχήματα',
        'taxi' => 'Ταξί',
        'minibus' => 'Μικρό Λεωφορείο',
        'bus' => 'Λεωφορεία & Πούλμαν',
        'emergency' => 'Οχήματα Έκτακτης Ανάγκης',
    ];

    public const TRANSPORT_LABELS = [
        'freight' => 'Εμπορευματικές',
        'passenger' => 'Επιβατικές',
    ];

    public const EMPLOYMENT_LABELS = [
        'own_business' => 'Ίδια Επιχείρηση',
        'employee' => 'Υπάλληλος',
        'contractor' => 'Εξωτερικός Συνεργάτης',
    ];

    /** Το είδος μεταφοράς μιας κατηγορίας, ή null αν άγνωστη. */
    public static function transportOfCategory(string $category): ?string
    {
        foreach (self::TAXONOMY as $transport => $categories) {
            if (isset($categories[$category])) {
                return $transport;
            }
        }

        return null;
    }

    /** Έγκυρος συνδυασμός κατηγορίας/τύπου; */
    public static function isValid(string $category, string $type): bool
    {
        $transport = self::transportOfCategory($category);

        return $transport !== null && isset(self::TAXONOMY[$transport][$category][$type]);
    }

    public static function typeLabel(string $category, string $type): string
    {
        $transport = self::transportOfCategory($category);

        return $transport !== null
            ? (self::TAXONOMY[$transport][$category][$type] ?? $type)
            : $type;
    }

    public static function categoryLabel(string $category): string
    {
        return self::CATEGORY_LABELS[$category] ?? $category;
    }

    public static function transportLabel(?string $transport): string
    {
        return self::TRANSPORT_LABELS[$transport] ?? (string) $transport;
    }
}
