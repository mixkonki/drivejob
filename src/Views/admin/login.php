<?php
// Admin Login Page
include ROOT_DIR . '/src/Views/partials/header.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/admin-login.css">

<div class="admin-login-container">
    <div class="admin-login-card">
        <div class="admin-login-header">
            <img src="<?php echo BASE_URL; ?>img/logo.png" alt="DriveJob" class="admin-logo">
            <h1>Admin Panel</h1>
            <p>Σύνδεση Διαχειριστή</p>
        </div>

        <?php if (isset($error) && $error): ?>
            <div class="alert alert-error">
                <i class="icon-error"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="icon-success"></i>
                <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <form method="POST" action="<?php echo BASE_URL; ?>admin/login" class="admin-login-form">
            <div class="form-group">
                <label for="email">Email Διαχειριστή</label>
                <div class="input-wrapper">
                    <i class="icon-email"></i>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                        placeholder="admin@drivejob.gr"
                        autocomplete="username">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Κωδικός Πρόσβασης</label>
                <div class="input-wrapper">
                    <i class="icon-password"></i>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        placeholder="Εισάγετε τον κωδικό σας"
                        autocomplete="current-password">
                    <button type="button" class="toggle-password" onclick="togglePassword()">
                        <i class="icon-eye" id="eye-icon"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label class="checkbox-wrapper">
                    <input type="checkbox" name="remember_me">
                    <span class="checkmark"></span>
                    Να με θυμάσαι
                </label>
            </div>

            <button type="submit" class="btn-admin-login">
                <i class="icon-login"></i>
                Σύνδεση
            </button>
        </form>

        <div class="admin-login-footer">
            <p>Προστατευμένη περιοχή - Μόνο για εξουσιοδοτημένους χρήστες</p>
            <a href="<?php echo BASE_URL; ?>" class="back-to-site">
                <i class="icon-arrow-left"></i>
                Επιστροφή στην κύρια σελίδα
            </a>
        </div>
    </div>
</div>

<script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eye-icon');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.className = 'icon-eye-off';
        } else {
            passwordInput.type = 'password';
            eyeIcon.className = 'icon-eye';
        }
    }

    // Auto-focus στο email field
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('email').focus();
    });

    // Security: Disable right-click και F12
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && e.key === 'I')) {
            e.preventDefault();
        }
    });
</script>

<style>
    /* Admin Login Specific Styles */
    .admin-login-container {
        min-height: 100vh;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .admin-login-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        padding: 40px;
        width: 100%;
        max-width: 400px;
        position: relative;
        overflow: hidden;
    }

    .admin-login-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea, #764ba2);
    }

    .admin-login-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .admin-logo {
        width: 60px;
        height: 60px;
        margin-bottom: 15px;
    }

    .admin-login-header h1 {
        color: #333;
        margin: 0 0 5px 0;
        font-size: 24px;
        font-weight: 600;
    }

    .admin-login-header p {
        color: #666;
        margin: 0;
        font-size: 14px;
    }

    .alert {
        padding: 12px 16px;
        border-radius: 6px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .alert-error {
        background: #fee;
        border: 1px solid #fcc;
        color: #c33;
    }

    .alert-success {
        background: #efe;
        border: 1px solid #cfc;
        color: #3c3;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 6px;
        color: #333;
        font-weight: 500;
        font-size: 14px;
    }

    .input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .input-wrapper i {
        position: absolute;
        left: 12px;
        color: #666;
        z-index: 1;
    }

    .input-wrapper input {
        width: 100%;
        padding: 12px 12px 12px 40px;
        border: 2px solid #e1e5e9;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.3s ease;
    }

    .input-wrapper input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .toggle-password {
        position: absolute;
        right: 12px;
        background: none;
        border: none;
        color: #666;
        cursor: pointer;
        padding: 4px;
    }

    .checkbox-wrapper {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        font-size: 14px;
        color: #666;
    }

    .checkbox-wrapper input[type="checkbox"] {
        display: none;
    }

    .checkmark {
        width: 18px;
        height: 18px;
        border: 2px solid #e1e5e9;
        border-radius: 3px;
        position: relative;
        transition: all 0.3s ease;
    }

    .checkbox-wrapper input[type="checkbox"]:checked+.checkmark {
        background: #667eea;
        border-color: #667eea;
    }

    .checkbox-wrapper input[type="checkbox"]:checked+.checkmark::after {
        content: '✓';
        position: absolute;
        top: -2px;
        left: 2px;
        color: white;
        font-size: 12px;
        font-weight: bold;
    }

    .btn-admin-login {
        width: 100%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 14px;
        border-radius: 6px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-admin-login:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .admin-login-footer {
        text-align: center;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #e1e5e9;
    }

    .admin-login-footer p {
        color: #666;
        font-size: 12px;
        margin: 0 0 10px 0;
    }

    .back-to-site {
        color: #667eea;
        text-decoration: none;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: color 0.3s ease;
    }

    .back-to-site:hover {
        color: #764ba2;
    }

    /* Icons (using CSS) */
    .icon-email::before {
        content: '📧';
    }

    .icon-password::before {
        content: '🔒';
    }

    .icon-eye::before {
        content: '👁';
    }

    .icon-eye-off::before {
        content: '🙈';
    }

    .icon-login::before {
        content: '🚪';
    }

    .icon-arrow-left::before {
        content: '←';
    }

    .icon-error::before {
        content: '⚠️';
    }

    .icon-success::before {
        content: '✅';
    }

    /* Responsive */
    @media (max-width: 480px) {
        .admin-login-card {
            padding: 30px 20px;
            margin: 10px;
        }

        .admin-login-header h1 {
            font-size: 20px;
        }
    }
</style>

<?php
include ROOT_DIR . '/src/Views/partials/footer.php';
?>