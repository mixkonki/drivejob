/**
 * Driver Profile JavaScript
 * Χειρισμός λειτουργικότητας για τη σελίδα προφίλ οδηγού
 */

document.addEventListener('DOMContentLoaded', function() {
    // Χειρισμός καρτελών (tabs)
    initTabs();
    
    // Χειρισμός διαγραφής πιστοποιήσεων
    initCertificateDeletion();
    
    // Χειρισμός διαγραφής εμπειρίας οχημάτων
    initVehicleExperienceDeletion();
    
    // Χειρισμός προβολής χάρτη
    initMap();
});

/**
 * Αρχικοποίηση λειτουργίας καρτελών
 */
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
            
            // Αποθήκευση της επιλεγμένης καρτέλας στο localStorage
            localStorage.setItem('selectedDriverProfileTab', tabId);
        });
    });
    
    // Έλεγχος αν υπάρχει αποθηκευμένη επιλογή καρτέλας
    const savedTab = localStorage.getItem('selectedDriverProfileTab');
    if (savedTab) {
        const savedTabButton = document.querySelector(`.tab-btn[data-tab="${savedTab}"]`);
        if (savedTabButton) {
            savedTabButton.click();
        }
    }
    
    // Έλεγχος αν υπάρχει παράμετρος tab στο URL
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    if (tabParam) {
        const tabButton = document.querySelector(`.tab-btn[data-tab="${tabParam}"]`);
        if (tabButton) {
            tabButton.click();
        }
    }
}

/**
 * Αρχικοποίηση λειτουργίας διαγραφής πιστοποιήσεων
 */
function initCertificateDeletion() {
    const deleteCertButtons = document.querySelectorAll('.delete-certification');
    
    deleteCertButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (confirm('Είστε σίγουροι ότι θέλετε να διαγράψετε αυτή την πιστοποίηση;')) {
                const certId = this.getAttribute('data-cert-id');
                
                // Αποστολή αιτήματος AJAX για διαγραφή
                fetch(`${BASE_URL}drivers/delete-certification/${certId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Αφαίρεση του στοιχείου από το DOM
                        this.closest('.certification-item').remove();
                        
                        // Εμφάνιση μηνύματος επιτυχίας
                        showNotification('Η πιστοποίηση διαγράφηκε με επιτυχία', 'success');
                    } else {
                        showNotification('Σφάλμα κατά τη διαγραφή της πιστοποίησης', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Σφάλμα κατά τη διαγραφή της πιστοποίησης', 'error');
                });
            }
        });
    });
}

/**
 * Αρχικοποίηση λειτουργίας διαγραφής εμπειρίας οχημάτων
 */
function initVehicleExperienceDeletion() {
    const deleteExpButtons = document.querySelectorAll('.delete-vehicle-experience');
    
    deleteExpButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (confirm('Είστε σίγουροι ότι θέλετε να διαγράψετε αυτή την εμπειρία οχήματος;')) {
                const expId = this.getAttribute('data-exp-id');
                
                // Αποστολή αιτήματος AJAX για διαγραφή
                fetch(`${BASE_URL}drivers/delete-vehicle-experience/${expId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Αφαίρεση του στοιχείου από το DOM
                        this.closest('.vehicle-experience-item').remove();
                        
                        // Εμφάνιση μηνύματος επιτυχίας
                        showNotification('Η εμπειρία οχήματος διαγράφηκε με επιτυχία', 'success');
                    } else {
                        showNotification('Σφάλμα κατά τη διαγραφή της εμπειρίας οχήματος', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Σφάλμα κατά τη διαγραφή της εμπειρίας οχήματος', 'error');
                });
            }
        });
    });
}

/**
 * Αρχικοποίηση χάρτη Google Maps
 */
function initMap() {
    // Έλεγχος αν υπάρχει στοιχείο χάρτη
    const mapElement = document.querySelector('.profile-map iframe');
    if (!mapElement) return;
    
    // Ο χάρτης έχει ήδη αρχικοποιηθεί μέσω του iframe
}

/**
 * Εμφάνιση ειδοποίησης
 * @param {string} message - Το μήνυμα της ειδοποίησης
 * @param {string} type - Ο τύπος της ειδοποίησης (success, error, warning, info)
 */
function showNotification(message, type = 'info') {
    // Έλεγχος αν υπάρχει ήδη στοιχείο ειδοποίησης
    let notification = document.querySelector('.notification');
    
    // Αν δεν υπάρχει, δημιουργία νέου
    if (!notification) {
        notification = document.createElement('div');
        notification.className = 'notification';
        document.body.appendChild(notification);
    }
    
    // Προσθήκη κλάσης τύπου και μηνύματος
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    // Εμφάνιση ειδοποίησης
    notification.style.display = 'block';
    
    // Αυτόματη απόκρυψη μετά από 3 δευτερόλεπτα
    setTimeout(() => {
        notification.style.display = 'none';
    }, 3000);
}
