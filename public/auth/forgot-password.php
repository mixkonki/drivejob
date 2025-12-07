<?php
session_start();

// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../../config/database.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    
    if ($email) {
        // Έλεγχος αν υπάρχει ο χρήστης
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            // Δημιουργία token
            $token = bin2hex(random_bytes(32));
            
            // Αποθήκευση token
            $stmt = $pdo->prepare("
                INSERT INTO password_resets (email, token) 
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE token = ?, created_at = NOW()
            ");
            $stmt->execute([$email, $token, $token]);
            
            // Εδώ θα στέλνατε email με το link
            $resetLink = "http://localhost/drivejob/public/auth/reset-password.php?token=" . $token;
            
            $message = "Ένα email με οδηγίες επαναφοράς κωδικού έχει σταλεί στο $email";
            
            // Για testing, εμφάνιση του link
            $message .= "<br><br>Για δοκιμή, χρησιμοποιήστε: <a href='$resetLink'>$resetLink</a>";
        } else {
            $message = "Αν το email υπάρχει στο σύστημα, θα λάβετε οδηγίες επαναφοράς.";
        }
    } else {
        $error = "Παρακαλώ εισάγετε έγκυρο email.";
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Επαναφορά Κωδικού - DriveJob</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Επαναφορά Κωδικού</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success"><?= $message ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Αποστολή Οδηγιών</button>
                            <a href="/drivejob/public/login.php" class="btn btn-link">Επιστροφή στη Σύνδεση</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>