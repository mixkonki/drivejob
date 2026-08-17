<?php

// src/Views/info/contact.php — Σελίδα Επικοινωνίας (route: GET /contact, υποβολή: POST /contact)

use Drivejob\Core\Session;

include ROOT_DIR . '/src/Views/partials/header.php';

$successMessage = Session::get('success_message');
$errorMessage = Session::get('error_message');
$old = Session::get('old_input') ?? [];
Session::remove('success_message');
Session::remove('error_message');
Session::remove('old_input');
?>
<main>
    <div class="container">
        <h1>Επικοινωνία</h1>
        <p>Επικοινωνήστε μαζί μας για οποιαδήποτε ερώτηση ή πληροφορία χρειάζεστε.</p>

        <?php if ($successMessage) : ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($errorMessage) : ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>contact" method="POST">
            <input type="hidden" name="csrf_token" value="<?= Session::get('csrf_token') ?>">

            <label for="name">Όνομα</label>
            <input type="text" id="name" name="name" placeholder="Το όνομά σας" required
                   value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Το email σας" required
                   value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <label for="message">Μήνυμα</label>
            <textarea id="message" name="message" placeholder="Το μήνυμά σας" rows="5" required><?= htmlspecialchars($old['message'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

            <button type="submit" class="btn-primary">Αποστολή</button>
        </form>
    </div>
</main>
<?php include ROOT_DIR . '/src/Views/partials/footer.php'; ?>
