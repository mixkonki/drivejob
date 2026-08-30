/**
 * ΑΚΤΙΝΑ ΕΡΓΑΣΙΑΣ — χάρτης, κύκλος, κάλυψη. (30/08/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΙ ΛΥΝΕΙ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Ο χάρτης στο προφίλ έδειχνε απλώς πού μένει ο οδηγός — πληροφορία που
 * ο ίδιος γνωρίζει. Ο σκοπός του ήταν άλλος: να δείχνει ΤΗΝ ΑΚΤΙΝΑ μέσα
 * στην οποία δέχεται να εργαστεί. Αυτό κάνει τώρα.
 *
 * Τρία πράγματα ταυτόχρονα:
 *
 *  1. ΟΠΤΙΚΟΠΟΙΗΣΗ — ο κύκλος μεγαλώνει ζωντανά καθώς σύρεται ο δείκτης.
 *     Τα «100 χλμ» είναι αφηρημένα· ο κύκλος πάνω στον χάρτη όχι.
 *
 *  2. ΚΑΛΥΨΗ ΣΕ ΟΝΟΜΑΤΑ — «Καλύπτεις Κιλκίς, Βέροια, Σέρρες, Κατερίνη».
 *     Ο οδηγός σκέφτεται σε πόλεις, όχι σε χιλιόμετρα: έτσι καταλαβαίνει
 *     αμέσως αν η ακτίνα του είναι σωστή. Υπολογίζεται τοπικά με τον
 *     τύπο haversine πάνω σε ενσωματωμένο πίνακα πόλεων — καμία κλήση
 *     API, δουλεύει και χωρίς δίκτυο.
 *
 *  3. ΣΙΩΠΗΛΗ ΣΥΜΠΛΗΡΩΣΗ ΣΥΝΤΕΤΑΓΜΕΝΩΝ — οι στήλες latitude/longitude
 *     του οδηγού ήταν NULL για όλους, ενώ το MatchingModel τις
 *     χρειάζεται για να υπολογίσει απόσταση. Εδώ γεωκωδικοποιείται η
 *     έδρα μία φορά και οι τιμές μπαίνουν σε κρυφά πεδία της φόρμας.
 *     Ο οδηγός ρυθμίζει μια ακτίνα· η πλατφόρμα αποκτά το κλειδί του
 *     ταιριάσματος.
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ LEAFLET ΚΑΙ ΟΧΙ GOOGLE MAPS (31/08/2026)
 * ══════════════════════════════════════════════════════════════════════
 *
 * Ο χάρτης Google έβγαζε στην παραγωγή «Αυτή η σελίδα δεν μπορεί να
 * φορτώσει σωστά τους Χάρτες Google» με υδατογράφημα «For development
 * purposes only»: το κλειδί δεν έχει ενεργή χρέωση στο Google Cloud.
 * Κάθε οδηγός έβλεπε σπασμένο χάρτη και ένα popup σφάλματος.
 *
 * Τρεις λόγοι που δεν το λύσαμε ενεργοποιώντας χρέωση:
 *
 *  1. ΚΟΣΤΟΣ ΠΟΥ ΜΕΓΑΛΩΝΕΙ. Το Maps JavaScript API χρεώνεται ανά
 *     φόρτωση. Με χίλιους οδηγούς που ανοίγουν το προφίλ τους, ο
 *     λογαριασμός δεν είναι μηδέν — για έναν κύκλο πάνω σε χάρτη.
 *  2. ΕΚΤΕΘΕΙΜΕΝΟ ΚΛΕΙΔΙ. Το κλειδί ήταν γραμμένο μέσα στα views, ορατό
 *     σε κάθε επισκέπτη. Χωρίς περιορισμό referrer, οποιοσδήποτε το
 *     χρησιμοποιεί και χρεώνεται ο λογαριασμός μας.
 *  3. ΕΞΑΡΤΗΣΗ ΠΟΥ ΣΠΑΕΙ ΣΙΩΠΗΛΑ. Μια λήξη κάρτας στο Google Cloud και
 *     ο χάρτης σβήνει σε όλη την πλατφόρμα, χωρίς καμία ειδοποίηση.
 *
 * Το Leaflet με πλακίδια OpenStreetMap είναι δωρεάν, χωρίς κλειδί και
 * χωρίς λογαριασμό. Σερβίρεται ΤΟΠΙΚΑ από το public/vendor/leaflet/ —
 * καμία εξάρτηση από CDN, καμία αλλαγή στο CSP.
 *
 * ΧΩΡΙΣ ΧΑΡΤΗ: αν για οποιονδήποτε λόγο δεν φορτώσει το Leaflet, ο
 * δείκτης και η λίστα πόλεων δουλεύουν κανονικά — μόνο ο χάρτης λείπει.
 * Καμία λειτουργία δεν εξαρτάται από αυτόν.
 */
