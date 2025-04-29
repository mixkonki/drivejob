/**
 * DriveJob - Διαχείριση Αγγελιών
 * 
 * Αυτό το αρχείο περιέχει τις λειτουργίες JavaScript για τη διαχείριση των αγγελιών
 * από τους οδηγούς και τις εταιρείες, συμπεριλαμβανομένης της δημιουργίας, επεξεργασίας
 * και προβολής αγγελιών.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Αρχικοποίηση όλων των λειτουργιών
    initLocationAutocomplete();
    initTagsSelection();
    initSalaryRangeValidation();
    initDateValidation();
    initRequirementsToggle();
    initFormValidation();
    initDeleteConfirmation();
    initApplicationSubmission();
    initMapPreview();
    initImagePreview();
});

/**
 * Αρχικοποίηση του autocomplete για τα πεδία τοποθεσίας με το Google Maps API
 */
function initLocationAutocomplete() {
    const locationInput = document.getElementById('location');
    if (!locationInput) return;
    
    try {
        // Δημιουργία του αντικειμένου Autocomplete
        const autocomplete = new google.maps.places.Autocomplete(locationInput, {
            types: ['(cities)']
        });
        
        // Όταν επιλέγεται τοποθεσία
        autocomplete.addListener('place_changed', function() {
            const place = autocomplete.getPlace();
            
            // Αν το αποτέλεσμα έχει γεωμετρία, ενημερώνουμε τα πεδία
            if (place.geometry) {
                const latitudeInput = document.getElementById('latitude');
                const longitudeInput = document.getElementById('longitude');
                
                if (latitudeInput && longitudeInput) {
                    latitudeInput.value = place.geometry.location.lat();
                    longitudeInput.value = place.geometry.location.lng();
                }
                
                // Αν έχει διεύθυνση, ενημερώνουμε το πεδίο διεύθυνσης
                if (place.address_components) {
                    let city = '', country = '';
                    
                    for (const component of place.address_components) {
                        if (component.types.includes('locality')) {
                            city = component.long_name;
                        } else if (component.types.includes('country')) {
                            country = component.long_name;
                        }
                    }
                    
                    // Ενημέρωση των πεδίων της πόλης και της χώρας αν υπάρχουν
                    const cityInput = document.getElementById('city');
                    const countryInput = document.getElementById('country');
                    
                    if (cityInput && city) {
                        cityInput.value = city;
                    }
                    
                    if (countryInput && country) {
                        countryInput.value = country;
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error initializing Google Maps Autocomplete:', error);
    }
}

/**
 * Αρχικοποίηση της επιλογής ετικετών (tags)
 */
function initTagsSelection() {
    const tagsContainer = document.querySelector('.tags-container');
    if (!tagsContainer) return;
    
    // Προσθήκη επιλογέα "Επιλογή Όλων"
    const selectAllContainer = document.createElement('div');
    selectAllContainer.className = 'select-all-container';
    
    const selectAllLabel = document.createElement('label');
    selectAllLabel.innerHTML = '<input type="checkbox" id="select-all-tags"> Επιλογή Όλων';
    
    selectAllContainer.appendChild(selectAllLabel);
    tagsContainer.parentNode.insertBefore(selectAllContainer, tagsContainer);
    
    // Προσθήκη λειτουργίας "Επιλογή Όλων"
    const selectAllCheckbox = document.getElementById('select-all-tags');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const tagCheckboxes = tagsContainer.querySelectorAll('input[type="checkbox"]');
            
            tagCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
        });
    }
    
    // Ενημέρωση του "Επιλογή Όλων" όταν αλλάζει η επιλογή των ετικετών
    const tagCheckboxes = tagsContainer.querySelectorAll('input[type="checkbox"]');
    tagCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (!selectAllCheckbox) return;
            
            const allChecked = Array.from(tagCheckboxes).every(cb => cb.checked);
            const noneChecked = Array.from(tagCheckboxes).every(cb => !cb.checked);
            
            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = !allChecked && !noneChecked;
        });
    });
}

/**
 * Αρχικοποίηση της επικύρωσης για το εύρος μισθού
 */
