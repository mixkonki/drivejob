/**
 * Ενημέρωση των πεδίων προϋπηρεσίας στη σελίδα αξιολόγησης οδηγού
 * 
 * Αυτό το script ενημερώνει τα πεδία προϋπηρεσίας στη σελίδα αξιολόγησης οδηγού
 * με βάση τα δεδομένα που λαμβάνονται από το debug_vehicle_experience.php.
 */

// Σταθερές για τα κλειδιά του localStorage
const FREIGHT_YEARS_KEY = 'drivejob_freight_years';
const PASSENGER_YEARS_KEY = 'drivejob_passenger_years';
const LAST_UPDATE_KEY = 'drivejob_last_update';

// Προσθήκη μηνύματος στο console log
function logDebug(message) {
    console.log(message);
}

// Άμεση ενημέρωση των πεδίων προϋπηρεσίας
function updateExperienceFields() {
    logDebug('Άμεση ενημέρωση των πεδίων προϋπηρεσίας...');
    
    // Έλεγχος αν υπάρχουν αποθηκευμένες τιμές στο localStorage
    const storedFreightYears = localStorage.getItem(FREIGHT_YEARS_KEY);
    const storedPassengerYears = localStorage.getItem(PASSENGER_YEARS_KEY);
    
    if (storedFreightYears) {
        const freightYears = parseInt(storedFreightYears);
        logDebug(`Ενημέρωση εμπορευματικών μεταφορών: ${freightYears} έτη`);
        updateFreightExperienceField(freightYears);
    }
    
    if (storedPassengerYears) {
        const passengerYears = parseInt(storedPassengerYears);
        logDebug(`Ενημέρωση επιβατικών μεταφορών: ${passengerYears} έτη`);
        updatePassengerExperienceField(passengerYears);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    logDebug('Φόρτωση driver-rating-update.js');
    
    // Έλεγχος αν υπάρχουν αποθηκευμένες τιμές στο localStorage
    const storedFreightYears = localStorage.getItem(FREIGHT_YEARS_KEY);
    const storedPassengerYears = localStorage.getItem(PASSENGER_YEARS_KEY);
    const lastUpdate = localStorage.getItem(LAST_UPDATE_KEY);
    
    // Υπολογισμός της διαφοράς χρόνου από την τελευταία ενημέρωση (σε λεπτά)
    const now = new Date();
    const timeDiff = lastUpdate ? (now - new Date(lastUpdate)) / (1000 * 60) : 999;
    
    logDebug(`Αποθηκευμένες τιμές: freight=${storedFreightYears}, passenger=${storedPassengerYears}, lastUpdate=${lastUpdate ? Math.round(timeDiff) + ' λεπτά πριν' : 'ποτέ'}`);
    
    // Άμεση ενημέρωση των πεδίων με τις αποθηκευμένες τιμές
    if (storedFreightYears && storedPassengerYears) {
        logDebug('Χρήση αποθηκευμένων τιμών από το localStorage');
        
        // Χρήση setTimeout για να δώσουμε χρόνο στη σελίδα να φορτώσει πλήρως
        setTimeout(updateExperienceFields, 500);
    }
    
    // Σε κάθε περίπτωση, κάνουμε αίτημα στο debug_vehicle_experience.php για να λάβουμε τις πιο πρόσφατες τιμές
    logDebug('Αίτημα στο debug_vehicle_experience.php...');
    
    // Χρήση setTimeout για να δώσουμε χρόνο στη σελίδα να φορτώσει πλήρως
    setTimeout(function() {
        fetch('/drivejob/public/drivers/debug_vehicle_experience.php')
            .then(response => {
                logDebug(`Απάντηση από server: ${response.status} ${response.statusText}`);
                return response.text();
            })
            .then(html => {
                logDebug('Λήφθηκαν δεδομένα από debug_vehicle_experience.php');
                
                // Εξαγωγή των στρογγυλοποιημένων ετών προϋπηρεσίας για εμπορευματικές μεταφορές
                const freightMatch = html.match(/Εμπορευματικές Μεταφορές[\s\S]*?Στρογγυλοποιημένα Έτη: <span class="debug-value">(\d+)<\/span>/);
                if (freightMatch && freightMatch[1]) {
                    const roundedFreightYears = parseInt(freightMatch[1]);
                    logDebug(`Στρογγυλοποιημένα έτη εμπορευματικών μεταφορών: ${roundedFreightYears}`);
                    
                    // Αποθήκευση της τιμής στο localStorage
                    localStorage.setItem(FREIGHT_YEARS_KEY, roundedFreightYears.toString());
                    
                    // Ενημέρωση του πεδίου προϋπηρεσίας εμπορευματικών μεταφορών
                    updateFreightExperienceField(roundedFreightYears);
                } else {
                    logDebug('Δεν βρέθηκαν δεδομένα προϋπηρεσίας εμπορευματικών μεταφορών');
                }
                
                // Εξαγωγή των στρογγυλοποιημένων ετών προϋπηρεσίας για επιβατικές μεταφορές
                const passengerMatch = html.match(/Επιβατικές Μεταφορές[\s\S]*?Στρογγυλοποιημένα Έτη: <span class="debug-value">(\d+)<\/span>/);
                if (passengerMatch && passengerMatch[1]) {
                    const roundedPassengerYears = parseInt(passengerMatch[1]);
                    logDebug(`Στρογγυλοποιημένα έτη επιβατικών μεταφορών: ${roundedPassengerYears}`);
                    
                    // Αποθήκευση της τιμής στο localStorage
                    localStorage.setItem(PASSENGER_YEARS_KEY, roundedPassengerYears.toString());
                    
                    // Ενημέρωση του πεδίου προϋπηρεσίας επιβατικών μεταφορών
                    updatePassengerExperienceField(roundedPassengerYears);
                } else {
                    logDebug('Δεν βρέθηκαν δεδομένα προϋπηρεσίας επιβατικών μεταφορών');
                }
                
                // Αποθήκευση της τρέχουσας ημερομηνίας/ώρας ως τελευταία ενημέρωση
                localStorage.setItem(LAST_UPDATE_KEY, now.toISOString());
            })
            .catch(error => {
                console.error('Σφάλμα κατά τη λήψη των δεδομένων:', error);
                logDebug(`Σφάλμα κατά τη λήψη των δεδομένων: ${error.message}`);
                
                // Αν υπάρχουν αποθηκευμένες τιμές και δεν έχουν χρησιμοποιηθεί ήδη,
                // τις χρησιμοποιούμε ως εφεδρική λύση
                if (storedFreightYears && storedPassengerYears) {
                    logDebug('Χρήση αποθηκευμένων τιμών ως εφεδρική λύση');
                    updateExperienceFields();
                }
            });
    }, 1000);
    
    // Προσθήκη event listener για το beforeunload event
    window.addEventListener('beforeunload', function() {
        logDebug('Αποθήκευση τιμών πριν την ανανέωση της σελίδας');
        
        // Αποθήκευση των τρεχουσών τιμών στο localStorage πριν την ανανέωση της σελίδας
        const freightTable = document.querySelector('.rating-column:nth-child(1) .qualifications-table table');
        const passengerTable = document.querySelector('.rating-column:nth-child(2) .qualifications-table table');
        
        if (freightTable && passengerTable) {
            const freightRows = freightTable.querySelectorAll('tbody tr');
            const passengerRows = passengerTable.querySelectorAll('tbody tr');
            
            // Εύρεση της γραμμής με τα έτη προϋπηρεσίας
            const freightExperienceRow = Array.from(freightRows).find(row => row.textContent.includes('Έτη προϋπηρεσίας'));
            const passengerExperienceRow = Array.from(passengerRows).find(row => row.textContent.includes('Έτη προϋπηρεσίας'));
            
            if (freightExperienceRow && passengerExperienceRow) {
                const freightPoints = parseInt(freightExperienceRow.querySelectorAll('td')[1].textContent);
                const passengerPoints = parseInt(passengerExperienceRow.querySelectorAll('td')[1].textContent);
                
                // Αποθήκευση των τιμών στο localStorage
                if (!isNaN(freightPoints)) {
                    localStorage.setItem(FREIGHT_YEARS_KEY, getYearsFromPoints(freightPoints).toString());
                }
                
                if (!isNaN(passengerPoints)) {
                    localStorage.setItem(PASSENGER_YEARS_KEY, getYearsFromPoints(passengerPoints).toString());
                }
                
                // Αποθήκευση της τρέχουσας ημερομηνίας/ώρας ως τελευταία ενημέρωση
                localStorage.setItem(LAST_UPDATE_KEY, new Date().toISOString());
            }
        }
    });
    
    // Προσθήκη event listener για το DOMNodeInserted event
    document.addEventListener('DOMNodeInserted', function(e) {
        // Έλεγχος αν το νέο στοιχείο είναι ο πίνακας εμπορευματικών ή επιβατικών μεταφορών
        if (e.target && e.target.classList && 
            (e.target.classList.contains('rating-column') || 
             e.target.classList.contains('qualifications-table'))) {
            logDebug('Εντοπίστηκε νέο στοιχείο στη σελίδα, ενημέρωση πεδίων...');
            setTimeout(updateExperienceFields, 100);
        }
    });
});

