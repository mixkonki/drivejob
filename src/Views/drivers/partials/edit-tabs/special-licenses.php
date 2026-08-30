<?php /* Καρτέλα «Ειδικές Άδειες» — v2 (30/08/2026).
   Ο «Τύπος Άδειας» ήταν ελεύθερο κείμενο: ο ένας έγραφε «εδχ», ο άλλος
   «ΕΔΧ ταξί» — τίποτα δεν φιλτραριζόταν. Τώρα επιλογή από τυποποιημένη
   λίστα (Helpers\SpecialLicenseTypes) ώστε οι άδειες να μπορούν να
   μπουν σε φίλτρα αγγελιών, ταίριασμα και βαθμολογία. */ ?>
                    <div class="tab-pane" id="special-licenses">
                        <div class="form-section">
                        <h2>Ειδικές Άδειες & Πιστοποιητικά Οδηγού</h2>
                        <p class="form-info">Άδειες και πιστοποιητικά πέρα από το δίπλωμα, το ΠΕΙ, το ADR, τον ταχογράφο και την άδεια χειριστή — που έχουν δικές τους καρτέλες.</p>

                        <?php
                        /**
                         * Ένα μπλοκ ειδικής άδειας. Ίδια συνάρτηση για τις
                         * υπάρχουσες εγγραφές και για το JS template — ένα markup,
                         * όχι δύο αντίγραφα που ξεσυγχρονίζονται.
                         */
                        $renderSpecialLicense = function ($idx, array $license = []) {
                            $type = (string) ($license['license_type'] ?? '');
                            // Παλιές ελεύθερες τιμές (π.χ. «εδχ») δεν ταιριάζουν σε
                            // κωδικό: πέφτουν στο «Άλλο» και κρατούν το κείμενό τους.
                            $isKnown = \Drivejob\Helpers\SpecialLicenseTypes::isValid($type);
                            $selected = $isKnown ? $type : ($type === '' ? '' : 'other');
                            $details = (string) ($license['details'] ?? '');
                            if (!$isKnown && $type !== '' && $details === '') {
                                $details = $type; // μη χαθεί ο παλιός ελεύθερος τίτλος
                            }
                        ?>
                            <div class="special-license-item form-section" data-idx="<?php echo $idx; ?>">
                                <div class="form-row">
                                    <div class="form-group sl-type-group">
                                        <label>Τύπος Άδειας</label>
                                        <select name="special_license_type[]" class="sl-type" required>
                                            <option value="">Επιλέξτε τύπο</option>
                                            <?php foreach (\Drivejob\Helpers\SpecialLicenseTypes::options() as $code => $label) : ?>
                                                <option value="<?php echo htmlspecialchars((string) $code, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selected === $code ? 'selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                                            <?php endforeach; ?>
                                            <?php /* Αν ο διαχειριστής απέσυρε τον τύπο που έχει ήδη ο
                                               οδηγός, τον κρατάμε ορατό ώστε να μη χαθεί στο save. */ ?>
                                            <?php if ($selected !== '' && !isset(\Drivejob\Helpers\SpecialLicenseTypes::options()[$selected])) : ?>
                                                <option value="<?php echo htmlspecialchars($selected, ENT_QUOTES, 'UTF-8'); ?>" selected><?php echo htmlspecialchars(\Drivejob\Helpers\SpecialLicenseTypes::label($selected), ENT_QUOTES, 'UTF-8'); ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Αριθμός Άδειας</label>
                                        <input type="text" name="special_license_number[]" value="<?php echo htmlspecialchars((string) ($license['license_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <?php
                                    // Κενή ημερομηνία = αορίστου διάρκειας (π.χ. ΠΕΕ). Το
                                    // δηλώνουμε ρητά με checkbox αντί να το αφήνουμε στην
                                    // τύχη ενός κενού πεδίου — feedback Κώστα 30/08.
                                    $slNoExpiry = empty($license['expiry_date']) && !empty($license);
                                    ?>
                                    <div class="form-group">
                                        <label>Ημερομηνία Λήξης</label>
                                        <?php /* ΠΟΤΕ disabled: τα disabled πεδία ΔΕΝ στέλνονται και
                                           τα παράλληλα arrays (type[]/expiry[]) ξεσυγχρονίζονται —
                                           η λήξη της μιας άδειας θα κατέληγε σε άλλη. Το πεδίο
                                           απλώς κρύβεται και στέλνεται κενό. */ ?>
                                        <input type="date" name="special_license_expiry[]" class="sl-expiry" value="<?php echo htmlspecialchars((string) ($license['expiry_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" <?php echo $slNoExpiry ? 'style="display:none;"' : ''; ?>>
                                        <label class="sl-no-expiry-label">
                                            <input type="checkbox" class="sl-no-expiry" <?php echo $slNoExpiry ? 'checked' : ''; ?>>
                                            <span>Χωρίς λήξη (αορίστου)</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Περιγραφή / Λεπτομέρειες</label>
                                    <input type="text" name="special_license_details[]" value="<?php echo htmlspecialchars($details, ENT_QUOTES, 'UTF-8'); ?>" placeholder="π.χ. εκδούσα αρχή, περιοχή ή τίτλος πιστοποιητικού">
                                    <p class="form-hint sl-other-hint" <?php echo $selected === 'other' ? '' : 'style="display:none;"'; ?>>Γράψε εδώ τον ακριβή τίτλο του πιστοποιητικού.</p>
                                </div>

                                <button type="button" class="btn-secondary remove-special-license">Αφαίρεση</button>
                            </div>
                        <?php };

                        foreach (array_values($driverSpecialLicenses ?? []) as $i => $license) {
                            $renderSpecialLicense($i, $license);
                        }
                        ?>

                        <div id="special-licenses-container"><!-- εδώ προστίθενται νέα μπλοκ --></div>

                        <template id="specialLicenseTemplate">
                            <?php $renderSpecialLicense('__IDX__'); ?>
                        </template>

                        <button type="button" id="add-special-license" class="btn-secondary">+ Προσθήκη ειδικής άδειας</button>
                        </div>

                        <script>
                        /*
                         * Προσθήκη/αφαίρεση μπλοκ ειδικών αδειών. Το παλιό
                         * initSpecialLicenses() του driver_edit_profile.js δούλευε με
                         * κρυφό template και clone — αντικαταστάθηκε από <template>.
                         */
                        (function () {
                            function wire(item) {
                                var remove = item.querySelector('.remove-special-license');
                                if (remove) {
                                    remove.addEventListener('click', function () { item.remove(); });
                                }
                                var type = item.querySelector('.sl-type');
                                var hint = item.querySelector('.sl-other-hint');
                                if (type && hint) {
                                    type.addEventListener('change', function () {
                                        hint.style.display = this.value === 'other' ? '' : 'none';
                                    });
                                }
                                // «Χωρίς λήξη»: κρύβει το πεδίο και το στέλνει κενό (=NULL).
                                // Το πεδίο μένει enabled ώστε τα arrays να μη χάσουν θέση.
                                var noExp = item.querySelector('.sl-no-expiry');
                                var exp = item.querySelector('.sl-expiry');
                                if (noExp && exp) {
                                    noExp.addEventListener('change', function () {
                                        if (this.checked) {
                                            exp.value = '';
                                            exp.style.display = 'none';
                                        } else {
                                            exp.style.display = '';
                                        }
                                    });
                                }
                            }

                            document.addEventListener('DOMContentLoaded', function () {
                                document.querySelectorAll('#special-licenses .special-license-item').forEach(function (item) {
                                    if (!item.closest('template')) { wire(item); }
                                });

                                var addBtn = document.getElementById('add-special-license');
                                var list = document.getElementById('special-licenses-container');
                                var tpl = document.getElementById('specialLicenseTemplate');
                                if (!addBtn || !list || !tpl) { return; }

                                addBtn.addEventListener('click', function () {
                                    var holder = document.createElement('div');
                                    holder.innerHTML = tpl.innerHTML.replace(/__IDX__/g, 'n' + Date.now());
                                    var item = holder.querySelector('.special-license-item');
                                    if (!item) { return; }
                                    list.appendChild(item);
                                    wire(item);
                                });
                            });
                        })();
                        </script>
                    </div>
