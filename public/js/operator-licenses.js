/**
 * Άδειες χειριστή μηχανημάτων έργου — ειδικότητες & υποειδικότητες.
 *
 * Ο κατάλογος (ΥΑ 1032/166/2013) έρχεται ΕΤΟΙΜΟΣ από την PHP ως
 * window.djOperatorCatalog = { "1": {"1.1": "Εκσκαφείς...", ...}, ... }.
 * Εδώ ζει η loadSubSpecialities() που το dropdown καλούσε ανέκαθεν —
 * αλλά δεν είχε γραφτεί ποτέ, κι ο πίνακας έμενε για πάντα άδειος.
 *
 * Κατάσταση επιλογών: window.allSelectedSubSpecialities
 *   { "2.7": { checked: true, group: "A" }, ... }
 * (αρχικοποιείται από τη βάση στο inline script του edit-profile).
 * Κάθε αλλαγή σειριοποιείται αμέσως στα κρυφά πεδία που διαβάζει ο
 * server: all_selected_subspecialities (JSON array κωδικών) και
 * all_selected_groups (JSON αντικείμενο κωδικός→ομάδα).
 */
(function () {
    'use strict';

    const catalog = window.djOperatorCatalog || {};

    function state() {
        if (!window.allSelectedSubSpecialities) window.allSelectedSubSpecialities = {};
        return window.allSelectedSubSpecialities;
    }

    /** Σειριοποίηση της πλήρους κατάστασης στα κρυφά πεδία της φόρμας. */
    function serialize() {
        const codes = [];
        const groups = {};
        Object.keys(state()).forEach(function (code) {
            const entry = state()[code];
            if (entry && entry.checked) {
                codes.push(code);
                groups[code] = entry.group === 'B' ? 'B' : 'A';
            }
        });
        const codesField = document.getElementById('all_selected_subspecialities');
        const groupsField = document.getElementById('all_selected_groups');
        if (codesField) codesField.value = JSON.stringify(codes);
        if (groupsField) groupsField.value = JSON.stringify(groups);
        renderSelectedList(codes, groups);
    }

    /** Ζωντανή ενημέρωση της λίστας «Επιλεγμένες Υποειδικότητες». */
    function renderSelectedList(codes, groups) {
        const container = document.querySelector('.selected-subspecialities');
        if (!container) return;
        const old = container.querySelectorAll('.speciality-group, .selected-list');
        old.forEach(function (el) { el.remove(); });

        if (!codes.length) {
            const ul = document.createElement('ul');
            ul.className = 'selected-list';
            const li = document.createElement('li');
            li.className = 'no-items';
            li.textContent = 'Δεν έχουν επιλεγεί υποειδικότητες';
            ul.appendChild(li);
            container.appendChild(ul);
            return;
        }

        codes.sort();
        const bySpec = {};
        codes.forEach(function (code) {
            const spec = code.split('.')[0];
            (bySpec[spec] = bySpec[spec] || []).push(code);
        });

        Object.keys(bySpec).sort().forEach(function (spec) {
            const div = document.createElement('div');
            div.className = 'speciality-group';
            const h = document.createElement('h6');
            h.textContent = spec + ' - ' + ((window.djOperatorSpecialityNames || {})[spec] || 'Ειδικότητα ' + spec);
            div.appendChild(h);
            const ul = document.createElement('ul');
            ul.className = 'selected-list';
            bySpec[spec].forEach(function (code) {
                const li = document.createElement('li');
                const name = (catalog[spec] || {})[code] || code;
                li.innerHTML = '<span class="subspeciality-id"></span> <span class="subspeciality-name"></span> <span class="subspeciality-group"></span>';
                li.children[0].textContent = code;
                li.children[1].textContent = name;
                li.children[2].textContent = 'Ομάδα ' + (groups[code] || 'A');
                ul.appendChild(li);
            });
            div.appendChild(ul);
            container.appendChild(div);
        });
    }

    /** Γεμίζει τον πίνακα υποειδικοτήτων για την επιλεγμένη ειδικότητα. */
    window.loadSubSpecialities = function (specialityId) {
        const container = document.getElementById('subSpecialityContainer');
        const tbody = document.getElementById('subSpecialitiesTableBody');
        if (!container || !tbody) return;

        tbody.innerHTML = '';
        const subs = catalog[specialityId] || {};

        if (!specialityId || !Object.keys(subs).length) {
            container.style.display = 'none';
            return;
        }
        container.style.display = 'block';

        Object.keys(subs).forEach(function (code) {
            const saved = state()[code];
            const tr = document.createElement('tr');

            const tdCode = document.createElement('td');
            tdCode.textContent = code;

            const tdName = document.createElement('td');
            tdName.textContent = subs[code];

            const tdCheck = document.createElement('td');
            const chk = document.createElement('input');
            chk.type = 'checkbox';
            chk.checked = !!(saved && saved.checked);
            chk.setAttribute('aria-label', 'Επιλογή ' + code);
            tdCheck.appendChild(chk);

            const tdGroup = document.createElement('td');
            const sel = document.createElement('select');
            [['A', 'Α (άνω 120 kW)'], ['B', 'Β (έως 120 kW)']].forEach(function (opt) {
                const o = document.createElement('option');
                o.value = opt[0];
                o.textContent = opt[1];
                sel.appendChild(o);
            });
            sel.value = (saved && saved.group === 'B') ? 'B' : 'A';
            sel.disabled = !chk.checked;
            sel.setAttribute('aria-label', 'Ομάδα ' + code);
            tdGroup.appendChild(sel);

            chk.addEventListener('change', function () {
                state()[code] = { checked: chk.checked, group: sel.value };
                sel.disabled = !chk.checked;
                serialize();
            });
            sel.addEventListener('change', function () {
                state()[code] = { checked: chk.checked, group: sel.value };
                serialize();
            });

            tr.appendChild(tdCode);
            tr.appendChild(tdName);
            tr.appendChild(tdCheck);
            tr.appendChild(tdGroup);
            tbody.appendChild(tr);
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        /*
         * Σειριοποίηση της αποθηκευμένης κατάστασης ΑΜΕΣΩΣ: τα κρυφά
         * πεδία ξεκινούσαν κενά, οπότε μια αποθήκευση προφίλ χωρίς να
         * αγγίξεις την καρτέλα χειριστή ΕΣΒΗΝΕ τις υποειδικότητες.
         */
        serialize();

        const specialitySelect = document.getElementById('operator_speciality');
        if (specialitySelect && specialitySelect.value) {
            window.loadSubSpecialities(specialitySelect.value);
        }
    });
})();
