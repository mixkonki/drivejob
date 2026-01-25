<?php
// Αρχικοποίηση της εφαρμογής
require_once __DIR__ . '/../config/config.php';

// Ανακατεύθυνση στη νέα διαδρομή auth/verification-required
header('Location: ' . BASE_URL . 'auth/verification-required');
exit;
