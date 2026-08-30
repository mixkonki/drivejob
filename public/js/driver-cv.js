/**
 * Οθόνη βιογραφικού — ζωντανή προεπισκόπηση & άμεση αποθήκευση.
 * (31/08/2026)
 *
 * ΔΥΟ ΑΡΧΕΣ:
 *
 *  1. Η ΠΡΟΕΠΙΣΚΟΠΗΣΗ ΑΛΛΑΖΕΙ ΑΚΑΡΙΑΙΑ. Τα στοιχεία υπάρχουν όλα στο DOM
 *     και ο διακόπτης απλώς τα κρύβει — καμία αναμονή δικτύου ανάμεσα
 *     στην πράξη και στο αποτέλεσμα. Ο οδηγός πρέπει να ΔΕΙ τι κάνει το
 *     κουμπί, όχι να το μάθει μετά από μισό δευτερόλεπτο.
 *
 *  2. Η ΑΠΟΘΗΚΕΥΣΗ ΓΙΝΕΤΑΙ ΜΟΝΗ ΤΗΣ, στο παρασκήνιο. Καθιερωμένο μοτίβο
 *     του project (πιστοποιητικά, γλώσσες, προϋπηρεσία): άμεση
 *     αποθήκευση ανά πράξη, καμία «Αποθήκευση Αλλαγών» σε οθόνη με
 *     τέσσερα πεδία.
 *
 * Το κείμενο της παρουσίασης αποθηκεύεται με καθυστέρηση (debounce):
 * μια αίτηση ανά πληκτρολόγηση θα ήταν επίθεση στον ίδιο μας τον server.
 */
(function () {
    'use strict';

    var SAVE_DELAY = 900;   // ms αδράνειας πριν σταλεί το κείμενο

    document.addEventListener('DOMContentLoaded', function () {
        var root = document.getElementById('cvRoot');
        if (!root) { return; }

        var summary = document.getElementById('cvSummary');
        var paperSummary = document.getElementById('cvPaperSummary');
        var counter = document.getElementById('cvCount');
        var resetBtn = document.getElementById('cvReset');
        var savedFlag = document.getElementById('cvSaved');
        var paper = document.getElementById('cvPaper');

        var saveUrl = root.dataset.saveUrl;
        var csrf = root.dataset.csrf;
        var timer = null;

        function toggles() {
            return root.querySelectorAll('[data-opt]');
        }

        function flash(text, isError) {
            if (!savedFlag) { return; }
            savedFlag.textContent = text;
            savedFlag.classList.toggle('is-error', !!isError);
            savedFlag.hidden = false;
            clearTimeout(savedFlag._t);
            savedFlag._t = setTimeout(function () { savedFlag.hidden = true; }, 2500);
        }

        /** Στέλνει ΟΛΗ την κατάσταση: μία πηγή, καμία μερική ενημέρωση. */
        function save() {
            var fd = new FormData();
            fd.append('csrf_token', csrf);
            fd.append('cv_summary', summary ? summary.value : '');

            toggles().forEach(function (t) {
                if (t.checked) { fd.append(t.name, '1'); }
            });

            fetch(saveUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (d && d.success) {
                        flash('Αποθηκεύτηκε');
                    } else {
                        flash((d && d.message) || 'Δεν αποθηκεύτηκε', true);
                    }
                })
                .catch(function () { flash('Πρόβλημα σύνδεσης', true); });
        }

        function saveSoon() {
            clearTimeout(timer);
            timer = setTimeout(save, SAVE_DELAY);
        }

        // ── Διακόπτες: κρύβουν/δείχνουν ΑΜΕΣΩΣ, αποθηκεύουν μετά ─────────
        toggles().forEach(function (t) {
            t.addEventListener('change', function () {
                var part = paper.querySelector('[data-part="' + t.dataset.opt + '"]');
                if (part) { part.hidden = !t.checked; }
                save();
            });
        });

        // ── Παρουσίαση ───────────────────────────────────────────────────
        function syncSummary() {
            var text = summary.value;
            if (counter) { counter.textContent = text.length; }
            if (paperSummary) {
                paperSummary.textContent = text;
                // Κενή παρουσίαση δεν αφήνει κενή γραμμή στο χαρτί.
                paperSummary.hidden = text.trim() === '';
            }
        }

        if (summary) {
            syncSummary();
            summary.addEventListener('input', function () {
                syncSummary();
                saveSoon();
            });
            // Έξοδος από το πεδίο = αποθήκευση τώρα, χωρίς αναμονή.
            summary.addEventListener('blur', function () {
                clearTimeout(timer);
                save();
            });
        }

        if (resetBtn && summary) {
            resetBtn.addEventListener('click', function () {
                summary.value = resetBtn.dataset.auto || '';
                syncSummary();
                clearTimeout(timer);
                save();
                summary.focus();
            });
        }
    });
})();
