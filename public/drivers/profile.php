<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use Drivejob\Core\Session;

// Έναρξη session
Session::start();

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι driver
if (!Session::has('user_id') || Session::get('user_role') !== 'driver') {
    header('Location: /drivejob/public/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="el">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Profile - DriveJob</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f5f5;
            color: #333;
        }

        .header {
            background: #a94442;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 1.5rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .welcome-card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .welcome-card h2 {
            color: #a94442;
            margin-bottom: 1rem;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .profile-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .profile-card h3 {
            color: #a94442;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .profile-card p {
            margin-bottom: 0.5rem;
            color: #666;
        }

        .profile-card a {
            display: inline-block;
            margin-top: 1rem;
            color: #a94442;
            text-decoration: none;
            font-weight: 500;
        }

        .profile-card a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>DriveJob - Προφίλ Οδηγού</h1>
        <div class="user-info">
            <span>Καλώς ήρθατε, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Οδηγός'); ?></span>
            <a href="/drivejob/public/logout.php" class="logout-btn">Αποσύνδεση</a>
        </div>
    </div>

    <div class="container">
        <div class="welcome-card">
            <h2>Το Προφίλ μου</h2>
            <p>Καλώς ήρθατε στο προφίλ σας στο DriveJob. Από εδώ μπορείτε να διαχειριστείτε τις πληροφορίες σας και να βρείτε νέες ευκαιρίες εργασίας.</p>
        </div>

        <div class="profile-grid">
            <div class="profile-card">
                <h3>📋 Προσωπικά Στοιχεία</h3>
                <p>Ενημερώστε τα προσωπικά σας στοιχεία</p>
                <a href="edit-profile.php">Επεξεργασία Προφίλ →</a>
            </div>

            <div class="profile-card">
                <h3>🚗 Εμπειρία Οχημάτων</h3>
                <p>Διαχειριστείτε την εμπειρία σας με διάφορα οχήματα</p>
                <a href="vehicle-experience.php">Προβολή Εμπειρίας →</a>
            </div>

            <div class="profile-card">