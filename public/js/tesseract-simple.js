// Απλοποιημένο wrapper για το Tesseract.js
window.TesseractWrapper = {
    // Προ-επεξεργασία εικόνας (απλά επιστρέφει την εικόνα ως έχει)
    preprocessImage: function(imageData) {
      console.log('TesseractWrapper.preprocessImage called');
      return Promise.resolve(imageData);
    },
    
    // Αναγνώριση κειμένου - απλά καλεί το Tesseract.recognize
    recognize: function(imageData, language, options) {
      console.log('TesseractWrapper.recognize called');
      
      // Βεβαιωνόμαστε ότι το Tesseract είναι διαθέσιμο
      if (typeof Tesseract === 'undefined') {
        console.error('Tesseract is not available');
        return Promise.reject(new Error('Tesseract is not available'));
      }
      
      // Δημιουργία ασφαλών επιλογών (χωρίς συναρτήσεις)
      const safeOptions = {};
      
      // Προσθήκη παραμέτρων αν υπάρχουν
      if (options && options.params) {
        safeOptions.tessedit_ocr_engine_mode = options.params.tessedit_ocr_engine_mode;
        safeOptions.tessedit_pageseg_mode = options.params.tessedit_pageseg_mode;
      }
      
      // Κλήση του Tesseract.recognize
      return Tesseract.recognize(imageData, language);
    },
    
    // Ανίχνευση γλώσσας/προσανατολισμού
    detect: function(imageData) {
      console.log('TesseractWrapper.detect called');
      
      // Βεβαιωνόμαστε ότι το Tesseract είναι διαθέσιμο
      if (typeof Tesseract === 'undefined') {
        console.error('Tesseract is not available');
        return Promise.reject(new Error('Tesseract is not available'));
      }
      
      // Κλήση του Tesseract.detect
      return Tesseract.detect(imageData);
    }
  };
  
  console.log('Simple TesseractWrapper initialized');