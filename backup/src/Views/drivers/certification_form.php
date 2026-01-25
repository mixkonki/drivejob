<!-- Πιστοποιητικά Εκπαίδευσης -->
<div class="form-section">
    <h3>Πιστοποιητικά Εκπαίδευσης</h3>
    <p class="form-info">Συμπληρώστε τα πιστοποιητικά εκπαίδευσης που διαθέτετε:</p>

    <div class="certifications-container" style="display: flex; flex-direction: row;">
        <div style="display: flex; width: 100%;">
            <!-- Αριστερή στήλη: Φόρμα προσθήκης πιστοποιητικού -->
            <div class="col-md-4">
                <div class="certification-form">
                    <h4>Προσθήκη Πιστοποιητικού</h4>

                    <div class="form-group">
                        <label for="new_title">Τίτλος Πιστοποίησης:</label>
                        <input type="text" id="new_title" name="new_title" class="form-control" placeholder="π.χ. Πιστοποιητικό Επαγγελματικής Ικανότητας (ΠΕΙ)">
                    </div>

                    <div class="form-group">
                        <label for="new_provider">Πάροχος/Οργανισμός:</label>
                        <input type="text" id="new_provider" name="new_provider" class="form-control" placeholder="π.χ. Υπουργείο Μεταφορών">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_category">Θεματολογία:</label>
                            <select id="new_category" name="new_category" class="form-control">
                                <option value="">Επιλέξτε θεματολογία...</option>
                                <option value="road_safety">Οδική ασφάλεια</option>
                                <option value="tachograph">Ταχογράφος</option>
                                <option value="loading_securing">Φόρτωση - Πρόσδεση</option>
                                <option value="technical">Τεχνική επιμόρφωση</option>
                                <option value="commercial">Εμπορική επιμόρφωση</option>
                                <option value="procedures">Διαδικασίες</option>
                                <option value="inspections">Έλεγχοι</option>
                                <option value="other">Άλλο</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="new_transport_type">Τύπος Μεταφοράς:</label>
                            <select id="new_transport_type" name="new_transport_type" class="form-control">
                                <option value="both">Εμπορευματικές και επιβατικές</option>
                                <option value="freight">Εμπορευματικές μεταφορές</option>
                                <option value="passenger">Επιβατικές μεταφορές</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_date">Ημερομηνία Απόκτησης:</label>
                            <input type="date" id="new_date" name="new_date" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="new_expiry">Ημερομηνία Λήξης:</label>
                            <input type="date" id="new_expiry" name="new_expiry" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="new_duration">Διάρκεια (ώρες):</label>
                            <input type="number" id="new_duration" name="new_duration" class="form-control" min="0">
                        </div>
                    </div>

                    <div class="file-upload">
                        <label for="new_certificate_file">Βεβαίωση/Πιστοποιητικό:</label>
                        <div class="file-upload-input">
                            <input type="file" id="new_certificate_file" name="new_certificate_file" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="new_description">Περιγραφή:</label>
                        <textarea id="new_description" name="new_description" rows="3" class="form-control" placeholder="Σύντομη περιγραφή του περιεχομένου του πιστοποιητικού"></textarea>
                    </div>

                    <button type="button" id="btn-add-certification" class="btn-primary">Προσθήκη Πιστοποιητικού</button>
                    <div id="save-reminder" class="save-reminder" style="display: none; margin-top: 15px; padding: 10px; background-color: #ffffd0; border: 1px solid #e6e600; border-radius: 4px;">
                        <strong>Σημείωση:</strong> Μην ξεχάσετε να κάνετε κλικ στο κουμπί <strong>"Αποθήκευση Αλλαγών"</strong> στο πάνω ή στο κάτω μέρος της σελίδας για να αποθηκευτούν οι αλλαγές σας.
                    </div>
                </div>
            </div>

            <!-- Δεξιά στήλη: Πίνακας πιστοποιητικών -->
            <div class="col-md-8">
                <div class="certifications-table">
                    <h4>Καταχωρημένα Πιστοποιητικά</h4>

                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Τίτλος</th>
                                <th>Θεματολογία</th>
                                <th>Τύπος</th>
                                <th>Ημ/νία Απόκτησης</th>
                                <th>Ημ/νία Λήξης</th>
                                <th>Διάρκεια (ώρες)</th>
                                <th>Πιστοποιητικό</th>
                                <th>Περιγραφή</th>
                                <th>Βαθμοί</th>
                                <th>Ενέργειες</th>
                            </tr>
                        </thead>
                        <tbody id="certifications-tbody">
                            <!-- Τα δεδομένα θα προστεθούν δυναμικά με JavaScript -->
                        </tbody>
                        <tfoot>
                            <tr class="total-row freight-total">
                                <td colspan="9" class="text-right"><strong>Σύνολο για εμπορευματικές μεταφορές:</strong></td>
                                <td id="freight-total-points">0</td>
                                <td></td>
                            </tr>
                            <tr class="total-row passenger-total">
                                <td colspan="9" class="text-right"><strong>Σύνολο για επιβατικές μεταφορές:</strong></td>
                                <td id="passenger-total-points">0</td>
                                <td></td>
                            </tr>
                            <tr class="total-row grand-total">
                                <td colspan="9" class="text-right"><strong>Συνολικοί βαθμοί:</strong></td>
                                <td id="grand-total-points">0</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Κρυφά πεδία για αποθήκευση των δεδομένων -->
    <div id="certifications-data">
        <!-- Εδώ θα προστεθούν δυναμικά τα πεδία για κάθε εγγραφή -->
    </div>
</div>

<!-- Δεδομένα πιστοποιητικών για φόρτωση από JavaScript -->
<?php if (isset($driverCertifications) && !empty($driverCertifications)) : ?>
    <script id="certifications-data-script">
        // Τα δεδομένα πιστοποιητικών θα φορτωθούν από το certifications.js
        window.initialCertifications = [
            <?php
            $count = count($driverCertifications);
            $i = 0;
            foreach ($driverCertifications as $index => $cert) :
                $i++;
            ?> {
                    id: <?php echo $index; ?>,
                    title: '<?php echo addslashes($cert['title'] ?? ''); ?>',
                    provider: '<?php echo addslashes($cert['provider'] ?? ''); ?>',
                    category: '<?php echo addslashes($cert['category'] ?? ''); ?>',
                    transport_type: '<?php echo addslashes($cert['transport_type'] ?? 'both'); ?>',
                    date: '<?php echo addslashes($cert['date'] ?? ''); ?>',
                    expiry: '<?php echo addslashes($cert['expiry'] ?? ''); ?>',
                    duration: <?php echo intval($cert['duration'] ?? 0); ?>,
                    description: '<?php echo addslashes($cert['description'] ?? ''); ?>',
                    certificate_file: '<?php echo addslashes($cert['certificate_file'] ?? ''); ?>'
                }
                <?php echo ($i < $count) ? ',' : ''; ?>
            <?php endforeach; ?>
        ];
    </script>
<?php endif; ?>