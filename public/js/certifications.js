/**
 * Λειτουργικότητα φόρμας πιστοποιητικών εκπαίδευσης
 */

// Αρχικοποίηση των μεταβλητών
let certifications = [];

// Αντιστοίχιση κατηγοριών με ονόματα
const categoryNames = {
    'road_safety': 'Οδική ασφάλεια',
    'tachograph': 'Ταχογράφος',
    'loading_securing': 'Φόρτωση - Πρόσδεση',
    'technical': 'Τεχνική επιμόρφωση',
    'commercial': 'Εμπορική επιμόρφωση',
    'procedures': 'Διαδικασίες',
    'inspections': 'Έλεγχοι',
    'other': 'Άλλο'
};

// Αντιστοίχιση κατηγοριών με βαθμούς
const categoryPoints = {
    'road_safety': 50,
    'tachograph': 20,
    'loading_securing': 50,
    'technical': 20,
    'commercial': 20,
    'procedures': 20,
    'inspections': 20,
    'other': 20
};

// Αντιστοίχιση τύπων μεταφοράς με ονόματα
const transportTypeNames = {
    'freight': 'Εμπορευματικές',
    'passenger': 'Επιβατικές',
    'both': 'Εμπορευματικές & Επιβατικές'
};

// Προσθήκη νέου πιστοποιητικού
function addCertification() {
    const titleInput = document.getElementById('new_title');
    const providerInput = document.getElementById('new_provider');
    const categorySelect = document.getElementById('new_category');
    const dateInput = document.getElementById('new_date');
    const expiryInput = document.getElementById('new_expiry');
    const durationInput = document.getElementById('new_duration');
    const descriptionInput = document.getElementById('new_description');

    // Έλεγχος υποχρεωτικών πεδίων
    if (!titleInput.value) {
        alert('Παρακαλώ συμπληρώστε τον τίτλο του πιστοποιητικού');
        titleInput.focus();
        return false;
    }
    
    if (!providerInput.value) {
        alert('Παρακαλώ συμπληρώστε τον πάροχο του πιστοποιητικού');
        providerInput.focus();
        return false;
    }
    
    if (!categorySelect.value) {
        alert('Παρακαλώ επιλέξτε θεματολογία για το πιστοποιητικό');
        categorySelect.focus();
        return false;
    }
    
    if (!dateInput.value) {
        alert('Παρακαλώ συμπληρώστε την ημερομηνία απόκτησης του πιστοποιητικού');
        dateInput.focus();
        return false;
    }
    
    if (!durationInput.value || parseInt(durationInput.value) <= 0) {
        alert('Παρακαλώ συμπληρώστε τη διάρκεια του πιστοποιητικού σε ώρες');
        durationInput.focus();
        return false;
    }

    // Δημιουργία νέας εγγραφής
    const newCertification = {
        id: Date.now(), // Χρήση timestamp ως μοναδικό αναγνωριστικό
        title: titleInput.value,
        provider: providerInput.value,
        category: categorySelect.value,
        transport_type: document.getElementById('new_transport_type').value,
        date: dateInput.value,
        expiry: expiryInput.value,
        duration: durationInput.value ? parseInt(durationInput.value) : 0,
        description: descriptionInput.value,
        certificate_file: '' // Το αρχείο θα προστεθεί μετά την αποθήκευση από το server
    };

    // Προσθήκη στον πίνακα
    certifications.push(newCertification);

    // Ενημέρωση του πίνακα και των συνόλων
    updateCertificationsTable();
    updateTotalPoints();

    // Καθαρισμός της φόρμας
    titleInput.value = '';
    providerInput.value = '';
    categorySelect.value = '';
    dateInput.value = '';
    expiryInput.value = '';
    durationInput.value = '';
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
}

// Διαγραφή πιστοποιητικού
function deleteCertification(id) {
    // Αφαίρεση από τον πίνακα
    certifications = certifications.filter(cert => cert.id !== id);

    // Ενημέρωση του πίνακα και των συνόλων
    updateCertificationsTable();
    updateTotalPoints();

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
}

