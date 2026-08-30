/**
 * Προσαρμογή φωτογραφίας προφίλ — μεγέθυνση & τοποθέτηση στον κύκλο.
 * (30/08/2026, μετά από αίτημα του Κώστα.)
 *
 * ΓΙΑΤΙ: το `object-fit: cover` έκοβε πάντα το κέντρο της φωτογραφίας.
 * Σε κατακόρυφη λήψη κινητού αυτό σημαίνει ότι το πρόσωπο μένει εκτός
 * κύκλου — ο οδηγός ανέβαζε φωτογραφία και έβλεπε τον λαιμό του.
 *
 * ΠΩΣ: μόλις επιλεγεί αρχείο ανοίγει ένας μικρός επεξεργαστής. Η εικόνα
 * σύρεται με ποντίκι ή δάχτυλο και μεγεθύνεται με slider. Στο «Εφαρμογή»
 * το ορατό τετράγωνο ζωγραφίζεται σε canvas 512×512, γίνεται JPEG blob
 * και μπαίνει ΠΙΣΩ στο ίδιο <input type="file"> μέσω DataTransfer.
 *
 * Έτσι ο server δεν αλλάζει καθόλου — δέχεται ένα κανονικό upload, απλώς
 * μικρότερο και ήδη κομμένο σωστά. Αν ο browser δεν υποστηρίζει
 * DataTransfer (παλιά Safari), ο επεξεργαστής δεν ανοίγει και το αρχείο
 * ανεβαίνει όπως πριν: καμία λειτουργία δεν χάνεται.
 */
