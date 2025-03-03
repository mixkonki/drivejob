<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Επικοινωνία - DriveJob</title>
    <link rel="stylesheet" href="../css/styles.css"> <!-- Σύνδεση με το styles.css -->
</head>
<body>
<?php 
// Συμπερίληψη του config.php για να οριστούν οι σταθερές
require_once __DIR__ . '/../config/config.php';

// Συμπερίληψη του header
include '../src/Views/header.php'; 
?>
<main>
<div class="container">
    <!-- Τίτλος σελίδας -->
    <h1>Επικοινωνία</h1>
    <!-- Περιγραφή σελίδας -->
    <p>Επικοινωνήστε μαζί μας για οποιαδήποτε ερώτηση ή πληροφορία χρειάζεστε.</p>
    <!-- Φόρμα Επικοινωνίας -->
    <form action="send_contact.php" method="POST">
        <label for="name">Όνομα</label>
        <input type="text" id="name" name="name" placeholder="Το όνομά σας" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="Το email σας" required>

        <label for="message">Μήνυμα</label>
        <textarea id="message" name="message" placeholder="Το μήνυμά σας" rows="5" required></textarea>

        <!-- Κουμπί αποστολής -->
        <button type="submit" class="btn-primary">Αποστολή</button>
    </form>
</div>
</main>
<?php 

// Συμπερίληψη του footer
include '../src/Views/footer.php'; 
?>
</body>
</html>
