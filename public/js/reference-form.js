/**
 * Δημόσια φόρμα σύστασης — πλευρά εργοδότη. (01/09/2026)
 *
 * Υποβολή με fetch στο ΙΔΙΟ URL (POST /reference/{token}) και
 * αντικατάσταση της φόρμας με το ευχαριστώ. Καμία πλοήγηση: ο
 * άνθρωπος ήρθε από Viber σε κινητό — ό,τι λιγότερο, τόσο καλύτερα.
 */
(function () {
    'use strict';

    var csrf = function () {
        return (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    };

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('ref-public-form');
        if (!form) { return; }
        var msg = document.getElementById('ref-public-msg');

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            var btn = document.getElementById('ref-public-submit');
            btn.disabled = true;

            try {
                var params = new URLSearchParams(new FormData(form));
                params.set('csrf_token', csrf());
                var res = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                    body: params.toString()
                });
                var data = await res.json();

                if (data.success) {
                    // Όλη η κάρτα γίνεται ευχαριστώ — τίποτα άλλο να κάνει.
                    var card = form.closest('.refs-card');
                    card.classList.add('refs-card--ok');
                    card.innerHTML = '<h1>Ευχαριστούμε!</h1>'
                        + '<p>Η σύστασή σας καταχωρήθηκε και θα συνυπολογιστεί στη '
                        + 'συνολική εικόνα του οδηγού.</p>';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    msg.hidden = false;
                    msg.textContent = data.message;
                    msg.className = 'refs-msg refs-msg--err';
                    btn.disabled = false;
                }
            } catch (err) {
                msg.hidden = false;
                msg.textContent = 'Κάτι πήγε στραβά. Δοκιμάστε ξανά.';
                msg.className = 'refs-msg refs-msg--err';
                btn.disabled = false;
            }
        });
    });
})();
