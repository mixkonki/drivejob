<?php
// Αρχικοποίηση της εφαρμογής
require_once __DIR__ . '/../config/config.php';

// Λήψη των παραμέτρων από το URL
$token = $_GET['token'] ?? '';

// Ανακατεύθυνση στη νέα διαδρομή auth/verify με το token
header('Location: ' . BASE_URL . 'auth/verify/' . urlencode($token));
exit;
