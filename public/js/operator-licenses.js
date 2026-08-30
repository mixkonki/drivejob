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

    function isMixed(item) {
        var g = item.querySelector('.op-group');
        return g && g.value === 'M';
    }

    /**
     * Γεμίζει τη λίστα υποειδικοτήτων ενός μπλοκ βάσει της ειδικότητάς του.
     * Η ομάδα πάει ΑΝΑ υποειδικότητα: σε «μικτή» άδεια κάθε γραμμή έχει
     * δικό της Α΄/Β΄ (mini select) — σε ενιαία, το select μένει κρυφό.
     */
    function renderSubs(item) {
        var select = item.querySelector('.op-speciality');
        var list = item.querySelector('.op-sub-list');
        if (!select || !list) { return; }

        var idx = item.dataset.idx;
        var spec = select.value;
        var subs = catalog()[spec] || {};
        var mixed = isMixed(item);

        var selected = {};
        try {
            var parsed = JSON.parse(item.dataset.selectedSubs || '{}');
            if (Array.isArray(parsed)) {
                parsed.forEach(function (c) { selected[c] = 'A'; });
            } else if (parsed && typeof parsed === 'object') {
                selected = parsed;
            }
        } catch (e) { selected = {}; }

        var html = '';
        Object.keys(subs).forEach(function (code) {
            var checked = Object.prototype.hasOwnProperty.call(selected, code) ? 'checked' : '';
            var grp = selected[code] === 'B' ? 'B' : 'A';
            html += '<div class="skill-item">'
                + '<input type="checkbox" class="skill-checkbox" name="op_lic[' + idx + '][subs][]" value="' + code + '" ' + checked + '>'
                + '<label class="skill-label"><strong>' + code + '</strong> ' + subs[code] + '</label>'
                + '<select class="op-sub-group" name="op_lic[' + idx + '][sub_group][' + code + ']"'
                + (mixed ? '' : ' style="display:none;"') + '>'
                + '<option value="A"' + (grp === 'A' ? ' selected' : '') + '>Α΄</option>'
                + '<option value="B"' + (grp === 'B' ? ' selected' : '') + '>Β΄</option>'
                + '</select>'
                + '</div>';
        });

        if (!html) {
            html = spec === '9'
                ? '<p class="form-hint">Η 9η ειδικότητα καλύπτει μηχανήματα πολλαπλών εργασιών — δεν έχει αριθμημένες υποειδικότητες.</p>'
                : '<p class="form-hint">Επίλεξε πρώτα ειδικότητα για να δεις τα μηχανήματά της.</p>';
        }

        list.innerHTML = html;
    }

    /** Μικτή ομάδα = υποχρεωτικά «συγκεκριμένα μηχανήματα» (η σφραγίδα
        «σύνολο ειδικότητας» εκδίδεται πάντα για ΜΙΑ ομάδα). */
    function applyGroupMode(item) {
        var mixed = isMixed(item);
        item.querySelectorAll('.op-sub-group').forEach(function (sel) {
            sel.style.display = mixed ? '' : 'none';
        });
        var allRadio = item.querySelector('input[name$="[covers_all]"][value="1"]');
        var someRadio = item.querySelector('input[name$="[covers_all]"][value="0"]');
        if (allRadio) {
            allRadio.disabled = mixed;
            if (mixed && allRadio.checked && someRadio) {
                someRadio.checked = true;
                someRadio.dispatchEvent(new Event('change'));
            }
        }
    }

    function wireItem(item) {
        var select = item.querySelector('.op-speciality');
        if (select) {
            select.addEventListener('change', function () {
                // Νέα ειδικότητα = άλλα μηχανήματα: οι παλιές επιλογές δεν ισχύουν.
                item.dataset.selectedSubs = '{}';
                renderSubs(item);
                applyGroupMode(item);
            });
        }

        var groupSel = item.querySelector('.op-group');
        if (groupSel) {
            groupSel.addEventListener('change', function () {
                applyGroupMode(item);
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
        applyGroupMode(item);
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
