<?php
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';

// Ανάκτηση σφαλμάτων και παλιών τιμών από το session
$errors = $_SESSION['errors'] ?? [];
$oldInput = $_SESSION['old_input'] ?? [];

// Καθαρισμός των session μεταβλητών μετά την ανάκτησή τους
unset($_SESSION['errors'], $_SESSION['old_input']);

// Σημείωση: Οι συναρτήσεις old(), hasError() και getError() ήδη ορίζονται στο form_helpers.php
// και δεν πρέπει να οριστούν ξανά εδώ
?>

<main>
    <div class="container">
        <h1>Επεξεργασία Προφίλ Εταιρείας</h1>

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

        <form action="<?php echo BASE_URL; ?>companies/update-profile" method="POST" enctype="multipart/form-data" class="edit-profile-form">
            <?php echo \Drivejob\Core\CSRF::tokenField(); ?>

            <div class="form-tabs">
                <div class="tab-nav">
                    <button type="button" class="tab-btn active" data-tab="basic-info">Βασικές Πληροφορίες</button>
                    <button type="button" class="tab-btn" data-tab="company-details">Στοιχεία Εταιρείας</button>
                    <button type="button" class="tab-btn" data-tab="fleet-management">Διαχείριση Στόλου</button>
                    <button type="button" class="tab-btn" data-tab="driver-management">Διαχείριση Οδηγών</button>
                    <button type="button" class="tab-btn" data-tab="compliance">Συμμόρφωση & Νομικά</button>
                    <button type="button" class="tab-btn" data-tab="services">Υπηρεσίες & Modules</button>
                    <button type="button" class="tab-btn" data-tab="location">Τοποθεσία</button>
                    <button type="button" class="tab-btn" data-tab="contact">Επικοινωνία</button>
                    <button type="button" class="tab-btn" data-tab="social">Κοινωνικά Δίκτυα</button>
                </div>

                <div class="tab-content">
                    <!-- Βασικές Πληροφορίες -->
                    <div class="tab-pane active" id="basic-info">
                        <h2>Βασικές Πληροφορίες</h2>

                        <div class="form-group <?php echo hasError('company_name') ? 'has-error' : ''; ?>">
                            <label for="company_name">Όνομα Εταιρείας</label>
                            <input type="text" id="company_name" name="company_name" value="<?php echo old('company_name', $companyData['company_name'] ?? ''); ?>" required>
                            <?php if (hasError('company_name')) : ?>
                                <div class="error-message"><?php echo getError('company_name'); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="company_logo">Λογότυπο Εταιρείας</label>
                            <?php if (isset($companyData['company_logo']) && $companyData['company_logo']) : ?>
                                <div class="current-logo">
                                    <img src="<?php echo BASE_URL . htmlspecialchars($companyData['company_logo']); ?>" alt="Τρέχον λογότυπο">
                                    <p>Τρέχον λογότυπο</p>
                                </div>
                            <?php endif; ?>
                            <input type="file" id="company_logo" name="company_logo" accept="image/jpeg, image/png, image/gif">
                            <p class="form-hint">Μέγιστο μέγεθος: 2MB. Επιτρεπόμενοι τύποι: JPEG, PNG, GIF</p>
                        </div>

                        <div class="form-group">
                            <label for="description">Περιγραφή Εταιρείας</label>
                            <textarea id="description" name="description" rows="6"><?php echo old('description', $companyData['description'] ?? ''); ?></textarea>
                            <p class="form-hint">Περιγράψτε την εταιρεία σας, τις δραστηριότητες και το όραμά σας.</p>
                        </div>
                    </div>

                    <!-- Στοιχεία Εταιρείας -->
                    <div class="tab-pane" id="company-details">
                        <h2>Στοιχεία Εταιρείας</h2>

                        <div class="form-group">
                            <label for="industry">Κλάδος</label>
                            <select id="industry" name="industry">
                                <option value="">Επιλέξτε Κλάδο</option>
                                <option value="Μεταφορές & Logistics" <?php echo old('industry', $companyData['industry'] ?? '') === 'Μεταφορές & Logistics' ? 'selected' : ''; ?>>Μεταφορές & Logistics</option>
                                <option value="Κατασκευές" <?php echo old('industry', $companyData['industry'] ?? '') === 'Κατασκευές' ? 'selected' : ''; ?>>Κατασκευές</option>
                                <option value="Βιομηχανία" <?php echo old('industry', $companyData['industry'] ?? '') === 'Βιομηχανία' ? 'selected' : ''; ?>>Βιομηχανία</option>
                                <option value="Τρόφιμα & Ποτά" <?php echo old('industry', $companyData['industry'] ?? '') === 'Τρόφιμα & Ποτά' ? 'selected' : ''; ?>>Τρόφιμα & Ποτά</option>
                                <option value="Λιανεμπόριο" <?php echo old('industry', $companyData['industry'] ?? '') === 'Λιανεμπόριο' ? 'selected' : ''; ?>>Λιανεμπόριο</option>
                                <option value="Υπηρεσίες" <?php echo old('industry', $companyData['industry'] ?? '') === 'Υπηρεσίες' ? 'selected' : ''; ?>>Υπηρεσίες</option>
                                <option value="Άλλο" <?php echo old('industry', $companyData['industry'] ?? '') === 'Άλλο' ? 'selected' : ''; ?>>Άλλο</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="company_size">Μέγεθος Εταιρείας</label>
                            <select id="company_size" name="company_size">
                                <option value="">Επιλέξτε Μέγεθος</option>
                                <option value="1-10 εργαζόμενοι" <?php echo old('company_size', $companyData['company_size'] ?? '') === '1-10 εργαζόμενοι' ? 'selected' : ''; ?>>1-10 εργαζόμενοι</option>
                                <option value="11-50 εργαζόμενοι" <?php echo old('company_size', $companyData['company_size'] ?? '') === '11-50 εργαζόμενοι' ? 'selected' : ''; ?>>11-50 εργαζόμενοι</option>
                                <option value="51-200 εργαζόμενοι" <?php echo old('company_size', $companyData['company_size'] ?? '') === '51-200 εργαζόμενοι' ? 'selected' : ''; ?>>51-200 εργαζόμενοι</option>
                                <option value="201-500 εργαζόμενοι" <?php echo old('company_size', $companyData['company_size'] ?? '') === '201-500 εργαζόμενοι' ? 'selected' : ''; ?>>201-500 εργαζόμενοι</option>
                                <option value="501+ εργαζόμενοι" <?php echo old('company_size', $companyData['company_size'] ?? '') === '501+ εργαζόμενοι' ? 'selected' : ''; ?>>501+ εργαζόμενοι</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="foundation_year">Έτος Ίδρυσης</label>
                            <input type="number" id="foundation_year" name="foundation_year" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo old('foundation_year', $companyData['foundation_year'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="vat_number">ΑΦΜ</label>
                            <input type="text" id="vat_number" name="vat_number" value="<?php echo old('vat_number', $companyData['vat_number'] ?? ''); ?>">
                        </div>

                        <!-- Νέα πεδία για τύπους μεταφορών -->
                        <h3>Τύποι Μεταφορών</h3>
                        <div class="form-group">
                            <label>Επιλέξτε τους τύπους μεταφορών που εκτελείτε:</label>
                            <div class="checkbox-group">
                                <?php
                                $transportTypes = json_decode($companyData['transport_types'] ?? '[]', true) ?: [];
                                $availableTypes = [
                                    'national' => 'Εθνικές Μεταφορές',
                                    'international' => 'Διεθνείς Μεταφορές',
                                    'urban' => 'Αστικές Διανομές',
                                    'refrigerated' => 'Ψυγεία',
                                    'hazmat' => 'Επικίνδυνα Φορτία (ADR)',
                                    'bulk' => 'Χύδην Φορτία',
                                    'container' => 'Containers',
                                    'vehicle_transport' => 'Μεταφορά Οχημάτων',
                                    'livestock' => 'Μεταφορά Ζώων',
                                    'oversized' => 'Υπερμεγέθη Φορτία'
                                ];
                                foreach ($availableTypes as $value => $label) : ?>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="transport_types[]" value="<?php echo $value; ?>"
                                            <?php echo in_array($value, $transportTypes) ? 'checked' : ''; ?>>
                                        <?php echo $label; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Διαχείριση Στόλου (νέο) -->
                    <div class="tab-pane" id="fleet-management">
                        <h2>Διαχείριση Στόλου - DriveFleet Solutions</h2>

                        <div class="form-group">
                            <label for="fleet_size">Μέγεθος Στόλου</label>
                            <input type="number" id="fleet_size" name="fleet_size" min="0"
                                value="<?php echo old('fleet_size', $companyData['fleet_size'] ?? 0); ?>">
                            <p class="form-hint">Συνολικός αριθμός οχημάτων στον στόλο σας</p>
                        </div>

                        <h3>Συστήματα Διαχείρισης Στόλου</h3>
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="has_fleet_management" value="1"
                                    <?php echo old('has_fleet_management', $companyData['has_fleet_management'] ?? false) ? 'checked' : ''; ?>>
                                Χρησιμοποιούμε σύστημα διαχείρισης στόλου
                            </label>

                            <label class="checkbox-label">
                                <input type="checkbox" name="has_telematics" value="1"
                                    <?php echo old('has_telematics', $companyData['has_telematics'] ?? false) ? 'checked' : ''; ?>>
                                Χρησιμοποιούμε σύστημα telematics
                            </label>

                            <label class="checkbox-label">
                                <input type="checkbox" name="has_route_optimization" value="1"
                                    <?php echo old('has_route_optimization', $companyData['has_route_optimization'] ?? false) ? 'checked' : ''; ?>>
                                Χρησιμοποιούμε βελτιστοποίηση διαδρομών
                            </label>
                        </div>

                        <div class="form-group">
                            <label for="maintenance_provider">Πάροχος Συντήρησης</label>
                            <input type="text" id="maintenance_provider" name="maintenance_provider"
                                value="<?php echo old('maintenance_provider', $companyData['maintenance_provider'] ?? ''); ?>"
                                placeholder="π.χ. Επίσημο Service Mercedes">
                        </div>

                        <div class="info-box">
                            <h4>🚛 DriveFleet Solutions</h4>
                            <p>Βελτιστοποιήστε τη λειτουργία του στόλου σας με:</p>
                            <ul>
                                <li>Asset Management & Maintenance Planning</li>
                                <li>Route Optimization με AI</li>
                                <li>Real-time Monitoring & Analytics</li>
                                <li>Telematics Integration</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Διαχείριση Οδηγών (νέο) -->
                    <div class="tab-pane" id="driver-management">
                        <h2>Διαχείριση Οδηγών - DriveManager Pro</h2>

                        <div class="form-group">
                            <label for="active_drivers">Ενεργοί Οδηγοί</label>
                            <input type="number" id="active_drivers" name="active_drivers" min="0"
                                value="<?php echo old('active_drivers', $companyData['active_drivers'] ?? 0); ?>">
                            <p class="form-hint">Αριθμός οδηγών που απασχολείτε αυτή τη στιγμή</p>
                        </div>

                        <h3>Συστήματα HR & Εκπαίδευσης</h3>
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="has_hr_system" value="1"
                                    <?php echo old('has_hr_system', $companyData['has_hr_system'] ?? false) ? 'checked' : ''; ?>>
                                Διαθέτουμε σύστημα διαχείρισης προσωπικού
                            </label>

                            <label class="checkbox-label">
                                <input type="checkbox" name="has_payroll_system" value="1"
                                    <?php echo old('has_payroll_system', $companyData['has_payroll_system'] ?? false) ? 'checked' : ''; ?>>
                                Διαθέτουμε αυτοματοποιημένο σύστημα μισθοδοσίας
                            </label>

                            <label class="checkbox-label">
                                <input type="checkbox" name="has_training_program" value="1"
                                    <?php echo old('has_training_program', $companyData['has_training_program'] ?? false) ? 'checked' : ''; ?>>
                                Διαθέτουμε πρόγραμμα εκπαίδευσης οδηγών
                            </label>
                        </div>

                        <div class="form-group">
                            <label for="average_hiring_time">Μέσος Χρόνος Πρόσληψης (ημέρες)</label>
                            <input type="number" id="average_hiring_time" name="average_hiring_time" min="0"
                                value="<?php echo old('average_hiring_time', $companyData['average_hiring_time'] ?? ''); ?>"
                                placeholder="π.χ. 14">
                        </div>

                        <div class="info-box">
                            <h4>👥 DriveManager Pro</h4>
                            <p>Διαχειριστείτε αποτελεσματικά το προσωπικό σας με:</p>
                            <ul>
                                <li>Ψηφιακός Φάκελος Οδηγού</li>
                                <li>Έξυπνο Scheduling με AI</li>
                                <li>Παρακολούθηση Απόδοσης & KPIs</li>
                                <li>Αυτοματοποιημένη Μισθοδοσία</li>
                                <li>Training Management & Career Development</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Συμμόρφωση & Νομικά (νέο) -->
                    <div class="tab-pane" id="compliance">
                        <h2>Συμμόρφωση & Νομική Υποστήριξη</h2>

                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="has_legal_support" value="1"
                                    <?php echo old('has_legal_support', $companyData['has_legal_support'] ?? false) ? 'checked' : ''; ?>>
                                Διαθέτουμε νομική υποστήριξη
                            </label>

                            <label class="checkbox-label">
                                <input type="checkbox" name="operates_internationally" value="1"
                                    <?php echo old('operates_internationally', $companyData['operates_internationally'] ?? false) ? 'checked' : ''; ?>>
                                Δραστηριοποιούμαστε διεθνώς
                            </label>
                        </div>

                        <div class="form-group" id="operating_countries_group" style="display: none;">
                            <label>Χώρες Δραστηριοποίησης</label>
                            <div class="checkbox-group">
                                <?php
                                $operatingCountries = json_decode($companyData['operating_countries'] ?? '[]', true) ?: [];
                                $countries = [
                                    'GR' => 'Ελλάδα',
                                    'DE' => 'Γερμανία',
                                    'IT' => 'Ιταλία',
                                    'FR' => 'Γαλλία',
                                    'ES' => 'Ισπανία',
                                    'NL' => 'Ολλανδία',
                                    'BE' => 'Βέλγιο',
                                    'AT' => 'Αυστρία',
                                    'PL' => 'Πολωνία',
                                    'RO' => 'Ρουμανία',
                                    'BG' => 'Βουλγαρία',
                                    'HU' => 'Ουγγαρία',
                                    'CZ' => 'Τσεχία',
                                    'SK' => 'Σλοβακία'
                                ];
                                foreach ($countries as $code => $name) : ?>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="operating_countries[]" value="<?php echo $code; ?>"
                                            <?php echo in_array($code, $operatingCountries) ? 'checked' : ''; ?>>
                                        <?php echo $name; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <h3>Πιστοποιήσεις & Εξειδικεύσεις</h3>
                        <div class="form-group">
                            <label>Εξειδικεύσεις Μεταφορών</label>
                            <div class="checkbox-group">
                                <?php
                                $specializations = json_decode($companyData['specializations'] ?? '[]', true) ?: [];
                                $availableSpecs = [
                                    'ADR' => 'ADR - Επικίνδυνα Φορτία',
                                    'ATP' => 'ATP - Ψυγεία',
                                    'SQAS' => 'SQAS - Χημικά',
                                    'GDP' => 'GDP - Φάρμακα',
                                    'ISO9001' => 'ISO 9001',
                                    'ISO14001' => 'ISO 14001',
                                    'ISO45001' => 'ISO 45001',
                                    'HACCP' => 'HACCP - Τρόφιμα'
                                ];
                                foreach ($availableSpecs as $value => $label) : ?>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="specializations[]" value="<?php echo $value; ?>"
                                            <?php echo in_array($value, $specializations) ? 'checked' : ''; ?>>
                                        <?php echo $label; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="info-box">
                            <h4>⚖️ DriveJobs Legal Hub</h4>
                            <p>Παραμείνετε συμμορφωμένοι με:</p>
                            <ul>
                                <li>Regulatory Updates & Compliance Tools</li>
                                <li>Νομική Υποστήριξη & Συμβουλές</li>
                                <li>Εξειδικευμένα Modules ανά τύπο μεταφοράς</li>
                                <li>AI-Powered Compliance Assistant</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Υπηρεσίες & Modules (νέο) -->
                    <div class="tab-pane" id="services">
                        <h2>Υπηρεσίες & Subscription</h2>

                        <div class="form-group">
                            <label for="subscription_plan">Πακέτο Συνδρομής</label>
                            <select id="subscription_plan" name="subscription_plan">
                                <option value="basic" <?php echo old('subscription_plan', $companyData['subscription_plan'] ?? 'basic') === 'basic' ? 'selected' : ''; ?>>Basic - Βασικές Λειτουργίες</option>
                                <option value="professional" <?php echo old('subscription_plan', $companyData['subscription_plan'] ?? '') === 'professional' ? 'selected' : ''; ?>>Professional - Προηγμένες Λειτουργίες</option>
                                <option value="enterprise" <?php echo old('subscription_plan', $companyData['subscription_plan'] ?? '') === 'enterprise' ? 'selected' : ''; ?>>Enterprise - Πλήρες Πακέτο</option>
                                <option value="custom" <?php echo old('subscription_plan', $companyData['subscription_plan'] ?? '') === 'custom' ? 'selected' : ''; ?>>Custom - Προσαρμοσμένο</option>
                            </select>
                        </div>

                        <h3>Ενεργά Modules</h3>
                        <div class="checkbox-group">
                            <?php
                            $enabledModules = json_decode($companyData['enabled_modules'] ?? '[]', true) ?: [];
                            $availableModules = [
                                'job_posting' => '📢 Δημοσίευση Αγγελιών',
                                'driver_search' => '🔍 Αναζήτηση Οδηγών',
                                'ats' => '📋 Applicant Tracking System',
                                'driver_management' => '👥 DriveManager Pro',
                                'fleet_management' => '🚛 DriveFleet Solutions',
                                'compliance' => '⚖️ Legal & Compliance Hub',
                                'analytics' => '📊 Advanced Analytics',
                                'api_access' => '🔌 API Access'
                            ];
                            foreach ($availableModules as $value => $label) : ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="enabled_modules[]" value="<?php echo $value; ?>"
                                        <?php echo in_array($value, $enabledModules) ? 'checked' : ''; ?>>
                                    <?php echo $label; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <h3>Στατιστικά Χρήσης</h3>
                        <div class="stats-grid">
                            <div class="stat-box">
                                <label>Μηνιαίες Αγγελίες</label>
                                <div class="stat-value"><?php echo $companyData['monthly_job_posts'] ?? 0; ?></div>
                            </div>
                            <div class="stat-box">
                                <label>Επιτυχημένες Προσλήψεις</label>
                                <div class="stat-value"><?php echo $companyData['successful_hires'] ?? 0; ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Τοποθεσία -->
                    <div class="tab-pane" id="location">
                        <h2>Τοποθεσία</h2>

                        <div class="form-group">
                            <label for="address">Διεύθυνση</label>
                            <input type="text" id="address" name="address" value="<?php echo old('address', $companyData['address'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="city">Πόλη</label>
                            <input type="text" id="city" name="city" value="<?php echo old('city', $companyData['city'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="country">Χώρα</label>
                            <input type="text" id="country" name="country" value="<?php echo old('country', $companyData['country'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="postal_code">Ταχυδρομικός Κώδικας</label>
                            <input type="text" id="postal_code" name="postal_code" value="<?php echo old('postal_code', $companyData['postal_code'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Επικοινωνία -->
                    <div class="tab-pane" id="contact">
                        <h2>Στοιχεία Επικοινωνίας</h2>

                        <div class="form-group <?php echo hasError('phone') ? 'has-error' : ''; ?>">
                            <label for="phone">Τηλέφωνο</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo old('phone', $companyData['phone'] ?? ''); ?>" required>
                            <?php if (hasError('phone')) : ?>
                                <div class="error-message"><?php echo getError('phone'); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group <?php echo hasError('website') ? 'has-error' : ''; ?>">
                            <label for="website">Ιστοσελίδα</label>
                            <input type="url" id="website" name="website" value="<?php echo old('website', $companyData['website'] ?? ''); ?>" placeholder="https://www.example.com">
                            <?php if (hasError('website')) : ?>
                                <div class="error-message"><?php echo getError('website'); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="contact_person">Υπεύθυνος Επικοινωνίας</label>
                            <input type="text" id="contact_person" name="contact_person" value="<?php echo old('contact_person', $companyData['contact_person'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="position">Θέση</label>
                            <input type="text" id="position" name="position" value="<?php echo old('position', $companyData['position'] ?? ''); ?>" placeholder="π.χ. Διευθυντής HR">
                        </div>
                    </div>

                    <!-- Κοινωνικά Δίκτυα -->
                    <div class="tab-pane" id="social">
                        <h2>Κοινωνικά Δίκτυα</h2>

                        <div class="form-group">
                            <label for="social_linkedin">LinkedIn</label>
                            <input type="url" id="social_linkedin" name="social_linkedin" value="<?php echo old('social_linkedin', $companyData['social_linkedin'] ?? ''); ?>" placeholder="https://www.linkedin.com/company/yourcompany">
                        </div>

                        <div class="form-group">
                            <label for="social_facebook">Facebook</label>
                            <input type="url" id="social_facebook" name="social_facebook" value="<?php echo old('social_facebook', $companyData['social_facebook'] ?? ''); ?>" placeholder="https://www.facebook.com/yourcompany">
                        </div>

                        <div class="form-group">
                            <label for="social_twitter">Twitter</label>
                            <input type="url" id="social_twitter" name="social_twitter" value="<?php echo old('social_twitter', $companyData['social_twitter'] ?? ''); ?>" placeholder="https://twitter.com/yourcompany">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">Αποθήκευση Αλλαγών</button>
                <a href="<?php echo BASE_URL; ?>companies/company_profile" class="btn-secondary">Ακύρωση</a>
            </div>
        </form>
    </div>
</main>

<style>
    /* Επιπλέον CSS για τα νέα πεδία */
    .checkbox-group {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 10px;
        margin-top: 10px;
    }

    .checkbox-label {
        display: flex;
        align-items: center;
        padding: 8px;
        background: #f5f5f5;
        border-radius: 4px;
        cursor: pointer;
        transition: background 0.2s;
    }

    .checkbox-label:hover {
        background: #e8e8e8;
    }

    .checkbox-label input[type="checkbox"] {
        margin-right: 8px;
    }

    .info-box {
        background: #e3f2fd;
        border: 1px solid #1976d2;
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
    }

    .info-box h4 {
        color: #1976d2;
        margin-bottom: 10px;
    }

    .info-box ul {
        margin-left: 20px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .stat-box {
        background: #f5f5f5;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
    }

    .stat-box label {
        display: block;
        color: #666;
        margin-bottom: 10px;
    }

    .stat-value {
        font-size: 2em;
        font-weight: bold;
        color: #aa3636;
    }

    .form-hint {
        font-size: 0.9em;
        color: #666;
        margin-top: 5px;
    }

    /* Responsive για τα tabs */
    @media (max-width: 768px) {
        .tab-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .tab-btn {
            font-size: 0.9em;
            padding: 8px 12px;
        }
    }
</style>


<!-- Προσθήκη του JavaScript για τις λειτουργίες -->
<?= \Drivejob\Helpers\Asset::js('js/company-features.js', false) ?>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/partials/footer.php';
?>