// Αρχικοποίηση λειτουργιών όταν φορτώνει το έγγραφο
document.addEventListener('DOMContentLoaded', function () {
    // Αρχικοποίηση καρτελών
    initTabs();

    // Αρχικοποίηση κουμπιού διαθεσιμότητας
    initAvailabilityToggle();
});

// Λειτουργία καρτελών
function initTabs()
{
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabButtons.forEach(button => {
        button.addEventListener('click', function () {
            const targetTab = this.getAttribute('data-tab');

            // Αφαίρεση ενεργών κλάσεων
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));

            // Ενεργοποίηση της επιλεγμένης καρτέλας
            this.classList.add('active');
            document.getElementById(targetTab).classList.add('active');

            // Ενημέρωση του URL με το hash για την καρτέλα
            window.location.hash = targetTab;
        });
    });

    // Έλεγχος για hash στο URL και ενεργοποίηση της αντίστοιχης καρτέλας
    const hash = window.location.hash.substring(1);
    if (hash) {
        const activeTab = document.querySelector(`.tab - btn[data - tab = "${hash}"]`);
        if (activeTab) {
            activeTab.click();
        }
    }
}

// Λειτουργία εναλλαγής διαθεσιμότητας
function initAvailabilityToggle()
{
    const toggleButton = document.getElementById('toggleAvailability');

    if (toggleButton) {
        toggleButton.addEventListener('click', function () {
            // Δημιουργία του αντικειμένου FormData και προσθήκη του CSRF token
            const formData = new FormData();
            formData.append('csrf_token', getCsrfToken());

            // Εκτέλεση του AJAX αιτήματος
            fetch(BASE_URL + 'drivers/toggle-availability', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Ενημέρωση της κατάστασης στην οθόνη
                    updateAvailabilityStatus();

                    // Εμφάνιση μηνύματος επιτυχίας
                    showMessage('Η κατάσταση διαθεσιμότητάς σας ενημερώθηκε με επιτυχία', 'success');
                } else {
                    // Εμφάνιση μηνύματος σφάλματος
                    showMessage('Υπήρξε ένα σφάλμα κατά την ενημέρωση της διαθεσιμότητας', 'error');
                }
            })
            .catch(error => {
                console.error('Σφάλμα:', error);
                showMessage('Υπήρξε ένα σφάλμα επικοινωνίας με τον διακομιστή', 'error');
            });
        });
    }
}

// Συνάρτηση για λήψη του CSRF token από τη σελίδα
function getCsrfToken()
{
    // Προσπάθεια λήψης από input με name="csrf_token"
    const csrfInput = document.querySelector('input[name="csrf_token"]');
    if (csrfInput) {
        return csrfInput.value;
    }

    // Εναλλακτικά, αν το token είναι αποθηκευμένο ως meta tag
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta) {
        return csrfMeta.getAttribute('content');
    }

    return '';
}

// Ενημέρωση της κατάστασης διαθεσιμότητας στην οθόνη
function updateAvailabilityStatus()
{
    const availabilityStatus = document.querySelector('.availability-status');
    const statusText = document.querySelector('.status-text');
    const toggleButton = document.getElementById('toggleAvailability');

    if (availabilityStatus && statusText && toggleButton) {
        // Εναλλαγή κλάσης διαθεσιμότητας
        availabilityStatus.classList.toggle('available');
        availabilityStatus.classList.toggle('unavailable');

        // Ενημέρωση κειμένου
        if (availabilityStatus.classList.contains('available')) {
            statusText.textContent = 'Διαθέσιμος/η για εργασία';
            toggleButton.textContent = 'Αλλαγή σε μη διαθέσιμος/η';
        } else {
            statusText.textContent = 'Μη διαθέσιμος/η για εργασία';
            toggleButton.textContent = 'Αλλαγή σε διαθέσιμος/η';
        }
    }
}

// Εμφάνιση μηνύματος στον χρήστη
function showMessage(message, type = 'success')
{
    // Έλεγχος αν υπάρχει ήδη container για μηνύματα
    let messageContainer = document.querySelector('.message-container');

    if (!messageContainer) {
        // Δημιουργία container αν δεν υπάρχει
        messageContainer = document.createElement('div');
        messageContainer.className = 'message-container';
        document.querySelector('.container').prepend(messageContainer);
    }

    // Δημιουργία του στοιχείου μηνύματος
    const messageElement = document.createElement('div');
    messageElement.className = `message ${type} - message`;
    messageElement.textContent = message;

    // Προσθήκη κουμπιού κλεισίματος
    const closeButton = document.createElement('span');
    closeButton.className = 'close-button';
    closeButton.innerHTML = '&times;';
    closeButton.onclick = function () {
        messageElement.remove();
    };

    messageElement.appendChild(closeButton);
    messageContainer.appendChild(messageElement);

    // Αυτόματη εξαφάνιση μετά από 5 δευτερόλεπτα
    setTimeout(() => {
        messageElement.remove();
    }, 5000);
}
    // --- Αρχικοποίηση χάρτη για τις προτεινόμενες θέσεις ---
