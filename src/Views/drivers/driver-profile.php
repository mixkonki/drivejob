<?php
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';
?>
<!-- Σύνδεση με το CSS αρχείο του προφίλ οδηγού -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver_profile.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver-skills-css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/expiring-licenses-css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver-rating-profile.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/toggle-switch.css">


<script>
    // Ορισμός των βασικών μεταβλητών
    const BASE_URL = '<?php echo BASE_URL; ?>';
</script>
<script src="<?php echo BASE_URL; ?>js/driver_profile.js"></script>
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
                                                                                        ? $this->getSubSpecialityName($subspecialityId)
                                                                                        : "Υποειδικότητα {$subspecialityId}";
                                                                                    ?>
                                                                                    <span class="subspeciality-name"><?php echo htmlspecialchars($name); ?></span>
                                                                                <?php endif; ?>
                                                                                <span class="subspeciality-group">(Ομάδα <?php echo htmlspecialchars($groupType); ?>)</span>
                                                                            </li>
                                                                        <?php endforeach; ?>
                                                                    </ul>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php else : ?>
                                                    <span class="not-available">Δεν έχουν καταχωρηθεί υποειδικότητες</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($driverOperator) && isset($driverOperator['expiry_date']) && $driverOperator['expiry_date']) : ?>
                                                    <span class="expiry-date"><?php echo date('d/m/Y', strtotime($driverOperator['expiry_date'])); ?></span>
                                                <?php else : ?>
                                                    <span class="not-available">Δεν έχει οριστεί</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($driverOperator) && isset($driverOperator['expiry_date']) && $driverOperator['expiry_date']) :
                                                    $isExpired = strtotime($driverOperator['expiry_date']) < time();
                                                    $expiresInThreeMonths = !$isExpired && (strtotime($driverOperator['expiry_date']) - time()) < 60 * 60 * 24 * 90;
                                                ?>
                                                    <span class="status-indicator <?php echo $isExpired ? 'expired' : ($expiresInThreeMonths ? 'expiring-soon' : 'valid'); ?>">
                                                        <?php
                                                        if ($isExpired) {
                                                            echo "Μη έγκυρη";
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

                                        <!-- Ειδικές Άδειες -->
                                        <tr>
                                            <td class="qualification-type">
                                                <span>Ειδικές Άδειες</span>
                                            </td>
                                            <td>
                                                <?php
                                                // Ελέγχουμε αν υπάρχουν ειδικές άδειες
                                                if (isset($driverSpecialLicenses) && !empty($driverSpecialLicenses)) :
                                                ?>
                                                    <div class="special-licenses-list">
                                                        <ul>
                                                            <?php foreach ($driverSpecialLicenses as $specialLicense) : ?>
                                                                <li>
                                                                    <strong><?php echo htmlspecialchars($specialLicense['license_type']); ?></strong>
                                                                    <?php if (!empty($specialLicense['license_number'])) : ?>
                                                                        - Αρ: <?php echo htmlspecialchars($specialLicense['license_number']); ?>
                                                                    <?php endif; ?>
                                                                    <?php if (!empty($specialLicense['details'])) : ?>
                                                                        <div class="special-license-details">
                                                                            <?php echo htmlspecialchars($specialLicense['details']); ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    </div>
                                                <?php else : ?>
                                                    <span class="not-available">Δεν έχουν καταχωρηθεί ειδικές άδειες</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                // Εμφάνιση της ημερομηνίας λήξης της πρώτης ειδικής άδειας (αν υπάρχει)
                                                if (isset($driverSpecialLicenses) && !empty($driverSpecialLicenses) && !empty($driverSpecialLicenses[0]['expiry_date'])) :
                                                ?>
                                                    <span class="expiry-date"><?php echo date('d/m/Y', strtotime($driverSpecialLicenses[0]['expiry_date'])); ?></span>
                                                <?php else : ?>
                                                    <span class="not-available">Δεν έχει οριστεί</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                // Έλεγχος εγκυρότητας της πρώτης ειδικής άδειας (αν υπάρχει)
                                                if (isset($driverSpecialLicenses) && !empty($driverSpecialLicenses) && !empty($driverSpecialLicenses[0]['expiry_date'])) :
                                                    $isExpired = strtotime($driverSpecialLicenses[0]['expiry_date']) < time();
                                                    $expiresInThreeMonths = !$isExpired && (strtotime($driverSpecialLicenses[0]['expiry_date']) - time()) < 60 * 60 * 24 * 90;
                                                ?>
                                                    <span class="status-indicator <?php echo $isExpired ? 'expired' : ($expiresInThreeMonths ? 'expiring-soon' : 'valid'); ?>">
                                                        <?php
                                                        if ($isExpired) {
                                                            echo "Μη έγκυρη";
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


                                    </tbody>
                                </table>
                            </div>
                        </section>


                    </div>

                    <div class="profile-sidebar">
                        <!-- Ενότητα Διαθεσιμότητας -->
                        <section class="profile-section availability-section">
                            <h3>Κατάσταση Διαθεσιμότητας</h3>
                            <div class="availability-status <?php echo $driverData['available_for_work'] ? 'available' : 'unavailable'; ?>">
                                <span class="status-icon"></span>
                                <span class="status-text">
                                    <?php echo $driverData['available_for_work'] ? 'Διαθέσιμος/η για εργασία' : 'Μη διαθέσιμος/η για εργασία'; ?>
                                </span>
                            </div>
                            <p class="availability-note">Μπορείτε να αλλάξετε την κατάσταση διαθεσιμότητάς σας από την <a href="<?php echo BASE_URL; ?>drivers/edit-profile">επεξεργασία προφίλ</a>.</p>
                        </section>
                        <!-- Τμήμα Σχετικά με εμένα -->
                        <section class="profile-section">
                            <h2>Σχετικά με εμένα</h2>
                            <div class="profile-about">
                                <?php if (isset($driverData['about_me']) && $driverData['about_me']) : ?>
                                    <?php echo nl2br(htmlspecialchars($driverData['about_me'])); ?>
                                <?php else : ?>
                                    <p class="profile-empty">Δεν έχετε προσθέσει πληροφορίες για τον εαυτό σας. <a href="<?php echo BASE_URL; ?>drivers/edit-profile">Προσθέστε τώρα!</a></p>
                                <?php endif; ?>
                            </div>
                        </section>
                        <!-- Ενότητα Στοιχείων Επικοινωνίας -->
                        <section class="profile-section">
                            <h2>Στοιχεία Επικοινωνίας</h2>
                            <ul class="contact-list">
                                <li>
                                    <img src="<?php echo BASE_URL; ?>img/email_icon.png" alt="Email">
                                    <span><?php echo htmlspecialchars($driverData['email']); ?></span>
                                </li>
                                <li>
                                    <img src="<?php echo BASE_URL; ?>img/phone_icon.png" alt="Τηλέφωνο">
                                    <span><?php echo htmlspecialchars($driverData['phone']); ?></span>
                                </li>
                                <?php if (isset($driverData['landline']) && $driverData['landline']) : ?>
                                    <li>
                                        <img src="<?php echo BASE_URL; ?>img/landline_icon.png" alt="Σταθερό Τηλέφωνο">
                                        <span><?php echo htmlspecialchars($driverData['landline']); ?></span>
                                    </li>
                                <?php endif; ?>
                                <?php if (isset($driverData['social_linkedin']) && $driverData['social_linkedin']) : ?>
                                    <li>
                                        <img src="<?php echo BASE_URL; ?>img/linkedin_icon.png" alt="LinkedIn">
                                        <a href="<?php echo htmlspecialchars($driverData['social_linkedin']); ?>" target="_blank">LinkedIn Προφίλ</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </section>
                        <!-- Ενότητα Τοποθεσίας -->
                        <?php if (isset($driverData['address']) && $driverData['address'] && isset($driverData['city']) && $driverData['city']) : ?>
                            <section class="profile-section">
                                <div class="location-details">
                                    <div class="location-address">
                                        <h2>Τοποθεσία: <span><?php echo htmlspecialchars($driverData['address'] . ', ' . $driverData['city'] . ', ' . $driverData['country']); ?></span></h2>



                                    </div>
                                </div>
                                <div class="profile-map">
                                    <iframe
                                        width="100%"
                                        height="200"
                                        frameborder="0"
                                        scrolling="no"
                                        marginheight="0"
                                        marginwidth="0"
                                        src="https://maps.google.com/maps?q=<?php echo urlencode($driverData['address'] . ', ' . $driverData['city'] . ', ' . $driverData['country']); ?>&output=embed"></iframe>
                                </div>
                            </section>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

            <!-- Καρτέλα Προσόντων & Πιστοποιήσεων -->
            <div class="tab-pane" id="qualifications">
                <!-- Προσωπικά Στοιχεία -->
                <div class="personal-details-section">
                    <h3>Προσωπικά Στοιχεία</h3>
                    <div class="skills-categories">
                        <!-- Ηλικία -->
                        <div class="skills-category">
                            <div class="category-header">
                                <div class="category-icon">
                                    <img src="<?php echo BASE_URL; ?>img/user_icon.png" alt="Ηλικία">
                                </div>
                                <h3>Ηλικία</h3>
                            </div>
                            <?php if (isset($driverData['birth_date']) && $driverData['birth_date']) :
                                $birthDate = new DateTime($driverData['birth_date']);
                                $now = new DateTime();
                                $age = $birthDate->diff($now)->y;
                            ?>
                                <div class="skill-tag"><?php echo $age; ?> ετών</div>
                            <?php else : ?>
                                <p class="no-skills">Δεν έχει καταχωρηθεί</p>
                            <?php endif; ?>
                        </div>

                        <!-- Οικογενειακή Κατάσταση -->
                        <div class="skills-category">
                            <div class="category-header">
                                <div class="category-icon">
                                    <img src="<?php echo BASE_URL; ?>img/profile_icon.png" alt="Οικογενειακή Κατάσταση">
                                </div>
                                <h3>Οικογενειακή Κατάσταση</h3>
                            </div>
                            <?php if (isset($driverData['marital_status']) && $driverData['marital_status']) :
                                $maritalStatus = [
                                    'single' => 'Άγαμος/η',
                                    'married' => 'Έγγαμος/η',
                                    'divorced' => 'Διαζευγμένος/η',
                                    'widowed' => 'Χήρος/α',
                                    'civil_partnership' => 'Σύμφωνο συμβίωσης',
                                    'no_answer' => 'Δεν απαντώ'
                                ];
                                $statusText = isset($maritalStatus[$driverData['marital_status']]) ?
                                    $maritalStatus[$driverData['marital_status']] : $driverData['marital_status'];
                            ?>
                                <div class="skill-tag"><?php echo $statusText; ?></div>
                            <?php else : ?>
                                <p class="no-skills">Δεν έχει καταχωρηθεί</p>
                            <?php endif; ?>
                        </div>

                        <!-- Επίπεδο Εκπαίδευσης -->
                        <div class="skills-category">
                            <div class="category-header">
                                <div class="category-icon">
                                    <img src="<?php echo BASE_URL; ?>img/training_icon.png" alt="Επίπεδο Εκπαίδευσης">
                                </div>
                                <h3>Επίπεδο Εκπαίδευσης</h3>
                            </div>
                            <?php if (isset($driverData['education_level']) && $driverData['education_level']) :
                                $educationLevels = [
                                    'primary' => 'Υποχρεωτική εκπαίδευση (Δημοτικό)',
                                    'secondary_low' => 'Υποχρεωτική εκπαίδευση (Γυμνάσιο)',
                                    'secondary_high' => 'Λύκειο',
                                    'vocational_low' => 'Επαγγελματική Εκπαίδευση (Γυμνάσιο)',
                                    'vocational' => 'Επαγγελματική Εκπαίδευση (Λύκειο)',
                                    'iek' => 'Ινστιτούτο Επαγγελματικής Κατάρισης (ΙΕΚ)',
                                    'tei' => 'Ανώτατο Τεχνολογικό Εκπαιδευτικό Ίδρυμα (ΑΤΕΙ)',
                                    'university' => 'Ανώτατο Εκπαιδευτικό Ίδρυμα (ΑΕΙ)',
                                    'postgraduate' => 'Μεταπτυχιακό',
                                    'doctorate' => 'Διδακτορικό',
                                    'no_answer' => 'Δεν απαντώ',
                                ];
                                $educationText = isset($educationLevels[$driverData['education_level']]) ?
                                    $educationLevels[$driverData['education_level']] : $driverData['education_level'];
                            ?>
                                <div class="skill-tag"><?php echo $educationText; ?></div>
                            <?php else : ?>
                                <p class="no-skills">Δεν έχει καταχωρηθεί</p>
                            <?php endif; ?>
                        </div>

                        <!-- Στρατιωτικές Υποχρεώσεις -->
                        <div class="skills-category">
                            <div class="category-header">
                                <div class="category-icon">
                                    <img src="<?php echo BASE_URL; ?>img/license_icon.png" alt="Στρατιωτικές Υποχρεώσεις">
                                </div>
                                <h3>Στρατιωτικές Υποχρεώσεις</h3>
                            </div>
                            <?php if (isset($driverData['military_service']) && $driverData['military_service']) :
                                $militaryStatus = [
                                    'completed' => 'Εκπληρωμένες',
                                    'exempt' => 'Απαλλαγή',
                                    'postponed' => 'Αναβολή',
                                    'unfulfilled' => 'Μη εκπληρωμένες',
                                    'not_applicable' => 'Δεν απαιτείται',
                                    'no_answer' => 'Δεν απαντώ'
                                ];
                                $militaryText = isset($militaryStatus[$driverData['military_service']]) ?
                                    $militaryStatus[$driverData['military_service']] : $driverData['military_service'];
                            ?>
                                <div class="skill-tag"><?php echo $militaryText; ?></div>
                            <?php else : ?>
                                <p class="no-skills">Δεν έχει καταχωρηθεί</p>
                            <?php endif; ?>
                        </div>

                        <!-- Ποινικό Μητρώο -->
                        <div class="skills-category">
                            <div class="category-header">
                                <div class="category-icon">
                                    <img src="<?php echo BASE_URL; ?>img/safety_icon.png" alt="Ποινικό Μητρώο">
                                </div>
                                <h3>Ποινικό Μητρώο</h3>
                            </div>
                            <?php if (isset($driverData['legal_status']) && $driverData['legal_status']) : ?>
                                <div class="skill-tag"><?php echo ($driverData['legal_status'] == 'yes') ? 'Διαθέσιμο Ποινικό Μητρώο' : 'Όχι'; ?></div>
                            <?php else : ?>
                                <p class="no-skills">Δεν είναι διαθέσιμο</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="driver-skills-section">
                    <h3>Δεξιότητες Οδηγού</h3>
                    <div class="skills-summary">
                        <div class="skills-categories">
                            <!-- Οδηγικές Ικανότητες -->
                            <div class="skills-category">
                                <div class="category-header">
                                    <div class="category-icon">
                                        <img src="<?php echo BASE_URL; ?>img/driving_skills_icon.png" alt="Οδηγικές Ικανότητες">
                                    </div>
                                    <h3>Οδηγικές Ικανότητες</h3>
                                </div>

                                <?php
                                $drivingSkills = [
                                    'defensive_driving' => 'Αμυντική Οδήγηση',
                                    'eco_driving' => 'Οικολογική Οδήγηση',
                                    'night_driving' => 'Νυχτερινή Οδήγηση',
                                    'mountain_driving' => 'Οδήγηση σε Ορεινές Περιοχές',
                                    'extreme_conditions' => 'Οδήγηση σε Ακραίες Συνθήκες',
                                    'precision_handling' => 'Ακρίβεια Χειρισμών'
                                ];

                                $hasDrivingSkills = false;
                                foreach ($drivingSkills as $skill => $label) {
                                    if (isset($driverSkills[$skill]) && $driverSkills[$skill]) {
                                        $hasDrivingSkills = true;
                                        break;
                                    }
                                }

                                if ($hasDrivingSkills) :
                                    foreach ($drivingSkills as $skill => $label) :
                                        if (isset($driverSkills[$skill]) && $driverSkills[$skill]) :
                                ?>
                                            <div class="skill-tag"><?php echo $label; ?></div>
                                    <?php
                                        endif;
                                    endforeach;
                                else :
                                    ?>
                                    <p class="no-skills">Δεν έχουν καταχωρηθεί δεξιότητες</p>
                                <?php endif; ?>
                            </div>

                            <!-- Ασφάλεια & Συμμόρφωση -->
                            <div class="skills-category">
                                <div class="category-header">
                                    <div class="category-icon">
                                        <img src="<?php echo BASE_URL; ?>img/safety_icon.png" alt="Ασφάλεια & Συμμόρφωση">
                                    </div>
                                    <h3>Ασφάλεια & Συμμόρφωση</h3>
                                </div>

                                <?php
                                $safetySkills = [
                                    'loading_securing' => 'Φόρτωση & Ασφάλιση Φορτίου',
                                    'emergency_response' => 'Αντιμετώπιση Έκτακτων Καταστάσεων',
                                    'first_aid' => 'Πρώτες Βοήθειες',
                                    'dangerous_goods' => 'Διαχείριση Επικίνδυνων Εμπορευμάτων',
                                    'tacograph_compliance' => 'Συμμόρφωση με Ταχογράφο',
                                    'fire_safety' => 'Πυρασφάλεια',
                                    'vehicle_inspection' => 'Έλεγχος Οχημάτων'
                                ];

                                $hasSafetySkills = false;
                                foreach ($safetySkills as $skill => $label) {
                                    if (isset($driverSkills[$skill]) && $driverSkills[$skill]) {
                                        $hasSafetySkills = true;
                                        break;
                                    }
                                }

                                if ($hasSafetySkills) :
                                    foreach ($safetySkills as $skill => $label) :
                                        if (isset($driverSkills[$skill]) && $driverSkills[$skill]) :
                                ?>
                                            <div class="skill-tag"><?php echo $label; ?></div>
                                    <?php
                                        endif;
                                    endforeach;
                                else :
                                    ?>
                                    <p class="no-skills">Δεν έχουν καταχωρηθεί δεξιότητες</p>
                                <?php endif; ?>
                            </div>

                            <!-- Επαγγελματισμός -->
                            <div class="skills-category">
                                <div class="category-header">
                                    <div class="category-icon">
                                        <img src="<?php echo BASE_URL; ?>img/professionalism_icon.png" alt="Επαγγελματισμός">
                                    </div>
                                    <h3>Επαγγελματισμός</h3>
                                </div>

                                <?php
                                $professionalSkills = [
                                    'customer_service' => 'Εξυπηρέτηση Πελατών',
                                    'time_management' => 'Διαχείριση Χρόνου',
                                    'route_planning' => 'Σχεδιασμός Διαδρομής',
                                    'conflict_resolution' => 'Επίλυση Συγκρούσεων',
                                    'multilingual' => 'Πολύγλωσσος',
                                    'report_writing' => 'Σύνταξη Αναφορών',
                                    'inspection_behavior' => 'Συμπεριφορά σε Έλεγχο',
                                    'border_crossing' => 'Διέλευση Συνόρων'
                                ];

                                $hasProfessionalSkills = false;
                                foreach ($professionalSkills as $skill => $label) {
                                    if (isset($driverSkills[$skill]) && $driverSkills[$skill]) {
                                        $hasProfessionalSkills = true;
                                        break;
                                    }
                                }

                                if ($hasProfessionalSkills) :
                                    foreach ($professionalSkills as $skill => $label) :
                                        if (isset($driverSkills[$skill]) && $driverSkills[$skill]) :
                                ?>
                                            <div class="skill-tag"><?php echo $label; ?></div>
                                    <?php
                                        endif;
                                    endforeach;
                                else :
                                    ?>
                                    <p class="no-skills">Δεν έχουν καταχωρηθεί δεξιότητες</p>
                                <?php endif; ?>
                            </div>

                            <!-- Τεχνικές Γνώσεις -->
                            <div class="skills-category">
                                <div class="category-header">
                                    <div class="category-icon">
                                        <img src="<?php echo BASE_URL; ?>img/technical_icon.png" alt="Τεχνικές Γνώσεις">
                                    </div>
                                    <h3>Τεχνικές Γνώσεις</h3>
                                </div>

                                <?php
                                $technicalSkills = [
                                    'vehicle_maintenance' => 'Συντήρηση Οχήματος',
                                    'troubleshooting' => 'Αντιμετώπιση Βλαβών',
                                    'digital_tachograph' => 'Ψηφιακός Ταχογράφος',
                                    'gps_systems' => 'Συστήματα GPS',
                                    'logistics_software' => 'Λογισμικό Logistics',
                                    'technical_terms' => 'Γνώση Τεχνικών Όρων',
                                    'equipment_handling' => 'Γνώση Χειρισμού Εξοπλισμού',
                                    'checklists_usage' => 'Χρήση Λιστών Ελέγχου'
                                ];

                                $hasTechnicalSkills = false;
                                foreach ($technicalSkills as $skill => $label) {
                                    if (isset($driverSkills[$skill]) && $driverSkills[$skill]) {
                                        $hasTechnicalSkills = true;
                                        break;
                                    }
                                }

                                if ($hasTechnicalSkills) :
                                    foreach ($technicalSkills as $skill => $label) :
                                        if (isset($driverSkills[$skill]) && $driverSkills[$skill]) :
                                ?>
                                            <div class="skill-tag"><?php echo $label; ?></div>
                                    <?php
                                        endif;
                                    endforeach;
                                else :
                                    ?>
                                    <p class="no-skills">Δεν έχουν καταχωρηθεί δεξιότητες</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (isset($driverData['additional_skills']) && $driverData['additional_skills']) : ?>
                            <div class="additional-skills">
                                <h3>Επιπλέον Δεξιότητες</h3>
                                <div class="additional-skills-content">
                                    <?php echo nl2br(htmlspecialchars($driverData['additional_skills'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Προϋπηρεσία σε Οχήματα -->
                <?php if (isset($driverVehicleExperience) && !empty($driverVehicleExperience)) : ?>
                    <div class="vehicle-experience-section">
                        <h3>Προϋπηρεσία σε Οχήματα</h3>
                        <div class="vehicle-experience-list">
                            <?php foreach ($driverVehicleExperience as $exp) : ?>
                                <div class="vehicle-experience-item">
                                    <div class="vehicle-experience-header">
                                        <div class="vehicle-title">
                                            <?php
                                            // Κατηγορίες οχημάτων
                                            $vehicleCategories = [
                                                'lcv' => 'Ελαφρά Επαγγελματικά Οχήματα',
                                                'rigid_truck' => 'Μεσαία & Βαρέα Φορτηγά',
                                                'articulated' => 'Αρθρωτά/Συρόμενα Οχήματα',
                                                'taxi' => 'Ταξί',
                                                'minibus' => 'Μικρό Λεωφορείο',
                                                'bus' => 'Λεωφορεία & Πούλμαν',
                                                'utility' => 'Οχήματα Δημοτικά/Κοινής Ωφέλειας',
                                                'construction' => 'Οχήματα Έργων/Κατασκευών',
                                                'emergency' => 'Οχήματα Έκτακτης Ανάγκης',
                                                'specialized' => 'Εξειδικευμένα Οχήματα'
                                            ];

                                            $categoryName = isset($vehicleCategories[$exp['vehicle_category']]) ? $vehicleCategories[$exp['vehicle_category']] : $exp['vehicle_category'];
                                            echo $categoryName;

                                            if (!empty($exp['vehicle_type_name'])) {
                                                echo ' - ' . htmlspecialchars($exp['vehicle_type_name']);
                                            } elseif (!empty($exp['vehicle_type'])) {
                                                echo ' - ' . htmlspecialchars($exp['vehicle_type']);
                                            }
                                            ?>
                                        </div>
                                        <div class="vehicle-years"><?php echo $exp['years']; ?> <?php echo $exp['years'] == 1 ? 'έτος' : 'έτη'; ?></div>
                                    </div>

                                    <?php if (!empty($exp['start_date']) || !empty($exp['end_date'])) : ?>
                                        <div class="vehicle-period">
                                            Περίοδος:
                                            <?php
                                            echo !empty($exp['start_date']) ? date('m/Y', strtotime($exp['start_date'])) : '-';
                                            echo ' έως ';
                                            echo !empty($exp['end_date']) ? date('m/Y', strtotime($exp['end_date'])) : 'σήμερα';
                                            ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($exp['description'])) : ?>
                                        <div class="vehicle-description">
                                            <?php echo nl2br(htmlspecialchars($exp['description'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="languages-section">
                    <h3>Γλωσσικές Ικανότητες</h3>
                    <div class="languages-list">
                        <?php
                        $languageLabels = [
                            'greek' => 'Ελληνικά',
                            'english' => 'Αγγλικά',
                            'german' => 'Γερμανικά',
                            'french' => 'Γαλλικά',
                            'italian' => 'Ιταλικά'
                        ];

                        $languageLevelLabels = [
                            'native' => 'Μητρική Γλώσσα',
                            'fluent' => 'Άριστα',
                            'good' => 'Καλά',
                            'basic' => 'Βασικά'
                        ];

                        $languageLevelClasses = [
                            'native' => 'native',
                            'fluent' => 'fluent',
                            'good' => 'good',
                            'basic' => 'basic'
                        ];

                        foreach ($languageLabels as $key => $label) :
                            $dbField = 'language_' . $key;
                            if (isset($driverData[$dbField]) && $driverData[$dbField]) :
                                $level = $driverData[$dbField];
                                $levelLabel = isset($languageLevelLabels[$level]) ? $languageLevelLabels[$level] : $level;
                                $levelClass = isset($languageLevelClasses[$level]) ? $languageLevelClasses[$level] : '';
                        ?>
                                <div class="language-item">
                                    <div class="language-name"><?php echo $label; ?></div>
                                    <div class="language-level <?php echo $levelClass; ?>"><?php echo $levelLabel; ?></div>
                                </div>
                            <?php
                            endif;
                        endforeach;

                        // Προσθήκη άλλης γλώσσας αν έχει οριστεί
                        if (isset($driverData['language_other_name']) && $driverData['language_other_name']) :
                            $otherLevel = $driverData['language_other_level'] ?? 'basic';
                            $otherLevelLabel = isset($languageLevelLabels[$otherLevel]) ? $languageLevelLabels[$otherLevel] : $otherLevel;
                            $otherLevelClass = isset($languageLevelClasses[$otherLevel]) ? $languageLevelClasses[$otherLevel] : '';
                            ?>
                            <div class="language-item">
                                <div class="language-name"><?php echo htmlspecialchars($driverData['language_other_name']); ?></div>
                                <div class="language-level <?php echo $otherLevelClass; ?>"><?php echo $otherLevelLabel; ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (isset($driverCertifications) && !empty($driverCertifications)) : ?>
                    <div class="certifications-section">
                        <h3>Πιστοποιήσεις & Σεμινάρια</h3>
                        <div class="certifications-list">
                            <?php foreach ($driverCertifications as $cert) : ?>
                                <div class="certification-item">
                                    <div class="certification-header">
                                        <h4><?php echo htmlspecialchars($cert['title']); ?></h4>
                                        <?php if (isset($cert['expiry']) && $cert['expiry']) :
                                            $isExpired = strtotime($cert['expiry']) < time();
                                            $expiresInThreeMonths = !$isExpired && (strtotime($cert['expiry']) - time()) < 60 * 60 * 24 * 90;
                                        ?>
                                            <div class="certification-status <?php echo $isExpired ? 'expired' : ($expiresInThreeMonths ? 'expiring-soon' : 'valid'); ?>">
                                                <?php
                                                if ($isExpired) {
                                                    echo "Έχει λήξει";
                                                } elseif ($expiresInThreeMonths) {
                                                    echo "Λήγει σύντομα";
                                                } else {
                                                    echo "Σε ισχύ";
                                                }
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="certification-details">
                                        <?php if (isset($cert['provider']) && $cert['provider']) : ?>
                                            <div class="certification-provider">
                                                <strong>Πάροχος:</strong> <?php echo htmlspecialchars($cert['provider']); ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="certification-dates">
                                            <?php if (isset($cert['date']) && $cert['date']) : ?>
                                                <div class="certification-date">
                                                    <strong>Ημ/νία Απόκτησης:</strong> <?php echo date('d/m/Y', strtotime($cert['date'])); ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (isset($cert['expiry']) && $cert['expiry']) : ?>
                                                <div class="certification-expiry">
                                                    <strong>Ημ/νία Λήξης:</strong> <?php echo date('d/m/Y', strtotime($cert['expiry'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <?php if (isset($cert['description']) && $cert['description']) : ?>
                                            <div class="certification-description">
                                                <?php echo nl2br(htmlspecialchars($cert['description'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($driverData['training_seminars']) && $driverData['training_seminars'] && isset($driverData['training_details']) && $driverData['training_details']) : ?>
                    <div class="training-section">
                        <h3>Εκπαιδευτικά Σεμινάρια</h3>
                        <div class="training-details">
                            <?php echo nl2br(htmlspecialchars($driverData['training_details'])); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="skills-actions">
                    <a href="<?php echo BASE_URL; ?>drivers/edit-profile#skills-tab" class="btn-primary">Επεξεργασία Προσόντων</a>
                </div>
            </div>

            <!-- Καρτέλα Αξιολόγησης Οδηγού -->
            <div class="tab-pane" id="self-assessment">
                <div class="profile-content">
                    <div class="driver-rating-container">
                        <h2>Αξιολόγηση Οδηγού</h2>

                        <div class="rating-main-layout">
                            <!-- Αριστερό τμήμα (3/4) με την αξιολόγηση -->
                            <div class="rating-main-column">
                                <!-- Κεντρικό τμήμα με τη συνολική βαθμολογία -->
                                <div class="rating-overview">
                                    <div class="rating-circle-container">
                                        <div class="score-circle">
                                            <svg viewBox="0 0 100 100">
                                                <circle class="score-background" cx="50" cy="50" r="45" fill="none"></circle>
                                                <circle class="score-progress" cx="50" cy="50" r="45" fill="none"
                                                    style="stroke-dashoffset: calc(283.5 - (283.5 * <?php echo isset($driverRating['total_score']) ? $driverRating['total_score'] : 0; ?>) / 100)"></circle>
                                            </svg>
                                            <div class="score-text">
                                                <span class="score-value"><?php echo isset($driverRating['total_score']) ? round($driverRating['total_score']) : '0'; ?></span>
                                                <span class="score-label">Συνολική<br>Βαθμολογία</span>
                                            </div>
                                        </div>
                                        <div class="rating-info">
                                            <p>Τελευταία ενημέρωση: <?php echo isset($driverRating['last_updated']) ? date('d/m/Y', strtotime($driverRating['last_updated'])) : date('d/m/Y'); ?></p>
                                        </div>
                                    </div>

                                    <!-- Επιμέρους κατηγορίες βαθμολογίας -->
                                    <div class="rating-categories">
                                        <div class="rating-category">
                                            <h3>Προσόντα</h3>
                                            <div class="category-score-container">
                                                <div class="category-score"><?php echo isset($driverRating['skills_score']) ? round($driverRating['skills_score'], 1) : '0'; ?>/25</div>
                                                <div class="progress-bar">
                                                    <div class="progress" style="width: <?php echo isset($driverRating['skills_score']) ? ($driverRating['skills_score'] / 25) * 100 : 0; ?>%"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="rating-category">
                                            <h3>Ασφάλεια</h3>
                                            <div class="category-score-container">
                                                <div class="category-score"><?php echo isset($driverRating['safety_score']) ? round($driverRating['safety_score'], 1) : '0'; ?>/30</div>
                                                <div class="progress-bar">
                                                    <div class="progress" style="width: <?php echo isset($driverRating['safety_score']) ? ($driverRating['safety_score'] / 30) * 100 : 0; ?>%"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="rating-category">
                                            <h3>Επαγγελματισμός</h3>
                                            <div class="category-score-container">
                                                <div class="category-score"><?php echo isset($driverRating['professionalism_score']) ? round($driverRating['professionalism_score'], 1) : '0'; ?>/25</div>
                                                <div class="progress-bar">
                                                    <div class="progress" style="width: <?php echo isset($driverRating['professionalism_score']) ? ($driverRating['professionalism_score'] / 25) * 100 : 0; ?>%"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="rating-category">
                                            <h3>Τεχνικές Δεξιότητες</h3>
                                            <div class="category-score-container">
                                                <div class="category-score"><?php echo isset($driverRating['technical_score']) ? round($driverRating['technical_score'], 1) : '0'; ?>/20</div>
                                                <div class="progress-bar">
                                                    <div class="progress" style="width: <?php echo isset($driverRating['technical_score']) ? ($driverRating['technical_score'] / 20) * 100 : 0; ?>%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Κουμπιά ενεργειών κάτω από την αξιολόγηση -->
                                <div class="rating-actions">
                                    <a href="<?php echo BASE_URL; ?>drivers/driver-rating" class="btn-action">
                                        <img src="<?php echo BASE_URL; ?>img/rating_icon.png" alt="Αναλυτική Βαθμολογία" class="action-icon">
                                        <span>Αναλυτική Βαθμολογία</span>
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>drivers/incident-history" class="btn-action">
                                        <img src="<?php echo BASE_URL; ?>img/history_icon.png" alt="Ιστορικό Συμβάντων" class="action-icon">
                                        <span>Ιστορικό Συμβάντων</span>
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>drivers/report-incident" class="btn-action">
                                        <img src="<?php echo BASE_URL; ?>img/report_icon.png" alt="Αναφορά Συμβάντος" class="action-icon">
                                        <span>Αναφορά Συμβάντος</span>
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>drivers/update-assessment" class="btn-action">
                                        <img src="<?php echo BASE_URL; ?>img/assessment_icon.png" alt="Συμπλήρωση Αυτοαξιολόγησης" class="action-icon">
                                        <span>Συμπλήρωση Αυτοαξιολόγησης</span>
                                    </a>
                                </div>
                                <!-- Τομέας τηλεματικής αν υπάρχει -->
                                <?php if (isset($telemetryData) && !empty($telemetryData)) : ?>
                                    <div class="telemetry-section">
                                        <h3>Δεδομένα Τηλεματικής</h3>
                                        <div class="telemetry-summary">
                                            <div class="telemetry-score">
                                                <div class="score-circle small">
                                                    <svg viewBox="0 0 100 100">
                                                        <circle class="score-background" cx="50" cy="50" r="45" fill="none"></circle>
                                                        <circle class="score-progress" cx="50" cy="50" r="45" fill="none"
                                                            style="stroke-dashoffset: calc(283.5 - (283.5 * <?php echo $telemetryData['score']; ?>) / 100)"></circle>
                                                    </svg>
                                                    <div class="score-text">
                                                        <span class="score-value"><?php echo $telemetryData['score']; ?></span>
                                                    </div>
                                                </div>
                                                <div class="score-label">Βαθμολογία Οδήγησης</div>
                                            </div>

                                            <div class="telemetry-metrics">
                                                <div class="metric-item">
                                                    <div class="metric-label">Μέση Ταχύτητα</div>
                                                    <div class="metric-value"><?php echo number_format($telemetryData['avg_speed'], 1); ?> χλμ/ώρα</div>
                                                </div>
                                                <div class="metric-item">
                                                    <div class="metric-label">Απότομα Φρεναρίσματα</div>
                                                    <div class="metric-value"><?php echo $telemetryData['harsh_braking']; ?></div>
                                                </div>
                                                <div class="metric-item">
                                                    <div class="metric-label">Απότομες Επιταχύνσεις</div>
                                                    <div class="metric-value"><?php echo $telemetryData['harsh_acceleration']; ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Δεξιό τμήμα (1/4) με πρόσφατα συμβάντα -->
                            <div class="rating-side-column">
                                <!-- Προώθηση εφαρμογής τηλεματικής -->
                                <div class="telemetry-promotion">
                                    <h3>Βελτιώστε τη βαθμολογία σας</h3>
                                    <p>Κατεβάστε την εφαρμογή DriveJob Telemetry για αυτόματη παρακολούθηση της οδηγικής συμπεριφοράς σας.</p>
                                    <a href="#" class="btn-app-download">
                                        <img src="<?php echo BASE_URL; ?>img/app_download.png" alt="Κατέβασμα Εφαρμογής">
                                        <span>Κατέβασμα Εφαρμογής</span>
                                    </a>
                                </div>

                                <!-- Πρόσφατα συμβάντα -->
                                <div class="recent-incidents">
                                    <h3>Πρόσφατα Συμβάντα</h3>
                                    <?php if (isset($recentIncidents) && !empty($recentIncidents)) : ?>
                                        <div class="incidents-summary">
                                            <?php foreach (array_slice($recentIncidents, 0, 3) as $incident) : ?>
                                                <div class="incident-item severity-<?php echo $incident['severity']; ?>">
                                                    <div class="incident-date"><?php echo date('d/m/Y', strtotime($incident['incident_date'])); ?></div>
                                                    <div class="incident-type">
                                                        <?php
                                                        $typeLabels = [
                                                            'accident' => 'Ατύχημα',
                                                            'traffic_violation' => 'Παράβαση ΚΟΚ',
                                                            'near_miss' => 'Παρ\' ολίγον ατύχημα',
                                                            'complaint' => 'Παράπονο',
                                                            'other' => 'Άλλο'
                                                        ];
                                                        echo isset($typeLabels[$incident['incident_type']]) ? $typeLabels[$incident['incident_type']] : $incident['incident_type'];
                                                        ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else : ?>
                                        <p class="no-incidents">Δεν έχετε καταχωρήσει συμβάντα.</p>
                                    <?php endif; ?>
                                </div>



                                <!-- Συμβουλές βελτίωσης -->
                                <div class="improvement-tips">
                                    <h3>Συμβουλές Βελτίωσης</h3>
                                    <div class="tip-item">
                                        <i class="tip-icon safety-icon"></i>
                                        <div class="tip-content">
                                            <h4>Βελτίωση Ασφάλειας</h4>
                                            <p>Τηρείτε τα όρια ταχύτητας και αποφεύγετε τις απότομες κινήσεις.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Προτάσεις εκπαίδευσης -->
                        <div class="training-suggestions">
                            <h3>Προτάσεις Εκπαίδευσης</h3>
                            <div class="training-courses">
                                <div class="course-item">
                                    <div class="course-icon"><img src="<?php echo BASE_URL; ?>img/course_icon.png" alt="Σεμινάριο"></div>
                                    <div class="course-details">
                                        <h4>Αμυντική Οδήγηση</h4>
                                        <p>Σεμινάριο αμυντικής οδήγησης για επαγγελματίες οδηγούς</p>
                                        <a href="#" class="course-link">Περισσότερα &raquo;</a>
                                    </div>
                                </div>
                                <div class="course-item">
                                    <div class="course-icon"><img src="<?php echo BASE_URL; ?>img/course_icon.png" alt="Σεμινάριο"></div>
                                    <div class="course-details">
                                        <h4>Διαχείριση Οδηγικού Στρες</h4>
                                        <p>Τεχνικές διαχείρισης άγχους κατά την οδήγηση</p>
                                        <a href="#" class="course-link">Περισσότερα &raquo;</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Καρτέλα Ταιριασμάτων Εργασίας -->
            <div class="tab-pane" id="job-matches">
                <div class="job-matches-container">
                    <h2>Προτεινόμενες Θέσεις Εργασίας</h2>

                    <?php
                    // Αν υπάρχουν ταιριάσματα από τον MatchingModel
                    if (isset($matchedListings) && !empty($matchedListings['results'])) :
                    ?>
                        <div class="matched-listings">
                            <?php foreach ($matchedListings['results'] as $listing) : ?>
                                <div class="job-match-card">
                                    <div class="match-percentage">
                                        <?php echo $listing['match_percentage']; ?>% ταίριασμα
                                    </div>
                                    <div class="match-details">
                                        <h3><a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>"><?php echo htmlspecialchars($listing['title']); ?></a></h3>
                                        <div class="match-meta">
                                            <span class="job-type"><?php echo $jobTypes[$listing['job_type']] ?? $listing['job_type']; ?></span>
                                            <span class="location"><?php echo htmlspecialchars($listing['location']); ?></span>
                                        </div>
                                        <p class="match-description"><?php echo substr(htmlspecialchars($listing['description']), 0, 150) . '...'; ?></p>
                                        <a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>" class="btn-primary">Προβολή</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <div class="no-matches">
                            <p>Δεν βρέθηκαν ταιριάσματα με το προφίλ σας.</p>
                            <p>Συμπληρώστε περισσότερες πληροφορίες στο προφίλ σας για καλύτερα αποτελέσματα.</p>
                            <a href="<?php echo BASE_URL; ?>drivers/edit-profile" class="btn-primary">Ενημέρωση Προφίλ</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Καρτέλα Αγγελιών -->
            <div class="tab-pane" id="my-listings">
                <div class="profile-section">
                    <h2>Δημοσιευμένες Αγγελίες</h2>

                    <?php
                    // Εδώ θα χρησιμοποιηθούν μόνο οι αγγελίες που έχει δημιουργήσει ο χρήστης
                    if (isset($myListings) && count($myListings['results']) > 0) :
                    ?>
                        <div class="driver-listings">
                            <?php foreach ($myListings['results'] as $listing) : ?>
                                <div class="listing-card">
                                    <div class="listing-title">
                                        <h3><a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>"><?php echo htmlspecialchars($listing['title']); ?></a></h3>
                                        <span class="listing-type <?php echo $listing['listing_type']; ?>">
                                            <?php echo $listing['listing_type'] === 'job_offer' ? 'Προσφορά Εργασίας' : 'Αναζήτηση Εργασίας'; ?>
                                        </span>
                                    </div>

                                    <div class="listing-details">
                                        <!-- [Περιεχόμενο αγγελίας παραμένει ως έχει] -->
                                    </div>

                                    <div class="listing-actions">
                                        <a href="<?php echo BASE_URL; ?>job-listings/edit/<?php echo $listing['id']; ?>" class="btn-secondary">Επεξεργασία</a>
                                        <form action="<?php echo BASE_URL; ?>job-listings/delete/<?php echo $listing['id']; ?>" method="post" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo \Drivejob\Core\CSRF::generateToken(); ?>">
                                            <button type="submit" class="btn-danger" onclick="return confirm('Είστε σίγουροι ότι θέλετε να διαγράψετε αυτή την αγγελία;')">Διαγραφή</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <p class="no-listings">Δεν έχετε δημοσιεύσει ακόμα αγγελίες.</p>
                    <?php endif; ?>

                    <div class="add-listing">
                        <a href="<?php echo BASE_URL; ?>job-listings/create" class="btn-primary">Δημιουργία Νέας Αγγελίας</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <script src="<?php echo BASE_URL; ?>js/driver_profile.js"></script>
</main>
<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/partials/footer.php';
?>