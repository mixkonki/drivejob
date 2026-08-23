<?php

use Drivejob\Core\Session;

// Ξεκίνημα συνεδρίας
Session::start();
// Ορισμός των Content Security Policy headers με υποστήριξη για WebAssembly
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'wasm-unsafe-eval' https://maps.googleapis.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' https: data:; font-src 'self' https://cdnjs.cloudflare.com; connect-src 'self' https://maps.googleapis.com blob: data:; frame-src 'self' https://maps.google.com https://www.google.com; worker-src 'self' blob:;");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");

// Όσο το ALLOW_INDEXING είναι false, καμία σελίδα δεν ευρετηριάζεται. Η
// κεφαλίδα καλύπτει και μη-HTML απαντήσεις (PDF, JSON), που το meta tag δεν
// μπορεί να φτάσει.
if (defined('ALLOW_INDEXING') && !ALLOW_INDEXING && !headers_sent()) {
    header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet');
}



// Βοηθητική συνάρτηση για να ελέγχουμε την τρέχουσα σελίδα
if (!function_exists('isCurrentPage')) {
    function isCurrentPage($page)
    {
        // Λειτουργεί με routes: συγκρίνει το path του request με το route
        $path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/', '/');
        $page = trim(str_replace('.php', '', (string) $page), '/');
        if ($page === '' || $page === 'index') {
            return $path === '';
        }
        return $path === $page || strpos($path, $page . '/') === 0;
    }
}

// Έλεγχος για συνδεδεμένο χρήστη
$isLoggedIn = Session::has('user_id');
$userName = Session::has('user_name') ? Session::get('user_name') : '';
$userRole = Session::has('user_role') ? Session::get('user_role') : '';
?>
<!DOCTYPE html>
<html lang="el">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="DriveJob - Ψηφιακή Πλατφόρμα Πρόσληψης Οδηγών και Επιχειρήσεων.">
    <meta name="keywords" content="εργασία, οδηγοί, εταιρείες, πρόσληψη, πλατφόρμα">
    <meta name="author" content="DriveJob">
