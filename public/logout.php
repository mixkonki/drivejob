<?php
// Αρχικοποίηση της εφαρμογής
require_once __DIR__ . '/../config/config.php';

// Ανακατεύθυνση στη νέα διαδρομή auth/logout
header('Location: ' . BASE_URL . 'auth/logout');
exit;
