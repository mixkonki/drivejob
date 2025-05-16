/**
 * Διαγνωστικό script για τη φόρμα προϋπηρεσίας οχημάτων
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Debug script loaded for vehicle experience form');

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
    const debugInfo = document.getElementById('debug-info');
    const hiddenFieldsDebug = document.getElementById('hidden-fields-debug');
    const experienceCount = document.getElementById('experience-count');
    const saveReminder = document.getElementById('save-reminder');

    // Καταγραφή των στοιχείων που βρέθηκαν
    console.log('Form elements:', {
        form,
        transportTypeSelect,
        vehicleTypeSelect,
        employmentTypeSelect,
        startDateInput,
        endDateInput,
        descriptionInput,
        addButton,
        vehicleExperienceData,
        debugInfo,
        hiddenFieldsDebug,
        experienceCount,
        saveReminder
    });

    // Καταγραφή των global μεταβλητών
    console.log('Global variables:', {
        vehicleExperiences: typeof vehicleExperiences !== 'undefined' ? vehicleExperiences : 'Not defined',
        vehicleTypes: typeof vehicleTypes !== 'undefined' ? vehicleTypes : 'Not defined',
        driverVehicleExperience: typeof driverVehicleExperience !== 'undefined' ? driverVehicleExperience : 'Not defined'
    });

    // Παρακολούθηση των event listeners
    if (addButton) {
        console.log('Adding debug event listener to add button');
        addButton.addEventListener('click', function() {
            console.log('Add button clicked');
            console.log('Form values:', {
                transportType: transportTypeSelect ? transportTypeSelect.value : 'N/A',
                vehicleType: vehicleTypeSelect ? vehicleTypeSelect.value : 'N/A',
                employmentType: employmentTypeSelect ? employmentTypeSelect.value : 'N/A',
                startDate: startDateInput ? startDateInput.value : 'N/A',
                endDate: endDateInput ? endDateInput.value : 'N/A',
                description: descriptionInput ? descriptionInput.value : 'N/A'
            });
        });
    }

    // Παρακολούθηση του transportTypeSelect
    if (transportTypeSelect) {
        console.log('Adding debug event listener to transport type select');
        transportTypeSelect.addEventListener('change', function() {
            console.log('Transport type changed to:', this.value);
            console.log('Vehicle type select before update:', vehicleTypeSelect ? vehicleTypeSelect.innerHTML : 'N/A');
            
            // Καταγραφή της κλήσης της συνάρτησης updateVehicleTypes
            if (typeof updateVehicleTypes === 'function') {
                console.log('Calling updateVehicleTypes function');
                // Αποθήκευση της αρχικής συνάρτησης
                const originalUpdateVehicleTypes = updateVehicleTypes;
                
                // Αντικατάσταση με wrapper για debugging
                window.updateVehicleTypes = function() {
                    console.log('updateVehicleTypes called with arguments:', arguments);
                    const result = originalUpdateVehicleTypes.apply(this, arguments);
                    console.log('Vehicle type select after update:', vehicleTypeSelect ? vehicleTypeSelect.innerHTML : 'N/A');
                    return result;
                };
            } else {
                console.error('updateVehicleTypes function not found');
            }
        });
    }

    // Παρακολούθηση της συνάρτησης updateHiddenFields
    if (typeof updateHiddenFields === 'function') {
        console.log('Wrapping updateHiddenFields function for debugging');
        // Αποθήκευση της αρχικής συνάρτησης
        const originalUpdateHiddenFields = updateHiddenFields;
        
        // Αντικατάσταση με wrapper για debugging
        window.updateHiddenFields = function() {
            console.log('updateHiddenFields called');
            console.log('vehicleExperiences before update:', JSON.parse(JSON.stringify(vehicleExperiences || [])));
            console.log('vehicle-experience-data before update:', vehicleExperienceData ? vehicleExperienceData.innerHTML : 'N/A');
            
            const result = originalUpdateHiddenFields.apply(this, arguments);
            
            console.log('vehicleExperiences after update:', JSON.parse(JSON.stringify(vehicleExperiences || [])));
            console.log('vehicle-experience-data after update:', vehicleExperienceData ? vehicleExperienceData.innerHTML : 'N/A');
            
            // Καταγραφή των κρυφών πεδίων
            const hiddenFields = document.querySelectorAll('input[type="hidden"][name^="vehicle_experience"]');
            console.log('Hidden fields count:', hiddenFields.length);
            
            const hiddenFieldsData = Array.from(hiddenFields).map(field => {
                return {
                    name: field.name,
                    value: field.value
                };
            });
            console.log('Hidden fields data:', hiddenFieldsData);
            
            return result;
        };
    } else {
        console.error('updateHiddenFields function not found');
    }

    // Παρακολούθηση της συνάρτησης addVehicleExperience
    if (typeof addVehicleExperience === 'function') {
        console.log('Wrapping addVehicleExperience function for debugging');
        // Αποθήκευση της αρχικής συνάρτησης
        const originalAddVehicleExperience = addVehicleExperience;
        
        // Αντικατάσταση με wrapper για debugging
        window.addVehicleExperience = function() {
            console.log('addVehicleExperience called');
            console.log('vehicleExperiences before add:', JSON.parse(JSON.stringify(vehicleExperiences || [])));
            
            const result = originalAddVehicleExperience.apply(this, arguments);
            
            console.log('vehicleExperiences after add:', JSON.parse(JSON.stringify(vehicleExperiences || [])));
            console.log('Result of addVehicleExperience:', result);
            
            return result;
        };
    } else {
        console.error('addVehicleExperience function not found');
    }

    // Παρακολούθηση της υποβολής της φόρμας
    if (form) {
        console.log('Adding debug event listener to form submit');
        form.addEventListener('submit', function(e) {
            console.log('Form submitted');
            
            // Καταγραφή των δεδομένων της φόρμας
            const formData = new FormData(form);
            const formDataObj = {};
            
            for (const [key, value] of formData.entries()) {
                formDataObj[key] = value;
            }
            
            console.log('Form data:', formDataObj);
            
            // Καταγραφή των κρυφών πεδίων
            const hiddenFields = document.querySelectorAll('input[type="hidden"][name^="vehicle_experience"]');
            console.log('Hidden fields count on submit:', hiddenFields.length);
            
            const hiddenFieldsData = Array.from(hiddenFields).map(field => {
                return {
                    name: field.name,
                    value: field.value
                };
            });
            console.log('Hidden fields data on submit:', hiddenFieldsData);
            
            // Έλεγχος αν υπάρχουν δεδομένα προϋπηρεσίας
            if (hiddenFields.length === 0) {
                console.warn('No vehicle experience data found in the form');
                alert('ΠΡΟΣΟΧΗ: Δεν βρέθηκαν δεδομένα προϋπηρεσίας στη φόρμα. Παρακαλώ προσθέστε τουλάχιστον μία εγγραφή προϋπηρεσίας πριν την υποβολή.');
                e.preventDefault();
                return false;
            }
        });
    }

    // Προσθήκη κουμπιού για εμφάνιση διαγνωστικών πληροφοριών
    const debugButton = document.createElement('button');
    debugButton.type = 'button';
    debugButton.className = 'btn-secondary';
    debugButton.style.marginTop = '20px';
    debugButton.textContent = 'Εμφάνιση Διαγνωστικών Πληροφοριών';
    
    debugButton.addEventListener('click', function() {
        console.log('Debug button clicked');
        
        // Δημιουργία του div για τις διαγνωστικές πληροφορίες
        let debugDiv = document.getElementById('js-debug-info');
        
        if (!debugDiv) {
            debugDiv = document.createElement('div');
            debugDiv.id = 'js-debug-info';
            debugDiv.style.margin = '20px 0';
            debugDiv.style.padding = '15px';
            debugDiv.style.backgroundColor = '#f8f9fa';
            debugDiv.style.border = '1px solid #ddd';
            debugDiv.style.borderRadius = '5px';
            
            // Προσθήκη του div στη σελίδα
            if (debugInfo) {
                debugInfo.parentNode.insertBefore(debugDiv, debugInfo.nextSibling);
            } else if (vehicleExperienceData) {
                vehicleExperienceData.appendChild(debugDiv);
            } else {
                document.querySelector('.vehicle-experience-container').appendChild(debugDiv);
            }
        }
        
        // Ενημέρωση του περιεχομένου
        debugDiv.innerHTML = `
            <h4>Διαγνωστικές Πληροφορίες JavaScript</h4>
            <p><strong>vehicleExperiences:</strong> ${vehicleExperiences ? vehicleExperiences.length : 'Not defined'} εγγραφές</p>
            <pre>${JSON.stringify(vehicleExperiences || [], null, 2)}</pre>
            
            <p><strong>Κρυφά πεδία:</strong> ${document.querySelectorAll('input[type="hidden"][name^="vehicle_experience"]').length} πεδία</p>
            <ul>
                ${Array.from(document.querySelectorAll('input[type="hidden"][name^="vehicle_experience"]')).map(field => {
                    return `<li>${field.name} = ${field.value}</li>`;
                }).join('')}
            </ul>
            
            <p><strong>Στοιχεία φόρμας:</strong></p>
            <ul>
                <li>Form: ${form ? 'Found' : 'Not found'}</li>
                <li>Transport Type Select: ${transportTypeSelect ? 'Found' : 'Not found'}</li>
                <li>Vehicle Type Select: ${vehicleTypeSelect ? 'Found' : 'Not found'}</li>
                <li>Employment Type Select: ${employmentTypeSelect ? 'Found' : 'Not found'}</li>
                <li>Start Date Input: ${startDateInput ? 'Found' : 'Not found'}</li>
                <li>End Date Input: ${endDateInput ? 'Found' : 'Not found'}</li>
                <li>Description Input: ${descriptionInput ? 'Found' : 'Not found'}</li>
                <li>Add Button: ${addButton ? 'Found' : 'Not found'}</li>
                <li>Vehicle Experience Data: ${vehicleExperienceData ? 'Found' : 'Not found'}</li>
                <li>Debug Info: ${debugInfo ? 'Found' : 'Not found'}</li>
                <li>Hidden Fields Debug: ${hiddenFieldsDebug ? 'Found' : 'Not found'}</li>
                <li>Experience Count: ${experienceCount ? 'Found' : 'Not found'}</li>
                <li>Save Reminder: ${saveReminder ? 'Found' : 'Not found'}</li>
            </ul>
            
            <p><strong>Συναρτήσεις:</strong></p>
            <ul>
                <li>updateVehicleTypes: ${typeof updateVehicleTypes === 'function' ? 'Defined' : 'Not defined'}</li>
                <li>addVehicleExperience: ${typeof addVehicleExperience === 'function' ? 'Defined' : 'Not defined'}</li>
                <li>updateHiddenFields: ${typeof updateHiddenFields === 'function' ? 'Defined' : 'Not defined'}</li>
                <li>deleteVehicleExperience: ${typeof deleteVehicleExperience === 'function' ? 'Defined' : 'Not defined'}</li>
                <li>updateExperienceTable: ${typeof updateExperienceTable === 'function' ? 'Defined' : 'Not defined'}</li>
                <li>calculateTotals: ${typeof calculateTotals === 'function' ? 'Defined' : 'Not defined'}</li>
            </ul>
        `;
    });
    
    // Προσθήκη του κουμπιού στη σελίδα
    if (vehicleExperienceData) {
        vehicleExperienceData.appendChild(debugButton);
    } else {
        const container = document.querySelector('.vehicle-experience-container');
        if (container) {
            container.appendChild(debugButton);
        }
    }

    console.log('Debug script initialization complete');
});
