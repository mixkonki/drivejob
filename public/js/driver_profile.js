document.addEventListener('DOMContentLoaded', function() {
    console.log('Script loaded'); // Για debugging
    
    // Λειτουργία καρτελών
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    if (tabButtons.length > 0) {
        console.log('Found tab buttons: ' + tabButtons.length);
        
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');
                console.log('Tab clicked: ' + targetTab);
                
                // Αφαίρεση ενεργών κλάσεων
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabPanes.forEach(pane => pane.classList.remove('active'));
                
                // Ενεργοποίηση της επιλεγμένης καρτέλας
                this.classList.add('active');
                document.getElementById(targetTab).classList.add('active');
                
                // Ειδικές ενέργειες για συγκεκριμένες καρτέλες
                if (targetTab === 'job-matches') {
                    // Καθυστέρηση για να βεβαιωθούμε ότι το tab είναι ορατό
                    setTimeout(initJobMatchesMap, 100);
                }
            });
        });
    } else {
        console.error('No tab buttons found!');
    }
    
    // Αρχικοποίηση χάρτη για τις προτεινόμενες θέσεις
    function initJobMatchesMap() {
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
                    // Χρησιμοποιούμε εικόνα μόνο αν υπάρχει
                    // icon: '/drivejob/public/img/driver_marker.png'
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
                    searchRadiusElement.addEventListener('change', function() {
                        const newRadius = parseInt(this.value);
                        radiusCircle.setRadius(newRadius * 1000);
                        loadJobMatches(driverLocation, newRadius);
                    });
                    
                    // Κουμπί ανανέωσης
                    const refreshButton = document.getElementById('refreshJobMatches');
                    if (refreshButton) {
                        refreshButton.addEventListener('click', function() {
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
            console.error('Map element not found!');
        }
    }
    
    // Φόρτωση προτεινόμενων θέσεων εργασίας
    function loadJobMatches(location, radius) {
        const matchesList = document.getElementById('matchedJobsList');
        if (!matchesList) {
            console.error('Matched jobs list element not found!');
            return;
        }
        
        matchesList.innerHTML = '<p class="loading-message">Φόρτωση προτεινόμενων θέσεων εργασίας...</p>';
        
        // Βάση URL για τους συνδέσμους
        const baseUrl = '/drivejob/public/';
        
        // Προσομοίωση για τώρα
        setTimeout(function() {
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
                    <div class="job-match-card">
                        <div class="match-score-badge" style="background-color: ${getMatchScoreColor(job.match_score)}">
                            ${job.match_score}%
                        </div>
                        <div class="job-match-details">
                            <h4><a href="${baseUrl}job-listings/show/${job.id}">${job.title}</a></h4>
                            <p class="job-match-company">${job.company}</p>
                            <div class="job-match-info">
                                <span class="job-match-location">
                                    <img src="${baseUrl}img/location_icon.png" alt="Τοποθεσία">
                                    ${job.location} (${job.distance} χλμ)
                                </span>
                                <span class="job-match-salary">
                                    <img src="${baseUrl}img/salary_icon.png" alt="Αμοιβή">
                                    ${job.salary}€/μήνα
                                </span>
                            </div>
                        </div>
                        <a href="${baseUrl}job-listings/show/${job.id}" class="btn-primary">Προβολή</a>
                    </div>
                `;
            });
            
            matchesList.innerHTML = matchesHTML;
        }, 1000);
    }
    
    // Βοηθητική συνάρτηση για το χρώμα του ποσοστού ταιριάσματος
    function getMatchScoreColor(score) {
        if (score >= 90) return '#28a745'; // Πράσινο
        if (score >= 75) return '#17a2b8'; // Μπλε
        if (score >= 60) return '#ffc107'; // Κίτρινο
        return '#dc3545'; // Κόκκινο
    }
    
    // Εναλλακτικός τρόπος λειτουργίας καρτελών με απλά κλικ
    if (tabButtons.length === 0) {
        // Fallback για τις καρτέλες
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.onclick = function() {
                const tabId = this.getAttribute('data-tab');
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                document.getElementById(tabId).classList.add('active');
                return false;
            };
        });
    }
});
// JavaScript για τη διαχείριση των αδειών οδήγησης
document.addEventListener('DOMContentLoaded', function() {
    // Χειρισμός της εμφάνισης/απόκρυψης του τμήματος άδειας οδήγησης
    const drivingLicenseCheckbox = document.getElementById('driving_license');
    const drivingLicenseTab = document.getElementById('driving_license_tab');
    
    if (drivingLicenseCheckbox && drivingLicenseTab) {
        drivingLicenseCheckbox.addEventListener('change', function() {
            drivingLicenseTab.classList.toggle('hidden', !this.checked);
        });
    }
    
    // Διαχείριση ημερομηνιών λήξης για κάθε κατηγορία
    const categoryExpiryMap = {
        // Δίκυκλα
        'AM': 'motorcycle_license_expiry',
        'A1': 'motorcycle_license_expiry',
        'A2': 'motorcycle_license_expiry',
        'A': 'motorcycle_license_expiry',
        
        // Επιβατικά
        'B': 'car_license_expiry',
        'BE': 'car_license_expiry',
        
        // Φορτηγά
        'C1': 'truck_license_expiry',
        'C': 'truck_license_expiry',
        'CE': 'truck_license_expiry',
        'C1E': 'truck_license_expiry',
        
        // Λεωφορεία
        'D1': 'bus_license_expiry',
        'D': 'bus_license_expiry',
        'DE': 'bus_license_expiry',
        'D1E': 'bus_license_expiry'
    };
    
    // Διαχείριση ορατότητας ΠΕΙ
    const peiSection = document.getElementById('pei_section');
    const peiCheckboxes = document.querySelectorAll('input[name^="has_pei_"]');
    const licenseTypeCheckboxes = document.querySelectorAll('input[name="license_types[]"]');
    
    // Έλεγχος εάν πρέπει να εμφανιστεί το τμήμα ΠΕΙ
    function updatePEIVisibility() {
        // Έλεγχος αν υπάρχει τουλάχιστον μια κατηγορία C ή D που έχει επιλεγεί
        const hasCOrDCategory = Array.from(licenseTypeCheckboxes).some(checkbox => {
            if (!checkbox.checked) return false;
            const category = checkbox.value;
            return ['C', 'CE', 'C1', 'C1E', 'D', 'DE', 'D1', 'D1E'].includes(category);
        });
        
        // Ενημέρωση ορατότητας τμήματος ΠΕΙ
        if (peiSection) {
            peiSection.classList.toggle('hidden', !hasCOrDCategory);
        }
    }
    
    // Ενημέρωση ορατότητας κατά την αρχικοποίηση
    updatePEIVisibility();
    
    // Προσθήκη event listeners για το ΠΕΙ
    licenseTypeCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updatePEIVisibility);
    });
    
    // Εμφάνιση επιβεβαιώσεων για τις ημερομηνίες λήξης
    const expiryDateInputs = document.querySelectorAll('input[type="date"]');
    
    expiryDateInputs.forEach(input => {
        input.addEventListener('change', function() {
            // Έλεγχος αν η ημερομηνία λήξης είναι κοντά ή έχει περάσει
            if (this.value) {
                const expiryDate = new Date(this.value);
                const today = new Date();
                const monthDiff = (expiryDate.getFullYear() - today.getFullYear()) * 12 + 
                                  (expiryDate.getMonth() - today.getMonth());
                
                // Αφαίρεση προηγούμενων notification messages
                const parent = this.closest('.form-group');
                const existingNotification = parent.querySelector('.expiry-notification');
                if (existingNotification) {
                    parent.removeChild(existingNotification);
                }
                
                // Προσθήκη notification ανάλογα με την κατάσταση της ημερομηνίας
                const notification = document.createElement('div');
                notification.className = 'expiry-notification';
                
                if (expiryDate < today) {
                    // Έχει λήξει
                    notification.className += ' expired';
                    notification.textContent = 'Η άδεια έχει λήξει! Απαιτείται ανανέωση.';
                } else if (monthDiff <= 3) {
                    // Λήγει σε λιγότερο από 3 μήνες
                    notification.className += ' expiring-soon';
                    notification.textContent = 'Η άδεια λήγει σύντομα! Προγραμματίστε ανανέωση.';
                }
                
                if (notification.textContent) {
                    parent.appendChild(notification);
                }
            }
        });
        
        // Trigger change event για τις ήδη συμπληρωμένες ημερομηνίες
        if (input.value) {
            const event = new Event('change');
            input.dispatchEvent(event);
        }
    });
    
    // Συγχρονισμός κοινών ημερομηνιών λήξης για κάθε κατηγορία
    
    // Ομαδοποίηση ημερομηνιών ανά τύπο άδειας
    const categoryGroups = {
        'motorcycle': ['AM', 'A1', 'A2', 'A'],
        'car': ['B', 'BE'],
        'truck': ['C1', 'C1E', 'C', 'CE'],
        'bus': ['D1', 'D1E', 'D', 'DE']
    };
    
    // Προσθήκη event listeners για συγχρονισμό ημερομηνιών λήξης ανά κατηγορία
    for (const groupName in categoryGroups) {
        const expiryInput = document.querySelector(`input[name="${groupName}_license_expiry"]`);
        
        if (expiryInput) {
            expiryInput.addEventListener('change', function() {
                // Ενημέρωση όλων των σχετικών πεδίων στον πίνακα με την ίδια ημερομηνία
                categoryGroups[groupName].forEach(category => {
                    const checkboxes = document.querySelectorAll(`input[name="license_types[]"][value="${category}"]`);
                    checkboxes.forEach(checkbox => {
                        if (checkbox.checked) {
                            // Βρίσκουμε το αντίστοιχο πεδίο ημερομηνίας λήξης στον πίνακα
                            const row = checkbox.closest('tr');
                            const dateInput = row.querySelector('input[type="date"]');
                            if (dateInput) {
                                dateInput.value = this.value;
                                
                                // Πυροδότηση του event change για ενημέρωση ειδοποιήσεων
                                const event = new Event('change');
                                dateInput.dispatchEvent(event);
                            }
                        }
                    });
                });
            });
        }
    }
    
    // Διαχείριση της εμφάνισης των ειδοποιήσεων λήξης ΠΕΙ
    const peiExpiryInputs = [
        document.getElementById('pei_c_expiry'),
        document.getElementById('pei_d_expiry')
    ];
    
    peiExpiryInputs.forEach(input => {
        if (input) {
            input.addEventListener('change', function() {
                if (this.value) {
                    const expiryDate = new Date(this.value);
                    const today = new Date();
                    const monthDiff = (expiryDate.getFullYear() - today.getFullYear()) * 12 + 
                                     (expiryDate.getMonth() - today.getMonth());
                    
                    // Αφαίρεση προηγούμενων ειδοποιήσεων
                    const parent = this.closest('.form-group');
                    const existingNotification = parent.querySelector('.pei-notification');
                    if (existingNotification) {
                        parent.removeChild(existingNotification);
                    }
                    
                    // Προσθήκη ειδοποίησης ανάλογα με την κατάσταση του ΠΕΙ
                    const notification = document.createElement('div');
                    notification.className = 'pei-notification';
                    
                    if (expiryDate < today) {
                        // Έχει λήξει
                        notification.className += ' expired';
                        notification.textContent = 'Το ΠΕΙ έχει λήξει! Απαιτείται ανανέωση.';
                    } else if (monthDiff <= 3) {
                        // Λήγει σε λιγότερο από 3 μήνες
                        notification.className += ' expiring-soon';
                        notification.textContent = 'Το ΠΕΙ λήγει σύντομα! Προγραμματίστε ανανέωση.';
                    }
                    
                    if (notification.textContent) {
                        parent.appendChild(notification);
                    }
                }
            });
            
            // Trigger change event για τις ήδη συμπληρωμένες ημερομηνίες
            if (input.value) {
                const event = new Event('change');
                input.dispatchEvent(event);
            }
        }
    });
    
    // Αυτόματη επιλογή ΠΕΙ όταν επιλέγεται μια κατηγορία C ή D
    licenseTypeCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                const category = this.value;
                
                // Αυτόματη επιλογή του αντίστοιχου checkbox ΠΕΙ
                if (['C', 'CE', 'C1', 'C1E'].includes(category)) {
                    const peiCCheckbox = document.querySelector('input[name="has_pei_c"]');
                    if (peiCCheckbox) {
                        peiCCheckbox.checked = true;
                    }
                } else if (['D', 'DE', 'D1', 'D1E'].includes(category)) {
                    const peiDCheckbox = document.querySelector('input[name="has_pei_d"]');
                    if (peiDCheckbox) {
                        peiDCheckbox.checked = true;
                    }
                }
            }
        });
    });
});