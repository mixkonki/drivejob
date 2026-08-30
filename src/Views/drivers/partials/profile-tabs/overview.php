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
                                                // Η πιο κοντινή λήξη ανά κατηγορία (στήλη 11 του διπλώματος)
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

                                                /*
                                                 * Λήξη εντύπου (πεδίο 4β): πότε λήγει το ίδιο το πλαστικό
                                                 * δίπλωμα — συχνά νωρίτερα από τις κατηγορίες, και είναι
                                                 * αυτή που καθορίζει πότε χρειάζεται ανανέωση εντύπου.
                                                 * Για την ένδειξη κατάστασης μετράει ό,τι λήγει πρώτο.
                                                 */
                                                $documentExpiry = !empty($driverData['license_document_expiry'])
                                                    ? $driverData['license_document_expiry'] : null;
                                                $effectiveExpiry = $earliestExpiry;
                                                if ($documentExpiry && (!$effectiveExpiry || strtotime($documentExpiry) < strtotime($effectiveExpiry))) {
                                                    $effectiveExpiry = $documentExpiry;
                                                }
                                                ?>
                                                <?php if ($documentExpiry || $earliestExpiry) : ?>
                                                    <?php if ($documentExpiry) : ?>
                                                        <div><span class="expiry-date"><?php echo date('d/m/Y', strtotime($documentExpiry)); ?></span>
                                                            <small style="color:#6b7280;">(έντυπο)</small></div>
                                                    <?php endif; ?>
                                                    <?php if ($earliestExpiry) : ?>
                                                        <div><span class="expiry-date"><?php echo date('d/m/Y', strtotime($earliestExpiry)); ?></span>
                                                            <small style="color:#6b7280;">(κατηγορίες)</small></div>
                                                    <?php endif; ?>
                                                <?php else : ?>
                                                    <span class="not-available">Δεν έχει οριστεί</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($effectiveExpiry) :
                                                    $isExpired = strtotime($effectiveExpiry) < time();
                                                    $expiresInThreeMonths = !$isExpired && (strtotime($effectiveExpiry) - time()) < 60 * 60 * 24 * 90;
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
                                                <?php
                                                /*
                                                 * v2 (30/08): εμφάνιση ΟΛΩΝ των αδειών του βιβλιαρίου.
                                                 * Πριν διαβαζόταν μόνο η πρώτη — και οι άδειες που
                                                 * καλύπτουν «το σύνολο της ειδικότητας» (χωρίς
                                                 * υποειδικότητες) δεν φαίνονταν καθόλου.
                                                 */
                                                $opList = $driverOperatorLicenses ?? [];
                                                ?>
                                                <?php if (!empty($opList)) : ?>
                                                    <div class="operator-licenses-list">
                                                        <?php foreach ($opList as $opLic) :
                                                            $opSpec = (string) ($opLic['speciality'] ?? '');
                                                            $opGroup = strtoupper((string) ($opLic['group_type'] ?? 'A'));
                                                            $opName = \Drivejob\Helpers\OperatorSpecialities::SPECIALITIES[$opSpec] ?? ('Ειδικότητα ' . $opSpec);
                                                            $opSubs = $opLic['sub_specialities'] ?? [];
                                                            $groupLabel = $opGroup === 'M' ? 'μικτή' : 'Ομάδα ' . $opGroup . '΄';
                                                        ?>
                                                            <div class="speciality-group">
                                                                <h6>
                                                                    <?php echo htmlspecialchars($opSpec . 'η ειδικότητα — ' . $opName, ENT_QUOTES, 'UTF-8'); ?>
                                                                    <span class="subspeciality-group">(<?php echo htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8'); ?>)</span>
                                                                </h6>
                                                                <?php if (!empty($opLic['covers_all'])) : ?>
                                                                    <p class="operator-covers-all">Σύνολο μηχανημάτων της ειδικότητας</p>
                                                                <?php elseif (!empty($opSubs)) : ?>
                                                                    <ul class="selected-subspecialities">
                                                                        <?php foreach ($opSubs as $subSpec) :
                                                                            $subCode = is_array($subSpec) ? ($subSpec['sub_speciality'] ?? '') : (string) $subSpec;
                                                                            $subGroup = is_array($subSpec) ? strtoupper($subSpec['group_type'] ?? $opGroup) : $opGroup;
                                                                            $subName = \Drivejob\Helpers\OperatorSpecialities::subName($subCode);
                                                                        ?>
                                                                            <li class="subspeciality-item">
                                                                                <span class="subspeciality-code"><?php echo htmlspecialchars($subCode, ENT_QUOTES, 'UTF-8'); ?></span>
                                                                                <span class="subspeciality-name"><?php echo htmlspecialchars($subName ?: ('Υποειδικότητα ' . $subCode), ENT_QUOTES, 'UTF-8'); ?></span>
                                                                                <?php if ($opGroup === 'M') : ?>
                                                                                    <span class="subspeciality-group">(Ομάδα <?php echo htmlspecialchars($subGroup, ENT_QUOTES, 'UTF-8'); ?>΄)</span>
                                                                                <?php endif; ?>
                                                                            </li>
                                                                        <?php endforeach; ?>
                                                                    </ul>
                                                                <?php else : ?>
                                                                    <p class="not-available">Δεν έχουν επιλεγεί μηχανήματα</p>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else : ?>
                                                    <span class="not-available">Δεν έχει καταχωρηθεί άδεια χειριστή</span>
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
                                        <?php
                                        /*
                                         * v2 (30/08): ΜΙΑ ΓΡΑΜΜΗ ΑΝΑ ΕΙΔΙΚΗ ΑΔΕΙΑ.
                                         * Πριν όλες οι ειδικές άδειες στριμώχνονταν σε ένα κελί
                                         * και η στήλη «Λήξη» έδειχνε την ημερομηνία της ΠΡΩΤΗΣ —
                                         * μια άδεια ΕΔΧ που έληγε φαινόταν έγκυρη επειδή η
                                         * επόμενη στη λίστα δεν είχε λήξει. Κάθε άδεια έχει
                                         * πλέον δική της γραμμή, λήξη και κατάσταση· κενή
                                         * ημερομηνία σημαίνει «Αορίστου», όχι «δεν ορίστηκε».
                                         */
                                        if (!empty($driverSpecialLicenses)) :
                                            foreach ($driverSpecialLicenses as $specialLicense) :
                                                $slExpiry = $specialLicense['expiry_date'] ?? null;
                                                $slTs = $slExpiry ? strtotime($slExpiry) : null;
                                        ?>
                                            <tr>
                                                <td class="qualification-type">
                                                    <span>Ειδική Άδεια</span>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars(\Drivejob\Helpers\SpecialLicenseTypes::label((string) $specialLicense['license_type']), ENT_QUOTES, 'UTF-8'); ?></strong>
                                                    <?php if (!empty($specialLicense['license_number'])) : ?>
                                                        <br><span class="qualification-detail">Αριθμός: <?php echo htmlspecialchars($specialLicense['license_number'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($specialLicense['details'])) : ?>
                                                        <div class="special-license-details"><?php echo htmlspecialchars($specialLicense['details'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($slTs) : ?>
                                                        <span class="expiry-date"><?php echo date('d/m/Y', $slTs); ?></span>
                                                    <?php else : ?>
                                                        <span class="expiry-date">Αορίστου</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($slTs) :
                                                        $isExpired = $slTs < time();
                                                        $expiresInThreeMonths = !$isExpired && ($slTs - time()) < 60 * 60 * 24 * 90;
                                                    ?>
                                                        <span class="status-indicator <?php echo $isExpired ? 'expired' : ($expiresInThreeMonths ? 'expiring-soon' : 'valid'); ?>">
                                                            <?php echo $isExpired ? 'Μη έγκυρη' : ($expiresInThreeMonths ? 'Λήγει σύντομα' : 'Έγκυρη'); ?>
                                                        </span>
                                                    <?php else : ?>
                                                        <span class="status-indicator valid">Έγκυρη</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; else : ?>
                                            <tr>
                                                <td class="qualification-type"><span>Ειδικές Άδειες</span></td>
                                                <td><span class="not-available">Δεν έχουν καταχωρηθεί ειδικές άδειες</span></td>
                                                <td><span class="not-available">-</span></td>
                                                <td><span class="not-available">-</span></td>
                                            </tr>
                                        <?php endif; ?>

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
                                    <img src="<?= \Drivejob\Helpers\Asset::url('img/email_icon.png') ?>" alt="Email">
                                    <span><?php echo htmlspecialchars($driverData['email']); ?></span>
                                </li>
                                <li>
                                    <img src="<?= \Drivejob\Helpers\Asset::url('img/phone_icon.png') ?>" alt="Τηλέφωνο">
                                    <span><?php echo htmlspecialchars($driverData['phone']); ?></span>
                                </li>
                                <?php if (isset($driverData['landline']) && $driverData['landline']) : ?>
                                    <li>
                                        <img src="<?= \Drivejob\Helpers\Asset::url('img/landline_icon.png') ?>" alt="Σταθερό Τηλέφωνο">
                                        <span><?php echo htmlspecialchars($driverData['landline']); ?></span>
                                    </li>
                                <?php endif; ?>
                                <?php if (isset($driverData['social_linkedin']) && $driverData['social_linkedin']) : ?>
                                    <li>
                                        <img src="<?= \Drivejob\Helpers\Asset::url('img/linkedin_icon.png') ?>" alt="LinkedIn">
                                        <a href="<?php echo htmlspecialchars($driverData['social_linkedin']); ?>" target="_blank">LinkedIn Προφίλ</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </section>
                        <!-- Απόρρητο & δεδομένα (GDPR — Πακέτο 7) -->
                        <section class="profile-section privacy-section">
                            <h2>Απόρρητο &amp; Δεδομένα</h2>
                            <p style="margin:6px 0 12px; color:#555;">Διαχειριστείτε τα προσωπικά σας δεδομένα (GDPR).</p>
                            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                                <a href="<?php echo BASE_URL; ?>gdpr/export" class="btn-secondary" style="padding:8px 16px; border-radius:6px; text-decoration:none; background:#eceff1; color:#333;">⬇️ Εξαγωγή δεδομένων (JSON)</a>
                                <a href="<?php echo BASE_URL; ?>gdpr/delete" style="padding:8px 16px; border-radius:6px; text-decoration:none; background:#fdecea; color:#b71c1c;">🗑️ Διαγραφή λογαριασμού</a>
                            </div>
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

