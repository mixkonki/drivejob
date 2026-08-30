<?php

namespace Drivejob\Helpers;

/**
 * Εικονίδια τυπικών προσόντων — inline SVG (30/08/2026).
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ INLINE SVG ΚΑΙ ΟΧΙ ΑΡΧΕΙΑ PNG / ΓΡΑΜΜΑΤΟΣΕΙΡΑ ΕΙΚΟΝΙΔΙΩΝ
 * ══════════════════════════════════════════════════════════════════════
 *
 *  - Τα PNG του project λείπουν κατά τόπους (view_icon.png, match_icon.png
 *    και άλλα δίνουν 404) — ένα εικονίδιο που δεν φορτώνει είναι χειρότερο
 *    από κανένα.
 *  - Το Font Awesome έρχεται από εξωτερικό CDN: μία αργή απόκριση και όλα
 *    τα προσόντα μένουν χωρίς σήμανση.
 *  - Το inline SVG κληρονομεί `currentColor`, οπότε κάθε ομάδα βάφει τα
 *    εικονίδιά της με το δικό της χρώμα χωρίς δεύτερο αρχείο.
 *
 * Ένα σχήμα ανά προσόν, 24×24, μόνο περίγραμμα — αναγνωρίσιμο και σε
 * 18px στο κινητό.
 */
