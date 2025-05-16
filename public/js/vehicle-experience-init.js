/**
 * Αρχικοποίηση των συναρτήσεων για τη φόρμα προϋπηρεσίας οχημάτων
 * Αυτό το αρχείο πρέπει να φορτωθεί πριν από τα άλλα scripts
 */

// Αρχικοποίηση των μεταβλητών
window.vehicleExperiences = [];

// Αρχικοποίηση των τύπων οχημάτων
window.vehicleTypes = {
    'lcv': {
        'panel_van': 'Κλειστό Van',
        'pickup_truck': 'Van με καρότσα (Pick-up)',
        'small_refrigerated': 'Μικρό φορτηγό ψυγείο/κατάψυξης'
    },
    'rigid_truck': {
        'distribution_truck': 'Φορτηγό Διανομών',
        'refrigerated_truck': 'Φορτηγό Ψυγείο/Κατάψυξης',
        'platform_truck': 'Φορτηγό Πλατφόρμα',
        'dump_truck': 'Ανατρεπόμενο Φορτηγό',
        'tanker_truck': 'Βυτιοφόρο (άκαμπτο)',
        'car_carrier': 'Όχημα Μεταφοράς Οχημάτων',
        'silo_truck': 'Φορτηγό με Σιλό',
        'crane_truck': 'Φορτηγό με Γερανό',
        'livestock_truck': 'Όχημα Μεταφοράς Ζώων'
    },
    'articulated': {
        'curtainsider': 'Επικαθήμενο με Μουσαμά',
        'reefer': 'Επικαθήμενο Ψυγείο/Κατάψυξη',
        'box_trailer': 'Επικαθήμενο Κλειστού Τύπου',
        'flatbed': 'Επικαθήμενο Πλατφόρμα',
        'tipper': 'Επικαθήμενο Ανατρεπόμενο',
        'tanker': 'Επικαθήμενο Βυτίο',
        'silo': 'Επικαθήμενο Σιλό',
        'container': 'Επικαθήμενο Μεταφοράς Εμπορευματοκιβωτίων',
        'car_transporter': 'Επικαθήμενο Μεταφοράς Οχημάτων',
        'livestock': 'Επικαθήμενο Μεταφοράς Ζώων',
        'low_loader': 'Επικαθήμενο Χαμηλής Κλίνης',
        'drawbar': 'Φορτηγό με Ρυμουλκούμενο (συρμός)'
    },
    'taxi': {
        'standard_taxi': 'Κλασικό Ταξί',
        'luxury_taxi': 'Ταξί Πολυτελείας',
        'accessible_taxi': 'Ταξί για ΑμεΑ'
    },
    'minibus': {
        'standard_minibus': 'Τυπικό Μικρό Λεωφορείο',
        'school_minibus': 'Σχολικό Μικρό Λεωφορείο',
        'accessible_minibus': 'Μικρό Λεωφορείο για ΑμεΑ',
        'luxury_minibus': 'Μικρό Λεωφορείο Πολυτελείας'
    },
    'bus': {
        'city_bus': 'Αστικό Λεωφορείο',
        'intercity_bus': 'Υπεραστικό Λεωφορείο',
        'coach': 'Τουριστικό Πούλμαν',
        'double_decker': 'Διώροφο Λεωφορείο',
        'articulated_bus': 'Αρθρωτό Λεωφορείο',
        'school_bus': 'Σχολικό Λεωφορείο'
    },
    'utility': {
        'garbage_truck': 'Απορριμματοφόρο',
        'street_sweeper': 'Σάρωθρο Δρόμων',
        'snow_plow': 'Εκχιονιστικό',
        'water_truck': 'Υδροφόρα',
        'maintenance_vehicle': 'Όχημα Συντήρησης'
    },
    'construction': {
        'concrete_mixer': 'Μπετονιέρα',
        'crane_truck': 'Γερανοφόρο',
        'excavator_transport': 'Μεταφορά Εκσκαφέων',
        'bulldozer_transport': 'Μεταφορά Μπουλντόζας'
    },
    'emergency': {
        'ambulance': 'Ασθενοφόρο',
        'fire_truck': 'Πυροσβεστικό',
        'police_vehicle': 'Αστυνομικό Όχημα',
        'rescue_vehicle': 'Όχημα Διάσωσης'
    },
    'specialized': {
        'mobile_workshop': 'Κινητό Συνεργείο',
        'mobile_library': 'Κινητή Βιβλιοθήκη',
        'mobile_medical': 'Κινητή Ιατρική Μονάδα',
        'food_truck': 'Κινητή Καντίνα',
        'other': 'Άλλο Εξειδικευμένο Όχημα'
    }
};

