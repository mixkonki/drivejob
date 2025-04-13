<!-- JavaScript για το χάρτη και τις καρτέλες -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCgZpJWVYyrY0U8U1jBGelEWryur3vIrzc&libraries=places"></script>

    document.addEventListener('DOMContentLoaded', function() {
        // Λειτουργία καρτελών
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabPanes = document.querySelectorAll('.tab-pane');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');
                
                // Αφαίρεση ενεργών κλάσεων
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabPanes.forEach(pane => pane.classList.remove('active'));
                
                // Ενεργοποίηση της επιλεγμένης καρτέλας
                this.classList.add('active');
                document.getElementById(targetTab).classList.add('active');
                
                // Ειδικές ενέργειες για συγκεκριμένες καρτέλες
                if (targetTab === 'job-matches') {
                    initJobMatchesMap();
                }
            });
        });
        
        // Αρχικοποίηση χάρτη για τις προτεινόμενες θέσεις
        function initJobMatchesMap() {
            if (document.getElementById('jobMatchesMap')) {
                const driverLocation = {
                    lat: <?php echo isset($driverLocation) ? $driverLocation['lat'] : 40.6401; ?>,
                    lng: <?php echo isset($driverLocation) ? $driverLocation['lng'] : 22.9444; ?>
                };
                
                const map = new google.maps.Map(document.getElementById('jobMatchesMap'), {
                    center: driverLocation,
                    zoom: 11
                });
                
                // Μαρκαδόρος για τη θέση του οδηγού
                const driverMarker = new google.maps.Marker({
                    position: driverLocation,
                    map: map,
                    title: 'Η θέση μου',
                    icon: '<?php echo BASE_URL; ?>img/driver_marker.png'
                });
                
                // Κύκλος για την ακτίνα αναζήτησης
                const searchRadius = parseInt(document.getElementById('searchRadius').value);
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
                document.getElementById('searchRadius').addEventListener('change', function() {
                    const newRadius = parseInt(this.value);
                    radiusCircle.setRadius(newRadius * 1000);
                    loadJobMatches(driverLocation, newRadius);
                });
                
                // Κουμπί ανανέωσης
                document.getElementById('refreshJobMatches').addEventListener('click', function() {
                    const currentRadius = parseInt(document.getElementById('searchRadius').value);
                    loadJobMatches(driverLocation, currentRadius);
                });
            }
        }
        
        // Φόρτωση προτεινόμενων θέσεων εργασίας
        function loadJobMatches(location, radius) {
            const matchesList = document.getElementById('matchedJobsList');
            matchesList.innerHTML = '<p class="loading-message">Φόρτωση προτεινόμενων θέσεων εργασίας...</p>';
            
            // Εδώ θα γίνεται το AJAX αίτημα για τη λήψη των ταιριασμάτων
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
                        title: 'Χειριστής μηχανήματων έργου',
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
                                <h4><a href="${'<?php echo BASE_URL; ?>'}job-listings/show/${job.id}">${job.title}</a></h4>
                                <p class="job-match-company">${job.company}</p>
                                <div class="job-match-info">
                                    <span class="job-match-location">
                                        <img src="${'<?php echo BASE_URL; ?>'}img/location_icon.png" alt="Τοποθεσία">
                                        ${job.location} (${job.distance} χλμ)
                                    </span>
                                    <span class="job-match-salary">
                                        <img src="${'<?php echo BASE_URL; ?>'}img/salary_icon.png" alt="Αμοιβή">
                                        ${job.salary}€/μήνα
                                    </span>
                                </div>
                            </div>
                            <a href="${'<?php echo BASE_URL; ?>'}job-listings/show/${job.id}" class="btn-primary">Προβολή</a>
                        </div>
                    `;
                });
                
                matchesList.innerHTML = matchesHTML;
            }, 1500);
        }
        
        // Βοηθητική συνάρτηση για το χρώμα του ποσοστού ταιριάσματος
        function getMatchScoreColor(score) {
            if (score >= 90) return '#28a745'; // Πράσινο
            if (score >= 75) return '#17a2b8'; // Μπλε
            if (score >= 60) return '#ffc107'; // Κίτρινο
            return '#dc3545'; // Κόκκινο
        }
    });