/**
 * Μετατροπή των βαθμών προϋπηρεσίας σε έτη
 * 
 * @param {number} points Οι βαθμοί προϋπηρεσίας
 * @return {number} Τα έτη προϋπηρεσίας
 */
function getYearsFromPoints(points) {
    switch (points) {
        case 0: return 1; // 0-1 έτος
        case 10: return 3; // 2-3 έτη
        case 20: return 5; // 4-5 έτη
        case 30: return 8; // 6-8 έτη
        case 40: return 9; // 9+ έτη
        default: return 0;
    }
}

/**
 * Ενημέρωση του πεδίου προϋπηρεσίας εμπορευματικών μεταφορών
 * 
 * @param {number} years Τα στρογγυλοποιημένα έτη προϋπηρεσίας
 */
function updateFreightExperienceField(years) {
    let points = 0;
    let range = "";
    
    if (years <= 1) {
        points = 0;
        range = "0-1 έτος";
    } else if (years <= 3) {
        points = 10;
        range = "2-3 έτη";
    } else if (years <= 5) {
        points = 20;
        range = "4-5 έτη";
    } else if (years <= 8) {
        points = 30;
        range = "6-8 έτη";
    } else {
        points = 40;
        range = "9+ έτη";
    }
    
    logDebug(`Εμπορευματικές μεταφορές - Έτη: ${years}, Βαθμοί: ${points}, Εύρος: ${range}`);
    
    // Μέθοδος 1: Εύρεση του πίνακα εμπορευματικών μεταφορών με βάση τη θέση του
    const freightTable = document.querySelector('.rating-column:nth-child(1) .qualifications-table table');
    if (freightTable) {
        const rows = freightTable.querySelectorAll('tbody tr');
        
        // Εύρεση της γραμμής με τα έτη προϋπηρεσίας
        const experienceRow = Array.from(rows).find(row => row.textContent.includes('Έτη προϋπηρεσίας'));
        if (experienceRow) {
            // Ενημέρωση του κειμένου
            const cells = experienceRow.querySelectorAll('td');
            if (cells.length >= 2) {
                cells[0].innerHTML = `Έτη προϋπηρεσίας (${range}): `;
                cells[1].textContent = points;
                logDebug('Ενημερώθηκε το πεδίο προϋπηρεσίας εμπορευματικών μεταφορών (Μέθοδος 1)');
                
                // Ενημέρωση της μερικής βαθμολογίας
                updateFreightPartialScore(freightTable, points);
                return;
            }
        }
    }
    
    // Μέθοδος 2: Εύρεση του πίνακα εμπορευματικών μεταφορών με βάση τον τίτλο
    const freightTitle = Array.from(document.querySelectorAll('.column-title')).find(title => title.textContent.includes('Οδηγός Εμπορευματικών Μεταφορών'));
    if (freightTitle) {
        const freightColumn = freightTitle.closest('.rating-column');
        if (freightColumn) {
            const table = freightColumn.querySelector('.qualifications-table table');
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                
                // Εύρεση της γραμμής με τα έτη προϋπηρεσίας
                const experienceRow = Array.from(rows).find(row => row.textContent.includes('Έτη προϋπηρεσίας'));
                if (experienceRow) {
                    // Ενημέρωση του κειμένου
                    const cells = experienceRow.querySelectorAll('td');
                    if (cells.length >= 2) {
                        cells[0].innerHTML = `Έτη προϋπηρεσίας (${range}): `;
                        cells[1].textContent = points;
                        logDebug('Ενημερώθηκε το πεδίο προϋπηρεσίας εμπορευματικών μεταφορών (Μέθοδος 2)');
                        
                        // Ενημέρωση της μερικής βαθμολογίας
                        updateFreightPartialScore(table, points);
                        return;
                    }
                }
            }
        }
    }
    
    // Μέθοδος 3: Εύρεση όλων των πινάκων και έλεγχος για τα έτη προϋπηρεσίας
    const allTables = document.querySelectorAll('.qualifications-table table');
    for (let i = 0; i < allTables.length; i++) {
        const table = allTables[i];
        const rows = table.querySelectorAll('tbody tr');
        
        // Εύρεση της γραμμής με τα έτη προϋπηρεσίας
        const experienceRow = Array.from(rows).find(row => row.textContent.includes('Έτη προϋπηρεσίας'));
        if (experienceRow) {
            // Έλεγχος αν είναι ο πίνακας εμπορευματικών μεταφορών
            const isFreightTable = table.closest('.rating-column').querySelector('.column-title').textContent.includes('Εμπορευματικών');
            if (isFreightTable) {
                // Ενημέρωση του κειμένου
                const cells = experienceRow.querySelectorAll('td');
                if (cells.length >= 2) {
                    cells[0].innerHTML = `Έτη προϋπηρεσίας (${range}): `;
                    cells[1].textContent = points;
                    logDebug('Ενημερώθηκε το πεδίο προϋπηρεσίας εμπορευματικών μεταφορών (Μέθοδος 3)');
                    
                    // Ενημέρωση της μερικής βαθμολογίας
                    updateFreightPartialScore(table, points);
                    return;
                }
            }
        }
    }
    
    logDebug('Δεν βρέθηκε ο πίνακας εμπορευματικών μεταφορών');
}

