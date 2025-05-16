/**
 * Κωδικοί χωρών για τηλέφωνα
 * 
 * Αυτό το αρχείο περιέχει τη λειτουργικότητα για την αυτόματη ενημέρωση
 * των κωδικών χώρας στα πεδία τηλεφώνου με βάση την επιλεγμένη χώρα.
 */

// Αντιστοίχιση κωδικών χωρών με κωδικούς τηλεφώνου
const countryCodes = {
    'GR': '+30', // Ελλάδα
    'CY': '+357', // Κύπρος
    'DE': '+49', // Γερμανία
    'FR': '+33', // Γαλλία
    'IT': '+39', // Ιταλία
    'ES': '+34', // Ισπανία
    'GB': '+44', // Ηνωμένο Βασίλειο
    'US': '+1', // Ηνωμένες Πολιτείες
    'CA': '+1', // Καναδάς
    'AU': '+61', // Αυστραλία
    'AT': '+43', // Αυστρία
    'BE': '+32', // Βέλγιο
    'BG': '+359', // Βουλγαρία
    'HR': '+385', // Κροατία
    'CZ': '+420', // Τσεχία
    'DK': '+45', // Δανία
    'EE': '+372', // Εσθονία
    'FI': '+358', // Φινλανδία
    'HU': '+36', // Ουγγαρία
    'IE': '+353', // Ιρλανδία
    'LV': '+371', // Λετονία
    'LT': '+370', // Λιθουανία
    'LU': '+352', // Λουξεμβούργο
    'MT': '+356', // Μάλτα
    'NL': '+31', // Ολλανδία
    'PL': '+48', // Πολωνία
    'PT': '+351', // Πορτογαλία
    'RO': '+40', // Ρουμανία
    'SK': '+421', // Σλοβακία
    'SI': '+386', // Σλοβενία
    'SE': '+46', // Σουηδία
    'CH': '+41', // Ελβετία
    'NO': '+47', // Νορβηγία
    'RS': '+381', // Σερβία
    'TR': '+90', // Τουρκία
};

document.addEventListener('DOMContentLoaded', function() {
    // Αναφορά στο πεδίο επιλογής χώρας
    const countrySelect = document.getElementById('country');
    
    // Αναφορά στα πεδία τηλεφώνου
    const phoneInput = document.getElementById('phone');
    const landlineInput = document.getElementById('landline');
    
    // Αρχικοποίηση των πεδίων τηλεφώνου με τον κωδικό της επιλεγμένης χώρας
    if (countrySelect && countrySelect.value && countryCodes[countrySelect.value]) {
        initializePhoneFields(countrySelect.value);
    }
    
    // Προσθήκη event listener για την αλλαγή της χώρας
    if (countrySelect) {
        countrySelect.addEventListener('change', function() {
            const countryCode = this.value;
            if (countryCode && countryCodes[countryCode]) {
                updatePhoneFields(countryCode);
            }
        });
    }
    
    // Αρχικοποίηση των πεδίων τηλεφώνου
    function initializePhoneFields(countryCode) {
        if (phoneInput && !phoneInput.value.startsWith('+')) {
            phoneInput.value = countryCodes[countryCode] + ' ' + phoneInput.value;
        }
        
        if (landlineInput && !landlineInput.value.startsWith('+')) {
            landlineInput.value = countryCodes[countryCode] + ' ' + landlineInput.value;
        }
    }
    
    // Ενημέρωση των πεδίων τηλεφώνου με τον νέο κωδικό χώρας
    function updatePhoneFields(countryCode) {
        const phoneCode = countryCodes[countryCode];
        
        if (phoneInput) {
            // Αφαίρεση του προηγούμενου κωδικού χώρας (αν υπάρχει)
            let phoneValue = phoneInput.value;
            if (phoneValue.startsWith('+')) {
                phoneValue = phoneValue.replace(/^\+\d+\s*/, '');
            }
            
            // Προσθήκη του νέου κωδικού χώρας
            phoneInput.value = phoneCode + ' ' + phoneValue;
        }
        
        if (landlineInput) {
            // Αφαίρεση του προηγούμενου κωδικού χώρας (αν υπάρχει)
            let landlineValue = landlineInput.value;
            if (landlineValue.startsWith('+')) {
                landlineValue = landlineValue.replace(/^\+\d+\s*/, '');
            }
            
            // Προσθήκη του νέου κωδικού χώρας
            landlineInput.value = phoneCode + ' ' + landlineValue;
        }
    }
});
