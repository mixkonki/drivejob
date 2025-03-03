document.addEventListener('DOMContentLoaded', () => {
    const birthDateInput = document.getElementById('birth_date');
    const ageDisplay = document.getElementById('age_display');
    const tabsContainer = document.getElementById('tabs_container');
    const saveButton = document.querySelector('.btn-save');
    const addressInput = document.getElementById('address');
    const editButton = document.getElementById('editButton');
    const formFields = document.querySelectorAll('#driverProfileForm input, #driverProfileForm select');

    console.log('Script loaded, fields found: ', formFields.length);
    formFields.forEach(field => {
        console.log('Field: ', field.name, ' Value: ', field.value);
    });

    const checkboxToTabMap = {
        'driving_license': 'driving_license_tab',
        'adr_certificate': 'adr_certificate_tab',
        'operator_license': 'operator_license_tab',
        'training_seminars': 'training_seminars_tab'
    };

    // **Αρχικοποίηση πεδίων ως readonly και ορισμός defaultValue**
    formFields.forEach(field => {
        field.readOnly = true; // Κλείδωμα των πεδίων κατά την αρχική φόρτωση
        field.defaultValue = field.value; // Ορισμός αρχικής τιμής στο defaultValue
    });

    // Ενεργοποίηση πεδίων όταν πατηθεί το κουμπί “Επεξεργασία”
    if (editButton) {
        editButton.addEventListener('click', () => {
            formFields.forEach(field => {
                field.readOnly = false; // Ξεκλείδωμα των πεδίων για επεξεργασία
            });
            editButton.style.display = 'none'; // Απόκρυψη του κουμπιού "Επεξεργασία"
            saveButton.style.display = 'inline'; // Εμφάνιση του κουμπιού "Αποθήκευση"
        });
    }

    // Υπολογισμός ηλικίας κατά την αλλαγή της ημερομηνίας γέννησης
    if (birthDateInput) {
        birthDateInput.addEventListener('change', () => {
            const birthDate = new Date(birthDateInput.value);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();

            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }

            ageDisplay.textContent = !isNaN(age) ? `Ηλικία: ${age}` : '';
        });
    }

    // Εμφάνιση/απόκρυψη tabs όταν αλλάζουν τα checkboxes
    for (const checkboxId in checkboxToTabMap) {
        const checkbox = document.getElementById(checkboxId);
        const tab = document.getElementById(checkboxToTabMap[checkboxId]);

        if (checkbox && tab) {
            checkbox.addEventListener('change', () => {
                tab.classList.toggle('hidden', !checkbox.checked);
            });
        }
    }

    // Λειτουργία Google Places Autocomplete
    if (addressInput) {
        const autocomplete = new google.maps.places.Autocomplete(addressInput, {
            types: ['geocode'], // Περιορισμός μόνο σε διευθύνσεις
        });

        // Καταχώρηση ακροατή για αλλαγές στην επιλογή της διεύθυνσης
        autocomplete.addListener('place_changed', () => {
            const place = autocomplete.getPlace();

            if (!place.address_components) {
                return;
            }

            const addressComponents = place.address_components;

            // Συμπλήρωση της πόλης αν υπάρχει
            const cityComponent = addressComponents.find(component =>
                component.types.includes('locality')
            );
            if (cityComponent) {
                document.getElementById('city').value = cityComponent.long_name;
            }

            // Συμπλήρωση της χώρας αν υπάρχει
            const countryComponent = addressComponents.find(component =>
                component.types.includes('country')
            );
            if (countryComponent) {
                document.getElementById('country').value = countryComponent.long_name;
            }

            // Συμπλήρωση του ταχυδρομικού κώδικα αν υπάρχει
            const postalCodeComponent = addressComponents.find(component =>
                component.types.includes('postal_code')
            );
            if (postalCodeComponent) {
                document.getElementById('postal_code').value = postalCodeComponent.long_name;
            }
        });
    }

    // Λειτουργία κουμπιού αποθήκευσης
    if (saveButton) {
        saveButton.addEventListener('click', (e) => {
            e.preventDefault();
            
            // **Έλεγχος αν έχουν γίνει αλλαγές**
            let changesMade = Array.from(formFields).some(field => field.value !== field.defaultValue);

            if (!changesMade) {
                alert('Δεν έχουν γίνει αλλαγές.');
                return;
            }

            // Ενημέρωση defaultValue μετά την αποθήκευση
            formFields.forEach(field => {
                field.defaultValue = field.value;
            });

            // Υποβολή φόρμας
            document.getElementById('driverProfileForm').submit();
        });
    }
});