(function () {
    'use strict';

    var OUTPUT_SIZE = 512;   // τελικό αρχείο (τετράγωνο)
    var STAGE_SIZE = 320;    // μέγεθος προεπισκόπησης

    function supportsDataTransfer() {
        try {
            var dt = new DataTransfer();
            return typeof dt.items === 'object';
        } catch (e) {
            return false;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var input = document.getElementById('profile_image');
        var editor = document.getElementById('avatarEditor');
        var canvas = document.getElementById('avatarCanvas');
        var zoom = document.getElementById('avatarZoom');
        var applyBtn = document.getElementById('avatarApply');
        var cancelBtn = document.getElementById('avatarCancel');
        var stage = document.getElementById('avatarStage');
        var preview = document.getElementById('avatarPreview');
        var placeholder = document.getElementById('avatarPlaceholder');
        var adjustBtn = document.getElementById('avatarAdjust');
        var hint = document.querySelector('.avatar-hint');
        // Η φωτογραφία που είναι ΟΝΤΩΣ αποθηκευμένη στον server — σημείο
        // επαναφοράς όταν ο οδηγός ακυρώσει μια προσαρμογή.
        var savedAvatar = adjustBtn ? (adjustBtn.dataset.current || '') : '';

        if (!input) { return; }

        // Χωρίς επεξεργαστή ή χωρίς DataTransfer: απλή προεπισκόπηση, όπως πριν.
        var canEdit = editor && canvas && zoom && supportsDataTransfer();

        var img = new Image();
        var state = { x: 0, y: 0, scale: 1, baseScale: 1, dragging: false, lastX: 0, lastY: 0 };
        var ctx = canvas ? canvas.getContext('2d') : null;

        function showPreviewOnly(file) {
            var r = new FileReader();
            r.onload = function (e) {
                if (preview) { preview.src = e.target.result; preview.style.display = ''; }
                if (placeholder) { placeholder.style.display = 'none'; }
                showAdjust(e.target.result);
            };
            r.readAsDataURL(file);
        }

        function draw() {
            if (!ctx) { return; }
            ctx.clearRect(0, 0, STAGE_SIZE, STAGE_SIZE);
            ctx.fillStyle = '#f3f4f6';
            ctx.fillRect(0, 0, STAGE_SIZE, STAGE_SIZE);
            var w = img.naturalWidth * state.scale;
            var h = img.naturalHeight * state.scale;
            ctx.drawImage(img, state.x, state.y, w, h);
        }

        function clampPosition() {
            var w = img.naturalWidth * state.scale;
            var h = img.naturalHeight * state.scale;
            // Η εικόνα δεν φεύγει από το πλαίσιο: πάντα καλύπτει τον κύκλο.
            state.x = Math.min(0, Math.max(STAGE_SIZE - w, state.x));
            state.y = Math.min(0, Math.max(STAGE_SIZE - h, state.y));
        }

        /**
         * Ανοίγει τον επεξεργαστή πάνω σε μια πηγή εικόνας (data: URL ή blob: URL).
         * Χωριστό από το openEditor(file) γιατί η ΥΠΑΡΧΟΥΣΑ φωτογραφία δεν είναι
         * αρχείο — έρχεται από τον server.
         */
        function openEditorFromSrc(src, onFail) {
            img.onload = function () {
                if (!img.naturalWidth) { if (onFail) { onFail(); } return; }
                // Αρχική κλίμακα: η μικρότερη που καλύπτει όλο το τετράγωνο.
                state.baseScale = Math.max(STAGE_SIZE / img.naturalWidth, STAGE_SIZE / img.naturalHeight);
                state.scale = state.baseScale;
                state.x = (STAGE_SIZE - img.naturalWidth * state.scale) / 2;
                state.y = (STAGE_SIZE - img.naturalHeight * state.scale) / 2;
                zoom.value = 100;
                editor.hidden = false;
                draw();
            };
            // Χαλασμένο ή μη αναγνώσιμο αρχείο: δεν κολλάει σιωπηλά ο επεξεργαστής.
            img.onerror = function () { if (onFail) { onFail(); } };
            img.src = src;
        }

        function openEditor(file) {
            var r = new FileReader();
            r.onload = function (e) {
                openEditorFromSrc(e.target.result, function () {
                    showPreviewOnly(file);   // ό,τι κι αν γίνει, το ανέβασμα προχωρά
                });
            };
            r.readAsDataURL(file);
        }

        function closeEditor() {
            editor.hidden = true;
        }

        /** Το κουμπί «Προσαρμογή» έχει νόημα μόνο όταν υπάρχει εικόνα στον κύκλο. */
        function showAdjust(src) {
            if (!adjustBtn) { return; }
            adjustBtn.dataset.current = src || '';
            adjustBtn.hidden = false;
        }

        // ── Είσοδος αρχείου ──────────────────────────────────────────────
        input.addEventListener('change', function () {
            var f = this.files && this.files[0];
            if (!f || !/^image\//.test(f.type)) { return; }
            // Το GIF μπορεί να είναι κινούμενο — δεν το πειράζουμε.
            if (canEdit && f.type !== 'image/gif') {
                openEditor(f);
            } else {
                showPreviewOnly(f);
            }
        });

        // Χωρίς επεξεργαστή δεν έχει νόημα να φαίνεται κουμπί που δεν κάνει τίποτα.
        if (!canEdit) {
            if (adjustBtn) { adjustBtn.hidden = true; }
            return;
        }

        // ── «Προσαρμογή» στην ΥΠΑΡΧΟΥΣΑ φωτογραφία ───────────────────────
        /*
         * Η εικόνα κατεβαίνει με fetch → blob → object URL αντί να μπει
         * κατευθείαν στο <img>. Λόγος: το canvas «μολύνεται» (tainted) από
         * εικόνα άλλης προέλευσης και το toBlob() πετάει SecurityError. Τα
         * uploads σερβίρονται μέσω CDN, οπότε δεν είναι δεδομένο ότι μένουν
         * στο ίδιο origin· το fetch same-origin επιστρέφει καθαρά bytes.
         */
        if (adjustBtn) {
            adjustBtn.addEventListener('click', function () {
                var src = adjustBtn.dataset.current;
                if (!src) { return; }

                adjustBtn.disabled = true;
                var done = function () { adjustBtn.disabled = false; };
                // Αν η αποθηκευμένη εικόνα δεν φορτώνει (σβήστηκε από τον
                // δίσκο, 404 από το CDN), ο οδηγός πρέπει να το μάθει —
                // όχι να πατά ένα κουμπί που «δεν κάνει τίποτα».
                var failed = function () {
                    done();
                    if (hint) {
                        hint.textContent = 'Η αποθηκευμένη φωτογραφία δεν φορτώθηκε. Ανεβάστε νέα.';
                        hint.classList.add('avatar-hint-active');
                    }
                };

                fetch(src, { credentials: 'same-origin' })
                    .then(function (r) {
                        if (!r.ok) { throw new Error('http ' + r.status); }
                        return r.blob();
                    })
                    .then(function (blob) {
                        var url = URL.createObjectURL(blob);
                        openEditorFromSrc(url, failed);
                        done();
                    })
                    .catch(function () {
                        // Εφεδρικά: απευθείας από το <img> — αν μολυνθεί το canvas
                        // το πιάνει το try/catch της «Εφαρμογής».
                        openEditorFromSrc(src, failed);
                        done();
                    });
            });
        }

        // ── Μετακίνηση (ποντίκι + αφή) ───────────────────────────────────
        function startDrag(x, y) { state.dragging = true; state.lastX = x; state.lastY = y; }
        function moveDrag(x, y) {
            if (!state.dragging) { return; }
            state.x += x - state.lastX;
            state.y += y - state.lastY;
            state.lastX = x;
            state.lastY = y;
            clampPosition();
            draw();
        }
        function endDrag() { state.dragging = false; }

        stage.addEventListener('mousedown', function (e) { e.preventDefault(); startDrag(e.clientX, e.clientY); });
        window.addEventListener('mousemove', function (e) { moveDrag(e.clientX, e.clientY); });
        window.addEventListener('mouseup', endDrag);

        stage.addEventListener('touchstart', function (e) {
            if (e.touches.length === 1) { startDrag(e.touches[0].clientX, e.touches[0].clientY); }
        }, { passive: true });
        stage.addEventListener('touchmove', function (e) {
            if (e.touches.length === 1) {
                e.preventDefault();
                moveDrag(e.touches[0].clientX, e.touches[0].clientY);
            }
        }, { passive: false });
        stage.addEventListener('touchend', endDrag);

        // ── Μεγέθυνση: κρατά σταθερό το κέντρο του κύκλου ────────────────
        zoom.addEventListener('input', function () {
            var factor = parseInt(this.value, 10) / 100;
            var newScale = state.baseScale * factor;
            var centerX = STAGE_SIZE / 2;
            var centerY = STAGE_SIZE / 2;
            var ratio = newScale / state.scale;
            state.x = centerX - (centerX - state.x) * ratio;
            state.y = centerY - (centerY - state.y) * ratio;
            state.scale = newScale;
            clampPosition();
            draw();
        });

        // ── Εφαρμογή: canvas → JPEG → πίσω στο file input ────────────────
        applyBtn.addEventListener('click', function () {
            var out = document.createElement('canvas');
            out.width = OUTPUT_SIZE;
            out.height = OUTPUT_SIZE;
            var octx = out.getContext('2d');
            var k = OUTPUT_SIZE / STAGE_SIZE;
            octx.fillStyle = '#ffffff';
            octx.fillRect(0, 0, OUTPUT_SIZE, OUTPUT_SIZE);
            octx.drawImage(
                img,
                state.x * k,
                state.y * k,
                img.naturalWidth * state.scale * k,
                img.naturalHeight * state.scale * k
            );

            var finish = function (dataUrl) {
                if (preview) { preview.src = dataUrl; preview.style.display = ''; }
                if (placeholder) { placeholder.style.display = 'none'; }
                showAdjust(dataUrl);   // η προσαρμοσμένη γίνεται η νέα «τρέχουσα»
                closeEditor();
                if (hint) {
                    hint.textContent = 'Η φωτογραφία προσαρμόστηκε — πατήστε «Αποθήκευση Αλλαγών» για να καταχωρηθεί.';
                    hint.classList.add('avatar-hint-active');
                }
            };

            try {
                out.toBlob(function (blob) {
                    if (!blob) { closeEditor(); return; }
                    try {
                        var file = new File([blob], 'profile.jpg', { type: 'image/jpeg' });
                        var dt = new DataTransfer();
                        dt.items.add(file);
                        input.files = dt.files;
                    } catch (e) {
                        // Αν αποτύχει, μένει το αρχικό αρχείο — τίποτα δεν χάνεται.
                    }
                    finish(out.toDataURL('image/jpeg', 0.9));
                }, 'image/jpeg', 0.9);
            } catch (e) {
                // SecurityError από μολυσμένο canvas (εικόνα άλλης προέλευσης):
                // δεν αλλάζουμε τίποτα, αλλά το λέμε αντί να «μη γίνεται κάτι».
                closeEditor();
                if (hint) {
                    hint.textContent = 'Η προσαρμογή δεν ήταν δυνατή. Ανεβάστε ξανά τη φωτογραφία.';
                    hint.classList.add('avatar-hint-active');
                }
            }
        });

        cancelBtn.addEventListener('click', function () {
            input.value = '';   // ακύρωση = καμία αλλαγή φωτογραφίας
            // Το κουμπί ξαναδείχνει την ΑΠΟΘΗΚΕΥΜΕΝΗ εικόνα, όχι αυτήν που
            // μόλις ακυρώθηκε — αλλιώς η επόμενη «Προσαρμογή» θα δούλευε
            // πάνω σε φωτογραφία που ο οδηγός απέρριψε.
            if (adjustBtn) {
                if (savedAvatar) {
                    adjustBtn.dataset.current = savedAvatar;
                    adjustBtn.hidden = false;
                } else {
                    adjustBtn.hidden = true;
                }
            }
            if (preview && !savedAvatar) {
                preview.removeAttribute('src');
                preview.style.display = 'none';
                if (placeholder) { placeholder.style.display = ''; }
            } else if (preview && savedAvatar) {
                preview.src = savedAvatar;
            }
            closeEditor();
        });
    });
})();
