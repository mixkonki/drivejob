/**
 * Ασφαλιστικό ιστορικό — ανέβασμα .xlsx. (01/09/2026)
 *
 * Multipart μέσω fetch (το αρχείο δεν περνά από URLSearchParams).
 * Μετά από επιτυχία: reload, ώστε η σύνοψη να ζωγραφιστεί από τη βάση —
 * μία πηγή αλήθειας, όχι δεύτερη ζωγραφική στο JS.
 */
(function () {
    'use strict';

    var csrf = function () {
        return (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    };

    function show(el, text, ok) {
        el.hidden = false;
        el.textContent = text;
        el.className = 'refs-msg ' + (ok ? 'refs-msg--ok' : 'refs-msg--err');
    }

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('ins-form');
        var msg = document.getElementById('ins-msg');
        if (!form) { return; }

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            var btn = document.getElementById('ins-submit');
            btn.disabled = true;
            show(msg, 'Ανάγνωση αρχείου…', true);

            try {
                var fd = new FormData(form);
                fd.set('csrf_token', csrf());
                var res = await fetch(BASE_URL + 'drivers/insurance', { method: 'POST', body: fd });
                var data = await res.json();
                show(msg, data.message, data.success);
                if (data.success) {
                    setTimeout(function () { window.location.reload(); }, 1600);
                }
            } catch (err) {
                show(msg, 'Το αρχείο δεν διαβάστηκε. Βεβαιώσου ότι είναι το .xlsx από το gov.gr.', false);
            } finally {
                btn.disabled = false;
            }
        });

        var del = document.getElementById('ins-delete');
        if (del) {
            del.addEventListener('click', async function () {
                if (!window.confirm('Να διαγραφεί όλο το ασφαλιστικό ιστορικό από το προφίλ σου;')) { return; }
                var params = new URLSearchParams();
                params.set('csrf_token', csrf());
                try {
                    var res = await fetch(BASE_URL + 'drivers/insurance/delete', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                        body: params.toString()
                    });
                    var data = await res.json();
                    if (data.success) { window.location.reload(); }
                } catch (e) { /* το επόμενο refresh τα συγχρονίζει */ }
            });
        }
    });
})();