function initJobMatchesMap()
{
    const mapElement = document.getElementById('jobMatchesMap');
    if (mapElement) {
        console.log('Initializing map');

        // Προεπιλεγμένες συντεταγμένες για Θεσσαλονίκη
        let driverLat = 40.6401;
        let driverLng = 22.9444;

        // Έλεγχος αν υπάρχει στοιχείο με τα data attributes
        if (mapElement.dataset.lat && mapElement.dataset.lng) {
            driverLat = parseFloat(mapElement.dataset.lat);
            driverLng = parseFloat(mapElement.dataset.lng);
        }

        console.log('Map coordinates:', driverLat, driverLng);

        const driverLocation = {
            lat: driverLat,
            lng: driverLng
        };

        try {
            // Έλεγχος αν το Google Maps API είναι διαθέσιμο
            if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
                console.error('Google Maps API not loaded!');
                return;
            }

            const map = new google.maps.Map(mapElement, {
                center: driverLocation,
                zoom: 11
                });

            // Μαρκαδόρος για τη θέση του οδηγού
            const driverMarker = new google.maps.Marker({
                position: driverLocation,
                map: map,
                title: 'Η θέση μου',
                // icon: BASE_URL + 'img/driver_marker.png' // Βεβαιωθείτε ότι το BASE_URL είναι διαθέσιμο
                });

            // Έλεγχος αν υπάρχει το element για το searchRadius
            const searchRadiusElement = document.getElementById('searchRadius');
            if (searchRadiusElement) {
                // Κύκλος για την ακτίνα αναζήτησης
                const searchRadius = parseInt(searchRadiusElement.value);
                const radiusCircle = new google.maps.Circle({
                    strokeColor: '#FF6B6B',
                    strokeOpacity: 0.8,
                    strokeWeight: 2,
                    fillColor: '#FF6B6B',
                    fillOpacity: 0.1,
                    map: map,
                    center: driverLocation,
                    radius: searchRadius * 1000 // Μετατροπή σε μέτρα
                    });

                // Φόρτωση προτεινόμενων θέσεων
                loadJobMatches(driverLocation, searchRadius);

                // Ενημέρωση ακτίνας όταν αλλάζει η επιλογή
                searchRadiusElement.addEventListener('change', function () {
                    const newRadius = parseInt(this.value);
                    radiusCircle.setRadius(newRadius * 1000);
                    loadJobMatches(driverLocation, newRadius);
                });

                // Κουμπί ανανέωσης
                const refreshButton = document.getElementById('refreshJobMatches');
                if (refreshButton) {
                    refreshButton.addEventListener('click', function () {
                        const currentRadius = parseInt(searchRadiusElement.value);
                        loadJobMatches(driverLocation, currentRadius);
                    });
                }
            }

            console.log('Map initialized successfully');
        } catch (error) {
            console.error('Error initializing map:', error);
        }
    } else {
        console.warn('Map element #jobMatchesMap not found!'); // Αλλαγή σε warning
    }
}

    // --- Φόρτωση προτεινόμενων θέσεων εργασίας ---
