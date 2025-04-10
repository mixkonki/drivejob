<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Εγγραφή Επιχείρησης - DriveJob</title>
    <link rel="stylesheet" href="../css/company_registration.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="logo">
                <a href="../index.php">
                    <img src="../img/logo.png" alt="DriveJob Logo">
                </a>
            </div>
            <h1>Εγγραφή Επιχείρησης</h1>
            <p>Δημιουργήστε το προφίλ της επιχείρησής σας στο <br><strong>DriveJobs</strong> μέσα σε λίγα λεπτά!</p>
            <div>
                <div class="role_user">
                    <img src="../img/company_icon.png" alt="Επιχείρηση">
                    <span>Επιχείρηση</span>
                </div>
                <form action="companies_register_process.php" method="POST">
                    <input type="text" id="company_name" name="company_name" placeholder="Όνομα Εταιρείας" required>
                    <input type="email" id="email" name="email" placeholder="Email Εταιρείας" required>
                    <input type="tel" id="phone" name="phone" placeholder="Τηλέφωνο Εταιρείας" required>
                    <div class="password-visibility">
                        <input type="password" id="password" name="password" placeholder="Συνθηματικό" required>
                        <span class="password-toggle" onclick="togglePasswordVisibility()">
                            <img src="../img/eye.png" alt="show/hide password" id="toggleIcon">
                        </span>
                    </div>
                    <p class="text_pass">Το συνθηματικό πρέπει να περιέχει:</p>
                    <ul class="password-hint">
                        <li>8-16 χαρακτήρες</li>
                        <li>1 κεφαλαίο γράμμα</li>
                        <li>1 αριθμός</li>
                        <li>1 ειδικός χαρακτήρας</li>
                    </ul>
                    <hr class="divider">
                    <div class="checkbox-group">
                        <label>
                            <input type="checkbox" name="human_check" required>
                            Δεν είμαι ρομπότ
                        </label>
                        <label>
                            <input type="checkbox" name="terms_check" required>
                            Αποδέχομαι τους <a href="#">όρους χρήσης</a>
                        </label>
                    </div>
                    <button type="submit" class="btn-primary">Εγγραφή</button>
                </form>
                <p class="login-link">Έχετε ήδη λογαριασμό; <a href="../login.php">Συνδεθείτε</a></p>
            </div>
        </div>
        <div class="info-box">
            <p>Με την εγγραφή σας σήμερα, θα έχετε πρόσβαση σε όλα τα προϊόντα DriveJobs. Δεν απαιτείται πιστωτική κάρτα!</p>
        </div>
    </div>
</body>
</html>
