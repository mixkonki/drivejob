<?php

/**
 * Migration: προσθήκη της τιμής «withdrawn» στο job_applications.status.
 *
 * Ο Driver\JobApplicationController::withdraw() γράφει status 'withdrawn',
 * αλλά το enum ήταν ('pending','viewed','shortlisted','rejected','hired').
 * Κάθε απόσυρση αίτησης απέτυχε σιωπηλά — ο οδηγός δεν μπορούσε ποτέ να
 * αποσύρει αίτηση.
 *
 * Το 'accepted' που έγραφε ο Company\JobApplicationController::accept()
 * ΔΕΝ προστίθεται: το 'hired' εκφράζει ήδη το ίδιο και ο controller
 * διορθώθηκε να το χρησιμοποιεί.
 *
 * Εκτέλεση:  php database/migrations/add_withdrawn_application_status.php
 * Idempotent.
 */

$pdo = require __DIR__ . '/_bootstrap.php';

echo "📋 Migration: job_applications.status — προσθήκη «withdrawn»\n\n";

$st = $pdo->query(
    "SELECT column_type FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'job_applications' AND column_name = 'status'"
);
$type = (string) $st->fetchColumn();

if ($type === '') {
    echo "  ❌ Δεν βρέθηκε η στήλη job_applications.status\n";
    exit(1);
}

echo "  Τρέχον: {$type}\n";

if (str_contains($type, "'withdrawn'")) {
    echo "  ⏭️  Η τιμή «withdrawn» υπάρχει ήδη.\n\n🟢 Ολοκληρώθηκε.\n";
    return;
}

$pdo->exec(
    "ALTER TABLE job_applications
     MODIFY COLUMN status ENUM('pending','viewed','shortlisted','rejected','hired','withdrawn')
     NOT NULL DEFAULT 'pending'"
);

$st = $pdo->query(
    "SELECT column_type FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'job_applications' AND column_name = 'status'"
);
echo "  ✅ Νέο:    " . $st->fetchColumn() . "\n";
echo "\n🟢 Ολοκληρώθηκε.\n";