function loadJobMatches(location, radius)
{
    const matchesList = document.getElementById('matchedJobsList');
    if (!matchesList) {
        console.error('Matched jobs list element not found!');
        return;
    }

    matchesList.innerHTML = '<p class="loading-message">Φόρτωση προτεινόμενων θέσεων εργασίας...</p>';

    // Βάση URL για τους συνδέσμους
    //const baseUrl = '/drivejob/public/'; // ΠΡΟΣΑΡΜΟΣΤΕ ΑΝ ΧΡΕΙΑΖΕΤΑΙ

    // Προσομοίωση για τώρα
    setTimeout(function () {
        // Ψεύτικα δεδομένα για την προεπισκόπηση
        const jobMatches = [
            {
                id: 1,
                title: 'Οδηγός φορτηγού για διανομές σε αλυσίδα σούπερ μάρκετ',
                company: 'Logistics ΑΕ',
                location: 'Θεσσαλονίκη',
                distance: 3.2,
                salary: '1200 - 1500',
                match_score: 92
        },
            {
                id: 2,
                title: 'Οδηγός λεωφορείου για τουριστική περιοχή',
                company: 'Τουριστικές Μεταφορές ΕΠΕ',
                location: 'Χαλκιδική',
                distance: 7.8,
                salary: '1300 - 1600',
                match_score: 85
        },
            {
                id: 3,
                title: 'Χειριστής μηχανημάτων έργου',
                company: 'Κατασκευαστική ΑΕ',
                location: 'Θεσσαλονίκη',
                distance: 5.1,
                salary: '1500 - 1800',
                match_score: 78
        }
        ];

        if (jobMatches.length === 0) {
            matchesList.innerHTML = '<p class="no-matches">Δεν βρέθηκαν θέσεις εργασίας που να ταιριάζουν με τα προσόντα σας στην επιλεγμένη ακτίνα.</p>';
            return;
        }

        let matchesHTML = '';
        jobMatches.forEach(job => {
            matchesHTML += `
                < div class = "job-match-card" >
                    < div class = "match-score-badge" style = "background-color: ${getMatchScoreColor(job.match_score)}" >
                        ${job.match_score} %
                    <  / div >
                    < div class = "job-match-details" >
                        < h4 > < a href = "${BASE_URL}job-listings/show/${job.id}" > ${job.title} < / a > < / h4 >
                        < p class = "job-match-company" > ${job.company} < / p >
                        < div class = "job-match-info" >
                            < span class = "job-match-location" >
                                < img src = "${BASE_URL}img/location_icon.png" alt = "Τοποθεσία" >
                                ${job.location} (${job.distance} χλμ)
                            <  / span >
                            < span class = "job-match-salary" >
                                < img src = "${BASE_URL}img/salary_icon.png" alt = "Αμοιβή" >
                                ${job.salary}€ / μήνα
                            <  / span >
                        <  / div >
                    <  / div >
                    < a href = "${BASE_URL}job-listings/show/${job.id}" class = "btn-primary" > Προβολή < / a >
                <  / div >
            `;
            });

        matchesList.innerHTML = matchesHTML;
}, 1000); // Προσομοίωση καθυστέρησης 1 δευτερολέπτου
    }

    // --- Βοηθητική συνάρτηση για το χρώμα του ποσοστού ταιριάσματος ---
    function getMatchScoreColor(score)
    {
        if (score >= 90) {
            return '#28a745'; // Πράσινο
        }
        if (score >= 75) {
            return '#17a2b8'; // Μπλε
        }
        if (score >= 60) {
            return '#ffc107'; // Κίτρινο
        }
        return '#dc3545'; // Κόκκινο
    }

    // --- JavaScript για τη διαχείριση των αδειών οδήγησης (Από τον αρχικό σας κώδικα) ---
    const drivingLicenseCheckbox = document.getElementById('driving_license');
    const drivingLicenseTab = document.getElementById('driving_license_tab');

    if (drivingLicenseCheckbox && drivingLicenseTab) {
        drivingLicenseCheckbox.addEventListener('change', function () {
            drivingLicenseTab.classList.toggle('hidden', !this.checked);
        });
        // Set initial state based on checkbox
        drivingLicenseTab.classList.toggle('hidden', !drivingLicenseCheckbox.checked);
    }

    // Διαχείριση ημερομηνιών λήξης για κάθε κατηγορία (Από τον αρχικό σας κώδικα)
    const categoryExpiryMap = {
        'AM': 'motorcycle_license_expiry', 'A1': 'motorcycle_license_expiry',
        'A2': 'motorcycle_license_expiry', 'A': 'motorcycle_license_expiry',
        'B': 'car_license_expiry', 'BE': 'car_license_expiry',
        'C1': 'truck_license_expiry', 'C': 'truck_license_expiry',
        'CE': 'truck_license_expiry', 'C1E': 'truck_license_expiry',
        'D1': 'bus_license_expiry', 'D': 'bus_license_expiry',
        'DE': 'bus_license_expiry', 'D1E': 'bus_license_expiry'
    };

    // Διαχείριση ορατότητας ΠΕΙ (Από τον αρχικό σας κώδικα)
    const peiSection = document.getElementById('pei_section');
    const licenseTypeCheckboxes = document.querySelectorAll('input[name="license_types[]"]');

    function updatePEIVisibility()
    {
        const hasCOrDCategory = Array.from(licenseTypeCheckboxes).some(checkbox => {
            if (!checkbox.checked) {
                return false;
            }
            const category = checkbox.value;
            return ['C', 'CE', 'C1', 'C1E', 'D', 'DE', 'D1', 'D1E'].includes(category);
        });
        if (peiSection) {
            peiSection.classList.toggle('hidden', !hasCOrDCategory);
        }
    }

    if (licenseTypeCheckboxes.length > 0) {
        updatePEIVisibility(); // Initial check
        licenseTypeCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updatePEIVisibility);
        });
    }

    // Εμφάνιση επιβεβαιώσεων για τις ημερομηνίες λήξης (Από τον αρχικό σας κώδικα)
    const expiryDateInputs = document.querySelectorAll('input[type="date"]');
    expiryDateInputs.forEach(input => {
        input.addEventListener('change', function () {
            if (this.value) {
                const expiryDate = new Date(this.value);
                const today = new Date();
                // Reset time parts for accurate date comparison
                today.setHours(0, 0, 0, 0);
                expiryDate.setHours(0, 0, 0, 0);

                const timeDiff = expiryDate.getTime() - today.getTime();
                const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));

                const parent = this.closest('.form-group') || this.closest('td'); // Handle table cells too
                if (!parent) {
                    return;
                }

                let notification = parent.querySelector('.expiry-notification');
                if (!notification) {
                    notification = document.createElement('div');
                    notification.className = 'expiry-notification';
                    // Insert after the input or within the cell
                    if (parent.tagName.toLowerCase() === 'td') {
                        parent.appendChild(notification);
                    } else {
                        this.parentNode.insertBefore(notification, this.nextSibling);
                    }
                }

                notification.textContent = '';
                notification.classList.remove('expired', 'expiring-soon');

                if (expiryDate < today) {
                    notification.classList.add('expired');
                    notification.textContent = 'Η άδεια έχει λήξει! Απαιτείται ανανέωση.';
                } else if (daysDiff <= 90) { // 3 months or less
                    notification.classList.add('expiring-soon');
                    notification.textContent = 'Η άδεια λήγει σύντομα! Προγραμματίστε ανανέωση.';
                }
            } else {
                 // Remove notification if date is cleared
                 const parent = this.closest('.form-group') || this.closest('td');
                if (parent) {
                    const existingNotification = parent.querySelector('.expiry-notification');
                    if (existingNotification) {
                        existingNotification.remove();
                    }
                }
            }
        });
        // Trigger change event for initially filled dates
    if (input.value) {
        input.dispatchEvent(new Event('change'));
    }
    });

    // Συγχρονισμός κοινών ημερομηνιών λήξης (Από τον αρχικό σας κώδικα)
    const categoryGroups = {
        'motorcycle': ['AM', 'A1', 'A2', 'A'], 'car': ['B', 'BE'],
        'truck': ['C1', 'C1E', 'C', 'CE'], 'bus': ['D1', 'D1E', 'D', 'DE']
    };

    for (const groupName in categoryGroups) {
        const expiryInput = document.querySelector(`input[name = "${groupName}_license_expiry"]`);
        if (expiryInput) {
            expiryInput.addEventListener('change', function () {
                const newExpiryDate = this.value;
                categoryGroups[groupName].forEach(category => {
                    const categoryCheckbox = document.querySelector(`input[name = "license_types[]"][value = "${category}"]`);
                    if (categoryCheckbox) {
                         const row = categoryCheckbox.closest('tr');
                        if (row) {
                            const dateInput = row.querySelector('input[type="date"]');
                            if (dateInput) {
                                dateInput.value = newExpiryDate;
                                dateInput.dispatchEvent(new Event('change')); // Trigger notification update
                            }
                        }
                    }
                });
            });
        }
    }

    // Διαχείριση της εμφάνισης των ειδοποιήσεων λήξης ΠΕΙ (Από τον αρχικό σας κώδικα)
    const peiExpiryInputs = [
        document.querySelector('input[name="pei_c_expiry"]'),
        document.querySelector('input[name="pei_d_expiry"]')
    ];

    peiExpiryInputs.forEach(input => {
        if (input) {
            input.addEventListener('change', function () {
                // Reuse the date checking logic from the general expiry check
                 this.dispatchEvent(new Event('change')); // Trigger the general handler
            });
             // Trigger initial check
            if (input.value) {
                input.dispatchEvent(new Event('change'));
            }
        }
    });

    // Αυτόματη επιλογή ΠΕΙ όταν επιλέγεται μια κατηγορία C ή D (Από τον αρχικό σας κώδικα)
    licenseTypeCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            if (this.checked) {
                const category = this.value;
                let peiInput = null;
                if (['C', 'CE', 'C1', 'C1E'].includes(category)) {
                     peiInput = document.querySelector('input[name="has_pei_c"]');
                } else if (['D', 'DE', 'D1', 'D1E'].includes(category)) {
                     peiInput = document.querySelector('input[name="has_pei_d"]');
                }
                if (peiInput && !peiInput.checked) {
                    peiInput.checked = true;
                    peiInput.dispatchEvent(new Event('change')); // Update dependent fields if any
                }
            }
        });
    });