function initSalaryRangeValidation() {
    const salaryMinInput = document.getElementById('salary_min');
    const salaryMaxInput = document.getElementById('salary_max');
    
    if (!salaryMinInput || !salaryMaxInput) return;
    
    // Έλεγχος ότι ο ελάχιστος μισθός δεν είναι μεγαλύτερος από τον μέγιστο
    function validateSalaryRange() {
        const minValue = parseInt(salaryMinInput.value) || 0;
        const maxValue = parseInt(salaryMaxInput.value) || 0;
        
        if (maxValue > 0 && minValue > maxValue) {
            salaryMaxInput.setCustomValidity('Η μέγιστη αμοιβή πρέπει να είναι μεγαλύτερη από την ελάχιστη.');
        } else {
            salaryMaxInput.setCustomValidity('');
        }
    }
    
    salaryMinInput.addEventListener('change', validateSalaryRange);
    salaryMaxInput.addEventListener('change', validateSalaryRange);
}

/**
 * Αρχικοποίηση της επικύρωσης για τις ημερομηνίες
 */
function initDateValidation() {
    const expiryDateInput = document.getElementById('expires_at');
    
    if (!expiryDateInput) return;
    
    // Η ημερομηνία λήξης πρέπει να είναι μελλοντική
    function validateExpiryDate() {
        const expiryDate = new Date(expiryDateInput.value);
        const today = new Date();
        today.setHours(0, 0, 0, 0); // Μηδενισμός της ώρας για σύγκριση μόνο ημερομηνιών
        
        if (expiryDate < today) {
            expiryDateInput.setCustomValidity('Η ημερομηνία λήξης πρέπει να είναι μελλοντική.');
        } else {
            expiryDateInput.setCustomValidity('');
        }
    }
    
    expiryDateInput.addEventListener('change', validateExpiryDate);
    validateExpiryDate(); // Αρχική επικύρωση
}

/**
 * Αρχικοποίηση της εναλλαγής προβολής των ειδικών απαιτήσεων
 */
function initRequirementsToggle() {
    const adrCheckbox = document.getElementById('adr_certificate');
    const operatorCheckbox = document.getElementById('operator_license');
    
    const adrDetailsContainer = document.getElementById('adr_details_container');
    const operatorDetailsContainer = document.getElementById('operator_details_container');
    
    // Εναλλαγή προβολής για τις λεπτομέρειες ADR
    if (adrCheckbox && adrDetailsContainer) {
        adrCheckbox.addEventListener('change', function() {
            adrDetailsContainer.style.display = adrCheckbox.checked ? 'block' : 'none';
        });
        
        // Αρχική κατάσταση
        adrDetailsContainer.style.display = adrCheckbox.checked ? 'block' : 'none';
    }
    
    // Εναλλαγή προβολής για τις λεπτομέρειες άδειας χειριστή
    if (operatorCheckbox && operatorDetailsContainer) {
        operatorCheckbox.addEventListener('change', function() {
            operatorDetailsContainer.style.display = operatorCheckbox.checked ? 'block' : 'none';
        });
        
        // Αρχική κατάσταση
        operatorDetailsContainer.style.display = operatorCheckbox.checked ? 'block' : 'none';
    }
}

/**
 * Αρχικοποίηση της επικύρωσης της φόρμας
 */
function initFormValidation() {
    const jobListingForm = document.querySelector('.job-listing-form');
    
    if (!jobListingForm) return;
    
    jobListingForm.addEventListener('submit', function(event) {
        // Έλεγχος αν τα υποχρεωτικά πεδία είναι συμπληρωμένα
        const requiredFields = jobListingForm.querySelectorAll('[required]');
        let hasError = false;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('error');
                hasError = true;
                
                // Προσθήκη μηνύματος σφάλματος
                const errorMsg = field.parentNode.querySelector('.error-message');
                if (!errorMsg) {
                    const msg = document.createElement('div');
                    msg.className = 'error-message';
                    msg.textContent = 'Το πεδίο είναι υποχρεωτικό.';
                    field.parentNode.appendChild(msg);
                }
            } else {
                field.classList.remove('error');
                const errorMsg = field.parentNode.querySelector('.error-message');
                if (errorMsg) {
                    errorMsg.remove();
                }
            }
        });
        
        // Έλεγχος για προσαρμοσμένα σφάλματα από το JavaScript
        const invalidFields = jobListingForm.querySelectorAll(':invalid');
        if (invalidFields.length > 0) {
            hasError = true;
        }
        
        if (hasError) {
            event.preventDefault();
            
            // Εστίαση στο πρώτο πεδίο με σφάλμα
            const firstErrorField = jobListingForm.querySelector('.error, :invalid');
            if (firstErrorField) {
                firstErrorField.focus();
                
                // Κύλιση στο πρώτο πεδίο με σφάλμα
                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
}

/**
 * Αρχικοποίηση της επιβεβαίωσης διαγραφής
 */
function initDeleteConfirmation() {
    const deleteButtons = document.querySelectorAll('.btn-danger[onclick*="confirm"]');
    
    deleteButtons.forEach(button => {
        // Αφαίρεση του ήδη υπάρχοντος onclick
        const onclickValue = button.getAttribute('onclick');
        button.removeAttribute('onclick');
        
        // Προσθήκη νέου event listener
        button.addEventListener('click', function(event) {
            if (!confirm('Είστε βέβαιοι ότι θέλετε να διαγράψετε αυτή την αγγελία;')) {
                event.preventDefault();
            }
        });
    });
}

/**
 * Αρχικοποίηση της υποβολής αίτησης
 */
function initApplicationSubmission() {
    const applyForms = document.querySelectorAll('form[action*="job-applications/apply"]');
    
    applyForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            
            // Εμφάνιση modal για εισαγωγή μηνύματος
            showApplicationModal(form.action);
        });
    });
}

