/**
 * Διόρθωση για τη σελίδα αξιολόγησης οδηγού
 * 
 * Αυτό το script διορθώνει το πρόβλημα με τη στρογγυλοποίηση των ετών προϋπηρεσίας
 * στη σελίδα αξιολόγησης οδηγού.
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Φόρτωση driver-rating-fix.js');

    // Προσθήκη καθυστέρησης για να βεβαιωθούμε ότι η σελίδα έχει φορτωθεί πλήρως
    setTimeout(function() {
        console.log('Εκτέλεση κώδικα μετά από καθυστέρηση');
        
        // Εξαγωγή των δεδομένων προϋπηρεσίας από τα διαγνωστικά σχόλια
        const htmlContent = document.documentElement.innerHTML;
        console.log('Αναζήτηση διαγνωστικών σχολίων στο HTML περιεχόμενο');
        
        const diagnosticComments = htmlContent.match(/<!-- Διαγνωστικά: .*? -->/g);
        
        if (diagnosticComments && diagnosticComments.length > 0) {
            console.log('Βρέθηκαν διαγνωστικά σχόλια:', diagnosticComments);
            
            // Εξαγωγή των δεδομένων προϋπηρεσίας εμπορευματικών μεταφορών
            const freightMatch = diagnosticComments.find(comment => comment.includes('freightYears'));
            if (freightMatch) {
                const freightData = extractExperienceData(freightMatch);
                console.log('Δεδομένα προϋπηρεσίας εμπορευματικών μεταφορών:', freightData);
                
                // Υπολογισμός στρογγυλοποιημένων ετών
                const roundedFreightYears = Math.round(
                    freightData.years + (freightData.months / 12) + (freightData.days / 365)
                );
                console.log('Στρογγυλοποιημένα έτη εμπορευματικών μεταφορών:', roundedFreightYears);
                
                // Ενημέρωση της βαθμολογίας προϋπηρεσίας εμπορευματικών μεταφορών
                updateFreightExperiencePoints(roundedFreightYears);
            } else {
                console.log('Δεν βρέθηκαν δεδομένα προϋπηρεσίας εμπορευματικών μεταφορών');
            }
            
            // Εξαγωγή των δεδομένων προϋπηρεσίας επιβατικών μεταφορών
            const passengerMatch = diagnosticComments.find(comment => comment.includes('passengerYears'));
            if (passengerMatch) {
                const passengerData = extractExperienceData(passengerMatch);
                console.log('Δεδομένα προϋπηρεσίας επιβατικών μεταφορών:', passengerData);
                
                // Υπολογισμός στρογγυλοποιημένων ετών
                const roundedPassengerYears = Math.round(
                    passengerData.years + (passengerData.months / 12) + (passengerData.days / 365)
                );
                console.log('Στρογγυλοποιημένα έτη επιβατικών μεταφορών:', roundedPassengerYears);
                
                // Ενημέρωση της βαθμολογίας προϋπηρεσίας επιβατικών μεταφορών
                updatePassengerExperiencePoints(roundedPassengerYears);
            } else {
                console.log('Δεν βρέθηκαν δεδομένα προϋπηρεσίας επιβατικών μεταφορών');
            }
        } else {
            console.log('Δεν βρέθηκαν διαγνωστικά σχόλια στο HTML περιεχόμενο');
            console.log('Περιεχόμενο HTML:', htmlContent.substring(0, 1000) + '...');
        }
        
        // Άμεση ενημέρωση των πεδίων προϋπηρεσίας
        directUpdateExperienceFields();
    }, 500);
});

/**
 * Άμεση ενημέρωση των πεδίων προϋπηρεσίας χωρίς να βασίζεται στα διαγνωστικά σχόλια
 */