(function () {
    'use strict';

    /*
     * Πρωτεύουσες νομών και μεγάλα αστικά κέντρα. Χρησιμοποιούνται ΜΟΝΟ
     * για να ονοματίσουν την κάλυψη — δεν είναι μητρώο, δεν χρειάζεται
     * να είναι πλήρης. Συντεταγμένες κέντρου πόλης.
     */
    var CITIES = [
        ['Αθήνα', 37.9838, 23.7275], ['Πειραιάς', 37.9420, 23.6465],
        ['Θεσσαλονίκη', 40.6401, 22.9444], ['Πάτρα', 38.2466, 21.7346],
        ['Ηράκλειο', 35.3387, 25.1442], ['Λάρισα', 39.6390, 22.4191],
        ['Βόλος', 39.3621, 22.9420], ['Ιωάννινα', 39.6650, 20.8537],
        ['Καβάλα', 40.9396, 24.4019], ['Σέρρες', 41.0856, 23.5479],
        ['Κιλκίς', 40.9938, 22.8752], ['Βέροια', 40.5232, 22.2030],
        ['Κατερίνη', 40.2719, 22.5027], ['Έδεσσα', 40.8018, 22.0470],
        ['Δράμα', 41.1524, 24.1467], ['Ξάνθη', 41.1355, 24.8879],
        ['Κομοτηνή', 41.1224, 25.4064], ['Αλεξανδρούπολη', 40.8457, 25.8744],
        ['Καστοριά', 40.5167, 21.2683], ['Κοζάνη', 40.3007, 21.7887],
        ['Πτολεμαΐδα', 40.5153, 21.6786], ['Γρεβενά', 40.0833, 21.4267],
        ['Φλώρινα', 40.7817, 21.4092], ['Πολύγυρος', 40.3789, 23.4436],
        ['Τρίκαλα', 39.5550, 21.7679], ['Καρδίτσα', 39.3646, 21.9214],
        ['Λαμία', 38.9000, 22.4333], ['Λιβαδειά', 38.4358, 22.8756],
        ['Χαλκίδα', 38.4636, 23.6017], ['Άρτα', 39.1600, 20.9856],
        ['Πρέβεζα', 38.9575, 20.7514], ['Ηγουμενίτσα', 39.5036, 20.2653],
        ['Αγρίνιο', 38.6214, 21.4079], ['Μεσολόγγι', 38.3706, 21.4306],
        ['Πύργος', 37.6753, 21.4411], ['Καλαμάτα', 37.0389, 22.1142],
        ['Τρίπολη', 37.5089, 22.3794], ['Κόρινθος', 37.9407, 22.9573],
        ['Ναύπλιο', 37.5675, 22.8006], ['Σπάρτη', 37.0736, 22.4297],
        ['Χανιά', 35.5138, 24.0180], ['Ρέθυμνο', 35.3662, 24.4826],
        ['Άγιος Νικόλαος', 35.1906, 25.7167], ['Ρόδος', 36.4341, 28.2176],
        ['Κως', 36.8933, 27.2889], ['Μυτιλήνη', 39.1100, 26.5547],
        ['Χίος', 38.3680, 26.1354], ['Σάμος', 37.7546, 26.9770],
        ['Σύρος', 37.4441, 24.9425], ['Κέρκυρα', 39.6243, 19.9217],
        ['Ζάκυνθος', 37.7870, 20.8990], ['Αργοστόλι', 38.1739, 20.4885],
        ['Λευκάδα', 38.8317, 20.7078], ['Τρίπολη', 37.5089, 22.3794]
    ];

    var EARTH_KM = 6371;

    function haversine(lat1, lon1, lat2, lon2) {
        var toRad = Math.PI / 180;
        var dLat = (lat2 - lat1) * toRad;
        var dLon = (lon2 - lon1) * toRad;
        var a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
            + Math.cos(lat1 * toRad) * Math.cos(lat2 * toRad)
            * Math.sin(dLon / 2) * Math.sin(dLon / 2);
        return EARTH_KM * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    document.addEventListener('DOMContentLoaded', function () {
        var root = document.getElementById('workRadius');
        if (!root) { return; }

        // Στην ΠΡΟΒΟΛΗ (επισκόπηση) δεν υπάρχουν χειριστήρια: μόνο κρυφό
        // πεδίο με την τιμή, χάρτης και λίστα πόλεων. Το ίδιο script
        // εξυπηρετεί και τις δύο σελίδες — μία συμπεριφορά, μία διόρθωση.
        var readOnly = root.classList.contains('wr--view');

        var slider = document.getElementById('radiusSlider');
        var number = document.getElementById('preferred_radius');
        var readout = document.getElementById('radiusReadout');
        var coverage = document.getElementById('radiusCoverage');
        var mapBox = document.getElementById('radiusMap');
        var latField = document.getElementById('driverLat');
        var lngField = document.getElementById('driverLng');
        var relocate = document.querySelector('[name="willing_to_relocate"]');

        // Τα βήματα του δείκτη δεν είναι γραμμικά: η διαφορά 20→40 χλμ
        // αλλάζει τη ζωή του οδηγού, η διαφορά 400→420 όχι.
        var STEPS = [10, 20, 30, 50, 75, 100, 150, 200, 300, 500, 9999];

        var lat = parseFloat(root.dataset.lat) || null;
        var lng = parseFloat(root.dataset.lng) || null;
        var city = root.dataset.city || '';

        var map = null;
        var circle = null;
        var marker = null;

        function currentKm() {
            return parseInt(number.value, 10) || 0;
        }

        function nearestStepIndex(km) {
            var best = 0;
            var bestDiff = Infinity;
            for (var i = 0; i < STEPS.length; i++) {
                var d = Math.abs(STEPS[i] - km);
                if (d < bestDiff) { bestDiff = d; best = i; }
            }
            return best;
        }

        function label(km) {
            if (km >= 9999) { return 'Όλη την Ελλάδα'; }
            if (km <= 0) { return 'Δεν έχει οριστεί'; }
            return km + ' χλμ' + (city ? ' από ' + city : '');
        }

        /** Ποιες πόλεις πέφτουν μέσα στον κύκλο. */
        function citiesWithin(km) {
            if (!lat || !lng || km <= 0) { return []; }
            if (km >= 9999) { return null; }   // null = όλη η χώρα

            var found = [];
            for (var i = 0; i < CITIES.length; i++) {
                var d = haversine(lat, lng, CITIES[i][1], CITIES[i][2]);
                if (d <= km) { found.push({ name: CITIES[i][0], km: Math.round(d) }); }
            }
            found.sort(function (a, b) { return a.km - b.km; });

            // Χωρίς διπλά ονόματα (η λίστα έχει μία-δυο επαναλήψεις)
            var seen = {};
            return found.filter(function (c) {
                if (seen[c.name]) { return false; }
                seen[c.name] = 1;
                return true;
            });
        }

        function renderCoverage(km) {
            if (!coverage) { return; }

            if (relocate && relocate.checked) {
                coverage.innerHTML = '<span class="wr-cov-all">Δηλώνετε διαθεσιμότητα σε όλη την Ελλάδα με μετεγκατάσταση.</span>';
                return;
            }

            var list = citiesWithin(km);

            if (list === null) {
                coverage.innerHTML = '<span class="wr-cov-all">Καλύπτετε όλη την Ελλάδα.</span>';
                return;
            }
            if (!lat || !lng) {
                coverage.innerHTML = '<span class="wr-cov-none">Συμπληρώστε πόλη για να δείτε τι καλύπτει η ακτίνα σας.</span>';
                return;
            }
            if (!list.length) {
                coverage.innerHTML = '<span class="wr-cov-none">Καμία μεγάλη πόλη σε αυτή την ακτίνα — δοκιμάστε μεγαλύτερη.</span>';
                return;
            }

            // Οι 8 κοντινότερες αρκούν· περισσότερες γίνονται τοίχος κειμένου.
            var shown = list.slice(0, 8);
            var html = '<span class="wr-cov-title">Καλύπτετε ' + list.length
                + (list.length === 1 ? ' πόλη' : ' πόλεις') + ':</span> ';
            html += shown.map(function (c) {
                return '<span class="wr-city">' + c.name + '<small>' + c.km + '</small></span>';
            }).join('');
            if (list.length > shown.length) {
                html += '<span class="wr-city wr-city-more">+' + (list.length - shown.length) + '</span>';
            }
            coverage.innerHTML = html;
        }

        function drawCircle(km) {
            if (!map || !circle) { return; }

            if (km >= 9999 || km <= 0) {
                if (map.hasLayer(circle)) { map.removeLayer(circle); }
                // Όλη η Ελλάδα: ζουμ που τη δείχνει ολόκληρη.
                if (km >= 9999) { map.setView([38.5, 23.5], 5.4); }
                return;
            }

            if (!map.hasLayer(circle)) { circle.addTo(map); }
            circle.setRadius(km * 1000);
            // Ο κύκλος γεμίζει το πλαίσιο: ο οδηγός βλέπει την ακτίνα σε
            // σχέση με τη γεωγραφία, όχι μια κουκκίδα σε άδειο χάρτη.
            map.fitBounds(circle.getBounds(), { padding: [8, 8] });
        }

        function update(km, fromSlider) {
            if (!readOnly) { number.value = km; }
            if (!fromSlider && slider) { slider.value = nearestStepIndex(km); }
            // Στην προβολή το κείμενο το γράφει ο server (σωστές λέξεις για
            // μετεγκατάσταση) — δεν το ξαναγράφουμε από εδώ.
            if (readout && !readOnly) { readout.textContent = label(km); }
            renderCoverage(km);
            drawCircle(km);
        }

        // ── Χάρτης (προαιρετικός) ────────────────────────────────────────
        function initMap() {
            if (!mapBox || typeof L === 'undefined') { return; }

            var center = [lat || 39.0742, lng || 21.8243];   // κέντρο Ελλάδας

            map = L.map(mapBox, {
                center: center,
                zoom: 9,
                zoomControl: true,
                attributionControl: true,
                // Δεν κλέβει την κύλιση της σελίδας: ο χρήστης που σκρολάρει
                // το προφίλ του δεν θέλει να ζουμάρει κατά λάθος τον χάρτη.
                scrollWheelZoom: false,
                dragging: !readOnly
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '© OpenStreetMap'
            }).addTo(map);

            // Κουκκίδα αντί για εικόνα-πινέζα: το Leaflet τραβά PNG marker
            // από το CDN του, που δεν θέλουμε — ο CircleMarker είναι SVG.
            marker = L.circleMarker(center, {
                radius: 5,
                color: '#fff',
                weight: 2,
                fillColor: '#b3261e',
                fillOpacity: 1
            }).addTo(map);

            circle = L.circle(center, {
                radius: 1000,
                color: '#b3261e',
                weight: 2,
                opacity: 0.85,
                fillColor: '#b3261e',
                fillOpacity: 0.10
            });

            mapBox.classList.add('is-ready');
            // Το Leaflet υπολογίζει διαστάσεις κατά τη δημιουργία· μόλις
            // πήρε το στοιχείο πλάτος (αφού βγήκε το display:none), πρέπει
            // να ξαναμετρήσει — αλλιώς ο χάρτης βγαίνει κομμένος.
            setTimeout(function () { map.invalidateSize(); drawCircle(currentKm()); }, 60);
        }

        /**
         * Γεωκωδικοποίηση της έδρας — ΜΙΑ φορά, μόνο αν λείπουν οι
         * συντεταγμένες. Το αποτέλεσμα μπαίνει σε κρυφά πεδία και
         * αποθηκεύεται με τη φόρμα: έτσι γεμίζουν οι στήλες που
         * χρειάζεται το ταίριασμα.
         */
        /**
         * Πρώτα ΤΟΠΙΚΑ: αν η πόλη της έδρας είναι στον πίνακα, οι
         * συντεταγμένες υπάρχουν ήδη. Καλύπτει τη συντριπτική πλειοψηφία
         * των οδηγών (πρωτεύουσες νομών) χωρίς καμία κλήση, χωρίς κλειδί
         * API και χωρίς δίκτυο — και δίνει αποτέλεσμα ΑΚΑΡΙΑΙΑ αντί για
         * «Συμπληρώστε πόλη» όσο περιμένουμε τον Geocoder.
         */
        function localGeocode() {
            if (lat && lng) { return true; }
            if (!city) { return false; }

            var norm = function (t) {
                return t.trim().toLowerCase()
                    .replace(/[άΆ]/g, 'α').replace(/[έΈ]/g, 'ε').replace(/[ήΉ]/g, 'η')
                    .replace(/[ίΊϊΐ]/g, 'ι').replace(/[όΌ]/g, 'ο').replace(/[ύΎϋΰ]/g, 'υ')
                    .replace(/[ώΏ]/g, 'ω');
            };
            var target = norm(city);

            for (var i = 0; i < CITIES.length; i++) {
                if (norm(CITIES[i][0]) === target) {
                    lat = CITIES[i][1];
                    lng = CITIES[i][2];
                    if (latField && !latField.value) { latField.value = lat.toFixed(8); }
                    if (lngField && !lngField.value) { lngField.value = lng.toFixed(8); }
                    return true;
                }
            }
            return false;
        }

        function geocodeBase() {
            if (localGeocode()) {
                // Βρέθηκε τοπικά — ο Geocoder θα ήταν σπατάλη κλήσης.
                if (map) {
                    map.setView([lat, lng], map.getZoom());
                    marker.setLatLng([lat, lng]);
                    circle.setLatLng([lat, lng]);
                }
                return;
            }
            if (lat && lng) { return; }

            /*
             * Πόλη εκτός του τοπικού πίνακα (χωριό, οικισμός): ρωτάμε το
             * Nominatim του OpenStreetMap — δωρεάν, χωρίς κλειδί.
             *
             * ΜΟΝΟ ΟΤΑΝ ΧΡΕΙΑΖΕΤΑΙ: ο πίνακας CITIES καλύπτει κάθε
             * πρωτεύουσα νομού, οπότε αυτό τρέχει σπάνια. Το Nominatim
             * ζητά να μην το βομβαρδίζουμε — και δεν το κάνουμε.
             */
            var address = root.dataset.address || city;
            if (!address) { return; }

            fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=gr&q='
                  + encodeURIComponent(address))
                .then(function (r) { return r.ok ? r.json() : []; })
                .then(function (results) {
                    if (!results || !results.length) { return; }
                    lat = parseFloat(results[0].lat);
                    lng = parseFloat(results[0].lon);
                    if (!lat || !lng) { return; }

                    if (latField) { latField.value = lat.toFixed(8); }
                    if (lngField) { lngField.value = lng.toFixed(8); }
                    if (map) {
                        map.setView([lat, lng], map.getZoom());
                        marker.setLatLng([lat, lng]);
                        circle.setLatLng([lat, lng]);
                    }
                    update(currentKm(), false);
                })
                .catch(function () {
                    // Χωρίς δίκτυο ή μπλοκαρισμένο: η ακτίνα και ο δείκτης
                    // δουλεύουν· μόνο η λίστα πόλεων μένει κενή.
                });
        }

        // ── Χειριστές ────────────────────────────────────────────────────
        if (slider) {
            slider.max = STEPS.length - 1;
            slider.value = nearestStepIndex(currentKm());
            slider.addEventListener('input', function () {
                update(STEPS[parseInt(this.value, 10)], true);
            });
        }

        // Ελεύθερη τιμή: ο οδηγός που ξέρει ότι δουλεύει «μέχρι τα 65 χλμ»
        // δεν πρέπει να στρογγυλοποιεί στα 50 ή στα 75.
        if (!readOnly) {
        number.addEventListener('input', function () {
            var v = parseInt(this.value, 10);
            if (isNaN(v) || v < 0) { return; }
            update(Math.min(v, 9999), false);
        });
        }

        if (relocate) {
            relocate.addEventListener('change', function () {
                root.classList.toggle('is-nationwide', this.checked);
                renderCoverage(currentKm());
            });
            root.classList.toggle('is-nationwide', relocate.checked);
        }

        // Αν αλλάξει η πόλη στη φόρμα, η έδρα ξαναϋπολογίζεται.
        var cityField = document.getElementById('city');
        if (cityField) {
            cityField.addEventListener('change', function () {
                city = this.value.trim();
                root.dataset.city = city;
                lat = null;
                lng = null;
                geocodeBase();
                update(currentKm(), false);
            });
        }

        initMap();
        geocodeBase();
        update(currentKm(), false);
    });
})();