/**
 * Εμφανίζει ένα modal για την εισαγωγή μηνύματος αίτησης
 * 
 * @param {string} formAction Το URL του action της φόρμας
 */
function showApplicationModal(formAction) {
    // Δημιουργία του modal
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h3>Υποβολή Αίτησης</h3>
            <form id="application-form" action="${formAction}" method="POST">
                <div class="form-group">
                    <label for="application-message">Μήνυμα (προαιρετικό)</label>
                    <textarea id="application-message" name="message" rows="5" placeholder="Γράψτε ένα σύντομο μήνυμα προς τον εργοδότη..."></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Υποβολή Αίτησης</button>
                    <button type="button" class="btn-secondary cancel-modal">Ακύρωση</button>
                </div>
            </form>
        </div>
    `;
    
    // Προσθήκη του CSRF token
    const csrfTokenInput = document.querySelector('input[name="csrf_token"]');
    if (csrfTokenInput) {
        const csrfToken = csrfTokenInput.value;
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = csrfToken;
        
        modal.querySelector('#application-form').appendChild(csrfInput);
    }
    
    // Προσθήκη του modal στο σώμα της σελίδας
    document.body.appendChild(modal);
    
    // Εμφάνιση του modal
    setTimeout(() => {
        modal.style.display = 'flex';
    }, 10);
    
    // Κλείσιμο του modal με το X
    const closeBtn = modal.querySelector('.close-modal');
    closeBtn.addEventListener('click', function() {
        closeModal(modal);
    });
    
    // Κλείσιμο του modal με το κουμπί Ακύρωση
    const cancelBtn = modal.querySelector('.cancel-modal');
    cancelBtn.addEventListener('click', function() {
        closeModal(modal);
    });
    
    // Κλείσιμο του modal με κλικ έξω από αυτό
    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeModal(modal);
        }
    });
    
    // Υποβολή της φόρμας αίτησης
    const applicationForm = modal.querySelector('#application-form');
    applicationForm.addEventListener('submit', function(event) {
        event.preventDefault();
        
        // Υποβολή της φόρμας με AJAX
        const formData = new FormData(applicationForm);
        
        fetch(applicationForm.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            closeModal(modal);
            
            if (data.success) {
                showNotification('success', 'Η αίτησή σας υποβλήθηκε με επιτυχία!');
                
                // Ανανέωση της σελίδας μετά από μικρή καθυστέρηση
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification('error', data.message || 'Υπήρξε ένα σφάλμα κατά την υποβολή της αίτησης.');
            }
        })
        .catch(error => {
            closeModal(modal);
            showNotification('error', 'Υπήρξε ένα σφάλμα επικοινωνίας με τον διακομιστή.');
            console.error('Error:', error);
        });
    });
}

/**
 * Κλείνει ένα modal
 * 
 * @param {HTMLElement} modal Το element του modal
 */
function closeModal(modal) {
    modal.style.opacity = '0';
    
    setTimeout(() => {
        modal.remove();
    }, 300);
}

/**
 * Εμφανίζει μια ειδοποίηση
 * 
 * @param {string} type Ο τύπος της ειδοποίησης ('success' ή 'error')
 * @param {string} message Το μήνυμα της ειδοποίησης
 */
function showNotification(type, message) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.right = '20px';
    }, 10);
    
    setTimeout(() => {
        notification.style.right = '-300px';
        
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

/**
 * Αρχικοποίηση της προεπισκόπησης χάρτη
 */
function initMapPreview() {
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');
    const mapPreviewContainer = document.getElementById('map-preview');
    
    if (!latitudeInput || !longitudeInput || !mapPreviewContainer) return;
    
    // Δημιουργία του χάρτη αν υπάρχουν συντεταγμένες
    function updateMapPreview() {
        const latitude = parseFloat(latitudeInput.value);
        const longitude = parseFloat(longitudeInput.value);
        
        if (isNaN(latitude) || isNaN(longitude)) {
            mapPreviewContainer.innerHTML = '<p>Δεν έχουν οριστεί έγκυρες συντεταγμένες.</p>';
            return;
        }
        
        // Δημιουργία του χάρτη
        mapPreviewContainer.innerHTML = `
            <iframe
                width="100%"
                height="250"
                frameborder="0"
                scrolling="no"
                marginheight="0"
                marginwidth="0"
                src="https://maps.google.com/maps?q=${latitude},${longitude}&z=15&output=embed"
            ></iframe>
        `;
    }
    
    // Ενημέρωση του χάρτη όταν αλλάζουν οι συντεταγμένες
    latitudeInput.addEventListener('change', updateMapPreview);
    longitudeInput.addEventListener('change', updateMapPreview);
    
    // Αρχική προεπισκόπηση
    updateMapPreview();
}

/**
 * Αρχικοποίηση της προεπισκόπησης εικόνων
 */
function initImagePreview() {
    // Εύρεση όλων των πεδίων μεταφόρτωσης εικόνων
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    
    imageInputs.forEach(input => {
        // Δημιουργία container για την προεπισκόπηση
        const previewContainer = document.createElement('div');
        previewContainer.className = 'image-preview-container';
        previewContainer.innerHTML = '<div class="image-preview"></div>';
        
        // Προσθήκη μετά το πεδίο μεταφόρτωσης
        input.parentNode.insertBefore(previewContainer, input.nextSibling);
        
        // Λήψη του container προεπισκόπησης
        const preview = previewContainer.querySelector('.image-preview');
        
        // Αν υπάρχει ήδη εικόνα, εμφάνισέ την
        const fieldName = input.name.replace('_upload', '');
        const existingImage = document.querySelector(`img[data-field="${fieldName}"]`);
        
        if (existingImage) {
            const imageUrl = existingImage.src;
            preview.innerHTML = `<img src="${imageUrl}" alt="Προεπισκόπηση">`;
            preview.style.display = 'block';
        }
        
        // Προεπισκόπηση της εικόνας κατά την επιλογή
        input.addEventListener('change', function() {
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Προεπισκόπηση">`;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        });
    });
}