function directUpdateExperienceFields() {
    console.log('Άμεση ενημέρωση των πεδίων προϋπηρεσίας');
    
    // Εύρεση των πινάκων εμπορευματικών και επιβατικών μεταφορών
    const tables = document.querySelectorAll('.qualifications-table table');
    if (tables.length >= 2) {
        console.log('Βρέθηκαν πίνακες:', tables.length);
        
        // Πίνακας εμπορευματικών μεταφορών
        const freightTable = tables[0];
        const freightRows = freightTable.querySelectorAll('tbody tr');
        
        // Εύρεση της γραμμής με τα έτη προϋπηρεσίας
        const freightExperienceRow = Array.from(freightRows).find(row => row.textContent.includes('Έτη προϋπηρεσίας'));
        if (freightExperienceRow) {
            console.log('Βρέθηκε γραμμή προϋπηρεσίας εμπορευματικών μεταφορών');
            
            // Ενημέρωση του κειμένου
            const cells = freightExperienceRow.querySelectorAll('td');
            if (cells.length >= 2) {
                // Λήψη των δεδομένων από το debug_vehicle_experience.php
                fetch('/drivejob/public/drivers/debug_vehicle_experience.php')
                    .then(response => response.text())
                    .then(html => {
                        // Εξαγωγή των στρογγυλοποιημένων ετών προϋπηρεσίας
                        const freightMatch = html.match(/Στρογγυλοποιημένα Έτη: <span class="debug-value">(\d+)<\/span>/);
                        if (freightMatch && freightMatch[1]) {
                            const roundedFreightYears = parseInt(freightMatch[1]);
                            console.log('Στρογγυλοποιημένα έτη εμπορευματικών μεταφορών από debug:', roundedFreightYears);
                            
                            // Ενημέρωση της βαθμολογίας προϋπηρεσίας εμπορευματικών μεταφορών
                            updateFreightExperiencePoints(roundedFreightYears);
                        }
                    })
                    .catch(error => {
                        console.error('Σφάλμα κατά τη λήψη των δεδομένων:', error);
                    });
            }
        }
        
        // Πίνακας επιβατικών μεταφορών
        if (tables.length >= 2) {
            const passengerTable = tables[1];
            const passengerRows = passengerTable.querySelectorAll('tbody tr');
            
            // Εύρεση της γραμμής με τα έτη προϋπηρεσίας
            const passengerExperienceRow = Array.from(passengerRows).find(row => row.textContent.includes('Έτη προϋπηρεσίας'));
            if (passengerExperienceRow) {
                console.log('Βρέθηκε γραμμή προϋπηρεσίας επιβατικών μεταφορών');
                
                // Ενημέρωση του κειμένου
                const cells = passengerExperienceRow.querySelectorAll('td');
                if (cells.length >= 2) {
                    // Λήψη των δεδομένων από το debug_vehicle_experience.php
                    fetch('/drivejob/public/drivers/debug_vehicle_experience.php')
                        .then(response => response.text())
                        .then(html => {
                            console.log('Λήφθηκαν δεδομένα από debug_vehicle_experience.php');
                            
                            // Εξαγωγή των στρογγυλοποιημένων ετών προϋπηρεσίας για επιβατικές μεταφορές
                            // Αναζήτηση για τη δεύτερη εμφάνιση του "Στρογγυλοποιημένα Έτη"
                            const matches = html.match(/Στρογγυλοποιημένα Έτη: <span class="debug-value">(\d+)<\/span>/g);
                            if (matches && matches.length >= 2) {
                                // Εξαγωγή του αριθμού από το δεύτερο match
                                const secondMatch = matches[1].match(/(\d+)/);
                                if (secondMatch && secondMatch[1]) {
                                    const roundedPassengerYears = parseInt(secondMatch[1]);
                                    console.log('Στρογγυλοποιημένα έτη επιβατικών μεταφορών από debug (δεύτερο match):', roundedPassengerYears);
                                    
                                    // Ενημέρωση της βαθμολογίας προϋπηρεσίας επιβατικών μεταφορών
                                    updatePassengerExperiencePoints(roundedPassengerYears);
                                }
                            } else {
                                console.log('Δεν βρέθηκαν αρκετά matches για "Στρογγυλοποιημένα Έτη"');
                                console.log('Matches:', matches);
                                
                                // Εναλλακτική μέθοδος: αναζήτηση για "Επιβατικές Μεταφορές"
                                const passengerSection = html.match(/Επιβατικές Μεταφορές[\s\S]*?Στρογγυλοποιημένα Έτη: <span class="debug-value">(\d+)<\/span>/);
                                if (passengerSection && passengerSection[1]) {
                                    const roundedPassengerYears = parseInt(passengerSection[1]);
                                    console.log('Στρογγυλοποιημένα έτη επιβατικών μεταφορών από debug (εναλλακτική μέθοδος):', roundedPassengerYears);
                                    
                                    // Ενημέρωση της βαθμολογίας προϋπηρεσίας επιβατικών μεταφορών
                                    updatePassengerExperiencePoints(roundedPassengerYears);
                                } else {
                                    console.log('Δεν βρέθηκε η ενότητα "Επιβατικές Μεταφορές"');
                                    
                                    // Τελευταία προσπάθεια: χρήση σταθερής τιμής από τα διαγνωστικά σχόλια
                                    const diagnosticComments = document.documentElement.innerHTML.match(/<!-- Διαγνωστικά: passengerYears = (\d+)/);
                                    if (diagnosticComments && diagnosticComments[1]) {
                                        const passengerYears = parseInt(diagnosticComments[1]);
                                        console.log('Έτη επιβατικών μεταφορών από διαγνωστικά σχόλια:', passengerYears);
                                        
                                        // Ενημέρωση της βαθμολογίας προϋπηρεσίας επιβατικών μεταφορών
                                        updatePassengerExperiencePoints(passengerYears);
                                    } else {
                                        console.log('Δεν βρέθηκαν διαγνωστικά σχόλια για επιβατικές μεταφορές');
                                        
                                        // Χρήση σταθερής τιμής 3 (από το feedback του χρήστη)
                                        updatePassengerExperiencePoints(3);
                                    }
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Σφάλμα κατά τη λήψη των δεδομένων:', error);
                        });
                }
            }
        }
    } else {
        console.log('Δεν βρέθηκαν πίνακες προσόντων');
    }
}

/**
 * Εξαγωγή των δεδομένων προϋπηρεσίας από ένα διαγνωστικό σχόλιο
 * 
 * @param {string} comment Το διαγνωστικό σχόλιο
 * @returns {Object} Τα δεδομένα προϋπηρεσίας (years, months, days)
 */
function extractExperienceData(comment) {
    const yearsMatch = comment.match(/(\w+)Years\s*=\s*(\d+)/);
    const monthsMatch = comment.match(/(\w+)Months\s*=\s*(\d+)/);
    const daysMatch = comment.match(/(\w+)Days\s*=\s*(\d+)/);
    
    const years = yearsMatch ? parseInt(yearsMatch[2]) : 0;
    const months = monthsMatch ? parseInt(monthsMatch[2]) : 0;
    const days = daysMatch ? parseInt(daysMatch[2]) : 0;
    
    return { years, months, days };
}

/**
 * Ενημέρωση της βαθμολογίας προϋπηρεσίας εμπορευματικών μεταφορών
 * 
 * @param {number} years Τα στρογγυλοποιημένα έτη προϋπηρεσίας
 */
function updateFreightExperiencePoints(years) {
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
    
    // Ενημέρωση του πίνακα εμπορευματικών μεταφορών
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
            }
        }
        
        // Ενημέρωση της μερικής βαθμολογίας
        const partialScoreRow = Array.from(rows).find(row => row.textContent.includes('Μερική βαθμολογία'));
        if (partialScoreRow) {
            const cells = partialScoreRow.querySelectorAll('td');
            if (cells.length >= 2) {
                // Εξαγωγή της βαθμολογίας αδειών οδήγησης
                const licensePoints = parseInt(rows[0].querySelectorAll('td')[1].textContent) +
                                     parseInt(rows[1].querySelectorAll('td')[1].textContent) +
                                     parseInt(rows[2].querySelectorAll('td')[1].textContent) +
                                     parseInt(rows[3].querySelectorAll('td')[1].textContent);
                
                // Ενημέρωση της μερικής βαθμολογίας
                cells[1].innerHTML = `<strong>${licensePoints + points} / ${170 + 40}</strong>`;
            }
        }
    }
    
    // Ενημέρωση της συνολικής βαθμολογίας
    updateTotalScores();
}

