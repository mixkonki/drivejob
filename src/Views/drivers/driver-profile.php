<?php
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';
?>
<!-- Σύνδεση με το CSS αρχείο του προφίλ οδηγού -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver-profile.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver-skills.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/expiring-licenses.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver-rating-profile.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/toggle-switch.css">


<script>
    // Ορισμός των βασικών μεταβλητών
    const BASE_URL = '<?php echo BASE_URL; ?>';
</script>
<script src="<?php echo BASE_URL; ?>js/driver-profile.js"></script>
<!-- Μετά το link του CSS και πριν το </head> -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCgZpJWVYyrY0U8U1jBGelEWryur3vIrzc&libraries=places"></script>

<main>
    <div class="container">
        <!-- Επικεφαλίδα προφίλ με βασικές πληροφορίες και στατιστικά -->
        <div class="profile-header">
            <div class="profile-image-wrapper">
                <div class="profile-image">
                    <?php if (isset($driverData['profile_image']) && $driverData['profile_image']) : ?>
                        <img src="<?php echo BASE_URL . htmlspecialchars($driverData['profile_image']); ?>" alt="Εικόνα προφίλ">
                    <?php else : ?>
                        <img src="<?php echo BASE_URL; ?>img/default_profile.png" alt="Προεπιλεγμένη εικόνα προφίλ">
                    <?php endif; ?>
                </div>

            </div>

            <div class="profile-info">
                <h1><?php echo htmlspecialchars($driverData['first_name'] . ' ' . $driverData['last_name']); ?></h1>

                <?php if (isset($driverData['city']) && $driverData['city']) : ?>
                    <p class="profile-location">
                        <img src="<?php echo BASE_URL; ?>img/location_icon.png" alt="Τοποθεσία">
                        <?php echo htmlspecialchars($driverData['city'] . ', ' . $driverData['country']); ?>
                    </p>
                <?php endif; ?>

                <div class="driver-rating">
                    <div class="rating-stars">
                        <?php
                        $rating = isset($driverData['rating']) ? floatval($driverData['rating']) : 0;
                        $fullStars = floor($rating);
                        $halfStar = $rating - $fullStars >= 0.5;
                        $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);

                        for ($i = 0; $i < $fullStars; $i++) : ?>
                            <i class="star full"></i>
                        <?php endfor; ?>

                        <?php if ($halfStar) : ?>
                            <i class="star half"></i>
                        <?php endif; ?>

                        <?php for ($i = 0; $i < $emptyStars; $i++) : ?>
                            <i class="star empty"></i>
                        <?php endfor; ?>

                        <span class="rating-value"><?php echo number_format($rating, 1); ?></span>
                    </div>
                    <span class="rating-count">(<?php echo $driverData['rating_count'] ?? 0; ?> αξιολογήσεις)</span>
                </div>

                <?php if (isset($driverData['experience_years']) && $driverData['experience_years']) : ?>
                    <div class="experience-badge">
                        <img src="<?php echo BASE_URL; ?>img/experience_icon.png" alt="Εμπειρία">
                        <span><?php echo $driverData['experience_years']; ?> έτη εμπειρίας</span>
                    </div>
                <?php endif; ?>


            </div>

            <!-- Ενότητα Στατιστικών Προφίλ μεταφέρθηκε εδώ -->
            <div class="profile-stats-header">
                <h3>Στατιστικά Προφίλ</h3>
                <ul class="profile-stats">
                    <li>
                        <div class="stat-icon">
                            <img src="<?php echo BASE_URL; ?>img/view_icon.png" alt="Προβολές">
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo isset($driverStats['profile_views']) ? $driverStats['profile_views'] : '0'; ?></span>
                            <span class="stat-label">Προβολές Προφίλ</span>
                        </div>
                    </li>
                    <li>
                        <div class="stat-icon">
                            <img src="<?php echo BASE_URL; ?>img/application_icon.png" alt="Αιτήσεις">
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo isset($driverStats['applications']) ? $driverStats['applications'] : '0'; ?></span>
                            <span class="stat-label">Αιτήσεις για Θέσεις</span>
                        </div>
                    </li>
                    <li>
                        <div class="stat-icon">
                            <img src="<?php echo BASE_URL; ?>img/match_icon.png" alt="Ταιριάσματα">
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo isset($driverStats['matches']) ? $driverStats['matches'] : '0'; ?></span>
                            <span class="stat-label">Ταιριάσματα Εργασίας</span>
                        </div>
                    </li>
                </ul>
                <div class="profile-image-actions">
                    <a href="<?php echo BASE_URL; ?>drivers/edit-profile" class="btn-primary">Επεξεργασία Προφίλ</a>

                    <?php if (isset($driverData['resume_file']) && $driverData['resume_file']) : ?>
                        <a href="<?php echo BASE_URL . htmlspecialchars($driverData['resume_file']); ?>" class="btn-secondary" target="_blank">Προβολή Βιογραφικού</a>
                    <?php endif; ?>

                    <a href="<?php echo BASE_URL; ?>drivers/edit-resume" class="btn-secondary">Ενημέρωση Βιογραφικού</a>
                </div>
            </div>


        </div>

    </div>

    <?php if (isset($_SESSION['success_message'])) : ?>
        <div class="success-message">
            <?php echo $_SESSION['success_message']; ?>
            <?php unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])) : ?>
        <div class="error-message">
            <?php echo $_SESSION['error_message']; ?>
            <?php unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <!-- Καρτέλες (tabs) με περιεχόμενο προφίλ -->
    <div class="profile-tabs">
        <nav class="tabs-nav">
            <button class="tab-btn active" data-tab="overview">Επισκόπηση</button>
            <button class="tab-btn" data-tab="qualifications">Προσόντα & Πιστοποιήσεις</button>
            <button class="tab-btn" data-tab="self-assessment">Αξιολόγηση Οδηγού</button>
            <button class="tab-btn" data-tab="job-matches">Ταιριάσματα Εργασίας</button>
            <button class="tab-btn" data-tab="my-listings">Αγγελίες</button>
        </nav>

        <div class="tab-content">
            <!-- Καρτέλα Επισκόπησης -->
            <div class="tab-pane active" id="overview">
                <div class="profile-content">
                    <div class="profile-main">
                        <!-- Τμήμα Επαγγελματικά Προσόντα & Άδειες -->
                        <section class="profile-section">
                            <h2>Επαγγελματικά Προσόντα & Άδειες</h2>
                            <div class="qualifications-table">
                                <table class="driver-qualifications">
                                    <thead>
                                        <tr>
                                            <th>Τυπικά Προσόντα</th>
                                            <th>Λεπτομέρειες</th>
                                            <th>Ημερομηνία Λήξης</th>
                                            <th>Κατάσταση</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Άδεια Οδήγησης -->
                                        <tr>
                                            <td class="qualification-type">


                                                <span>Άδεια Οδήγησης</span>
                                            </td>
                                            <td>
                                                <?php if (isset($driverData['license_number']) && $driverData['license_number']) : ?>
                                                    <div class="license-details">
                                                        <div><strong>Αριθμός:</strong> <?php echo htmlspecialchars($driverData['license_number']); ?></div>
                                                        <div class="license-categories-summary">
                                                            <strong>Κατηγορίες:</strong>
                                                            <?php
                                                            if (isset($driverLicenseTypes) && !empty($driverLicenseTypes)) {
                                                                echo htmlspecialchars(implode(', ', $driverLicenseTypes));
                                                            } else {
                                                                echo "Δεν έχουν καταχωρηθεί";
                                                            }
                                                            ?>
                                                        </div>
                                                    </div>
                                                <?php else : ?>
                                                    <span class="not-available">Δεν έχει καταχωρηθεί</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                // Εύρεση της πιο πρόσφατης ημερομηνίας λήξης των αδειών οδήγησης
                                                $earliestExpiry = null;
                                                if (isset($driverLicenses) && !empty($driverLicenses)) {
                                                    foreach ($driverLicenses as $license) {
                                                        if (!empty($license['expiry_date'])) {
                                                            if ($earliestExpiry === null || strtotime($license['expiry_date']) < strtotime($earliestExpiry)) {
                                                                $earliestExpiry = $license['expiry_date'];
                                                            }
                                                        }
                                                    }
                                                }
                                                if ($earliestExpiry) :
                                                ?>
                                                    <span class="expiry-date"><?php echo date('d/m/Y', strtotime($earliestExpiry)); ?></span>
                                                <?php else : ?>
                                                    <span class="not-available">Δεν έχει οριστεί</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($earliestExpiry) :
                                                    $isExpired = strtotime($earliestExpiry) < time();
                                                    $expiresInThreeMonths = !$isExpired && (strtotime($earliestExpiry) - time()) < 60 * 60 * 24 * 90;
                                                ?>
                                                    <span class="status-indicator <?php echo $isExpired ? 'expired' : ($expiresInThreeMonths ? 'expiring-soon' : 'valid'); ?>">
                                                        <?php
                                                        if ($isExpired) {
                                                            echo "Έχει λήξει";
                                                        } elseif ($expiresInThreeMonths) {
                                                            echo "Λήγει σύντομα";
                                                        } else {
                                                            echo "Έγκυρη";
                                                        }
                                                        ?>
                                                    </span>
                                                <?php else : ?>
                                                    <span class="not-available">Άγνωστο</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <!-- ΠΕΙ Εμπορευμάτων -->
                                        <tr>
                                            <td class="qualification-type">

                                                <span>ΠΕΙ Εμπορευμάτων</span>
                                            </td>
                                            <td>
                                                <?php if ($hasPeiC) : ?>
                                                    <div><strong>Αριθμός:</strong> <?php echo $driverData['pei_c_number'] ?? '95/' ?> <?php if ($peiCExpiryDate) : ?>
                                                            <span class="expiry-date"><?php echo date('d-m-Y', strtotime($peiCExpiryDate)); ?></span>
                                                        <?php else : ?>
                                                            <span class="not-available">Δεν έχει οριστεί</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div><strong>Κατηγορία:</strong> Εμπορευμάτων</div>
                                                <?php else : ?>
                                                    <span class="not-available">Δεν διαθέτει</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($peiCExpiryDate) : ?>
                                                    <span class="expiry-date"><?php echo date('d/m/Y', strtotime($peiCExpiryDate)); ?></span>
                                                <?php else : ?>
                                                    <span class="not-available">Δεν έχει οριστεί</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($peiCExpiryDate) :
                                                    $isExpired = strtotime($peiCExpiryDate) < time();
                                                    $expiresInThreeMonths = !$isExpired && (strtotime($peiCExpiryDate) - time()) < 60 * 60 * 24 * 90;
                                                ?>
                                                    <span class="status-indicator <?php echo $isExpired ? 'expired' : ($expiresInThreeMonths ? 'expiring-soon' : 'valid'); ?>">
                                                        <?php
                                                        if ($isExpired) {
                                                            echo "Έχει λήξει";
                                                        } elseif ($expiresInThreeMonths) {
                                                            echo "Λήγει σύντομα";
                                                        } else {
                                                            echo "Έγκυρο";
                                                        }
                                                        ?>
                                                    </span>
                                                <?php else : ?>
                                                    <span class="not-available">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <!-- ΠΕΙ Επιβατών -->
                                        <tr>
                                            <td class="qualification-type">

                                                <span>ΠΕΙ Επιβατών</span>
                                            </td>
                                            <td>
                                                <?php if ($hasPeiD) : ?>
                                                    <div><strong>Αριθμός:</strong> <?php echo $driverData['pei_d_number'] ?? '95/' ?> <?php if ($peiDExpiryDate) : ?>
                                                            <span class="expiry-date"><?php echo date('d/m/Y', strtotime($peiDExpiryDate)); ?></span>
                                                        <?php else : ?>
                                                            <span class="not-available">Δεν έχει οριστεί</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div><strong>Κατηγορία:</strong> Επιβατών</div>
                                                <?php else : ?>
                                                    <span class="not-available">Δεν διαθέτει</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($peiDExpiryDate) : ?>
                                                    <span class="expiry-date"><?php echo date('d/m/Y', strtotime($peiDExpiryDate)); ?></span>
                                                <?php else : ?>
                                                    <span class="not-available">Δεν έχει οριστεί</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($peiDExpiryDate) :
                                                    $isExpired = strtotime($peiDExpiryDate) < time();
                                                    $expiresInThreeMonths = !$isExpired && (strtotime($peiDExpiryDate) - time()) < 60 * 60 * 24 * 90;
                                                ?>
                                                    <span class="status-indicator <?php echo $isExpired ? 'expired' : ($expiresInThreeMonths ? 'expiring-soon' : 'valid'); ?>">
                                                        <?php
                                                        if ($isExpired) {
                                                            echo "Έχει λήξει";
                                                        } elseif ($expiresInThreeMonths) {
                                                            echo "Λήγει σύντομα";
                                                        } else {
                                                            echo "Έγκυρο";
                                                        }
                                                        ?>
                                                    </span>
                                                <?php else : ?>
                                                    <span class="not-available">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <!-- ADR -->
                                        <tr>
                                            <td class="qualification-type">

                                                <span>Πιστοποιητικό ADR</span>
                                            </td>
                                            <td>
                                                <?php if (isset($driverADR) && $driverADR): ?>
                                                    <div><strong>Αριθμός:</strong> <?php echo htmlspecialchars($driverADR['certificate_number'] ?? 'Εγγεγραμμένο'); ?></div>
                                                    <?php if (!empty($driverADR['adr_type'])): ?>
                                                        <div><strong>Κατηγορία:</strong> <?php echo htmlspecialchars($driverADR['adr_type']); ?></div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="not-available">Δεν διαθέτει</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($driverADR['expiry_date']) && $driverADR['expiry_date']) : ?>
                                                    <span class="expiry-date"><?php echo date('d/m/Y', strtotime($driverADR['expiry_date'])); ?></span>
                                                <?php else : ?>
                                                    <span class="not-available">Δεν έχει οριστεί</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($driverADR['expiry_date']) && $driverADR['expiry_date']) :
                                                    $isExpired = strtotime($driverADR['expiry_date']) < time();
                                                    $expiresInThreeMonths = !$isExpired && (strtotime($driverADR['expiry_date']) - time()) < 60 * 60 * 24 * 90;
                                                ?>
                                                    <span class="status-indicator <?php echo $isExpired ? 'expired' : ($expiresInThreeMonths ? 'expiring-soon' : 'valid'); ?>">
                                                        <?php
                                                        if ($isExpired) {
                                                            echo "Έχει λήξει";
                                                        } elseif ($expiresInThreeMonths) {
                                                            echo "Λήγει σύντομα";
                                                        } else {
                                                            echo "Έγκυρο";
                                                        }
                                                        ?>
                                                    </span>
                                                <?php else : ?>
                                                    <span class="not-available">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <!-- Κάρτα Ταχογράφου -->
                                        <tr>
                                            <td class="qualification-type">

                                                <span>Κάρτα Ταχογράφου</span>
                                            </td>
                                            <td>
                                                <?php if (isset($driverTachograph) && $driverTachograph) : ?>
                                                    <div><strong>Αριθμός:</strong> <?php echo htmlspecialchars($driverTachograph['card_number'] ?? 'Εγγεγραμμένο'); ?></div>
                                                    <div><strong>Κατηγορία:</strong> Ψηφιακή κάρτα ταχογράφου</div>
                                                <?php else : ?>
                                                    <span class="not-available">Δεν διαθέτει</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($driverTachograph['expiry_date']) && $driverTachograph['expiry_date']) : ?>
                                                    <span class="expiry-date"><?php echo date('d/m/Y', strtotime($driverTachograph['expiry_date'])); ?></span>
                                                <?php else : ?>
                                                    <span class="not-available">Δεν έχει οριστεί</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($driverTachograph['expiry_date']) && $driverTachograph['expiry_date']) :
                                                    $isExpired = strtotime($driverTachograph['expiry_date']) < time();
                                                    $expiresInThreeMonths = !$isExpired && (strtotime($driverTachograph['expiry_date']) - time()) < 60 * 60 * 24 * 90;
                                                ?>
                                                    <span class="status-indicator <?php echo $isExpired ? 'expired' : ($expiresInThreeMonths ? 'expiring-soon' : 'valid'); ?>">
                                                        <?php
                                                        if ($isExpired) {
                                                            echo "Έχει λήξει";
                                                        } elseif ($expiresInThreeMonths) {
                                                            echo "Λήγει σύντομα";
                                                        } else {
                                                            echo "Έγκυρη";
                                                        }
                                                        ?>
                                                    </span>
                                                <?php else : ?>
                                                    <span class="not-available">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <!-- Άδεια Χειριστή Μηχανημάτων Έργου -->
                                        <tr>
                                            <td class="qualification-type">
                                                <span>Άδεια Χειριστή</span>
                                            </td>
                                            <td>
                                                <?php if (isset($operatorSubSpecialities) && !empty($operatorSubSpecialities)) : ?>
                                                    <div class="operator-subspecialities">
                                                        <strong>Υποειδικότητες & Ομάδες:</strong>
                                                        <?php
                                                        // Ομαδοποίηση των υποειδικοτήτων ανά ειδικότητα και αφαίρεση διπλοτύπων
                                                        $specialityGroups = [];
                                                        $processedSubSpecialities = []; // Για την αποφυγή διπλοτύπων

                                                        foreach ($operatorSubSpecialities as $subSpec) {
                                                            $specialityId = substr($subSpec['sub_speciality'], 0, 1);
                                                            $key = $subSpec['sub_speciality']; // Κλειδί για έλεγχο διπλοτύπων

                                                            // Έλεγχος αν έχουμε ήδη επεξεργαστεί αυτή την υποειδικότητα
                                                            if (in_array($key, $processedSubSpecialities)) {
                                                                continue;
                                                            }

                                                            // Προσθήκη στο σύνολο των επεξεργασμένων υποειδικοτήτων
                                                            $processedSubSpecialities[] = $key;

                                                            // Προσθήκη στην κατάλληλη ομάδα
                                                            if (!isset($specialityGroups[$specialityId])) {
                                                                $specialityGroups[$specialityId] = [];
                                                            }
                                                            $specialityGroups[$specialityId][] = $subSpec;
                                                        }

                                                        // Ορισμός των ονομάτων ειδικοτήτων
                                                        $specialityNames = [
                                                            '1' => 'Εργασίες εκσκαφής και χωματουργικές',
                                                            '2' => 'Εργασίες ανύψωσης και μεταφοράς φορτίων',
                                                            '3' => 'Εργασίες οδοστρωσίας',
                                                            '4' => 'Εργασίες εξυπηρέτησης οδών και αεροδρομίων',
                                                            '5' => 'Εργασίες υπόγειων έργων και μεταλλείων',
                                                            '6' => 'Εργασίες έλξης',
                                                            '7' => 'Εργασίες διάτρησης και κοπής εδαφών',
                                                            '8' => 'Ειδικές εργασίες ανύψωσης'
                                                        ];
                                                        ?>
                                                        <div class="subspecialities-groups">
                                                            <?php foreach ($specialityGroups as $specialityId => $subSpecialities) : ?>
                                                                <div class="speciality-group">
                                                                    <h6><?php echo $specialityId . ' - ' . ($specialityNames[$specialityId] ?? 'Ειδικότητα ' . $specialityId); ?></h6>
                                                                    <ul class="selected-subspecialities">
                                                                        <?php foreach ($subSpecialities as $subSpec) :
                                                                            $subspecialityId = $subSpec['sub_speciality'];
                                                                            $groupType = $subSpec['group_type'] ?? 'A';
                                                                        ?>
                                                                            <li class="subspeciality-item">
                                                                                <span class="subspeciality-code"><?php echo htmlspecialchars($subspecialityId); ?></span>
                                                                                <?php if (isset($subSpec['name']) && $subSpec['name']) : ?>
                                                                                    <span class="subspeciality-name"><?php echo htmlspecialchars($subSpec['name']); ?></span>
                                                                                <?php else : ?>
                                                                                    <?php
                                                                                    // Αν δεν υπάρχει το όνομα, χρησιμοποιούμε τη συνάρτηση getSubSpecialityName του Controller
                                                                                    $name = isset($this) && method_exists($this, 'getSubSpecialityName')
                                                                                        ? $this->getSubSpecialityName($subspecialityI
