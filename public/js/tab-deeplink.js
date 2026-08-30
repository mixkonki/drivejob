/**
 * ΣΥΝΔΕΣΜΟΙ ΠΟΥ ΠΑΝΕ ΑΚΡΙΒΩΣ ΕΚΕΙ ΠΟΥ ΠΡΕΠΕΙ. (31/08/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΟ ΠΡΟΒΛΗΜΑ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Το κουμπί «Επεξεργασία Προσόντων» έδειχνε ήδη σε
 * `drivers/edit-profile#skills-tab` — αλλά η σελίδα άνοιγε στην ΠΡΩΤΗ
 * καρτέλα. Οι καρτέλες είναι JavaScript, όχι anchors: το hash δεν
 * σήμαινε τίποτα για κανέναν.
 *
 * Ο οδηγός που έβλεπε «ΠΕΙ Εμπορευμάτων — δεν διαθέτει» έπρεπε να:
 * πατήσει Επεξεργασία → θυμηθεί σε ποια καρτέλα ζει το ΠΕΙ → τη βρει
 * ανάμεσα σε επτά → βρει το πεδίο μέσα της. Τέσσερα βήματα και μνήμη,
 * για κάτι που είχε ήδη μπροστά του.
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΙ ΚΑΝΕΙ
 * ══════════════════════════════════════════════════════════════════════
 *
 *   drivers/edit-profile#skills-tab
 *       → ανοίγει την καρτέλα «Προσόντα & Πιστοποιήσεις»
 *
 *   drivers/edit-profile#driving-licenses~pei_c_number
 *       → ανοίγει την καρτέλα ΚΑΙ φωτίζει το συγκεκριμένο πεδίο
 *
 * Το `~` και όχι δεύτερο `#`: ένα URL έχει ΕΝΑ hash. Το tilde δεν
 * χρειάζεται κωδικοποίηση και διαβάζεται καθαρά στη γραμμή διευθύνσεων.
 *
 * ΚΑΙ ΑΝΤΙΣΤΡΟΦΑ: κάθε αλλαγή καρτέλας γράφει το hash. Έτσι το refresh
 * μένει στην ίδια καρτέλα, το «πίσω» του browser δουλεύει, και ο οδηγός
 * μπορεί να στείλει σύνδεσμο σε ακριβώς αυτό που βλέπει.
 *
 * ΓΕΝΙΚΟ ΕΞΕΠΙΤΗΔΕΣ: δουλεύει σε ΚΑΘΕ σελίδα με `.tab-btn[data-tab]` —
 * επεξεργασία προφίλ, προβολή προφίλ, και ό,τι έρθει. Καμία λίστα
 * σελίδων να συντηρείται.
 */
(function () {
    'use strict';

    var FLASH_MS = 2200;

    document.addEventListener('DOMContentLoaded', function () {
        var buttons = document.querySelectorAll('.tab-btn[data-tab]');
        if (!buttons.length) { return; }

        function activate(tabId, scrollIntoView) {
            var btn = document.querySelector('.tab-btn[data-tab="' + tabId + '"]');
            var pane = document.getElementById(tabId);
            if (!btn || !pane) { return false; }

            document.querySelectorAll('.tab-btn').forEach(function (b) { b.classList.remove('active'); });
            document.querySelectorAll('.tab-pane').forEach(function (p) { p.classList.remove('active'); });
            btn.classList.add('active');
            pane.classList.add('active');

            if (scrollIntoView) {
                // Στην κορυφή των καρτελών, όχι στο τέλος του pane: ο
                // οδηγός θέλει να δει την αρχή αυτού που άνοιξε.
                var nav = document.querySelector('.tabs-nav') || btn;
                nav.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            return true;
        }

        /** Φωτίζει προσωρινά το στοιχείο-στόχο ώστε να πέσει το μάτι. */
        function flash(id) {
            var el = document.getElementById(id)
                || document.querySelector('[name="' + id + '"]')
                || document.querySelector('.' + id);
            if (!el) { return; }

            // Το πεδίο μπορεί να είναι μέσα σε ενότητα — φωτίζουμε την
            // ομάδα του αν υπάρχει, αλλιώς το ίδιο.
            var target = el.closest('.form-group, .form-section') || el;

            setTimeout(function () {
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                target.classList.add('deeplink-flash');
                setTimeout(function () { target.classList.remove('deeplink-flash'); }, FLASH_MS);

                // Αν είναι πεδίο εισαγωγής, ο δρόμος συνεχίζει μόνος του.
                if (el.matches('input, select, textarea') && !el.disabled) {
                    try { el.focus({ preventScroll: true }); } catch (e) { /* παλιοί browsers */ }
                }
            }, 260);   // αφού τελειώσει το scroll της καρτέλας
        }

        /** «#tab» ή «#tab~στόχος» */
        function applyHash(scrollIntoView) {
            var hash = (location.hash || '').replace(/^#/, '');
            if (!hash) { return; }

            var parts = hash.split('~');
            if (activate(parts[0], scrollIntoView) && parts[1]) {
                flash(parts[1]);
            }
        }

        // ── Κλικ σε καρτέλα: γράφει το hash ──────────────────────────────
        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = this.getAttribute('data-tab');
                // replaceState και όχι location.hash: το δεύτερο θα
                // πυροδοτούσε hashchange και θα ξανα-σκρόλαρε τη σελίδα
                // σε κάθε κλικ.
                if (window.history && history.replaceState) {
                    history.replaceState(null, '', '#' + id);
                }
            });
        });

        // ── Σύνδεσμος μέσα στην ΙΔΙΑ σελίδα ──────────────────────────────
        window.addEventListener('hashchange', function () { applyHash(true); });

        // ── Φόρτωση με hash ──────────────────────────────────────────────
        // Χωρίς scroll: ο χρήστης μόλις ήρθε, η σελίδα είναι ήδη στην
        // κορυφή. Με στόχο (~) το flash κάνει το δικό του scroll.
        applyHash(false);
    });
})();
