<?php
// Αρχικοποίηση της εφαρμογής
require_once __DIR__ . '/../../config/config.php';

// Ανακατεύθυνση στη νέα διαδρομή companies/register
header('Location: ' . BASE_URL . 'companies/register');
exit;
