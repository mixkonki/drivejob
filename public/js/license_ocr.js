/**
 * Επεξεργάζεται το κείμενο από την εμπρόσθια όψη του διπλώματος
 * 
 * @param {string} text - Το κείμενο που εξήχθη από το OCR
 */
function processFrontSideText(text) {
    console.log("Επεξεργασία εμπρόσθιας όψης...");
    
    // Εξαγωγή αριθμού άδειας (πεδίο 5)
    let licenseNumber = extractLicenseNumber(text);
    if (licenseNumber) {
        console.log("Αριθμός άδειας:", licenseNumber);
        document.getElementById('license_number').value = licenseNumber;
    }
    
    // Εξαγωγή ημερομηνίας λήξης εντύπου (πεδίο 4β)
    let documentExpiry = extractDocumentExpiry(text);
    if (documentExpiry) {
        console.log("Ημερομηνία λήξης εντύπου:", documentExpiry);
        document.getElementById('license_document_expiry').value = documentExpiry;
    }
    
    alert('Ολοκληρώθηκε η επεξεργασία της εμπρόσθιας όψης του διπλώματος.\n' + 
          (licenseNumber ? 'Αριθμός άδειας: ' + licenseNumber + '\n' : '') + 
          (documentExpiry ? 'Ημερομηνία λήξης: ' + documentExpiry : ''));
}

/**
 * Επεξεργάζεται το κείμενο από την οπίσθια όψη του διπλώματος
 * 
 * @param {string} text - Το κείμενο που εξήχθη από το OCR
 */
function processBackSideText(text) {
    console.log("Επεξεργασία οπίσθιας όψης...");
    
    // Εξαγωγή κωδικών περιορισμών από τη στήλη 12
    let licenseCodes = extractLicenseCodes(text);
    if (licenseCodes) {
        console.log("Κωδικοί:", licenseCodes);
        document.getElementById('license_codes').value = licenseCodes;
    }
    
    // Εξαγωγή κατηγοριών αδειών και ημερομηνιών λήξης
    let categories = extractLicenseCategories(text);
    console.log("Εντοπίστηκαν κατηγορίες:", categories);
    
    if (categories && categories.length > 0) {
        // Επιλογή των αντίστοιχων checkboxes και συμπλήρωση ημερομηνιών
        categories.forEach(cat => {
            // Εύρεση του checkbox για τη συγκεκριμένη κατηγορία
            const checkbox = document.querySelector(`input[name="license_types[]"][value="${cat.type}"]`);
            if (checkbox) {
                console.log(`Ενεργοποίηση κατηγορίας ${cat.type}`);
                checkbox.checked = true;
                
                // Πυροδότηση του event change για να ενεργοποιηθούν τα σχετικά πεδία
                const event = new Event('change');
                checkbox.dispatchEvent(event);
                
                // Συμπλήρωση της ημερομηνίας λήξης
                const dateField = document.querySelector(`input[name="license_expiry[${cat.type}]"]`);
                if (dateField && cat.expiry) {
                    console.log(`Ημερομηνία λήξης για ${cat.type}: ${cat.expiry}`);
                    dateField.value = cat.expiry;
                    
                    // Ενημέρωση των συγχρονισμένων πεδίων
                    const changeEvent = new Event('change');
                    dateField.dispatchEvent(changeEvent);
                }
            } else {
                console.log(`Δεν βρέθηκε το checkbox για την κατηγορία ${cat.type}`);
            }
        });
    }
    
    let message = 'Ολοκληρώθηκε η επεξεργασία της οπίσθιας όψης του διπλώματος.\n';
    if (licenseCodes) {
        message += 'Κωδικοί: ' + licenseCodes + '\n';
    }
    if (categories && categories.length > 0) {
        message += 'Κατηγορίες που εντοπίστηκαν: ' + categories.map(cat => cat.type).join(', ');
    } else {
        message += 'Δεν εντοπίστηκαν κατηγορίες αδειών.';
    }
    
    alert(message);
}

/**
 * Εξάγει τον αριθμό της άδειας οδήγησης από το κείμενο OCR
 * 
 * @param {string} text - Το κείμενο που εξήχθη από το OCR
 * @return {string|null} Ο αριθμός άδειας ή null αν δεν βρέθηκε
 */
function extractLicenseNumber(text) {
    console.log("Αναζήτηση αριθμού άδειας...");
    
    // Διάφορα πρότυπα αναζήτησης για να καλύψουμε διαφορετικές μορφές
    const patterns = [
        /(?:ΑΡΙΘ\s*ΑΔΕΙΑΣ|5\s*\.)[^\d]*(\d{6,10})/i,
        /(?:5\.)[^\d]*([A-Z0-9]{5,10})/i,
        /(?:ΑΡΙΘΜΟΣ ΑΔΕΙΑΣ)[^\d]*([A-Z0-9]{5,10})/i
    ];
    
    for (const regex of patterns) {
        const match = text.match(regex);
        if (match) {
            return match[1];
        }
    }
    
    return null;
}

