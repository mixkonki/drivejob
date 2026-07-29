<?php

/**
 * Εκτέλεση των migrations για το σύστημα ταιριάσματος αγγελιών
 * 
 * Αυτό το script εκτελεί τα migrations για τους πίνακες match_preferences και match_history
 */

echo "Εκτέλεση των migrations για το σύστημα ταιριάσματος αγγελιών...\n";

// Εκτέλεση του migration για τον πίνακα match_preferences
echo "\nΔημιουργία του πίνακα match_preferences:\n";
require_once __DIR__ . '/create_match_preferences_table.php';

// Εκτέλεση του migration για τον πίνακα match_history
echo "\nΔημιουργία του πίνακα match_history:\n";
require_once __DIR__ . '/create_match_history_table.php';

echo "\nΗ εκτέλεση των migrations ολοκληρώθηκε με επιτυχία!\n";