// Ορισμός της συνάρτησης updateHiddenFields
window.updateHiddenFields = function() {
    console.log('Original updateHiddenFields called');
    
    // Εντοπισμός του container για τα κρυφά πεδία
    const vehicleExperienceData = document.getElementById('vehicle-experience-data');
    
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
        
        console.log('Hidden fields created:', document.querySelectorAll('input[type="hidden"][name^="vehicle_experience"]').length);
    } catch (error) {
        console.error('Error in updateHiddenFields:', error);
    }
};

// Ορισμός της συνάρτησης addVehicleExperience
window.addVehicleExperience = function() {
    console.log('Original addVehicleExperience called');
    
    const typeSelect = document.getElementById('new_vehicle_type');
    const transportTypeSelect = document.getElementById('new_transport_type');
    const employmentTypeSelect = document.getElementById('new_employment_type');
    const startDateInput = document.getElementById('new_start_date');
    const endDateInput = document.getElementById('new_end_date');
    const descriptionInput = document.getElementById('new_description');
    
    // Έλεγχος υποχρεωτικών πεδίων
    if (!transportTypeSelect.value) {
        alert('Παρακαλώ επιλέξτε είδος μεταφοράς');
        return false;
    }
    
    if (!typeSelect.value) {
        alert('Παρακαλώ επιλέξτε τύπο οχήματος');
        return false;
    }
    
    // Υπολογισμός διάρκειας σε έτη, μήνες, ημέρες
    let years = 0,
        months = 0,
        days = 0;
    
    if (startDateInput.value && endDateInput.value) {
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);
        
        if (endDate < startDate) {
            alert('Η ημερομηνία λήξης πρέπει να είναι μεταγενέστερη της ημερομηνίας έναρξης');
            return false;
        }
        
        // Υπολογισμός διαφοράς σε χιλιοστά του δευτερολέπτου
        const diffTime = Math.abs(endDate - startDate);
        // Μετατροπή σε ημέρες
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        // Υπολογισμός ετών, μηνών, ημερών
        years = Math.floor(diffDays / 365);
        const remainingDays = diffDays % 365;
        months = Math.floor(remainingDays / 30);
        days = remainingDays % 30;
    }
    
    // Εξαγωγή της κατηγορίας και του τύπου οχήματος από την τιμή του dropdown
    const vehicleTypeValue = typeSelect.value;
    const [vehicleCategory, vehicleType] = vehicleTypeValue.split('_');
    
    // Δημιουργία νέας εγγραφής
    const newExperience = {
        id: vehicleExperiences.length,
        vehicleCategory: vehicleCategory,
        vehicleType: vehicleType,
        transportType: transportTypeSelect.value,
        employmentType: employmentTypeSelect.value,
        startDate: startDateInput.value,
        endDate: endDateInput.value,
        years: years,
        months: months,
        days: days,
        description: descriptionInput.value
    };
    
    console.log('New experience:', newExperience);
    
    // Προσθήκη στον πίνακα
    vehicleExperiences.push(newExperience);
    
    console.log('vehicleExperiences array:', vehicleExperiences);
    
    // Ενημέρωση του πίνακα
    updateExperienceTable();
    
    // Υπολογισμός συνόλων
    calculateTotals();
    
    // Καθαρισμός της φόρμας
    typeSelect.innerHTML = '<option value="">Επιλέξτε πρώτα είδος μεταφοράς...</option>';
    transportTypeSelect.value = '';
    employmentTypeSelect.value = '';
    startDateInput.value = '';
    endDateInput.value = '';
    descriptionInput.value = '';
    
    // Ενημέρωση των κρυφών πεδίων για αποθήκευση
    updateHiddenFields();
    
    // Εμφάνιση του μηνύματος υπενθύμισης αποθήκευσης
    const saveReminder = document.getElementById('save-reminder');
    if (saveReminder) {
        saveReminder.style.display = 'block';
    }
    
    // Προσθήκη κλάσης για να τονίσουμε τα κουμπιά αποθήκευσης
    const saveButtons = document.querySelectorAll('.form-actions .btn-save');
    saveButtons.forEach(button => {
        button.classList.add('highlight-save-button');
    });
    
    return true;
};

