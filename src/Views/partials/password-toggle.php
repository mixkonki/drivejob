<?php

/**
 * Κουμπί εμφάνισης / απόκρυψης συνθηματικού.
 *
 * ΓΙΑΤΙ ΥΠΑΡΧΕΙ: οι φόρμες εγγραφής έδειχναν το κείμενο «show/hide password»
 * αντί για εικονίδιο, επειδή τα αρχεία img/eye.png και img/eye-slash.png δεν
 * υπήρξαν ποτέ στο repo — ο browser εμφάνιζε το alt text της σπασμένης εικόνας.
 *
 * Τα εικονίδια είναι πλέον inline SVG: δεν χρειάζονται αρχεία, δεν μπορούν να
 * ξαναλείψουν, κληρονομούν το χρώμα του κειμένου και είναι ευκρινή σε κάθε
 * ανάλυση.
 *
 * Χρήση — μέσα σε δοχείο με position: relative, αμέσως μετά το <input>:
 *
 *     <div class="password-visibility">
 *         <input type="password" id="password" name="password" ...>
 *         <?php $passwordFieldId = 'password';
 *               include ROOT_DIR . '/src/Views/partials/password-toggle.php'; ?>
 *     </div>
 *
 * Μεταβλητές:
 *   $passwordFieldId  (υποχρεωτικό) το id του πεδίου συνθηματικού
 */

$passwordFieldId = $passwordFieldId ?? 'password';

// Η JS και τα στιλ μπαίνουν μία φορά, όσες φορές κι αν συμπεριληφθεί το partial
$firstUse = !isset($GLOBALS['__dj_password_toggle_loaded']);
$GLOBALS['__dj_password_toggle_loaded'] = true;
?>

<button type="button"
        class="dj-password-toggle"
        data-target="<?php echo htmlspecialchars($passwordFieldId, ENT_QUOTES, 'UTF-8'); ?>"
        aria-label="Εμφάνιση συνθηματικού"
        aria-pressed="false"
        title="Εμφάνιση συνθηματικού">
    <svg class="dj-eye" viewBox="0 0 24 24" width="20" height="20" fill="none"
         stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
         stroke-linejoin="round" aria-hidden="true" focusable="false">
        <path d="M1.5 12S5.5 4.8 12 4.8 22.5 12 22.5 12 18.5 19.2 12 19.2 1.5 12 1.5 12Z"/>
        <circle cx="12" cy="12" r="3.2"/>
    </svg>
    <svg class="dj-eye-off" viewBox="0 0 24 24" width="20" height="20" fill="none"
         stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
         stroke-linejoin="round" aria-hidden="true" focusable="false">
        <path d="M9.9 5.1A9.9 9.9 0 0 1 12 4.8c6.5 0 10.5 7.2 10.5 7.2a19.4 19.4 0 0 1-2.6 3.7"/>
        <path d="M6.2 6.4A19.2 19.2 0 0 0 1.5 12S5.5 19.2 12 19.2a9.9 9.9 0 0 0 4-.8"/>
        <path d="M9.8 9.9a3.2 3.2 0 0 0 4.4 4.4"/>
        <line x1="2.5" y1="2.5" x2="21.5" y2="21.5"/>
    </svg>
</button>

<?php if ($firstUse) : ?>
<style>
    .password-visibility { position: relative; }

    .dj-password-toggle {
        position: absolute;
        right: .6rem;
        top: 50%;
        transform: translateY(-50%);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.2rem;
        height: 2.2rem;
        padding: 0;
        border: 0;
        border-radius: 50%;
        background: transparent;
        color: #6b7280;
        cursor: pointer;
        transition: color .15s ease, background-color .15s ease;
    }

    .dj-password-toggle:hover { color: #374151; background: rgba(0, 0, 0, .05); }
    .dj-password-toggle:focus-visible { outline: 2px solid #2563eb; outline-offset: 2px; }

    /* Εξ ορισμού φαίνεται το ανοιχτό μάτι· όταν το συνθηματικό είναι ορατό,
       φαίνεται το μάτι με τη γραμμή. */
    .dj-password-toggle .dj-eye-off { display: none; }
    .dj-password-toggle[aria-pressed="true"] .dj-eye { display: none; }
    .dj-password-toggle[aria-pressed="true"] .dj-eye-off { display: block; }

    /* Χώρος ώστε το κείμενο να μην περνά κάτω από το κουμπί */
    .password-visibility input[type="password"],
    .password-visibility input[type="text"] { padding-right: 3rem; }
</style>

<script>
    /**
     * Ένας ακροατής για όλα τα κουμπιά της σελίδας, ακόμη και για όσα
     * προστεθούν αργότερα δυναμικά.
     */
    document.addEventListener('click', function (event) {
        var button = event.target.closest('.dj-password-toggle');
        if (!button) return;

        var field = document.getElementById(button.dataset.target);
        if (!field) return;

        var willShow = field.type === 'password';
        field.type = willShow ? 'text' : 'password';

        button.setAttribute('aria-pressed', willShow ? 'true' : 'false');
        var label = willShow ? 'Απόκρυψη συνθηματικού' : 'Εμφάνιση συνθηματικού';
        button.setAttribute('aria-label', label);
        button.setAttribute('title', label);
    });
</script>
<?php endif; ?>