/**
 * Ενημέρωση του πεδίου προϋπηρεσίας επιβατικών μεταφορών
 * 
 * @param {number} years Τα στρογγυλοποιημένα έτη προϋπηρεσίας
 */
function updatePassengerExperienceField(years) {
    let points = 0;
    let range = "";
    
    if (years <= 1) {
        points = 0;
        range = "0-1 έτος";
    } else if (years <= 3) {
        points = 10;
        range = "2-3 έτη";
    } else if (years <= 5) {
        points = 20;
        range = "4-5 έτη";
    } else if (years <= 8) {
        points = 30;
        range = "6-8 έτη";
    } else {
        points = 40;
        range = "9+ έτη";
    }
    
    logDebug(`Επιβατικές μεταφορές - Έτη: ${years}, Βαθμοί: ${points}, Εύρος: ${range}`);
    
    // Μέθοδος 1: Εύρεση του πίνακα επιβατικών μεταφορών με βάση τη θέση του
    const passengerTable = document.querySelector('.rating-column:nth-child(2) .qualifications-table table');
    if (passengerTable) {
        const rows = passengerTable.querySelectorAll('tbody tr');
        
        // Εύρεση της γραμμής με τα έτη προϋπηρεσίας
        const experienceRow = Array.from(rows).find(row => row.textContent.includes('Έτη προϋπηρεσίας'));
        if (experienceRow) {
            // Ενημέρωση του κειμένου
            const cells = experienceRow.querySelectorAll('td');
            if (cells.length >= 2) {
                cells[0].innerHTML = `Έτη προϋπηρεσίας (${range}): `;
                cells[1].textContent = points;
                logDebug('Ενημερώθηκε το πεδίο προϋπηρεσίας επιβατικών μεταφορών (Μέθοδος 1)');
                
                // Ενημέρωση της μερικής βαθμολογίας
                updatePassengerPartialScore(passengerTable, points);
                return;
            }
        }
    }
    
    // Μέθοδος 2: Εύρεση του πίνακα επιβατικών μεταφορών με βάση τον τίτλο
    const passengerTitle = Array.from(document.querySelectorAll('.column-title')).find(title => title.textContent.includes('Οδηγός Επιβατικών Μεταφορών'));
    if (passengerTitle) {
        const passengerColumn = passengerTitle.closest('.rating-column');
        if (passengerColumn) {
            const table = passengerColumn.querySelector('.qualifications-table table');
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                
                // Εύρεση της γραμμής με τα έτη προϋπηρεσίας
                const experienceRow = Array.from(rows).find(row => row.textContent.includes('Έτη προϋπηρεσίας'));
                if (experienceRow) {
                    // Ενημέρωση του κειμένου
                    const cells = experienceRow.querySelectorAll('td');
                    if (cells.length >= 2) {
                        cells[0].innerHTML = `Έτη προϋπηρεσίας (${range}): `;
                        cells[1].textContent = points;
                        logDebug('Ενημερώθηκε το πεδίο προϋπηρεσίας επιβατικών μεταφορών (Μέθοδος 2)');
                        
                        // Ενημέρωση της μερικής βαθμολογίας
                        updatePassengerPartialScore(table, points);
                        return;
                    }
                }
            }
        }
    }
    
    // Μέθοδος 3: Εύρεση όλων των πινάκων και έλεγχος για τα έτη προϋπηρεσίας
    const allTables = document.querySelectorAll('.qualifications-table table');
    for (let i = 0; i < allTables.length; i++) {
        const table = allTables[i];
        const rows = table.querySelectorAll('tbody tr');
        
        // Εύρεση της γραμμής με τα έτη προϋπηρεσίας
        const experienceRow = Array.from(rows).find(row => row.textContent.includes('Έτη προϋπηρεσίας'));
        if (experienceRow) {
            // Έλεγχος αν είναι ο πίνακας επιβατικών μεταφορών
            const isPassengerTable = table.closest('.rating-column').querySelector('.column-title').textContent.includes('Επιβατικών');
            if (isPassengerTable) {
                // Ενημέρωση του κειμένου
                const cells = experienceRow.querySelectorAll('td');
                if (cells.length >= 2) {
                    cells[0].innerHTML = `Έτη προϋπηρεσίας (${range}): `;
                    cells[1].textContent = points;
                    logDebug('Ενημερώθηκε το πεδίο προϋπηρεσίας επιβατικών μεταφορών (Μέθοδος 3)');
                    
                    // Ενημέρωση της μερικής βαθμολογίας
                    updatePassengerPartialScore(table, points);
                    return;
                }
            }
        }
    }
    
    logDebug('Δεν βρέθηκε ο πίνακας επιβατικών μεταφορών');
}

