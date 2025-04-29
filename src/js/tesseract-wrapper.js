// Εισαγωγή του Tesseract.js
import { createWorker } from 'tesseract.js';

// Συνάρτηση βοηθός για αποσφαλμάτωση
function debugLog(message, data) {
  console.log(`DEBUG: ${message}`, data);
}

// Απλοποιημένος wrapper για το Tesseract.js
const TesseractWrapper = {
  /**
   * Προετοιμάζει μια εικόνα για OCR
   */
  preprocessImage: function(image) {
    debugLog('preprocessImage called with:', typeof image);
    return new Promise((resolve, reject) => {
      // Υπάρχων κώδικας...

      img.onload = function() {
        debugLog('Image loaded successfully');
        // Υπάρχων κώδικας...
        resolve(canvas.toDataURL('image/png'));
      };
      // Υπόλοιπος κώδικας...
    });
  },
  
  /**
   * Εκτελεί αναγνώριση κειμένου σε μια εικόνα
   */
  recognize: async function(imageData, language = 'eng+ell', options = {}) {
    debugLog('recognize called with language:', language);
    debugLog('recognize options:', JSON.stringify(options, (key, value) => {
      if (typeof value === 'function') return 'function() { ... }';
      return value;
    }));
    
    try {
      debugLog('Creating worker...');
      // Αντί για την αρχική μέθοδο, χρησιμοποιήστε μια πιο απλή προσέγγιση
      const worker = await createWorker();
      debugLog('Worker created successfully');
      
      debugLog('Loading language...');
      await worker.loadLanguage(language);
      debugLog('Language loaded successfully');
      
      debugLog('Initializing worker...');
      await worker.initialize(language);
      debugLog('Worker initialized successfully');
      
      // Έλεγχος και προσεκτική εφαρμογή παραμέτρων
      if (options.params) {
        debugLog('Setting parameters...');
        try {
          // Φιλτράρισμα των παραμέτρων για να αφαιρεθούν οι συναρτήσεις
          const safeParams = {};
          for (const key in options.params) {
            if (typeof options.params[key] !== 'function') {
              safeParams[key] = options.params[key];
            } else {
              debugLog(`Skipping function parameter: ${key}`);
            }
          }
          await worker.setParameters(safeParams);
          debugLog('Parameters set successfully');
        } catch (paramError) {
          debugLog('Error setting parameters:', paramError.message);
          throw paramError;
        }
      }
      
      debugLog('Starting recognition...');
      const result = await worker.recognize(imageData);
      debugLog('Recognition completed successfully');
      
      debugLog('Terminating worker...');
      await worker.terminate();
      debugLog('Worker terminated successfully');
      
      return result;
    } catch (error) {
      debugLog('Error in OCR process:', error.message);
      debugLog('Full error:', error);
      throw error;
    }
  },
  
  /**
   * Ανιχνεύει τη γλώσσα και τον προσανατολισμό μιας εικόνας
   */
  detect: async function(imageData, options = {}) {
    debugLog('detect called');
    try {
      // Υπάρχων κώδικας με προσθήκη μηνυμάτων αποσφαλμάτωσης...
    } catch (error) {
      debugLog('Error in detection:', error.message);
      throw error;
    }
  }
};

debugLog('TesseractWrapper defined successfully');
// Εξαγωγή του αντικειμένου TesseractWrapper
export default TesseractWrapper;