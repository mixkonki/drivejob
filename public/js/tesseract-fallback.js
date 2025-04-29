// Fallback wrapper για το Tesseract.js σε περίπτωση που το bundle δεν φορτωθεί σωστά
(function() {
    console.log('Έλεγχος για TesseractWrapper');
    
    // Ελέγχουμε αν το TesseractWrapper είναι διαθέσιμο και έχει τις απαραίτητες μεθόδους
    if (typeof window.TesseractWrapper === 'undefined' || 
        typeof window.TesseractWrapper.preprocessImage !== 'function') {
        
        console.warn('Χρήση fallback TesseractWrapper');
        
        // Δημιουργία fallback wrapper
        window.TesseractWrapper = {
            // Προ-επεξεργασία εικόνας
            preprocessImage: function(imageData) {
                return Promise.resolve(imageData);
            },
            
            // Αναγνώριση κειμένου - θα χρειαστεί το Tesseract.js να είναι φορτωμένο
            recognize: function(imageData, language, options) {
                if (typeof Tesseract !== 'undefined') {
                    return Tesseract.recognize(
                        imageData,
                        language || 'eng+ell',
                        options?.logger ? { logger: options.logger } : {}
                    );
                } else {
                    return Promise.reject(new Error('Το Tesseract.js δεν έχει φορτωθεί'));
                }
            },
            
            // Ανίχνευση γλώσσας/προσανατολισμού
            detect: function(imageData) {
                if (typeof Tesseract !== 'undefined') {
                    return Tesseract.detect(imageData);
                } else {
                    return Promise.reject(new Error('Το Tesseract.js δεν έχει φορτωθεί'));
                }
            }
        };
    } else {
        console.log('TesseractWrapper διαθέσιμο με preprocessImage:', 
                   typeof window.TesseractWrapper.preprocessImage);
    }
})();
// Fallback wrapper για το Tesseract.js
(function() {
    // Ελέγχουμε αν το TesseractWrapper είναι διαθέσιμο και έχει τις απαραίτητες μεθόδους
    setTimeout(() => {
        if (typeof window.TesseractWrapper === 'undefined' || 
            typeof window.TesseractWrapper.preprocessImage !== 'function') {
            
            console.warn('Ενεργοποίηση fallback TesseractWrapper');
            
            // Δημιουργία fallback wrapper
            window.TesseractWrapper = {
                // Προ-επεξεργασία εικόνας
                preprocessImage: function(imageData) {
                    return Promise.resolve(imageData);
                },
                
                // Αναγνώριση κειμένου - απλοποιημένη έκδοση
                recognize: function(imageData, language, options) {
                    if (typeof Tesseract !== 'undefined') {
                        return Tesseract.recognize(imageData, language);
                    } else {
                        return Promise.reject(new Error('Το Tesseract.js δεν έχει φορτωθεί'));
                    }
                },
                
                // Ανίχνευση γλώσσας/προσανατολισμού
                detect: function(imageData) {
                    if (typeof Tesseract !== 'undefined') {
                        return Tesseract.detect(imageData);
                    } else {
                        return Promise.reject(new Error('Το Tesseract.js δεν έχει φορτωθεί'));
                    }
                }
            };
        }
    }, 500); // Μικρή καθυστέρηση για να βεβαιωθούμε ότι το bundle έχει φορτωθεί
})();
// Fallback wrapper για το Tesseract.js
(function() {
    console.log('DEBUG-FALLBACK: Checking if TesseractWrapper exists:', typeof window.TesseractWrapper !== 'undefined');
    
    // Έλεγχος αν το TesseractWrapper έχει οριστεί σωστά
    setTimeout(() => {
        console.log('DEBUG-FALLBACK: Delayed check - TesseractWrapper exists:', typeof window.TesseractWrapper !== 'undefined');
        if (typeof window.TesseractWrapper !== 'undefined') {
            console.log('DEBUG-FALLBACK: TesseractWrapper methods:', Object.keys(window.TesseractWrapper));
        }
        
        // Έλεγχος αν το Tesseract.js υπάρχει
        console.log('DEBUG-FALLBACK: Tesseract exists:', typeof Tesseract !== 'undefined');
        
        // Υπόλοιπος κώδικας...
    }, 500);
})();