/**
 * Εξάγει την ημερομηνία λήξης του εντύπου από το κείμενο OCR
 * 
 * @param {string} text - Το κείμενο που εξήχθη από το OCR
 * @return {string|null} Η ημερομηνία σε μορφή YYYY-MM-DD ή null αν δεν βρέθηκε
 */
function extractDocumentExpiry(text) {
    console.log("Αναζήτηση ημερομηνίας λήξης εντύπου...");
    
    // Διάφορα πρότυπα για διαφορετικές μορφές ημερομηνίας
    const patterns = [
        /(?:4β|ΛΗΞΗ)[^\d]*(\d{2})[\/\.\-](\d{2})[\/\.\-](\d{4})/i,
        /(?:4β|ΛΗΞΗ)[^\d]*(\d{1,2})[\s\/\.\-](\d{1,2})[\s\/\.\-](\d{4})/i,
        /(?:ΗΜΕΡΟΜΗΝΙΑ ΛΗΞΗΣ)[^\d]*(\d{1,2})[\s\/\.\-](\d{1,2})[\s\/\.\-](\d{4})/i
    ];
    
    for (const regex of patterns) {
        const match = text.match(regex);
        if (match) {
            const day = match[1].padStart(2, '0');
            const month = match[2].padStart(2, '0');
            const year = match[3];
            return `${year}-${month}-${day}`;
        }
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
    console.log("Αναζήτηση κωδικών...");
    
    // Διάφορα πρότυπα για τους κωδικούς
    const patterns = [
        /(?:12|ΚΩΔΙΚΟΙ)[^\d]*([\d\.,]+)/i,
        /(?:12\.)[^\d]*([\d\.,]+)/i,
        /(?:ΚΩΔΙΚΟΙ ΠΕΡΙΟΡΙΣΜΩΝ)[^\d]*([\d\.,]+)/i
    ];
    
    for (const regex of patterns) {
        const match = text.match(regex);
        if (match) {
            return match[1];
        }
    }
    
    return null;
}

/**
 * Εξάγει τις κατηγορίες αδειών και τις ημερομηνίες λήξης
 * 
 * @param {string} text - Το κείμενο που εξήχθη από το OCR
 * @return {Array|null} Πίνακας με τις κατηγορίες και τις ημερομηνίες λήξης
 */
function extractLicenseCategories(text) {
    console.log("Αναζήτηση κατηγοριών αδειών...");
    
    const categories = [];
    
    // Τυπικές κατηγορίες για αναζήτηση
    const typesToSearch = ['AM', 'A1', 'A2', 'A', 'B', 'BE', 'C1', 'C1E', 'C', 'CE', 'D1', 'D1E', 'D', 'DE'];
    
    // Έλεγχος για κάθε κατηγορία στο κείμενο
    typesToSearch.forEach(type => {
        // Διάφορα πρότυπα για την εύρεση ημερομηνιών
        const patterns = [
            // Πρότυπο για "B 01.01.2030" ή "B 01/01/2030"
            new RegExp(`${type}\\s+(\\d{2})[/\\.-](\\d{2})[/\\.-](\\d{4})`, 'i'),
            // Πρότυπο για "B έως 01.01.2030"
            new RegExp(`${type}[^\\d]*(?:έως|μέχρι|λήξη)[^\\d]*(\\d{2})[/\\.-](\\d{2})[/\\.-](\\d{4})`, 'i'),
            // Πρότυπο για κατηγορία και ημερομηνία σε διαφορετικές γραμμές
            new RegExp(`${type}[^\\n]*\\n[^\\d]*(\\d{2})[/\\.-](\\d{2})[/\\.-](\\d{4})`, 'i')
        ];
        
        // Έλεγχος κάθε προτύπου
        for (const regex of patterns) {
            const match = text.match(regex);
            if (match) {
                const day = match[1].padStart(2, '0');
                const month = match[2].padStart(2, '0');
                const year = match[3];
                const expiryDate = `${year}-${month}-${day}`;
                
                categories.push({
                    type: type,
                    expiry: expiryDate
                });
                
                // Αν βρέθηκε ταίριασμα, δεν χρειάζεται να ελέγξουμε τα υπόλοιπα πρότυπα
                break;
            }
        }
    });
    
    return categories.length > 0 ? categories : null;
}