// Ορισμός της συνάρτησης updateVehicleTypes
window.updateVehicleTypes = function(transportTypeSelect, typeSelect) {
    console.log('Original updateVehicleTypes called with arguments:', arguments);
    
    const transportType = transportTypeSelect.value;
    const typeDropdown = typeSelect;
    
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
            if (window.vehicleTypes && window.vehicleTypes[category]) {
                // Δημιουργία optgroup για την κατηγορία
                const optgroup = document.createElement('optgroup');
                optgroup.label = getCategoryName(category);
                
                // Προσθήκη των τύπων οχημάτων στο optgroup
                for (const [value, text] of Object.entries(window.vehicleTypes[category])) {
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

// Ορισμός της συνάρτησης updateExperienceTable
window.updateExperienceTable = function() {
    console.log('Original updateExperienceTable called');
    
    const tbody = document.getElementById('vehicle-experience-tbody');
    
    // Έλεγχος αν το tbody υπάρχει
    if (!tbody) {
        console.error('vehicle-experience-tbody element not found');
        return;
    }
    
    tbody.innerHTML = '';
    
    if (vehicleExperiences.length === 0) {
        const tr = document.createElement('tr');
        tr.innerHTML = '<td colspan="5" class="text-center">Δεν έχει καταχωρηθεί προϋπηρεσία</td>';
        tbody.appendChild(tr);
        return;
    }
    
    vehicleExperiences.forEach((exp, index) => {
        const tr = document.createElement('tr');
        
        // Εύρεση των ονομάτων για εμφάνιση
        const categoryName = getCategoryName(exp.vehicleCategory);
        const typeName = getTypeName(exp.vehicleCategory, exp.vehicleType);
        const transportTypeName = exp.transportType === 'freight' ? 'Εμπορευματικές' : 'Επιβατικές';
        
        // Διάρκεια σε έτη, μήνες, ημέρες
        const duration = `${exp.years} έτη, ${exp.months} μήνες, ${exp.days} ημέρες`;
        
        tr.innerHTML = `
            <td>${index + 1}</td>
            <td>${typeName || categoryName}</td>
            <td>${transportTypeName}</td>
            <td>${duration}</td>
            <td>
                <button type="button" class="btn-delete-experience" data-id="${exp.id}">
                    <i class="fas fa-trash"></i> Διαγραφή
                </button>
            </td>
        `;
        
        tbody.appendChild(tr);
    });
    
    // Προσθήκη event listeners για τα κουμπιά διαγραφής
    document.querySelectorAll('.btn-delete-experience').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = parseInt(this.getAttribute('data-id'));
            deleteVehicleExperience(id);
        });
    });
};

// Ορισμός της συνάρτησης calculateTotals
window.calculateTotals = function() {
    console.log('Original calculateTotals called');
    
    // Υπολογισμός συνόλων για εμπορευματικές και επιβατικές μεταφορές
    let freightYears = 0,
        freightMonths = 0,
        freightDays = 0;
    let passengerYears = 0,
        passengerMonths = 0,
        passengerDays = 0;
    
    vehicleExperiences.forEach(exp => {
        if (exp.transportType === 'freight') {
            freightYears += exp.years;
            freightMonths += exp.months;
            freightDays += exp.days;
        } else if (exp.transportType === 'passenger') {
            passengerYears += exp.years;
            passengerMonths += exp.months;
            passengerDays += exp.days;
        }
    });
    
    // Κανονικοποίηση των μηνών και ημερών
    freightMonths += Math.floor(freightDays / 30);
    freightDays = freightDays % 30;
    freightYears += Math.floor(freightMonths / 12);
    freightMonths = freightMonths % 12;
    
    passengerMonths += Math.floor(passengerDays / 30);
    passengerDays = passengerDays % 30;
    passengerYears += Math.floor(passengerMonths / 12);
    passengerMonths = passengerMonths % 12;
    
    // Υπολογισμός συνολικής προϋπηρεσίας
    const totalYears = freightYears + passengerYears;
    const totalMonths = freightMonths + passengerMonths;
    const totalDays = freightDays + passengerDays;
    
    // Κανονικοποίηση του συνόλου
    let normalizedTotalYears = totalYears;
    let normalizedTotalMonths = totalMonths;
    let normalizedTotalDays = totalDays;
    
    normalizedTotalMonths += Math.floor(normalizedTotalDays / 30);
    normalizedTotalDays = normalizedTotalDays % 30;
    normalizedTotalYears += Math.floor(normalizedTotalMonths / 12);
    normalizedTotalMonths = normalizedTotalMonths % 12;
    
    // Ενημέρωση των στοιχείων στον πίνακα
    const freightTotal = document.getElementById('freight-total');
    const passengerTotal = document.getElementById('passenger-total');
    const totalExperience = document.getElementById('total-experience');
    
    if (freightTotal) {
        freightTotal.textContent = `${freightYears} έτη, ${freightMonths} μήνες, ${freightDays} ημέρες`;
    }
    
    if (passengerTotal) {
        passengerTotal.textContent = `${passengerYears} έτη, ${passengerMonths} μήνες, ${passengerDays} ημέρες`;
    }
    
    if (totalExperience) {
        totalExperience.textContent = `${normalizedTotalYears} έτη, ${normalizedTotalMonths} μήνες, ${normalizedTotalDays} ημέρες`;
    }
};

