<?php /* Καρτέλα «special-licenses» της φόρμας επεξεργασίας προφίλ — αποσπάστηκε από το edit-profile.php (Πακέτο 5.4).
   Μοιράζεται το scope μεταβλητών του γονικού view (include). */ ?>
                    <div class="tab-pane" id="special-licenses">
                        <h2>Ειδικές Άδειες</h2>

                        <div id="special-licenses-container">
                            <!-- Λίστα ειδικών αδειών -->
                            <?php if (isset($driverSpecialLicenses) && count($driverSpecialLicenses) > 0) : ?>
                                <?php foreach ($driverSpecialLicenses as $index => $license) : ?>
                                    <div class="special-license-item" id="special-license-item-<?php echo $index; ?>">
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="special_license_type_<?php echo $index; ?>">Τύπος Άδειας</label>
                                                <input type="text" id="special_license_type_<?php echo $index; ?>" name="special_license_type[]" value="<?php echo htmlspecialchars($license['license_type']); ?>" required>
                                            </div>

                                            <div class="form-group">
                                                <label for="special_license_number_<?php echo $index; ?>">Αριθμός Άδειας</label>
                                                <input type="text" id="special_license_number_<?php echo $index; ?>" name="special_license_number[]" value="<?php echo htmlspecialchars($license['license_number'] ?? ''); ?>">
                                            </div>

                                            <div class="form-group">
                                                <label for="special_license_expiry_<?php echo $index; ?>">Ημερομηνία Λήξης</label>
                                                <input type="date" id="special_license_expiry_<?php echo $index; ?>" name="special_license_expiry[]" value="<?php echo $license['expiry_date'] ?? ''; ?>">
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="special_license_details_<?php echo $index; ?>">Περιγραφή/Λεπτομέρειες</label>
                                            <textarea id="special_license_details_<?php echo $index; ?>" name="special_license_details[]" rows="2"><?php echo htmlspecialchars($license['details'] ?? ''); ?></textarea>
                                        </div>

                                        <button type="button" class="btn-secondary remove-special-license" data-index="<?php echo $index; ?>">Αφαίρεση</button>
                                        <hr class="section-divider">
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <!-- Κενό στοιχείο για προσθήκη νέας άδειας (κρυμμένο αρχικά) -->
                            <div class="special-license-item" id="special-license-template" style="display: none;">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="special_license_type_new">Τύπος Άδειας</label>
                                        <input type="text" id="special_license_type_new" name="special_license_type[]">
                                    </div>

                                    <div class="form-group">
                                        <label for="special_license_number_new">Αριθμός Άδειας</label>
                                        <input type="text" id="special_license_number_new" name="special_license_number[]">
                                    </div>

                                    <div class="form-group">
                                        <label for="special_license_expiry_new">Ημερομηνία Λήξης</label>
                                        <input type="date" id="special_license_expiry_new" name="special_license_expiry[]">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="special_license_details_new">Περιγραφή/Λεπτομέρειες</label>
                                    <textarea id="special_license_details_new" name="special_license_details[]" rows="2"></textarea>
                                </div>

                                <button type="button" class="btn-secondary remove-special-license" data-index="new">Αφαίρεση</button>
                                <hr class="section-divider">
                            </div>
                        </div>

                        <!-- Το κουμπί εμφανίζεται μόνο στην καρτέλα ειδικών αδειών -->
                        <button type="button" id="add-special-license" class="btn-primary">Προσθήκη Ειδικής Άδειας</button>
                    </div>
                </div>
            </div>
            <!-- Προσθήκη στο αρχείο edit_profile.php στο κατάλληλο σημείο, όπου βρίσκονται οι καρτέλες -->
