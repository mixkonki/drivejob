/**
 * Σεμινάρια & πιστοποιητικά — προσθήκη, διόρθωση, διαγραφή επιτόπου.
 * (30/08/2026)
 *
 * Άμεση αποθήκευση ανά πράξη, χωρίς αλλαγή σελίδας και χωρίς «Αποθήκευση
 * Αλλαγών»: ίδια αρχή με την προϋπηρεσία και τις γλώσσες. Η λίστα ζει
 * μέσα στη μεγάλη φόρμα του προφίλ, γι' αυτό δεν χρησιμοποιεί <form>
 * (η HTML δεν επιτρέπει ένθετη φόρμα) αλλά fetch με FormData.
 */
(function () {
    'use strict';

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    document.addEventListener('DOMContentLoaded', function () {
        var root = document.getElementById('crtManager');
        if (!root) { return; }

        var list = document.getElementById('crtList');
        var empty = document.getElementById('crtEmpty');
        var msg = document.getElementById('crtMsg');
        var csrf = root.dataset.csrf;

        function say(text, isError) {
            msg.textContent = text;
            msg.className = 'crt-msg ' + (isError ? 'crt-msg-error' : 'crt-msg-ok');
            msg.hidden = false;
            if (!isError) {
                setTimeout(function () { msg.hidden = true; }, 4000);
            }
        }

        function refreshEmpty() {
            if (empty) { empty.hidden = list.querySelectorAll('.crt-item').length > 0; }
        }

        function post(url, fd, onOk) {
            fd.append('csrf_token', csrf);
            fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && data.success) {
                        onOk(data);
                    } else {
                        say((data && data.message) || 'Κάτι πήγε στραβά.', true);
                    }
                })
                .catch(function () {
                    say('Πρόβλημα σύνδεσης. Δοκιμάστε ξανά.', true);
                });
        }

        // ── Προσθήκη ─────────────────────────────────────────────────────
        var addBtn = document.getElementById('crtAdd');
        if (addBtn) {
            addBtn.addEventListener('click', function () {
                var title = document.getElementById('crtNewTitle');
                if (!title.value.trim()) {
                    say('Συμπληρώστε τον τίτλο.', true);
                    title.focus();
                    return;
                }

                var fd = new FormData();
                fd.append('title', title.value.trim());
                fd.append('provider', document.getElementById('crtNewProvider').value.trim());
                fd.append('category', document.getElementById('crtNewCategory').value);
                fd.append('transport_type', document.getElementById('crtNewTransport').value);
                fd.append('date', document.getElementById('crtNewDate').value);
                fd.append('expiry', document.getElementById('crtNewExpiry').value);
                fd.append('duration', document.getElementById('crtNewDuration').value);
                fd.append('description', document.getElementById('crtNewDescription').value.trim());

                var file = document.getElementById('crtNewFile');
                if (file.files && file.files[0]) { fd.append('certificate_file', file.files[0]); }

                addBtn.disabled = true;
                post(root.dataset.addUrl, fd, function (data) {
                    addBtn.disabled = false;
                    // Η νέα εγγραφή χρειάζεται πλήρες markup με φόρμα διόρθωσης —
                    // το φέρνει σωστά ο server, οπότε ανανεώνουμε τη λίστα.
                    say(data.message || 'Η πιστοποίηση αποθηκεύτηκε.');
                    window.location.reload();
                });
                addBtn.disabled = false;
            });
        }

        // ── Διόρθωση / Διαγραφή (event delegation: πιάνει και νέες γραμμές) ─
        list.addEventListener('click', function (e) {
            var item = e.target.closest('.crt-item');
            if (!item) { return; }
            var id = item.dataset.id;

            if (e.target.classList.contains('crt-edit')) {
                item.querySelector('.crt-view').hidden = true;
                item.querySelector('.crt-edit-form').hidden = false;
                return;
            }

            if (e.target.classList.contains('crt-cancel')) {
                item.querySelector('.crt-edit-form').hidden = true;
                item.querySelector('.crt-view').hidden = false;
                return;
            }

            if (e.target.classList.contains('crt-delete')) {
                // Χωρίς confirm(): οι διάλογοι μπλοκάρουν και ενοχλούν.
                // Δεύτερο πάτημα μέσα σε 4΄΄ επιβεβαιώνει.
                if (e.target.dataset.armed !== '1') {
                    e.target.dataset.armed = '1';
                    var original = e.target.textContent;
                    e.target.textContent = 'Επιβεβαίωση;';
                    setTimeout(function () {
                        e.target.dataset.armed = '';
                        e.target.textContent = original;
                    }, 4000);
                    return;
                }

                post(root.dataset.deleteUrl + id, new FormData(), function () {
                    item.remove();
                    refreshEmpty();
                    say('Η πιστοποίηση διαγράφηκε.');
                });
                return;
            }

            if (e.target.classList.contains('crt-save')) {
                var form = item.querySelector('.crt-edit-form');
                var titleEl = form.querySelector('.crt-f-title');
                if (!titleEl.value.trim()) {
                    say('Συμπληρώστε τον τίτλο.', true);
                    return;
                }

                var fd = new FormData();
                fd.append('title', titleEl.value.trim());
                fd.append('provider', form.querySelector('.crt-f-provider').value.trim());
                fd.append('category', form.querySelector('.crt-f-category').value);
                fd.append('transport_type', form.querySelector('.crt-f-transport').value);
                fd.append('date', form.querySelector('.crt-f-date').value);
                fd.append('expiry', form.querySelector('.crt-f-expiry').value);
                fd.append('duration', form.querySelector('.crt-f-duration').value);
                fd.append('description', form.querySelector('.crt-f-description').value.trim());

                var f = form.querySelector('.crt-f-file');
                if (f.files && f.files[0]) { fd.append('certificate_file', f.files[0]); }

                e.target.disabled = true;
                post(root.dataset.updateUrl + id, fd, function (data) {
                    e.target.disabled = false;
                    var row = data.data && data.data.row ? data.data.row : null;
                    if (row) {
                        // Ενημέρωση της προβολής χωρίς reload.
                        var view = item.querySelector('.crt-view');
                        var main = view.querySelector('.crt-view-main');
                        var chips = '<strong class="crt-title">' + esc(row.title) + '</strong>';
                        if (row.category_label) { chips += ' <span class="crt-chip">' + esc(row.category_label) + '</span>'; }
                        chips += ' <span class="crt-chip crt-chip-soft">' + esc(row.transport_label) + '</span>';
                        if (row.expiry) {
                            chips += ' <span class="crt-chip ' + (row.expired ? 'crt-chip-expired' : 'crt-chip-ok') + '">'
                                + (row.expired ? 'Έληξε ' : 'Λήξη ') + esc(row.expiry) + '</span>';
                        } else {
                            chips += ' <span class="crt-chip crt-chip-soft">Χωρίς λήξη</span>';
                        }
                        main.innerHTML = chips;

                        var meta = view.querySelector('.crt-view-meta');
                        var parts = [];
                        if (row.provider) { parts.push('<span>' + esc(row.provider) + '</span>'); }
                        if (row.date) { parts.push('<span>Απόκτηση: ' + esc(row.date) + '</span>'); }
                        if (row.duration) { parts.push('<span>' + esc(row.duration) + ' ώρες</span>'); }
                        // Ο σύνδεσμος αρχείου διατηρείται όπως ήταν.
                        var oldLink = meta.querySelector('a');
                        if (oldLink) { parts.push(oldLink.outerHTML); }
                        meta.innerHTML = parts.join(' ');
                    }
                    form.hidden = true;
                    item.querySelector('.crt-view').hidden = false;
                    say(data.message || 'Η πιστοποίηση ενημερώθηκε.');
                });
            }
        });

        refreshEmpty();
    });
})();
