/**
 * Σεμινάρια & Πιστοποιητικά — άμεση αποθήκευση ανά εγγραφή.
 *
 * «Προσθήκη» → POST /drivers/certifications (multipart, με το αρχείο
 * βεβαίωσης αν υπάρχει) → η κάρτα εμφανίζεται σωσμένη. «Διαγραφή» →
 * POST /drivers/certifications/delete/{id}. Ό,τι βλέπεις είναι στη βάση.
 */
(function () {
    'use strict';

    const BASE = (window.BASE_URL || '/');
    const csrf = () => (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    const list = document.getElementById('crt-list');
    const addBtn = document.getElementById('crt-add-btn');
    const msg = document.getElementById('crt-msg');

    if (!list || !addBtn) return;

    function showMsg(text, ok) {
        msg.textContent = text;
        msg.className = 'crt-msg ' + (ok ? 'ok' : 'err');
        if (ok) setTimeout(function () { msg.className = 'crt-msg'; }, 4000);
    }

    // Ημερολόγιο από το κουμπί 📅 — η πληκτρολόγηση μένει ελεύθερη.
    document.querySelectorAll('.crt-cal').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = document.getElementById(btn.dataset.for || '');
            if (!input) return;
            if (typeof input.showPicker === 'function') {
                try { input.showPicker(); } catch (e) { input.focus(); }
            } else {
                input.focus();
            }
        });
    });

    function span(text, cls) {
        const s = document.createElement('span');
        if (cls) s.className = cls;
        s.textContent = text;
        return s;
    }

    function card(row) {
        const div = document.createElement('div');
        div.className = 'crt-item';
        div.dataset.id = row.id;

        const top = document.createElement('div');
        top.className = 't';
        const h = document.createElement('h3');
        h.textContent = row.title;
        const del = document.createElement('button');
        del.type = 'button';
        del.className = 'crt-del';
        del.dataset.id = row.id;
        del.textContent = 'Διαγραφή';
        top.appendChild(h);
        top.appendChild(del);
        div.appendChild(top);

        const meta = document.createElement('div');
        meta.className = 'meta';
        if (row.provider) meta.appendChild(span(row.provider));
        if (row.category_label) meta.appendChild(span(row.category_label, 'crt-badge'));
        if (row.transport_label) meta.appendChild(span(row.transport_label));
        if (row.date) meta.appendChild(span('Απόκτηση: ' + row.date));
        if (row.expiry) {
            meta.appendChild(span((row.expired ? 'Έληξε' : 'Λήξη') + ': ' + row.expiry,
                'crt-badge ' + (row.expired ? 'expired' : 'valid')));
        }
        if (row.duration) meta.appendChild(span(row.duration + ' ώρες'));
        if (row.file_url) {
            const wrap = document.createElement('span');
            wrap.className = 'crt-file';
            const a = document.createElement('a');
            a.href = row.file_url;
            a.target = '_blank';
            a.rel = 'noopener';
            a.textContent = 'Βεβαίωση ↗';
            wrap.appendChild(a);
            meta.appendChild(wrap);
        }
        div.appendChild(meta);
        return div;
    }

    addBtn.addEventListener('click', async function () {
        const title = document.getElementById('crt_title').value.trim();
        if (!title) { showMsg('Συμπλήρωσε τον τίτλο της πιστοποίησης.', false); return; }

        const fd = new FormData();
        fd.set('csrf_token', csrf());
        fd.set('title', title);
        fd.set('provider', document.getElementById('crt_provider').value);
        fd.set('category', document.getElementById('crt_category').value);
        fd.set('transport_type', document.getElementById('crt_transport').value);
        fd.set('date', document.getElementById('crt_date').value);
        fd.set('expiry', document.getElementById('crt_expiry').value);
        fd.set('duration', document.getElementById('crt_duration').value);
        fd.set('description', document.getElementById('crt_description').value);
        const fileInput = document.getElementById('crt_file');
        if (fileInput.files.length) fd.set('certificate_file', fileInput.files[0]);

        addBtn.disabled = true;
        try {
            const res = await fetch(BASE + 'drivers/certifications', {
                method: 'POST', body: fd, credentials: 'same-origin'
            });
            const data = await res.json();
            if (!data || data.success !== true) {
                showMsg((data && data.message) || 'Η αποθήκευση απέτυχε.', false);
                return;
            }
            list.appendChild(card(data.data.row));
            document.getElementById('crt-empty').style.display = 'none';

            // Καθάρισμα φόρμας για την επόμενη
            ['crt_title', 'crt_provider', 'crt_date', 'crt_expiry', 'crt_duration', 'crt_description'].forEach(function (id) {
                document.getElementById(id).value = '';
            });
            document.getElementById('crt_category').value = '';
            fileInput.value = '';
            showMsg('Αποθηκεύτηκε ✓', true);
        } catch (e) {
            showMsg('Πρόβλημα σύνδεσης — δοκίμασε ξανά.', false);
        } finally {
            addBtn.disabled = false;
        }
    });

    list.addEventListener('click', async function (ev) {
        const btn = ev.target.closest('.crt-del');
        if (!btn) return;
        if (!window.confirm('Να διαγραφεί αυτή η πιστοποίηση;')) return;

        btn.disabled = true;
        try {
            const params = new URLSearchParams();
            params.set('csrf_token', csrf());
            const res = await fetch(BASE + 'drivers/certifications/delete/' + btn.dataset.id, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                body: params.toString(),
                credentials: 'same-origin'
            });
            const data = await res.json();
            if (!data || data.success !== true) {
                showMsg((data && data.message) || 'Η διαγραφή απέτυχε.', false);
                btn.disabled = false;
                return;
            }
            const item = btn.closest('.crt-item');
            if (item) item.remove();
            if (!list.querySelector('.crt-item')) {
                document.getElementById('crt-empty').style.display = '';
            }
            showMsg('Διαγράφηκε ✓', true);
        } catch (e) {
            showMsg('Πρόβλημα σύνδεσης — δοκίμασε ξανά.', false);
            btn.disabled = false;
        }
    });
})();
