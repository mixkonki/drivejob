/**
 * Άδειες χειριστή ΜΕ — v2 (25/08/2026).
 *
 * Ξαναγράφτηκε από το μηδέν: το παλιό script υποστήριζε ΜΙΑ ειδικότητα
 * ανά χειριστή (λάθος μοντέλο) και κρατούσε state σε κρυφά JSON πεδία
 * που έσβηναν δεδομένα. Τώρα: επαναλαμβανόμενα μπλοκ αδειών (ένα ανά
 * Ομάδα+Ειδικότητα), απλά πεδία φόρμας op_lic[N][...], και οι
 * υποειδικότητες renders ζωντανά από τον κατάλογο (window.djOperatorCatalog).
 */
(function () {
    'use strict';

    function catalog() {
        return window.djOperatorCatalog || {};
    }

    /** Γεμίζει τη λίστα υποειδικοτήτων ενός μπλοκ βάσει της ειδικότητάς του. */
    function renderSubs(item) {
        var select = item.querySelector('.op-speciality');
        var list = item.querySelector('.op-sub-list');
        if (!select || !list) { return; }

        var idx = item.dataset.idx;
        var spec = select.value;
        var subs = catalog()[spec] || {};

        var selected = [];
        try {
            selected = JSON.parse(item.dataset.selectedSubs || '[]');
        } catch (e) { selected = []; }

        var html = '';
        Object.keys(subs).forEach(function (code) {
            var checked = selected.indexOf(code) !== -1 ? 'checked' : '';
            html += '<div class="skill-item">'
                + '<input type="checkbox" class="skill-checkbox" name="op_lic[' + idx + '][subs][]" value="' + code + '" ' + checked + '>'
                + '<label class="skill-label"><strong>' + code + '</strong> ' + subs[code] + '</label>'
                + '</div>';
        });

        if (!html) {
            html = spec === '9'
                ? '<p class="form-hint">Η 9η ειδικότητα καλύπτει μηχανήματα πολλαπλών εργασιών — δεν έχει αριθμημένες υποειδικότητες.</p>'
                : '<p class="form-hint">Επίλεξε πρώτα ειδικότητα για να δεις τα μηχανήματά της.</p>';
        }

        list.innerHTML = html;
    }

    function wireItem(item) {
        var select = item.querySelector('.op-speciality');
        if (select) {
            select.addEventListener('change', function () {
                // Νέα ειδικότητα = άλλα μηχανήματα: οι παλιές επιλογές δεν ισχύουν.
                item.dataset.selectedSubs = '[]';
                renderSubs(item);
            });
        }

        item.querySelectorAll('input[name^="op_lic"][name$="[covers_all]"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                var wrap = item.querySelector('.op-sub-wrap');
                if (wrap) { wrap.style.display = this.value === '1' ? 'none' : ''; }
            });
        });

        var remove = item.querySelector('.op-lic-remove');
        if (remove) {
            remove.addEventListener('click', function () {
                item.remove();
            });
        }

        renderSubs(item);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.op-lic-item').forEach(wireItem);

        var addBtn = document.getElementById('addOpLic');
        var listEl = document.getElementById('opLicList');
        var tpl = document.getElementById('opLicTemplate');

        if (addBtn && listEl && tpl) {
            addBtn.addEventListener('click', function () {
                var idx = 'n' + Date.now();
                var holder = document.createElement('div');
                holder.innerHTML = tpl.innerHTML.replace(/__IDX__/g, idx);
                var item = holder.querySelector('.op-lic-item');
                if (!item) { return; }
                item.dataset.idx = idx;
                listEl.appendChild(item);
                wireItem(item);
            });
        }
    });
})();
