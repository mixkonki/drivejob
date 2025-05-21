<?php
// Φόρτωση του header
include ROOT_DIR . '/src/Views/partials/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1 class="page-title">Αναφορά Περιστατικού</h1>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION['error_message']; ?>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['errors']) && is_array($_SESSION['errors'])): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($_SESSION['errors'] as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php unset($_SESSION['errors']); ?>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form action="<?php echo BASE_URL; ?>drivers/save-incident" method="post" enctype="multipart/form-data">
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <div class="form-group">
                            <label for="incident_type">Τύπος Περιστατικού <span class="text-danger">*</span></label>
                            <select name="incident_type" id="incident_type" class="form-control" required>
                                <option value="">-- Επιλέξτε Τύπο Περιστατικού --</option>
                                <option value="Ατύχημα" <?php echo (isset($_SESSION['old_input']['incident_type']) && $_SESSION['old_input']['incident_type'] == 'Ατύχημα') ? 'selected' : ''; ?>>Ατύχημα</option>
                                <option value="Βλάβη Οχήματος" <?php echo (isset($_SESSION['old_input']['incident_type']) && $_SESSION['old_input']['incident_type'] == 'Βλάβη Οχήματος') ? 'selected' : ''; ?>>Βλάβη Οχήματος</option>
                                <option value="Καθυστέρηση Παράδοσης" <?php echo (isset($_SESSION['old_input']['incident_type']) && $_SESSION['old_input']['incident_type'] == 'Καθυστέρηση Παράδοσης') ? 'selected' : ''; ?>>Καθυστέρηση Παράδοσης</option>
                                <option value="Πρόβλημα με Φορτίο" <?php echo (isset($_SESSION['old_input']['incident_type']) && $_SESSION['old_input']['incident_type'] == 'Πρόβλημα με Φορτίο') ? 'selected' : ''; ?>>Πρόβλημα με Φορτίο</option>
                                <option value="Πρόβλημα με Πελάτη" <?php echo (isset($_SESSION['old_input']['incident_type']) && $_SESSION['old_input']['incident_type'] == 'Πρόβλημα με Πελάτη') ? 'selected' : ''; ?>>Πρόβλημα με Πελάτη</option>
                                <option value="Άλλο" <?php echo (isset($_SESSION['old_input']['incident_type']) && $_SESSION['old_input']['incident_type'] == 'Άλλο') ? 'selected' : ''; ?>>Άλλο</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="incident_date">Ημερομηνία Περιστατικού <span class="text-danger">*</span></label>
                            <input type="date" name="incident_date" id="incident_date" class="form-control"
                                value="<?php echo isset($_SESSION['old_input']['incident_date']) ? $_SESSION['old_input']['incident_date'] : date('Y-m-d'); ?>"
                                max="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="severity">Σοβαρότητα</label>
                            <select name="severity" id="severity" class="form-control">
                                <option value="low" <?php echo (isset($_SESSION['old_input']['severity']) && $_SESSION['old_input']['severity'] == 'low') ? 'selected' : ''; ?>>Χαμηλή</option>
                                <option value="medium" <?php echo (!isset($_SESSION['old_input']['severity']) || (isset($_SESSION['old_input']['severity']) && $_SESSION['old_input']['severity'] == 'medium')) ? 'selected' : ''; ?>>Μέτρια</option>
                                <option value="high" <?php echo (isset($_SESSION['old_input']['severity']) && $_SESSION['old_input']['severity'] == 'high') ? 'selected' : ''; ?>>Υψηλή</option>
                                <option value="critical" <?php echo (isset($_SESSION['old_input']['severity']) && $_SESSION['old_input']['severity'] == 'critical') ? 'selected' : ''; ?>>Κρίσιμη</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="location">Τοποθεσία</label>
                            <input type="text" name="location" id="location" class="form-control"
                                value="<?php echo isset($_SESSION['old_input']['location']) ? htmlspecialchars($_SESSION['old_input']['location']) : ''; ?>"
                                placeholder="Πού συνέβη το περιστατικό;">
                        </div>

                        <div class="form-group">
                            <label for="description">Περιγραφή <span class="text-danger">*</span></label>
                            <textarea name="description" id="description" class="form-control" rows="5"
                                placeholder="Περιγράψτε λεπτομερώς το περιστατικό..." required><?php echo isset($_SESSION['old_input']['description']) ? htmlspecialchars($_SESSION['old_input']['description']) : ''; ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="incident_file">Αρχείο Τεκμηρίωσης (προαιρετικό)</label>
                            <div class="custom-file">
                                <input type="file" name="incident_file" id="incident_file" class="custom-file-input">
                                <label class="custom-file-label" for="incident_file">Επιλέξτε αρχείο...</label>
                            </div>
                            <small class="form-text text-muted">Μπορείτε να ανεβάσετε φωτογραφίες, έγγραφα ή άλλα αρχεία σχετικά με το περιστατικό (μέγιστο μέγεθος: 5MB).</small>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Αποθήκευση Περιστατικού
                            </button>
                            <a href="<?php echo BASE_URL; ?>drivers/incident-history" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Ακύρωση
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Ενημέρωση του ονόματος του επιλεγμένου αρχείου στο input
    document.querySelector('.custom-file-input').addEventListener('change', function(e) {
        var fileName = e.target.files[0].name;
        var nextSibling = e.target.nextElementSibling;
        nextSibling.innerText = fileName;
    });
</script>

<style>
    .page-title {
        margin-bottom: 20px;
    }

    .card {
        margin-bottom: 30px;
        border: none;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        font-weight: 600;
        margin-bottom: 8px;
        display: block;
    }

    .custom-file-label {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .btn {
        padding: 8px 16px;
        margin-right: 10px;
    }
</style>

<?php
// Καθαρισμός των παλιών δεδομένων εισόδου
if (isset($_SESSION['old_input'])) {
    unset($_SESSION['old_input']);
}

// Φόρτωση του footer
include ROOT_DIR . '/src/Views/partials/footer.php';
?>