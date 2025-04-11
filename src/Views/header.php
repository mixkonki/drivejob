<?php
use Drivejob\Core\Session;

// Ξεκίνημα συνεδρίας
Session::start();

// Ορισμός των Content Security Policy headers
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://maps.googleapis.com; style-src 'self' 'unsafe-inline'; img-src 'self' https: data:; font-src 'self'; connect-src 'self' https://maps.googleapis.com; frame-src 'self' https://maps.google.com https://www.google.com;");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");

// Βοηθητική συνάρτηση για να ελέγχουμε την τρέχουσα σελίδα
function isCurrentPage($page) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    return $currentPage === $page;
}

// Έλεγχος για συνδεδεμένο χρήστη
$isLoggedIn = Session::has('user_id');
$userName = Session::has('user_name') ? Session::get('user_name') : '';
$userRole = Session::has('role') ? Session::get('role') : '';
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="DriveJob - Ψηφιακή Πλατφόρμα Πρόσληψης Οδηγών και Επιχειρήσεων.">
    <meta name="keywords" content="εργασία, οδηγοί, εταιρείες, πρόσληψη, πλατφόρμα">
    <meta name="author" content="DriveJob">

    <!-- Δυναμικός τίτλος σελίδας -->
    <title>DriveJob - <?php echo isset($pageTitle) ? $pageTitle : 'Καλώς Ήρθατε'; ?></title>

    <!-- Σύνδεση με το CSS αρχείο -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/styles.css">
    <link rel="icon" href="<?php echo BASE_URL; ?>img/favicon.ico" type="image/x-icon">

    <!-- Σύνδεση του header.js -->
    <script src="<?php echo BASE_URL; ?>js/header.js" defer></script>
</head>
<body>
    <header class="header">
        <!-- Λογότυπο -->
        <div class="logo">
            <a href="<?php echo BASE_URL; ?>">
                <img src="<?php echo BASE_URL; ?>img/logo.png" alt="Λογότυπο DriveJob">
            </a>
        </div>
        
        <!-- Μενού πλοήγησης -->
        <nav class="nav-menu">
            <ul>
                <li>
                    <a href="<?php echo BASE_URL; ?>" class="<?php echo isCurrentPage('index.php') ? 'active' : ''; ?>">
                        Αρχική
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>job-listings" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'job-listings') !== false ? 'active' : ''; ?>">
                        Αγγελίες
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>about.php" class="<?php echo isCurrentPage('about.php') ? 'active' : ''; ?>">
                        Σχετικά
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>contact.php" class="<?php echo isCurrentPage('contact.php') ? 'active' : ''; ?>">
                        Επικοινωνία
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Ενέργειες χρήστη -->
        <div class="user-actions">
            <?php if ($isLoggedIn): ?>
                <!-- Dropdown για τον συνδεδεμένο χρήστη -->
                <div class="dropdown">
                    <button class="btn btn-dark dropdown-toggle">
                        <!-- Εμφάνιση εικόνας προφίλ ή default εικονιδίου -->
                        <img src="<?php echo Session::get('user_image', BASE_URL . 'img/profile_placeholder.png'); ?>" alt="User Picture" class="user-picture" />
                        <?php echo $userName ?: 'Το προφίλ μου'; ?>
                    </button>
                    <div class="dropdown-menu">
                        <div class="dropdown-header">
                            <?php echo htmlspecialchars($userName ?: 'Χρήστης'); ?>
                        </div>
                        <!-- Επιλογές προφίλ, αποσύνδεσης -->
                        <?php if ($userRole === 'company'): ?>
                        <a href="<?php echo BASE_URL; ?>companies/company_profile.php">
                            <img src="<?php echo BASE_URL; ?>img/profile_icon.png" alt="Profile Icon" />
                            Προφίλ
                        </a>
                        <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>drivers/driver_profile.php">
                            <img src="<?php echo BASE_URL; ?>img/profile_icon.png" alt="Profile Icon" />
                            Προφίλ
                        </a>
                        <?php endif; ?>
                        <a href="<?php echo BASE_URL; ?>logout.php">
                            <img src="<?php echo BASE_URL; ?>img/logout_icon.png" alt="Logout Icon" />
                            Αποσύνδεση
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Σύνδεση για μη συνδεδεμένο χρήστη -->
                <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-dark">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    Σύνδεση
                </a>
            <?php endif; ?>
        </div>
    </header>   