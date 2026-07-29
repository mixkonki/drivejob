<?php

use Drivejob\Core\CSRF;

/**
 * View για τις προτιμήσεις ταιριάσματος
 * 
 * Επιτρέπει στους χρήστες να ρυθμίσουν τα βάρη των διαφόρων κριτηρίων
 * για το ταίριασμα αγγελιών και οδηγών
 */

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'login');
    exit();
}

// Λήψη του τύπου του χρήστη
$userType = $_SESSION['user_role'] ?? '';
$isDriver = $userType === 'driver';

// Προεπιλεγμένες τιμές αν δεν υπάρχουν προτιμήσεις
$preferences = $preferences ?? [
    'location_weight' => 1.0,
    'job_type_weight' => 1.0,
    'vehicle_type_weight' => 1.0,
    'license_weight' => 1.0,
    'experience_weight' => 1.0,
    'skills_weight' => 1.0,
    'schedule_weight' => 1.0,
    'rating_weight' => 1.0
];

// Τίτλος σελίδας
$pageTitle = 'Προτιμήσεις Ταιριάσματος';

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">Προτιμήσεις Ταιριάσματος</h1>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5>Ρύθμιση Βαρών Κριτηρίων</h5>
                </div>
                <div class="card-body">
                    <p class="mb-4">
                        Ρυθμίστε τη σημαντικότητα κάθε κριτηρίου για το ταίριασμα αγγελιών και οδηγών.
                        Υψηλότερες τιμές σημαίνουν μεγαλύτερη σημαντικότητα στο συνολικό σκορ ταιριάσματος.
                    </p>

                    <form action="<?php echo BASE_URL; ?>matching/save-preferences" method="POST">
                        <?php echo CSRF::generateToken(); ?>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="location_weight">Τοποθεσία</label>
                                    <div class="d-flex align-items-center">
                                        <input type="range" class="form-range flex-grow-1 me-2" id="location_weight" name="location_weight"
                                            min="0" max="2" step="0.1" value="<?php echo $preferences['location_weight']; ?>">
                                        <span class="weight-value"><?php echo $preferences['location_weight']; ?></span>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="job_type_weight">Τύπος Εργασίας</label>
                                    <div class="d-flex align-items-center">
                                        <input type="range" class="form-range flex-grow-1 me-2" id="job_type_weight" name="job_type_weight"
                                            min="0" max="2" step="0.1" value="<?php echo $preferences['job_type_weight']; ?>">
                                        <span class="weight-value"><?php echo $preferences['job_type_weight']; ?></span>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="vehicle_type_weight">Τύπος Οχήματος</label>
                                    <div class="d-flex align-items-center">
                                        <input type="range" class="form-range flex-grow-1 me-2" id="vehicle_type_weight" name="vehicle_type_weight"
                                            min="0" max="2" step="0.1" value="<?php echo $preferences['vehicle_type_weight']; ?>">
                                        <span class="weight-value"><?php echo $preferences['vehicle_type_weight']; ?></span>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="license_weight">Άδειες Οδήγησης</label>
                                    <div class="d-flex align-items-center">
                                        <input type="range" class="form-range flex-grow-1 me-2" id="license_weight" name="license_weight"
                                            min="0" max="2" step="0.1" value="<?php echo $preferences['license_weight']; ?>">
                                        <span class="weight-value"><?php echo $preferences['license_weight']; ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="experience_weight">Εμπειρία</label>
                                    <div class="d-flex align-items-center">
                                        <input type="range" class="form-range flex-grow-1 me-2" id="experience_weight" name="experience_weight"
                                            min="0" max="2" step="0.1" value="<?php echo $preferences['experience_weight']; ?>">
                                        <span class="weight-value"><?php echo $preferences['experience_weight']; ?></span>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="skills_weight">Δεξιότητες</label>
                                    <div class="d-flex align-items-center">
                                        <input type="range" class="form-range flex-grow-1 me-2" id="skills_weight" name="skills_weight"
                                            min="0" max="2" step="0.1" value="<?php echo $preferences['skills_weight']; ?>">
                                        <span class="weight-value"><?php echo $preferences['skills_weight']; ?></span>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="schedule_weight">Πρόγραμμα Εργασίας</label>
                                    <div class="d-flex align-items-center">
                                        <input type="range" class="form-range flex-grow-1 me-2" id="schedule_weight" name="schedule_weight"
                                            min="0" max="2" step="0.1" value="<?php echo $preferences['schedule_weight']; ?>">
                                        <span class="weight-value"><?php echo $preferences['schedule_weight']; ?></span>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="rating_weight">Αξιολογήσεις</label>
                                    <div class="d-flex align-items-center">
                                        <input type="range" class="form-range flex-grow-1 me-2" id="rating_weight" name="rating_weight"
                                            min="0" max="2" step="0.1" value="<?php echo $preferences['rating_weight']; ?>">
                                        <span class="weight-value"><?php echo $preferences['rating_weight']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Αποθήκευση Προτιμήσεων</button>
                            <a href="<?php echo BASE_URL . ($isDriver ? 'drivers/profile' : 'companies/profile'); ?>" class="btn btn-secondary ms-2">Επιστροφή</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Ενημέρωση των τιμών των sliders σε πραγματικό χρόνο
    document.addEventListener('DOMContentLoaded', function() {
        const sliders = document.querySelectorAll('input[type="range"]');

        sliders.forEach(slider => {
            slider.addEventListener('input', function() {
                const valueDisplay = this.parentElement.querySelector('.weight-value');
                valueDisplay.textContent = this.value;
            });
        });
    });
</script>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/partials/footer.php';
?>