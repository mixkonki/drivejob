/**
 * Διόρθωση προβλημάτων στη φόρμα προϋπηρεσίας οχημάτων
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Fix script loaded for vehicle experience form');

    // Εντοπισμός των στοιχείων της φόρμας
    const form = document.getElementById('vehicleExperienceForm');
    const transportTypeSelect = document.getElementById('new_transport_type');
    const vehicleTypeSelect = document.getElementById('new_vehicle_type');
    const employmentTypeSelect = document.getElementById('new_employment_type');
    const startDateInput = document.getElementById('new_start_date');
    const endDateInput = document.getElementById('new_end_date');
    const descriptionInput = document.getElementById('new_description');
    const addButton = document.getElementById('btn-add-experience');
    const vehicleExperienceData = document.getElementById('vehicle-experience-data');
    const experienceTableBody = document.getElementById('vehicle-experience-tbody');

    // Έλεγχος αν τα απαραίτητα στοιχεία υπάρχουν
    if (!form || !transportTypeSelect || !vehicleTypeSelect || !addButton || !vehicleExperienceData) {
        console.error('Missing required form elements');
        return;
    }

    // Διόρθωση 1: Βεβαιωνόμαστε ότι το vehicleExperiences είναι αρχικοποιημένο
    if (typeof vehicleExperiences === 'undefined') {
        console.log('Initializing vehicleExperiences array');
        window.vehicleExperiences = [];
    }

    // Διόρθωση 2: Αντικατάσταση της συνάρτησης updateVehicleTypes
    if (typeof updateVehicleTypes === 'function') {
        console.log('Fixing updateVehicleTypes function');
        
        // Αποθήκευση της αρχικής συνάρτησης
        const originalUpdateVehicleTypes = updateVehicleTypes;
        
        // Αντικατάσταση με διορθωμένη έκδοση
        window.updateVehicleTypes = function(transportTypeSelect, typeSelect) {
            console.log('Calling updateVehicleTypes function');
            
            // Αν δεν έχει οριστεί το typeSelect, χρησιμοποιούμε το vehicleTypeSelect
            if (!typeSelect) {
                typeSelect = vehicleTypeSelect;
            }
            
            // Καλούμε την αρχική συνάρτηση με τις σωστές παραμέτρους
            return originalUpdateVehicleTypes(transportTypeSelect, typeSelect);
        };
    } else {
        // Αν η συνάρτηση δεν υπάρχει, την ορίζουμε
        console.log('Creating updateVehicleTypes function');
        
        window.updateVehicleTypes = function(transportTypeSelect, typeSelect) {
            console.log('updateVehicleTypes called with arguments:', arguments);
            
            const transportType = transportTypeSelect.value;
            const typeDropdown = typeSelect || vehicleTypeSelect;
            
            console.log('Transport type changed to:', transportType);
            
            // Καθαρισμός του dropdown
            typeDropdown.innerHTML = '<option value="">Επιλέξτε τύπο οχήματος...</option>';
            
            // Αν έχει επιλεγεί είδος μεταφοράς, προσθέτουμε τους αντίστοιχους τύπους
            if (transportType) {
                // Ορισμός των κατηγοριών οχημάτων ανάλογα με το είδος μεταφοράς
                let categories = [];
                
                if (transportType === 'freight') {
                    // Εμπορευματικές μεταφορές
                    categories = ['lcv', 'rigid_truck', 'articulated', 'utility', 'construction', 'specialized'];
                } else if (transportType === 'passenger') {
                    // Επιβατικές μεταφορές
                    categories = ['taxi', 'minibus', 'bus', 'emergency'];
                }
                
                // Προσθήκη των τύπων οχημάτων για κάθε κατηγορία
                categories.forEach(category => {
                    if (vehicleTypes[category]) {
                        // Δημιουργία optgroup για την κατηγορία
                        const optgroup = document.createElement('optgroup');
                        optgroup.label = getCategoryName(category);
                        
                        // Προσθήκη των τύπων οχημάτων στο optgroup
                        for (const [value, text] of Object.entries(vehicleTypes[category])) {
                            const option = document.createElement('option');
                            option.value = category + '_' + value; // Συνδυασμός κατηγορίας και τύπου
                            option.textContent = text;
                            optgroup.appendChild(option);
                        }
                        
                        // Προσθήκη του optgroup στο dropdown
                        typeDropdown.appendChild(optgroup);
                    }
                });
            }
            
            return true;
        };
    }

    // Διόρθωση 3: Αντικατάσταση της συνάρτησης addVehicleExperience
    if (typeof addVehicleExperience === 'function') {
        console.log('Fixing addVehicleExperience function');
        
        // Αποθήκευση της αρχικής συνάρτησης
        const originalAddVehicleExperience = addVehicleExperience;
        
        // Αντικατάσταση με διορθωμένη έκδοση
        window.addVehicleExperience = function() {
            console.log('addVehicleExperience called');
            console.log('vehicleExperiences before add:', vehicleExperiences);
            
            // Καλούμε την αρχική συνάρτηση
            const result = originalAddVehicleExperience();
            
            console.log('vehicleExperiences after add:', vehicleExperiences);
            console.log('Result of addVehicleExperience:', result);
            
            return result;
        };
    }

    // Διόρθωση 4: Αντικατάσταση της συνάρτησης updateHiddenFields
    if (typeof updateHiddenFields === 'function') {
        console.log('Fixing updateHiddenFields function');
        
        // Αποθήκευση της αρχικής συνάρτησης
        const originalUpdateHiddenFields = updateHiddenFields;
        
        // Αντικατάσταση με διορθωμένη έκδοση
        window.updateHiddenFields = function() {
            console.log('updateHiddenFields called');
            
            // Έλεγχος αν το vehicleExperienceData υπάρχει
            if (!vehicleExperienceData) {
                console.error('vehicle-experience-data element not found');
                return;
            }
            
            // Έλεγχος αν το vehicleExperiences είναι κενό
            if (!vehicleExperiences || vehicleExperiences.length === 0) {
                console.warn('vehicleExperiences is empty, nothing to update');
                return;
            }
            
            try {
                // Καλούμε την αρχική συνάρτηση
                originalUpdateHiddenFields();
                
                // Έλεγχος αν τα κρυφά πεδία δημιουργήθηκαν
                const hiddenFields = document.querySelectorAll('input[type="hidden"][name^="vehicle_experience"]');
                console.log('Hidden fields count after update:', hiddenFields.length);
                
                if (hiddenFields.length === 0 && vehicleExperiences.length > 0) {
                    console.warn('No hidden fields created, manually creating them');
                    
                    // Καθαρισμός του container
                    const debugInfo = document.getElementById('debug-info');
                    vehicleExperienceData.innerHTML = '';
                    
                    if (debugInfo) {
                        vehicleExperienceData.appendChild(debugInfo);
                    }
                    
                    // Χειροκίνητη δημιουργία των κρυφών πεδίων
                    vehicleExperiences.forEach((exp, index) => {
                        const fields = `
                            <input type="hidden" name="vehicle_experience[${index}][vehicle_category]" value="${exp.vehicleCategory || ''}">
                            <input type="hidden" name="vehicle_experience[${index}][vehicle_type]" value="${exp.vehicleType || ''}">
                            <input type="hidden" name="vehicle_experience[${index}][transport_type]" value="${exp.transportType || 'freight'}">
                            <input type="hidden" name="vehicle_experience[${index}][employment_type]" value="${exp.employmentType || 'employee'}">
                            <input type="hidden" name="vehicle_experience[${index}][start_date]" value="${exp.startDate || ''}">
                            <input type="hidden" name="vehicle_experience[${index}][end_date]" value="${exp.endDate || ''}">
                            <input type="hidden" name="vehicle_experience[${index}][years]" value="${exp.years || 0}">
                            <input type="hidden" name="vehicle_experience[${index}][months]" value="${exp.months || 0}">
                            <input type="hidden" name="vehicle_experience[${index}][days]" value="${exp.days || 0}">
                            <input type="hidden" name="vehicle_experience[${index}][description]" value="${exp.description || ''}">
                        `;
                        
                        vehicleExperienceData.innerHTML += fields;
                    });
                    
                    console.log('Manually created hidden fields:', document.querySelectorAll('input[type="hidden"][name^="vehicle_experience"]').length);
                }
            } catch (error) {
                console.error('Error in updateHiddenFields:', error);
            }
        };
    } else {
        // Αν η συνάρτηση δεν υπάρχει, την ορίζουμε
        console.log('Creating updateHiddenFields function');
        
        window.updateHiddenFields = function() {
            console.log('updateHiddenFields called');
            
            // Έλεγχος αν το vehicleExperienceData υπάρχει
            if (!vehicleExperienceData) {
                console.error('vehicle-experience-data element not found');
                return;
            }
            
            // Έλεγχος αν το vehicleExperiences είναι κενό
            if (!vehicleExperiences || vehicleExperiences.length === 0) {
                console.warn('vehicleExperiences is empty, nothing to update');
                return;
            }
            
            try {
                // Καθαρισμός του container
                const debugInfo = document.getElementById('debug-info');
                vehicleExperienceData.innerHTML = '';
                
                if (debugInfo) {
                    vehicleExperienceData.appendChild(debugInfo);
                }
                
                // Χειροκίνητη δημιουργία των κρυφών πεδίων
                vehicleExperiences.forEach((exp, index) => {
                    const fields = `
                        <input type="hidden" name="vehicle_experience[${index}][vehicle_category]" value="${exp.vehicleCategory || ''}">
                        <input type="hidden" name="vehicle_experience[${index}][vehicle_type]" value="${exp.vehicleType || ''}">
                        <input type="hidden" name="vehicle_experience[${index}][transport_type]" value="${exp.transportType || 'freight'}">
                        <input type="hidden" name="vehicle_experience[${index}][employment_type]" value="${exp.employmentType || 'employee'}">
                        <input type="hidden" name="vehicle_experience[${index}][start_date]" value="${exp.startDate || ''}">
                        <input type="hidden" name="vehicle_experience[${index}][end_date]" value="${exp.endDate || ''}">
                        <input type="hidden" name="vehicle_experience[${index}][years]" value="${exp.years || 0}">
                        <input type="hidden" name="vehicle_experience[${index}][months]" value="${exp.months || 0}">
                        <input type="hidden" name="vehicle_experience[${index}][days]" value="${exp.days || 0}">
                        <input type="hidden" name="vehicle_experience[${index}][description]" value="${exp.description || ''}">
                    `;
                    
                    vehicleExperienceData.innerHTML += fields;
                });
                
                console.log('Manually created hidden fields:', document.querySelectorAll('input[type="hidden"][name^="vehicle_experience"]').length);
            } catch (error) {
                console.error('Error in updateHiddenFields:', error);
            }
        };
    }

    // Διόρθωση 5: Προσθήκη ελέγχου πριν την υποβολή της φόρμας
    if (form) {
        console.log('Adding form submit validation');
        
        form.addEventListener('submit', function(e) {
            console.log('Form submitted');
            
            // Καταγραφή των δεδομένων της φόρμας
            const formData = {};
            const formElements = form.elements;
            
            for (let i = 0; i < formElements.length; i++) {
                const element = formElements[i];
                if (element.name) {
                    formData[element.name] = element.value;
                }
            }
            
            console.log('Form data:', formData);
            
            // Καταγραφή των κρυφών πεδίων
            const hiddenFields = document.querySelectorAll('input[type="hidden"][name^="vehicle_experience"]');
            console.log('Hidden fields count on submit:', hiddenFields.length);
            
            const hiddenFieldsData = [];
            hiddenFields.forEach(function(field) {
                hiddenFieldsData.push({
                    name: field.name,
                    value: field.value
                });
            });
            
            console.log('Hidden fields data on submit:', hiddenFieldsData);
            
            // Έλεγχος αν υπάρχουν δεδομένα προϋπηρεσίας
            if (hiddenFields.length === 0) {
                console.warn('No vehicle experience data found in the form');
                
                // Έλεγχος αν υπάρχουν συμπληρωμένα πεδία στη φόρμα
                const transportType = formData.new_transport_type;
                const vehicleType = formData.new_vehicle_type;
                const employmentType = formData.new_employment_type;
                const startDate = formData.new_start_date;
                const endDate = formData.new_end_date;
                const description = formData.new_description;
                
                // Αν υπάρχουν συμπληρωμένα πεδία, προσθέτουμε τα δεδομένα στον πίνακα vehicleExperiences
                if (transportType && vehicleType) {
                    console.log('Found form data, adding to vehicleExperiences');
                    
                    // Υπολογισμός διάρκειας σε έτη, μήνες, ημέρες
                    let years = 0,
                        months = 0,
                        days = 0;
                    
                    if (startDate && endDate) {
                        const start = new Date(startDate);
                        const end = new Date(endDate);
                        
                        if (end < start) {
                            alert('Η ημερομηνία λήξης πρέπει να είναι μεταγενέστερη της ημερομηνίας έναρξης');
                            e.preventDefault();
                            return false;
                        }
                        
                        // Υπολογισμός διαφοράς σε χιλιοστά του δευτερολέπτου
                        const diffTime = Math.abs(end - start);
                        // Μετατροπή σε ημέρες
                        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                        
                        // Υπολογισμός ετών, μηνών, ημερών
                        years = Math.floor(diffDays / 365);
                        const remainingDays = diffDays % 365;
                        months = Math.floor(remainingDays / 30);
                        days = remainingDays % 30;
                    }
                    
                    // Εξαγωγή της κατηγορίας και του τύπου οχήματος από την τιμή του dropdown
                    const vehicleTypeValue = vehicleType;
                    const [vehicleCategory, vehicleTypeCode] = vehicleTypeValue.split('_');
                    
                    // Δημιουργία νέας εγγραφής
                    const newExperience = {
                        id: 0,
                        vehicleCategory: vehicleCategory,
                        vehicleType: vehicleTypeCode,
                        transportType: transportType,
                        employmentType: employmentType,
                        startDate: startDate,
                        endDate: endDate,
                        years: years,
                        months: months,
                        days: days,
                        description: description
                    };
                    
                    console.log('New experience:', newExperience);
                    
                    // Προσθήκη στον πίνακα
                    vehicleExperiences = [newExperience];
                    
                    console.log('vehicleExperiences array:', vehicleExperiences);
                    
                    // Ενημέρωση των κρυφών πεδίων
                    updateHiddenFields();
                    
                    // Έλεγχος αν δημιουργήθηκαν τα κρυφά πεδία
                    const updatedHiddenFields = document.querySelectorAll('input[type="hidden"][name^="vehicle_experience"]');
                    console.log('Hidden fields count after update:', updatedHiddenFields.length);
                    
                    if (updatedHiddenFields.length === 0) {
                        console.error('Failed to create hidden fields');
                        alert('Παρακαλώ προσθέστε τουλάχιστον μία εγγραφή προϋπηρεσίας πριν την υποβολή.');
                        e.preventDefault();
                        return false;
                    }
                    
                    console.log('Form validation passed, proceeding with submit');
                    return true;
                } else if (vehicleExperiences.length === 0) {
                    // Αν δεν υπάρχουν συμπληρωμένα πεδία και δεν υπάρχουν εγγραφές προϋπηρεσίας,
                    // εμφανίζουμε προειδοποίηση στον χρήστη
                    console.warn('No vehicle experience data found');
                    
                    // Ελέγχουμε αν υπάρχουν ήδη εγγραφές προϋπηρεσίας στη βάση δεδομένων
                    // Αν υπάρχουν, ρωτάμε τον χρήστη αν θέλει να τις διαγράψει
                    if (confirm('Δεν έχετε προσθέσει καμία εγγραφή προϋπηρεσίας. Αν συνεχίσετε, όλες οι υπάρχουσες εγγραφές προϋπηρεσίας θα διαγραφούν. Θέλετε να συνεχίσετε;')) {
                        console.log('User confirmed deletion of existing vehicle experience records');
                        return true;
                    } else {
                        console.log('User cancelled form submission');
                        e.preventDefault();
                        return false;
                    }
                }
            }
            
            console.log('Form validation passed, proceeding with submit');
        });
    }

    // Διόρθωση 6: Προσθήκη κουμπιού για χειροκίνητη ενημέρωση των κρυφών πεδίων
    const fixButton = document.createElement('button');
    fixButton.type = 'button';
    fixButton.className = 'btn-secondary';
    fixButton.style.marginTop = '20px';
    fixButton.style.marginRight = '10px';
    fixButton.textContent = 'Ενημέρωση Κρυφών Πεδίων';
    
    fixButton.addEventListener('click', function() {
        console.log('Manual update of hidden fields requested');
        updateHiddenFields();
        alert('Τα κρυφά πεδία ενημερώθηκαν. Μπορείτε τώρα να υποβάλετε τη φόρμα.');
    });
    
    // Προσθήκη του κουμπιού στη σελίδα
    if (vehicleExperienceData) {
        vehicleExperienceData.appendChild(fixButton);
    }

    // Διόρθωση 7: Προσθήκη κουμπιού για εμφάνιση διαγνωστικών πληροφοριών
    const debugButton = document.createElement('button');
    debugButton.type = 'button';
    debugButton.className = 'btn-secondary';
    debugButton.style.marginTop = '20px';
    debugButton.textContent = 'Εμφάνιση Διαγνωστικών Πληροφοριών';
    
    debugButton.addEventListener('click', function() {
        console.log('Debug button clicked');
        
        // Δημιουργία του container για τις διαγνωστικές πληροφορίες
        let debugInfo = document.getElementById('debug-info');
        
        if (!debugInfo) {
            debugInfo = document.createElement('div');
            debugInfo.id = 'debug-info';
            debugInfo.style.marginTop = '20px';
            debugInfo.style.padding = '10px';
            debugInfo.style.backgroundColor = '#f8f9fa';
            debugInfo.style.border = '1px solid #ddd';
            debugInfo.style.borderRadius = '5px';
            
            vehicleExperienceData.appendChild(debugInfo);
        }
        
        // Εμφάνιση των διαγνωστικών πληροφοριών
        debugInfo.innerHTML = `
            <h3>Διαγνωστικές Πληροφορίες</h3>
            <p><strong>vehicleExperiences:</strong> ${JSON.stringify(vehicleExperiences)}</p>
            <p><strong>Κρυφά πεδία:</strong> ${document.querySelectorAll('input[type="hidden"][name^="vehicle_experience"]').length}</p>
            <p><strong>Συναρτήσεις:</strong></p>
            <ul>
                <li>updateHiddenFields: ${typeof updateHiddenFields === 'function' ? 'Ορίστηκε' : 'Δεν ορίστηκε'}</li>
                <li>updateVehicleTypes: ${typeof updateVehicleTypes === 'function' ? 'Ορίστηκε' : 'Δεν ορίστηκε'}</li>
                <li>addVehicleExperience: ${typeof addVehicleExperience === 'function' ? 'Ορίστηκε' : 'Δεν ορίστηκε'}</li>
                <li>updateExperienceTable: ${typeof updateExperienceTable === 'function' ? 'Ορίστηκε' : 'Δεν ορίστηκε'}</li>
                <li>removeVehicleExperience: ${typeof removeVehicleExperience === 'function' ? 'Ορίστηκε' : 'Δεν ορίστηκε'}</li>
                <li>loadExistingExperiences: ${typeof loadExistingExperiences === 'function' ? 'Ορίστηκε' : 'Δεν ορίστηκε'}</li>
            </ul>
        `;
    });
    
    // Προσθήκη του κουμπιού στη σελίδα
    if (vehicleExperienceData) {
        vehicleExperienceData.appendChild(debugButton);
    }

    console.log('Fix script initialization complete');
});