<?php if (defined('ALLOW_INDEXING') && !ALLOW_INDEXING): ?>
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
<?php endif; ?>
    <meta name="csrf-token" content="<?php echo \Drivejob\Core\CSRF::getCurrentToken(); ?>">

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#3b82f6">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="DriveJob">
    <meta name="msapplication-TileColor" content="#3b82f6">
    <meta name="msapplication-config" content="<?php echo BASE_URL; ?>browserconfig.xml">
    <link rel="manifest" href="<?php echo BASE_URL; ?>manifest.json">
    <link rel="apple-touch-icon" href="<?= \Drivejob\Helpers\Asset::url('img/icons/icon-192x192.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= \Drivejob\Helpers\Asset::url('img/icons/icon-192x192.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= \Drivejob\Helpers\Asset::url('img/icons/icon-96x96.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= \Drivejob\Helpers\Asset::url('img/icons/icon-72x72.png') ?>">



    <!-- Δυναμικός τίτλος σελίδας -->
    <title>DriveJob - <?php echo isset($pageTitle) ? $pageTitle : 'Καλώς Ήρθατε'; ?></title>

    <!-- Σύνδεση με το CSS αρχείο -->
    <?= \Drivejob\Helpers\Asset::css('css/styles.css') ?>
    <?= \Drivejob\Helpers\Asset::css('css/job-listings.css') ?>
    <link rel="icon" href="<?= \Drivejob\Helpers\Asset::url('img/favicon.ico') ?>" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Επιπλέον CSS αρχεία -->
    <?php if (isset($extraCss) && is_array($extraCss)) : ?>
        <?php foreach ($extraCss as $css) : ?>
            <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Σύνδεση του header.js -->
    <?= \Drivejob\Helpers\Asset::js('js/header.js') ?>

    <!-- Επιπλέον JS αρχεία -->
    <?php if (isset($extraJs) && is_array($extraJs)) : ?>
        <?php foreach ($extraJs as $js) : ?>
            <script src="<?php echo BASE_URL; ?>js/<?php echo $js; ?>" defer></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- PWA Service Worker Registration -->
    <script>
        /*
         * Καταχώρηση service worker.
         *
         * Η προηγούμενη έκδοση ρωτούσε τον χρήστη «New version available!
         * Reload to update?» — στα αγγλικά, και αν πατούσε Άκυρο έμενε
         * κολλημένος στην παλιά έκδοση για πάντα. Τώρα η ενημέρωση
         * εφαρμόζεται σιωπηλά στην επόμενη πλοήγηση.
         */
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('<?php echo BASE_URL; ?>sw.js')
                    .then(function (registration) {
                        // Έλεγχος για νεότερη έκδοση σε κάθε φόρτωση
                        registration.update();

                        registration.addEventListener('updatefound', function () {
                            var newWorker = registration.installing;
                            if (!newWorker) return;

                            newWorker.addEventListener('statechange', function () {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    console.log('[SW] νέα έκδοση έτοιμη — θα ενεργοποιηθεί στην επόμενη πλοήγηση');
                                }
                            });
                        });
                    })
                    .catch(function (error) {
                        console.log('[SW] η καταχώρηση απέτυχε:', error);
                    });

                // Όταν αλλάξει ο ελεγκτής, φορτώνουμε μία φορά ώστε ο χρήστης
                // να δει αμέσως τη νέα έκδοση αντί να μείνει στην παλιά.
                var refreshing = false;
                navigator.serviceWorker.addEventListener('controllerchange', function () {
                    if (refreshing) return;
                    refreshing = true;
                    window.location.reload();
                });
            });
        }

        // Handle PWA install prompt
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', function(e) {
            // Prevent Chrome 67 and earlier from automatically showing the prompt
            e.preventDefault();
            // Stash the event so it can be triggered later
            deferredPrompt = e;

            // Show custom install button or banner
            const installBanner = document.createElement('div');
            installBanner.id = 'pwa-install-banner';
            installBanner.innerHTML = `
                <div style="position: fixed; bottom: 20px; left: 20px; right: 20px;
                           background: #3b82f6; color: white; padding: 15px;
                           border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                           z-index: 10000; text-align: center;">
                    <p style="margin: 0 0 10px 0; font-size: 14px;">📱 Install DriveJob on your device!</p>
                    <button onclick="installPWA()" style="background: white; color: #3b82f6;
                              border: none; padding: 8px 16px; border-radius: 5px;
                              font-weight: bold; cursor: pointer;">Install</button>
                    <button onclick="dismissInstall()" style="background: transparent; color: white;
                              border: 1px solid white; padding: 8px 16px; border-radius: 5px;
                              margin-left: 10px; cursor: pointer;">Later</button>
                </div>
            `;
            document.body.appendChild(installBanner);

            // Auto-hide after 10 seconds
            setTimeout(() => {
                const banner = document.getElementById('pwa-install-banner');
                if (banner) banner.remove();
            }, 10000);
        });

        function installPWA() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then(function(choiceResult) {
                    console.log('User choice:', choiceResult.outcome);
                    deferredPrompt = null;

                    // Remove install banner
                    const banner = document.getElementById('pwa-install-banner');
                    if (banner) banner.remove();
                });
            }
        }

        function dismissInstall() {
            const banner = document.getElementById('pwa-install-banner');
            if (banner) banner.remove();
            deferredPrompt = null;
        }

        // Handle app installed event
        window.addEventListener('appinstalled', function(evt) {
            console.log('DriveJob PWA was installed successfully!');
            // Analytics tracking
            if (typeof gtag !== 'undefined') {
                gtag('event', 'pwa_installed', {
                    event_category: 'pwa',
                    event_label: 'DriveJob PWA'
                });
            }
        });

        // Handle online/offline status
        window.addEventListener('online', function() {
            console.log('Back online');
            // Show online notification
            showNetworkStatus('Back online!', 'success');
        });

        window.addEventListener('offline', function() {
            console.log('Gone offline');
            // Show offline notification
            showNetworkStatus('You are offline. Some features may not work.', 'warning');
        });

        function showNetworkStatus(message, type) {
            const statusDiv = document.createElement('div');
            statusDiv.style.cssText = `
                position: fixed; top: 20px; right: 20px; z-index: 10001;
                padding: 10px 15px; border-radius: 5px; color: white;
                font-weight: bold; max-width: 300px;
                ${type === 'success' ? 'background: #10b981;' : 'background: #f59e0b;'}
            `;
            statusDiv.textContent = message;
            document.body.appendChild(statusDiv);

            setTimeout(() => {
                statusDiv.remove();
            }, 3000);
        }
    </script>