/**
 * Ενημέρωση της μερικής βαθμολογίας εμπορευματικών μεταφορών
 * 
 * @param {HTMLElement} table Ο πίνακας εμπορευματικών μεταφορών
 * @param {number} experiencePoints Οι βαθμοί προϋπηρεσίας
 */
function updateFreightPartialScore(table, experiencePoints) {
    const rows = table.querySelectorAll('tbody tr');
    
    // Εύρεση της γραμμής με τη μερική βαθμολογία
    const partialScoreRow = Array.from(rows).find(row => row.textContent.includes('Μερική βαθμολογία'));
    if (partialScoreRow) {
        const cells = partialScoreRow.querySelectorAll('td');
        if (cells.length >= 2) {
            try {
                // Εξαγωγή της βαθμολογίας αδειών οδήγησης
                const licensePoints = parseInt(rows[0].querySelectorAll('td')[1].textContent) +
                                     parseInt(rows[1].querySelectorAll('td')[1].textContent) +
                                     parseInt(rows[2].querySelectorAll('td')[1].textContent) +
                                     parseInt(rows[3].querySelectorAll('td')[1].textContent);
                
                // Ενημέρωση της μερικής βαθμολογίας
                cells[1].innerHTML = `<strong>${licensePoints + experiencePoints} / ${170 + 40}</strong>`;
                logDebug(`Ενημερώθηκε η μερική βαθμολογία εμπορευματικών μεταφορών: ${licensePoints + experiencePoints} / ${170 + 40}`);
            } catch (error) {
                logDebug(`Σφάλμα κατά την ενημέρωση της μερικής βαθμολογίας εμπορευματικών μεταφορών: ${error.message}`);
            }
        }
    }
    
    // Ενημέρωση της συνολικής βαθμολογίας
    setTimeout(updateTotalScores, 100);
}

