<!-- Οπτικό Βιογραφικό Οδηγού -->
<section class="profile-section driver-visual-resume">
    <h2>Βιογραφικό</h2>
    
    <div class="visual-resume-container">
        <!-- Προσωπικά Στοιχεία -->
        <div class="resume-section personal-info">
            <div class="section-header">
                <i class="fas fa-user"></i>
                <h3>Προσωπικά Στοιχεία</h3>
            </div>
            <div class="section-content">
                <div class="info-grid">
                    <?php if (isset($driver['birth_date']) && $driver['birth_date']) : ?>
                        <div class="info-item">
                            <span class="info-label">Ηλικία:</span>
                            <span class="info-value">
                                <?php
                                $birthDate = new DateTime($driver['birth_date']);
                                $now = new DateTime();
                                $age = $now->diff($birthDate)->y;
                                echo $age . ' ετών';
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($driver['marital_status']) && $driver['marital_status']) : ?>
                        <div class="info-item">
                            <span class="info-label">Οικογενειακή Κατάσταση:</span>
                            <span class="info-value">
                                <?php
                                $maritalStatus = '';
                                switch ($driver['marital_status']) {
                                    case 'single':
                                        $maritalStatus = 'Άγαμος/η';
                                        break;
                                    case 'married':
                                        $maritalStatus = 'Έγγαμος/η';
                                        break;
                                    case 'divorced':
                                        $maritalStatus = 'Διαζευγμένος/η';
                                        break;
                                    case 'widowed':
                                        $maritalStatus = 'Χήρος/α';
                                        break;
                                    default:
                                        $maritalStatus = $driver['marital_status'];
                                }
                                echo htmlspecialchars($maritalStatus);
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($driver['military_service']) && $driver['military_service']) : ?>
                        <div class="info-item">
                            <span class="info-label">Στρατιωτικές Υποχρεώσεις:</span>
                            <span class="info-value">
                                <?php
                                $militaryService = '';
                                switch ($driver['military_service']) {
                                    case 'completed':
                                        $militaryService = 'Εκπληρωμένες';
                                        break;
                                    case 'exempt':
                                        $militaryService = 'Απαλλαγή';
                                        break;
                                    case 'postponed':
                                        $militaryService = 'Αναβολή';
                                        break;
                                    case 'not_required':
                                        $militaryService = 'Δεν απαιτείται';
                                        break;
                                    default:
                                        $militaryService = $driver['military_service'];
                                }
                                echo htmlspecialchars($militaryService);
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($driver['education_level']) && $driver['education_level']) : ?>
                        <div class="info-item">
                            <span class="info-label">Επίπεδο Εκπαίδευσης:</span>
                            <span class="info-value">
                                <?php
                                $educationLevel = '';
                                switch ($driver['education_level']) {
                                    case 'primary':
                                        $educationLevel = 'Δημοτικό';
                                        break;
                                    case 'secondary':
                                        $educationLevel = 'Δευτεροβάθμια Εκπαίδευση';
                                        break;
                                    case 'highschool':
                                        $educationLevel = 'Λύκειο';
                                        break;
                                    case 'vocational':
                                        $educationLevel = 'Επαγγελματική Σχολή';
                                        break;
                                    case 'college':
                                        $educationLevel = 'ΙΕΚ/Κολλέγιο';
                                        break;
                                    case 'university':
                                        $educationLevel = 'Πανεπιστημιακή Εκπαίδευση';
                                        break;
                                    case 'postgraduate':
                                        $educationLevel = 'Μεταπτυχιακό';
                                        break;
                                    default:
                                        $educationLevel = $driver['education_level'];
                                }
                                echo htmlspecialchars($educationLevel);
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($driver['legal_status']) && $driver['legal_status']) : ?>
                        <div class="info-item">
                            <span class="info-label">Νομική Κατάσταση:</span>
                            <span class="info-value">
                                <?php echo htmlspecialchars($driver['legal_status']); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Επαγγελματική Εμπειρία -->
        <div class="resume-section experience">
            <div class="section-header">
                <i class="fas fa-briefcase"></i>
                <h3>Επαγγελματική Εμπειρία</h3>
            </div>
            <div class="section-content">
                <?php if (isset($driver['experience_years']) && $driver['experience_years'] > 0) : ?>
                    <div class="experience-years">
                        <span class="years-number"><?php echo $driver['experience_years']; ?></span>
                        <span class="years-text">έτη εμπειρίας</span>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($driver['work_experience']) && !empty($driver['work_experience'])) : ?>
                    <div class="experience-details">
                        <?php echo nl2br(htmlspecialchars($driver['work_experience'])); ?>
                    </div>
                <?php else : ?>
                    <p class="no-details-message">Δεν έχει καταχωρηθεί αναλυτική εργασιακή εμπειρία.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Γλωσσικές Ικανότητες -->
        <div class="resume-section languages">
            <div class="section-header">
                <i class="fas fa-language"></i>
                <h3>Γλωσσικές Ικανότητες</h3>
            </div>
            <div class="section-content">
                <div class="languages-grid">
                    <?php
                    $languageLevels = [
                        'native' => ['Μητρική', 5],
                        'fluent' => ['Άριστα', 4],
                        'good' => ['Καλά', 3],
                        'basic' => ['Βασικά', 1]
                    ];

                    $languages = [
                        'language_greek' => 'Ελληνικά',
                        'language_english' => 'Αγγλικά',
                        'language_german' => 'Γερμανικά',
                        'language_french' => 'Γαλλικά',
                        'language_italian' => 'Ιταλικά'
                    ];

                    foreach ($languages as $langKey => $langName) {
                        if (isset($driver[$langKey]) && !empty($driver[$langKey])) {
                            $level = $driver[$langKey];
                            $levelInfo = isset($languageLevels[$level]) ? $languageLevels[$level] : ['Άγνωστο', 0];
                            $dots = $levelInfo[1];
                            ?>
                        <div class="language-item">
                            <span class="language-name"><?php echo $langName; ?></span>
                            <div class="language-level">
                                <?php for ($i = 1; $i <= 5; $i++) : ?>
                                    <span class="level-dot <?php echo $i <= $dots ? 'filled' : ''; ?>"></span>
                                <?php endfor; ?>
                            </div>
                            <span class="level-text"><?php echo $levelInfo[0]; ?></span>
                        </div>
                            <?php
                        }
                    }

                    // Άλλη γλώσσα, αν υπάρχει
                    if (
                        isset($driver['language_other_name']) && !empty($driver['language_other_name']) &&
                        isset($driver['language_other_level']) && !empty($driver['language_other_level'])
                    ) {
                        $level = $driver['language_other_level'];
                        $levelInfo = isset($languageLevels[$level]) ? $languageLevels[$level] : ['Άγνωστο', 0];
                        $dots = $levelInfo[1];
                        ?>
                        <div class="language-item">
                            <span class="language-name"><?php echo htmlspecialchars($driver['language_other_name']); ?></span>
                            <div class="language-level">
                                <?php for ($i = 1; $i <= 5; $i++) : ?>
                                    <span class="level-dot <?php echo $i <= $dots ? 'filled' : ''; ?>"></span>
                                <?php endfor; ?>
                            </div>
                            <span class="level-text"><?php echo $levelInfo[0]; ?></span>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Δεξιότητες -->
        <div class="resume-section skills">
            <div class="section-header">
                <i class="fas fa-tools"></i>
                <h3>Δεξιότητες Οδηγού</h3>
            </div>
            <div class="section-content">
                <?php if (isset($driverSkills) && !empty($driverSkills)) : ?>
                    <div class="skills-grid">
                        <?php
                        $skillLabels = [
                            'defensive_driving' => 'Αμυντική Οδήγηση',
                            'eco_driving' => 'Οικολογική Οδήγηση',
                            'night_driving' => 'Νυχτερινή Οδήγηση',
                            'mountain_driving' => 'Οδήγηση σε Ορεινές Περιοχές',
                            'extreme_conditions' => 'Οδήγηση σε Ακραίες Συνθήκες',
                            'loading_securing' => 'Φόρτωση & Ασφάλιση Φορτίου',
                            'emergency_response' => 'Αντιμετώπιση Έκτακτων Καταστάσεων',
                            'first_aid' => 'Πρώτες Βοήθειες',
                            'dangerous_goods' => 'Διαχείριση Επικίνδυνων Εμπορευμάτων',
                            'tacograph_compliance' => 'Συμμόρφωση με Ταχογράφο',
                            'customer_service' => 'Εξυπηρέτηση Πελατών',
                            'time_management' => 'Διαχείριση Χρόνου',
                            'route_planning' => 'Σχεδιασμός Διαδρομής',
                            'conflict_resolution' => 'Επίλυση Συγκρούσεων',
                            'multilingual' => 'Πολύγλωσσος',
                            'vehicle_maintenance' => 'Συντήρηση Οχήματος',
                            'troubleshooting' => 'Αντιμετώπιση Βλαβών',
                            'digital_tachograph' => 'Ψηφιακός Ταχογράφος',
                            'gps_systems' => 'Συστήματα GPS',
                            'logistics_software' => 'Λογισμικό Logistics'
                        ];

                        foreach ($skillLabels as $skillKey => $skillLabel) {
                            if (isset($driverSkills[$skillKey]) && $driverSkills[$skillKey] == 1) {
                                ?>
                            <div class="skill-item">
                                <span class="skill-badge"><?php echo $skillLabel; ?></span>
                            </div>
                                <?php
                            }
                        }

                        // Επιπλέον δεξιότητες
                        if (isset($driver['additional_skills']) && !empty($driver['additional_skills'])) {
                            ?>
                            <div class="additional-skills">
                                <h4>Επιπλέον Δεξιότητες</h4>
                                <p><?php echo nl2br(htmlspecialchars($driver['additional_skills'])); ?></p>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                <?php else : ?>
                    <p class="no-details-message">Δεν έχουν καταχωρηθεί ειδικές δεξιότητες.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Κουμπί για το κατέβασμα του βιογραφικού -->
    <div class="resume-download-section">
        <a href="<?php echo BASE_URL; ?>drivers/download-resume/<?php echo $driver['id']; ?>" class="btn-primary download-resume">
            <i class="fas fa-file-pdf"></i>
            Κατέβασμα Βιογραφικού (PDF)
        </a>
    </div>
</section>

<style>
    /* Οπτικό Βιογραφικό Οδηγού */
    .driver-visual-resume {
        margin-bottom: 40px;
    }
    
    .visual-resume-container {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 25px;
    }
    
    .resume-section {
        background-color: #f8f9fa;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    .section-header {
        display: flex;
        align-items: center;
        background-color: #e9ecef;
        padding: 15px 20px;
    }
    
    .section-header i {
        font-size: 20px;
        color: #495057;
        margin-right: 10px;
    }
    
    .section-header h3 {
        margin: 0;
        font-size: 18px;
        color: #343a40;
    }
    
    .section-content {
        padding: 20px;
    }
    
    /* Προσωπικά Στοιχεία */
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 15px;
    }
    
    .info-item {
        display: flex;
        flex-direction: column;
    }
    
    .info-label {
        font-size: 14px;
        font-weight: 500;
        color: #6c757d;
        margin-bottom: 5px;
    }
    
    .info-value {
        font-size: 16px;
        color: #343a40;
    }
    
    /* Επαγγελματική Εμπειρία */
    .experience-years {
        display: flex;
        align-items: baseline;
        margin-bottom: 20px;
    }
    
    .years-number {
        font-size: 36px;
        font-weight: bold;
        color: #0d6efd;
        margin-right: 10px;
    }
    
    .years-text {
        font-size: 18px;
        color: #495057;
    }
    
    .experience-details {
        line-height: 1.6;
        color: #495057;
    }
    
    /* Γλωσσικές Ικανότητες */
    .languages-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 15px;
    }
    
    .language-item {
        display: flex;
        flex-direction: column;
    }
    
    .language-name {
        font-size: 16px;
        font-weight: 500;
        color: #343a40;
        margin-bottom: 5px;
    }
    
    .language-level {
        display: flex;
        gap: 3px;
        margin-bottom: 5px;
    }
    
    .level-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: #dee2e6;
    }
    
    .level-dot.filled {
        background-color: #0d6efd;
    }
    
    .level-text {
        font-size: 14px;
        color: #6c757d;
    }
    
    /* Δεξιότητες */
    .skills-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .skill-item {
        margin-bottom: 10px;
    }
    
    .skill-badge {
        display: inline-block;
        padding: 8px 15px;
        background-color: #e9ecef;
        color: #495057;
        border-radius: 20px;
        font-size: 14px;
    }
    
    .additional-skills {
        width: 100%;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #dee2e6;
    }
    
    .additional-skills h4 {
        margin-top: 0;
        margin-bottom: 10px;
        font-size: 16px;
        color: #343a40;
    }
    
    .no-details-message {
        font-style: italic;
        color: #6c757d;
    }
    
    /* Κουμπί Κατεβάσματος Βιογραφικού */
    .resume-download-section {
        margin-top: 30px;
        text-align: center;
    }
    
    .download-resume {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 25px;
        font-size: 16px;
        font-weight: 500;
        border-radius: 8px;
        background-color: #0d6efd;
        color: white;
        text-decoration: none;
        transition: background-color 0.3s;
    }
    
    .download-resume:hover {
        background-color: #0a58ca;
    }
    
    .download-resume i {
        font-size: 20px;
    }
    
    /* Προσαρμογή για μικρότερες οθόνες */
    @media (max-width: 992px) {
        .visual-resume-container {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 576px) {
        .info-grid, .languages-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
