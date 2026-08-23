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
    <img src="<?php echo BASE_URL; ?>img/logo.png" alt="DriveJob" width="180" height="52">
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
</style>
<?php endif; ?>
