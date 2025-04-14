/**
 * Script για την υλοποίηση OCR με Tesseract.js για το σκανάρισμα διπλώματος οδήγησης
 * 
 * Αυτό το αρχείο θα πρέπει να προστεθεί στη σελίδα edit_profile.php
 * Θα χρειαστεί να συμπεριλάβετε το Tesseract.js από το CDN
 * 
 * <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
 */

// Όταν φορτώσει το DOM
document.addEventListener('DOMContentLoaded', function() {
    // Σύνδεση των κουμπιών σκαναρίσματος με τις αντίστοιχες λειτουργίες
    const scanFrontButton = document.getElementById('scan-license-front');
    const scanBackButton = document.getElementById('scan-license-back');
    
    if (scanFrontButton) {
        scanFrontButton.addEventListener('click', function() {
            scanLicenseSide('front');
        });
    }
    
    if (scanBackButton) {
        scanBackButton.addEventListener('click', function() {
            scanLicenseSide('back');
        });
    }
});

/**
 * Σκανάρει την εμπρόσθια ή οπίσθια όψη του διπλώματος
 * 
 * @param {string} side - 'front' για την εμπρόσθια όψη, 'back' για την οπίσθια
 */
function scanLicenseSide(side) {
    // Αναφορά στο πεδίο εισαγωγής αρχείου
    const inputId = (side === 'front') ? 'license_front_image' : 'license_back_image';
    const fileInput = document.getElementById(inputId);
    
    // Έλεγχος αν έχει επιλεγεί αρχείο
    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        alert('Παρακαλώ επιλέξτε πρώτα μια εικόνα του διπλώματος.');
        return;
    }
    
    // Εμφάνιση μηνύματος φόρτωσης
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'ocr-loading';
    loadingDiv.innerHTML = `
        <div class="spinner"></div>
        <p>Γίνεται σάρωση της εικόνας... Παρακαλώ περιμένετε.</p>
    `;
    document.body.appendChild(loadingDiv);
    
    // Μετατροπή του αρχείου σε URL
    const imageFile = fileInput.files[0];
    const imageUrl = URL.createObjectURL(imageFile);
    
    // Εκτέλεση του OCR με Tesseract.js
    Tesseract.recognize(
        imageUrl,
        'ell+eng', // Ελληνικά + Αγγλικά για καλύτερη αναγνώριση
        { 
            logger: m => console.log(m),
            // Εκπαίδευσε το μοντέλο για αναγνώριση εγγράφων αυτοκινήτου
            tessedit_ocr_engine_mode: '3',
            tessedit_char_whitelist: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789αβγδεζηθικλμνξοπρστυφχψω.-'
        }
    ).then(({ data: { text } }) => {
        console.log('OCR Result:', text);
        
        // Αφαίρεση του μηνύματος φόρτωσης
        document.body.removeChild(loadingDiv);
        
        // Επεξεργασία του κειμένου για εξαγωγή πληροφοριών
        if (side === 'front') {
            processFrontSideText(text);
        } else {
            processBackSideText(text);
        }
        
        // Απελευθέρωση της μνήμης
        URL.revokeObjectURL(imageUrl);
    }).catch(err => {
        console.error('OCR Error:', err);
        alert('Σφάλμα κατά το σκανάρισμα: ' + err.message);
        document.body.removeChild(loadingDiv);
        URL.revokeObjectURL(imageUrl);
    });
}

/**
 * Επεξεργάζεται το κείμενο από την εμπρόσθια όψη του διπλώματος
 * 
 * @param {string} text - Το κείμενο που εξήχθη από το OCR
 */
function processFrontSideText(text) {
    // Εξαγωγή αριθμού άδειας (πεδίο 5)
    let licenseNumber = extractLicenseNumber(text);
    if (licenseNumber) {
        document.getElementById('license_number').value = licenseNumber;
    }
    
    // Εξαγωγή ημερομηνίας λήξης εντύπου (πεδίο 4β)
    let documentExpiry = extractDocumentExpiry(text);
    if (documentExpiry) {
        document.getElementById('license_document_expiry').value = documentExpiry;
    }
    
    // Άλλες εξαγωγές...
    
    alert('Ολοκληρώθηκε η σάρωση της εμπρόσθιας όψης του διπλώματος.');
}

/**
 * Επεξεργάζεται το κείμενο από την οπίσθια όψη του διπλώματος
 * 
 * @param {string} text - Το κείμενο που εξήχθη από το OCR
 */