class QualIcons
{
    /** Οι διαδρομές κάθε εικονιδίου (χωρίς το <svg> περίβλημα). */
    private const PATHS = [
        // ── Οδήγηση ────────────────────────────────────────────────────
        // Δίπλωμα: κάρτα με φωτογραφία και γραμμές στοιχείων
        'license' => '<rect x="2" y="4" width="20" height="16" rx="2"/><circle cx="8" cy="11" r="2.2"/><line x1="13" y1="9" x2="19" y2="9"/><line x1="13" y1="13" x2="19" y2="13"/><line x1="5" y1="16.5" x2="11" y2="16.5"/>',
        // ΠΕΙ εμπορευμάτων: φορτηγό με κιβώτιο
        'pei_freight' => '<rect x="1" y="7" width="12" height="9" rx="1"/><path d="M13 10h4l3 3.2V16h-7z"/><circle cx="6" cy="18.5" r="1.8"/><circle cx="17" cy="18.5" r="1.8"/>',
        // ΠΕΙ επιβατών: λεωφορείο
        'pei_passenger' => '<rect x="3" y="3" width="18" height="13" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="12" y1="3" x2="12" y2="9"/><circle cx="7.5" cy="18.5" r="1.6"/><circle cx="16.5" cy="18.5" r="1.6"/>',

        // ── Πιστοποιήσεις οχήματος ─────────────────────────────────────
        // ADR: τρίγωνο κινδύνου
        'adr' => '<path d="M12 3 L22 20 H2 Z"/><line x1="12" y1="9" x2="12" y2="14"/><circle cx="12" cy="17" r=".6" fill="currentColor"/>',
        // Ταχογράφος: μετρητής
        'tachograph' => '<circle cx="12" cy="12" r="9"/><path d="M12 12 L16.5 8.5"/><line x1="12" y1="3" x2="12" y2="5"/><line x1="21" y1="12" x2="19" y2="12"/><line x1="3" y1="12" x2="5" y2="12"/>',

        // ── Μηχανήματα έργου (ανά ειδικότητα) ──────────────────────────
        // 1. Εκσκαφή / χωματουργικά — εκσκαφέας με μπούμα και κουβά
        'op_1' => '<path d="M3 20h18"/><rect x="3" y="14" width="8" height="4" rx="1"/><path d="M9 14 L13 6 L17 8"/><path d="M15 11 h5 v3 h-5 z"/>',
        // 2. Ανύψωση φορτίων ή προσώπων — γερανός
        'op_2' => '<path d="M3 21h18"/><path d="M6 21V4h12"/><line x1="6" y1="4" x2="18" y2="10"/><line x1="18" y1="4" x2="18" y2="8"/><path d="M16 12h4v3h-4z"/>',
        // 3. Οδοστρωσία — οδοστρωτήρας
        'op_3' => '<circle cx="6" cy="16" r="4"/><rect x="12" y="12" width="9" height="4" rx="1"/><path d="M12 12V8h6v4"/><line x1="12" y1="19" x2="21" y2="19"/>',
        // 4. Εξυπηρέτηση οδών & αεροδρομίων — όχημα με βούρτσα/λεπίδα
        'op_4' => '<rect x="7" y="8" width="11" height="6" rx="1"/><path d="M3 18 L7 10"/><circle cx="10" cy="17" r="1.8"/><circle cx="16" cy="17" r="1.8"/><line x1="2" y1="19" x2="5" y2="19"/>',
        // 5. Υπόγεια έργα & μεταλλεία — στοά με βαγονέτο
        'op_5' => '<path d="M3 20V12a9 9 0 0 1 18 0v8"/><rect x="8" y="14" width="8" height="4" rx="1"/><circle cx="10" cy="19.5" r="1.2"/><circle cx="14" cy="19.5" r="1.2"/>',
        // 6. Έλξη — ελκυστήρας με άγκιστρο
        'op_6' => '<rect x="3" y="9" width="10" height="6" rx="1"/><circle cx="6" cy="18" r="2"/><circle cx="12" cy="18" r="2"/><path d="M13 12h4a3 3 0 0 1 3 3v2"/>',
        // 7. Διάτρηση & κοπή εδαφών — γεωτρύπανο
        'op_7' => '<path d="M3 21h18"/><rect x="4" y="15" width="8" height="4" rx="1"/><line x1="16" y1="3" x2="16" y2="17"/><path d="M14 17h4"/><path d="M14 7h4"/><path d="M14 11h4"/>',
        // 8. Ειδικές εργασίες ανύψωσης — τηλεσκοπικός ανυψωτήρας
        'op_8' => '<path d="M3 20h18"/><rect x="3" y="14" width="7" height="4" rx="1"/><path d="M9 15 L20 7"/><path d="M17 4h4v4"/>',
        // 9. Πολλαπλές εργασίες — γρανάζι με βέλη
        'op_9' => '<circle cx="12" cy="12" r="3.2"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M5 5l2 2M17 17l2 2M19 5l-2 2M7 17l-2 2"/>',

        // ── Ειδικές άδειες οδηγού ──────────────────────────────────────
        'edx_taxi' => '<rect x="2" y="10" width="20" height="8" rx="2"/><path d="M6 10V7h12v3"/><rect x="9" y="3" width="6" height="3" rx="1"/><circle cx="7" cy="19.5" r="1.4"/><circle cx="17" cy="19.5" r="1.4"/>',
        'live_animals' => '<path d="M4 18v-5a4 4 0 0 1 4-4h6l4 3v6"/><circle cx="7.5" cy="20" r="1.4"/><circle cx="16.5" cy="20" r="1.4"/><path d="M9 9V6M13 9V6"/><line x1="6" y1="13" x2="16" y2="13"/>',
        'rental_driver' => '<path d="M5 17h14"/><path d="M6 17l1.5-6h9L18 17"/><circle cx="8" cy="19" r="1.4"/><circle cx="16" cy="19" r="1.4"/><path d="M12 3v4"/><path d="M10 5h4"/>',
        'pee_freight' => '<path d="M5 4h11l3 3v13H5z"/><line x1="8" y1="10" x2="16" y2="10"/><line x1="8" y1="14" x2="14" y2="14"/><circle cx="17" cy="17.5" r="3"/><path d="M15.6 17.5l1 1 2-2.2"/>',
        'pee_passenger' => '<path d="M5 4h11l3 3v13H5z"/><circle cx="11" cy="10.5" r="2"/><path d="M7.5 16c.6-1.8 2-2.8 3.5-2.8s2.9 1 3.5 2.8"/>',
        'special_other' => '<path d="M12 2l2.6 5.6 6 .8-4.4 4.2 1.1 6.1L12 15.8 6.7 18.7l1.1-6.1L3.4 8.4l6-.8z"/>',

        // ── Ενότητες βιογραφικού ───────────────────────────────────────
        // Προϋπηρεσία: τιμόνι — η ίδια η δουλειά, όχι έγγραφο
        'experience' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3"/><line x1="12" y1="3" x2="12" y2="9"/><line x1="4.5" y1="16.5" x2="9.4" y2="13.6"/><line x1="19.5" y1="16.5" x2="14.6" y2="13.6"/>',
        // Σεμινάριο: δίπλωμα με κορδέλα
        'seminar' => '<circle cx="12" cy="9" r="5"/><path d="M8.5 13.2 7 21l5-2.4 5 2.4-1.5-7.8"/>',

        // Εφεδρικό
        'default' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v4"/><circle cx="12" cy="16" r=".6" fill="currentColor"/>',
    ];

    /** Ποιο εικονίδιο ταιριάζει σε ποια ειδική άδεια (κωδικός καταλόγου). */
    private const SPECIAL_MAP = [
        'edx_taxi' => 'edx_taxi',
        'live_animals' => 'live_animals',
        'rental_driver' => 'rental_driver',
        'pee_freight' => 'pee_freight',
        'pee_passenger' => 'pee_passenger',
    ];

    /**
     * Επιστρέφει έτοιμο <svg>. Το `currentColor` το βάφει η ομάδα του.
     */
    public static function svg(string $name, string $class = 'qual-icon'): string
    {
        $paths = self::PATHS[$name] ?? self::PATHS['default'];

        return '<svg class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '" viewBox="0 0 24 24" '
            . 'fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" '
            . 'stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths . '</svg>';
    }

    /** Εικονίδιο ειδικότητας μηχανημάτων έργου (1–9). */
    public static function operator(string $speciality, string $class = 'qual-icon'): string
    {
        return self::svg('op_' . $speciality, $class);
    }

    /** Εικονίδιο ειδικής άδειας από τον κωδικό καταλόγου. */
    public static function special(string $code, string $class = 'qual-icon'): string
    {
        return self::svg(self::SPECIAL_MAP[$code] ?? 'special_other', $class);
    }
}
