<?php // src/Views/gdpr/delete-confirm.php — route: GET /gdpr/delete (Πακέτο 7)
include ROOT_DIR . '/src/Views/partials/header.php'; ?>
<main>
    <div class="container" style="max-width: 640px; margin: 0 auto; padding: 2rem 1rem;">
        <h1>Διαγραφή Λογαριασμού</h1>

        <?php if ($msg = \Drivejob\Core\Session::get('error_message')): \Drivejob\Core\Session::remove('error_message'); ?>
            <div class="alert alert-danger" style="background:#fdecea; border:1px solid #e57373; color:#b71c1c; padding:12px 16px; border-radius:6px; margin-bottom:16px;">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <div style="background:#fff3e0; border:1px solid #ffb74d; padding:16px 20px; border-radius:8px; margin-bottom:20px; line-height:1.6;">
            <strong>⚠️ Η ενέργεια είναι οριστική και μη αναστρέψιμη.</strong>
            <p style="margin:8px 0 0;">Θα διαγραφούν: το προφίλ σας, οι άδειες και τα πιστοποιητικά σας,
            τα αρχεία σας (φωτογραφίες εγγράφων, βιογραφικό), οι αιτήσεις, οι συνομιλίες
            και τα σκορ ταιριάσματος. Σύμφωνα με το άρθρο 17 GDPR.</p>
            <p style="margin:8px 0 0;">Συμβουλή: κατεβάστε πρώτα αντίγραφο των δεδομένων σας από την
            <a href="<?php echo BASE_URL; ?>gdpr/export">Εξαγωγή δεδομένων</a>.</p>
        </div>

        <form method="POST" action="<?php echo BASE_URL; ?>gdpr/delete">
            <?php echo \Drivejob\Core\CSRF::tokenField(); ?>
            <div class="form-group" style="margin-bottom:16px;">
                <label for="password" style="display:block; margin-bottom:6px; font-weight:600;">
                    Επιβεβαιώστε με τον κωδικό πρόσβασής σας:
                </label>
                <input type="password" id="password" name="password" required
                       style="width:100%; padding:10px 12px; border:1px solid #ccc; border-radius:6px;">
            </div>
            <div style="display:flex; gap:12px;">
                <button type="submit" class="btn-danger"
                        style="background:#c62828; color:#fff; border:0; border-radius:6px; padding:12px 24px; cursor:pointer;"
                        onclick="return confirm('Σίγουρα θέλετε να διαγράψετε ΟΡΙΣΤΙΚΑ τον λογαριασμό σας;');">
                    Οριστική διαγραφή λογαριασμού
                </button>
                <a href="javascript:history.back()" class="btn-secondary"
                   style="background:#eceff1; color:#333; border-radius:6px; padding:12px 24px; text-decoration:none;">
                    Ακύρωση
                </a>
            </div>
        </form>
    </div>
</main>
<?php include ROOT_DIR . '/src/Views/partials/footer.php'; ?>
