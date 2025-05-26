# Οδηγός Debugging για Company Features

## ✅ Τι Λειτουργεί (Επιβεβαιωμένο)

1. **Database**: Όλες οι στήλες υπάρχουν
2. **Files**: Όλα τα αρχεία είναι στη θέση τους
3. **JavaScript**: Το αρχείο υπάρχει με tab functionality
4. **Components**: Όλα τα components υπάρχουν

## 🔍 Πιθανά Προβλήματα & Λύσεις

### 1. JavaScript δεν φορτώνει

**Έλεγχος στο Browser:**
1. Ανοίξτε το Developer Tools (F12)
2. Πηγαίνετε στο tab "Console"
3. Ελέγξτε για JavaScript errors
4. Πηγαίνετε στο tab "Network" και ελέγξτε αν το company-features.js φορτώνει (status 200)

**Πιθανή Λύση:**
```html
<!-- Βεβαιωθείτε ότι το path είναι σωστό -->
<script src="<?php echo BASE_URL; ?>js/company-features.js"></script>
```

### 2. Tabs δεν λειτουργούν

**Debug στο Console:**
```javascript
// Εκτελέστε αυτά στο browser console
console.log(document.querySelectorAll('.tab-btn').length); // Πρέπει να δείξει > 0
console.log(document.querySelectorAll('.tab-pane').length); // Πρέπει να δείξει > 0
```

**Πιθανή Λύση:**
Αν τα elements δεν βρίσκονται, το JavaScript εκτελείται πριν φορτώσει το DOM. Προσθέστε:
```javascript
// Στην αρχή του company-features.js
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeTabs);
} else {
    initializeTabs();
}
```

### 3. Form δεν υποβάλλεται

**Έλεγχος:**
1. Στο Network tab, δείτε αν γίνεται POST request στο `/companies/update-profile`
2. Ελέγξτε το Response για errors

**Debug PHP:**
Προσθέστε στην αρχή του `public/companies/update-profile.php`:
```php
error_log("Update profile called");
error_log("POST data: " . print_r($_POST, true));
```

Μετά δείτε το error log:
```bash
tail -f /var/log/apache2/error.log
# ή για WAMP
tail -f c:/wamp64/logs/apache_error.log
```

### 4. Δεδομένα δεν αποθηκεύονται

**SQL Debug:**
Στο `CompaniesController::update()`, προσθέστε:
```php
// Μετά το collectFormData()
error_log("Data to save: " . print_r($data, true));

// Μετά το update
error_log("Update result: " . ($updateResult ? 'SUCCESS' : 'FAILED'));
```

### 5. CSRF Token Issues

**Έλεγχος:**
```php
// Προσθέστε στο update-profile.php
if (!isset($_POST['csrf_token'])) {
    die("No CSRF token in POST");
}
```

## 🛠️ Quick Fix Script

Δημιουργήστε `fix-company-features.php`:

```php
<?php
// 1. Clear any cached routes
if (function_exists('opcache_reset')) {
    opcache_reset();
}

// 2. Check permissions
$dirs = [
    'public/uploads/companies',
    'logs'
];

foreach ($dirs as $dir) {
    if (!is_writable($dir)) {
        echo "WARNING: $dir is not writable\n";
        chmod($dir, 0777);
    }
}

// 3. Test form submission
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Form</title>
</head>
<body>
    <h1>Test Company Update</h1>
    <form action="/companies/update-profile" method="POST">
        <input type="hidden" name="csrf_token" value="test123">
        <input type="text" name="company_name" value="Test Company">
        <input type="number" name="fleet_size" value="5">
        <label>
            <input type="checkbox" name="has_fleet_management" value="1" checked>
            Has Fleet Management
        </label>
        <button type="submit">Test Submit</button>
    </form>
</body>
</html>
```

## 📋 Checklist για Testing

1. **Login ως Company**
   - [ ] Μπορείτε να συνδεθείτε;
   - [ ] Redirect στο company profile;

2. **Edit Profile Page**
   - [ ] Η σελίδα φορτώνει χωρίς errors;
   - [ ] Τα tabs εμφανίζονται;
   - [ ] Κλικ σε tab αλλάζει το περιεχόμενο;

3. **Form Submission**
   - [ ] Αλλάξτε κάποια πεδία
   - [ ] Πατήστε "Αποθήκευση"
   - [ ] Εμφανίζεται success message;
   - [ ] Οι αλλαγές αποθηκεύτηκαν;

## 🔧 Browser Console Commands

```javascript
// 1. Check if jQuery loaded (αν χρησιμοποιείται)
console.log(typeof jQuery !== 'undefined' ? 'jQuery loaded' : 'jQuery NOT loaded');

// 2. Check BASE_URL
console.log(document.querySelector('base')?.href || 'No base URL');

// 3. Manually trigger tab
document.querySelector('[data-tab="fleet-management"]').click();

// 4. Check form action
console.log(document.querySelector('.edit-profile-form').action);

// 5. List all form inputs
Array.from(document.querySelectorAll('.edit-profile-form input')).forEach(input => {
    console.log(input.name, input.value, input.type);
});
```

## 📱 Mobile Testing

Αν δεν λειτουργεί σε mobile:
1. Ελέγξτε αν τα touch events λειτουργούν
2. Προσθέστε: `cursor: pointer;` στα `.tab-btn`
3. Αυξήστε το μέγεθος των buttons για mobile

## 🚨 Emergency Fixes

### Fix 1: Force Tab Switch
```javascript
// Προσθέστε στο τέλος του company-features.js
window.switchTab = function(tabName) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
    
    const btn = document.querySelector(`[data-tab="${tabName}"]`);
    const pane = document.getElementById(tabName);
    
    if (btn && pane) {
        btn.classList.add('active');
        pane.classList.add('active');
    }
};
```

### Fix 2: Direct Update Test
```bash
# Από command line
curl -X POST http://localhost/companies/update-profile \
  -d "csrf_token=test&company_name=Test&fleet_size=10"
```

## 📞 Άμεση Βοήθεια

Αν τίποτα δεν λειτουργεί:

1. **Καθαρίστε το cache:**
   ```bash
   rm -rf cache/*
   ```

2. **Restart Apache/PHP**

3. **Check logs:**
   - Apache error log
   - PHP error log
   - Browser console

4. **Απλό test:**
   Δημιουργήστε `/test-tabs.html` με μόνο HTML/JS για να δείτε αν τα tabs λειτουργούν isolated.