// Ενημέρωση του πίνακα πιστοποιητικών
function updateCertificationsTable() {
    const tbody = document.getElementById('certifications-tbody');
    
    if (!tbody) {
        console.error('certifications-tbody element not found');
        return;
    }
    
    tbody.innerHTML = '';

    if (certifications.length === 0) {
        const tr = document.createElement('tr');
        tr.innerHTML = '<td colspan="6" class="text-center">Δεν έχουν καταχωρηθεί πιστοποιητικά</td>';
        tbody.appendChild(tr);
        return;
    }

    certifications.forEach((cert, index) => {
        const tr = document.createElement('tr');

        // Εύρεση του ονόματος της κατηγορίας
        const categoryName = categoryNames[cert.category] || cert.category || 'Μη καθορισμένο';
        
        // Εύρεση του ονόματος του τύπου μεταφοράς
        const transportTypeName = transportTypeNames[cert.transport_type] || 'Εμπορευματικές & Επιβατικές';
        
        // Υπολογισμός των βαθμών
        const points = categoryPoints[cert.category] || 0;

        // Μορφοποίηση ημερομηνιών
        const formattedDate = cert.date ? new Date(cert.date).toLocaleDateString('el-GR') : '-';
        const formattedExpiry = cert.expiry ? new Date(cert.expiry).toLocaleDateString('el-GR') : '-';
        
        // Προσθήκη στοιχείου προεπισκόπησης αρχείου αν υπάρχει
        let filePreview = '-';
        if (cert.certificate_file) {
            const fileExt = cert.certificate_file.split('.').pop().toLowerCase();
            if (['jpg', 'jpeg', 'png'].includes(fileExt)) {
                filePreview = `<a href="${BASE_URL}uploads/certificates/${cert.certificate_file}" target="_blank" class="file-preview">
                    <img src="${BASE_URL}uploads/certificates/${cert.certificate_file}" alt="Προεπισκόπηση" width="30" height="30">
                    <span>Προβολή</span>
                </a>`;
            } else if (fileExt === 'pdf') {
                filePreview = `<a href="${BASE_URL}uploads/certificates/${cert.certificate_file}" target="_blank" class="file-preview">
                    <i class="fas fa-file-pdf" style="font-size: 24px; color: #dc3545;"></i>
                    <span>Προβολή PDF</span>
                </a>`;
            } else {
                filePreview = `<a href="${BASE_URL}uploads/certificates/${cert.certificate_file}" target="_blank" class="file-preview">
                    <i class="fas fa-file" style="font-size: 24px;"></i>
                    <span>Προβολή αρχείου</span>
                </a>`;
            }
        }
        
        // Περικοπή περιγραφής αν είναι πολύ μεγάλη
        const shortDescription = cert.description ? 
            (cert.description.length > 50 ? cert.description.substring(0, 50) + '...' : cert.description) : 
            '-';

        tr.innerHTML = `
            <td>${index + 1}</td>
            <td>${cert.title}</td>
            <td>${categoryName}</td>
            <td>${transportTypeName}</td>
            <td>${formattedDate}</td>
            <td>${formattedExpiry}</td>
            <td>${cert.duration || '-'}</td>
            <td>${filePreview}</td>
            <td title="${cert.description || ''}">${shortDescription}</td>
            <td>${points}</td>
            <td>
                <button type="button" class="edit-certification" data-id="${cert.id}" title="Επεξεργασία">
                    <i class="fas fa-edit"></i>
                </button>
                <button type="button" class="btn-delete-certification" data-id="${cert.id}" title="Διαγραφή">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);
    });

    // Προσθήκη event listeners για τα κουμπιά διαγραφής και επεξεργασίας
    document.querySelectorAll('.btn-delete-certification').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = parseInt(this.getAttribute('data-id'));
            deleteCertification(id);
        });
    });
    
    document.querySelectorAll('.edit-certification').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = parseInt(this.getAttribute('data-id'));
            editCertification(id);
        });
    });
}

// Υπολογισμός και ενημέρωση των συνολικών βαθμών
function updateTotalPoints() {
    let freightTotal = 0;
    let passengerTotal = 0;
    let grandTotal = 0;
    
    certifications.forEach(cert => {
        const points = categoryPoints[cert.category] || 0;
        
        if (cert.transport_type === 'freight' || cert.transport_type === 'both') {
            freightTotal += points;
        }
        
        if (cert.transport_type === 'passenger' || cert.transport_type === 'both') {
            passengerTotal += points;
        }
        
        // Για το συνολικό άθροισμα, μετράμε κάθε πιστοποιητικό μόνο μία φορά
        grandTotal += points;
    });
    
    // Ενημέρωση των στοιχείων στον πίνακα
    document.getElementById('freight-total-points').textContent = freightTotal;
    document.getElementById('passenger-total-points').textContent = passengerTotal;
    document.getElementById('grand-total-points').textContent = grandTotal;
}

// Ενημέρωση των κρυφών πεδίων για αποθήκευση
function updateHiddenFields() {
    const container = document.getElementById('certifications-data');
    
    if (!container) {
        console.error('certifications-data element not found');
        return;
    }
    
    // Καθαρισμός του container
    container.innerHTML = '';
    
    // Δημιουργία κρυφών πεδίων για κάθε εγγραφή
    certifications.forEach((cert, index) => {
        const fields = `
            <input type="hidden" name="certifications[${index}][title]" value="${cert.title || ''}">
            <input type="hidden" name="certifications[${index}][provider]" value="${cert.provider || ''}">
            <input type="hidden" name="certifications[${index}][category]" value="${cert.category || ''}">
            <input type="hidden" name="certifications[${index}][transport_type]" value="${cert.transport_type || 'both'}">
            <input type="hidden" name="certifications[${index}][date]" value="${cert.date || ''}">
            <input type="hidden" name="certifications[${index}][expiry]" value="${cert.expiry || ''}">
            <input type="hidden" name="certifications[${index}][duration]" value="${cert.duration || 0}">
            <input type="hidden" name="certifications[${index}][description]" value="${cert.description || ''}">
            <input type="hidden" name="certifications[${index}][certificate_file]" value="${cert.certificate_file || ''}">
        `;
        
        container.innerHTML += fields;
    });
}

// Φόρτωση των υπαρχόντων δεδομένων πιστοποιητικών
function loadExistingCertifications() {
    // Έλεγχος αν υπάρχουν δεδομένα στο window.initialCertifications
    if (window.initialCertifications && window.initialCertifications.length > 0) {
        console.log('Φόρτωση δεδομένων από window.initialCertifications');
        
        // Αντιγραφή των δεδομένων στον πίνακα certifications
        certifications = window.initialCertifications.map(cert => {
            return {
                id: cert.id || Date.now() + Math.floor(Math.random() * 1000),
                title: cert.title || '',
                provider: cert.provider || '',
                category: cert.category || '',
                transport_type: cert.transport_type || 'both',
                date: cert.date || '',
                expiry: cert.expiry || '',
                duration: parseInt(cert.duration || 0),
                description: cert.description || '',
                certificate_file: cert.certificate_file || ''
            };
        });
        
        // Ενημέρωση του πίνακα
        updateCertificationsTable();
        
        return true;
    }
    
    // Έλεγχος αν υπάρχουν ήδη δεδομένα πιστοποιητικών στα κρυφά πεδία
    const existingCertifications = document.querySelectorAll('input[type="hidden"][name^="certifications"]');
    
    if (existingCertifications.length === 0) {
        console.log('Δεν βρέθηκαν δεδομένα πιστοποιητικών');
        return false;
    }
    
    console.log('Φόρτωση δεδομένων από κρυφά πεδία');
    
    // Δημιουργία ενός αντικειμένου για την αποθήκευση των δεδομένων
    const certificationsData = {};
    
    // Συλλογή των δεδομένων από τα κρυφά πεδία
    existingCertifications.forEach(function(field) {
        const name = field.name;
        const value = field.value;
        
        // Εξαγωγή του δείκτη και του ονόματος του πεδίου από το όνομα του πεδίου
        const matches = name.match(/certifications\[(\d+)\]\[([^\]]+)\]/);
        
        if (matches) {
            const index = matches[1];
            const fieldName = matches[2];
            
            // Δημιουργία του αντικειμένου για τον δείκτη αν δεν υπάρχει
            if (!certificationsData[index]) {
                certificationsData[index] = {};
            }
            
            // Αποθήκευση της τιμής
            certificationsData[index][fieldName] = value;
        }
    });
    
    // Μετατροπή του αντικειμένου σε πίνακα
    const certificationsArray = Object.values(certificationsData);
    
    // Προσθήκη του id σε κάθε εγγραφή
    certifications = certificationsArray.map((cert, index) => {
        return {
                id: Date.now() + index, // Χρήση timestamp + index ως μοναδικό αναγνωριστικό
                title: cert.title || '',
                provider: cert.provider || '',
                category: cert.category || '',
                transport_type: cert.transport_type || 'both',
                date: cert.date || '',
                expiry: cert.expiry || '',
                duration: parseInt(cert.duration || 0),
                description: cert.description || '',
                certificate_file: cert.certificate_file || ''
        };
    });
    
    // Ενημέρωση του πίνακα και των συνόλων
    updateCertificationsTable();
    updateTotalPoints();
    
    return true;
}

// Έλεγχος της φόρμας πριν την υποβολή
function validateForm(form) {
    // Έλεγχος αν υπάρχουν δεδομένα πιστοποιητικών
    const hiddenFields = document.querySelectorAll('input[type="hidden"][name^="certifications"]');
    
    if (hiddenFields.length === 0) {
        // Έλεγχος αν υπάρχουν συμπληρωμένα πεδία στη φόρμα
        const title = document.getElementById('new_title').value;
        
        if (title) {
            // Αν υπάρχουν συμπληρωμένα πεδία, προσθέτουμε τα δεδομένα στον πίνακα
            if (addCertification()) {
                return true;
            }
            return false;
        }
        
        // Αν δεν υπάρχουν συμπληρωμένα πεδία και δεν υπάρχουν εγγραφές πιστοποιητικών,
        // ρωτάμε τον χρήστη αν θέλει να συνεχίσει
        return confirm('Δεν έχετε προσθέσει κανένα πιστοποιητικό εκπαίδευσης. Αν συνεχίσετε, όλα τα υπάρχοντα πιστοποιητικά θα διαγραφούν. Θέλετε να συνεχίσετε;');
    }
    
    return true;
}

// Επεξεργασία πιστοποιητικού
function editCertification(id) {
    // Εύρεση του πιστοποιητικού με το συγκεκριμένο id
    const certification = certifications.find(cert => cert.id === id);
    
    if (!certification) {
        console.error('Δεν βρέθηκε πιστοποιητικό με id:', id);
        return;
    }
    
    // Συμπλήρωση της φόρμας με τα δεδομένα του πιστοποιητικού
    document.getElementById('new_title').value = certification.title || '';
    document.getElementById('new_provider').value = certification.provider || '';
    document.getElementById('new_category').value = certification.category || '';
    document.getElementById('new_transport_type').value = certification.transport_type || 'both';
    document.getElementById('new_date').value = certification.date || '';
    document.getElementById('new_expiry').value = certification.expiry || '';
    document.getElementById('new_duration').value = certification.duration || '';
    document.getElementById('new_description').value = certification.description || '';
    
    // Αλλαγή του κουμπιού προσθήκης σε κουμπί ενημέρωσης
    const addButton = document.getElementById('btn-add-certification');
    if (addButton) {
        addButton.textContent = 'Ενημέρωση Πιστοποιητικού';
        addButton.dataset.editId = id;
        addButton.dataset.action = 'update';
        
        // Αλλαγή του event listener
        addButton.removeEventListener('click', addCertification);
        addButton.addEventListener('click', function() {
            updateCertification(id);
        });
    }
    
    // Κύλιση στην κορυφή της φόρμας
    document.querySelector('.certification-form').scrollIntoView({ behavior: 'smooth' });
}

// Ενημέρωση πιστοποιητικού
function updateCertification(id) {
    // Εύρεση του πιστοποιητικού με το συγκεκριμένο id
    const index = certifications.findIndex(cert => cert.id === id);
    
    if (index === -1) {
        console.error('Δεν βρέθηκε πιστοποιητικό με id:', id);
        return;
    }
    
    // Λήψη των τιμών από τη φόρμα
    const titleInput = document.getElementById('new_title');
    const providerInput = document.getElementById('new_provider');
    const categorySelect = document.getElementById('new_category');
    const transportTypeSelect = document.getElementById('new_transport_type');
    const dateInput = document.getElementById('new_date');
    const expiryInput = document.getElementById('new_expiry');
    const durationInput = document.getElementById('new_duration');
    const descriptionInput = document.getElementById('new_description');
    
    // Έλεγχος υποχρεωτικών πεδίων
    if (!titleInput.value) {
        alert('Παρακαλώ συμπληρώστε τον τίτλο του πιστοποιητικού');
        titleInput.focus();
        return false;
    }
    
    if (!providerInput.value) {
        alert('Παρακαλώ συμπληρώστε τον πάροχο του πιστοποιητικού');
        providerInput.focus();
        return false;
    }
    
    if (!categorySelect.value) {
        alert('Παρακαλώ επιλέξτε θεματολογία για το πιστοποιητικό');
        categorySelect.focus();
        return false;
    }
    
    if (!dateInput.value) {
        alert('Παρακαλώ συμπληρώστε την ημερομηνία απόκτησης του πιστοποιητικού');
        dateInput.focus();
        return false;
    }
    
    if (!durationInput.value || parseInt(durationInput.value) <= 0) {
        alert('Παρακαλώ συμπληρώστε τη διάρκεια του πιστοποιητικού σε ώρες');
        durationInput.focus();
        return false;
    }
    
    // Ενημέρωση του πιστοποιητικού
    certifications[index] = {
        id: id,
        title: titleInput.value,
        provider: providerInput.value,
        category: categorySelect.value,
        transport_type: transportTypeSelect.value,
        date: dateInput.value,
        expiry: expiryInput.value,
        duration: durationInput.value ? parseInt(durationInput.value) : 0,
        description: descriptionInput.value,
        certificate_file: certifications[index].certificate_file || '' // Διατήρηση του υπάρχοντος αρχείου
    };
    
    // Ενημέρωση του πίνακα και των συνόλων
    updateCertificationsTable();
    updateTotalPoints();
    
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
    
    // Επαναφορά του κουμπιού προσθήκης
    const addButton = document.getElementById('btn-add-certification');
    if (addButton) {
        addButton.textContent = 'Προσθήκη Πιστοποιητικού';
        delete addButton.dataset.editId;
        delete addButton.dataset.action;
        
        // Επαναφορά του event listener
        addButton.removeEventListener('click', function() {
            updateCertification(id);
        });
        addButton.addEventListener('click', addCertification);
    }
    
    // Καθαρισμός της φόρμας
    titleInput.value = '';
    providerInput.value = '';
    categorySelect.value = '';
    transportTypeSelect.value = 'both';
    dateInput.value = '';
    expiryInput.value = '';
    durationInput.value = '';
    descriptionInput.value = '';
    
    return true;
}

// Αρχικοποίηση των event listeners
document.addEventListener('DOMContentLoaded', function() {
    console.log('Αρχικοποίηση certifications.js');
    
    // Φόρτωση των υπαρχόντων δεδομένων
    loadExistingCertifications();
    
    // Ενημέρωση των συνόλων
    updateTotalPoints();
    
    // Event listener για το κουμπί προσθήκης
    const addButton = document.getElementById('btn-add-certification');
    if (addButton) {
        addButton.addEventListener('click', addCertification);
    }
    
    // Event listener για τη φόρμα
    const form = document.getElementById('certificationsForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                return false;
            }
        });
    }
});
