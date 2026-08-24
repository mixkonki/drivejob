/**
 * Σκανάρισμα διπλώματος με OCR — ο χειριστής που δεν γράφτηκε ποτέ.
 *
 * ══════════════════════════════════════════════════════════════════════════
 *  ΤΙ ΕΛΕΙΠΕ
 * ══════════════════════════════════════════════════════════════════════════
 *
 * Η φόρμα είχε δύο κουμπιά «Σκανάρισμα με OCR» και φόρτωνε το Tesseract —
 * αλλά ΚΑΝΕΝΑ script δεν έδενε το κλικ με το σκανάρισμα. Ο οδηγός ανέβαζε
 * τη φωτογραφία, πατούσε το κουμπί, και δεν γινόταν απολύτως τίποτα.
 *
 * ══════════════════════════════════════════════════════════════════════════
 *  ΠΩΣ ΔΟΥΛΕΥΕΙ
 * ══════════════════════════════════════════════════════════════════════════
 *
 * 1. Παίρνει την εικόνα από το διπλανό input file (χωρίς να τη στείλει
 *    πουθενά — το OCR τρέχει ΣΤΟΝ BROWSER, η φωτογραφία του διπλώματος
 *    δεν φεύγει από τη συσκευή για το σκανάρισμα).
 * 2. Προεπεξεργασία σε canvas: μεγέθυνση + γκρι + αντίθεση. Οι φωτογραφίες
 *    κινητού είναι λοξές και σκοτεινές· χωρίς αυτό το βήμα το OCR βλέπει
 *    θόρυβο.
 * 3. Tesseract (ελληνικά + αγγλικά) πάνω στο επεξεργασμένο καρέ.
 * 4. Από το κείμενο βγαίνουν: αριθμός άδειας (9 ψηφία, πεδίο 5), ημερομηνία
 *    λήξης (πεδίο 4β — η ΜΕΤΑΓΕΝΕΣΤΕΡΗ ημερομηνία του εντύπου), κωδικοί
 *    στήλης 12, κατηγορίες.
 * 5. ΠΡΟΣΥΜΠΛΗΡΩΝΕΙ τα πεδία — δεν αποθηκεύει τίποτα. Ο οδηγός βλέπει τι
 *    βρέθηκε, το διορθώνει, και αποθηκεύει ο ίδιος. Το OCR σε φωτογραφία
 *    διπλώματος κάνει λάθη· ένα σύστημα που θα αποθήκευε ό,τι διάβασε θα
 *    γέμιζε τη βάση με σκουπίδια που μοιάζουν αληθινά.
 */
