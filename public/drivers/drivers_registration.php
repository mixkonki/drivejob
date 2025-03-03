<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Εγγραφή Οδηγού - DriveJob</title>
    <link rel="stylesheet" href="../css/drivers_registration.css">
</head>

<body>
    <div class="container">
        <div class="form-container">
            <div class="logo">
                <a href="../index.php">
                    <img src="../img/logo.png" alt="DriveJob Logo">
                </a>
            </div>
            <h1>Εγγραφείτε</h1>
            <p>Αποκτήστε πρόσβαση στο <br><strong>DriveJobs</strong> μέσα σε 30 δευτερόλεπτα!</p>
            <div>
                <div class="role_user">
                    <img src="../img/driver_icon.png" alt="Οδηγός">
                    <span>Οδηγός</span>
                </div>
                <form action="drivers_register_process.php" method="POST">
                    <input type="email" id="email" name="email" placeholder="Email" required>
                    <input type="text" id="last_name" name="last_name" placeholder="Επώνυμο" required>
                    <input type="text" id="first_name" name="first_name" placeholder="Όνομα" required>
                    <input type="tel" id="phone" name="phone" placeholder="Κινητό τηλέφωνο" required>
                    
                    <div class="password-visibility">
                        <input type="password" id="password" name="password" placeholder="Συνθηματικό" required>
                        <span class="password-toggle" onclick="togglePasswordVisibility()">
                            <img src="../img/eye.png" alt="show/hide password" id="toggleIcon">
                        </span>
                    </div>
            
                    <p class="text_pass">Το συνθηματικό πρέπει να περιέχει:</p>
                    <ul class="password-hint">
                        <li> 8-16 χαρακτήρες</li>
                        <li> 1 κεφαλαίο γράμμα</li>
                        <li> 1 αριθμός</li>
                        <li> 1 ειδικός χαρακτήρας</li>
                    </ul>
                    
                    </div>
                    <hr class="divider">
                    <div class="checkbox-group">
                    <div class="checkbox-group">
                        <label>
                            <input type="checkbox" name="human_check" required>
                            Δεν είμαι ρομπότ
                        </label>
                        <label>
                            <input type="checkbox" name="terms_check" required>
                            Αποδέχομαι τους<a href="#">&nbsp;όρους χρήσης</a>
                        </label>
                    </div>
                    </div>

                    <button type="submit" class="btn-primary">Εγγραφή</button>
                </form>
                <p class="login-link">Έχετε ήδη λογαριασμό; <a href="#">Συνδεθείτε</a></p>
                <hr class="divider">    
                <div class="google-signup">
                    <button class="btn-google">
                        <img src="../img/google_icon.png" alt="Google Logo">
                        Συνδεθείτε με την Google
                    </button>
                </div>
        </div>
        <div class="info-box">
            <p>Με την εγγραφή σας σήμερα, θα έχετε πρόσβαση σε όλα τα προϊόντα DriveJobs. Δεν απαιτείται πιστωτική κάρτα!</p>
        </div>
    </div>

    <script src="../js/drivers_registration.js"></script>
</body>
</html>
