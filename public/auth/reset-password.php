<?php
session_start();

// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../../config/database.php';

$message = '';
$error = '';
$token = $_GET['token'] ?? $_POST['token'] ?? '';

if (!$token) {
    header('Location: /drivejob/public/auth/forgot-password.php');
    exit;
}

// Έλεγχος token
$stmt = $pdo->prepare("
    SELECT email FROM password_resets 
    WHERE token = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    $error = "Το link επαναφοράς έχει λήξει ή δεν είναι έγκυρο.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (strlen($password) < 6) {
        $error = "Ο κωδικός πρέπει να έχει τουλάχιστον 6 χαρακτήρες.";
    } elseif ($password !== $confirmPassword) {
        $error = "Οι κωδικοί δεν ταιριάζουν.";
    } else {
        // Ενημέρωση κωδικού
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, $reset['email']]);
        
        // Διαγραφή token
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);
        
        $message = "Ο κωδικός σας ενημερώθηκε επιτυχώς!";
        
        // Redirect μετά από 3 δευτερόλεπτα
        header("refresh:3;url=/drivejob/public/login.php");
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Νέος Κωδικός - DriveJob</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Εισάγετε Νέο Κωδικό</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success"><?= $message ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <?php if ($reset && !$message): ?>
                        <form method="POST">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                            <div class="mb-3">
                                <label for="password" class="form-label">Νέος Κωδικός</label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Επιβεβαίωση Κωδικού</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                            </div>
                            <button type="submit" class="btn btn-primary">Αλλαγή Κωδικού</button>
                        </form>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <a href="/drivejob/public/login.php" class="btn btn-link">Επιστροφή στη Σύνδεση</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>