/**
 * Ενημέρωση διαθεσιμότητας οδηγού μέσω AJAX
 * 
 * @param {HTMLElement} button Το κουμπί που πατήθηκε
 */
function toggleAvailability(button) {
    // Λήψη του CSRF token
    const csrfToken = document.querySelector('input[name="csrf_token"]').value;
    
    // Αποστολή του αιτήματος
    fetch('/drivers/toggle-availability', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `csrf_token=${csrfToken}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Ενημέρωση του UI
            const availabilityStatus = document.querySelector('.availability-status');
            if (availabilityStatus) {
                availabilityStatus.classList.toggle('available');
                availabilityStatus.classList.toggle('unavailable');
                
                const statusText = availabilityStatus.querySelector('.status-text');
                if (statusText) {
                    statusText.textContent = availabilityStatus.classList.contains('available') ? 
                        'Διαθέσιμος/η για εργασία' : 'Μη διαθέσιμος/η για εργασία';
                }
            }
            
            // Ενημέρωση του κειμένου του κουμπιού
            button.textContent = button.textContent.includes('μη διαθέσιμος') ? 
                'Αλλαγή σε διαθέσιμος/η' : 'Αλλαγή σε μη διαθέσιμος/η';
            
            showNotification('success', 'Η διαθεσιμότητά σας ενημερώθηκε με επιτυχία!');
        } else {
            showNotification('error', 'Υπήρξε ένα σφάλμα κατά την ενημέρωση της διαθεσιμότητας.');
        }
    })
    .catch(error => {
        showNotification('error', 'Υπήρξε ένα σφάλμα επικοινωνίας με τον διακομιστή.');
        console.error('Error:', error);
    });
}