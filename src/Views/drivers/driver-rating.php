<?php
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver_profile.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver-rating.css">

<main>
    <div class="container">
        <div class="page-header">
            <h1>Αξιολόγηση Οδηγού</h1>
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

        <?php
        // Ορισμός των μεταβλητών που λείπουν
        $driverSkills = $driverProfile['skills'] ?? [];

        // Ομαδοποίηση δεξιοτήτων ανά κατηγορία
        $skillCategories = [
            'Οδηγικές Ικανότητες' => [
                'defensive_driving' => 'Αμυντική Οδήγηση',
                'eco_driving' => 'Οικολογική Οδήγηση',
                'night_driving' => 'Νυχτερινή Οδήγηση',
                'mountain_driving' => 'Οδήγηση σε Ορεινές Περιοχές',
                'extreme_conditions' => 'Οδήγηση σε Ακραίες Συνθήκες',
                'precision_handling' => 'Ακρίβεια χειρισμών'
            ],
            'Ασφάλεια & Συμμόρφωση' => [
                'loading_securing' => 'Φόρτωση & Ασφάλιση Φορτίου',
                'emergency_response' => 'Αντιμετώπιση Έκτακτων Καταστάσεων',
                'first_aid' => 'Πρώτες Βοήθειες',
                'dangerous_goods' => 'Διαχείριση Επικίνδυνων Εμπορευμάτων',
                'tacograph_compliance' => 'Συμμόρφωση με Ταχογράφο',
                'fire_safety' => 'Πυρασφάλεια',
                'vehicle_inspection' => 'Έλεγχος οχημάτων'
            ],
            'Επαγγελματισμός' => [
                'customer_service' => 'Εξυπηρέτηση Πελατών',
                'time_management' => 'Διαχείριση Χρόνου',
                'route_planning' => 'Σχεδιασμός Διαδρομής',
                'conflict_resolution' => 'Επίλυση Συγκρούσεων',
                'multilingual' => 'Πολύγλωσσος',
                'report_writing' => 'Σύνταξη αναφορών',
                'inspection_behavior' => 'Συμπεριφορά σε έλεγχο',
                'border_crossing' => 'Διέλευση συνόρων'
            ],
            'Τεχνικές Γνώσεις' => [
                'vehicle_maintenance' => 'Συντήρηση Οχήματος',
                'troubleshooting' => 'Αντιμετώπιση Βλαβών',
                'digital_tachograph' => 'Ψηφιακός Ταχογράφος',
                'gps_systems' => 'Συστήματα GPS',
                'logistics_software' => 'Λογισμικό Logistics',
                'technical_terms' => 'Γνώση τεχνικών όρων',
                'equipment_handling' => 'Γνώση χειρισμού εξοπλισμού',
                'checklists_usage' => 'Χρήση λιστών ελέγχου'
            ]
        ];

        // Ειδικές δεξιότητες μόνο για εμπορευματικές μεταφορές
        $freightOnlySkills = [
            'loading_securing',
            'dangerous_goods'
        ];
        ?>

        <!-- Το τμήμα αποσφαλμάτωσης έχει αφαιρεθεί -->

        <div class="rating-container">
            <?php
            // Υπολογισμός βαθμολογίας για άδεια οδήγησης C/CE
            $drivingLicensePoints = 0;
            $hasDrivingLicense = false;

            // Νέο σύστημα βαθμολογίας για άδειες οδήγησης
            if (in_array('CE', $driverLicenseTypes)) {
                // Η άδεια CE υποκαθιστά την C και δίνει 100 βαθμούς
                $drivingLicensePoints = 100;
                $hasDrivingLicense = true;
            } elseif (in_array('C', $driverLicenseTypes)) {
                // Η άδεια C δίνει 50 βαθμούς
                $drivingLicensePoints = 50;
                $hasDrivingLicense = true;
            }

            // Υπολογισμός βαθμολογίας για ΠΕΙ εμπορευμάτων
            $peiPoints = $hasPeiC ? 10 : 0;

            // Υπολογισμός βαθμολογίας για κάρτα ταχογράφου
            $tachographPoints = (isset($driverTachograph) && $driverTachograph) ? 10 : 0;

            // Υπολογισμός βαθμολογίας για ADR
            $adrPoints = 0;
            $hasAdr = isset($driverADR) && $driverADR;
            if ($hasAdr) {
                $adrCategory = $driverADR['adr_type'] ?? 'Π1';
                $adrCategories = [
                    'Π1' => ['points' => 10],
                    'Π2' => ['points' => 15],
                    'Π3' => ['points' => 20],
                    'Π4' => ['points' => 25],
                    'Π5' => ['points' => 35],
                    'Π6' => ['points' => 40],
                    'Π7' => ['points' => 45],
                    'Π8' => ['points' => 50]
                ];

                if (isset($adrCategories[$adrCategory])) {
                    $adrPoints = $adrCategories[$adrCategory]['points'];
                }
            }

            // Υπολογισμός βαθμολογίας για προϋπηρεσία
            // Υπολογισμός ετών προϋπηρεσίας από τα δεδομένα vehicle_experience
            $freightYears = 0;
            $freightMonths = 0;
            $freightDays = 0;
            $passengerYears = 0;
            $passengerMonths = 0;
            $passengerDays = 0;

            if (isset($driverVehicleExperience) && !empty($driverVehicleExperience)) {
                foreach ($driverVehicleExperience as $exp) {
                    if (isset($exp['transport_type']) && $exp['transport_type'] === 'freight') {
                        $freightYears += isset($exp['years']) ? intval($exp['years']) : 0;
                        $freightMonths += isset($exp['months']) ? intval($exp['months']) : 0;
                        $freightDays += isset($exp['days']) ? intval($exp['days']) : 0;
                    } else if (isset($exp['transport_type']) && $exp['transport_type'] === 'passenger') {
                        $passengerYears += isset($exp['years']) ? intval($exp['years']) : 0;
                        $passengerMonths += isset($exp['months']) ? intval($exp['months']) : 0;
                        $passengerDays += isset($exp['days']) ? intval($exp['days']) : 0;
                    }
                }

                // Κανονικοποίηση των μηνών και ημερών
                $freightMonths += floor($freightDays / 30);
                $freightDays = $freightDays % 30;
                $freightYears += floor($freightMonths / 12);
                $freightMonths = $freightMonths % 12;

                $passengerMonths += floor($passengerDays / 30);
                $passengerDays = $passengerDays % 30;
                $passengerYears += floor($passengerMonths / 12);
                $passengerMonths = $passengerMonths % 12;
            }

            // Στρογγυλοποίηση των ετών προϋπηρεσίας στον πλησιέστερο ακέραιο
            // Υπολογισμός δεκαδικού μέρους για τα έτη εμπορευματικών μεταφορών
            $freightDecimalYears = $freightYears + ($freightMonths / 12) + ($freightDays / 365);
            $roundedFreightYears = round($freightDecimalYears);

            // Υπολογισμός δεκαδικού μέρους για τα έτη επιβατικών μεταφορών
            $passengerDecimalYears = $passengerYears + ($passengerMonths / 12) + ($passengerDays / 365);
            $roundedPassengerYears = round($passengerDecimalYears);

            // Εμφάνιση διαγνωστικών μηνυμάτων

            // Υπολογισμός βαθμολογίας για προϋπηρεσία εμπορευματικών μεταφορών
            $freightExperiencePoints = 0;
            $freightExperienceRange = "";

            if ($roundedFreightYears <= 1) {
                $freightExperiencePoints = 0;
                $freightExperienceRange = "0-1 έτος";
            } elseif ($roundedFreightYears <= 3) {
                $freightExperiencePoints = 10;
                $freightExperienceRange = "2-3 έτη";
            } elseif ($roundedFreightYears <= 5) {
                $freightExperiencePoints = 20;
                $freightExperienceRange = "4-5 έτη";
            } elseif ($roundedFreightYears <= 8) {
                $freightExperiencePoints = 30;
                $freightExperienceRange = "6-8 έτη";
            } else {
                $freightExperiencePoints = 40;
                $freightExperienceRange = "9+ έτη";
            }

            // Υπολογισμός βαθμολογίας για προϋπηρεσία επιβατικών μεταφορών
            $passengerExperiencePoints = 0;
            $passengerExperienceRange = "";

            if ($roundedPassengerYears <= 1) {
                $passengerExperiencePoints = 0;
                $passengerExperienceRange = "0-1 έτος";
            } elseif ($roundedPassengerYears <= 3) {
                $passengerExperiencePoints = 10;
                $passengerExperienceRange = "2-3 έτη";
            } elseif ($roundedPassengerYears <= 5) {
                $passengerExperiencePoints = 20;
                $passengerExperienceRange = "4-5 έτη";
            } elseif ($roundedPassengerYears <= 8) {
                $passengerExperiencePoints = 30;
                $passengerExperienceRange = "6-8 έτη";
            } else {
                $passengerExperiencePoints = 40;
                $passengerExperienceRange = "9+ έτη";
            }

            // Για συμβατότητα με τον υπόλοιπο κώδικα
            $experiencePoints = $freightExperiencePoints;
            $experienceRange = $freightExperienceRange;

            // Συνολική βαθμολογία τυπικών προσόντων
            $licensePoints = $drivingLicensePoints + $peiPoints + $tachographPoints + $adrPoints;
            $maxLicensePoints = 170; // Μέγιστη βαθμολογία τυπικών προσόντων (100 για άδεια + 10 για ΠΕΙ + 10 για ταχογράφο + 50 για ADR)

            // Υπολογισμός βαθμολογίας για επιβατικές μεταφορές
            $passengerDrivingLicensePoints = 0;

            // Νέο σύστημα βαθμολογίας για άδειες οδήγησης επιβατικών μεταφορών
            if (in_array('DE', $driverLicenseTypes)) {
                // Η άδεια DE υποκαθιστά την D και δίνει 100 βαθμούς
                $passengerDrivingLicensePoints = 100;
            } elseif (in_array('D', $driverLicenseTypes)) {
                // Η άδεια D δίνει 50 βαθμούς
                $passengerDrivingLicensePoints = 50;
            }

            $passengerLicensePoints = $passengerDrivingLicensePoints;
            if ($hasPeiD) $passengerLicensePoints += 10;
            if (isset($driverTachograph) && $driverTachograph) $passengerLicensePoints += 10;

            // Οι μεταβλητές $skillCategories και $freightOnlySkills έχουν ήδη οριστεί παραπάνω

            // Μέγιστες βαθμολογίες ανά κατηγορία σύμφωνα με τις νέες οδηγίες
            $categoryMaxScores = [
                'Οδηγικές Ικανότητες' => 120,
                'Ασφάλεια & Συμμόρφωση' => 140,
                'Επαγγελματισμός' => 160,
                'Τεχνικές Γνώσεις' => 100
            ];

            // Μέγιστες βαθμολογίες ανά κατηγορία για επιβατικές μεταφορές
            $passengerCategoryMaxScores = [
                'Οδηγικές Ικανότητες' => 120,
                'Ασφάλεια & Συμμόρφωση' => 100, // Χωρίς τις δεξιότητες μόνο για εμπορευματικές μεταφορές
                'Επαγγελματισμός' => 160,
                'Τεχνικές Γνώσεις' => 100
            ];

            // Υπολογισμός βαθμολογίας ανά κατηγορία για εμπορευματικές μεταφορές
            $freightCategoryScores = [];
            $freightTotalSkillPoints = 0;
            $freightMaxSkillPoints = 0;

            foreach ($skillCategories as $categoryName => $skills) {
                $categoryScore = 0;
                $activeSkillsCount = 0;
                $totalSkillsCount = count($skills);

                foreach ($skills as $skillKey => $skillName) {
                    // Έλεγχος αν η δεξιότητα είναι ενεργή
                    // Οι δεξιότητες μπορεί να είναι αποθηκευμένες με διαφορετικούς τρόπους
                    if (
                        // Περίπτωση 1: Η δεξιότητα είναι αποθηκευμένη ως κλειδί με τιμή 1
                        (isset($driverSkills[$skillKey]) && $driverSkills[$skillKey] == 1) ||
                        // Περίπτωση 2: Η δεξιότητα είναι αποθηκευμένη ως πεδίο με το όνομα της δεξιότητας
                        (isset($driverData[$skillKey]) && $driverData[$skillKey] == 1)
                    ) {
                        $activeSkillsCount++;
                    }
                }

                // Κάθε δεξιότητα αξίζει 20 βαθμούς σύμφωνα με τις νέες οδηγίες
                $categoryScore = $activeSkillsCount * 20;
                $categoryMaxScore = $categoryMaxScores[$categoryName];

                $freightCategoryScores[$categoryName] = [
                    'score' => $categoryScore,
                    'maxScore' => $categoryMaxScore,
                    'activeCount' => $activeSkillsCount
                ];

                $freightTotalSkillPoints += $categoryScore;
                $freightMaxSkillPoints += $categoryMaxScore;
            }

            // Υπολογισμός βαθμολογίας ανά κατηγορία για επιβατικές μεταφορές
            $passengerCategoryScores = [];
            $passengerTotalSkillPoints = 0;
            $passengerMaxSkillPoints = 0;

            foreach ($skillCategories as $categoryName => $skills) {
                $categoryScore = 0;
                $activeSkillsCount = 0;
                $totalSkillsCount = 0;

                foreach ($skills as $skillKey => $skillName) {
                    // Παραλείπουμε τις δεξιότητες που είναι μόνο για εμπορευματικές μεταφορές
                    if (in_array($skillKey, $freightOnlySkills)) {
                        continue;
                    }

                    $totalSkillsCount++;

                    // Έλεγχος αν η δεξιότητα είναι ενεργή
                    // Οι δεξιότητες μπορεί να είναι αποθηκευμένες με διαφορετικούς τρόπους
                    if (
                        // Περίπτωση 1: Η δεξιότητα είναι αποθηκευμένη ως κλειδί με τιμή 1
                        (isset($driverSkills[$skillKey]) && $driverSkills[$skillKey] == 1) ||
                        // Περίπτωση 2: Η δεξιότητα είναι αποθηκευμένη ως πεδίο με το όνομα της δεξιότητας
                        (isset($driverData[$skillKey]) && $driverData[$skillKey] == 1)
                    ) {
                        $activeSkillsCount++;
                    }
                }

                // Κάθε δεξιότητα αξίζει 20 βαθμούς σύμφωνα με τις νέες οδηγίες
                $categoryScore = $activeSkillsCount * 20;
                $categoryMaxScore = $passengerCategoryMaxScores[$categoryName];

                $passengerCategoryScores[$categoryName] = [
                    'score' => $categoryScore,
                    'maxScore' => $categoryMaxScore,
                    'activeCount' => $activeSkillsCount
                ];

                $passengerTotalSkillPoints += $categoryScore;
                $passengerMaxSkillPoints += $categoryMaxScore;
            }

            // Οι συνολικές βαθμολογίες δεξιοτήτων έχουν ήδη υπολογιστεί παραπάνω

            // Υπολογισμός βαθμολογίας από πιστοποιητικά
            $freightCertificationsPoints = 0;
            $passengerCertificationsPoints = 0;
            $maxCertificationsPoints = 200; // Μέγιστη βαθμολογία πιστοποιητικών

            // Αντιστοίχιση κατηγοριών με βαθμούς
            $certCategoryPoints = [
                'road_safety' => 50,
                'tachograph' => 20,
                'loading_securing' => 50,
                'technical' => 20,
                'commercial' => 20,
                'procedures' => 20,
                'inspections' => 20,
                'other' => 20
            ];

            // Υπολογισμός βαθμολογίας από πιστοποιητικά
            if (isset($driverProfile['certifications']) && !empty($driverProfile['certifications'])) {
                foreach ($driverProfile['certifications'] as $cert) {
                    $points = $certCategoryPoints[$cert['category'] ?? ''] ?? 0;

                    if (($cert['transport_type'] ?? 'both') === 'freight' || ($cert['transport_type'] ?? 'both') === 'both') {
                        $freightCertificationsPoints += $points;
                    }

                    if (($cert['transport_type'] ?? 'both') === 'passenger' || ($cert['transport_type'] ?? 'both') === 'both') {
                        $passengerCertificationsPoints += $points;
                    }
                }
            }

            // Συνολικές βαθμολογίες
            $freightTotalScore = $licensePoints + $freightExperiencePoints + $freightTotalSkillPoints + $freightCertificationsPoints;
            $freightMaxTotalScore = $maxLicensePoints + 40 + $freightMaxSkillPoints + $maxCertificationsPoints;

            $passengerTotalScore = $passengerLicensePoints + $passengerExperiencePoints + $passengerTotalSkillPoints + $passengerCertificationsPoints;
            $passengerMaxTotalScore = 120 + 40 + $passengerMaxSkillPoints + $maxCertificationsPoints;
            ?>

            <!-- Νέα δομή με τρεις στήλες βαθμολογίας -->
            <div class="driver-categories-columns">
                <!-- Στήλη Οδηγού Εμπορευματικών Μεταφορών -->
                <div class="rating-column">
                    <h2 class="column-title">Οδηγός Εμπορευματικών Μεταφορών <span class="total-score-label">(<strong style="color: #aa3636;"><?php echo $freightTotalScore; ?>/<?php echo $freightMaxTotalScore; ?></strong>)</span></h2>
                    <div class="rating-categories">
                        <!-- Προσόντα - Άδειες Οδήγησης και Προϋπηρεσία -->
                        <div class="rating-category">
                            <div class="qualifications-table">
                                <table>
                                    <thead>
                                        <tr>
                                            <th colspan="2"><strong>Τυπικά προσόντα</strong></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Άδεια οδήγησης: </td>
                                            <td><?php echo $drivingLicensePoints; ?></td>
                                        </tr>
                                        <tr>
                                            <td>ΠΕΙ εμπορευμάτων: </td>
                                            <td><?php echo $peiPoints; ?></td>
                                        </tr>
                                        <tr>
                                            <td>Κάρτα Ψηφιακού Ταχογράφου: </td>
                                            <td><?php echo $tachographPoints; ?></td>
                                        </tr>
                                        <tr>
                                            <td>Πιστοποιητικό ADR: </td>
                                            <td><?php echo $adrPoints; ?></td>
                                        </tr>
                                        <tr>
                                            <td>Έτη προϋπηρεσίας (<?php echo $freightExperienceRange; ?>): </td>
                                            <td><?php echo $freightExperiencePoints; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Μερική βαθμολογία: </strong></td>
                                            <td><strong><?php echo $licensePoints + $freightExperiencePoints; ?> / <?php echo $maxLicensePoints + 40; ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Προσόντα - Δεξιότητες -->
                        <div class="rating-category">
                            <div class="qualifications-table">
                                <table>
                                    <thead>
                                        <tr>
                                            <th colspan="2"><strong>Προσόντα & Δεξιότητες</strong></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Εμφάνιση βαθμολογίας ανά κατηγορία
                                        foreach ($freightCategoryScores as $categoryName => $scoreData) {
                                            echo "<tr>";
                                            echo "<td>{$categoryName}: </td>";
                                            echo "<td>{$scoreData['score']} / {$scoreData['maxScore']}</td>";
                                            echo "</tr>";
                                        }
                                        ?>
                                        <tr>
                                            <td>Πιστοποιητικά Εκπαίδευσης: </td>
                                            <td><?php echo $freightCertificationsPoints; ?> / <?php echo $maxCertificationsPoints; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Μερική βαθμολογία: </strong></td>
                                            <td><strong><?php echo $freightTotalSkillPoints + $freightCertificationsPoints; ?> / <?php echo $freightMaxSkillPoints + $maxCertificationsPoints; ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Στήλη Οδηγού Επιβατικών Μεταφορών -->
                <div class="rating-column">
                    <h2 class="column-title">Οδηγός Επιβατικών Μεταφορών <span class="total-score-label">(<strong style="color: #aa3636;"><?php echo $passengerTotalScore; ?>/<?php echo $passengerMaxTotalScore; ?></strong>)</span></h2>
                    <div class="rating-categories">
                        <!-- Προσόντα - Άδειες Οδήγησης και Προϋπηρεσία -->
                        <div class="rating-category">
                            <div class="qualifications-table">
                                <table>
                                    <thead>
                                        <tr>
                                            <th colspan="2"><strong>Τυπικά προσόντα</strong></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Άδεια οδήγησης: </td>
                                            <td><?php echo $passengerDrivingLicensePoints; ?></td>
                                        </tr>
                                        <tr>
                                            <td>ΠΕΙ επιβατών: </td>
                                            <td><?php echo $hasPeiD ? 10 : 0; ?></td>
                                        </tr>
                                        <tr>
                                            <td>Κάρτα Ψηφιακού Ταχογράφου: </td>
                                            <td><?php echo (isset($driverTachograph) && $driverTachograph) ? 10 : 0; ?></td>
                                        </tr>
                                        <tr>
                                            <td>Έτη προϋπηρεσίας (<?php echo $passengerExperienceRange; ?>): </td>
                                            <td><?php echo $passengerExperiencePoints; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Μερική βαθμολογία: </strong></td>
                                            <td><strong><?php echo $passengerLicensePoints + $passengerExperiencePoints; ?> / <?php echo 120 + 40; ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Προσόντα - Δεξιότητες -->
                        <div class="rating-category">
                            <div class="qualifications-table">
                                <table>
                                    <thead>
                                        <tr>
                                            <th colspan="2"><strong>Προσόντα & Δεξιότητες</strong></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Εμφάνιση βαθμολογίας ανά κατηγορία
                                        foreach ($passengerCategoryScores as $categoryName => $scoreData) {
                                            echo "<tr>";
                                            echo "<td>{$categoryName}: </td>";
                                            echo "<td>{$scoreData['score']} / {$scoreData['maxScore']}</td>";
                                            echo "</tr>";
                                        }
                                        ?>
                                        <tr>
                                            <td>Πιστοποιητικά Εκπαίδευσης: </td>
                                            <td><?php echo $passengerCertificationsPoints; ?> / <?php echo $maxCertificationsPoints; ?></td>
                                        </tr>
                                        <tr>

                                        <tr>
                                            <td><strong>Μερική βαθμολογία: </strong></td>
                                            <td><strong><?php echo $passengerTotalSkillPoints + $passengerCertificationsPoints; ?> / <?php echo $passengerMaxSkillPoints + $maxCertificationsPoints; ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Στήλη Χειριστή Μηχανημάτων Έργου -->
                <div class="rating-column">
                    <h2 class="column-title">Χειριστής Μηχανημάτων Έργου</h2>
                    <div class="rating-categories">
                        <!-- Άδεια Χειριστή Μηχανημάτων Έργου -->
                        <div class="rating-category">
                            <div class="qualifications-table">
                                <table>
                                    <thead>
                                        <tr>
                                            <th colspan="2"><strong>Τυπικά προσόντα</strong></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="2" class="no-data">Δεν έχουν καταχωρηθεί άδειες χειριστή</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Μερική βαθμολογία: </strong></td>
                                            <td><strong>0 από 100</strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/partials/footer.php';
?>