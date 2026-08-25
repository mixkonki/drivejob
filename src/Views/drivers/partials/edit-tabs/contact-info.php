<?php /* Ενότητα «Στοιχεία Επικοινωνίας» — από 25/08 ΔΕΝ είναι δική της καρτέλα:
   περιλαμβάνεται ΜΕΣΑ στην καρτέλα «Προσωπικά Στοιχεία» (personal-info.php),
   ώστε προσωπικά + επικοινωνία να ζουν σε μία οθόνη. Η αλλαγή κωδικού
   μεταφέρθηκε στη σελίδα drivers/security (μενού λογαριασμού). */ ?>
                        <hr class="section-divider">
                        <h2>Στοιχεία Επικοινωνίας</h2>

                        <?php /* Προσαρμοστικό πλέγμα (25/08): συγγενικά πεδία στην ίδια
                           .form-row — 2-3 στήλες όταν χωράει η οθόνη, μία στο κινητό. */ ?>
                        <div class="form-row">
                            <div class="form-group <?php echo isset($errors['phone']) ? 'has-error' : ''; ?>">
                                <label for="phone">Κινητό Τηλέφωνο</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo old('phone', $driverData['phone'] ?? ''); ?>" required>
                                <?php if (isset($errors['phone'])) : ?>
                                    <div class="error-message"><?php echo $errors['phone']; ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="landline">Σταθερό Τηλέφωνο</label>
                                <input type="tel" id="landline" name="landline" value="<?php echo old('landline', $driverData['landline'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo $driverData['email'] ?? ''; ?>" readonly>
                                <p class="form-hint">Δεν αλλάζει — επικοινωνήστε με τη διαχείριση.</p>
                            </div>
                        </div>

                        <div class="form-row addr-row">
                            <div class="form-group">
                                <label for="address">Διεύθυνση</label>
                                <input type="text" id="address" name="address" value="<?php echo old('address', $driverData['address'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="house_number">Αριθμός</label>
                                <input type="text" id="house_number" name="house_number" value="<?php echo old('house_number', $driverData['house_number'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="postal_code">Ταχ. Κώδικας</label>
                                <input type="text" id="postal_code" name="postal_code" value="<?php echo old('postal_code', $driverData['postal_code'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">Πόλη</label>
                                <input type="text" id="city" name="city" value="<?php echo old('city', $driverData['city'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="country">Χώρα</label>
                                <select id="country" name="country" class="country-select">
                                    <option value="">Επιλέξτε χώρα</option>
                                    <option value="GR" <?php echo (old('country', $driverData['country'] ?? '') == 'GR') ? 'selected' : ''; ?>>Ελλάδα</option>
                                    <option value="CY" <?php echo (old('country', $driverData['country'] ?? '') == 'CY') ? 'selected' : ''; ?>>Κύπρος</option>
                                    <option value="DE" <?php echo (old('country', $driverData['country'] ?? '') == 'DE') ? 'selected' : ''; ?>>Γερμανία</option>
                                    <option value="FR" <?php echo (old('country', $driverData['country'] ?? '') == 'FR') ? 'selected' : ''; ?>>Γαλλία</option>
                                    <option value="IT" <?php echo (old('country', $driverData['country'] ?? '') == 'IT') ? 'selected' : ''; ?>>Ιταλία</option>
                                    <option value="ES" <?php echo (old('country', $driverData['country'] ?? '') == 'ES') ? 'selected' : ''; ?>>Ισπανία</option>
                                    <option value="GB" <?php echo (old('country', $driverData['country'] ?? '') == 'GB') ? 'selected' : ''; ?>>Ηνωμένο Βασίλειο</option>
                                    <option value="US" <?php echo (old('country', $driverData['country'] ?? '') == 'US') ? 'selected' : ''; ?>>Ηνωμένες Πολιτείες</option>
                                    <option value="CA" <?php echo (old('country', $driverData['country'] ?? '') == 'CA') ? 'selected' : ''; ?>>Καναδάς</option>
                                    <option value="AU" <?php echo (old('country', $driverData['country'] ?? '') == 'AU') ? 'selected' : ''; ?>>Αυστραλία</option>
                                    <option value="AT" <?php echo (old('country', $driverData['country'] ?? '') == 'AT') ? 'selected' : ''; ?>>Αυστρία</option>
                                    <option value="BE" <?php echo (old('country', $driverData['country'] ?? '') == 'BE') ? 'selected' : ''; ?>>Βέλγιο</option>
                                    <option value="BG" <?php echo (old('country', $driverData['country'] ?? '') == 'BG') ? 'selected' : ''; ?>>Βουλγαρία</option>
                                    <option value="HR" <?php echo (old('country', $driverData['country'] ?? '') == 'HR') ? 'selected' : ''; ?>>Κροατία</option>
                                    <option value="CZ" <?php echo (old('country', $driverData['country'] ?? '') == 'CZ') ? 'selected' : ''; ?>>Τσεχία</option>
                                    <option value="DK" <?php echo (old('country', $driverData['country'] ?? '') == 'DK') ? 'selected' : ''; ?>>Δανία</option>
                                    <option value="EE" <?php echo (old('country', $driverData['country'] ?? '') == 'EE') ? 'selected' : ''; ?>>Εσθονία</option>
                                    <option value="FI" <?php echo (old('country', $driverData['country'] ?? '') == 'FI') ? 'selected' : ''; ?>>Φινλανδία</option>
                                    <option value="HU" <?php echo (old('country', $driverData['country'] ?? '') == 'HU') ? 'selected' : ''; ?>>Ουγγαρία</option>
                                    <option value="IE" <?php echo (old('country', $driverData['country'] ?? '') == 'IE') ? 'selected' : ''; ?>>Ιρλανδία</option>
                                    <option value="LV" <?php echo (old('country', $driverData['country'] ?? '') == 'LV') ? 'selected' : ''; ?>>Λετονία</option>
                                    <option value="LT" <?php echo (old('country', $driverData['country'] ?? '') == 'LT') ? 'selected' : ''; ?>>Λιθουανία</option>
                                    <option value="LU" <?php echo (old('country', $driverData['country'] ?? '') == 'LU') ? 'selected' : ''; ?>>Λουξεμβούργο</option>
                                    <option value="MT" <?php echo (old('country', $driverData['country'] ?? '') == 'MT') ? 'selected' : ''; ?>>Μάλτα</option>
                                    <option value="NL" <?php echo (old('country', $driverData['country'] ?? '') == 'NL') ? 'selected' : ''; ?>>Ολλανδία</option>
                                    <option value="PL" <?php echo (old('country', $driverData['country'] ?? '') == 'PL') ? 'selected' : ''; ?>>Πολωνία</option>
                                    <option value="PT" <?php echo (old('country', $driverData['country'] ?? '') == 'PT') ? 'selected' : ''; ?>>Πορτογαλία</option>
                                    <option value="RO" <?php echo (old('country', $driverData['country'] ?? '') == 'RO') ? 'selected' : ''; ?>>Ρουμανία</option>
                                    <option value="SK" <?php echo (old('country', $driverData['country'] ?? '') == 'SK') ? 'selected' : ''; ?>>Σλοβακία</option>
                                    <option value="SI" <?php echo (old('country', $driverData['country'] ?? '') == 'SI') ? 'selected' : ''; ?>>Σλοβενία</option>
                                    <option value="SE" <?php echo (old('country', $driverData['country'] ?? '') == 'SE') ? 'selected' : ''; ?>>Σουηδία</option>
                                    <option value="CH" <?php echo (old('country', $driverData['country'] ?? '') == 'CH') ? 'selected' : ''; ?>>Ελβετία</option>
                                    <option value="NO" <?php echo (old('country', $driverData['country'] ?? '') == 'NO') ? 'selected' : ''; ?>>Νορβηγία</option>
                                    <option value="RS" <?php echo (old('country', $driverData['country'] ?? '') == 'RS') ? 'selected' : ''; ?>>Σερβία</option>
                                    <option value="TR" <?php echo (old('country', $driverData['country'] ?? '') == 'TR') ? 'selected' : ''; ?>>Τουρκία</option>
                                </select>
                            </div>
                        </div>

                        <!-- Προσθήκη τμήματος Μέσα Κοινωνικής Δικτύωσης -->
                        <hr class="section-divider">
                        <h3>Μέσα Κοινωνικής Δικτύωσης</h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="social_linkedin">LinkedIn</label>
                                <input type="url" id="social_linkedin" name="social_linkedin" value="<?php echo old('social_linkedin', $driverData['social_linkedin'] ?? ''); ?>" placeholder="https://www.linkedin.com/in/yourprofile">
                            </div>

                            <div class="form-group">
                                <label for="social_facebook">Facebook</label>
                                <input type="url" id="social_facebook" name="social_facebook" value="<?php echo old('social_facebook', $driverData['social_facebook'] ?? ''); ?>" placeholder="https://www.facebook.com/yourprofile">
                            </div>

                            <div class="form-group">
                                <label for="social_twitter">Twitter/X</label>
                                <input type="url" id="social_twitter" name="social_twitter" value="<?php echo old('social_twitter', $driverData['social_twitter'] ?? ''); ?>" placeholder="https://twitter.com/yourusername">
                            </div>

                            <div class="form-group">
                                <label for="social_instagram">Instagram</label>
                                <input type="url" id="social_instagram" name="social_instagram" value="<?php echo old('social_instagram', $driverData['social_instagram'] ?? ''); ?>" placeholder="https://www.instagram.com/yourusername">
                            </div>
                        </div>

<?php /* Η «Αλλαγή Κωδικού Πρόσβασης» μεταφέρθηκε στη σελίδα drivers/security. */ ?>
