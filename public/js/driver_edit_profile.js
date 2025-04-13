<!-- JavaScript για τη λειτουργικότητα της φόρμας -->
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
        if (addressInput) {
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
    });