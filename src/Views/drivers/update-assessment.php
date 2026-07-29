<?php
// Φόρτωση του header
include ROOT_DIR . '/src/Views/partials/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1 class="page-title">Αυτοαξιολόγηση Οδηγού</h1>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION['error_message']; ?>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success_message']; ?>
                    <?php unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5>Αξιολογήστε τις δεξιότητές σας</h5>
                    <p class="text-muted mb-0">Η αυτοαξιολόγηση βοηθά στην καλύτερη αντιστοίχιση με τις κατάλληλες θέσεις εργασίας.</p>
                </div>
                <div class="card-body">
                    <form action="<?php echo BASE_URL; ?>drivers/update-assessment" method="post">
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <div class="assessment-section">
                            <div class="form-group">
                                <label for="driving_skills">Οδηγικές Ικανότητες</label>
                                <div class="rating-container">
                                    <div class="rating-scale">
                                        <span>Αρχάριος</span>
                                        <span>Μέτριος</span>
                                        <span>Καλός</span>
                                        <span>Πολύ Καλός</span>
                                        <span>Άριστος</span>
                                    </div>
                                    <div class="rating-input">
                                        <?php
                                        $drivingSkills = isset($assessment['driving_skills']) ? (int)$assessment['driving_skills'] : 3;
                                        for ($i = 1; $i <= 5; $i++):
                                        ?>
                                            <input type="radio" id="driving_skills_<?php echo $i; ?>" name="driving_skills" value="<?php echo $i; ?>" <?php echo ($drivingSkills == $i) ? 'checked' : ''; ?>>
                                            <label for="driving_skills_<?php echo $i; ?>"><?php echo $i; ?></label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Αξιολογήστε τις οδηγικές σας ικανότητες, συμπεριλαμβανομένου του χειρισμού οχημάτων, της τήρησης του ΚΟΚ και της ασφαλούς οδήγησης.</small>
                            </div>

                            <div class="form-group">
                                <label for="vehicle_knowledge">Γνώση Οχημάτων</label>
                                <div class="rating-container">
                                    <div class="rating-scale">
                                        <span>Αρχάριος</span>
                                        <span>Μέτριος</span>
                                        <span>Καλός</span>
                                        <span>Πολύ Καλός</span>
                                        <span>Άριστος</span>
                                    </div>
                                    <div class="rating-input">
                                        <?php
                                        $vehicleKnowledge = isset($assessment['vehicle_knowledge']) ? (int)$assessment['vehicle_knowledge'] : 3;
                                        for ($i = 1; $i <= 5; $i++):
                                        ?>
                                            <input type="radio" id="vehicle_knowledge_<?php echo $i; ?>" name="vehicle_knowledge" value="<?php echo $i; ?>" <?php echo ($vehicleKnowledge == $i) ? 'checked' : ''; ?>>
                                            <label for="vehicle_knowledge_<?php echo $i; ?>"><?php echo $i; ?></label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Αξιολογήστε τις γνώσεις σας σχετικά με τη λειτουργία, τη συντήρηση και την επίλυση προβλημάτων των οχημάτων.</small>
                            </div>

                            <div class="form-group">
                                <label for="safety_awareness">Συνείδηση Ασφάλειας</label>
                                <div class="rating-container">
                                    <div class="rating-scale">
                                        <span>Αρχάριος</span>
                                        <span>Μέτριος</span>
                                        <span>Καλός</span>
                                        <span>Πολύ Καλός</span>
                                        <span>Άριστος</span>
                                    </div>
                                    <div class="rating-input">
                                        <?php
                                        $safetyAwareness = isset($assessment['safety_awareness']) ? (int)$assessment['safety_awareness'] : 3;
                                        for ($i = 1; $i <= 5; $i++):
                                        ?>
                                            <input type="radio" id="safety_awareness_<?php echo $i; ?>" name="safety_awareness" value="<?php echo $i; ?>" <?php echo ($safetyAwareness == $i) ? 'checked' : ''; ?>>
                                            <label for="safety_awareness_<?php echo $i; ?>"><?php echo $i; ?></label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Αξιολογήστε την ικανότητά σας να αναγνωρίζετε και να αποφεύγετε κινδύνους, να τηρείτε τους κανόνες ασφαλείας και να προστατεύετε το φορτίο.</small>
                            </div>

                            <div class="form-group">
                                <label for="time_management">Διαχείριση Χρόνου</label>
                                <div class="rating-container">
                                    <div class="rating-scale">
                                        <span>Αρχάριος</span>
                                        <span>Μέτριος</span>
                                        <span>Καλός</span>
                                        <span>Πολύ Καλός</span>
                                        <span>Άριστος</span>
                                    </div>
                                    <div class="rating-input">
                                        <?php
                                        $timeManagement = isset($assessment['time_management']) ? (int)$assessment['time_management'] : 3;
                                        for ($i = 1; $i <= 5; $i++):
                                        ?>
                                            <input type="radio" id="time_management_<?php echo $i; ?>" name="time_management" value="<?php echo $i; ?>" <?php echo ($timeManagement == $i) ? 'checked' : ''; ?>>
                                            <label for="time_management_<?php echo $i; ?>"><?php echo $i; ?></label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Αξιολογήστε την ικανότητά σας να διαχειρίζεστε αποτελεσματικά το χρόνο, να τηρείτε προθεσμίες και να προγραμματίζετε διαδρομές.</small>
                            </div>

                            <div class="form-group">
                                <label for="customer_service">Εξυπηρέτηση Πελατών</label>
                                <div class="rating-container">
                                    <div class="rating-scale">
                                        <span>Αρχάριος</span>
                                        <span>Μέτριος</span>
                                        <span>Καλός</span>
                                        <span>Πολύ Καλός</span>
                                        <span>Άριστος</span>
                                    </div>
                                    <div class="rating-input">
                                        <?php
                                        $customerService = isset($assessment['customer_service']) ? (int)$assessment['customer_service'] : 3;
                                        for ($i = 1; $i <= 5; $i++):
                                        ?>
                                            <input type="radio" id="customer_service_<?php echo $i; ?>" name="customer_service" value="<?php echo $i; ?>" <?php echo ($customerService == $i) ? 'checked' : ''; ?>>
                                            <label for="customer_service_<?php echo $i; ?>"><?php echo $i; ?></label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Αξιολογήστε την ικανότητά σας να επικοινωνείτε αποτελεσματικά με πελάτες, να επιλύετε προβλήματα και να παρέχετε επαγγελματική εξυπηρέτηση.</small>
                            </div>

                            <div class="form-group">
                                <label for="stress_handling">Διαχείριση Άγχους</label>
                                <div class="rating-container">
                                    <div class="rating-scale">
                                        <span>Αρχάριος</span>
                                        <span>Μέτριος</span>
                                        <span>Καλός</span>
                                        <span>Πολύ Καλός</span>
                                        <span>Άριστος</span>
                                    </div>
                                    <div class="rating-input">
                                        <?php
                                        $stressHandling = isset($assessment['stress_handling']) ? (int)$assessment['stress_handling'] : 3;
                                        for ($i = 1; $i <= 5; $i++):
                                        ?>
                                            <input type="radio" id="stress_handling_<?php echo $i; ?>" name="stress_handling" value="<?php echo $i; ?>" <?php echo ($stressHandling == $i) ? 'checked' : ''; ?>>
                                            <label for="stress_handling_<?php echo $i; ?>"><?php echo $i; ?></label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Αξιολογήστε την ικανότητά σας να παραμένετε ήρεμοι και αποτελεσματικοί σε αγχωτικές καταστάσεις, όπως κυκλοφοριακή συμφόρηση ή πιεστικές προθεσμίες.</small>
                            </div>

                            <div class="form-group">
                                <label for="comments">Σχόλια & Παρατηρήσεις</label>
                                <textarea name="comments" id="comments" class="form-control" rows="4" placeholder="Προσθέστε τυχόν σχόλια ή παρατηρήσεις σχετικά με τις δεξιότητές σας..."><?php echo isset($assessment['comments']) ? htmlspecialchars($assessment['comments']) : ''; ?></textarea>
                                <small class="form-text text-muted">Προσθέστε οποιεσδήποτε πρόσθετες πληροφορίες που θεωρείτε σημαντικές για τις δεξιότητές σας ως οδηγός.</small>
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Αποθήκευση Αυτοαξιολόγησης
                            </button>
                            <a href="<?php echo BASE_URL; ?>drivers/profile" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Ακύρωση
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .page-title {
        margin-bottom: 20px;
    }

    .card {
        margin-bottom: 30px;
        border: none;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .card-header {
        background-color: #f8f9fa;
        padding: 15px 20px;
    }

    .form-group {
        margin-bottom: 25px;
    }

    label {
        font-weight: 600;
        margin-bottom: 8px;
        display: block;
    }

    .rating-container {
        margin-bottom: 10px;
    }

    .rating-scale {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
        font-size: 12px;
        color: #666;
    }

    .rating-input {
        display: flex;
        justify-content: space-between;
        position: relative;
        height: 30px;
    }

    .rating-input::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 2px;
        background-color: #ddd;
        transform: translateY(-50%);
        z-index: 0;
    }

    .rating-input input[type="radio"] {
        display: none;
    }

    .rating-input label {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background-color: #f0f0f0;
        border: 2px solid #ddd;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        position: relative;
        z-index: 1;
        transition: all 0.2s ease;
        margin: 0;
        font-weight: normal;
        color: #666;
    }

    .rating-input input[type="radio"]:checked+label {
        background-color: #007bff;
        border-color: #007bff;
        color: white;
    }

    .assessment-section {
        padding: 10px 0;
    }

    .btn {
        padding: 8px 16px;
        margin-right: 10px;
    }
</style>

<?php
// Φόρτωση του footer
include ROOT_DIR . '/src/Views/partials/footer.php';
?>