/**
 * Γλωσσικές ικανότητες — άμεση αποθήκευση ανά γλώσσα.
 *
 * «Προσθήκη» → POST /drivers/languages → η γλώσσα εμφανίζεται σωσμένη.
 * «×» → POST /drivers/languages/delete/{id}. Ίδια φιλοσοφία με την
 * προϋπηρεσία οχημάτων: ό,τι βλέπεις είναι ήδη στη βάση.
 */
(function () {
    'use strict';

    const BASE = (window.BASE_URL || '/');
    const csrf = () => (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    const list = document.getElementById('dj-lang-list');
    const nameInput = document.getElementById('dj-lang-name');
    const levelSel = document.getElementById('dj-lang-level');
    const addBtn = document.getElementById('dj-lang-add');
    const msg = document.getElementById('dj-lang-msg');

    if (!list || !nameInput || !addBtn) return;

    function showMsg(text, ok) {
        msg.textContent = text;
        msg.style.display = 'block';
        msg.style.background = ok ? '#dcfce7' : '#fee2e2';
        msg.style.color = ok ? '#166534' : '#991b1b';
        if (ok) setTimeout(function () { msg.style.display = 'none'; }, 3500);
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

    function chip(row) {
        const li = document.createElement('li');
        li.dataset.id = row.id;
        li.style.cssText = 'display:flex; align-items:center; gap:.45rem; background:#f3f4f6; border:1px solid #e5e7eb; border-radius:999px; padding:.3rem .4rem .3rem .9rem;';
        const shortLevel = { native: 'Μητρική', fluent: 'Άριστα', good: 'Καλά', basic: 'Βασικά' }[row.level] || row.level;
        const span = document.createElement('span');
        const strong = document.createElement('strong');
        strong.textContent = row.name;
        const lvl = document.createElement('span');
        lvl.style.cssText = 'color:#6b7280; font-size:.85em;';
        lvl.textContent = ' · ' + shortLevel;
        span.appendChild(strong);
        span.appendChild(lvl);
        const del = document.createElement('button');
        del.type = 'button';
        del.className = 'dj-lang-del';
        del.dataset.id = row.id;
        del.title = 'Διαγραφή';
        del.textContent = '×';
        del.style.cssText = 'border:0; background:#e5e7eb; color:#6b7280; border-radius:50%; width:22px; height:22px; line-height:1; cursor:pointer;';
        li.appendChild(span);
        li.appendChild(del);
        return li;
    }

    async function addLanguage() {
        const name = nameInput.value.trim();
        if (!name) { showMsg('Γράψε το όνομα της γλώσσας.', false); nameInput.focus(); return; }

        const params = new URLSearchParams();
        params.set('language_name', name);
        params.set('level', levelSel.value);

        addBtn.disabled = true;
        try {
            const data = await post(BASE + 'drivers/languages', params);
            if (!data || data.success !== true) {
                showMsg((data && data.message) || 'Η αποθήκευση απέτυχε.', false);
                return;
            }
            const row = data.data.row;
            // Αν η γλώσσα υπήρχε (ενημέρωση επιπέδου), αντικατάσταση του chip.
            const existing = list.querySelector('li[data-id="' + row.id + '"]');
            if (existing) existing.remove();
            list.appendChild(chip(row));
            nameInput.value = '';
            showMsg('Αποθηκεύτηκε ✓', true);
            nameInput.focus();
        } catch (e) {
            showMsg('Πρόβλημα σύνδεσης — δοκίμασε ξανά.', false);
        } finally {
            addBtn.disabled = false;
        }
    }

    addBtn.addEventListener('click', addLanguage);
    nameInput.addEventListener('keydown', function (ev) {
        if (ev.key === 'Enter') { ev.preventDefault(); addLanguage(); }
    });

    list.addEventListener('click', async function (ev) {
        const btn = ev.target.closest('.dj-lang-del');
        if (!btn) return;
        btn.disabled = true;
        try {
            const data = await post(BASE + 'drivers/languages/delete/' + btn.dataset.id, new URLSearchParams());
            if (!data || data.success !== true) {
                showMsg((data && data.message) || 'Η διαγραφή απέτυχε.', false);
                btn.disabled = false;
                return;
            }
            const li = btn.closest('li');
            if (li) li.remove();
            showMsg('Διαγράφηκε ✓', true);
        } catch (e) {
            showMsg('Πρόβλημα σύνδεσης — δοκίμασε ξανά.', false);
            btn.disabled = false;
        }
    });
})();
