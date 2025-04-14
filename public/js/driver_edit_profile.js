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
        });
    });
    
    // Υπολογισμός ηλικίας από την ημερομηνία γέννησης
    const birthDateInput = document.getElementById('birth_date');
    const ageDisplay = document.getElementById('age_display');
    
    if (birthDateInput && ageDisplay) {
        birthDateInput.addEventListener('change', function() {
            const birthDate = new Date(this.value);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            
            if (!isNaN(age)) {
                ageDisplay.textContent = `Ηλικία: ${age} ετών`;
            } else {
                ageDisplay.textContent = '';
            }
        });
        
        // Υπολογισμός ηλικίας κατά τη φόρτωση της σελίδας
        if (birthDateInput.value) {
            const event = new Event('change');
            birthDateInput.dispatchEvent(event);
        }
    }
    
    // Εμφάνιση/απόκρυψη των λεπτομερειών αδειών με βάση τα checkboxes
    const checkboxToTabMap = {
        'driving_license': 'driving_license_tab',
        'adr_certificate': 'adr_certificate_tab',
        'operator_license': 'operator_license_tab',
        'training_seminars': 'training_seminars_tab'
    };
    
    Object.keys(checkboxToTabMap).forEach(checkboxId => {
        const checkbox = document.getElementById(checkboxId);
        const tab = document.getElementById(checkboxToTabMap[checkboxId]);
        
        if (checkbox && tab) {
            // Αρχική κατάσταση
            tab.classList.toggle('hidden', !checkbox.checked);
            
            // Χειρισμός αλλαγών
            checkbox.addEventListener('change', function() {
                tab.classList.toggle('hidden', !this.checked);
            });
        }
    });
    
    // Αυτόματη συμπλήρωση διεύθυνσης με Google Places API
    const addressInput = document.getElementById('address');
    if (addressInput && typeof google !== 'undefined' && google.maps && google.maps.places) {
        const autocomplete = new google.maps.places.Autocomplete(addressInput, {
            types: ['address'],
        });
        
        autocomplete.addListener('place_changed', function() {
            const place = autocomplete.getPlace();
            
            if (!place.address_components) {
                return;
            }
            
            // Συμπλήρωση των πεδίων με βάση την επιλεγμένη διεύθυνση
            place.address_components.forEach(component => {
                const types = component.types;
                
                if (types.includes('street_number')) {
                    document.getElementById('house_number').value = component.long_name;
                } else if (types.includes('route')) {
                    // Η οδός ήδη βρίσκεται στο πεδίο διεύθυνσης
                } else if (types.includes('locality')) {
                    document.getElementById('city').value = component.long_name;
                } else if (types.includes('country')) {
                    document.getElementById('country').value = component.long_name;
                } else if (types.includes('postal_code')) {
                    document.getElementById('postal_code').value = component.long_name;
                }
            });
        });
    }
    
    // Έλεγχος ισχύος νέου κωδικού
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordStrengthDiv = document.getElementById('password-strength');
    
    if (newPasswordInput && passwordStrengthDiv) {
        newPasswordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let feedback = '';
            
            if (password.length >= 8) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^A-Za-z0-9]/)) strength++;
            
            // Εμφάνιση ισχύος κωδικού
            if (password.length === 0) {
                passwordStrengthDiv.textContent = '';
                passwordStrengthDiv.className = 'password-strength';
            } else if (strength < 2) {
                passwordStrengthDiv.textContent = 'Αδύναμος κωδικός';
                passwordStrengthDiv.className = 'password-strength weak';
            } else if (strength < 4) {
                passwordStrengthDiv.textContent = 'Μέτριος κωδικός';
                passwordStrengthDiv.className = 'password-strength medium';
            } else {
                passwordStrengthDiv.textContent = 'Ισχυρός κωδικός';
                passwordStrengthDiv.className = 'password-strength strong';
            }
            
            // Έλεγχος ταιριάσματος κωδικών
            if (confirmPasswordInput.value) {
                if (password === confirmPasswordInput.value) {
                    confirmPasswordInput.setCustomValidity('');
                } else {
                    confirmPasswordInput.setCustomValidity('Οι κωδικοί δεν ταιριάζουν');
                }
            }
        });
        
        // Έλεγχος ταιριάσματος κωδικών
        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', function() {
                if (newPasswordInput.value === this.value) {
                    this.setCustomValidity('');
                } else {
                    this.setCustomValidity('Οι κωδικοί δεν ταιριάζουν');
                }
            });
        }
    }
    
    // Δεδομένα υποειδικοτήτων για χειριστές μηχανημάτων
    const operatorSubSpecialities = {
        '1': [
            {id: '1.1', name: 'Εκσκαφείς όλων των τύπων', group: 'A'},
            {id: '1.2', name: 'Πασσαλοπήχτες παντός τύπου', group: 'B'},
            {id: '1.3', name: 'Σύνθετα εκσκαπτικά και φορτωτικά μηχανήματα', group: 'A'},
            {id: '1.4', name: 'Προωθητήρες γαιών (ΜΠΟΛΤΟΖΕΣ)', group: 'A'},
            {id: '1.5', name: 'Φορτωτές αλυσότροχοι και λαστιχοφόροι', group: 'A'},
            {id: '1.6', name: 'Αποξέστες γαιών (ΣΚΡΕΪΠΕΡ)', group: 'B'},
            {id: '1.7', name: 'Τορναντόζες παντός τύπου', group: 'B'},
            {id: '1.8', name: 'Βυθοκόροι παντός τύπου', group: 'A'},
            {id: '1.9', name: 'Μηχανήματα επεξεργασίας αδρανών υλικών', group: 'B'}
        ],
        '2': [
            {id: '2.1', name: 'Γερανοί μεταθετοί παντός τύπου', group: 'A'},
            {id: '2.2', name: 'Γερανοφόρα μηχανήματα παντός τύπου', group: 'A'},
            {id: '2.3', name: 'Γερανοφόρα οχήματα (παπαγάλοι)', group: 'B'},
            {id: '2.4', name: 'Τρυπανοφόρα οχήματα', group: 'B'},
            {id: '2.5', name: 'Καλαθοφόρα οχήματα', group: 'B'},
            {id: '2.6', name: 'Αντλίες (πρέσες) ετοίμου σκυροδέματος', group: 'A'},
            {id: '2.7', name: 'Ανυψωτικά περονοφόρα μηχανήματα (ΚΛΑΡΚ)', group: 'A'},
            {id: '2.8', name: 'Ηλεκτροκίνητα περονοφόρα ανυψωτικά μηχανήματα', group: 'B'},
            {id: '2.9', name: 'Βαρέα οχήματα ανύψωσης και μεταφοράς', group: 'A'}
        ],
        '3': [
            {id: '3.1', name: 'Βαρέα οχήματα μεταφοράς γαιωδών υλικών', group: 'A'},
            {id: '3.2', name: 'Βαρέα οχήματα μεταφοράς πέτρινων όγκων', group: 'A'},
            {id: '3.3', name: 'Μηχανήματα διάστρωσης ασφάλτου (ΦΙΝΙΤΣΕΡ)', group: 'A'},
            {id: '3.4', name: 'Μηχανήματα εκσκαφής και αποξέσεως ασφάλτου (ΦΡΕΖΕΣ)', group: 'B'},
            {id: '3.5', name: 'Διαμορφωτές γαιών, οδών (ΓΚΡΕΪΝΤΕΡ)', group: 'A'},
            {id: '3.6', name: 'Μηχανήματα πλάγιας εκσκαφής πρανών', group: 'B'},
            {id: '3.7', name: 'Οδοστρωτήρες παντός τύπου', group: 'A'},
            {id: '3.8', name: 'Μηχανήματα ανακύκλωσης ασφάλτου', group: 'B'},
            {id: '3.9', name: 'Προθερμαντήρες θέρμανσης ασφάλτου', group: 'B'},
            {id: '3.10', name: 'Μηχανήματα κοπής ασφάλτου και πεζοδρομίων', group: 'B'},
            {id: '3.11', name: 'Στατικά και δονητικά μηχανήματα συμπύκνωσης', group: 'A'},
            {id: '3.12', name: 'Πισσωτικά μηχανήματα εμποτισμού ασφάλτου', group: 'B'}
        ],
        '4': [
            {id: '4.1', name: 'Μηχανικά σάρωθρα (σκούπες)', group: 'A'},
            {id: '4.2', name: 'Εκχιονιστικά οχήματα - μηχανήματα', group: 'A'},
            {id: '4.3', name: 'Οχήματα - μηχανήματα διάστρωσης αλατιού', group: 'B'},
            {id: '4.4', name: 'Ειδικά οχήματα καθαρισμού διαδρόμων αεροδρομίων', group: 'B'},
            {id: '4.5', name: 'Οχήματα φορτοεκφόρτωσης αεροσκαφών', group: 'A'},
            {id: '4.6', name: 'Μηχανήματα σήμανσης - διαγράμμισης οδών και αεροδρομίων', group: 'B'},
            {id: '4.7', name: 'Βυτιοφόρα αποφρακτικά οχήματα', group: 'A'},
            {id: '4.8', name: 'Οχήματα εξυπηρέτησης οδών και αεροδρομίων με βραχίονα', group: 'B'}
        ],
        '5': [
            {id: '5.1', name: 'Μηχανήματα διανοίξεως στοών – σηράγγων (αρουραίοι)', group: 'A'},
            {id: '5.2', name: 'Ηλεκτροκίνητα μηχανήματα διανοίξεως στοών – γαλαριών', group: 'A'},
            {id: '5.3', name: 'Μηχανήματα εκσκαφής και φόρτωσης στα υπόγεια έργα', group: 'A'},
            {id: '5.4', name: 'Μηχανήματα εκσκαφών στα υπόγεια έργα μικρότερης ισχύος', group: 'B'},
            {id: '5.5', name: 'Μεγάλοι ηλεκτροκίνητοι εκσκαφείς λιγνίτη', group: 'A'},
            {id: '5.6', name: 'Μηχανήματα επιφανειακών ορυχείων', group: 'A'}
        ],
        '6': [
            {id: '6.1', name: 'Ελκυστήρες παντός τύπου (πλην των γεωργικών)', group: 'A'},
            {id: '6.2', name: 'Αυτοκινούμενοι αεροσυμπιεστές', group: 'B'}
        ],
        '7': [
            {id: '7.1', name: 'Γεωτρύπανα παντός τύπου', group: 'A'},
            {id: '7.2', name: 'Διατρητικά μηχανήματα', group: 'A'},
            {id: '7.3', name: 'Ηλεκτροκίνητα διατρητικά μηχανήματα', group: 'B'}
        ],
        '8': [
            {id: '8.1', name: 'Ηλεκτροκίνητοι περιστρεφόμενοι γερανοί', group: 'A'},
            {id: '8.2', name: 'Γερανοί σταθεροί παντός τύπου', group: 'A'},
            {id: '8.3', name: 'Ηλεκτροκίνητοι οικοδομικοί γερανοί', group: 'B'},
            {id: '8.4', name: 'Πλωτοί ηλεκτροκίνητοι γερανοί', group: 'A'},
            {id: '8.5', name: 'Ηλεκτροκίνητες γερανογέφυρες', group: 'A'},
            {id: '8.6', name: 'Ηλεκτροκίνητοι σταθεροί γερανοί τροφοδοτήσεως', group: 'B'},
            {id: '8.7', name: 'Ηλεκτροκίνητες ανυψωτικές πλατφόρμες', group: 'B'},
            {id: '8.8', name: 'Ηλεκτροκίνητα μηχανήματα φορτοεκφόρτωσης', group: 'A'},
            {id: '8.9', name: 'Ειδικά μηχανήματα αναβατώρες - πυλώνες', group: 'B'}
        ]
    };
    
    // Φόρτωση υποειδικοτήτων με βάση την επιλεγμένη ειδικότητα
    function loadSubSpecialities(specialityId) {
        const subSpecialityContainer = document.getElementById('subSpecialityContainer');
        const subSpecialitiesDiv = document.getElementById('subSpecialities');
        
        if (!specialityId) {
            subSpecialityContainer.style.display = 'none';
            return;
        }
        
        subSpecialityContainer.style.display = 'block';
        subSpecialitiesDiv.innerHTML = '';
        
        if (operatorSubSpecialities[specialityId]) {
            operatorSubSpecialities[specialityId].forEach(item => {
                const checkboxDiv = document.createElement('div');
                checkboxDiv.className = 'checkbox-group';
                
                // Έλεγχος αν η συγκεκριμένη υποειδικότητα είναι επιλεγμένη
                const isChecked = window.selectedSubSpecialities && window.selectedSubSpecialities.includes(item.id);
                
                checkboxDiv.innerHTML = `
                    <label class="checkbox-label">
                        <input type="checkbox" name="operator_sub_specialities[]" value="${item.id}" ${isChecked ? 'checked' : ''}>
                        <span>${item.id} - ${item.name} (Ομάδα ${item.group})</span>
                    </label>
                `;
                
                subSpecialitiesDiv.appendChild(checkboxDiv);
            });
        }
    }
    
    // Φόρτωση των επιλεγμένων υποειδικοτήτων κατά την αρχικοποίηση
    window.selectedSubSpecialities = [];
    
    // Φόρτωση των υποειδικοτήτων αν υπάρχει επιλεγμένη ειδικότητα
    const specialitySelect = document.getElementById('operator_speciality');
    if (specialitySelect) {
        specialitySelect.addEventListener('change', function() {
            loadSubSpecialities(this.value);
        });
        
        // Αρχική φόρτωση αν υπάρχει τιμή
        if (specialitySelect.value) {
            loadSubSpecialities(specialitySelect.value);
        }
    }
});
