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