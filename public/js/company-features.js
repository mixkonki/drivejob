/**
 * Company Features JavaScript
 * Λειτουργικότητα για τα tabs και τις interactive features
 */

document.addEventListener('DOMContentLoaded', function() {
    // Tab Navigation - More specific selectors for company edit form
    const tabBtns = document.querySelectorAll('.form-tabs .tab-btn');
    const tabPanes = document.querySelectorAll('.form-tabs .tab-pane');

    if (tabBtns.length > 0) {
        tabBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all tabs
                tabBtns.forEach(b => b.classList.remove('active'));
                tabPanes.forEach(p => p.classList.remove('active'));
                
                // Add active class to clicked tab
                btn.classList.add('active');
                const tabId = btn.getAttribute('data-tab');
                const targetPane = document.getElementById(tabId);
                if (targetPane) {
                    targetPane.classList.add('active');
                }
            });
        });
    }

    // International Operations Toggle
    const internationalCheckbox = document.querySelector('input[name="operates_internationally"]');
    const countriesGroup = document.getElementById('operating_countries_group');

    if (internationalCheckbox && countriesGroup) {
        // Set initial state
        countriesGroup.style.display = internationalCheckbox.checked ? 'block' : 'none';
        
        internationalCheckbox.addEventListener('change', function() {
            countriesGroup.style.display = this.checked ? 'block' : 'none';
            
            // If unchecked, also uncheck all country checkboxes
            if (!this.checked) {
                const countryCheckboxes = countriesGroup.querySelectorAll('input[type="checkbox"]');
                countryCheckboxes.forEach(cb => cb.checked = false);
            }
        });
    }

    // Fleet Management Features Toggle
    const fleetManagementCheckbox = document.querySelector('input[name="has_fleet_management"]');
    const fleetFeatures = document.querySelectorAll('.fleet-feature');

    if (fleetManagementCheckbox) {
        fleetManagementCheckbox.addEventListener('change', function() {
            fleetFeatures.forEach(feature => {
                feature.style.opacity = this.checked ? '1' : '0.5';
                const inputs = feature.querySelectorAll('input');
                inputs.forEach(input => {
                    if (input !== fleetManagementCheckbox) {
                        input.disabled = !this.checked;
                    }
                });
            });
        });
    }

    // HR System Features Toggle
    const hrSystemCheckbox = document.querySelector('input[name="has_hr_system"]');
    const hrFeatures = document.querySelectorAll('.hr-feature');

    if (hrSystemCheckbox) {
        hrSystemCheckbox.addEventListener('change', function() {
            hrFeatures.forEach(feature => {
                feature.style.opacity = this.checked ? '1' : '0.5';
                const inputs = feature.querySelectorAll('input');
                inputs.forEach(input => {
                    if (input !== hrSystemCheckbox) {
                        input.disabled = !this.checked;
                    }
                });
            });
        });
    }

    // Form Validation
    const editProfileForm = document.querySelector('.edit-profile-form');
    if (editProfileForm) {
        editProfileForm.addEventListener('submit', function(e) {
            // Basic validation
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                    
                    // Show error message
                    let errorMsg = field.parentElement.querySelector('.field-error');
                    if (!errorMsg) {
                        errorMsg = document.createElement('div');
                        errorMsg.className = 'field-error';
                        errorMsg.textContent = 'Αυτό το πεδίο είναι υποχρεωτικό';
                        field.parentElement.appendChild(errorMsg);
                    }
                } else {
                    field.classList.remove('error');
                    const errorMsg = field.parentElement.querySelector('.field-error');
                    if (errorMsg) {
                        errorMsg.remove();
                    }
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Παρακαλώ συμπληρώστε όλα τα υποχρεωτικά πεδία');
            }
        });
    }

    // Auto-save draft
    let autoSaveTimer;
    const formInputs = document.querySelectorAll('.edit-profile-form input, .edit-profile-form select, .edit-profile-form textarea');
    
    formInputs.forEach(input => {
        input.addEventListener('change', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(() => {
                saveDraft();
            }, 2000);
        });
    });

    function saveDraft() {
        const formData = new FormData(editProfileForm);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            if (data[key]) {
                if (!Array.isArray(data[key])) {
                    data[key] = [data[key]];
                }
                data[key].push(value);
            } else {
                data[key] = value;
            }
        }
        
        localStorage.setItem('company_profile_draft', JSON.stringify(data));
        showNotification('Οι αλλαγές αποθηκεύτηκαν προσωρινά', 'info');
    }

    // Load draft on page load
    const savedDraft = localStorage.getItem('company_profile_draft');
    if (savedDraft && editProfileForm) {
        const draftData = JSON.parse(savedDraft);
        const loadDraftBtn = document.createElement('button');
        loadDraftBtn.type = 'button';
        loadDraftBtn.className = 'btn-secondary';
        loadDraftBtn.textContent = 'Φόρτωση προσωρινά αποθηκευμένων αλλαγών';
        loadDraftBtn.onclick = function() {
            if (confirm('Θέλετε να φορτώσετε τις προσωρινά αποθηκευμένες αλλαγές;')) {
                loadDraft(draftData);
                this.remove();
            }
        };
        
        const formActions = document.querySelector('.form-actions');
        if (formActions) {
            formActions.insertBefore(loadDraftBtn, formActions.firstChild);
        }
    }

    function loadDraft(data) {
        Object.keys(data).forEach(key => {
            const field = document.querySelector(`[name="${key}"]`);
            if (field) {
                if (field.type === 'checkbox') {
                    field.checked = data[key] === '1' || data[key] === 'on';
                } else {
                    field.value = data[key];
                }
            }
        });
        showNotification('Οι προσωρινά αποθηκευμένες αλλαγές φορτώθηκαν', 'success');
    }

    // Notification function
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }

    // Character counter for textareas
    const textareas = document.querySelectorAll('textarea[maxlength]');
    textareas.forEach(textarea => {
        const maxLength = textarea.getAttribute('maxlength');
        const counter = document.createElement('div');
        counter.className = 'char-counter';
        counter.textContent = `0 / ${maxLength}`;
        textarea.parentElement.appendChild(counter);
        
        textarea.addEventListener('input', function() {
            const currentLength = this.value.length;
            counter.textContent = `${currentLength} / ${maxLength}`;
            
            if (currentLength > maxLength * 0.9) {
                counter.classList.add('warning');
            } else {
                counter.classList.remove('warning');
            }
        });
    });

    // Smooth scroll to section if hash in URL
    if (window.location.hash) {
        const targetTab = window.location.hash.substring(1);
        const targetBtn = document.querySelector(`[data-tab="${targetTab}"]`);
        if (targetBtn) {
            targetBtn.click();
            setTimeout(() => {
                targetBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 100);
        }
    }
});

// Additional CSS for notifications
const style = document.createElement('style');
style.textContent = `
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.3s ease;
    z-index: 9999;
    max-width: 300px;
}

.notification.show {
    opacity: 1;
    transform: translateX(0);
}

.notification-info {
    background: #3b82f6;
}

.notification-success {
    background: #10b981;
}

.notification-error {
    background: #ef4444;
}

.notification-warning {
    background: #f59e0b;
}

.field-error {
    color: #ef4444;
    font-size: 0.875rem;
    margin-top: 5px;
}

.char-counter {
    text-align: right;
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 5px;
}

.char-counter.warning {
    color: #f59e0b;
}

input.error,
textarea.error,
select.error {
    border-color: #ef4444 !important;
}
`;
document.head.appendChild(style);
