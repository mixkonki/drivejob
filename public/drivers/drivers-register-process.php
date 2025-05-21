<?php
// Αρχικοποίηση της εφαρμογής
require_once __DIR__ . '/../../config/config.php';

// Ανακατεύθυνση στη νέα διαδρομή drivers/register
header('Location: ' . BASE_URL . 'drivers/register');
exit;
