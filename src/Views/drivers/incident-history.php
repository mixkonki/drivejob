<?php

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver_profile.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver-incidents.css">

<main>
    <div class="container">
        <div class="incidents-header">
            <h1>Ιστορικό Συμβάντων</h1>
            <a href="<?php echo BASE_URL; ?>drivers/report-incident" class="btn-primary">Καταχώρηση Νέου Συμβάντος</a>
        </div>
        
        <?php if (isset($_SESSION['success_message'])) : ?>
            <div class="success-message">
                <?php echo $_SESSION['success_message']; ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($incidents)) : ?>
            <div class="no-incidents">
                <p>Δεν έχετε καταχωρήσει κανένα συμβάν.</p>
                <p>Η καταχώρηση συμβάντων είναι εθελοντική αλλά συνιστάται για την ακριβέστερη αξιολόγηση της οδηγικής σας συμπεριφοράς.</p>
            </div>
        <?php else : ?>
            <div class="incidents-list">
                <?php foreach ($incidents as $incident) : ?>
                    <div class="incident-item severity-<?php echo $incident['severity']; ?>">
                        <div class="incident-header">
                            <div class="incident-type">
                                <?php
                                $typeLabels = [
                                'accident' => 'Ατύχημα',
                                'traffic_violation' => 'Παράβαση ΚΟΚ',
                                'near_miss' => 'Παρ\' ολίγον ατύχημα',
                                'complaint' => 'Παράπονο',
                                'other' => 'Άλλο'
                                ];
                                echo isset($typeLabels[$incident['incident_type']]) ? $typeLabels[$incident['incident_type']] : $incident['incident_type'];
                                ?>
                        </div>
                        <div class="incident-date"><?php echo date('d/m/Y', strtotime($incident['incident_date'])); ?></div>
                    </div>
                    
                    <div class="incident-severity">
                        Σοβαρότητα: 
                        <?php for ($i = 1; $i <= 5; $i++) : ?>
                            <span class="severity-dot <?php echo $i <= $incident['severity'] ? 'active' : ''; ?>"></span>
                        <?php endfor; ?>
                    </div>
                    
                    <div class="incident-description">
                        <?php echo nl2br(htmlspecialchars($incident['description'])); ?>
                    </div>
                    
                    <div class="incident-verification">
                        <?php if ($incident['verified']) : ?>
                            <span class="verification-badge verified">Επαληθευμένο</span>
                        <?php else : ?>
                            <span class="verification-badge unverified">Μη επαληθευμένο</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
        </div>
        
        <div class="incidents-info">
            <p><strong>Σημείωση:</strong> Η επαλήθευση συμβάντων γίνεται αυτόματα ή από το διαχειριστή του συστήματος. 
            Τα μη επαληθευμένα συμβάντα μπορεί να έχουν μικρότερη επίδραση στη βαθμολογία σας.</p>
        </div>
        <?php endif; ?>
</div>
</main>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php';
?>
