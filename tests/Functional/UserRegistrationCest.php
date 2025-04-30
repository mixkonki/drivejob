// Παράδειγμα 4: Ένα Functional Test με Codeception
// File: tests/Functional/UserRegistrationCest.php

class UserRegistrationCest
{
public function _before(FunctionalTester $I)
{
// Καθαρίστε τη βάση δεδομένων πριν από κάθε test
$I->runShellCommand('php artisan migrate:fresh --env=testing');
}

public function testDriverRegistrationSuccess(FunctionalTester $I)
{
// Επίσκεψη στη σελίδα εγγραφής
$I->amOnPage('/register/driver');
$I->see('Εγγραφή Οδηγού');

// Συμπλήρωση της φόρμας
$I->fillField('name', 'Γιώργος Παπαδόπουλος');
$I->fillField('email', 'gpapadopoulos@example.com');
$I->fillField('phone', '6912345678');
$I->fillField('password', 'StrongP@ss123');
$I->fillField('password_confirmation', 'StrongP@ss123');
$I->selectOption('driverType', 'Επαγγελματίας Οδηγός');
$I->fillField('experience', '5');

// Επιλογή αδειών οδήγησης
$I->checkOption('licenses[]', 'C');
$I->checkOption('licenses[]', 'CE');

// Υποβολή της φόρμας
$I->click('Εγγραφή');

// Έλεγχος επιτυχίας
$I->seeCurrentUrlEquals('/driver/dashboard');
$I->see('Καλωσορίσατε, Γιώργος Παπαδόπουλος');

// Επαλήθευση ότι ο οδηγός αποθηκεύτηκε στη βάση
$I->seeInDatabase('drivers', [
'email' => 'gpapadopoulos@example.com',
'name' => 'Γιώργος Παπαδόπουλος',
'years_experience' => '5'
]);

$I->seeInDatabase('driver_licenses', [
'driver_email' => 'gpapadopoulos@example.com',
'license_type' => 'C'
]);

$I->seeInDatabase('driver_licenses', [
'driver_email' => 'gpapadopoulos@example.com',
'license_type' => 'CE'
]);
}

public function testDriverRegistrationValidationErrors(FunctionalTester $I)
{
$I->amOnPage('/register/driver');

// Υποβολή κενής φόρμας
$I->click('Εγγραφή');

// Έλεγχος μηνυμάτων λάθους
$I->see('Το πεδίο Ονοματεπώνυμο είναι υποχρεωτικό');
$I->see('Το πεδίο Email είναι υποχρεωτικό');
$I->see('Το πεδίο Κωδικός είναι υποχρεωτικό');

// Συμπλήρωση με άκυρο email
$I->fillField('name', 'Γιώργος Παπαδόπουλος');
$I->fillField('email', 'invalid-email');
$I->fillField('password', 'weak');
$I->fillField('password_confirmation', 'weak');
$I->click('Εγγραφή');

// Έλεγχος μηνυμάτων λάθους
$I->see('Το πεδίο Email πρέπει να είναι έγκυρη διεύθυνση email');
$I->see('Ο κωδικός πρέπει να έχει τουλάχιστον 8 χαρακτήρες');

// Συμπλήρωση με διαφορετικούς κωδικούς
$I->fillField('email', 'valid@example.com');
$I->fillField('password', 'StrongP@ss123');
$I->fillField('password_confirmation', 'DifferentP@ss123');
$I->click('Εγγραφή');

// Έλεγχος μηνύματος λάθους επιβεβαίωσης κωδικού
$I->see('Η επιβεβαίωση κωδικού δεν ταιριάζει');
}
}