/**
 * Ενημέρωση της μερικής βαθμολογίας επιβατικών μεταφορών
 * 
 * @param {HTMLElement} table Ο πίνακας επιβατικών μεταφορών
 * @param {number} experiencePoints Οι βαθμοί προϋπηρεσίας
 */
function updatePassengerPartialScore(table, experiencePoints) {
    const rows = table.querySelectorAll('tbody tr');
    
    // Εύρεση της γραμμής με τη μερική βαθμολογία
    const partialScoreRow = Array.from(rows).find(row => row.textContent.includes('Μερική βαθμολογία'));
    if (partialScoreRow) {
        const cells = partialScoreRow.querySelectorAll('td');
        if (cells.length >= 2) {
            try {
                // Εξαγωγή της βαθμολογίας αδειών οδήγησης
                const licensePoints = parseInt(rows[0].querySelectorAll('td')[1].textContent) +
                                     parseInt(rows[1].querySelectorAll('td')[1].textContent) +
                                     parseInt(rows[2].querySelectorAll('td')[1].textContent);
                
                // Ενημέρωση της μερικής βαθμολογίας
                cells[1].innerHTML = `<strong>${licensePoints + experiencePoints} / ${120 + 40}</strong>`;
                logDebug(`Ενημερώθηκε η μερική βαθμολογία επιβατικών μεταφορών: ${licensePoints + experiencePoints} / ${120 + 40}`);
            } catch (error) {
                logDebug(`Σφάλμα κατά την ενημέρωση της μερικής βαθμολογίας επιβατικών μεταφορών: ${error.message}`);
            }
        }
    }
    
    // Ενημέρωση της συνολικής βαθμολογίας
    setTimeout(updateTotalScores, 100);
}

