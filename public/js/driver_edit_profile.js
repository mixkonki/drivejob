document.addEventListener('DOMContentLoaded', function() {
    // -------------------- Καταγραφή σφαλμάτων --------------------
    window.onerror = function(message, source, lineno, colno, error) {
        console.error('ΣΦΑΛΜΑ:', message, 'στη γραμμή', lineno, 'της πηγής', source);
        console.error('Λεπτομέρειες:', error);
        return false; // Επιτρέπει την κανονική διαχείριση σφαλμάτων του προγράμματος περιήγησης
    };

    // -------------------- Ορισμός βοηθητικών συναρτήσεων OCR --------------------
    window.preprocessImageForOCR = function(imageDataUrl) {
        return Promise.resolve(imageDataUrl);
    };
    
    window.performOCR = function(imageData, languages) {
        if (typeof Tesseract === 'undefined') {
            return Promise.reject(new Error('Το Tesseract.js δεν είναι διαθέσιμο'));
        }
        return Tesseract.recognize(
            imageData,
            languages || 'eng+ell'
        ).then(result => {
            return result.data.text;
        });
    };

    // -------------------- Αρχικοποίηση λειτουργίας καρτελών --------------------
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
    
    // -------------------- Υπολογισμός ηλικίας --------------------
    const birthDateInput = document.getElementById('birth_date');
    const ageDisplay = document.getElementById('age_display');
    
    if (birthDateInput && ageDisplay) {
        function calculateAge() {
            const birthDate = new Date(birthDateInput.value);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            
            if (!isNaN(age) && birthDateInput.value) {
                ageDisplay.textContent = `Ηλικία: ${age} ετών`;
            } else {
                ageDisplay.textContent = '';
            }
        }
        
        birthDateInput.addEventListener('change', calculateAge);
        birthDateInput.addEventListener('input', calculateAge);
        
        // Υπολογισμός ηλικίας κατά τη φόρτωση της σελίδας
        if (birthDateInput.value) {
            calculateAge();
        }
    }
    
    // -------------------- Εμφάνιση/απόκρυψη λεπτομερειών αδειών --------------------
    const licenseSections = [
        { checkboxId: 'driving_license', tabId: 'driving_license_tab' },
        { checkboxId: 'adr_certificate', tabId: 'adr_certificate_tab' },
        { checkboxId: 'operator_license', tabId: 'operator_license_tab' },
        { checkboxId: 'tachograph_card', tabId: 'tachograph_card_tab' },
        { checkboxId: 'training_seminars', tabId: 'training_seminars_tab' }
    ];
    
    licenseSections.forEach(section => {
        const checkbox = document.getElementById(section.checkboxId);
        const tab = document.getElementById(section.tabId);
        
        if (checkbox && tab) {
            // Αρχική κατάσταση
            tab.classList.toggle('hidden', !checkbox.checked);
            
            // Χειρισμός αλλαγών
            checkbox.addEventListener('change', function() {
                tab.classList.toggle('hidden', !this.checked);
            });
        }
    });
    
    // -------------------- Χειρισμός ΠΕΙ οδήγησης --------------------
    // Διαχείριση των checkbox ΠΕΙ και των αντίστοιχων πεδίων ημερομηνίας
    const peiCheckboxes = document.querySelectorAll('input[name^="has_pei_"]');
    
    peiCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Εύρεση του κοντινότερου πεδίου ημερομηνίας ΠΕΙ
            const peiField = this.closest('.pei-field');
            if (peiField) {
                const dateField = peiField.querySelector('input[type="date"]');
                if (dateField) {
                    dateField.disabled = !this.checked;
                    
                    // Αν ενεργοποιείται το ΠΕΙ και δεν έχει ημερομηνία, ορίζουμε μια μελλοντική
                    if (this.checked && !dateField.value) {
                        const today = new Date();
                        const fiveYearsLater = new Date(today);
                        fiveYearsLater.setFullYear(today.getFullYear() + 5);
                        dateField.value = fiveYearsLater.toISOString().split('T')[0];
                    }
                }
            }
        });
    });
    
    // Συγχρονισμός ημερομηνιών ΠΕΙ για κατηγορίες
    function syncExpiryDates(fieldNames) {
        const fields = document.querySelectorAll(fieldNames);
        fields.forEach(field => {
            field.addEventListener('change', function() {
                if (this.disabled) return;
                
                const newDate = this.value;
                fields.forEach(f => {
                    if (f !== this && !f.disabled) {
                        f.value = newDate;
                    }
                });
            });
        });
    }
    
    // Συγχρονισμός για ΠΕΙ φορτηγών (C) και λεωφορείων (D)
    syncExpiryDates('input[name="pei_c_expiry"]');
    syncExpiryDates('input[name="pei_d_expiry"]');
    
    // Χειρισμός των checkbox κατηγοριών αδειών
    const licenseTypeCheckboxes = document.querySelectorAll('input[name="license_types[]"]');
    licenseTypeCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Εύρεση του πλησιέστερου πεδίου ημερομηνίας λήξης
            const row = this.closest('tr');
            if (!row) return;
            
            const dateField = row.querySelector('input[type="date"][name^="license_expiry"]');
            if (dateField) {
                dateField.disabled = !this.checked;
                
                // Αν ενεργοποιείται η άδεια και δεν έχει ημερομηνία, ορίζουμε μια μελλοντική
                if (this.checked && !dateField.value) {
                    const today = new Date();
                    const fifteenYearsLater = new Date(today);
                    fifteenYearsLater.setFullYear(today.getFullYear() + 15);
                    dateField.value = fifteenYearsLater.toISOString().split('T')[0];
                }
            }
            
            // Χειρισμός των πεδίων ΠΕΙ
            const peiField = row.querySelector('.pei-field');
            if (peiField) {
                const peiCheckbox = peiField.querySelector('input[type="checkbox"]');
                const peiDateField = peiField.querySelector('input[type="date"]');
                
                if (peiCheckbox) {
                    if (!this.checked) {
                        // Αν η κατηγορία δεν είναι επιλεγμένη, απενεργοποιούμε το ΠΕΙ
                        peiCheckbox.disabled = true;
                        peiCheckbox.checked = false;
                    } else {
                        // Αν η κατηγορία είναι επιλεγμένη, ενεργοποιούμε το checkbox ΠΕΙ
                        peiCheckbox.disabled = false;
                    }
                }
                
                if (peiDateField) {
                    // Το πεδίο ημερομηνίας ΠΕΙ ενεργοποιείται μόνο αν το checkbox ΠΕΙ είναι επιλεγμένο
                    peiDateField.disabled = !this.checked || (peiCheckbox && !peiCheckbox.checked);
                }
            }
        });
        
        // Αρχικοποίηση με βάση την τρέχουσα κατάσταση του checkbox
        const changeEvent = new Event('change');
        checkbox.dispatchEvent(changeEvent);
    });
    
    // -------------------- Χειρισμός εικόνων --------------------
    // Γενική συνάρτηση για το χειρισμό της μεταφόρτωσης εικόνων
    function handleImageUpload(input) {
        if (!input.files || !input.files[0]) return;
        
        // Έλεγχος μεγέθους αρχείου (max 2MB)
        const fileSize = input.files[0].size / 1024 / 1024; // σε MB
        if (fileSize > 2) {
            alert('Το αρχείο είναι πολύ μεγάλο. Μέγιστο επιτρεπόμενο μέγεθος: 2MB');
            input.value = ''; // Καθαρισμός της επιλογής
            return;
        }
        
        // Έλεγχος τύπου αρχείου
        const fileType = input.files[0].type;
        if (!['image/jpeg', 'image/png', 'image/gif'].includes(fileType)) {
            alert('Μη αποδεκτός τύπος αρχείου. Επιτρέπονται μόνο JPEG, PNG και GIF.');
            input.value = '';
            return;
        }
        
        // Εμφάνιση προεπισκόπησης
        const parent = input.parentElement;
        let previewContainer = parent.querySelector('.preview-image') || parent.querySelector('.current-image');
        
        if (!previewContainer) {
            // Δημιουργία νέου container για προεπισκόπηση
            previewContainer = document.createElement('div');
            previewContainer.className = 'preview-image';
            
            const previewImg = document.createElement('img');
            const previewText = document.createElement('p');
            previewText.textContent = 'Προεπισκόπηση εικόνας';
            
            previewContainer.appendChild(previewImg);
            previewContainer.appendChild(previewText);
            
            // Προσθήκη πριν από το input
            parent.insertBefore(previewContainer, input);
        } else {
            // Ενημέρωση του υπάρχοντος container
            const previewImg = previewContainer.querySelector('img');
            if (previewImg) {
                const file = input.files[0];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewImg.alt = file.name;
                };
                
                reader.readAsDataURL(file);
            }
        }
    }
    
    // Εφαρμογή σε όλα τα πεδία εικόνων
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    imageInputs.forEach(input => {
        input.addEventListener('change', function() {
            handleImageUpload(this);
        });
    });
    
    // Χειρισμός για το αρχείο βιογραφικού
    const resumeInput = document.getElementById('resume_file');
    if (resumeInput) {
        resumeInput.addEventListener('change', function() {
            if (!this.files || !this.files[0]) return;
            
            // Έλεγχος μεγέθους αρχείου (max 5MB)
            const fileSize = this.files[0].size / 1024 / 1024; // σε MB
            if (fileSize > 5) {
                alert('Το αρχείο είναι πολύ μεγάλο. Μέγιστο επιτρεπόμενο μέγεθος: 5MB');
                this.value = ''; // Καθαρισμός της επιλογής
                return;
            }
            
            // Έλεγχος τύπου αρχείου
            const fileName = this.files[0].name.toLowerCase();
            if (!fileName.endsWith('.pdf') && !fileName.endsWith('.doc') && !fileName.endsWith('.docx')) {
                alert('Μη αποδεκτός τύπος αρχείου. Επιτρέπονται μόνο PDF, DOC και DOCX.');
                this.value = '';
                return;
            }
            
            // Εμφάνιση ονόματος αρχείου
            const parent = this.parentElement;
            let fileInfo = parent.querySelector('.file-info');
            
            if (!fileInfo) {
                fileInfo = document.createElement('div');
                fileInfo.className = 'file-info';
                parent.insertBefore(fileInfo, this.nextSibling);
            }
            
            fileInfo.textContent = `Επιλεγμένο αρχείο: ${this.files[0].name}`;
        });
    }
    
    // -------------------- Διαχείριση ειδικών αδειών --------------------
    const addSpecialLicenseBtn = document.getElementById('add-special-license');
    const specialLicensesContainer = document.getElementById('special-licenses-container');
    const specialLicenseTemplate = document.getElementById('special-license-template');
    
    if (addSpecialLicenseBtn && specialLicensesContainer && specialLicenseTemplate) {
        // Μετρητής για τις νέες άδειες
        const existingItems = specialLicensesContainer.querySelectorAll('.special-license-item:not(#special-license-template)');
        let licenseCounter = existingItems.length > 0 ? existingItems.length : 0;
        
        // Προσθήκη νέας ειδικής άδειας
        addSpecialLicenseBtn.addEventListener('click', function() {
            // Κλωνοποίηση του προτύπου
            const clone = specialLicenseTemplate.cloneNode(true);
            const uniqueId = 'special-license-item-' + new Date().getTime(); // Χρονοσφραγίδα για μοναδικότητα
            clone.id = uniqueId;
            clone.style.display = 'block';
            
            // Ενημέρωση των IDs των πεδίων
            const inputs = clone.querySelectorAll('input, textarea');
            inputs.forEach(input => {
                // Ενημέρωση του ID χωρίς να αλλάξει το name
                const oldId = input.id;
                const newId = oldId.replace('_new', '_' + licenseCounter);
                input.id = newId;
                
                // Καθαρισμός της τιμής
                input.value = '';
                
                // Αφαίρεση του required για να αποφευχθούν σφάλματα
                if (input.hasAttribute('required')) {
                    input.removeAttribute('required');
                }
            });
            
            // Ενημέρωση του κουμπιού αφαίρεσης
            const removeButton = clone.querySelector('.remove-special-license');
            if (removeButton) {
                removeButton.dataset.index = uniqueId;
            }
            
            // Προσθήκη στον container
            specialLicensesContainer.appendChild(clone);
            licenseCounter++;
        });
        
        // Αφαίρεση ειδικής άδειας (event delegation)
        document.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('remove-special-license')) {
                const itemId = e.target.dataset.index;
                let item;
                
                // Προσπάθεια εύρεσης με το πλήρες ID ή με το αριθμητικό μέρος
                if (itemId.startsWith('special-license-item-')) {
                    item = document.getElementById(itemId);
                } else {
                    item = document.getElementById('special-license-item-' + itemId);
                }
                
                if (item) {
                    if (confirm('Είστε βέβαιοι ότι θέλετε να αφαιρέσετε αυτή την άδεια;')) {
                        item.remove();
                    }
                }
            }
        });
    }
    
    // -------------------- Έλεγχος κωδικού πρόσβασης --------------------
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordStrengthDiv = document.getElementById('password-strength');
    
    if (newPasswordInput && passwordStrengthDiv) {
        newPasswordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
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
            if (confirmPasswordInput && confirmPasswordInput.value) {
                checkPasswordMatch();
            }
        });
        
        // Έλεγχος ταιριάσματος κωδικών
        if (confirmPasswordInput) {
            function checkPasswordMatch() {
                if (newPasswordInput.value === confirmPasswordInput.value) {
                    confirmPasswordInput.setCustomValidity('');
                    confirmPasswordInput.classList.remove('input-error');
                } else {
                    confirmPasswordInput.setCustomValidity('Οι κωδικοί δεν ταιριάζουν');
                    confirmPasswordInput.classList.add('input-error');
                }
            }
            
            confirmPasswordInput.addEventListener('input', checkPasswordMatch);
        }
    }
    
    // -------------------- Υποειδικότητες Άδειας Χειριστή --------------------
    // Ορισμός του αντικειμένου operatorSubSpecialities με όλες τις υποειδικότητες
    window.operatorSubSpecialities = {
        '1': [
            {id: '1.1', name: 'Εκσκαφείς όλων των τύπων', group: 'A'},
            {id: '1.2', name: 'Τσάπες φορτωτές (JCB)', group: 'B'},
            {id: '1.3', name: 'Προωθητές γαιών όλων των τύπων', group: 'A'},
            {id: '1.4', name: 'Φορτωτές αλυσότροχοι και λαστιχοφόροι', group: 'A'},
            {id: '1.5', name: 'Γερανοί - εκσκαφείς', group: 'A'},
            {id: '1.6', name: 'Ισοπεδωτές (GRADER)', group: 'B'},
            {id: '1.7', name: 'Αποξεστές (SCRAPERS)', group: 'B'},
            {id: '1.8', name: 'Ανυψωτικά περονοφόρα μηχανήματα (κλαρκ)', group: 'A'},
            {id: '1.9', name: 'Διαμορφωτές εδαφών', group: 'B'}
        ],
        '2': [
            {id: '2.1', name: 'Γερανοί παντός τύπου', group: 'A'},
            {id: '2.2', name: 'Γερανογέφυρες', group: 'A'},
            {id: '2.3', name: 'Γερανοί - εκσκαφείς', group: 'B'},
            {id: '2.4', name: 'Μηχανήματα απασχολούμενα στην κατασκευή στοών', group: 'B'},
            {id: '2.5', name: 'Φορτωτές - εκφορτωτές λιμένων', group: 'B'},
            {id: '2.6', name: 'Ηλεκτροκίνητα ανυψωτικά μηχανήματα', group: 'A'},
            {id: '2.7', name: 'Πασσαλοπήκτες', group: 'A'},
            {id: '2.8', name: 'Μηχανικοί πτύσσοβραχίονες για εργασίες σε ύψος', group: 'B'},
            {id: '2.9', name: 'Περονοφόρα ανυψωτικά μηχανήματα (κλαρκ)', group: 'A'}
        ],
        '3': [
            {id: '3.1', name: 'Μηχανήματα διάστρωσης υλικών οδοστρωσίας', group: 'A'},
            {id: '3.2', name: 'Οδοστρωτήρες', group: 'A'},
            {id: '3.3', name: 'Διαστρωτήρες σκυροδέματος (FINISHER)', group: 'A'},
            {id: '3.4', name: 'Διαστρωτήρες ασφαλτοσκυροδέματος', group: 'B'},
            {id: '3.5', name: 'Συμπιεστές - συμπυκνωτές', group: 'A'},
            {id: '3.6', name: 'Δονητικές πλάκες', group: 'B'},
            {id: '3.7', name: 'Μηχανήματα κατασκευής δαπέδων', group: 'A'},
            {id: '3.8', name: 'Θραυστήρες μηχανημάτων οδοστρωσίας', group: 'B'},
            {id: '3.9', name: 'Διαγραμμιστικές μηχανές', group: 'B'},
            {id: '3.10', name: 'Εναποθέτες υλικών', group: 'B'},
            {id: '3.11', name: 'Αυτοκινούμενοι διαστρωτήρες ασφάλτου', group: 'A'},
            {id: '3.12', name: 'Μηχανήματα σταθεροποίησης του εδάφους', group: 'B'}
        ],
        '4': [
            {id: '4.1', name: 'Αυτοκινούμενα σάρωθρα', group: 'A'},
            {id: '4.2', name: 'Πολύσπαστα ανυψωτικά μηχανήματα', group: 'A'},
            {id: '4.3', name: 'Μηχανήματα απόφραξης οχετών και φρεατίων', group: 'B'},
            {id: '4.4', name: 'Αποχιονιστικά μηχανήματα', group: 'B'},
            {id: '4.5', name: 'Αυτοκινούμενες αλατιέρες', group: 'A'},
            {id: '4.6', name: 'Αυτοκινούμενοι διαστρωτήρες σκυροδέματος', group: 'B'},
            {id: '4.7', name: 'Αυτοκινούμενοι διαστρωτήρες ασφάλτου', group: 'A'},
            {id: '4.8', name: 'Διαγραμμιστικές μηχανές οδών', group: 'B'}
        ],
        '5': [
            {id: '5.1', name: 'Μηχανήματα διάτρησης σηράγγων', group: 'A'},
            {id: '5.2', name: 'Φορτωτές υπόγειων έργων', group: 'A'},
            {id: '5.3', name: 'Περονοφόρα ανυψωτικά μηχανήματα (κλαρκ)', group: 'A'},
            {id: '5.4', name: 'Μηχανήματα κατασκευής φρεάτων', group: 'B'},
            {id: '5.5', name: 'Διατρητικά μηχανήματα', group: 'A'},
            {id: '5.6', name: 'Εκσκαφείς υπόγειων έργων', group: 'A'}
        ],
        '6': [
            {id: '6.1', name: 'Ελκυστήρες', group: 'A'},
            {id: '6.2', name: 'Αυτοκινούμενοι αεροσυμπιεστές', group: 'B'}
        ],
        '7': [
            {id: '7.1', name: 'Γεωτρύπανα', group: 'A'},
            {id: '7.2', name: 'Διατρητικά μηχανήματα', group: 'A'},
            {id: '7.3', name: 'Μηχανήματα πύκνωσης εδαφών χωρίς δόνηση', group: 'B'}
        ],
        '8': [
            {id: '8.1', name: 'Γερανοί παντός τύπου', group: 'A'},
            {id: '8.2', name: 'Γερανοί - εκσκαφείς', group: 'A'},
            {id: '8.3', name: 'Αντλίες σκυροδέματος', group: 'B'},
            {id: '8.4', name: 'Ανυψωτικές πλατφόρμες παντός τύπου', group: 'A'},
            {id: '8.5', name: 'Περονοφόρα ανυψωτικά μηχανήματα (κλαρκ)', group: 'A'},
            {id: '8.6', name: 'Αναβατόρια παντός τύπου', group: 'B'},
            {id: '8.7', name: 'Μηχανικοί πτύσσοβραχίονες για εργασίες σε ύψος', group: 'B'},
            {id: '8.8', name: 'Ανυψωτικά μηχανήματα εμπορευματοκιβωτίων', group: 'A'},
            {id: '8.9', name: 'Εξέδρες εργασίας', group: 'B'}
        ]
    };

    // Αντικείμενο που αποθηκεύει τις επιλεγμένες υποειδικότητες από όλες τις ειδικότητες
    window.allSelectedSubSpecialities = window.allSelectedSubSpecialities || {};

    /**
     * Φορτώνει τις υποειδικότητες μιας ειδικότητας
     * @param {string} specialityId - ID της ειδικότητας
     */
    window.loadSubSpecialities = function(specialityId) {
        const subSpecialityContainer = document.getElementById('subSpecialityContainer');
        const tableBody = document.getElementById('subSpecialitiesTableBody');
        
        if (!subSpecialityContainer || !tableBody) {
            console.error('Δεν βρέθηκαν τα απαραίτητα στοιχεία DOM');
            return;
        }
        
        // Αν δεν έχει επιλεγεί ειδικότητα, απόκρυψη του container
        if (!specialityId) {
            subSpecialityContainer.style.display = 'none';
            return;
        }
        
        // Αποθήκευση των τρέχουσων επιλογών πριν την αλλαγή της εμφάνισης
        const currentCheckboxes = tableBody.querySelectorAll('input[name="operator_sub_specialities[]"]');
        currentCheckboxes.forEach(checkbox => {
            const subSpecId = checkbox.value;
            if (checkbox.checked) {
                // Αποθήκευση της τρέχουσας επιλογής και της ομάδας
                const groupRadios = document.querySelectorAll(`input[name="group_${subSpecId}"]`);
                let selectedGroup = 'A';
                groupRadios.forEach(radio => {
                    if (radio.checked) {
                        selectedGroup = radio.value;
                    }
                });
                
                // Ενημέρωση του global αντικειμένου
                if (!window.allSelectedSubSpecialities[subSpecId]) {
                    window.allSelectedSubSpecialities[subSpecId] = {
                        checked: true,
                        group: selectedGroup
                    };
                } else {
                    window.allSelectedSubSpecialities[subSpecId].checked = true;
                    window.allSelectedSubSpecialities[subSpecId].group = selectedGroup;
                }
            } else if (window.allSelectedSubSpecialities[subSpecId]) {
                window.allSelectedSubSpecialities[subSpecId].checked = false;
            }
        });
        
        // Εμφάνιση του container και καθαρισμός του πίνακα
        subSpecialityContainer.style.display = 'block';
        tableBody.innerHTML = '';
        
        // Δημιουργία και προσθήκη των γραμμών του πίνακα για κάθε υποειδικότητα
        if (window.operatorSubSpecialities && window.operatorSubSpecialities[specialityId]) {
            window.operatorSubSpecialities[specialityId].forEach(item => {
                const subSpecId = item.id;
                
                // Έλεγχος αν η υποειδικότητα είναι επιλεγμένη
                let isChecked = false;
                let groupValue = item.group || 'A';
                
                // Έλεγχος από το global αντικείμενο
                if (window.allSelectedSubSpecialities[subSpecId]) {
                    isChecked = window.allSelectedSubSpecialities[subSpecId].checked;
                    groupValue = window.allSelectedSubSpecialities[subSpecId].group;
                }
                // Έλεγχος από τα αρχικά δεδομένα
                else if (window.selectedSubSpecialities && window.selectedSubSpecialities.includes(subSpecId)) {
                    isChecked = true;
                    
                    // Εύρεση της ομάδας από τα δεδομένα της βάσης
                    if (window.driverOperatorSubSpecialities) {
                        const found = window.driverOperatorSubSpecialities.find(
                            spec => spec.sub_speciality === subSpecId
                        );
                        if (found && found.group_type) {
                            groupValue = found.group_type;
                        }
                    }
                    
                    // Αποθήκευση στο global αντικείμενο
                    window.allSelectedSubSpecialities[subSpecId] = {
                        checked: true,
                        group: groupValue
                    };
                }
                
                // Δημιουργία της γραμμής του πίνακα
                const row = document.createElement('tr');
                
                row.innerHTML = `
                    <td>${subSpecId}</td>
                    <td>${item.name}</td>
                    <td>
                        <label class="toggle-switch">
                            <input type="checkbox" name="operator_sub_specialities[]" value="${subSpecId}" ${isChecked ? 'checked' : ''} 
                                onchange="updateSubSpecialitySelection(this, '${subSpecId}')">
                            <span class="toggle-slider"></span>
                        </label>
                    </td>
                    <td>
                        <div class="radio-group" id="group_container_${subSpecId}" ${isChecked ? '' : 'style="display:none;"'}>
                            <label class="radio-label">
                                <input type="radio" name="group_${subSpecId}" value="A" ${groupValue === 'A' ? 'checked' : ''} 
                                    onchange="updateSubSpecialityGroup('${subSpecId}', 'A')">
                                <span>A</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="group_${subSpecId}" value="B" ${groupValue === 'B' ? 'checked' : ''} 
                                    onchange="updateSubSpecialityGroup('${subSpecId}', 'B')">
                                <span>B</span>
                            </label>
                        </div>
                    </td>
                `;
                
                tableBody.appendChild(row);
            });
        } else {
            console.log(`Δεν βρέθηκαν υποειδικότητες για την ειδικότητα ${specialityId}`);
        }
    };

    /**
     * Ενημερώνει την επιλογή μιας υποειδικότητας
     * @param {HTMLElement} checkbox - Το checkbox που άλλαξε κατάσταση
     * @param {string} subSpecialityId - ID της υποειδικότητας
     */
    window.updateSubSpecialitySelection = function(checkbox, subSpecialityId) {
        if (!window.allSelectedSubSpecialities) {
            window.allSelectedSubSpecialities = {};
        }
        
        // Αποθήκευση της επιλογής
        if (!window.allSelectedSubSpecialities[subSpecialityId]) {
            window.allSelectedSubSpecialities[subSpecialityId] = {
                checked: checkbox.checked,
                group: 'A'  // Προεπιλεγμένη τιμή αν δεν έχει οριστεί
            };
        } else {
            window.allSelectedSubSpecialities[subSpecialityId].checked = checkbox.checked;
        }
        
        // Εμφάνιση/απόκρυψη των radio buttons ομάδων
        const groupContainer = document.getElementById('group_container_' + subSpecialityId);
        if (groupContainer) {
            groupContainer.style.display = checkbox.checked ? 'block' : 'none';
        }
    };

    /**
     * Ενημερώνει την ομάδα μιας υποειδικότητας
     * @param {string} subSpecialityId - ID της υποειδικότητας
     * @param {string} groupValue - Τιμή της ομάδας (A ή B)
     */
    window.updateSubSpecialityGroup = function(subSpecialityId, groupValue) {
        if (!window.allSelectedSubSpecialities) {
            window.allSelectedSubSpecialities = {};
        }
        
        // Ενημέρωση της ομάδας
        if (!window.allSelectedSubSpecialities[subSpecialityId]) {
            window.allSelectedSubSpecialities[subSpecialityId] = {
                checked: true,  // Αν αλλάζουμε ομάδα, θεωρούμε ότι είναι επιλεγμένο
                group: groupValue
            };
        } else {
            window.allSelectedSubSpecialities[subSpecialityId].group = groupValue;
        }
    };

    /**
     * Προετοιμάζει τα δεδομένα για αποστολή πριν την υποβολή της φόρμας
     */
    window.prepareOperatorSpecialitiesForSubmission = function() {
        const form = document.getElementById('driverProfileForm');
        if (!form) {
            console.error("Δεν βρέθηκε η φόρμα!");
            return;
        }
        
        // Μετατροπή του αντικειμένου σε μορφή κατάλληλη για αποστολή
        const selectedSubSpecialitiesArray = [];
        const selectedGroupsObj = {};
        
        for (const subSpecId in window.allSelectedSubSpecialities) {
            if (window.allSelectedSubSpecialities[subSpecId].checked) {
                selectedSubSpecialitiesArray.push(subSpecId);
                selectedGroupsObj[subSpecId] = window.allSelectedSubSpecialities[subSpecId].group;
            }
        }
        
        // Προσθήκη ή ενημέρωση των κρυφών πεδίων
        let hiddenField = document.getElementById('all_selected_subspecialities');
        if (!hiddenField) {
            hiddenField = document.createElement('input');
            hiddenField.type = 'hidden';
            hiddenField.id = 'all_selected_subspecialities';
            hiddenField.name = 'all_selected_subspecialities';
            form.appendChild(hiddenField);
        }
        hiddenField.value = JSON.stringify(selectedSubSpecialitiesArray);
        
        let groupsField = document.getElementById('all_selected_groups');
        if (!groupsField) {
            groupsField = document.createElement('input');
            groupsField.type = 'hidden';
            groupsField.id = 'all_selected_groups';
            groupsField.name = 'all_selected_groups';
            form.appendChild(groupsField);
        }
        groupsField.value = JSON.stringify(selectedGroupsObj);
    };

    // Προσθήκη του listener υποβολής στη φόρμα
    const form = document.getElementById('driverProfileForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            window.prepareOperatorSpecialitiesForSubmission();
        });
        
        // Αρχική φόρτωση των υποειδικοτήτων αν έχει επιλεγεί ειδικότητα
        const specialitySelect = document.getElementById('operator_speciality');
        if (specialitySelect && specialitySelect.value) {
            window.loadSubSpecialities(specialitySelect.value);
        }
    }

    // -------------------- Διαχείριση OCR για σκανάρισμα εγγράφων --------------------
    const scanButtons = document.querySelectorAll('.btn-scan');
    scanButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Έλεγχος για το Tesseract
            if (typeof Tesseract === 'undefined') {
                console.error('Το Tesseract δεν έχει φορτωθεί σωστά.');
                alert('Η λειτουργία OCR δεν είναι διαθέσιμη. Παρακαλώ εισάγετε τα στοιχεία χειροκίνητα.');
                return;
            }
            
            const buttonId = this.id;
            const targetInputId = buttonId.replace('scan-', '').replace('-front', '_front_image').replace('-back', '_back_image');
            const fileInput = document.getElementById(targetInputId);
            
            if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                alert('Παρακαλώ επιλέξτε πρώτα μια εικόνα για σκανάρισμα.');
                return;
            }
            
            // Εμφάνιση ένδειξης φόρτωσης
            const loadingIndicator = document.createElement('div');
            loadingIndicator.className = 'loading-indicator';
            loadingIndicator.innerHTML = '<span>Γίνεται επεξεργασία OCR...</span>';
            this.parentNode.appendChild(loadingIndicator);
            this.disabled = true;
            
            const file = fileInput.files[0];
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const imageDataUrl = e.target.result;
                
                // Χρήση του Tesseract για OCR
                try {
                    Tesseract.recognize(imageDataUrl, 'eng+ell')
                        .then(result => {
                            console.log("Αναγνωρισμένο κείμενο:", result.data.text);
                            
                            // Εξαγωγή πληροφοριών ανάλογα με τον τύπο του εγγράφου
                            if (buttonId.includes('license')) {
                                extractLicenseInfo(result.data.text);
                            } else if (buttonId.includes('adr')) {
                                extractADRInfo(result.data.text);
                            } else if (buttonId.includes('tachograph')) {
                                extractTachographInfo(result.data.text);
                            } else if (buttonId.includes('operator')) {
                                extractOperatorInfo(result.data.text);
                            }
                            
                            // Αφαίρεση ένδειξης φόρτωσης
                            loadingIndicator.remove();
                            button.disabled = false;
                            
                            alert('Η αναγνώριση ολοκληρώθηκε. Παρακαλώ ελέγξτε τα πεδία και κάντε διορθώσεις όπου χρειάζεται.');
                        })
                        .catch(err => {
                            console.error('Σφάλμα κατά την αναγνώριση OCR:', err);
                            alert('Σφάλμα κατά την αναγνώριση. Παρακαλώ εισάγετε τα δεδομένα χειροκίνητα.');
                            loadingIndicator.remove();
                            button.disabled = false;
                        });
                } catch (error) {
                    console.error('Σφάλμα κατά την προετοιμασία OCR:', error);
                    alert('Σφάλμα προετοιμασίας OCR. Παρακαλώ εισάγετε τα δεδομένα χειροκίνητα.');
                    loadingIndicator.remove();
                    button.disabled = false;
                }
            };
            
            reader.onerror = function(error) {
                console.error('Σφάλμα ανάγνωσης αρχείου:', error);
                alert('Σφάλμα κατά την ανάγνωση του αρχείου. Παρακαλώ προσπαθήστε ξανά.');
                loadingIndicator.remove();
                button.disabled = false;
            };
            
            reader.readAsDataURL(file);
        });
    });
    
    // Συναρτήσεις εξαγωγής πληροφοριών (ορίζονται ως συναρτήσεις κενού περιεχομένου στην περίπτωση που δεν υπάρχουν οι εξαγωγές πληροφοριών)
    window.extractLicenseInfo = window.extractLicenseInfo || function(text) { 
        console.log("Εξαγωγή πληροφοριών άδειας οδήγησης από:", text); 
    };
    
    window.extractADRInfo = window.extractADRInfo || function(text) { 
        console.log("Εξαγωγή πληροφοριών ADR από:", text); 
    };
    
    window.extractTachographInfo = window.extractTachographInfo || function(text) { 
        console.log("Εξαγωγή πληροφοριών ταχογράφου από:", text); 
    };
    
    window.extractOperatorInfo = window.extractOperatorInfo || function(text) { 
        console.log("Εξαγωγή πληροφοριών άδειας χειριστή από:", text); 
    };
    
    // -------------------- Υποστήριξη για πολλαπλές επιλογές --------------------
    const multipleSelectElements = document.querySelectorAll('select[multiple]');
    multipleSelectElements.forEach(select => {
        // Προσθήκη βοηθητικού μηνύματος
        const helpText = document.createElement('div');
        helpText.className = 'select-help-text';
        helpText.textContent = 'Επιλέξτε με Ctrl+κλικ για πολλαπλή επιλογή';
        select.parentNode.insertBefore(helpText, select.nextSibling);
    });
});