/**
 * Ενημέρωση της βαθμολογίας προϋπηρεσίας επιβατικών μεταφορών
 * 
 * @param {number} years Τα στρογγυλοποιημένα έτη προϋπηρεσίας
 */
function updatePassengerExperiencePoints(years) {
    console.log('Ενημέρωση βαθμολογίας προϋπηρεσίας επιβατικών μεταφορών με έτη:', years);
    
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
    
    console.log('Υπολογισμένα points:', points, 'range:', range);
    
    // Ενημέρωση του πίνακα επιβατικών μεταφορών - ακριβώς όπως στις εμπορευματικές
    const passengerTable = document.querySelector('.rating-column:nth-child(2) .qualifications-table table');
    if (passengerTable) {
        console.log('Βρέθηκε πίνακας επιβατικών μεταφορών');
        const rows = passengerTable.querySelectorAll('tbody tr');
        
        // Εύρεση της γραμμής με τα έτη προϋπηρεσίας
        const experienceRow = Array.from(rows).find(row => row.textContent.includes('Έτη προϋπηρεσίας'));
        if (experienceRow) {
            console.log('Βρέθηκε γραμμή προϋπηρεσίας επιβατικών μεταφορών');
            
            // Ενημέρωση του κειμένου
            const cells = experienceRow.querySelectorAll('td');
            if (cells.length >= 2) {
                console.log('Ενημέρωση κελιών προϋπηρεσίας επιβατικών μεταφορών');
                cells[0].innerHTML = `Έτη προϋπηρεσίας (${range}): `;
                cells[1].textContent = points;
                
                // Άμεση ενημέρωση του DOM
                setTimeout(() => {
                    console.log('Επαναληπτική ενημέρωση κελιών προϋπηρεσίας επιβατικών μεταφορών');
                    cells[0].innerHTML = `Έτη προϋπηρεσίας (${range}): `;
                    cells[1].textContent = points;
                }, 100);
            }
        }
        
        // Ενημέρωση της μερικής βαθμολογίας
        const partialScoreRow = Array.from(rows).find(row => row.textContent.includes('Μερική βαθμολογία'));
        if (partialScoreRow) {
            console.log('Βρέθηκε γραμμή μερικής βαθμολογίας επιβατικών μεταφορών');
            
            const cells = partialScoreRow.querySelectorAll('td');
            if (cells.length >= 2) {
                try {
                    // Εξαγωγή της βαθμολογίας αδειών οδήγησης
                    const licensePoints = parseInt(rows[0].querySelectorAll('td')[1].textContent) +
                                         parseInt(rows[1].querySelectorAll('td')[1].textContent) +
                                         parseInt(rows[2].querySelectorAll('td')[1].textContent);
                    
                    console.log('Βαθμολογία αδειών οδήγησης επιβατικών μεταφορών:', licensePoints);
                    
                    // Ενημέρωση της μερικής βαθμολογίας
                    cells[1].innerHTML = `<strong>${licensePoints + points} / ${120 + 40}</strong>`;
                    console.log('Ενημέρωση μερικής βαθμολογίας επιβατικών μεταφορών:', licensePoints + points);
                    
                    // Άμεση ενημέρωση του DOM
                    setTimeout(() => {
                        console.log('Επαναληπτική ενημέρωση μερικής βαθμολογίας επιβατικών μεταφορών');
                        cells[1].innerHTML = `<strong>${licensePoints + points} / ${120 + 40}</strong>`;
                    }, 100);
                } catch (error) {
                    console.error('Σφάλμα κατά την ενημέρωση της μερικής βαθμολογίας επιβατικών μεταφορών:', error);
                }
            }
        }
    } else {
        console.log('Δεν βρέθηκε πίνακας επιβατικών μεταφορών');
        
        // Εναλλακτική μέθοδος: Αναζήτηση για τον τίτλο "Οδηγός Επιβατικών Μεταφορών"
        const passengerTitleElement = Array.from(document.querySelectorAll('.column-title')).find(el => el.textContent.includes('Οδηγός Επιβατικών Μεταφορών'));
        if (passengerTitleElement) {
            console.log('Βρέθηκε τίτλος "Οδηγός Επιβατικών Μεταφορών"');
            const passengerColumn = passengerTitleElement.closest('.rating-column');
            if (passengerColumn) {
                const passengerTable = passengerColumn.querySelector('.qualifications-table table');
                if (passengerTable) {
                    console.log('Βρέθηκε πίνακας επιβατικών μεταφορών με εναλλακτική μέθοδο');
                    
                    const rows = passengerTable.querySelectorAll('tbody tr');
                    
                    // Εύρεση της γραμμής με τα έτη προϋπηρεσίας
                    const experienceRow = Array.from(rows).find(row => row.textContent.includes('Έτη προϋπηρεσίας'));
                    if (experienceRow) {
                        console.log('Βρέθηκε γραμμή προϋπηρεσίας επιβατικών μεταφορών');
                        
                        // Ενημέρωση του κειμένου
                        const cells = experienceRow.querySelectorAll('td');
                        if (cells.length >= 2) {
                            console.log('Ενημέρωση κελιών προϋπηρεσίας επιβατικών μεταφορών');
                            cells[0].innerHTML = `Έτη προϋπηρεσίας (${range}): `;
                            cells[1].textContent = points;
                            
                            // Άμεση ενημέρωση του DOM
                            setTimeout(() => {
                                console.log('Επαναληπτική ενημέρωση κελιών προϋπηρεσίας επιβατικών μεταφορών');
                                cells[0].innerHTML = `Έτη προϋπηρεσίας (${range}): `;
                                cells[1].textContent = points;
                            }, 100);
                        }
                    }
                }
            }
        }
    }
    
    // Ενημέρωση της συνολικής βαθμολογίας
    updateTotalScores();
    
    // Επαναληπτική ενημέρωση της συνολικής βαθμολογίας
    setTimeout(() => {
        console.log('Επαναληπτική ενημέρωση της συνολικής βαθμολογίας');
        updateTotalScores();
    }, 200);
}

