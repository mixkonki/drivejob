// Προσθέστε αυτό σε ένα κοινό αρχείο JavaScript (π.χ. public/js/app.js)
document.addEventListener('DOMContentLoaded', function() {
    // Προσθήκη του CSRF token σε όλα τα AJAX αιτήματα
    let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    if (csrfToken) {
        // Για χρήση με το Fetch API
        window.fetchWithCSRF = function(url, options = {}) {
            // Ορισμός προεπιλογών
            options = Object.assign({
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                credentials: 'same-origin'
            }, options);
            
            // Αν υπάρχουν ήδη headers, συγχώνευσέ τα
            if (options.headers) {
                options.headers = Object.assign({
                    'X-CSRF-Token': csrfToken
                }, options.headers);
            }
            
            return fetch(url, options);
        };
        
        // Για χρήση με το XMLHttpRequest
        let originalXHROpen = XMLHttpRequest.prototype.open;
        XMLHttpRequest.prototype.open = function() {
            let result = originalXHROpen.apply(this, arguments);
            this.setRequestHeader('X-CSRF-Token', csrfToken);
            return result;
        };
    }
});