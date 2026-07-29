<?php
// Αρχικοποίηση της εφαρμογής
require_once __DIR__ . '/../config/config.php';

// Ανακατεύθυνση στη νέα διαδρομή auth/access-denied
header('Location: ' . BASE_URL . 'auth/access-denied');
exit;
