<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

use Drivejob\Core\Session;

// Ξεκίνημα ή συνέχιση συνεδρίας
Session::start();

// Εμφάνιση πληροφοριών συνεδρίας
echo "<h1>Πληροφορίες Συνεδρίας</h1>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Session Data:\n";
print_r($_SESSION);
echo "</pre>";

// Προσθήκη επιλογών καθαρισμού/επανεκκίνησης
echo "<a href='?action=clear'>Καθαρισμός Συνεδρίας</a> | ";
echo "<a href='?action=regenerate'>Επανεκκίνηση ID Συνεδρίας</a> | ";
echo "<a href='?action=destroy'>Καταστροφή Συνεδρίας</a>";

// Διαχείριση ενεργειών
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'clear':
            $_SESSION = [];
            echo "<p>Η συνεδρία καθαρίστηκε!</p>";
            break;
        case 'regenerate':
            session_regenerate_id(true);
            echo "<p>Το ID συνεδρίας ανανεώθηκε!</p>";
            break;
        case 'destroy':
            Session::destroy();
            echo "<p>Η συνεδρία καταστράφηκε!</p>";
            break;
    }
    
    echo "<meta http-equiv='refresh' content='2;url=debug_session.php'>";
}