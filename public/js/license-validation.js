// Λειτουργία ελέγχου ηλικίας και λήξεων αδειών οδήγησης
document.addEventListener('DOMContentLoaded', function() {
    // Υπολογισμός ηλικίας από ημερομηνία γέννησης
    const birthDateInput = document.getElementById('birth_date');
    const ageDisplay = document.getElementById('age_display');
    
    // Συνάρτηση για τον υπολογισμό της ηλικίας
    function calculateAge(birthDate) {
        const today = new Date();
        const birth = new Date(birthDate);
        let age = today.getFullYear() - birth.getFullYear();
        const monthDiff = today.getMonth() - birth.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
            age--;
        }
        
        return age;
    }
    
    // Ενημέρωση ηλικίας στην οθόνη
    function updateAgeDisplay() {
        if (birthDateInput.value) {
            const age = calculateAge(birthDateInput.value);
            ageDisplay.textContent = `Ηλικία: ${age} ετών`;
            
            // Ενημέρωση για περιορισμούς ηλικίας
            if (age >= 65) {
                ageDisplay.innerHTML += `<br><span class="warning-text">Προσοχή: Άτομα άνω των 65 ετών έχουν περιορισμούς στην οδήγηση επαγγελματικών κατηγοριών</span>`;
            }
            if (age >= 67) {
                ageDisplay.innerHTML += `<br><span class="error-text">Προσοχή: Άτομα άνω των 67 ετών δεν επιτρέπεται να οδηγούν κατηγορίες C & D</span>`;
            }
            
            // Έλεγχος κατηγοριών αδειών με βάση την ηλικία
            checkLicenseAgeRestrictions(age);
        } else {
            ageDisplay.textContent = '';
        }
    }
    
    // Έλεγχος περιορισμών αδειών με βάση την ηλικία
    function checkLicenseAgeRestrictions(age) {
        const licenseTypeCheckboxes = document.querySelectorAll('input[name="license_types[]"]');
        
        licenseTypeCheckboxes.forEach(checkbox => {
            const licenseType = checkbox.value;
            const row = checkbox.closest('tr');
            
            // Έλεγχος για κατηγορίες που επηρεάζονται από την ηλικία
            if (['C', 'CE', 'C1', 'C1E', 'D', 'DE', 'D1', 'D1E'].includes(licenseType)) {
                if (age >= 67) {
                    // Άτομα άνω των 67 ετών δεν μπορούν να οδηγούν C & D
                    checkbox.checked = false;
                    checkbox.disabled = true;
                    if (row) {
                        row.classList.add('license-age-restricted');
                        
                        // Προσθήκη μηνύματος προειδοποίησης
                        let warningCell = row.querySelector('.age-restriction-warning');
                        if (!warningCell) {
                            const lastCell = row.querySelector('td:last-child');
                            if (lastCell) {
                                warningCell = document.createElement('div');
                                warningCell.className = 'age-restriction-warning';
                                warningCell.textContent = 'Περιορισμός λόγω ηλικίας (>67)';
                                lastCell.appendChild(warningCell);
                            }
                        }
                    }
                } else if (age >= 65 && ['D', 'DE', 'D1', 'D1E'].includes(licenseType)) {
                    // Προειδοποίηση για άτομα 65-67 ετών (περιορισμοί για σχολικά λεωφορεία)
                    if (row) {
                        row.classList.add('license-age-warning');
                        
                        // Προσθήκη μηνύματος προειδοποίησης
                        let warningCell = row.querySelector('.age-restriction-warning');
                        if (!warningCell) {
                            const lastCell = row.querySelector('td:last-child');
                            if (lastCell) {
                                warningCell = document.createElement('div');
                                warningCell.className = 'age-restriction-warning';
                                warningCell.textContent = 'Περιορισμοί για σχολικά λεωφορεία (>65)';
                                lastCell.appendChild(warningCell);
                            }
                        }
                    }
                } else {
                    // Αφαίρεση προειδοποιήσεων αν η ηλικία είναι εντός ορίων
                    checkbox.disabled = false;
                    if (row) {
                        row.classList.remove('license-age-restricted', 'license-age-warning');
                        const warningCell = row.querySelector('.age-restriction-warning');
                        if (warningCell) {
                            warningCell.remove();
                        }
                    }
                }
            }
        });
    }
    
    // Εκτέλεση ελέγχων κατά την αλλαγή ημερομηνίας γέννησης
    if (birthDateInput) {
        birthDateInput.addEventListener('change', updateAgeDisplay);
        birthDateInput.addEventListener('input', updateAgeDisplay);
        
        // Έλεγχος κατά τη φόρτωση της σελίδας
        if (birthDateInput.value) {
            updateAgeDisplay();
        }
    }
    
    // ========== Έλεγχοι Ημερομηνιών Λήξης ==========
    
    // Προεπιλεγμένες ημερομηνίες λήξης για νέες άδειες
    function setDefaultExpiryDates() {
        const today = new Date();
        
        // Έλεγχος ηλικίας αν υπάρχει ημερομηνία γέννησης
        let maxAgeDate = null;
        if (birthDateInput && birthDateInput.value) {
            const birthDate = new Date(birthDateInput.value);
            const age65Date = new Date(birthDate);
            age65Date.setFullYear(birthDate.getFullYear() + 65);
            
            // Αν η ημερομηνία των 65 ετών είναι μελλοντική και μικρότερη από την κανονική λήξη
            if (age65Date > today) {
                maxAgeDate = age65Date;
            }
        }
        
        // Ορίζουμε προεπιλεγμένες ημερομηνίες λήξης για διάφορες κατηγορίες
        const licenseTypeCheckboxes = document.querySelectorAll('input[name="license_types[]"]');
        
        licenseTypeCheckboxes.forEach(checkbox => {
            if (!checkbox.checked) return; // Προχωράμε μόνο για επιλεγμένες άδειες
            
            const licenseType = checkbox.value;
            const row = checkbox.closest('tr');
            if (!row) return;
            
            const expiryDateInput = row.querySelector(`input[name="license_expiry[${licenseType}]"]`);
            if (!expiryDateInput || expiryDateInput.value) return; // Έχει ήδη ημερομηνία
            
            // Υπολογισμός προεπιλεγμένης ημερομηνίας λήξης
            const expiryDate = new Date(today);
            
            if (['C', 'CE', 'C1', 'C1E', 'D', 'DE', 'D1', 'D1E'].includes(licenseType)) {
                // Κατηγορίες C & D: 5 έτη
                expiryDate.setFullYear(today.getFullYear() + 5);
            } else {
                // Άλλες κατηγορίες (π.χ. A, B): 15 έτη ή μέχρι τα 65
                expiryDate.setFullYear(today.getFullYear() + 15);
            }
            
            // Έλεγχος για όριο ηλικίας
            if (maxAgeDate && maxAgeDate < expiryDate) {
                expiryDate.setTime(maxAgeDate.getTime());
            }
            
            // Ορισμός ημερομηνίας λήξης
            expiryDateInput.value = expiryDate.toISOString().split('T')[0];
        });
        
        // Για πιστοποιητικά ΠΕΙ, ADR, κάρτα ταχογράφου (5 έτη)
        const peiExpiryInputs = document.querySelectorAll('input[name^="pei_"][name$="_expiry"]');
        const adrExpiryInput = document.getElementById('adr_certificate_expiry');
        const tachoExpiryInput = document.getElementById('tachograph_card_expiry');
        
        const fiveYearsLater = new Date(today);
        fiveYearsLater.setFullYear(today.getFullYear() + 5);
        const fiveYearsDate = fiveYearsLater.toISOString().split('T')[0];
        
        // ΠΕΙ
        peiExpiryInputs.forEach(input => {
            if (!input.disabled && !input.value) {
                input.value = fiveYearsDate;
            }
        });
        
        // ADR
        if (adrExpiryInput && document.getElementById('adr_certificate').checked && !adrExpiryInput.value) {
            adrExpiryInput.value = fiveYearsDate;
        }
        
        // Κάρτα ταχογράφου
        if (tachoExpiryInput && document.getElementById('tachograph_card').checked && !tachoExpiryInput.value) {
            tachoExpiryInput.value = fiveYearsDate;
        }
    }
    
    // Έλεγχος για επερχόμενες λήξεις
    function checkExpiryDates() {
        const today = new Date();
        const oneMonthLater = new Date(today);
        oneMonthLater.setMonth(today.getMonth() + 1);
        
        const twoMonthsLater = new Date(today);
        twoMonthsLater.setMonth(today.getMonth() + 2);
        
        const oneYearLater = new Date(today);
        oneYearLater.setFullYear(today.getFullYear() + 1);
        
        // Έλεγχος ημερομηνιών λήξης αδειών οδήγησης
        const expiryDateInputs = document.querySelectorAll('input[type="date"][name^="license_expiry"]');
        expiryDateInputs.forEach(input => {
            if (!input.value) return;
            
            const expiryDate = new Date(input.value);
            if (expiryDate <= today) {
                // Έχει λήξει
                input.classList.add('date-expired');
                addExpiryWarning(input, 'Έχει λήξει!');
            } else if (expiryDate <= twoMonthsLater) {
                // Λήγει σε λιγότερο από 2 μήνες (μπορεί να ανανεωθεί)
                input.classList.add('date-expiring-soon');
                addExpiryWarning(input, 'Λήγει σύντομα! Μπορεί να ανανεωθεί τώρα.');
            } else {
                input.classList.remove('date-expired', 'date-expiring-soon');
                removeExpiryWarning(input);
            }
        });
        
        // Έλεγχος ημερομηνιών λήξης ΠΕΙ
        const peiExpiryInputs = document.querySelectorAll('input[name^="pei_"][name$="_expiry"]');
        peiExpiryInputs.forEach(input => {
            if (!input.value || input.disabled) return;
            
            const expiryDate = new Date(input.value);
            if (expiryDate <= today) {
                // Έχει λήξει
                input.classList.add('date-expired');
                addExpiryWarning(input, 'Το ΠΕΙ έχει λήξει!');
            } else if (expiryDate <= oneYearLater) {
                // Λήγει σε λιγότερο από 1 έτος (μπορεί να ανανεωθεί)
                input.classList.add('date-expiring-soon');
                addExpiryWarning(input, 'Το ΠΕΙ λήγει σύντομα! Μπορεί να ανανεωθεί τώρα.');
            } else {
                input.classList.remove('date-expired', 'date-expiring-soon');
                removeExpiryWarning(input);
            }
        });
        
        // Έλεγχος ημερομηνίας λήξης ADR
        const adrExpiryInput = document.getElementById('adr_certificate_expiry');
        if (adrExpiryInput && adrExpiryInput.value && document.getElementById('adr_certificate').checked) {
            const expiryDate = new Date(adrExpiryInput.value);
            if (expiryDate <= today) {
                // Έχει λήξει
                adrExpiryInput.classList.add('date-expired');
                addExpiryWarning(adrExpiryInput, 'Το ADR έχει λήξει!');
            } else if (expiryDate <= oneYearLater) {
                // Λήγει σε λιγότερο από 1 έτος (μπορεί να ανανεωθεί)
                adrExpiryInput.classList.add('date-expiring-soon');
                addExpiryWarning(adrExpiryInput, 'Το ADR λήγει σύντομα! Μπορεί να ανανεωθεί τώρα.');
            } else {
                adrExpiryInput.classList.remove('date-expired', 'date-expiring-soon');
                removeExpiryWarning(adrExpiryInput);
            }
        }
        
        // Έλεγχος ημερομηνίας λήξης κάρτας ταχογράφου
        const tachoExpiryInput = document.getElementById('tachograph_card_expiry');
        if (tachoExpiryInput && tachoExpiryInput.value && document.getElementById('tachograph_card').checked) {
            const expiryDate = new Date(tachoExpiryInput.value);
            if (expiryDate <= today) {
                // Έχει λήξει
                tachoExpiryInput.classList.add('date-expired');
                addExpiryWarning(tachoExpiryInput, 'Η κάρτα ταχογράφου έχει λήξει!');
            } else if (expiryDate <= twoMonthsLater) {
                // Λήγει σε λιγότερο από 2 μήνες (μπορεί να ανανεωθεί)
                tachoExpiryInput.classList.add('date-expiring-soon');
                addExpiryWarning(tachoExpiryInput, 'Η κάρτα ταχογράφου λήγει σύντομα! Μπορεί να ανανεωθεί τώρα.');
            } else {
                tachoExpiryInput.classList.remove('date-expired', 'date-expiring-soon');
                removeExpiryWarning(tachoExpiryInput);
            }
        }
    }
    
    // Προσθήκη προειδοποίησης λήξης
    function addExpiryWarning(input, message) {
        let warningSpan = input.parentElement.querySelector('.expiry-warning');
        if (!warningSpan) {
            warningSpan = document.createElement('span');
            warningSpan.className = 'expiry-warning';
            input.parentElement.appendChild(warningSpan);
        }
        warningSpan.textContent = message;
    }
    
    // Αφαίρεση προειδοποίησης λήξης
    function removeExpiryWarning(input) {
        const warningSpan = input.parentElement.querySelector('.expiry-warning');
        if (warningSpan) {
            warningSpan.remove();
        }
    }
    
    // Αρχικοποίηση ελέγχων και ακροατών
    function initLicenseValidation() {
        // Έλεγχος για επιλογές αδειών
        const licenseTypeCheckboxes = document.querySelectorAll('input[name="license_types[]"]');
        licenseTypeCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                // Όταν επιλέγεται μια άδεια, ορίζουμε προεπιλεγμένη ημερομηνία λήξης
                setDefaultExpiryDates();
                // Ελέγχος για ημερομηνίες λήξης
                checkExpiryDates();
            });
        });
        
        // Έλεγχος για επιλογές ΠΕΙ
        const peiCheckboxes = document.querySelectorAll('input[name^="has_pei_"]');
        peiCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                setDefaultExpiryDates();
                checkExpiryDates();
            });
        });
        
        // Έλεγχος για επιλογή ADR
        const adrCheckbox = document.getElementById('adr_certificate');
        if (adrCheckbox) {
            adrCheckbox.addEventListener('change', function() {
                setDefaultExpiryDates();
                checkExpiryDates();
            });
        }
        
        // Έλεγχος για επιλογή κάρτας ταχογράφου
        const tachoCheckbox = document.getElementById('tachograph_card');
        if (tachoCheckbox) {
            tachoCheckbox.addEventListener('change', function() {
                setDefaultExpiryDates();
                checkExpiryDates();
            });
        }
        
        // Έλεγχος για αλλαγές σε ημερομηνίες λήξης
        const allExpiryInputs = document.querySelectorAll('input[type="date"][name*="expiry"]');
        allExpiryInputs.forEach(input => {
            input.addEventListener('change', checkExpiryDates);
        });
        
        // Αρχικός έλεγχος κατά τη φόρτωση
        setDefaultExpiryDates();
        checkExpiryDates();
    }
    
    // Εκτέλεση όλων των ελέγχων κατά την αρχικοποίηση
    initLicenseValidation();
});

// Προσθήκη CSS για προειδοποιήσεις λήξης
const style = document.createElement('style');
style.textContent = `
    .date-expired {
        border: 2px solid #f44336 !important;
        background-color: rgba(244, 67, 54, 0.1) !important;
    }
    
    .date-expiring-soon {
        border: 2px solid #ff9800 !important;
        background-color: rgba(255, 152, 0, 0.1) !important;
    }
    
    .expiry-warning {
        display: block;
        margin-top: 5px;
        font-size: 0.85em;
        font-weight: bold;
        color: #f44336;
    }
    
    .license-age-restricted {
        opacity: 0.6;
        background-color: rgba(244, 67, 54, 0.1) !important;
    }
    
    .license-age-warning {
        background-color: rgba(255, 152, 0, 0.1) !important;
    }
    
    .age-restriction-warning {
        margin-top: 5px;
        font-size: 0.85em;
        font-weight: bold;
        color: #f44336;
    }
    
    .warning-text {
        color: #ff9800;
        font-weight: bold;
    }
    
    .error-text {
        color: #f44336;
        font-weight: bold;
    }
`;
document.head.appendChild(style);