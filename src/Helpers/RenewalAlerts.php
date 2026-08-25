<?php

namespace Drivejob\Helpers;

/**
 * Υπενθυμίσεις ανανέωσης εγγράφων — ΜΟΝΟ όταν πλησιάζει η λήξη.
 *
 * Αρχή (feedback Κώστα 25/08): «όχι μόνιμα μηνύματα καρφωτά». Τα στατικά
 * ενημερωτικά μπλοκ αντικαθίστανται από αυτό το helper: δείχνει ειδοποίηση
 * μόνο μέσα στο παράθυρο ανανέωσης του κάθε εγγράφου, με σύνδεσμο για την
 * ενέργεια (gov.gr, αναζήτηση σχολής κλπ). Εκτός παραθύρου: τίποτα.
 *
 * Παράθυρα ανά έγγραφο (μία πηγή αλήθειας):
 *  - Δίπλωμα: ανανέωση έως 2 μήνες πριν τη λήξη  → 60 ημέρες
 *  - ΠΕΙ: ανανέωση έως 1 έτος πριν τη λήξη        → 365 ημέρες
 *  - ADR: ανανέωση τον τελευταίο χρόνο πριν λήξη  → 365 ημέρες
 *  - Κάρτα ταχογράφου: αίτηση λίγο πριν τη λήξη   → 60 ημέρες
 *  - Άδεια χειριστή: θεώρηση 11ετίας              → 180 ημέρες
 */
final class RenewalAlerts
{
    public const WINDOW_LICENSE    = 60;
    public const WINDOW_PEI        = 365;
    public const WINDOW_ADR        = 365;
    public const WINDOW_TACHOGRAPH = 60;
    public const WINDOW_OPERATOR   = 180;

    /** gov.gr: ανανέωση άδειας οδήγησης */
    public const URL_LICENSE = 'https://www.gov.gr/ipiresies/polites-kai-kathemerinoteta/diploma-odegeses/ananeose-adeias-odegeses';
    /** gov.gr: κάρτα ψηφιακού ταχογράφου */
    public const URL_TACHOGRAPH = 'https://www.gov.gr/ipiresies/polites-kai-kathemerinoteta/metakineseis/karta-psephiakou-takhographou';

    /**
     * Επιστρέφει HTML ειδοποίησης αν το έγγραφο λήγει μέσα στο παράθυρο
     * (ή έχει ήδη λήξει), αλλιώς κενό string.
     *
     * @param string|null $expiryDate 'Y-m-d' (ή ό,τι καταλαβαίνει το strtotime)
     * @param int         $windowDays πόσες ημέρες πριν τη λήξη ανοίγει το παράθυρο
     * @param string      $docLabel   π.χ. «Η άδεια οδήγησης»
     * @param string      $actionHtml επιπλέον οδηγία/σύνδεσμος (ήδη escaped HTML)
     */
    public static function render(?string $expiryDate, int $windowDays, string $docLabel, string $actionHtml = ''): string
    {
        if (!$expiryDate) {
            return '';
        }
        $ts = strtotime($expiryDate);
        if ($ts === false) {
            return '';
        }

        $days = (int) floor(($ts - strtotime('today')) / 86400);
        if ($days > $windowDays) {
            return ''; // εκτός παραθύρου — καμία ειδοποίηση
        }

        $date = date('d/m/Y', $ts);
        if ($days < 0) {
            $cls  = 'renewal-alert renewal-alert--expired';
            $text = $docLabel . ' <strong>έληξε στις ' . $date . '</strong>.';
        } elseif ($days === 0) {
            $cls  = 'renewal-alert renewal-alert--expired';
            $text = $docLabel . ' <strong>λήγει σήμερα</strong>.';
        } else {
            $cls  = 'renewal-alert';
            $text = $docLabel . ' λήγει στις <strong>' . $date . '</strong> (σε ' . $days . ' ημέρες).';
        }

        return '<div class="' . $cls . '">'
            . '<p>' . $text . ($actionHtml !== '' ? ' ' . $actionHtml : '') . '</p>'
            . '</div>';
    }

    /** Σύνδεσμος-ενέργεια με ενιαία μορφή. */
    public static function link(string $url, string $label): string
    {
        return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
            . '" target="_blank" rel="noopener">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . ' ↗</a>';
    }

    /** Αναζήτηση σχολής/φορέα κοντά στον οδηγό (με βάση την πόλη του). */
    public static function schoolSearchLink(string $what, ?string $city): string
    {
        $q = trim($what . ' ' . ($city ?: ''));
        return self::link('https://www.google.com/search?q=' . rawurlencode($q), 'Εύρεση σχολής στην περιοχή σας');
    }
}
