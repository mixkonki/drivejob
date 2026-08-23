<?php

/**
 * Λογότυπο DriveJob με σύνδεσμο στην αρχική.
 *
 * ΓΙΑΤΙ ΕΧΕΙ ΕΝΣΩΜΑΤΩΜΕΝΑ ΣΤΙΛ: οι φόρμες εγγραφής δεν φορτώνουν το κοινό
 * header, και το CSS τους δεν φορτώνει πάντα στην παραγωγή. Ένα λογότυπο που
 * βασίζεται σε εξωτερικό αρχείο εμφανίστηκε σε φυσικό μέγεθος και ξεχείλισε
 * έξω από τη φόρμα. Τα στιλ μπαίνουν εδώ ώστε να ισχύουν πάντα.
 *
 * Χρήση:
 *     <?php include ROOT_DIR . '/src/Views/partials/brand-logo.php'; ?>
 */

$firstUse = !isset($GLOBALS['__dj_brand_logo_loaded']);
$GLOBALS['__dj_brand_logo_loaded'] = true;
?>

<a href="<?php echo BASE_URL; ?>" class="dj-brand-home"
   aria-label="Επιστροφή στην αρχική σελίδα του DriveJob">
    <?php
    /*
     * Οι διαστάσεις είναι ΥΠΟΛΟΓΙΣΜΕΝΕΣ, όχι μαντεμένες. Το logo.png είναι
     * 2800×1000 (αναλογία 2.8:1). Το σταθερό width="180" height="52" που
     * ήταν εδώ δήλωνε αναλογία 3.46:1 — ο browser κράτησε χώρο σε λάθος
     * σχήμα και η σελίδα «πηδούσε» μόλις φόρτωνε η εικόνα.
     */
    $logoFile = (defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 3)) . '/public/img/logo.png';
    $logoH = 52;
    $logoW = 146; // εφεδρεία αν το αρχείο δεν διαβάζεται
    if (is_file($logoFile) && ($dim = @getimagesize($logoFile))) {
        $logoW = (int) round($logoH * ($dim[0] / max(1, $dim[1])));
    }
    ?>
    <img src="<?= \Drivejob\Helpers\Asset::url('img/logo.png') ?>" alt="DriveJob"
         width="<?= $logoW ?>" height="<?= $logoH ?>">
</a>

<?php if ($firstUse) : ?>
<style>
    .dj-brand-home {
        display: block;
        width: 100%;
        text-align: center;
        margin: 0 auto 1.25rem;
        line-height: 0;
    }

    .dj-brand-home img {
        display: inline-block;
        height: 52px;
        width: auto;
        max-width: 100%;
        object-fit: contain;
        transition: opacity .15s ease;
    }

    .dj-brand-home:hover img { opacity: .8; }

    .dj-brand-link {
        color: inherit;
        text-decoration: none;
        border-bottom: 1px solid currentColor;
    }

    .dj-brand-link:hover { opacity: .75; }

    /*
     * ΤΟ BODY ΚΟΒΕΙ ΤΗΝ ΚΟΡΥΦΗ ΤΗΣ ΣΕΛΙΔΑΣ.
     *
     * Οι φόρμες εγγραφής ορίζουν στο body: display:flex, align-items:center
     * και height:100vh. Όσο το περιεχόμενο χωράει στο παράθυρο όλα δείχνουν
     * σωστά — μόλις το ξεπεράσει, το κεντράρισμα το σπρώχνει εξίσου προς τα
     * πάνω και προς τα κάτω, και η κορυφή βγαίνει ΕΞΩ από την οθόνη σε
     * αρνητικό top. Δεν φτάνει ούτε το scroll, γιατί το ύψος του body είναι
     * κλειδωμένο στο 100vh.
     *
     * Το λογότυπο βρέθηκε σε top: -46px — υπήρχε, φόρτωνε, αλλά ήταν
     * αθέατο. Στο Firefox χανόταν εντελώς, στο Chrome μόλις που ξεπρόβαλλε.
     *
     * Η διόρθωση: στοίχιση από πάνω και ύψος που μεγαλώνει με το περιεχόμενο.
     * Ο κανόνας ζει ΕΔΩ, μαζί με το λογότυπο, ώστε να ισχύει ακόμη κι αν το
     * εξωτερικό φύλλο στιλ δεν φορτώσει.
     */
    body {
        align-items: flex-start;
        height: auto;
        min-height: 100vh;
    }
</style>
<?php endif; ?>
<?php
// Καθαρισμός ώστε το partial να μην αφήνει μεταβλητές στο view που το κάλεσε.
unset($firstUse, $logoFile, $logoH, $logoW, $dim);
?>