/**
 * Ενημέρωση των συνολικών βαθμολογιών
 */
function updateTotalScores() {
    try {
        // Εξαγωγή των βαθμολογιών από τους πίνακες
        const freightTable = document.querySelector('.rating-column:nth-child(1)');
        const passengerTable = document.querySelector('.rating-column:nth-child(2)');
        
        if (freightTable && passengerTable) {
            // Εξαγωγή των μερικών βαθμολογιών από τον πίνακα εμπορευματικών μεταφορών
            const freightQualificationsTable = freightTable.querySelector('.qualifications-table');
            const freightSkillsTable = freightTable.querySelectorAll('.qualifications-table')[1];
            
            // Εξαγωγή των μερικών βαθμολογιών από τον πίνακα επιβατικών μεταφορών
            const passengerQualificationsTable = passengerTable.querySelector('.qualifications-table');
            const passengerSkillsTable = passengerTable.querySelectorAll('.qualifications-table')[1];
            
            if (freightQualificationsTable && freightSkillsTable && passengerQualificationsTable && passengerSkillsTable) {
                // Εξαγωγή των μερικών βαθμολογιών
                const freightQualificationsScore = parseInt(
                    freightQualificationsTable.querySelector('tbody tr:last-child td:last-child strong').textContent.split('/')[0].trim()
                );
                const freightSkillsScore = parseInt(
                    freightSkillsTable.querySelector('tbody tr:last-child td:last-child strong').textContent.split('/')[0].trim()
                );
                const passengerQualificationsScore = parseInt(
                    passengerQualificationsTable.querySelector('tbody tr:last-child td:last-child strong').textContent.split('/')[0].trim()
                );
                const passengerSkillsScore = parseInt(
                    passengerSkillsTable.querySelector('tbody tr:last-child td:last-child strong').textContent.split('/')[0].trim()
                );
                
                // Υπολογισμός των συνολικών βαθμολογιών
                const freightTotalScore = freightQualificationsScore + freightSkillsScore;
                const passengerTotalScore = passengerQualificationsScore + passengerSkillsScore;
                
                // Ενημέρωση των συνολικών βαθμολογιών
                const freightTotalScoreElement = freightTable.querySelector('.column-title .total-score-label strong');
                const passengerTotalScoreElement = passengerTable.querySelector('.column-title .total-score-label strong');
                
                if (freightTotalScoreElement && passengerTotalScoreElement) {
                    // Εξαγωγή των μέγιστων βαθμολογιών
                    const freightMaxScore = parseInt(freightTotalScoreElement.textContent.split('/')[1]);
                    const passengerMaxScore = parseInt(passengerTotalScoreElement.textContent.split('/')[1]);
                    
                    // Ενημέρωση των συνολικών βαθμολογιών
                    freightTotalScoreElement.textContent = `${freightTotalScore}/${freightMaxScore}`;
                    passengerTotalScoreElement.textContent = `${passengerTotalScore}/${passengerMaxScore}`;
                    
                    logDebug(`Ενημερώθηκαν οι συνολικές βαθμολογίες: Εμπορευματικές=${freightTotalScore}/${freightMaxScore}, Επιβατικές=${passengerTotalScore}/${passengerMaxScore}`);
                }
            }
        }
    } catch (error) {
        logDebug(`Σφάλμα κατά την ενημέρωση των συνολικών βαθμολογιών: ${error.message}`);
    }
}