</head>

<body>
    <header class="header">
        <!-- Λογότυπο -->
        <div class="logo">
            <a href="<?php echo BASE_URL; ?>">
                <img src="<?= \Drivejob\Helpers\Asset::url('img/logo.png') ?>" alt="Λογότυπο DriveJob">
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
                    <a href="<?php echo BASE_URL; ?>about" class="<?php echo isCurrentPage('about') ? 'active' : ''; ?>">
                        Σχετικά
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>contact" class="<?php echo isCurrentPage('contact') ? 'active' : ''; ?>">
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
                                $query = "SELECT company_logo FROM companies WHERE id = ?";
                                $stmt = $pdo->prepare($query);
                                $stmt->execute([$companyId]);
                                $result = $stmt->fetch(\PDO::FETCH_ASSOC);

                                if ($result && !empty($result['company_logo'])) {
                                    $profileImage = BASE_URL . $result['company_logo'];
                                }
                            }
                        }

                        // Αν δεν βρέθηκε εικόνα, χρησιμοποίησε την προεπιλεγμένη
                        if (empty($profileImage)) {
                            if ($userRole === 'company') {
                                $profileImage = BASE_URL . 'img/default_company_logo.png';
                            } else {
                                $profileImage = BASE_URL . 'img/user_icon.png';
                            }
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
                        <?php if ($userRole === 'admin') : ?>
                            <a href="<?php echo BASE_URL; ?>admin/monitoring/dashboard">
                                <i class="fas fa-tachometer-alt"></i>
                                Admin Dashboard
                            </a>
                            <a href="<?php echo BASE_URL; ?>admin/users">
                                <i class="fas fa-users"></i>
                                Διαχείριση Χρηστών
                            </a>
                            <a href="<?php echo BASE_URL; ?>admin/job-listings">
                                <i class="fas fa-briefcase"></i>
                                Διαχείριση Αγγελιών
                            </a>
                            <a href="<?php echo BASE_URL; ?>admin/analytics">
                                <i class="fas fa-chart-line"></i>
                                Στατιστικά
                            </a>
                            <a href="<?php echo BASE_URL; ?>admin/monitoring/dashboard">
                                <i class="fas fa-server"></i>
                                System Monitoring
                            </a>
                            <div class="dropdown-divider"></div>
                        <?php elseif ($userRole === 'company') : ?>
                            <a href="<?php echo BASE_URL; ?>companies/profile">
                                <img src="<?= \Drivejob\Helpers\Asset::url('img/profile_icon.png') ?>" alt="Profile Icon" />
                                Προφίλ
                            </a>
                        <?php else : ?>
                            <a href="<?php echo BASE_URL; ?>drivers/profile">
                                <img src="<?= \Drivejob\Helpers\Asset::url('img/profile_icon.png') ?>" alt="Profile Icon" />
                                Προφίλ
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo BASE_URL; ?>auth/logout">
                            <img src="<?= \Drivejob\Helpers\Asset::url('img/logout_icon.png') ?>" alt="Logout Icon" />
                            Αποσύνδεση
                        </a>
                    </div>
                </div>
            <?php
            else :
            ?>
                <!-- Σύνδεση για μη συνδεδεμένο χρήστη -->
                <a href="<?php echo BASE_URL; ?>auth/login" class="btn btn-dark">
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