/**
 * Ενημέρωση των πεδίων προϋπηρεσίας στη σελίδα επεξεργασίας προφίλ οδηγού
 * 
 * Αυτό το script ενημερώνει τα πεδία προϋπηρεσίας στη σελίδα επεξεργασίας προφίλ οδηγού
 * με βάση τα δεδομένα που λαμβάνονται από το debug_vehicle_experience.php.
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Φόρτωση update-profile-experience.js');
    
    // Έλεγχος αν βρισκόμαστε στη σελίδα επεξεργασίας προφίλ
    const experienceDisplay = document.querySelector('.experience-display');
    if (!experienceDisplay) {
        console.log('Δεν βρέθηκε το στοιχείο experience-display');
        return;
    }
    
    // Εύρεση των στηλών προϋπηρεσίας
    const freightColumn = experienceDisplay.querySelector('.experience-column:nth-child(2) div:last-child');
    const passengerColumn = experienceDisplay.querySelector('.experience-column:nth-child(3) div:last-child');
    
    if (!freightColumn || !passengerColumn) {
        console.log('Δεν βρέθηκαν οι στήλες προϋπηρεσίας');
        return;
    }
    
    // Κάνουμε αίτημα στο debug_vehicle_experience.php για να λάβουμε τις τιμές προϋπηρεσίας
    fetch('/drivejob/public/drivers/debug_vehicle_experience.php')
        .then(response => {
            console.log(`Απάντηση από server: ${response.status} ${response.statusText}`);
            return response.text();
        })
        .then(html => {
            console.log('Λήφθηκαν δεδομένα από debug_vehicle_experience.php');
            
            // Εξαγωγή των στρογγυλοποιημένων ετών προϋπηρεσίας για εμπορευματικές μεταφορές
            const freightMatch = html.match(/Εμπορευματικές Μεταφορές[\s\S]*?Στρογγυλοποιημένα Έτη: <span class="debug-value">(\d+)<\/span>/);
            if (freightMatch && freightMatch[1]) {
                const roundedFreightYears = parseInt(freightMatch[1]);
                console.log(`Στρογγυλοποιημένα έτη εμπορευματικών μεταφορών: ${roundedFreightYears}`);
                
                // Ενημέρωση του πεδίου προϋπηρεσίας εμπορευματικών μεταφορών
                freightColumn.textContent = `${roundedFreightYears} έτη`;
                freightColumn.style.color = '#28a745';
            } else {
                console.log('Δεν βρέθηκαν δεδομένα προϋπηρεσίας εμπορευματικών μεταφορών');
            }
            
            // Εξαγωγή των στρογγυλοποιημένων ετών προϋπηρεσίας για επιβατικές μεταφορές
            const passengerMatch = html.match(/Επιβατικές Μεταφορές[\s\S]*?Στρογγυλοποιημένα Έτη: <span class="debug-value">(\d+)<\/span>/);
            if (passengerMatch && passengerMatch[1]) {
                const roundedPassengerYears = parseInt(passengerMatch[1]);
                console.log(`Στρογγυλοποιημένα έτη επιβατικών μεταφορών: ${roundedPassengerYears}`);
                
                // Ενημέρωση του πεδίου προϋπηρεσίας επιβατικών μεταφορών
                passengerColumn.textContent = `${roundedPassengerYears} έτη`;
                passengerColumn.style.color = '#dc3545';
            } else {
                console.log('Δεν βρέθηκαν δεδομένα προϋπηρεσίας επιβατικών μεταφορών');
            }
        })
        .catch(error => {
            console.error('Σφάλμα κατά τη λήψη των δεδομένων:', error);
        });
});
