<?php
// Όσο το ALLOW_INDEXING είναι false, καμία σελίδα δεν ευρετηριάζεται. Η
// κεφαλίδα καλύπτει και μη-HTML απαντήσεις (PDF, JSON), που το meta tag δεν
// μπορεί να φτάσει.
if (defined('ALLOW_INDEXING') && !ALLOW_INDEXING && !headers_sent()) {
    header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet');
}
?>
<!DOCTYPE html>
<html lang="el">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Admin Panel - DriveJob'; ?></title>

    <!-- Admin CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/styles.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/admin.css">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>img/favicon.png">
</head>

<body class="admin-body">
    <!-- Admin Navigation -->
    <nav class="admin-nav">
        <div class="admin-nav-header">
            <a href="<?php echo BASE_URL; ?>admin/dashboard" class="admin-logo">
                <img src="<?php echo BASE_URL; ?>img/logo.png" alt="DriveJob Admin">
                <span>Admin Panel</span>
            </a>
            <button class="admin-nav-toggle" onclick="toggleAdminNav()">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>

        <ul class="admin-nav-menu">
            <li class="admin-nav-item">
                <a href="<?php echo BASE_URL; ?>admin/dashboard" class="admin-nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/dashboard') !== false ? 'active' : ''; ?>">
                    <i class="icon-dashboard"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="admin-nav-item">
                <a href="<?php echo BASE_URL; ?>admin/users" class="admin-nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/users') !== false || strpos($_SERVER['REQUEST_URI'], '/admin/user-details') !== false ? 'active' : ''; ?>">
                    <i class="icon-users"></i>
                    <span>Χρήστες</span>
                </a>
            </li>

            <li class="admin-nav-item">
                <a href="<?php echo BASE_URL; ?>admin/job-listings" class="admin-nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/job-listings') !== false ? 'active' : ''; ?>">
                    <i class="icon-briefcase"></i>
                    <span>Αγγελίες</span>
                </a>
            </li>

            <li class="admin-nav-item">
                <a href="<?php echo BASE_URL; ?>admin/analytics" class="admin-nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/analytics') !== false ? 'active' : ''; ?>">
                    <i class="icon-chart"></i>
                    <span>Στατιστικά</span>
                </a>
            </li>

            <li class="admin-nav-item">
                <a href="<?php echo BASE_URL; ?>admin/settings" class="admin-nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/settings') !== false ? 'active' : ''; ?>">
                    <i class="icon-settings"></i>
                    <span>Ρυθμίσεις</span>
                </a>
            </li>

            <li class="admin-nav-item">
                <a href="<?php echo BASE_URL; ?>admin/activity-logs" class="admin-nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/activity-logs') !== false ? 'active' : ''; ?>">
                    <i class="icon-activity"></i>
                    <span>Logs</span>
                </a>
            </li>

            <li class="admin-nav-item">
                <a href="<?php echo BASE_URL; ?>admin/monitoring/dashboard" class="admin-nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/monitoring') !== false ? 'active' : ''; ?>">
                    <i class="icon-monitoring"></i>
                    <span>System Monitoring</span>
                </a>
            </li>
        </ul>

        <div class="admin-nav-footer">
            <div class="admin-user-info">
                <img src="<?php echo BASE_URL; ?>img/default_profile.png" alt="Admin" class="admin-user-avatar">
                <div class="admin-user-details">
                    <span class="admin-user-name"><?php echo $_SESSION['user_name'] ?? 'Administrator'; ?></span>
                    <span class="admin-user-role">Super Admin</span>
                </div>
            </div>
            <a href="<?php echo BASE_URL; ?>auth/logout" class="admin-logout">
                <i class="icon-logout"></i>
                <span>Αποσύνδεση</span>
            </a>
        </div>
    </nav>

    <!-- Admin Main Content -->
    <main class="admin-main">
        <!-- Top Bar -->
        <div class="admin-topbar">
            <div class="admin-breadcrumb">
                <a href="<?php echo BASE_URL; ?>admin/dashboard">Admin</a>
                <?php if (isset($breadcrumb)): ?>
                    <?php foreach ($breadcrumb as $item): ?>
                        <span class="breadcrumb-separator">/</span>
                        <?php if (isset($item['url'])): ?>
                            <a href="<?php echo $item['url']; ?>"><?php echo $item['title']; ?></a>
                        <?php else: ?>
                            <span><?php echo $item['title']; ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="admin-topbar-actions">
                <button class="admin-notification-btn" onclick="toggleNotifications()">
                    <i class="icon-bell"></i>
                    <span class="notification-badge">3</span>
                </button>

                <a href="<?php echo BASE_URL; ?>" target="_blank" class="admin-view-site">
                    <i class="icon-external"></i>
                    <span>Προβολή Site</span>
                </a>
            </div>
        </div>

        <!-- Page Content -->
        <div class="admin-content">