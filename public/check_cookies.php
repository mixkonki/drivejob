<?php
// public/check_cookies.php

// Αρχικοποίηση της εφαρμογής
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Session;

// Έλεγχος αν τα cookies είναι ενεργοποιημένα
$cookiesEnabled = isset($_COOKIE['cookie_test']);

// Αρχική σελίδα για ανακατεύθυνση
$originalPage = Session::get('original_page', BASE_URL);
Session::remove('original_page');

// Αν τα cookies είναι ενεργοποιημένα, ανακατευθύνουμε πίσω
if ($cookiesEnabled) {
    header('Location: ' . $originalPage);
    exit();
}

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php';
?>

<main>
    <div class="container">
        <div class="error-page">
            <h1>Τα Cookies είναι Απενεργοποιημένα</h1>
            <h2>Παρακαλώ ενεργοποιήστε τα cookies στον browser σας</h2>
            <p>Η ιστοσελίδα DriveJob απαιτεί την ενεργοποίηση των cookies για τη σωστή λειτουργία της. Παρακαλούμε ενεργοποιήστε τα cookies και προσπαθήστε ξανά.</p>
            
            <div class="instructions">
                <h3>Οδηγίες ενεργοποίησης cookies:</h3>
                <div class="browser-instructions">
                    <h4>Google Chrome</h4>
                    <ol>
                        <li>Πατήστε στο μενού (τρεις κάθετες τελείες) στην επάνω δεξιά γωνία</li>
                        <li>Επιλέξτε "Ρυθμίσεις"</li>
                        <li>Κάτω από την ενότητα "Απόρρητο και ασφάλεια", κάντε κλικ στο "Cookies και άλλα δεδομένα ιστότοπου"</li>
                        <li>Βεβαιωθείτε ότι τα cookies είναι ενεργοποιημένα</li>
                    </ol>
                </div>
                <!-- Προσθέστε οδηγίες για άλλους browsers -->
            </div>
            
            <a href="<?php echo BASE_URL; ?>" class="btn-primary">Δοκιμάστε Ξανά</a>
        </div>
    </div>
</main>

<style>
    .error-page {
        text-align: center;
        padding: 50px 0;
    }
    
    .error-page h1 {
        font-size: 42px;
        color: #aa3636;
        margin-bottom: 10px;
    }
    
    .error-page h2 {
        font-size: 24px;
        margin-bottom: 20px;
    }
    
    .error-page p {
        font-size: 16px;
        color: #666;
        margin-bottom: 30px;
    }
    
    .instructions {
        text-align: left;
        max-width: 600px;
        margin: 30px auto;
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 5px;
        background-color: #f9f9f9;
    }
    
    .instructions h3 {
        margin-bottom: 20px;
        font-size: 18px;
    }
    
    .browser-instructions {
        margin-bottom: 20px;
    }
    
    .browser-instructions h4 {
        margin-bottom: 10px;
        font-size: 16px;
        color: #333;
    }
    
    .browser-instructions ol {
        padding-left: 20px;
    }
    
    .browser-instructions li {
        margin-bottom: 5px;
    }
</style>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php';
?>