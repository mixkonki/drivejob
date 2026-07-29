<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use Drivejob\Core\Session;

// Έναρξη session
Session::start();

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι admin
if (!Session::has('user_id') || Session::get('user_role') !== 'admin') {
    header('Location: /drivejob/public/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="el">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - DriveJob</title>
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

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .dashboard-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .dashboard-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .dashboard-card h3 {
            color: #666;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .dashboard-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: #a94442;
        }

        .dashboard-card a {
            display: inline-block;
            margin-top: 1rem;
            color: #a94442;
            text-decoration: none;
            font-weight: 500;
        }

        .dashboard-card a:hover {
            text-decoration: underline;
        }

        .quick-links {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-top: 2rem;
        }

        .quick-links h3 {
            color: #a94442;
            margin-bottom: 1rem;
        }

        .quick-links ul {
            list-style: none;
        }

        .quick-links li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }

        .quick-links li:last-child {
            border-bottom: none;
        }

        .quick-links a {
            color: #666;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quick-links a:hover {
            color: #a94442;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>DriveJob Admin Dashboard</h1>
        <div class="user-info">
            <span>Καλώς ήρθατε, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Administrator'); ?></span>
            <a href="/drivejob/public/logout.php" class="logout-btn">Αποσύνδεση</a>
        </div>
    </div>

    <div class="container">
        <div class="welcome-card">
            <h2>Πίνακας Ελέγχου Διαχειριστή</h2>
            <p>Καλώς ήρθατε στον πίνακα ελέγχου του DriveJob. Από εδώ μπορείτε να διαχειριστείτε όλες τις λειτουργίες του συστήματος.</p>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>Οδηγοί</h3>
                <div class="number">0</div>
                <a href="/drivejob/public/admin/drivers">Διαχείριση Οδηγών →</a>
            </div>

            <div class="dashboard-card">
                <h3>Εταιρείες</h3>
                <div class="number">0</div>
                <a href="/drivejob/public/admin/companies">Διαχείριση Εταιρειών →</a>
            </div>

            <div class="dashboard-card">
                <h3>Αγγελίες</h3>
                <div class="number">0</div>
                <a href="/drivejob/public/admin/jobs">Διαχείριση Αγγελιών →</a>
            </div>

            <div class="dashboard-card">
                <h3>Χρήστες</h3>
                <div class="number">5</div>
                <a href="/drivejob/public/admin/users">Διαχείριση Χρηστών →</a>
            </div>
        </div>

        <div class="quick-links">
            <h3>Γρήγοροι Σύνδεσμοι</h3>
            <ul>
                <li><a href="/drivejob/public/admin/settings">⚙️ Ρυθμίσεις Συστήματος</a></li>
                <li><a href="/drivejob/public/admin/reports">📊 Αναφορές</a></li>
                <li><a href="/drivejob/public/admin/logs">📝 Αρχεία Καταγραφής</a></li>
                <li><a href="/drivejob/public/admin/backup">💾 Αντίγραφα Ασφαλείας</a></li>
                <li><a href="/drivejob/public/admin/help">❓ Βοήθεια & Τεκμηρίωση</a></li>
            </ul>
        </div>
    </div>
</body>

</html>