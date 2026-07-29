<?php

/**
 * View για την επιβεβαίωση διαγραφής αγγελίας
 */

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ' . BASE_URL . 'auth/login');
    exit();
}

$userRole = $_SESSION['user_role'];

// Έλεγχος αν υπάρχει η αγγελία
if (!isset($listing) || empty($listing)) {
    header('Location: ' . BASE_URL . 'job-listings');
    exit();
}

// Έλεγχος αν ο χρήστης είναι ο ιδιοκτήτης της αγγελίας
$isOwner = false;
if ($userRole === 'company' && !empty($listing['company_id']) && $_SESSION['user_id'] == $listing['company_id']) {
    $isOwner = true;
} elseif ($userRole === 'driver' && !empty($listing['driver_id']) && $_SESSION['user_id'] == $listing['driver_id']) {
    $isOwner = true;
}

if (!$isOwner) {
    header('Location: ' . BASE_URL . 'job-listings');
    exit();
}

// Τίτλος σελίδας
$pageTitle = 'Διαγραφή Αγγελίας';

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h4>Διαγραφή Αγγελίας</h4>
                </div>
                <div class="card-body">
                    <p class="lead">Είστε βέβαιοι ότι θέλετε να διαγράψετε την αγγελία με τίτλο: <strong><?php echo htmlspecialchars($listing['title']); ?></strong>;</p>
                    <p>Αυτή η ενέργεια δεν μπορεί να αναιρεθεί.</p>

                    <div class="alert alert-warning">
                        <p><strong>Προσοχή:</strong> Η διαγραφή της αγγελίας θα αφαιρέσει όλες τις σχετικές πληροφορίες, συμπεριλαμβανομένων των αιτήσεων και των προσφορών.</p>
                    </div>

                    <form action="<?php echo BASE_URL; ?>job-listings/destroy/<?php echo $listing['id']; ?>" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>" class="btn btn-secondary">Ακύρωση</a>
                            <button type="submit" class="btn btn-danger">Διαγραφή Αγγελίας</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/partials/footer.php';
?>