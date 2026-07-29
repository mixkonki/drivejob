document.addEventListener('DOMContentLoaded', function() {
    // Αναφορές στα στοιχεία του DOM
    const legalStatusRadios = document.querySelectorAll('input[name="legal_status"]');
    const criminalRecordUpload = document.getElementById('criminal_record_upload');
    const criminalRecordFile = document.getElementById('criminal_record_file');

    // Αρχικοποίηση - Εμφάνιση του πεδίου ανεβάσματος αρχείου αν είναι επιλεγμένο το "Ναι"
    function initializeToggle() {
        const yesRadio = document.querySelector('input[name="legal_status"][value="yes"]');
        if (yesRadio && yesRadio.checked) {
            criminalRecordUpload.style.display = 'block';
        } else {
            criminalRecordUpload.style.display = 'none';
        }
    }

    // Προσθήκη event listeners στα radio buttons
    legalStatusRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            if (this.value === 'yes') {
                criminalRecordUpload.style.display = 'block';
            } else {
                criminalRecordUpload.style.display = 'none';
                // Καθαρισμός του πεδίου αρχείου όταν επιλέγεται "Όχι"
                if (criminalRecordFile) {
                    criminalRecordFile.value = '';
                }
            }
        });
    });

    // Αρχικοποίηση κατά τη φόρτωση της σελίδας
    initializeToggle();

    // Προσθήκη κουμπιών για εναλλαγή του ποινικού μητρώου
    const radioGroup = document.querySelector('.radio-group');
    if (radioGroup) {
        // Δημιουργία του toggle button
        const toggleContainer = document.createElement('div');
        toggleContainer.className = 'toggle-container';
        toggleContainer.style.marginBottom = '15px';
        toggleContainer.style.display = 'flex';
        toggleContainer.style.alignItems = 'center';

        const toggleLabel = document.createElement('label');
        toggleLabel.className = 'toggle-switch-label';
        toggleLabel.style.display = 'flex';
        toggleLabel.style.alignItems = 'center';
        toggleLabel.style.cursor = 'pointer';

        const labelText = document.createElement('span');
        labelText.className = 'toggle-label-text';
        labelText.textContent = 'Έχω ποινικό μητρώο:';
        labelText.style.marginRight = '10px';
        labelText.style.fontWeight = '500';

        const toggleSwitch = document.createElement('span');
        toggleSwitch.className = 'toggle-switch';
        toggleSwitch.style.position = 'relative';
        toggleSwitch.style.display = 'inline-block';
        toggleSwitch.style.width = '60px';
        toggleSwitch.style.height = '34px';

        const toggleInput = document.createElement('input');
        toggleInput.type = 'checkbox';
        toggleInput.className = 'toggle-switch-input';
        toggleInput.style.opacity = '0';
        toggleInput.style.width = '0';
        toggleInput.style.height = '0';

        // Έλεγχος αν το "Ναι" είναι επιλεγμένο και ρύθμιση του toggle ανάλογα
        const yesRadio = document.querySelector('input[name="legal_status"][value="yes"]');
        if (yesRadio && yesRadio.checked) {
            toggleInput.checked = true;
        }

        const toggleSlider = document.createElement('span');
        toggleSlider.className = 'toggle-switch-slider';
        toggleSlider.style.position = 'absolute';
        toggleSlider.style.cursor = 'pointer';
        toggleSlider.style.top = '0';
        toggleSlider.style.left = '0';
        toggleSlider.style.right = '0';
        toggleSlider.style.bottom = '0';
        toggleSlider.style.backgroundColor = '#ccc';
        toggleSlider.style.transition = '.4s';
        toggleSlider.style.borderRadius = '34px';

        // Προσθήκη του κύκλου στο slider
        toggleSlider.innerHTML = '<span style="position: absolute; content: \'\'; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; transform: ' + (toggleInput.checked ? 'translateX(26px)' : 'translateX(0)') + ';"></span>';

        const toggleText = document.createElement('span');
        toggleText.className = 'toggle-switch-text';
        toggleText.textContent = toggleInput.checked ? 'Ναι' : 'Όχι';
        toggleText.style.marginLeft = '10px';

        // Προσθήκη event listener στο toggle
        toggleInput.addEventListener('change', function() {
            const yesRadio = document.querySelector('input[name="legal_status"][value="yes"]');
            const noRadio = document.querySelector('input[name="legal_status"][value="no"]');
            
            if (this.checked) {
                yesRadio.checked = true;
                criminalRecordUpload.style.display = 'block';
                toggleText.textContent = 'Ναι';
                toggleSlider.querySelector('span').style.transform = 'translateX(26px)';
                toggleSlider.style.backgroundColor = '#2196F3';
            } else {
                noRadio.checked = true;
                criminalRecordUpload.style.display = 'none';
                if (criminalRecordFile) {
                    criminalRecordFile.value = '';
                }
                toggleText.textContent = 'Όχι';
                toggleSlider.querySelector('span').style.transform = 'translateX(0)';
                toggleSlider.style.backgroundColor = '#ccc';
            }
        });

        // Αρχικοποίηση του χρώματος του slider
        if (toggleInput.checked) {
            toggleSlider.style.backgroundColor = '#2196F3';
        }

        // Συναρμολόγηση του toggle
        toggleSwitch.appendChild(toggleInput);
        toggleSwitch.appendChild(toggleSlider);
        toggleLabel.appendChild(labelText);
        toggleLabel.appendChild(toggleSwitch);
        toggleLabel.appendChild(toggleText);
        toggleContainer.appendChild(toggleLabel);

        // Προσθήκη του toggle στη σελίδα
        radioGroup.prepend(toggleContainer);

        // Απόκρυψη των αρχικών radio buttons
        const radioLabels = radioGroup.querySelectorAll('.radio-inline');
        radioLabels.forEach(function(label) {
            label.style.display = 'none';
        });
    }
});
