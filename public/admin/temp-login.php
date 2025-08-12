<?php
session_start();
require_once '../../src/bootstrap.php';

// Get database connection
$container = \Drivejob\Core\Container::getInstance();
$pdo = $container->get('pdo');

$message = '';

if ($_POST) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        // Check admin user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            // Set session
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['role'] = 'admin';
            $_SESSION['email'] = $admin['email'];
            $_SESSION['first_name'] = $admin['first_name'] ?? 'Admin';
            $_SESSION['last_name'] = $admin['last_name'] ?? 'User';

            // Redirect to settings
            header('Location: settings.php');
            exit;
        } else {
            $message = 'Λάθος email ή password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="el">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - DriveJob</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <h2 class="text-center mb-4">
            <i class="fas fa-user-shield me-2"></i>
            Admin Login
        </h2>

        <?php if ($message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email"
                    value="admin@drivejob.gr" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password"
                    value="admin123" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-sign-in-alt me-2"></i>
                Σύνδεση
            </button>
        </form>

        <div class="text-center mt-3">
            <small class="text-muted">
                Temporary login για testing
            </small>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>

</html>