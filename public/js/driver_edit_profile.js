document.addEventListener('DOMContentLoaded', function() {
    // Αρχικοποίηση των καρτελών
    initTabs();
    
    // Αρχικοποίηση των toggle για τις άδειες
    initLicenseToggles();
    
    // Αρχικοποίηση του toggle διαθεσιμότητας
    initAvailabilityToggle();
    
    // Αρχικοποίηση των κουμπιών αποθήκευσης
    initSaveButtons();
    
    // Αρχικοποίηση των ειδικών αδειών
    initSpecialLicenses();
    
    // Αρχικοποίηση των πιστοποιήσεων
    initCertifications();
    
    // Υπολογισμός ηλικίας από την ημερομηνία γέννησης
    initAgeCalculation();
});

// Αρχικοποίηση των καρτελών
function initTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Αφαίρεση της κλάσης active από όλα τα κουμπιά και τις καρτέλες
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));
            
            // Προσθήκη της κλάσης active στο επιλεγμένο κουμπί
            this.classList.add('active');
            
            // Εμφάνιση της αντίστοιχης καρτέλας
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
        });
    });
}

// Αρχικοποίηση των toggle για τις άδειες
function initLicenseToggles() {
    // Άδεια οδήγησης
    const drivingLicenseCheckbox = document.getElementById('driving_license');
    const drivingLicenseTab = document.getElementById('driving_license_tab');
    
    if (drivingLicenseCheckbox && drivingLicenseTab) {
        drivingLicenseCheckbox.addEventListener('change', function() {
            drivingLicenseTab.classList.toggle('hidden', !this.checked);
        });
    }
    
    // Πιστοποιητικό ADR
    const adrCertificateCheckbox = document.getElementById('adr_certificate');
    const adrCertificateTab = document.getElementById('adr_certificate_tab');
    
    if (adrCertificateCheckbox && adrCertificateTab) {
        adrCertificateCheckbox.addEventListener('change', function() {
            adrCertificateTab.classList.toggle('hidden', !this.checked);
        });
    }
    
    // Άδεια χειριστή μηχανημάτων έργου
    const operatorLicenseCheckbox = document.getElementById('operator_license');
    const operatorLicenseTab = document.getElementById('operator_license_tab');
    
    if (operatorLicenseCheckbox && operatorLicenseTab) {
        operatorLicenseCheckbox.addEventListener('change', function() {
            operatorLicenseTab.classList.toggle('hidden', !this.checked);
        });
    }
    
    // Κάρτα ψηφιακού ταχογράφου
    const tachographCardCheckbox = document.getElementById('tachograph_card');
    const tachographCardTab = document.getElementById('tachograph_card_tab');
    
    if (tachographCardCheckbox && tachographCardTab) {
        tachographCardCheckbox.addEventListener('change', function() {
            tachographCardTab.classList.toggle('hidden', !this.checked);
        });
    }
    
    // ΠΕΙ checkboxes
    const peiCheckboxes = document.querySelectorAll('input[name^="has_pei_"]');
    
    peiCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const peiType = this.name.split('_')[2][0]; // Παίρνουμε το πρώτο γράμμα του τύπου (c ή d)
            const expiryInput = document.querySelector(`input[name="pei_${peiType}_expiry"]`);
            
            if (expiryInput) {
                expiryInput.disabled = !this.checked;
            }
        });
    });
}

// Αρχικοποίηση του toggle διαθεσιμότητας
function initAvailabilityToggle() {
    const availabilityToggle = document.getElementById('available_for_work');
    const toggleText = document.querySelector('.toggle-switch-text');
    
    if (availabilityToggle && toggleText) {
        availabilityToggle.addEventListener('change', function() {
            toggleText.textContent = this.checked ? 'Διαθέσιμος/η για εργασία' : 'Μη διαθέσιμος/η';
        });
    }
}

// Αρχικοποίηση των κουμπιών αποθήκευσης
function initSaveButtons() {
    const saveButtons = document.querySelectorAll('.btn-save');
    const form = document.getElementById('driverProfileForm');
    
    saveButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            form.submit();
        });
    });
}

