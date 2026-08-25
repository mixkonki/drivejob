/**
 * Σελίδα «Προϋπηρεσία σε Οχήματα» — άμεση αποθήκευση ανά εγγραφή.
 *
 * Καμία ταξινομία εδώ: τα select έρχονται ΕΤΟΙΜΑ από τον server
 * (VehicleExperienceTypes). Το JS κάνει μόνο τρία πράγματα:
 *   1. δείχνει/κρύβει τα optgroups του «Τύπος Οχήματος» ανά είδος μεταφοράς
 *   2. POST /drivers/vehicle-experience στο «Προσθήκη» → νέα γραμμή στον πίνακα
 *   3. POST /drivers/vehicle-experience/delete/{id} στο «Διαγραφή»
 *
 * Ό,τι βλέπει ο χρήστης στον πίνακα είναι ήδη σωσμένο στη βάση.
 */
(function () {
    'use strict';

    const BASE = (window.BASE_URL || '/');
    const csrf = () => (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    const transportSel = document.getElementById('vxp_transport');
    const typeSel = document.getElementById('vxp_type');
    const tbody = document.getElementById('vxp-tbody');
    const addBtn = document.getElementById('vxp-add-btn');
    const msg = document.getElementById('vxp-msg');

    if (!transportSel || !typeSel || !tbody || !addBtn) return;

    /*
     * Τα optgroups που δεν ταιριάζουν στο είδος μεταφοράς ΑΦΑΙΡΟΥΝΤΑΙ από
     * το DOM (κρατιούνται σε μνήμη) — το display:none σε <optgroup> δεν
     * είναι αξιόπιστο σε όλους τους browsers (Safari το αγνοεί).
     */
    const allGroups = Array.from(typeSel.querySelectorAll('optgroup'));
    const placeholder = typeSel.querySelector('option');

    function renderTypeOptions() {
        const transport = transportSel.value;
        typeSel.innerHTML = '';
        typeSel.appendChild(placeholder);
        placeholder.textContent = transport ? 'Επιλέξτε τύπο οχήματος...' : 'Επιλέξτε πρώτα είδος μεταφοράς...';
        allGroups.forEach(function (g) {
            if (g.dataset.transport === transport) typeSel.appendChild(g);
        });
        typeSel.disabled = !transport;
        typeSel.value = '';
    }

    transportSel.addEventListener('change', renderTypeOptions);
    renderTypeOptions();

    // Ημερολόγιο με το κουμπί 📅 — η πληκτρολόγηση στο πεδίο μένει ελεύθερη.
    document.querySelectorAll('.vxp-cal').forEach(function (btn) {
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

    function showMsg(text, ok) {
        msg.textContent = text;
        msg.className = 'vxp-msg ' + (ok ? 'ok' : 'err');
        if (ok) setTimeout(function () { msg.className = 'vxp-msg'; }, 4000);
    }

    function setTotals(totals) {
        if (!totals) return;
        const f = document.getElementById('vxp-total-freight');
        const p = document.getElementById('vxp-total-passenger');
        const a = document.getElementById('vxp-total-all');
        if (f) f.textContent = totals.freight;
        if (p) p.textContent = totals.passenger;
        if (a) {
            a.innerHTML = '';
            const strong = document.createElement('strong');
            strong.textContent = totals.all;
            a.appendChild(strong);
        }
    }

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    async function post(url, params) {
        params.set('csrf_token', csrf());
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: params.toString(),
            credentials: 'same-origin'
        });
        return res.json();
    }

    addBtn.addEventListener('click', async function () {
        const typeVal = typeSel.value; // "κατηγορία|τύπος"
        if (!transportSel.value) { showMsg('Επίλεξε είδος μεταφοράς.', false); return; }
        if (!typeVal) { showMsg('Επίλεξε τύπο οχήματος.', false); return; }
        const start = document.getElementById('vxp_start').value;
        if (!start) { showMsg('Συμπλήρωσε την ημερομηνία έναρξης.', false); return; }

        const parts = typeVal.split('|');
        const params = new URLSearchParams();
        params.set('vehicle_category', parts[0]);
        params.set('vehicle_type', parts[1] || '');
        params.set('employment_type', document.getElementById('vxp_employment').value);
        params.set('start_date', start);
        params.set('end_date', document.getElementById('vxp_end').value);
        params.set('description', document.getElementById('vxp_description').value);

        addBtn.disabled = true;
        try {
            const data = await post(BASE + 'drivers/vehicle-experience', params);
            if (!data || data.success !== true) {
                showMsg((data && data.message) || 'Η αποθήκευση απέτυχε.', false);
                return;
            }
            const row = data.data.row;
            const tr = document.createElement('tr');
            tr.dataset.id = row.id;
            tr.innerHTML =
                '<td>' + esc(row.type_label) + '<div class="cat">' + esc(row.category_label) + '</div></td>' +
                '<td>' + esc(row.transport_label) + '</td>' +
                '<td>' + esc(row.period) + '</td>' +
                '<td>' + esc(row.duration) + '</td>' +
                '<td><button type="button" class="vxp-del" data-id="' + row.id + '">Διαγραφή</button></td>';
            tbody.appendChild(tr);
            setTotals(data.data.totals);
            document.getElementById('vxp-empty').style.display = 'none';

            // Καθάρισμα φόρμας για την επόμενη καταχώρηση
            typeSel.value = '';
            document.getElementById('vxp_start').value = '';
            document.getElementById('vxp_end').value = '';
            document.getElementById('vxp_description').value = '';
            showMsg('Αποθηκεύτηκε ✓', true);
        } catch (e) {
            showMsg('Πρόβλημα σύνδεσης — δοκίμασε ξανά.', false);
        } finally {
            addBtn.disabled = false;
        }
    });

    tbody.addEventListener('click', async function (ev) {
        const btn = ev.target.closest('.vxp-del');
        if (!btn) return;
        if (!window.confirm('Να διαγραφεί αυτή η εγγραφή προϋπηρεσίας;')) return;

        btn.disabled = true;
        try {
            const data = await post(BASE + 'drivers/vehicle-experience/delete/' + btn.dataset.id, new URLSearchParams());
            if (!data || data.success !== true) {
                showMsg((data && data.message) || 'Η διαγραφή απέτυχε.', false);
                btn.disabled = false;
                return;
            }
            const tr = btn.closest('tr');
            if (tr) tr.remove();
            setTotals(data.data.totals);
            if (!tbody.querySelector('tr')) {
                document.getElementById('vxp-empty').style.display = '';
            }
            showMsg('Διαγράφηκε ✓', true);
        } catch (e) {
            showMsg('Πρόβλημα σύνδεσης — δοκίμασε ξανά.', false);
            btn.disabled = false;
        }
    });
})();
