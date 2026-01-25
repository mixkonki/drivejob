<?php
// src/Views/errors/error.php

// Χρήση ROOT_DIR για σωστή διαδρομή
include ROOT_DIR . '/src/Views/partials/header.php';

// Προεπιλεγμένες τιμές
$title = isset($title) ? $title : 'Σφάλμα';
$message = isset($message) ? $message : 'Υπήρξε ένα σφάλμα κατά την επεξεργασία του αιτήματός σας.';
$code = isset($code) ? $code : 500;
?>

<div class="container error-container">
    <div class="text-center mt-5">
        <?php if ($code): ?>
            <h1 class="display-1"><?php echo htmlspecialchars($code); ?></h1>
        <?php endif; ?>
        <h2 class="mb-4"><?php echo htmlspecialchars($title); ?></h2>
        <p class="lead"><?php echo htmlspecialchars($message); ?></p>

        <?php if (isset($details) && !empty($details)): ?>
            <div class="alert alert-danger mt-3">
                <?php echo htmlspecialchars($details); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($exception) && isset($debug) && $debug): ?>
            <div class="card mt-4 text-left">
                <div class="card-header">
                    <h5 class="mb-0">Λεπτομέρειες σφάλματος</h5>
                </div>
                <div class="card-body">
                    <p><strong>Τύπος:</strong> <?php echo get_class($exception); ?></p>
                    <p><strong>Αρχείο:</strong> <?php echo $exception->getFile(); ?></p>
                    <p><strong>Γραμμή:</strong> <?php echo $exception->getLine(); ?></p>
                    <p><strong>Κωδικός:</strong> <?php echo $exception->getCode(); ?></p>

                    <?php if (method_exists($exception, 'getContext')): ?>
                        <h6 class="mt-3">Context:</h6>
                        <pre><?php print_r($exception->getContext()); ?></pre>
                    <?php endif; ?>

                    <h6 class="mt-3">Stack Trace:</h6>
                    <pre><?php echo $exception->getTraceAsString(); ?></pre>
                </div>
            </div>
        <?php endif; ?>

        <a href="<?php echo BASE_URL; ?>" class="btn btn-primary mt-3">Επιστροφή στην αρχική</a>
    </div>
</div>

<?php
// Χρήση ROOT_DIR για σωστή διαδρομή
include ROOT_DIR . '/src/Views/partials/footer.php';
?>