<?php /* Καρτέλα «overview» του προφίλ οδηγού — αποσπάστηκε από το driver-profile.php (Πακέτο 5.4).
   Μοιράζεται το scope μεταβλητών του γονικού view (include). */ ?>
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
                        <!-- AI Matching Widget -->
                        <?php include dirname(__DIR__, 2) . '/partials/ai-matching-widget.php'; ?>

                        <!-- Messages Widget -->
                        <?php include dirname(__DIR__, 2) . '/partials/messages-widget.php'; ?>

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

