/**
 * Συστάσεις εργοδοτών — πλευρά οδηγού. (01/09/2026)
 *
 * Τρεις δουλειές: (1) δημιουργία πρόσκλησης χωρίς αλλαγή σελίδας, ώστε
 * ο σύνδεσμος να εμφανιστεί ΑΜΕΣΩΣ και να αντιγραφεί με ένα πάτημα,
 * (2) αντιγραφή συνδέσμου από τις εκκρεμείς, (3) ακύρωση εκκρεμούς.
 *
 * Η ΑΝΤΙΓΡΑΦΗ έχει και εφεδρεία (select + execCommand) γιατί το
 * navigator.clipboard θέλει HTTPS — και το τοπικό στήσιμο του Κώστα
 * τρέχει σε http://drivejob.test.
 */
(function () {
    'use strict';

    var csrf = function () {
        return (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    };

    function copy(text, btn) {
        var done = function () {
            var old = btn.textContent;
            btn.textContent = 'Αντιγράφηκε ✓';
            setTimeout(function () { btn.textContent = old; }, 1800);
        };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(done);
        } else {
            var tmp = document.createElement('textarea');
            tmp.value = text;
            document.body.appendChild(tmp);
            tmp.select();
            try { document.execCommand('copy'); done(); } catch (e) { /* αμελητέο */ }
            document.body.removeChild(tmp);
        }
    }

    function show(el, text, ok) {
        el.hidden = false;
        el.textContent = text;
        el.className = 'refs-msg ' + (ok ? 'refs-msg--ok' : 'refs-msg--err');
    }

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('ref-form');
        var msg = document.getElementById('ref-msg');

        if (form) {
            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                var btn = document.getElementById('ref-submit');
                btn.disabled = true;

                try {
                    var params = new URLSearchParams(new FormData(form));
                    params.set('csrf_token', csrf());
                    var res = await fetch(BASE_URL + 'drivers/references', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                        body: params.toString()
                    });
                    var data = await res.json();
                    show(msg, data.message, data.success);

                    if (data.success) {
                        // ΠΡΩΤΑ το reset, ΜΕΤΑ ο σύνδεσμος: το #ref-link ζει
                        // μέσα στην ίδια <form>, και το reset() θα το
                        // καθάριζε μαζί με τα υπόλοιπα (πιάστηκε στο e2e).
                        form.reset();
                        var box = document.getElementById('ref-link-box');
                        document.getElementById('ref-link').value = data.data.link;
                        box.hidden = false;
                        // Η λίστα εκκρεμών άλλαξε — αλλά ο οδηγός πρώτα
                        // αντιγράφει τον σύνδεσμο. Όχι reload εδώ.
                    }
                } catch (err) {
                    show(msg, 'Κάτι πήγε στραβά. Δοκίμασε ξανά.', false);
                } finally {
                    btn.disabled = false;
                }
            });
        }

        var copyBtn = document.getElementById('ref-copy');
        if (copyBtn) {
            copyBtn.addEventListener('click', function () {
                copy(document.getElementById('ref-link').value, copyBtn);
            });
        }

        document.querySelectorAll('.refs-copybtn').forEach(function (b) {
            b.addEventListener('click', function () { copy(b.dataset.link, b); });
        });

        document.querySelectorAll('.refs-cancel').forEach(function (b) {
            b.addEventListener('click', async function () {
                var item = b.closest('.refs-item');
                var params = new URLSearchParams();
                params.set('csrf_token', csrf());
                try {
                    var res = await fetch(BASE_URL + 'drivers/references/delete/' + item.dataset.id, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                        body: params.toString()
                    });
                    var data = await res.json();
                    if (data.success) { item.remove(); }
                    else if (msg) { show(msg, data.message, false); }
                } catch (e) { /* το επόμενο refresh τα συγχρονίζει */ }
            });
        });
    });
})();
