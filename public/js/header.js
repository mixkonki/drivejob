document.addEventListener('DOMContentLoaded', () => {
    const dropdown = document.querySelector('.dropdown');
    
    // Εάν το dropdown δεν υπάρχει, επιστρέφουμε πρόωρα
    if (!dropdown) {
        return;
    }
    
    const dropdownMenu = dropdown.querySelector('.dropdown-menu');
    const dropdownButton = dropdown.querySelector('.dropdown-toggle');
    
    // Εάν το dropdownMenu ή το dropdownButton δεν υπάρχουν, επιστρέφουμε πρόωρα
    if (!dropdownMenu || !dropdownButton) {
        return;
    }

    // Προσθήκη event listener για το κουμπί του dropdown
    dropdownButton.addEventListener('click', (e) => {
        e.stopPropagation(); // Αποτροπή της διάδοσης του event στο document
        dropdownMenu.classList.toggle('show');
    });

    // Κλείσιμο dropdown όταν γίνεται κλικ εκτός
    document.addEventListener('click', (e) => {
        if (!dropdown.contains(e.target)) {
            dropdownMenu.classList.remove('show');
        }
    });
});