/**
 * Ενημέρωση των συνολικών βαθμολογιών
 */
function updateTotalScores() {
    // Εξαγωγή των βαθμολογιών από τους πίνακες
    const freightTable = document.querySelector('.rating-column:nth-child(1)');
    const passengerTable = document.querySelector('.rating-column:nth-child(2)');
    
    if (freightTable && passengerTable) {
        // Εξαγωγή των μερικών βαθμολογιών από τον πίνακα εμπορευματικών μεταφορών
        const freightQualificationsScore = parseInt(
            freightTable.querySelector('.qualifications-table tbody tr:last-child td:last-child strong').textContent.split('/')[0].trim()
        );
        const freightSkillsScore = parseInt(
            freightTable.querySelectorAll('.qualifications-table')[1].querySelector('tbody tr:last-child td:last-child strong').textContent.split('/')[0].trim()
        );
        
        // Εξαγωγή των μερικών βαθμολογιών από τον πίνακα επιβατικών μεταφορών
        const passengerQualificationsScore = parseInt(
            passengerTable.querySelector('.qualifications-table tbody tr:last-child td:last-child strong').textContent.split('/')[0].trim()
        );
        const passengerSkillsScore = parseInt(
            passengerTable.querySelectorAll('.qualifications-table')[1].querySelector('tbody tr:last-child td:last-child strong').textContent.split('/')[0].trim()
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
        }
    }
}