// Ορισμός της συνάρτησης deleteVehicleExperience
window.deleteVehicleExperience = function(id) {
    console.log('Original deleteVehicleExperience called with id:', id);
    
    // Αφαίρεση από τον πίνακα
    vehicleExperiences = vehicleExperiences.filter(exp => exp.id !== id);
    
    // Ενημέρωση του πίνακα
    updateExperienceTable();
    
    // Υπολογισμός συνόλων
    calculateTotals();
    
    // Ενημέρωση των κρυφών πεδίων για αποθήκευση
    updateHiddenFields();
};

// Ορισμός της συνάρτησης removeVehicleExperience (alias για deleteVehicleExperience)
window.removeVehicleExperience = function(id) {
    console.log('removeVehicleExperience called with id:', id);
    return deleteVehicleExperience(id);
};

// Ορισμός των βοηθητικών συναρτήσεων
window.getCategoryName = function(category) {
    const categories = {
        'lcv': 'Ελαφρά Επαγγελματικά Οχήματα',
        'rigid_truck': 'Μεσαία & Βαρέα Φορτηγά',
        'articulated': 'Αρθρωτά/Συρόμενα Οχήματα',
        'taxi': 'Ταξί',
        'minibus': 'Μικρό Λεωφορείο',
        'bus': 'Λεωφορεία & Πούλμαν',
        'utility': 'Οχήματα Δημοτικά/Κοινής Ωφέλειας',
        'construction': 'Οχήματα Έργων/Κατασκευών',
        'emergency': 'Οχήματα Έκτακτης Ανάγκης',
        'specialized': 'Άλλα Εξειδικευμένα Οχήματα'
    };
    
    return categories[category] || category;
};

window.getTypeName = function(category, type) {
    if (category && type && window.vehicleTypes && window.vehicleTypes[category] && window.vehicleTypes[category][type]) {
        return window.vehicleTypes[category][type];
    }
    return '';
};

// Ορισμός της συνάρτησης loadExistingExperiences
window.loadExistingExperiences = function() {
    console.log('loadExistingExperiences called');
    
    // Έλεγχος αν υπάρχουν ήδη δεδομένα προϋπηρεσίας
    const existingExperience = document.querySelectorAll('input[type="hidden"][name^="vehicle_experience"]');
    
    if (existingExperience.length === 0) {
        console.log('No existing experience data found');
        return false;
    }
    
    console.log('Found existing experience data:', existingExperience.length, 'fields');
    
    // Δημιουργία ενός αντικειμένου για την αποθήκευση των δεδομένων
    const experienceData = {};
    
    // Συλλογή των δεδομένων από τα κρυφά πεδία
    existingExperience.forEach(function(field) {
        const name = field.name;
        const value = field.value;
        
        // Εξαγωγή του δείκτη και του ονόματος του πεδίου από το όνομα του πεδίου
        const matches = name.match(/vehicle_experience\[(\d+)\]\[([^\]]+)\]/);
        
        if (matches) {
            const index = matches[1];
            const fieldName = matches[2];
            
            // Δημιουργία του αντικειμένου για τον δείκτη αν δεν υπάρχει
            if (!experienceData[index]) {
                experienceData[index] = {};
            }
            
            // Αποθήκευση της τιμής
            experienceData[index][fieldName] = value;
        }
    });
    
    console.log('Parsed experience data:', experienceData);
    
    // Μετατροπή του αντικειμένου σε πίνακα
    const experienceArray = Object.values(experienceData);
    
    console.log('Experience array:', experienceArray);
    
    // Αποθήκευση των δεδομένων στον πίνακα vehicleExperiences
    window.vehicleExperiences = experienceArray;
    
    // Ενημέρωση του πίνακα
    updateExperienceTable();
    
    // Υπολογισμός συνόλων
    calculateTotals();
    
    return true;
};

// Αρχικοποίηση των δεδομένων προϋπηρεσίας
document.addEventListener('DOMContentLoaded', function() {
    console.log('Loading existing experience data');
    loadExistingExperiences();
});

console.log('Vehicle experience initialization complete');