function processBackSideText(text) {
    // Εξαγωγή κωδικών περιορισμών από τη στήλη 12
    let licenseCodes = extractLicenseCodes(text);
    if (licenseCodes) {
        document.getElementById('license_codes').value = licenseCodes;
    }
    
    // Εξαγωγή κατηγοριών αδειών και ημερομηνιών λήξης
    let categories = extractLicenseCategories(text);
    if (categories && categories.length > 0) {
        // Επιλογή των αντίστοιχων checkboxes και συμπλήρωση ημερομηνιών
        categories.forEach(cat => {
            // Εύρεση του checkbox για τη συγκεκριμένη κατηγορία
            const checkbox = document.querySelector(`input[name="license_types[]"][value="${cat.type}"]`);
            if (checkbox) {
                checkbox.checked = true;
                
                // Συμπλήρωση της ημερομηνίας λήξης
                const dateField = document.querySelector(`input[name="license_expiry[${cat.type}]"]`);
                if (dateField && cat.expiry) {
                    dateField.value = cat.expiry;
                    
                    // Ενημέρωση των συγχρονισμένων πεδίων
                    const event = new Event('change');
                    dateField.dispatchEvent(event);
                }
            }
        });
    }
    
    alert('Ολοκληρώθηκε η σάρωση της οπίσθιας όψης του διπλώματος.');
}

/**
 * Εξάγει τον αριθμό της άδειας οδήγησης από το κείμενο OCR
 * 
 * @param {string} text - Το κείμενο που εξήχθη από το OCR
 * @return {string|null} Ο αριθμός άδειας ή null αν δεν βρέθηκε
 */
function extractLicenseNumber(text) {
    // Παράδειγμα αναγνώρισης με regex
    // Στην πραγματικότητα, χρειάζεται πιο προηγμένη αναγνώριση βάσει της μορφής του ελληνικού διπλώματος
    const regex = /(?:ΑΡΙΘ\s*ΑΔΕΙΑΣ|5\s*\.)[^\d]*(\d{6,10})/i;
    const match = text.match(regex);
    
    return match ? match[1] : null;
}

/**
 * Εξάγει την ημερομηνία λήξης του εντύπου από το κείμενο OCR
 * 
 * @param {string} text - Το κείμενο που εξήχθη από το OCR
 * @return {string|null} Η ημερομηνία σε μορφή YYYY-MM-DD ή null αν δεν βρέθηκε
 */
function extractDocumentExpiry(text) {
    // Παράδειγμα αναγνώρισης με regex
    // Αναζήτηση ημερομηνίας μετά το "4β" ή "ΛΗΞΗ"
    const regex = /(?:4β|ΛΗΞΗ)[^\d]*(\d{2})[\/\.\-](\d{2})[\/\.\-](\d{4})/i;
    const match = text.match(regex);
    
    if (match) {
        const day = match[1];
        const month = match[2];
        const year = match[3];
        return `${year}-${month}-${day}`;
    }
    
    return null;
}

/**
 * Εξάγει τους κωδικούς περιορισμών από το κείμενο OCR
 * 
 * @param {string} text - Το κείμενο που εξήχθη από το OCR
 * @return {string|null} Οι κωδικοί ή null αν δεν βρέθηκαν
 */
function extractLicenseCodes(text) {
    // Παράδειγμα αναγνώρισης κωδικών - στήλη 12
    const regex = /(?:12|ΚΩΔΙΚΟΙ)[^\d]*([0-9,.]+)/i;
    const match = text.match(regex);
    
    return match ? match[1] : null;
}

/**
 * Εξάγει τις κατηγορίες αδειών και τις ημερομηνίες λήξης
 * 
 * @param {string} text - Το κείμενο που εξήχθη από το OCR
 * @return {Array|null} Πίνακας με τις κατηγορίες και τις ημερομηνίες λήξης
 */
function extractLicenseCategories(text) {
    // Αυτή η συνάρτηση είναι πιο περίπλοκη και εξαρτάται από τη μορφή του διπλώματος
    // Παράδειγμα απλής αναγνώρισης
    const categories = [];
    
    // Τυπικές κατηγορίες για αναζήτηση
    const typesToSearch = ['AM', 'A1', 'A2', 'A', 'B', 'BE', 'C1', 'C1E', 'C', 'CE', 'D1', 'D1E', 'D', 'DE'];
    
    typesToSearch.forEach(type => {
        // Ψάχνουμε την κατηγορία ακολουθούμενη από ημερομηνία
        const regex = new RegExp(`${type}[^\\d]*(\\d{2})[/\\.-](\\d{2})[/\\.-](\\d{4})`, 'i');
        const match = text.match(regex);
        
        if (match) {
            const day = match[1];
            const month = match[2];
            const year = match[3];
            
            categories.push({
                type: type,
                expiry: `${year}-${month}-${day}`
            });
        }
    });
    
    return categories.length > 0 ? categories : null;
}

// Προσθήκη CSS για το spinner φόρτωσης
const style = document.createElement('style');
style.textContent = `
    .ocr-loading {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }
    
    .ocr-loading p {
        color: white;
        font-size: 18px;
        margin-top: 20px;
    }
    
    .spinner {
        border: 8px solid #f3f3f3;
        border-top: 8px solid #aa3636;
        border-radius: 50%;
        width: 60px;
        height: 60px;
        animation: spin 2s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
`;

document.head.appendChild(style);