// Αρχικοποίηση των ειδικών αδειών
function initSpecialLicenses() {
    const addButton = document.getElementById('add-special-license');
    const container = document.getElementById('special-licenses-container');
    const template = document.getElementById('special-license-template');
    
    if (addButton && container && template) {
        addButton.addEventListener('click', function() {
            const newItem = template.cloneNode(true);
            newItem.style.display = 'block';
            newItem.id = 'special-license-item-' + Date.now();
            
            // Ενημέρωση των IDs των πεδίων
            const inputs = newItem.querySelectorAll('input, textarea');
            inputs.forEach(input => {
                const newId = input.id.replace('_new', '_' + Date.now());
                input.id = newId;
            });
            
            // Προσθήκη του event listener για το κουμπί αφαίρεσης
            const removeButton = newItem.querySelector('.remove-special-license');
            if (removeButton) {
                removeButton.setAttribute('data-index', Date.now());
                removeButton.addEventListener('click', function() {
                    newItem.remove();
                });
            }
            
            // Προσθήκη του νέου στοιχείου πριν το template
            container.insertBefore(newItem, template);
        });
        
        // Προσθήκη event listeners για τα υπάρχοντα κουμπιά αφαίρεσης
        const removeButtons = document.querySelectorAll('.remove-special-license');
        removeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const index = this.getAttribute('data-index');
                if (index !== 'new') {
                    const item = document.getElementById('special-license-item-' + index);
                    if (item) {
                        item.remove();
                    }
                }
            });
        });
    }
}

// Αρχικοποίηση των πιστοποιήσεων
function initCertifications() {
    const addButton = document.getElementById('btn-add-certification');
    const container = document.getElementById('certifications-list');
    
    if (addButton && container) {
        addButton.addEventListener('click', function() {
            const index = container.querySelectorAll('.certification-entry').length;
            const newEntry = document.createElement('div');
            newEntry.className = 'certification-entry';
            
            newEntry.innerHTML = `
                <div class="form-row">
                    <div class="form-group">
                        <label>Τίτλος Πιστοποίησης:</label>
                        <input type="text" name="certifications[${index}][title]">
                    </div>
                    <div class="form-group">
                        <label>Πάροχος/Οργανισμός:</label>
                        <input type="text" name="certifications[${index}][provider]">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Ημερομηνία Απόκτησης:</label>
                        <input type="date" name="certifications[${index}][date]">
                    </div>
                    <div class="form-group">
                        <label>Ημερομηνία Λήξης (αν υπάρχει):</label>
                        <input type="date" name="certifications[${index}][expiry]">
                    </div>
                </div>
                <div class="form-group">
                    <label>Περιγραφή:</label>
                    <textarea name="certifications[${index}][description]" rows="2"></textarea>
                </div>
                <button type="button" class="btn-remove-certification" data-index="${index}">Αφαίρεση</button>
            `;
            
            container.appendChild(newEntry);
            
            // Προσθήκη event listener για το κουμπί αφαίρεσης
            const removeButton = newEntry.querySelector('.btn-remove-certification');
            if (removeButton) {
                removeButton.addEventListener('click', function() {
                    newEntry.remove();
                });
            }
        });
        
        // Προσθήκη event listeners για τα υπάρχοντα κουμπιά αφαίρεσης
        const removeButtons = document.querySelectorAll('.btn-remove-certification');
        removeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const entry = this.closest('.certification-entry');
                if (entry) {
                    entry.remove();
                }
            });
        });
    }
}

// Υπολογισμός ηλικίας από την ημερομηνία γέννησης
function initAgeCalculation() {
    const birthDateInput = document.getElementById('birth_date');
    const ageDisplay = document.getElementById('age_display');
    
    if (birthDateInput && ageDisplay) {
        // Υπολογισμός ηλικίας κατά τη φόρτωση της σελίδας
        if (birthDateInput.value) {
            const age = calculateAge(new Date(birthDateInput.value));
            ageDisplay.textContent = `Ηλικία: ${age} ετών`;
        }
        
        // Υπολογισμός ηλικίας κατά την αλλαγή της ημερομηνίας
        birthDateInput.addEventListener('change', function() {
            if (this.value) {
                const age = calculateAge(new Date(this.value));
                ageDisplay.textContent = `Ηλικία: ${age} ετών`;
            } else {
                ageDisplay.textContent = '';
            }
        });
    }
}

// Βοηθητική συνάρτηση για τον υπολογισμό της ηλικίας
function calculateAge(birthDate) {
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    
    return age;
}

/*
 * Προεπισκόπηση εικόνων εγγράφων (25/08): κάθε .doc-upload κάρτα
 * (δίπλωμα, ADR, άδεια χειριστή, κάρτα ταχογράφου) δείχνει αμέσως τη
 * νέα εικόνα μόλις επιλεγεί αρχείο — πριν την αποθήκευση.
 */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.doc-upload').forEach(function (card) {
        var input = card.querySelector('.doc-file-input');
        var img = card.querySelector('.doc-preview');
        var ph = card.querySelector('.doc-placeholder');
        var drop = card.querySelector('.doc-drop');
        if (!input || !img) return;
        input.addEventListener('change', function () {
            var f = this.files && this.files[0];
            if (!f || !/^image\//.test(f.type)) return;
            var r = new FileReader();
            r.onload = function (e) {
                img.src = e.target.result;
                img.style.display = '';
                if (ph) ph.style.display = 'none';
                if (drop) drop.classList.add('has-image');
            };
            r.readAsDataURL(f);
        });
    });
});
