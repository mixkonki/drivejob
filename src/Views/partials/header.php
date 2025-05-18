<?php

use Drivejob\Core\Session;

// Ξεκίνημα συνεδρίας
Session::start();
// Ορισμός των Content Security Policy headers με υποστήριξη για WebAssembly
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'wasm-unsafe-eval' https://maps.googleapis.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' https: data:; font-src 'self' https://cdnjs.cloudflare.com; connect-src 'self' https://maps.googleapis.com blob: data:; frame-src 'self' https://maps.google.com https://www.google.com; worker-src 'self' blob:;");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");


// Βοηθητική συνάρτηση για να ελέγχουμε την τρέχουσα σελίδα
if (!function_exists('isCurrentPage')) {
    function isCurrentPage($page)
    {
        $currentPage = basename($_SERVER['PHP_SELF']);
        return $currentPage === $page;
    }
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
    <meta name="csrf-token" content="<?php echo \Drivejob\Core\CSRF::getCurrentToken(); ?>">



    <!-- Δυναμικός τίτλος σελίδας -->
    <title>DriveJob - <?php echo isset($pageTitle) ? $pageTitle : 'Καλώς Ήρθατε'; ?></title>

    <!-- Σύνδεση με το CSS αρχείο -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/styles.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/job-listings.css">
    <link rel="icon" href="<?php echo BASE_URL; ?>img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

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
                    <a href="<?php echo BASE_URL; ?>job-listings" class="<?php echo isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'job-listings') !== false ? 'active' : ''; ?>">
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
            <?php if ($isLoggedIn) :
            ?>
                <!-- Dropdown για τον συνδεδεμένο χρήστη -->
                <div class="dropdown">
                    <button class="btn btn-dark dropdown-toggle">
                        <!-- Εμφάνιση εικόνας προφίλ ή default εικονιδίου -->
                        <?php
                        $profileImage = '';
                        if ($userRole === 'driver' && Session::has('user_id')) {
                            // Ανάκτηση της εικόνας προφίλ του οδηγού από τη βάση δεδομένων
                            $driverId = Session::get('user_id');
                            $pdo = $GLOBALS['pdo'] ?? null;

                            if ($pdo) {
                                $query = "SELECT profile_image FROM drivers WHERE id = ?";
                                $stmt = $pdo->prepare($query);
                                $stmt->execute([$driverId]);
                                $result = $stmt->fetch(\PDO::FETCH_ASSOC);

                                if ($result && !empty($result['profile_image'])) {
                                    $profileImage = BASE_URL . $result['profile_image'];
                                }
                            }
                        } else if ($userRole === 'company' && Session::has('user_id')) {
                            // Ανάκτηση του λογότυπου της εταιρείας από τη βάση δεδομένων
                            $companyId = Session::get('user_id');
                            $pdo = $GLOBALS['pdo'] ?? null;

                            if ($pdo) {
                                $query = "SELECT logo FROM companies WHERE id = ?";
                                $stmt = $pdo->prepare($query);
                                $stmt->execute([$companyId]);
                                $result = $stmt->fetch(\PDO::FETCH_ASSOC);

                                if ($result && !empty($result['logo'])) {
                                    $profileImage = BASE_URL . $result['logo'];
                                }
                            }
                        }

                        // Αν δεν βρέθηκε εικόνα, χρησιμοποίησε την προεπιλεγμένη
                        if (empty($profileImage)) {
                            $profileImage = BASE_URL . 'img/profile_placeholder.png';
                        }
                        ?>
                        <style>
                            .user-picture {
                                width: 30px;
                                height: 30px;
                                border-radius: 50%;
                                object-fit: cover;
                                margin-right: 5px;
                            }
                        </style>
                        <img src="<?php echo $profileImage; ?>" alt="User Picture" class="user-picture" />
                    </button>
                    <div class="dropdown-menu">
                        <div class="dropdown-header">
                            <?php echo htmlspecialchars($userName ?: 'Χρήστης'); ?>
                        </div>
                        <!-- Επιλογές προφίλ, αποσύνδεσης -->
                        <?php if ($userRole === 'company') :
                        ?>
                            <a href="<?php echo BASE_URL; ?>companies/company_profile">
                                <img src="<?php echo BASE_URL; ?>img/profile_icon.png" alt="Profile Icon" />
                                Προφίλ
                            </a>
                        <?php
                        else :
                        ?>
                            <a href="<?php echo BASE_URL; ?>drivers/driver_profile">
                                <img src="<?php echo BASE_URL; ?>img/profile_icon.png" alt="Profile Icon" />
                                Προφίλ
                            </a>
                        <?php
                        endif; ?>
                        <a href="<?php echo BASE_URL; ?>logout.php">
                            <img src="<?php echo BASE_URL; ?>img/logout_icon.png" alt="Logout Icon" />
                            Αποσύνδεση
                        </a>
                    </div>
                </div>
            <?php
            else :
            ?>
                <!-- Σύνδεση για μη συνδεδεμένο χρήστη -->
                <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-dark">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    Σύνδεση
                </a>
            <?php
            endif; ?>
        </div>
    </header>