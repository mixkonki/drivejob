<!-- Προϋπηρεσία σε Οχήματα -->
<div class="form-section">
    <h3>Προϋπηρεσία σε Οχήματα</h3>
    <p class="form-info">Συμπληρώστε τα οχήματα στα οποία έχετε επαγγελματική εμπειρία:</p>

    <div class="vehicle-experience-container">
        <div class="row">
            <!-- Αριστερή στήλη: Φόρμα προσθήκης προϋπηρεσίας -->
            <div class="col-md-6">
                <div class="vehicle-experience-form">
                    <h4>Προσθήκη Προϋπηρεσίας</h4>

                    <div class="form-group">
                        <label for="new_transport_type">Είδος Μεταφοράς:</label>
                        <select id="new_transport_type" name="new_transport_type" class="form-control">
                            <option value="">Επιλέξτε είδος μεταφοράς...</option>
                            <option value="freight">Εμπορευματική Μεταφορά</option>
                            <option value="passenger">Επιβατική Μεταφορά</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="new_vehicle_type">Τύπος Οχήματος:</label>
                        <select id="new_vehicle_type" name="new_vehicle_type" class="form-control">
                            <option value="">Επιλέξτε πρώτα είδος μεταφοράς...</option>
                        </select>
                    </div>


                    <div class="form-group">
                        <label for="new_employment_type">Σχέση Εργασίας:</label>
                        <select id="new_employment_type" name="new_employment_type" class="form-control">
                            <option value="">Επιλέξτε σχέση εργασίας...</option>
                            <option value="own_business">Ίδια Επιχείρηση</option>
                            <option value="employee">Υπάλληλος</option>
                            <option value="contractor">Εξωτερικός Συνεργάτης</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Περίοδος:</label>
                        <!-- Πληκτρολόγηση ΚΑΙ ημερολόγιο: γράφεις ελεύθερα
                             ηη/μμ/εεεε μέσα στο πεδίο, ή πατάς το κουμπί 📅
                             για το πτυσσόμενο. -->
                        <div class="date-range" style="display:flex; align-items:center; gap:.4rem; flex-wrap:wrap;">
                            <input type="date" id="new_start_date" name="new_start_date" class="form-control" style="flex:1; min-width:130px;">
                            <button type="button" class="dj-cal" data-for="new_start_date" title="Άνοιγμα ημερολογίου"
                                    style="border:1px solid #d1d5db; background:#f9fafb; border-radius:6px; padding:.35rem .55rem; cursor:pointer;">📅</button>
                            <span>έως</span>
                            <input type="date" id="new_end_date" name="new_end_date" class="form-control" style="flex:1; min-width:130px;">
                            <button type="button" class="dj-cal" data-for="new_end_date" title="Άνοιγμα ημερολογίου"
                                    style="border:1px solid #d1d5db; background:#f9fafb; border-radius:6px; padding:.35rem .55rem; cursor:pointer;">📅</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="new_description">Περιγραφή Καθηκόντων:</label>
                        <textarea id="new_description" name="new_description" rows="3" class="form-control"></textarea>
                    </div>

                    <button type="button" id="btn-add-experience" class="btn-primary">Προσθήκη Προϋπηρεσίας</button>
                    <div id="save-reminder" class="save-reminder" style="display: none; margin-top: 15px; padding: 10px; background-color: #ffffd0; border: 1px solid #e6e600; border-radius: 4px;">
                        <strong>Σημείωση:</strong> Μην ξεχάσετε να κάνετε κλικ στο κουμπί <strong>"Αποθήκευση Αλλαγών"</strong> στο πάνω ή στο κάτω μέρος της σελίδας για να αποθηκευτούν οι αλλαγές σας.
                    </div>
                </div>
            </div>

            <!-- Δεξιά στήλη: Πίνακας προϋπηρεσίας -->
            <div class="col-md-6">
                <div class="vehicle-experience-table">
                    <h4>Καταχωρημένη Προϋπηρεσία</h4>

                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Είδος Οχήματος</th>
                                <th>Είδος Μεταφορών</th>
                                <th>Διάστημα</th>
                                <th>Ενέργειες</th>
                            </tr>
                        </thead>
                        <tbody id="vehicle-experience-tbody">
                            <!-- Τα δεδομένα θα προστεθούν δυναμικά με JavaScript -->
                        </tbody>
                        <tfoot>
                            <tr class="summary-row freight-summary">
                                <td colspan="2">Μερικό Σύνολο (Εμπορευματικές)</td>
                                <td>Εμπορευματικές</td>
                                <td id="freight-total">0 έτη, 0 μήνες, 0 ημέρες</td>
                                <td></td>
                            </tr>
                            <tr class="summary-row passenger-summary">
                                <td colspan="2">Μερικό Σύνολο (Επιβατικές)</td>
                                <td>Επιβατικές</td>
                                <td id="passenger-total">0 έτη, 0 μήνες, 0 ημέρες</td>
                                <td></td>
                            </tr>
                            <tr class="summary-row total-summary">
                                <td colspan="2"><strong>Συνολική Προϋπηρεσία</strong></td>
                                <td>Όλα τα είδη</td>
                                <td id="total-experience"><strong>0 έτη, 0 μήνες, 0 ημέρες</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Δηλώνει στον server ότι η φόρμα περιέχει την ενότητα προϋπηρεσίας:
         χωρίς αυτό, ο server δεν ξέρει αν το κενό vehicle_experience[]
         σημαίνει «διάγραψέ τα όλα» ή «η φόρμα δεν είχε την ενότητα». -->
    <input type="hidden" name="vehicle_experience_submitted" value="1">

    <!-- Κρυφά πεδία για αποθήκευση των δεδομένων -->
    <div id="vehicle-experience-data">
        <!-- Εδώ θα προστεθούν δυναμικά τα πεδία για κάθε εγγραφή -->
    </div>
</div>

<!-- Δεδομένα προϋπηρεσίας για φόρτωση από JavaScript -->
<?php if (isset($driverVehicleExperience) && !empty($driverVehicleExperience)) : ?>
    <script id="vehicle-experience-data-script">
        // Τα δεδομένα προϋπηρεσίας θα φορτωθούν από το vehicle-experience.js
        window.initialVehicleExperience = [
            <?php
            $count = count($driverVehicleExperience);
            $i = 0;
            foreach ($driverVehicleExperience as $index => $exp) :
                $i++;
            ?> {
                    id: <?php echo $index; ?>,
                    vehicleCategory: '<?php echo addslashes($exp['vehicle_category']); ?>',
                    vehicleType: '<?php echo addslashes($exp['vehicle_type'] ?? ''); ?>',
                    transportType: '<?php echo isset($exp['transport_type']) ? addslashes($exp['transport_type']) : "freight"; ?>',
                    employmentType: '<?php echo isset($exp['employment_type']) ? addslashes($exp['employment_type']) : "own_business"; ?>',
                    startDate: '<?php echo addslashes($exp['start_date'] ?? ''); ?>',
                    endDate: '<?php echo addslashes($exp['end_date'] ?? ''); ?>',
                    years: <?php echo intval($exp['years'] ?? 0); ?>,
                    months: <?php echo isset($exp['months']) ? intval($exp['months']) : 0; ?>,
                    days: <?php echo isset($exp['days']) ? intval($exp['days']) : 0; ?>,
                    description: '<?php echo addslashes($exp['description'] ?? ''); ?>'
                }
                <?php echo ($i < $count) ? ',' : ''; ?>
            <?php endforeach; ?>
        ];
    </script>
<?php endif; ?>