window.onerror = function(message, source, lineno, colno, error) {
    // Καταγραφή σφάλματος στην κονσόλα
    console.error('JS ERROR:', message, 'at', source, 'line', lineno);
    
    // Αποστολή του σφάλματος στον server για καταγραφή
    fetch('log-error.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            message: message,
            source: source,
            lineno: lineno,
            colno: colno,
            stack: error ? error.stack : null
        })
    });
    
    return false; // Επιτρέπει την κανονική διαχείριση σφαλμάτων του προγράμματος περιήγησης
};