(function () {
    'use strict';

    /*
     * ΟΛΑ ΤΑ ΑΡΧΕΙΑ ΤΟΥ OCR ΣΕΡΒΙΡΟΝΤΑΙ ΑΠΟ ΕΜΑΣ — κανένα CDN.
     *
     * Το Tesseract χρειάζεται τέσσερα κομμάτια: τη βιβλιοθήκη, τον worker,
     * τον πυρήνα WebAssembly και τα γλωσσικά μοντέλα (ελληνικά+αγγλικά,
     * έκδοση best_int — μικρότερα ΚΑΙ ακριβέστερα από τα default). Αν
     * φορτώνονταν από CDN θα θέλαμε χαλάρωμα του CSP και θα σπάγαμε όποτε
     * το CDN έχει πρόβλημα. Στο ~12MB του φακέλου js/tesseract/ απαντά το
     * ότι κατεβαίνουν ΜΟΝΟ όταν κάποιος πατήσει «Σκανάρισμα» — ποτέ στη
     * φόρτωση της σελίδας.
     */
    var TESSERACT_BASE = (window.BASE_URL || '/') + 'js/tesseract/';
    var TESSERACT_CDN = TESSERACT_BASE + 'tesseract5.min.js';

    /** Φόρτωση του Tesseract v5 μία φορά, την πρώτη φορά που θα χρειαστεί. */
    var tesseractReady = null;
    function loadTesseract() {
        if (tesseractReady) {
            return tesseractReady;
        }
        tesseractReady = new Promise(function (resolve, reject) {
            if (window.Tesseract && typeof window.Tesseract.createWorker === 'function') {
                resolve(window.Tesseract);
                return;
            }
            var s = document.createElement('script');
            s.src = TESSERACT_CDN;
            s.onload = function () {
                if (window.Tesseract && typeof window.Tesseract.createWorker === 'function') {
                    resolve(window.Tesseract);
                } else {
                    reject(new Error('Το Tesseract φορτώθηκε αλλά δεν έχει createWorker'));
                }
            };
            s.onerror = function () {
                reject(new Error('Αποτυχία φόρτωσης του Tesseract από το CDN'));
            };
            document.head.appendChild(s);
        });
        return tesseractReady;
    }

    /**
     * Προεπεξεργασία: μεγέθυνση έως 1800px, γκρι, αύξηση αντίθεσης.
     */
    function preprocess(file, enhance) {
        return new Promise(function (resolve, reject) {
            var img = new Image();
            var url = URL.createObjectURL(file);
            img.onload = function () {
                URL.revokeObjectURL(url);
                /*
                 * Πρώτο πέρασμα (enhance=false): ΦΥΣΙΚΟ μέγεθος. Η
                 * επανασχηματοποίηση του canvas θολώνει τα ψηφία — μετρήθηκε
                 * ότι και μόνο το upscale αρκούσε να χαλάσει τον αριθμό.
                 * Κλιμάκωση μόνο στα άκρα: τεράστιες φωτογραφίες κινητού
                 * κατεβαίνουν (ταχύτητα), μικροσκοπικές ανεβαίνουν.
                 */
                var scale = 1;
                var largest = Math.max(img.width, img.height);
                if (largest > 2600) {
                    scale = 2600 / largest;
                } else if (enhance && largest < 1800) {
                    scale = Math.min(1800 / largest, 2.5);
                } else if (largest < 900) {
                    scale = 900 / largest;
                }
                var w = Math.round(img.width * scale);
                var h = Math.round(img.height * scale);
                var canvas = document.createElement('canvas');
                canvas.width = w;
                canvas.height = h;
                var ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, w, h);

                if (enhance) {
                    var data = ctx.getImageData(0, 0, w, h);
                    var px = data.data;
                    var contrast = 1.35;
                    var intercept = 128 * (1 - contrast);
                    for (var i = 0; i < px.length; i += 4) {
                        var g = 0.299 * px[i] + 0.587 * px[i + 1] + 0.114 * px[i + 2];
                        g = g * contrast + intercept;
                        g = g < 0 ? 0 : (g > 255 ? 255 : g);
                        px[i] = px[i + 1] = px[i + 2] = g;
                    }
                    ctx.putImageData(data, 0, 0);
                }
                resolve(canvas);
            };
            img.onerror = function () {
                URL.revokeObjectURL(url);
                reject(new Error('Η εικόνα δεν διαβάστηκε'));
            };
            img.src = url;
        });
    }

    /** Εξαγωγή δομημένων πεδίων από το ωμό κείμενο του OCR. */
    function parseLicense(text) {
        var out = { number: null, expiry: null, codes: [], categories: [] };
        var clean = text.replace(/\s+/g, ' ');

        // Πεδίο 5: αριθμός άδειας — 9 ψηφία, ίσως με κενά από το OCR
        // («123 456 789»). Οι ημερομηνίες αφαιρούνται ΠΡΙΝ την αναζήτηση,
        // αλλιώς το «12.06.2019 12.06.2034» κολλάει σε ψευδο-εννιάρι.
        var numSource = clean.replace(/\b\d{2}[.\/-]\d{2}[.\/-]\d{4}\b/g, ' ');
        var candRe = /\d[\d ]{7,16}\d/g;
        var cand;
        outer:
        while ((cand = candRe.exec(numSource)) !== null) {
            /*
             * Ο υποψήφιος μπορεί να κουβαλά γειτονικά ψηφία («123 456 789 4»
             * όπου το 4 είναι ο επόμενος αριθμός πεδίου). Δοκιμάζονται όλες
             * οι ΣΥΝΕΧΟΜΕΝΕΣ ενώσεις ομάδων — η πρώτη που δίνει ακριβώς 9
             * ψηφία είναι ο αριθμός της άδειας.
             */
            var groups = cand[0].split(' ').filter(function (g) { return g !== ''; });
            for (var gi = 0; gi < groups.length; gi++) {
                var joined = '';
                for (var gj = gi; gj < groups.length; gj++) {
                    joined += groups[gj];
                    if (joined.length === 9) {
                        out.number = joined;
                        break outer;
                    }
                    if (joined.length > 9) {
                        break;
                    }
                }
            }
        }

        // Ημερομηνίες dd.mm.yyyy ή dd/mm/yyyy ή dd-mm-yyyy.
        // Στο έντυπο: 4α = έκδοση, 4β = λήξη. Η λήξη είναι η ΜΕΤΑΓΕΝΕΣΤΕΡΗ.
        var dates = [];
        var re = /\b(\d{2})[.\/-](\d{2})[.\/-](\d{4})\b/g;
        var m;
        while ((m = re.exec(clean)) !== null) {
            var d = parseInt(m[1], 10), mo = parseInt(m[2], 10), y = parseInt(m[3], 10);
            if (d >= 1 && d <= 31 && mo >= 1 && mo <= 12 && y > 1990 && y < 2100) {
                dates.push(new Date(y, mo - 1, d));
            }
        }
        if (dates.length) {
            dates.sort(function (a, b) { return a - b; });
            var latest = dates[dates.length - 1];
            if (latest > new Date()) {
                var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
                out.expiry = latest.getFullYear() + '-' + pad(latest.getMonth() + 1) + '-' + pad(latest.getDate());
            }
        }

        // Κωδικοί στήλης 12: μορφή NN.NN (01.01, 01.06) και το 95 (ΠΕΙ).
        //
        // ΠΑΓΙΔΑ: το «12.06» μέσα στο «12.06.2034» ΔΕΝ είναι κωδικός —
        // είναι η αρχή ημερομηνίας. Πριν ψάξουμε κωδικούς, σβήνουμε από το
        // κείμενο κάθε πλήρη ημερομηνία, αλλιώς κάθε δίπλωμα «αποκτά»
        // δύο ανύπαρκτους κωδικούς από τις ημερομηνίες έκδοσης/λήξης.
        var noDates = clean.replace(/\b\d{2}[.\/-]\d{2}[.\/-]\d{4}\b/g, ' ');
        var codeRe = /\b(\d{2}\.\d{2}|95|78)\b/g;
        var seen = {};
        while ((m = codeRe.exec(noDates)) !== null) {
            if (!seen[m[1]]) { seen[m[1]] = true; out.codes.push(m[1]); }
        }

        /*
         * Κατηγορίες. Δύο τρόποι, γιατί το OCR άλλοτε κρατά τα κενά
         * («B C CE») κι άλλοτε τα καταπίνει («BCCE»):
         *
         *  1. Αυτόνομες λέξεις — ο απλός δρόμος.
         *  2. Τεμαχισμός κολλημένων: κάθε συστάδα από γράμματα κατηγοριών
         *     δοκιμάζεται να «σπάσει» σε γνωστούς κωδικούς, με προτίμηση
         *     στον μακρύτερο (C1E πριν από C1 πριν από C). Το «BCCE»
         *     γίνεται B + C + CE — αυτό ακριβώς που γράφει το δίπλωμα.
         *     Αν η συστάδα ΔΕΝ τεμαχίζεται πλήρως σε κωδικούς, απορρίπτεται
         *     ολόκληρη — καλύτερα να λείψει μια κατηγορία παρά να
         *     εφευρεθεί.
         */
        var cats = ['C1E', 'D1E', 'AM', 'A1', 'A2', 'B1', 'BE', 'C1', 'CE', 'D1', 'DE', 'A', 'B', 'C', 'D'];
        var found = {};

        cats.forEach(function (c) {
            var r = new RegExp('(^|[^A-Z0-9])' + c + '($|[^A-Z0-9E])');
            if (r.test(clean.toUpperCase())) { found[c] = true; }
        });

        var runRe = /\b[ABCDEM12]{2,8}\b/g;
        var run;
        while ((run = runRe.exec(clean.toUpperCase())) !== null) {
            var token = run[0];
            var pos = 0;
            var pieces = [];
            while (pos < token.length) {
                var matched = null;
                for (var ci = 0; ci < cats.length; ci++) {
                    if (token.substring(pos, pos + cats[ci].length) === cats[ci]) {
                        matched = cats[ci];
                        break;
                    }
                }
                if (matched === null) {
                    pieces = null;
                    break;
                }
                pieces.push(matched);
                pos += matched.length;
            }
            /*
             * Φρένο στα ψευδώς θετικά: μια αγγλική λέξη όπως «CAB» τεμαχίζεται
             * «επιτυχώς» σε C+A+B. Δεκτός μόνο ο τεμαχισμός που περιέχει
             * τουλάχιστον έναν ΠΟΛΥΨΗΦΙΟ κωδικό (CE, C1, BE, AM…) — τυχαία
             * λέξη δεν σχηματίζει τέτοιον, ενώ το κολλημένο «BCCE» τον έχει.
             */
            var hasMultiChar = pieces && pieces.some(function (c) { return c.length >= 2; });
            if (pieces && pieces.length > 1 && hasMultiChar) {
                pieces.forEach(function (c) { found[c] = true; });
            }
        }

        out.categories = Object.keys(found);

        return out;
    }

    /** Το panel αποτελεσμάτων κάτω από το κουμπί. */
    function showResult(button, html, isError) {
        var panel = button.parentNode.querySelector('.ocr-result');
        if (!panel) {
            panel = document.createElement('div');
            panel.className = 'ocr-result';
            panel.style.cssText = 'margin-top:8px; padding:10px 12px; border-radius:6px; font-size:.85rem; line-height:1.5;';
            button.parentNode.appendChild(panel);
        }
        panel.style.background = isError ? '#fee2e2' : '#f0fdf4';
        panel.style.border = isError ? '1px solid #fca5a5' : '1px solid #86efac';
        panel.style.color = isError ? '#991b1b' : '#166534';
        panel.innerHTML = html;
    }

    function setBusy(button, label) {
        if (!button.dataset.originalHtml) {
            button.dataset.originalHtml = button.innerHTML;
        }
        button.disabled = true;
        button.textContent = label;
    }

    function setIdle(button) {
        button.disabled = false;
        if (button.dataset.originalHtml) {
            button.innerHTML = button.dataset.originalHtml;
        }
    }

    /**
     * Τσεκάρει την κατηγορία στον πίνακα αδειών — ΜΟΝΟ τσεκάρει, ποτέ δεν
     * ξε-τσεκάρει: αν ο οδηγός έχει ήδη δηλώσει κατηγορία που το OCR δεν
     * διάβασε, η αποτυχία του OCR δεν επιτρέπεται να του τη σβήσει.
     */
    function checkCategory(code) {
        var box = document.querySelector('input[name="license_types[]"][value="' + code + '"]');
        if (!box) {
            return false;
        }
        if (!box.checked) {
            box.checked = true;
            box.dispatchEvent(new Event('change', { bubbles: true }));
        }
        return true;
    }

    /**
     * Ανοίγει την ενότητα αδειών: τσεκάρει το «Διαθέτω άδεια οδήγησης» και
     * εμφανίζει τον πίνακα. Χωρίς αυτό, το OCR συμπλήρωνε πεδία μέσα σε
     * κρυφό (hidden) τμήμα και ο οδηγός δεν έβλεπε τίποτα να τσεκάρεται.
     */
    function ensureLicenseSectionOpen() {
        var master = document.getElementById('driving_license');
        if (master && !master.checked) {
            master.checked = true;
            master.dispatchEvent(new Event('change', { bubbles: true }));
        }
        var tab = document.getElementById('driving_license_tab');
        if (tab) {
            tab.classList.remove('hidden');
        }
    }

    function fillIfEmpty(id, value) {
        var el = document.getElementById(id);
        if (el && value && !el.value) {
            el.value = value;
            el.dispatchEvent(new Event('change', { bubbles: true }));
            return true;
        }
        return false;
    }

    async function scan(button, fileInput) {
        var file = fileInput.files && fileInput.files[0];
        if (!file) {
            showResult(button, 'Διάλεξε πρώτα τη φωτογραφία του διπλώματος στο πεδίο από πάνω, και μετά πάτησε «Σκανάρισμα».', true);
            return;
        }

        try {
            setBusy(button, 'Φόρτωση OCR…');
            var T = await loadTesseract();

            setBusy(button, 'Σκανάρισμα… (10–30″)');
            var worker = await T.createWorker(['ell', 'eng'], 1, {
                workerPath: TESSERACT_BASE + 'worker.min.js',
                corePath: TESSERACT_BASE,
                langPath: TESSERACT_BASE + 'lang',
                logger: function (ev) {
                    if (ev.status === 'recognizing text' && ev.progress) {
                        setBusy(button, 'Σκανάρισμα… ' + Math.round(ev.progress * 100) + '%');
                    }
                }
            });

            /*
             * ΔΥΟ ΠΕΡΑΣΜΑΤΑ, ΜΕΤΡΗΜΕΝΑ.
             *
             * Πρώτο πέρασμα στην ΑΥΘΕΝΤΙΚΗ εικόνα: σε καθαρή φωτογραφία το
             * φίλτρο αντίθεσης ΧΑΛΑΕΙ την ακρίβεια (μετρήθηκε: το 123456789
             * διαβαζόταν 1234567809 μετά το φίλτρο). Αν λείπουν βασικά
             * πεδία, δεύτερο πέρασμα με ενίσχυση — εκεί βοηθά τις σκοτεινές
             * φωτογραφίες κινητού. Κρατάμε ό,τι βρέθηκε από τα δύο.
             */
            var plain = await preprocess(file, false);
            var result = await worker.recognize(plain);
            var parsed = parseLicense(result.data.text || '');

            if (parsed.number || parsed.expiry || parsed.codes.length || parsed.categories.length) {
                ensureLicenseSectionOpen();
            }

            if (!parsed.number || !parsed.expiry) {
                setBusy(button, 'Δεύτερο πέρασμα…');
                var enhanced = await preprocess(file, true);
                var second = await worker.recognize(enhanced);
                var parsed2 = parseLicense(second.data.text || '');
                parsed.number = parsed.number || parsed2.number;
                parsed.expiry = parsed.expiry || parsed2.expiry;
                if (!parsed.codes.length) { parsed.codes = parsed2.codes; }
                if (!parsed.categories.length) { parsed.categories = parsed2.categories; }
            }

            await worker.terminate();
            var lines = [];

            if (parsed.number && fillIfEmpty('license_number', parsed.number)) {
                lines.push('Αριθμός άδειας: <strong>' + parsed.number + '</strong> — συμπληρώθηκε');
            } else if (parsed.number) {
                lines.push('Αριθμός άδειας: <strong>' + parsed.number + '</strong> (το πεδίο είχε ήδη τιμή — δεν άλλαξε)');
            }

            if (parsed.expiry && fillIfEmpty('license_document_expiry', parsed.expiry)) {
                lines.push('Λήξη εντύπου (4β): <strong>' + parsed.expiry.split('-').reverse().join('/') + '</strong> — συμπληρώθηκε');
            }

            if (parsed.codes.length) {
                var codesText = parsed.codes.join(', ');
                if (fillIfEmpty('license_codes', codesText)) {
                    lines.push('Κωδικοί στήλης 12: <strong>' + codesText + '</strong> — συμπληρώθηκαν');
                } else {
                    lines.push('Κωδικοί στήλης 12: <strong>' + codesText + '</strong>');
                }
            }

            if (parsed.categories.length) {
                ensureLicenseSectionOpen();
                var ticked = [];
                var missed = [];
                parsed.categories.forEach(function (code) {
                    if (checkCategory(code)) {
                        ticked.push(code);
                    } else {
                        missed.push(code);
                    }
                });
                if (ticked.length) {
                    lines.push('Κατηγορίες: <strong>' + ticked.join(', ') + '</strong> — τσεκαρίστηκαν στον πίνακα. '
                        + 'Συμπλήρωσε την ημερομηνία λήξης καθεμιάς (στήλη 11 στο πίσω μέρος).');
                }
                if (missed.length) {
                    lines.push('Διαβάστηκαν αλλά δεν βρέθηκαν στον πίνακα: ' + missed.join(', '));
                }
            }

            if (!lines.length) {
                showResult(button,
                    'Δεν διαβάστηκε κάτι αξιοποιήσιμο. Δοκίμασε φωτογραφία με καλύτερο φωτισμό, '
                    + 'κάθετη λήψη χωρίς γωνία, και το δίπλωμα να πιάνει όλο το κάδρο. '
                    + 'Ή συμπλήρωσε τα πεδία με το χέρι — τα βλέπεις πάνω στο έντυπο.', true);
            } else {
                lines.push('<span style="color:#4d7c0f;">Έλεγξε τις τιμές πριν την αποθήκευση — το σκανάρισμα βοηθά, δεν αποφασίζει.</span>');
                showResult(button, lines.join('<br>'), false);
            }
        } catch (err) {
            showResult(button,
                'Το σκανάρισμα απέτυχε (' + (err && err.message ? err.message : 'άγνωστο σφάλμα') + '). '
                + 'Συμπλήρωσε τα πεδία με το χέρι — η φωτογραφία θα αποθηκευτεί κανονικά.', true);
        } finally {
            setIdle(button);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Κάθε κουμπί .btn-scan δουλεύει με το input file της ίδιας ομάδας.
        document.querySelectorAll('.btn-scan').forEach(function (button) {
            var group = button.closest('.form-group') || button.parentNode;
            var fileInput = group.querySelector('input[type="file"]');
            if (!fileInput) {
                return;
            }
            button.addEventListener('click', function (e) {
                e.preventDefault();
                scan(button, fileInput);
            });
        });
    });
})();
